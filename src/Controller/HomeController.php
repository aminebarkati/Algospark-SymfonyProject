<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\ProblemRepository;
use App\Repository\SubmissionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ProblemRepository $problems, SubmissionRepository $submissions, UserRepository $users, FavoriteRepository $favorites): Response
    {
        $latestProblems = $problems->findLatest(1);
        $featuredProblem = $latestProblems !== [] ? $latestProblems[0] : null;
        $user = $this->getUser();
        $userStats = null;

        if ($user instanceof User) {
            $userId = $user->getId() ?? 0;
            $userStats = [
                'username' => $user->getUserIdentifier(),
                'rating' => $user->getRating(),
                'submissions' => $submissions->countByUserId($userId),
                'solved' => $submissions->countSolvedProblemsByUserId($userId),
                'favorites' => $favorites->countByUserId($userId),
            ];
        }

        return $this->render('home/index.html.twig', [
            'problemCount' => $problems->count([]),
            'submissionCount' => $submissions->count([]),
            'userCount' => $users->count([]),
            'featuredProblem' => $featuredProblem,
            'recentSubmissions' => $submissions->findLatest(5),
            'userStats' => $userStats,
        ]);
    }
}
