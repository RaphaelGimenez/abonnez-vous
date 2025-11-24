<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class HelloController extends AbstractController
{
    #[Route('/hello', name: 'app_hello')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/HelloController.php',
        ]);
    }

    #[Route('/hello/me', name: 'app_hello_greet')]
    #[IsGranted('ROLE_USER')]
    public function greet(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'greeting' => sprintf('Hello, %s!', $user->getEmail()),
        ]);
    }
}
