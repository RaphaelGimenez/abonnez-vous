<?php

namespace App\Factory;

use App\Entity\Plan;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Plan>
 */
final class PlanFactory extends PersistentProxyObjectFactory
{
	/**
	 * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
	 *
	 * @todo inject services if required
	 */
	public function __construct() {}

	public static function class(): string
	{
		return Plan::class;
	}

	/**
	 * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
	 *
	 * @todo add your default values here
	 */
	protected function defaults(): array|callable
	{
		return [
			'monthlyPrice' => self::faker()->randomNumber(),
			'name' => self::faker()->text(255),
			'yearlyPrice' => self::faker()->randomNumber(),
			'stripeMonthlyLookupKey' => self::faker()->text(255),
			'stripeYearlyLookupKey' => self::faker()->text(255),
		];
	}

	/**
	 * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
	 */
	protected function initialize(): static
	{
		return $this
			// ->afterInstantiate(function(Plan $plan): void {})
		;
	}
}
