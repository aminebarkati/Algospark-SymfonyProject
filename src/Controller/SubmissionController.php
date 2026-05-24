<?php
namespace App\Controller;

use App\Entity\Language;
use App\Entity\Problem;
use App\Entity\Submission;
use App\Entity\User;
use App\Repository\SubmissionRepository;
use App\Repository\TestCaseRepository;
use App\Repository\ProblemRepository;
use App\Repository\LanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubmissionController extends AbstractController
{
    #[Route('/submissions', name: 'submissions_index')]
    public function index(SubmissionRepository $submissions): Response
    {
        return $this->render('submission/index.html.twig', [
            'submissions' => $submissions->findLatest(20),
            'currentUser' => $this->getUser(),
        ]);
    }

    #[Route('/problems/{id}/submit', name: 'submission_create', methods: ['POST'])]
    public function create(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized. Please log in.'], Response::HTTP_UNAUTHORIZED);
        }

        $problem = $doctrine->getRepository(Problem::class)->find($id);
        if (!$problem) {
            return new JsonResponse(['success' => false, 'message' => 'Problem not found.'], Response::HTTP_NOT_FOUND);
        }

        $languageId = (int) $request->request->get('language_id', 0);
        $language = $doctrine->getRepository(Language::class)->find($languageId);
        if (!$language instanceof Language) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid language.'], Response::HTTP_BAD_REQUEST);
        }

        $code = trim((string) $request->request->get('code', ''));
        if ($code === '' && $request->files->has('solution_file')) {
            $file = $request->files->get('solution_file');
            if ($file) {
                $code = (string) file_get_contents($file->getPathname());
            }
        }
        if ($code === '') {
            return new JsonResponse(['success' => false, 'message' => 'Please enter code or attach a solution file.'], Response::HTTP_BAD_REQUEST);
        }

        $submission = new Submission();
        $submission->setUser($currentUser)
            ->setProblem($problem)
            ->setLanguage($language)
            ->setSubmittedAt(new \DateTimeImmutable())
            ->setPassedTests(0)
            ->setTotalTests(null);

        $em = $doctrine->getManager();
        $em->persist($submission);
        $em->flush();

        $storageDir = $this->getParameter('kernel.project_dir') . '/storage/submission_files/' . $currentUser->getId();
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $filePath = $storageDir . '/' . $submission->getId() . (string) ($language->getFileExtension() ?: '.txt');
        file_put_contents($filePath, $code);

        return new JsonResponse([
            'success' => true,
            'message' => 'Solution submitted successfully! You can check the verdict status on the submissions page.',
            'redirect' => $this->generateUrl('problems_show', ['id' => $id]),
        ]);
    }

    #[Route('/api/submissions/filter', name: 'submissions_filter')]
    public function filter(Request $request, SubmissionRepository $submissions): JsonResponse
    {
        $type = (string) $request->query->get('type', 'all');
        $currentUser = $this->getUser();

        if ($type === 'me') {
            if (!$currentUser instanceof User) {
                return new JsonResponse(['error' => 'Not logged in'], Response::HTTP_UNAUTHORIZED);
            }
            $rows = $submissions->findByUserId($currentUser->getId() ?? 0);
        } elseif ($type === 'favourites') {
            if (!$currentUser instanceof User) {
                return new JsonResponse(['error' => 'Not logged in'], Response::HTTP_UNAUTHORIZED);
            }
            $rows = $submissions->findAllFavoritesByUserId($currentUser->getId() ?? 0);
        } else {
            $rows = $submissions->findLatest(20);
        }

        $data = array_map(static function (Submission $submission): array {
            $user = $submission->getUser();
            $problem = $submission->getProblem();
            $language = $submission->getLanguage();
            $verdict = $submission->getVerdict();

            return [
                'id' => $submission->getId(),
                'submitted_at' => $submission->getSubmittedAt()->format(DATE_ATOM),
                'username' => $user->getUsername(),
                'title' => $problem->getTitle(),
                'category' => $problem->getCategory(),
                'language_name' => $language->getName(),
                'verdict' => $verdict?->getVerdict() ?? 'PENDING',
                'display_name' => $verdict?->getDisplayName() ?? 'Pending',
                'color_code' => $verdict?->getColorCode() ?? '#6c757d',
                'difficulty' => $problem->getDifficulty(),
                'execution_time_ms' => $submission->getExecutionTimeMs(),
                'memory_used_mb' => $submission->getMemoryUsedMb(),
                'pid' => $problem->getId(),
            ];
        }, $rows);

        return new JsonResponse(['submissions' => $data]);
    }

    #[Route('/api/problems/{id}/recent-submissions', name: 'problem_recent_submissions')]
    public function recent(int $id, SubmissionRepository $submissions): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized. Please log in.'], Response::HTTP_UNAUTHORIZED);
        }

        $rows = $submissions->findRecentByUserAndProblem($currentUser->getId() ?? 0, $id, 5);
        return new JsonResponse(['success' => true, 'message' => 'Recent submissions loaded.', 'items' => $rows]);
    }
}
