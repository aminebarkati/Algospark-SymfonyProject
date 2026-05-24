<?php
namespace App\Controller;

use App\Entity\Problem;
use App\Entity\TestCase;
use App\Repository\ProblemRepository;
use App\Repository\LanguageRepository;
use App\Repository\TestCaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

class ProblemController extends AbstractController
{
    #[Route('/problems', name: 'problems_index')]
    public function index(ProblemRepository $repo): Response
    {
        $problems = $repo->findLatest(20);

        return $this->render('problem/index.html.twig', ['problems' => $problems]);
    }

    #[Route('/problems/{id}', name: 'problems_show', requirements: ['id' => '\\d+'])]
    public function show(int $id, ProblemRepository $problemRepository, TestCaseRepository $testCaseRepository, LanguageRepository $languageRepository): Response
    {
        $problem = $problemRepository->find($id);

        if (!$problem) {
            throw $this->createNotFoundException('Problem not found');
        }

        $testCases = $testCaseRepository->findSampleByProblemId($id);
        $languages = $languageRepository->findEnabledLanguages();

        return $this->render('problem/show.html.twig', [
            'problem' => $problem,
            'testCases' => $testCases,
            'languages' => $languages,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/problems/new', name: 'admin_problem_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProblemRepository $problemRepository, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $title = trim((string) $request->request->get('title', ''));
            $description = trim((string) $request->request->get('description', ''));
            $category = trim((string) $request->request->get('category', 'General'));
            $difficulty = (int) $request->request->get('difficulty', 900);
            $timeLimitMs = (int) $request->request->get('time_limit_ms', 1000);
            $memoryLimitMb = (int) $request->request->get('memory_limit_mb', 256);

            if ($title === '' || $description === '') {
                return $this->renderProblemForm(['error' => 'Title and description are required.']);
            }

            if ($problemRepository->findOneBy(['title' => $title])) {
                return $this->renderProblemForm(['error' => 'A problem with this title already exists.']);
            }

            $inputs = (array) $request->request->all('test_input');
            $expectedOutputs = (array) $request->request->all('test_expected_output');
            $sampleFlags = (array) $request->request->all('test_is_sample');

            $testCases = [];
            foreach ($inputs as $index => $input) {
                $input = trim((string) $input);
                $expectedOutput = trim((string) ($expectedOutputs[$index] ?? ''));
                $isSample = (string) ($sampleFlags[$index] ?? '0') === '1';
                if ($input === '' && $expectedOutput === '') {
                    continue;
                }
                if ($input === '' || $expectedOutput === '') {
                    return $this->renderProblemForm(['error' => 'Each test case needs both input and expected output.']);
                }
                $testCases[] = [
                    'input' => $input,
                    'expected_output' => $expectedOutput,
                    'is_sample' => $isSample,
                ];
            }

            if ($testCases === []) {
                return $this->renderProblemForm(['error' => 'Add at least one test case.']);
            }

            $problem = new Problem();
            $problem->setTitle($title)
                ->setDescription($description)
                ->setCategory($category !== '' ? $category : 'General')
                ->setDifficulty(max(1, $difficulty))
                ->setTimeLimitMs(max(1, $timeLimitMs))
                ->setMemoryLimitMb(max(1, $memoryLimitMb))
                ->setSuccessCount(0)
                ->setTotalAttempts(0)
                ->setAcceptanceRate(0);

            $em = $entityManager;
            $em->persist($problem);
            $em->flush();

            foreach ($testCases as $row) {
                $testCase = new TestCase();
                $testCase->setProblem($problem)
                    ->setInput($row['input'])
                    ->setExpectedOutput($row['expected_output'])
                    ->setSample((bool) $row['is_sample']);
                $em->persist($testCase);
            }

            $em->flush();

            return $this->redirectToRoute('problems_show', ['id' => $problem->getId()]);
        }

        return $this->renderProblemForm();
    }

    private function renderProblemForm(array $data = []): Response
    {
        return $this->render('problem/new.html.twig', [
            'error' => $data['error'] ?? null,
        ]);
    }
}
