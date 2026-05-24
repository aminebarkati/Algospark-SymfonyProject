<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/users', name: 'users_index')]
    public function index(UserRepository $users): Response
    {
        return $this->render('users/index.html.twig', ['users' => $users->findAll()]);
    }
}
