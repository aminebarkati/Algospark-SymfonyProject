<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile_show_current')]
    public function edit(Request $request, UserRepository $users): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->render('profile/edit.html.twig', ['targetUser' => null, 'actorUser' => null]);
        }

        $actorUser = $users->find($currentUser->getId());
        if (!$actorUser) {
            return $this->render('profile/edit.html.twig', ['targetUser' => null, 'actorUser' => null]);
        }
        $targetUser = $actorUser;
        $requestedTargetId = (int) $request->query->get('user_id', $actorUser?->getId() ?? 0);
        if ($actorUser && $actorUser->isAdmin() && $requestedTargetId > 0) {
            $candidate = $users->find($requestedTargetId);
            if ($candidate) {
                $targetUser = $candidate;
            }
        }

        return $this->render('profile/edit.html.twig', [
            'actorUser' => $actorUser,
            'targetUser' => $targetUser,
        ]);
    }

    #[Route('/users/{username}', name: 'profile_show')]
    public function show(string $username, UserRepository $users, FavoriteRepository $favorites): Response
    {
        $targetUser = $users->findByUsername($username);
        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }

        $currentUser = $this->getUser();
        $isFavourite = false;
        if ($currentUser instanceof User) {
            $isFavourite = null !== $favorites->checkFavoriteById($currentUser->getId() ?? 0, $targetUser->getId() ?? 0);
        }

        return $this->render('profile/show.html.twig', [
            'targetUser' => $targetUser,
            'currentUser' => $currentUser,
            'isFavourite' => $isFavourite,
        ]);
    }
}
