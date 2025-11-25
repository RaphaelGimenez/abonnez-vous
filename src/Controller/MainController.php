<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

final class MainController extends AbstractController
{
	#[Route('/', name: 'app_main')]
	public function index(): Response
	{
		/** @var ?User $user */
		$user = $this->getUser();
		$currentSubscription = $user?->getSubscription();
		$currentPlan = $currentSubscription?->getPlan();

		return $this->render('index.html.twig', [
			'controller_name' => 'MainController',
			'user' => $user,
			'currentSubscription' => $currentSubscription,
			'currentPlan' => $currentPlan,
		]);
	}
}
