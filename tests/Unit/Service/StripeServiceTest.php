<?php

namespace App\Tests\Unit\Service;

use App\Entity\Plan;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Service\StripeService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\Service\Checkout\CheckoutServiceFactory;
use Stripe\Service\Checkout\SessionService;
use Stripe\Service\PriceService;
use Stripe\StripeClient;

class StripeServiceTest extends TestCase
{
	private MockObject&StripeClient $stripeClient;
	private StripeService $service;
	private MockObject&PriceService $priceServiceMock;
	private MockObject&SessionService $sessionServiceMock;
	private MockObject&CheckoutServiceFactory $checkoutServiceMock;

	protected function setUp(): void
	{
		$this->stripeClient = $this->createMock(StripeClient::class);
		$this->priceServiceMock = $this->createMock(PriceService::class);
		$this->sessionServiceMock = $this->createMock(SessionService::class);
		$this->checkoutServiceMock = $this->createMock(CheckoutServiceFactory::class);

		$this->stripeClient->prices = $this->priceServiceMock;
		$this->stripeClient->checkout = $this->checkoutServiceMock;
		$this->checkoutServiceMock->sessions = $this->sessionServiceMock;

		$this->service = new StripeService($this->stripeClient);
	}

	public function testCreateCheckoutSessionReturnsStripeUrl(): void
	{
		// Arrange
		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId('cus_12345');

		$plan = new Plan();
		$plan->setStripeMonthlyLookupKey('monthly_plan_123');
		$plan->setStripeYearlyLookupKey('yearly_plan_123');
		$billingPeriod = SubscriptionBillingPeriod::MONTHLY;

		$this->priceServiceMock->expects($this->once())
			->method('all')
			->willReturn((object) ['data' => [(object) ['id' => 'price_12345']]]);

		$checkoutSessionMock = Session::constructFrom([
			'id' => 'cs_test_12345',
			'object' => 'checkout.session',
			'url' => 'https://checkout.stripe.com/test/cs_test_12345',
			'mode' => 'subscription',
			'customer_email' => $user->getEmail(),
		]);

		$this->sessionServiceMock->expects($this->once())
			->method('create')
			->with($this->callback(function ($params) use ($user) {
				return $params['mode'] === 'subscription'
					&& $params['customer'] === $user->getStripeCustomerId()
					&& $params['customer_email'] === $user->getEmail()
					&& $params['line_items'][0]['price'] === "price_12345";
			}))
			->willReturn($checkoutSessionMock);

		// Act
		$session = $this->service->createCheckoutSession(
			$user,
			$plan,
			$billingPeriod
		);

		// Assert
		$this->assertSame('https://checkout.stripe.com/test/cs_test_12345', $session->url);
	}

	/**
	 * @dataProvider billingPeriodProvider
	 */
	public function testCreateCheckoutSessionUsesCorrectLookupKey(
		string $monthlyLookupKey,
		string $yearlyLookupKey,
		string $expectedPriceLookupKey,
		SubscriptionBillingPeriod $billingPeriod
	): void {
		// Arrange
		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId('cus_12345');

		$plan = new Plan();
		$plan->setStripeMonthlyLookupKey($monthlyLookupKey);
		$plan->setStripeYearlyLookupKey($yearlyLookupKey);

		$this->priceServiceMock->expects($this->once())
			->method('all')
			->with([
				'lookup_keys' => [$expectedPriceLookupKey],
				'limit' => 1,
			])
			->willReturn((object) ['data' => [(object) ['id' => 'price_12345']]]);

		$this->sessionServiceMock->method('create')
			->willReturn(Session::constructFrom([]));

		// Act
		$this->service->createCheckoutSession(
			$user,
			$plan,
			$billingPeriod
		);
	}

	public static function billingPeriodProvider(): array
	{
		$monthlyLookupKey = 'monthly_plan_123';
		$yearlyLookupKey = 'yearly_plan_123';

		return [
			'monthly' => [
				'monthlyLookupKey' => $monthlyLookupKey,
				'yearlyLookupKey' => $yearlyLookupKey,
				'expectedPriceLookupKey' =>
				$monthlyLookupKey,
				'billingPeriod' => SubscriptionBillingPeriod::MONTHLY,
			],
			'yearly' => [
				'monthlyLookupKey' => $monthlyLookupKey,
				'yearlyLookupKey' => $yearlyLookupKey,
				'expectedPriceLookupKey' =>
				$yearlyLookupKey,
				'billingPeriod' => SubscriptionBillingPeriod::YEARLY,
			],
		];
	}
}
