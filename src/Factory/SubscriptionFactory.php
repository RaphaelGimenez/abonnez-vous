<?php

namespace App\Factory;

use App\Entity\Subscription;
use App\Enum\SubscriptionBillingPeriod;
use App\Enum\SubscriptionStatus;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Subscription>
 */
final class SubscriptionFactory extends PersistentProxyObjectFactory
{
	/**
	 * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
	 *
	 * @todo inject services if required
	 */
	public function __construct() {}

	public static function class(): string
	{
		return Subscription::class;
	}

	/**
	 * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
	 *
	 * @todo add your default values here
	 */
	protected function defaults(): array|callable
	{
		return [
			'billingPeriod' => self::faker()->randomElement(SubscriptionBillingPeriod::cases()),
			'plan' => PlanFactory::new(),
			'status' => self::faker()->randomElement(SubscriptionStatus::cases()),
			'user' => UserFactory::new(),
			'stripeSubscriptionId' => self::faker()->uuid(),
		];
	}

	/**
	 * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
	 */
	protected function initialize(): static
	{
		return $this
			// ->afterInstantiate(function(Subscription $subscription): void {})
		;
	}
}
