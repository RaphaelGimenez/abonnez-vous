<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
	#[Route('/debug/request', name: 'debug_request')]
	public function debugRequest(Request $request): JsonResponse
	{
		return $this->json([
			'scheme' => $request->getScheme(),
			'isSecure' => $request->isSecure(),
			'host' => $request->getHost(),
			'clientIp' => $request->getClientIp(),
			'headers' => [
				'x-forwarded-proto' => $request->headers->get('x-forwarded-proto'),
				'x-forwarded-for' => $request->headers->get('x-forwarded-for'),
				'x-forwarded-host' => $request->headers->get('x-forwarded-host'),
			],
			'session' => [
				'started' => $request->hasSession() && $request->getSession()->isStarted(),
				'id' => $request->hasSession() ? $request->getSession()->getId() : null,
			],
			'cookies' => $request->cookies->all(),
		]);
	}
}
