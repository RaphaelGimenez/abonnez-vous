<?php

namespace App\Tests\Unit\Service;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Enum\SubscriptionStatus;
use App\Exception\Stripe\InvalidLookupKeyException;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\Service\BillingPortal\BillingPortalServiceFactory;
use Stripe\Service\Checkout\CheckoutServiceFactory;
use Stripe\Service\Checkout\SessionService;
use Stripe\Service\CustomerService;
use Stripe\Service\PriceService;
use Stripe\StripeClient;

class StripeServiceTest extends TestCase
{
	private MockObject&StripeClient $stripeClient;
	private MockObject&EntityManagerInterface $entityManager;
	private MockObject&\App\Repository\SubscriptionRepository $subscriptionRepositoryMock;
	private StripeService $service;
	private MockObject&PriceService $priceServiceMock;
	private MockObject&SessionService $sessionServiceMock;
	private MockObject&CheckoutServiceFactory $checkoutServiceMock;
	private MockObject&CustomerService $customerServiceMock;
	private MockObject&BillingPortalServiceFactory $billingPortalFactoryMock;
	private MockObject&\Stripe\Service\BillingPortal\SessionService $billingPortalServiceMock;

	protected function setUp(): void
	{
		$this->stripeClient = $this->createMock(StripeClient::class);
		$this->entityManager = $this->createMock(EntityManagerInterface::class);
		$this->subscriptionRepositoryMock = $this->createMock(\App\Repository\SubscriptionRepository::class);
		$this->priceServiceMock = $this->createMock(PriceService::class);
		$this->sessionServiceMock = $this->createMock(SessionService::class);
		$this->checkoutServiceMock = $this->createMock(CheckoutServiceFactory::class);
		$this->customerServiceMock = $this->createMock(CustomerService::class);
		$this->billingPortalFactoryMock = $this->createMock(BillingPortalServiceFactory::class);
		$this->billingPortalServiceMock = $this->createMock(\Stripe\Service\BillingPortal\SessionService::class);

		$this->stripeClient->prices = $this->priceServiceMock;
		$this->stripeClient->checkout = $this->checkoutServiceMock;
		$this->checkoutServiceMock->sessions = $this->sessionServiceMock;
		$this->stripeClient->customers = $this->customerServiceMock;
		$this->stripeClient->billingPortal = $this->billingPortalFactoryMock;
		$this->billingPortalFactoryMock->sessions = $this->billingPortalServiceMock;

		$paramMock = $this->createMock(\Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface::class);
		$paramMock->method('get')
			->willReturnMap([
				['app.default_uri', $_ENV['DEFAULT_URI']],
			]);

		$this->service = new StripeService(
			$this->stripeClient,
			$this->entityManager,
			$this->createMock(\App\Repository\UserRepository::class),
			$this->createMock(\App\Repository\PlanRepository::class),
			$this->subscriptionRepositoryMock,
			$paramMock
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
					&& $params['line_items'][0]['price'] === "price_12345"
					&& $params['success_url'] === $_ENV['DEFAULT_URI']
					&& $params['cancel_url'] === $_ENV['DEFAULT_URI'] . '/subscription/subscribe?canceled=true';
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

	public function testCreateBillingPortalSessionReturnsStripeUrl(): void
	{
		// Arrange
		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId('cus_12345');

		$billingSessionMock = \Stripe\BillingPortal\Session::constructFrom([
			'id' => 'bps_test_12345',
			'object' => 'billing_portal.session',
			'url' => 'https://billing.stripe.com/test/portal_12345',
			'customer' => 'cus_12345',
		]);

		$this->billingPortalServiceMock->expects($this->once())
			->method('create')
			->with($this->callback(function ($params) use ($user) {
				return $params['customer'] === $user->getStripeCustomerId()
					&& $params['return_url'] === $_ENV['DEFAULT_URI'];
			}))
			->willReturn($billingSessionMock);

		// Act
		$session = $this->service->createBillingPortalSession($user);

		// Assert
		$this->assertSame('https://billing.stripe.com/test/portal_12345', $session->url);
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

	/**
	 * @dataProvider customerSubscriptionUpdatedProvider
	 */
	public function testHandleCustomerSubscriptionUpdatedUpdatesSubscriptionStatus(string $subscriptionStatus, SubscriptionStatus $expectedStatus): void
	{
		// Arrange
		$plan = new Plan();
		$plan->setName('Basic');
		$plan->setStripeMonthlyLookupKey('price_123');

		$user = new User();
		$user->setEmail('test@example.com');
		$user->setStripeCustomerId('cus_test_123');

		$subscriptionEntity = new Subscription();
		$subscriptionEntity->setStripeSubscriptionId('sub_test_123');
		$subscriptionEntity->setUser($user);
		$subscriptionEntity->setPlan($plan);
		$subscriptionEntity->setStatus(SubscriptionStatus::ACTIVE);

		$this->subscriptionRepositoryMock->expects($this->once())
			->method('findOneBy')
			->with(['stripeSubscriptionId' => 'sub_test_123'])
			->willReturn($subscriptionEntity);

		$this->subscriptionRepositoryMock->expects($this->once())
			->method('save')
			->with(
				$this->callback(function ($subscription) use ($expectedStatus) {
					return $subscription->getStatus() === $expectedStatus;
				}),
				$this->anything()
			);

		// Act & Assert
		$subscription = $this->createStripeSubscription('sub_test_123', $subscriptionStatus, 'price_123');
		$this->service->handleCustomerSubscriptionUpdated($subscription);
	}

	public function createStripeSubscription(string $id, string $status, string $lookupKey): \Stripe\Subscription
	{
		$subscription = new \Stripe\Subscription($id);
		$subscription->status = $status;
		$subscription->items = (object)[
			'data' => [(object)[
				'price' => (object)[
					'lookup_key' => $lookupKey,
				],
			]],
		];
		return $subscription;
	}

	public static function customerSubscriptionUpdatedProvider(): array
	{
		return [
			'active' => [
				'subscriptionStatus' => 'active',
				'expectedStatus' => SubscriptionStatus::ACTIVE
			],
			'canceled' => [
				'subscriptionStatus' => 'canceled',
				'expectedStatus' => SubscriptionStatus::CANCELED
			],
		];
	}
}
