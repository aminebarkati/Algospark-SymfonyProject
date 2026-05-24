<?php

declare(strict_types=1);

use App\Entity\Language;
use App\Entity\Problem;
use App\Entity\Submission;
use App\Entity\TestCase;
use App\Entity\VerdictStatus;
use App\Kernel;
use App\Repository\ProblemRepository;
use App\Repository\TestCaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$projectDir = dirname(__DIR__);
$dotenv = new Dotenv();
if (is_file($projectDir . '/.env')) {
	$dotenv->usePutenv()->loadEnv($projectDir . '/.env');
}

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "worker.php must be run from the command line.\n");
	exit(1);
}

const POLL_INTERVAL_SECONDS = 3;
const MAX_ITERATIONS = 0; // 0 = run forever

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
$submissionRepo = $em->getRepository(Submission::class);
/** @var TestCaseRepository $testCaseRepo */
$testCaseRepo = $em->getRepository(TestCase::class);
/** @var ProblemRepository $problemRepo */
$problemRepo = $em->getRepository(Problem::class);
$verdictRepo = $em->getRepository(VerdictStatus::class);

$baseDir = dirname(__DIR__) . '/storage/submission_files';
if (!is_dir($baseDir)) {
	mkdir($baseDir, 0755, true);
}

log_msg('Judge worker started. PID: ' . getmypid());

$iterations = 0;

while (true) {
	$submission = $submissionRepo->createQueryBuilder('s')
		->leftJoin('s.verdict', 'v')->addSelect('v')
		->join('s.user', 'u')->addSelect('u')
		->join('s.problem', 'p')->addSelect('p')
		->join('s.language', 'l')->addSelect('l')
		->andWhere('s.verdict IS NULL')
		->orderBy('s.submittedAt', 'ASC')
		->addOrderBy('s.id', 'ASC')
		->setMaxResults(1)
		->getQuery()
		->getOneOrNullResult();

	if (!$submission instanceof Submission) {
		sleep(POLL_INTERVAL_SECONDS);
		continue;
	}

	$em->beginTransaction();
	try {
		/** @var Submission $submission */
		$submission = $em->find(Submission::class, $submission->getId());
		if (!$submission instanceof Submission) {
			$em->rollback();
			continue;
		}

		$result = judgeSubmission($submission, $baseDir, $verdictRepo, $testCaseRepo);

		$submission->setVerdict($result['verdict'])
			->setExecutionTimeMs($result['execution_time_ms'])
			->setMemoryUsedMb(null)
			->setPassedTests($result['passed_tests'])
			->setTotalTests($result['total_tests'])
			->setJudgedAt(new DateTimeImmutable());

		$em->flush();

		$problemRepo->updateJudgingStats($submission->getProblem()->getId() ?? 0, $result['verdict_code'] === 'AC');
		$em->commit();

		log_msg(sprintf(
			'Submission #%d -> %s (%d/%d tests, %d ms)',
			$submission->getId() ?? 0,
			$result['verdict_code'],
			$result['passed_tests'],
			$result['total_tests'],
			$result['execution_time_ms']
		));
	} catch (Throwable $e) {
		if ($em->getConnection()->isTransactionActive()) {
			$em->rollback();
		}
		log_msg('Worker error: ' . $e->getMessage());
	}

	$iterations++;
	if (MAX_ITERATIONS > 0 && $iterations >= MAX_ITERATIONS) {
		log_msg('Reached MAX_ITERATIONS. Exiting.');
		break;
	}
}

/**
 * @return array{verdict: VerdictStatus, verdict_code: string, execution_time_ms: int, passed_tests: int, total_tests: int}
 */
