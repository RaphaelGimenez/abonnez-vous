<?php

namespace App\Tests\Unit\Service;

use App\Entity\Plan;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Exception\Stripe\InvalidLookupKeyException;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\Service\Checkout\CheckoutServiceFactory;
use Stripe\Service\Checkout\SessionService;
use Stripe\Service\CustomerService;
use Stripe\Service\PriceService;
use Stripe\StripeClient;

class StripeServiceTest extends TestCase
{
	private MockObject&StripeClient $stripeClient;
	private MockObject&EntityManagerInterface $entityManager;
	private StripeService $service;
	private MockObject&PriceService $priceServiceMock;
	private MockObject&SessionService $sessionServiceMock;
	private MockObject&CheckoutServiceFactory $checkoutServiceMock;
	private MockObject&CustomerService $customerServiceMock;

	protected function setUp(): void
	{
		$this->stripeClient = $this->createMock(StripeClient::class);
		$this->entityManager = $this->createMock(EntityManagerInterface::class);
		$this->priceServiceMock = $this->createMock(PriceService::class);
		$this->sessionServiceMock = $this->createMock(SessionService::class);
		$this->checkoutServiceMock = $this->createMock(CheckoutServiceFactory::class);
		$this->customerServiceMock = $this->createMock(CustomerService::class);

		$this->stripeClient->prices = $this->priceServiceMock;
		$this->stripeClient->checkout = $this->checkoutServiceMock;
		$this->checkoutServiceMock->sessions = $this->sessionServiceMock;
		$this->stripeClient->customers = $this->customerServiceMock;

		$this->service = new StripeService(
			$this->stripeClient,
			$this->entityManager,
			$this->createMock(\App\Repository\UserRepository::class),
			$this->createMock(\App\Repository\PlanRepository::class),
			$this->createMock(\Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface::class)
		);
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

	public function testCreacteCheckoutSessionShouldCreateStripeCustomerIfNotExists(): void
	{
		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId(null);
		$plan = new Plan();
		$plan->setStripeMonthlyLookupKey('monthly_plan_123');
		$billingPeriod = SubscriptionBillingPeriod::MONTHLY;

		$this->priceServiceMock->expects($this->once())
			->method('all')
			->willReturn((object) ['data' => [(object) ['id' => 'price_12345']]]);
		$this->sessionServiceMock->expects($this->once())
			->method('create')
			->with($this->callback(function ($params) {
				return $params['customer'] === 'cus_new_12345';
			}))
			->willReturn(Session::constructFrom([]));
		$this->customerServiceMock->expects($this->once())
			->method('create')
			->willReturn((object) ['id' => 'cus_new_12345']);

		$this->service->createCheckoutSession(
			$user,
			$plan,
			$billingPeriod
		);
	}

	public function testGetOrCreateStripeCustomerIdReturnsExistingId(): void
	{
		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId('cus_12345');

		$stripeCustomerId = $this->service->getOrCreateStripeCustomerId($user);
		$this->assertSame('cus_12345', $stripeCustomerId);
	}

	public function testGetOrCreateStripeCustomerIdCreatesNewCustomer(): void
	{
		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId(null);
		$this->customerServiceMock->expects($this->once())
			->method('create')
			->with($this->callback(function ($params) use ($user) {
				return $params['email'] === $user->getEmail();
			}))
			->willReturn((object) ['id' => 'cus_new_12345']);

		$this->entityManager->expects($this->once())
			->method('flush');

		$stripeCustomerId = $this->service->getOrCreateStripeCustomerId($user);
		$this->assertSame('cus_new_12345', $stripeCustomerId);
		$this->assertSame('cus_new_12345', $user->getStripeCustomerId());
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

	/**
	 * @dataProvider invalidLookupKeyProvider
	 */
	public function testCreateCheckoutSessionThrowsExceptionOnInvalidLookupKey(
		string $scenario,
	): void {
		// Arrange
		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId('cus_12345');

		$plan = new Plan();
		$plan->setStripeMonthlyLookupKey('invalid_monthly_key');
		$plan->setStripeYearlyLookupKey('invalid_yearly_key');

		switch ($scenario) {
			case 'empty_results':
				$this->priceServiceMock->expects($this->once())
					->method('all')
					->willReturn((object) ['data' => []]);
				break;
			case 'api_exception':
				$this->priceServiceMock->expects($this->once())
					->method('all')
					->willThrowException(new \Exception('Stripe API error'));
				break;
			case 'null_lookup_key':
				$plan->setStripeMonthlyLookupKey(null);
				$this->priceServiceMock->expects($this->never())
					->method('all');
				break;
		}

		// Assert
		$this->expectException(InvalidLookupKeyException::class);

		// Act
		$this->service->createCheckoutSession(
			$user,
			$plan,
			SubscriptionBillingPeriod::MONTHLY
		);
	}

	public function testCreateCheckoutSessionAddsMetadata(): void
	{
		// Arrange
		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId('cus_12345');
		$plan = new Plan();
		$plan->setStripeMonthlyLookupKey('monthly_plan_123');
		$billingPeriod = SubscriptionBillingPeriod::MONTHLY;
		$this->priceServiceMock->expects($this->once())
			->method('all')
			->willReturn((object) ['data' => [(object) ['id' => 'price_12345']]]);
		$this->sessionServiceMock->expects($this->once())
			->method('create')
			->with($this->callback(function ($params) use ($plan, $billingPeriod) {
				return isset($params['metadata'])
					&& $params['metadata']['planId'] === $plan->getId()
					&& $params['metadata']['billingPeriod'] === $billingPeriod->value;
			}))
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

	public static function invalidLookupKeyProvider(): array
	{
		return [
			'empty_results' => [
				'scenario' => 'empty_results',
			],
			'stripe_api_exception' => [
				'scenario' => 'api_exception',
			],
			'null_lookup_key' => [
				'scenario' => 'null_lookup_key',
			],
		];
	}
}
