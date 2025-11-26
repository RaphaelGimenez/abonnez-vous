<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Exception\Stripe\InvalidLookupKeyException;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;

class StripeService
{
	private StripeClient $stripeClient;
	private EntityManagerInterface $entityManager;

	public function __construct(
		StripeClient $stripeClient,
		EntityManagerInterface $entityManager
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
}
