<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Exception\Stripe\InvalidLookupKeyException;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;

class StripeService
{
	private StripeClient $stripeClient;
	private EntityManagerInterface $entityManager;

	public function __construct(
		StripeClient $stripeClient,
		EntityManagerInterface $entityManager,
		private UserRepository $userRepository,
		private PlanRepository $planRepository
	) {
		$this->stripeClient = $stripeClient;
		$this->entityManager = $entityManager;
	}

	/**
	 * Creates a Stripe Checkout session for subscription billing
	 *
	 * @param array $options Array containing checkout configuration
	 * @return \Stripe\Checkout\Session
	 */
	public function createCheckoutSession(
		User $user,
		Plan $plan,
		SubscriptionBillingPeriod $billingPeriod
	): \Stripe\Checkout\Session {
		$priceLookupKey = $billingPeriod === SubscriptionBillingPeriod::MONTHLY
			? $plan->getStripeMonthlyLookupKey()
			: $plan->getStripeYearlyLookupKey();

		if (!$priceLookupKey) {
			throw new InvalidLookupKeyException();
		}

		$prices = null;

		try {
			$prices = $this->stripeClient->prices->all([
				'lookup_keys' => [$priceLookupKey],
				'limit' => 1,
			]);
		} catch (\Exception $e) {
			throw new InvalidLookupKeyException();
		}

		if (empty($prices->data)) {
			throw new InvalidLookupKeyException();
		}

		$stripeCustomerId = $this->getOrCreateStripeCustomerId($user);

		$session = $this->stripeClient->checkout->sessions->create([
			'mode' => 'subscription',
			'customer' => $stripeCustomerId,
			'line_items' => [[
				'price' => $prices->data[0]->id,
				'quantity' => 1,
			]],
			'success_url' =>  'http://localhost:8000/subscription/success',
			'cancel_url' =>  'http://localhost:8000/subscription/cancel',
		]);

		return $session;
	}

	public function getOrCreateStripeCustomerId(User $user): string
	{
		if ($user->getStripeCustomerId()) {
			return $user->getStripeCustomerId();
		}

		$customer = $this->stripeClient->customers->create([
			'email' => $user->getEmail(),
			'metadata' => [
				'userId' => $user->getId(),
			],
		]);

		$user->setStripeCustomerId($customer->id);
		$this->entityManager->flush();

		return $customer->id;
	}

	public function verifyWebhookSignature(string $payload, string $signature): \Stripe\Event
	{
		$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET');
		return \Stripe\Webhook::constructEvent(
			$payload,
			$signature,
			$webhookSecret
		);
	}

	/**
	 * Handle checkout session completed event from Stripe webhook
	 * @param \Stripe\Checkout\Session $session
	 */
	public function handleCheckoutSessionCompleted(mixed $session): void
	{
		$customerId = $session->customer;
		$subscriptionId = $session->subscription;
		$metadata = $session->metadata ?? null;

		$planId = $metadata->planId ?? null;
		$billingPeriod = isset($metadata->billingPeriod) ? SubscriptionBillingPeriod::tryFrom($metadata->billingPeriod) : null;

		// Implement logic to create a subscription record in your database
		// linking the user, plan, and Stripe subscription ID.
		$user = $this->userRepository->findOneBy(['stripeCustomerId' => $customerId]);
		$plan = $this->planRepository->find($planId);
		$subscription = new Subscription();
		$subscription->setUser($user);
		$subscription->setPlan($plan);
		$subscription->setStripeSubscriptionId($subscriptionId);
		$subscription->setBillingPeriod($billingPeriod);

		$this->entityManager->persist($subscription);
		$this->entityManager->flush();
	}
}
