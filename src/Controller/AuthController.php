<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;

class AuthController extends AbstractController
{
    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
    public function ajaxLogin(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $hasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator)
    {
        $data = $request->request->all();
        $username = $data['_username'] ?? $data['username'] ?? '';
        $password = $data['_password'] ?? $data['password'] ?? '';

        $user = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        if (!$hasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        // Programmatically authenticate and start session
        $userAuthenticator->authenticateUser($user, $authenticator, $request);

        return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('problems_index')]);
    }

    #[Route('/auth/register', name: 'auth_register', methods: ['POST'])]
    public function ajaxRegister(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $hasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator)
    {
        $em = $doctrine->getManager();
        $data = $request->request->all();
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            return new JsonResponse(['success' => false, 'message' => 'Missing fields'], 400);
        }

        $repo = $em->getRepository(User::class);
        if ($repo->findOneBy(['username' => $username]) || $repo->findOneBy(['email' => $email])) {
            return new JsonResponse(['success' => false, 'message' => 'User already exists'], 400);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $em->persist($user);
        $em->flush();

        $userAuthenticator->authenticateUser($user, $authenticator, $request);

        return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('problems_index')]);
    }
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony will intercept this route
        throw new \Exception('This should never be reached.');
    }
}
