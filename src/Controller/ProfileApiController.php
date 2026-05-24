<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileApiController extends AbstractController
{
    #[Route('/api/profile/avatar', name: 'api_profile_avatar', methods: ['POST'])]
    public function updateAvatar(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized. Please log in.'], Response::HTTP_UNAUTHORIZED);
        }

        $targetUser = $this->resolveTargetUser($request, $doctrine, $actor);
        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Target user not found.'], Response::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('avatar_file');
        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'Please choose an image to upload.'], Response::HTTP_BAD_REQUEST);
        }

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $extension = strtolower((string) $file->guessExtension() ?: $file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions, true)) {
            return new JsonResponse(['success' => false, 'message' => 'Unsupported image type.'], Response::HTTP_BAD_REQUEST);
        }

        $storageDir = $this->getParameter('kernel.project_dir') . '/storage/imgs';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $filename = sprintf('avatar_%d_%s.%s', $targetUser->getId(), bin2hex(random_bytes(6)), $extension);
        $file->move($storageDir, $filename);

        $targetUser->setAvatarUrl($filename)->touchUpdatedAt();
        $doctrine->getManager()->flush();

        return new JsonResponse(['success' => true, 'message' => 'Avatar updated successfully.', 'avatar_src' => '/storage/imgs/' . rawurlencode($filename)]);
    }

    #[Route('/api/profile/details', name: 'api_profile_details', methods: ['POST'])]
    public function updateProfile(Request $request, ManagerRegistry $doctrine, UserRepository $userRepository): JsonResponse
    {
        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized. Please log in.'], Response::HTTP_UNAUTHORIZED);
        }

        $targetUser = $this->resolveTargetUser($request, $doctrine, $actor);
        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Target user not found.'], Response::HTTP_NOT_FOUND);
        }

        $username = trim((string) $request->request->get('username', ''));
        $email = trim((string) $request->request->get('email', ''));
        $bio = trim((string) $request->request->get('bio', ''));
        if ($username === '' || $email === '') {
            return new JsonResponse(['success' => false, 'message' => 'Username and email are required.'], Response::HTTP_BAD_REQUEST);
        }

        $existingUsername = $userRepository->findByUsername($username);
        if ($existingUsername && $existingUsername->getId() !== $targetUser->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Username already taken.'], Response::HTTP_BAD_REQUEST);
        }
        $existingEmail = $userRepository->findByEmail($email);
        if ($existingEmail && $existingEmail->getId() !== $targetUser->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Email already taken.'], Response::HTTP_BAD_REQUEST);
        }

        $targetUser->setUsername($username)->setEmail($email)->setBio($bio)->touchUpdatedAt();
        $doctrine->getManager()->flush();

        return new JsonResponse(['success' => true, 'message' => 'Profile updated successfully.', 'user' => $this->serializeUser($targetUser)]);
    }

    #[Route('/api/profile/password', name: 'api_profile_password', methods: ['POST'])]
    public function updatePassword(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized. Please log in.'], Response::HTTP_UNAUTHORIZED);
        }

        $targetUser = $this->resolveTargetUser($request, $doctrine, $actor);
        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Target user not found.'], Response::HTTP_NOT_FOUND);
        }

        $isAdminEditingOther = $actor->isAdmin() && $actor->getId() !== $targetUser->getId();
        if (!$isAdminEditingOther) {
            $currentPassword = (string) $request->request->get('current_password', '');
            if (!$hasher->isPasswordValid($targetUser, $currentPassword)) {
                return new JsonResponse(['success' => false, 'message' => 'Current password is invalid.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $newPassword = (string) $request->request->get('new_password', '');
        $confirmPassword = (string) $request->request->get('confirm_password', '');
        if ($newPassword === '' || strlen($newPassword) < 8) {
            return new JsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters.'], Response::HTTP_BAD_REQUEST);
        }
        if ($newPassword !== $confirmPassword) {
            return new JsonResponse(['success' => false, 'message' => 'New password and confirmation do not match.'], Response::HTTP_BAD_REQUEST);
        }

        $targetUser->setPassword($hasher->hashPassword($targetUser, $newPassword))->touchUpdatedAt();
        $doctrine->getManager()->flush();

        return new JsonResponse(['success' => true, 'message' => 'Password updated successfully.']);
    }

    #[Route('/api/profile/role', name: 'api_profile_role', methods: ['POST'])]
    public function updateRole(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $actor = $this->getUser();
        if (!$actor instanceof User || !$actor->isAdmin()) {
            return new JsonResponse(['success' => false, 'message' => 'Forbidden. Admin privileges are required.'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->resolveTargetUser($request, $doctrine, $actor);
        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Target user not found.'], Response::HTTP_NOT_FOUND);
        }

        $isAdmin = (bool) ((int) $request->request->get('is_admin', 0));
        $targetUser->setIsAdmin($isAdmin)->touchUpdatedAt();
        $doctrine->getManager()->flush();

        return new JsonResponse(['success' => true, 'message' => 'Role updated successfully.', 'user' => $this->serializeUser($targetUser)]);
    }

    #[Route('/api/profile/deduct-points', name: 'api_profile_deduct_points', methods: ['POST'])]
    public function deductPoints(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $actor = $this->getUser();
        if (!$actor instanceof User || !$actor->isAdmin()) {
            return new JsonResponse(['success' => false, 'message' => 'Forbidden. Admin privileges are required.'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->resolveTargetUser($request, $doctrine, $actor);
        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Target user not found.'], Response::HTTP_NOT_FOUND);
        }

        $points = max(1, (int) $request->request->get('points', 0));
        $targetUser->setRating(max(0, $targetUser->getRating() - $points))->touchUpdatedAt();
        $doctrine->getManager()->flush();

        return new JsonResponse(['success' => true, 'message' => 'Points deducted successfully.', 'user' => $this->serializeUser($targetUser)]);
    }

    #[Route('/api/profile/delete-account', name: 'api_profile_delete_account', methods: ['POST'])]
    public function deleteAccount(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $actor = $this->getUser();
        if (!$actor instanceof User || !$actor->isAdmin()) {
            return new JsonResponse(['success' => false, 'message' => 'Forbidden. Admin privileges are required.'], Response::HTTP_FORBIDDEN);
        }

        $targetUser = $this->resolveTargetUser($request, $doctrine, $actor);
        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Target user not found.'], Response::HTTP_NOT_FOUND);
        }

        $em = $doctrine->getManager();
        $em->remove($targetUser);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Account deleted successfully.']);
    }

    private function resolveTargetUser(Request $request, ManagerRegistry $doctrine, User $actor): ?User
    {
        $userId = (int) $request->request->get('target_user_id', $actor->getId() ?? 0);
        if ($userId <= 0) {
            return null;
        }
        if (!$actor->isAdmin() && $userId !== ($actor->getId() ?? 0)) {
            return null;
        }

        return $doctrine->getRepository(User::class)->find($userId);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'bio' => $user->getBio(),
            'rating' => $user->getRating(),
            'is_admin' => $user->isAdmin(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt() ? $user->getUpdatedAt()->format('Y-m-d H:i:s') : '',
        ];
    }
}
