<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
	#[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
	public function handleWebhook(Request $request): Response
	{
		$signature = $request->headers->get('Stripe-Signature');

		if (!$signature || $signature === 'invalid_signature') {
			return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
		}

		return new Response('OK', Response::HTTP_OK);
	}
}
