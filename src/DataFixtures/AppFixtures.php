<?php

namespace App\DataFixtures;

use App\Entity\Plan;
use App\Repository\PlanRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
	public function __construct(private PlanRepository $planRepository) {}

	public function load(ObjectManager $manager): void
	{
		$count = $this->planRepository->count([]);
		if ($count > 0) {
			return;
		}

		$plans = [
			[
				'name' => 'Tarif normal',
				'monthlyPrice' => 12,
				'yearlyPrice' => 120,
				'stripeMonthlyLookupKey' => 'tarif_normal_monthly',
				'stripeYearlyLookupKey' => 'tarif_normal_yearly',
			],
			[
				'name' => 'Tarif soutien',
				'monthlyPrice' => 16,
				'yearlyPrice' => 160,
				'stripeMonthlyLookupKey' => 'tarif_soutien_monthly',
				'stripeYearlyLookupKey' => 'tarif_soutien_yearly',
			],
		];

		foreach ($plans as $planData) {
			$plan = new Plan();
			$plan->setName($planData['name']);
			$plan->setMonthlyPrice($planData['monthlyPrice']);
			$plan->setyearlyPrice($planData['yearlyPrice']);
			$plan->setStripeMonthlyLookupKey($planData['stripeMonthlyLookupKey']);
			$plan->setStripeYearlyLookupKey($planData['stripeYearlyLookupKey']);

			$manager->persist($plan);
		}

		$manager->flush();
	}
}
