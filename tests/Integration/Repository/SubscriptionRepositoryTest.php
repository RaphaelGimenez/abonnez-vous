<?php

namespace App\Tests\Integration\Repository;

use App\Factory\PlanFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SubscriptionRepositoryTest extends KernelTestCase
{
	use Factories, ResetDatabase;

	private \App\Repository\SubscriptionRepository $repository;
	private \Doctrine\ORM\EntityManagerInterface $entityManager;
	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()
			->get('doctrine')
			->getManager();
		$this->repository = $this->entityManager->getRepository(\App\Entity\Subscription::class);
	}

	public function testSave(): void
	{
		$user = UserFactory::createOne();
		$plan = PlanFactory::createOne();
		$subscription = new \App\Entity\Subscription();
		$subscription->setStripeSubscriptionId('sub_test_123');
		$subscription->setUser($user->_real());
		$subscription->setPlan($plan->_real());

		$this->repository->save($subscription);

		$found = $this->repository->findOneBy(['stripeSubscriptionId' => 'sub_test_123']);

		$this->assertNotNull($found);
		$this->assertEquals('sub_test_123', $found->getStripeSubscriptionId());
	}

	public function testSaveWithFlushFalse(): void
	{
		$user = UserFactory::createOne();
		$plan = PlanFactory::createOne();
		$subscription = new \App\Entity\Subscription();
		$subscription->setStripeSubscriptionId('sub_test_456');
		$subscription->setUser($user->_real());
		$subscription->setPlan($plan->_real());

		$this->repository->save($subscription, false);

		$notFound = $this->repository->findOneBy(['stripeSubscriptionId' => 'sub_test_456']);

		$this->assertNull($notFound);
	}
}
