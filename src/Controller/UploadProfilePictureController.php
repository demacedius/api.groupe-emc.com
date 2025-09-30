<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;

class UploadProfilePictureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    #[Route('/api/users/{id}/profile_picture', name: 'user_profile_picture_upload', methods: ['POST'])]
    public function upload(Request $request, int $id): JsonResponse
    {
        // Check if user is authenticated
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        // Find the user by ID
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Check if user can upload (own picture or admin)
        if ($currentUser->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        try {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('imageFile');

            if (!$uploadedFile) {
                throw new BadRequestHttpException('"imageFile" is required.');
            }

            $user->setImageFile($uploadedFile);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Return a success response with the image URL
            return $this->json([
                'success' => true,
                'message' => 'Profile picture uploaded successfully',
                'imageUrl' => $user->getProfilePicture(),
                'user' => [
                    'id' => $user->getId(),
                    'profilePicture' => $user->getProfilePicture()
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}