function judgeSubmission(Submission $submission, string $baseDir, $verdictRepo, $testCaseRepo): array
{
	$workDir = sys_get_temp_dir() . '/algospark_' . $submission->getId() . '_' . time();
	mkdir($workDir, 0755, true);

	try {
		$language = $submission->getLanguage();
		$problem = $submission->getProblem();
		$isJava = strtolower($language->getName()) === 'java';

		$srcPath = $baseDir . '/' . ($submission->getUser()->getId() ?? 0) . '/' . ($submission->getId() ?? 0) . (string) ($language->getFileExtension() ?: '.txt');
		if (!file_exists($srcPath)) {
			throw new RuntimeException('Source file not found: ' . $srcPath);
		}

		$javaMainClass = null;
		if ($isJava) {
			$javaSource = (string) file_get_contents($srcPath);
			[$localSrc, $javaMainClass] = prepareJavaSourceFile($javaSource, $workDir);
		} else {
			$localSrc = $workDir . '/Main' . (string) ($language->getFileExtension() ?: '.txt');
			copy($srcPath, $localSrc);
		}

		$verdicts = loadVerdicts($verdictRepo);
		$tests = array_map(static fn(TestCase $testCase): array => [
			'input' => $testCase->getInput(),
			'expected_output' => $testCase->getExpectedOutput(),
			'is_sample' => $testCase->isSample(),
		], $testCaseRepo->findByProblemId($problem->getId() ?? 0));

		$result = [
			'verdict' => $verdicts['AC'],
			'verdict_code' => 'AC',
			'execution_time_ms' => 0,
			'passed_tests' => 0,
			'total_tests' => count($tests),
		];

		if ($result['total_tests'] === 0) {
			$result['verdict'] = $verdicts['RE'];
			$result['verdict_code'] = 'RE';
			return $result;
		}

		$maxTimeMs = 0;
		$binaryPath = null;

		if (needsCompile($language->getName())) {
			$compileResult = compileLanguage($language->getName(), (string) $language->getCompilerCommand(), $localSrc, $workDir);
			if ($compileResult['exit_code'] !== 0) {
				$result['verdict'] = $verdicts['CE'];
				$result['verdict_code'] = 'CE';
				$result['execution_time_ms'] = $compileResult['time_ms'];
				return $result;
			}
			$binaryPath = $isJava ? $workDir : $workDir . '/Main';
		}

		foreach ($tests as $testCase) {
			$inputFile = $workDir . '/input.txt';
			file_put_contents($inputFile, (string) ($testCase['input'] ?? ''));

			$runResult = runCode(
				$language->getName(),
				$localSrc,
				$binaryPath,
				$workDir,
				$inputFile,
				(int) ($problem->getTimeLimitMs() ?? 1000),
				$javaMainClass
			);

			$maxTimeMs = max($maxTimeMs, $runResult['time_ms']);

			if ($runResult['timed_out']) {
				$result['verdict'] = $verdicts['TLE'];
				$result['verdict_code'] = 'TLE';
				$result['execution_time_ms'] = $maxTimeMs;
				return $result;
			}

			if ($runResult['exit_code'] !== 0) {
				$result['verdict'] = $verdicts['RE'];
				$result['verdict_code'] = 'RE';
				$result['execution_time_ms'] = $maxTimeMs;
				return $result;
			}

			$actual = normalizeOutput($runResult['stdout']);
			$expected = normalizeOutput((string) ($testCase['expected_output'] ?? ''));
			if ($actual !== $expected) {
				$result['verdict'] = $verdicts['WA'];
				$result['verdict_code'] = 'WA';
				$result['execution_time_ms'] = $maxTimeMs;
				return $result;
			}

			$result['passed_tests']++;
		}

		$result['execution_time_ms'] = $maxTimeMs;
		return $result;
	} finally {
		cleanUp($workDir);
	}
}

function loadVerdicts($verdictRepo): array
{
	$codes = ['AC', 'WA', 'TLE', 'MLE', 'RE', 'CE', 'PENDING'];
	$verdicts = [];
	foreach ($codes as $code) {
		$verdict = $verdictRepo->findOneBy(['verdict' => $code]);
		if (!$verdict instanceof VerdictStatus) {
			throw new RuntimeException('Missing verdict row: ' . $code);
		}
		$verdicts[$code] = $verdict;
	}

	return $verdicts;
}

function needsCompile(string $lang): bool
{
	return in_array(strtolower($lang), ['c', 'c++', 'java'], true);
}

