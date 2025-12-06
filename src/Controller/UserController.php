<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        return $this->render('user/profile.html.twig');
    }

    #[Route('/profile/edit', name: 'user_edit')]
    public function edit(): Response
    {
        return $this->render('user/edit.html.twig');
    }

    #[Route('/user/api-test', name: 'user_api_test')]
    public function apiTest(): Response
    {
        return $this->render('user/api_test.html.twig');
    }

    #[Route('/getuser', name: 'getuser', methods: ['GET'])]
    public function getUserInfo(): JsonResponse
    {
        // Get the currently authenticated user
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Return user information (excluding sensitive data like password)
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'isVerified' => $user->isVerified(),
        ]);
    }

    #[Route('/user/update', name: 'user_update', methods: ['POST', 'PUT'])]
    public function updateUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        // Get the currently authenticated user
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Get data from request
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        // Update username if provided
        if (isset($data['username']) && !empty($data['username'])) {
            $user->setUsername($data['username']);
        }

        // Update email if provided
        if (isset($data['email']) && !empty($data['email'])) {
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
            }
            $user->setEmail($data['email']);
        }

        // Update password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            // Optionally, you might want to require the current password for verification
            if (isset($data['currentPassword'])) {
                if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                    return $this->json(['error' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
                }
            }

            // Hash and set the new password
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        try {
            // Persist changes to the database
            $entityManager->flush();

            return $this->json([
                'message' => 'User information updated successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                    'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'isVerified' => $user->isVerified(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update user information',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}