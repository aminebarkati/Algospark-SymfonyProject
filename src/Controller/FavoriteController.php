<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\FavoriteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FavoriteController extends AbstractController
{
    #[Route('/api/favorites', name: 'api_favorite_add', methods: ['POST'])]
    public function add(Request $request, FavoriteRepository $favorites, ManagerRegistry $doctrine): JsonResponse
    {
        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized. Please log in.'], 401);
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $favoriteUserId = (int) ($payload['favorite_user_id'] ?? 0);
        if ($favoriteUserId <= 0 || $favoriteUserId === ($actor->getId() ?? 0)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid favorite user.'], 400);
        }

        if ($favorites->checkFavoriteById($actor->getId() ?? 0, $favoriteUserId)) {
            return new JsonResponse(['success' => true, 'message' => 'Already favorite.']);
        }

        $favorites->addFavorite($actor->getId() ?? 0, $favoriteUserId);
        return new JsonResponse(['success' => true, 'message' => 'Favorite added.']);
    }

    #[Route('/api/favorites', name: 'api_favorite_delete', methods: ['DELETE'])]
    public function delete(Request $request, FavoriteRepository $favorites): JsonResponse
    {
        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized. Please log in.'], 401);
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $favoriteUserId = (int) ($payload['favorite_user_id'] ?? 0);
        if ($favoriteUserId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid favorite user.'], 400);
        }

        $favorites->deleteByUserId($actor->getId() ?? 0, $favoriteUserId);
        return new JsonResponse(['success' => true, 'message' => 'Favorite removed.']);
    }
}