function compileLanguage(string $lang, string $compilerCmd, string $srcPath, string $workDir): array
{
	$lang = strtolower($lang);

	if ($lang === 'java') {
		$cmd = sprintf('%s %s 2>&1', $compilerCmd, escapeshellarg($srcPath));
	} else {
		$binary = $workDir . '/Main';
		$cmd = sprintf('%s %s -o %s 2>&1', $compilerCmd, escapeshellarg($srcPath), escapeshellarg($binary));
	}

	$startTime = microtime(true);
	exec($cmd, $outputLines, $exitCode);

	return [
		'exit_code' => $exitCode,
		'output' => implode("\n", $outputLines),
		'time_ms' => (int) round((microtime(true) - $startTime) * 1000),
	];
}

function runCode(string $lang, string $srcPath, ?string $binaryPath, string $workDir, string $inputFile, int $timeLimitMs, ?string $javaMainClass = null): array
{
	$lang = strtolower($lang);
	$timeLimitSec = max(1, (int) ceil($timeLimitMs / 1000));

	switch ($lang) {
		case 'c':
		case 'c++':
			$runCmd = escapeshellarg((string) $binaryPath);
			break;
		case 'java':
			$runCmd = 'java -cp ' . escapeshellarg((string) $binaryPath) . ' ' . escapeshellarg($javaMainClass ?? 'Main');
			break;
		case 'python':
			$runCmd = 'python3 ' . escapeshellarg($srcPath);
			break;
		case 'javascript':
			$runCmd = 'node ' . escapeshellarg($srcPath);
			break;
		default:
			throw new RuntimeException('Unsupported language: ' . $lang);
	}

	$stdoutFile = $workDir . '/stdout.txt';
	$stderrFile = $workDir . '/stderr.txt';

	$startTime = microtime(true);
	$fullCmd = sprintf(
		'timeout %ds %s < %s > %s 2> %s',
		$timeLimitSec,
		$runCmd,
		escapeshellarg($inputFile),
		escapeshellarg($stdoutFile),
		escapeshellarg($stderrFile)
	);
	exec($fullCmd, $ignored, $exitCode);

	return [
		'stdout' => file_exists($stdoutFile) ? (string) file_get_contents($stdoutFile) : '',
		'stderr' => file_exists($stderrFile) ? (string) file_get_contents($stderrFile) : '',
		'exit_code' => $exitCode,
		'timed_out' => $exitCode === 124,
		'time_ms' => (int) round((microtime(true) - $startTime) * 1000),
	];
}

function normalizeOutput(string $output): string
{
	$output = str_replace("\r\n", "\n", $output);
	$lines = array_map('rtrim', explode("\n", $output));

	while ($lines !== [] && end($lines) === '') {
		array_pop($lines);
	}

	return implode("\n", $lines);
}

/**
 * @return array{0: string, 1: string}
 */
function prepareJavaSourceFile(string $sourceCode, string $workDir): array
{
	$packageName = detectJavaPackageName($sourceCode);
	$mainClass = detectJavaMainClassName($sourceCode) ?? 'Main';
	$relativePath = ($packageName !== null ? str_replace('.', '/', $packageName) . '/' : '') . $mainClass . '.java';
	$localSrc = $workDir . '/' . $relativePath;
	$localDir = dirname($localSrc);
	if (!is_dir($localDir)) {
		mkdir($localDir, 0755, true);
	}

	file_put_contents($localSrc, $sourceCode);

	return [$localSrc, $packageName !== null ? $packageName . '.' . $mainClass : $mainClass];
}

function detectJavaPackageName(string $sourceCode): ?string
{
	if (preg_match('/^\s*package\s+([A-Za-z_][A-Za-z0-9_\.]*?)\s*;/m', $sourceCode, $matches) === 1) {
		return $matches[1];
	}

	return null;
}

function detectJavaMainClassName(string $sourceCode): ?string
{
	if (preg_match('/^\s*public\s+(?:final\s+|abstract\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)\b/m', $sourceCode, $matches) === 1) {
		return $matches[1];
	}

	if (preg_match('/^\s*(?:final\s+|abstract\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)\b/m', $sourceCode, $matches) === 1) {
		return $matches[1];
	}

	return null;
}

function cleanUp(string $dir): void
{
	if (!is_dir($dir)) {
		return;
	}

	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}

	rmdir($dir);
}

function log_msg(string $message): void
{
	echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}
