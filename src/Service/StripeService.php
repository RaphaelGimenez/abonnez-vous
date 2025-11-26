<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Exception\Stripe\InvalidLookupKeyException;
use Stripe\StripeClient;

\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

class StripeService
{
	private StripeClient $stripeClient;
	public function __construct(
		StripeClient $stripeClient
	) {
		$this->stripeClient = $stripeClient;
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

		$session = $this->stripeClient->checkout->sessions->create([
			'mode' => 'subscription',
			'customer_email' => $user->getEmail(),
			'customer' => $user->getStripeCustomerId(),
			'line_items' => [[
				'price' => $prices->data[0]->id,
				'quantity' => 1,
			]],
			'success_url' =>  '/subscription/success',
			'cancel_url' =>  '/subscription/cancel',
		]);

		return $session;
	}
}
