<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeaderboardController extends AbstractController
{
    #[Route('/leaderboard', name: 'leaderboard_index')]
    public function index(UserRepository $users): Response
    {
        return $this->render('leaderboard/index.html.twig', ['users' => $users->findAllOrderedByRating()]);
    }
}
