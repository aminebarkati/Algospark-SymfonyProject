<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    #[Route('/users', name: 'users_index')]
    public function index(UserRepository $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('users/index.html.twig', ['users' => $users->findAll()]);
    }
}
