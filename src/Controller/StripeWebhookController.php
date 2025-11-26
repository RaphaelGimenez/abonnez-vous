<?php

namespace App\Controller;

use App\Service\StripeService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
	public function __construct(
		private readonly StripeService $stripeService,
		private readonly LoggerInterface $logger
	) {}

	#[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
	public function handleWebhook(Request $request): Response
	{
		$payload = $request->getContent();
		$signature = $request->headers->get('Stripe-Signature');

		$event = null;

		$this->logger->info('Received Stripe webhook', ['payload' => $payload, 'signature' => $signature]);
		if (!$signature) {
			$this->logger->warning('No Stripe signature found in the request headers');
			return new Response('No signature', Response::HTTP_BAD_REQUEST);
		}

		try {
			$this->logger->info('Verifying Stripe webhook signature');
			$event = $this->stripeService->verifyWebhookSignature($payload, $signature);
		} catch (\Exception $e) {
			$this->logger->error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
			return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
		}

		try {
			$this->logger->info('Processing Stripe webhook event', ['event' => $event->type]);
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
		} catch (\Exception $e) {
			$this->logger->error('Error processing Stripe webhook', ['error' => $e->getMessage()]);
			return new Response('Error processing webhook: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}
}
