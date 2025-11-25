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
				'annualPrice' => 120,
			],
			[
				'name' => 'Tarif soutien',
				'monthlyPrice' => 16,
				'annualPrice' => 160,
			],
		];

		foreach ($plans as $planData) {
			$plan = new Plan();
			$plan->setName($planData['name']);
			$plan->setMonthlyPrice($planData['monthlyPrice']);
			$plan->setAnnualPrice($planData['annualPrice']);

			$manager->persist($plan);
		}

		$manager->flush();
	}
}
