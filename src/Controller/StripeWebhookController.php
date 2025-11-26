<?php

namespace App\Controller;

use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
	public function __construct(
		private readonly StripeService $stripeService
	) {}

	#[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
	public function handleWebhook(Request $request): Response
	{
		$payload = $request->getContent();
		$signature = $request->headers->get('Stripe-Signature');

		$event = null;

		if (!$signature) {
			return new Response('No signature', Response::HTTP_BAD_REQUEST);
		}

		try {
			$event = $this->stripeService->verifyWebhookSignature($payload, $signature);
		} catch (\Exception $e) {
			return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
		}

		switch ($event->type) {
			case 'checkout.session.completed':
				$session = $event->data->object;
				$this->stripeService->handleCheckoutSessionCompleted($session);
				break;
			// Handle other event types as needed
			default:
				// Unexpected event type
				return new Response('Unhandled event type', Response::HTTP_BAD_REQUEST);
		}

		return new Response('OK', Response::HTTP_OK);
	}
}
