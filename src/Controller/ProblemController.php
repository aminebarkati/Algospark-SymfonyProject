<?php
namespace App\Controller;

use App\Repository\LanguageRepository;
use App\Repository\ProblemRepository;
use App\Repository\TestCaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
}
