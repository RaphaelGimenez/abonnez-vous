<?php

namespace App\Tests\Unit\Service;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Enum\SubscriptionStatus;
use App\Exception\AlreadySubscribedException;
use App\Exception\InvalidSubscriptionStatusException;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SubscriptionServiceTest extends TestCase
{
	private SubscriptionService $subscriptionService;

	protected function setUp(): void
	{
		$mockEntityManager = $this->createMock(EntityManagerInterface::class);
		$this->subscriptionService = new SubscriptionService($mockEntityManager);
	}

	/**
	 * @dataProvider validCancelStatusProvider
	 */
	public function testCancelSubscriptionShouldChangeStatusToCanceled(SubscriptionStatus $status): void
	{
		$subscription = new Subscription();
		$subscription->setStatus($status);
		$this->subscriptionService->cancelSubscription($subscription);
		$this->assertSame(SubscriptionStatus::CANCELED, $subscription->getStatus(), 'Status should be CANCELED after cancellation');
		$this->assertFalse($subscription->isAutoRenew(), 'Auto-renew should be disabled after cancellation');
	}

	/**
	 * @dataProvider invalidCancelStatusProvider
	 */
	public function testCancelSubscriptionWithInvalidStatusShouldThrowsException(
		SubscriptionStatus $status,
		string $expectedMessage
	): void {
		$subscription = new Subscription();
		$subscription->setStatus($status);

		$this->expectException(InvalidSubscriptionStatusException::class);
		$this->expectExceptionMessage($expectedMessage);

		$this->subscriptionService->cancelSubscription($subscription);
	}

	public function testRenewSubscriptionShouldChangeStatusToRenewing(): void
	{
		$subscription = new Subscription();
		$subscription->setStatus(SubscriptionStatus::CANCELED);
		$this->subscriptionService->renewSubscription($subscription);
		$this->assertSame(SubscriptionStatus::RENEWING, $subscription->getStatus(), 'Status should be RENEWING after renewal');
		$this->assertTrue($subscription->isAutoRenew(), 'Auto-renew should be enabled after renewal');
	}

	/**
	 * @dataProvider invalidRenewStatusProvider
	 */
	public function testRenewSubscriptionWithInvalidStatusShouldThrowsException(
		SubscriptionStatus $status,
		string $expectedMessage
	): void {
		$subscription = new Subscription();
		$subscription->setStatus($status);
		$this->expectException(InvalidSubscriptionStatusException::class);
		$this->expectExceptionMessage($expectedMessage);
		$this->subscriptionService->renewSubscription($subscription);
	}

	public function testCreateSubscriptionShouldThrowExceptionIfUserAlreadySubscribed(): void
	{
		$subscription = new Subscription();
		$subscription->setStatus(SubscriptionStatus::ACTIVE);
		$user = $this->createMock(User::class);
		$user->method('getSubscription')->willReturn($subscription);

		$plan = $this->createMock(Plan::class);
		$billingPeriod = SubscriptionBillingPeriod::MONTHLY;

		$this->expectException(AlreadySubscribedException::class);
		$this->subscriptionService->createSubscription($user, $plan, $billingPeriod);
	}

	public function testCreateSubscriptionShouldHaveCorrectProperties(): void
	{
		$user = $this->createMock(User::class);
		$user->method('getSubscription')->willReturn(null);

		$plan = $this->createMock(Plan::class);
		$billingPeriod = SubscriptionBillingPeriod::YEARLY;

		$subscription = $this->subscriptionService->createSubscription($user, $plan, $billingPeriod);

		$this->assertSame($user, $subscription->getUser());
		$this->assertSame($plan, $subscription->getPlan());
		$this->assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
		$this->assertSame($billingPeriod, $subscription->getBillingPeriod());
		$this->assertInstanceOf(\DateTimeImmutable::class, $subscription->getStartDate());
		$this->assertInstanceOf(\DateTimeImmutable::class, $subscription->getEndDate());
		$this->assertTrue($subscription->isAutoRenew());
	}

	/**
	 * @dataProvider billingPeriodProvider
	 */
	public function testCreateSubscriptionShouldSetEndDateCorrectly(SubscriptionBillingPeriod $billingPeriod, string $expectedModification): void
	{
		$user = $this->createMock(User::class);
		$user->method('getSubscription')->willReturn(null);

		$plan = $this->createMock(Plan::class);

		$subscription = $this->subscriptionService->createSubscription($user, $plan, $billingPeriod);
		$expectedEndDate = $subscription->getStartDate()->modify($expectedModification);
		$this->assertEquals($expectedEndDate, $subscription->getEndDate());
	}

	// Data Providers
	public static function validCancelStatusProvider(): array
	{
		return [
			'active' => [
				'status' => SubscriptionStatus::ACTIVE,
			],
			'renewing' => [
				'status' => SubscriptionStatus::RENEWING,
			],
		];
	}

	public static function invalidCancelStatusProvider(): array
	{
		return [
			'already canceled' => [
				'status' => SubscriptionStatus::CANCELED,
				'expectedMessage' => "Can't cancel already canceled subscription"
			],
			'already expired' => [
				'status' => SubscriptionStatus::EXPIRED,
				'expectedMessage' => "Can't cancel already expired subscription"
			],
		];
	}

	public static function invalidRenewStatusProvider(): array
	{
		return [
			'active' => [
				'status' => SubscriptionStatus::ACTIVE,
				'expectedMessage' => "Can't renew already active subscription"
			],
			'expired' => [
				'status' => SubscriptionStatus::EXPIRED,
				'expectedMessage' => "Can't renew already expired subscription"
			],
			'renewing' => [
				'status' => SubscriptionStatus::RENEWING,
				'expectedMessage' => "Can't renew already renewing subscription"
			],
		];
	}

	public static function billingPeriodProvider(): array
	{
		return [
			'monthly' => [
				SubscriptionBillingPeriod::MONTHLY,
				'+1 month'
			],
			'yearly' => [
				SubscriptionBillingPeriod::YEARLY,
				'+1 year'
			],
		];
	}
}
