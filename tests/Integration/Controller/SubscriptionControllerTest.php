<?php

namespace App\Tests\Integration\Controller;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionStatus;
use App\Factory\PlanFactory;
use App\Factory\SubscriptionFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SubscriptionControllerTest extends WebTestCase
{
	use ResetDatabase, Factories;
	private KernelBrowser $client;
	private EntityManagerInterface $entityManager;

	protected function setUp(): void
	{
		$this->client = static::createClient();
		$this->client->enableProfiler();
		$this->entityManager = $this->client->getContainer()
			->get(EntityManagerInterface::class);
	}

	/**
	 * Test subscription
	 */

	public function testSubscribePageDisplaysPlans(): void
	{
		// Arrange
		PlanFactory::createOne();
		PlanFactory::createOne();

		// Act
		$this->client->request('GET', '/subscription/subscribe');

		// Assert
		$this->assertResponseIsSuccessful();
		$this->assertSelectorTextContains('h1', 'Choisissez votre offre');
		$this->assertSelectorCount(2, '[data-testid="plan-card"]');
	}

	public function testSubscribeRequiresAuthentication(): void
	{
		// Arrange
		$plan =	PlanFactory::createOne();

		// Act
		$this->client->request('POST', '/subscription/subscribe/plan/' . $plan->getId());

		// Assert
		$this->assertResponseRedirects('/login', 302);
	}

	public function testAuthenticatedUserCanSubscribe(): void
	{
		// Arrange
		$user = $this->createAuthenticatedUser(
			'subscriber@example.com',
			'securepassword'
		);
		$plan = PlanFactory::createOne();

		// Act
		$this->submitSubscriptionForm($plan, 'monthly');
		$this->client->followRedirect();

		// Assert
		$this->assertSelectorExists('[data-testid="flash-success"]');
		$this->assertHasSubscription($user, $plan, SubscriptionStatus::ACTIVE);
	}

	public function testUserCannotSubscribeTwice(): void
	{
		// Arrange
		$user = $this->createAuthenticatedUser();
		$plan = PlanFactory::createOne();
		$originalSubscription = SubscriptionFactory::createOne([
			'user' => $user,
			'plan' => $plan,
			'status' => SubscriptionStatus::ACTIVE,
		]);

		// Act
		$this->submitSubscriptionForm($plan, 'monthly');
		$this->client->followRedirect();

		// Assert
		$this->assertSelectorExists('[data-testid="flash-error"]');
		$currentSubscription = $this->assertHasSubscription($user, $plan, SubscriptionStatus::ACTIVE);
		$this->assertSame($originalSubscription->getId(), $currentSubscription->getId(), 'Should keep original subscription');
		$this->assertSame(1, SubscriptionFactory::repository()->count(['user' => $user]), 'User should have exactly one subscription');
	}

	public function testSubscribeWithInvalidBillingPeriod(): void
	{
		$this->createAuthenticatedUser();
		$plan = PlanFactory::createOne();

		$this->submitSubscriptionForm($plan, 'invalid_period');
		$this->client->followRedirect();

		$this->assertSelectorExists('[data-testid="flash-error"]');
		$this->assertSame(0, SubscriptionFactory::repository()->count(), 'No subscription should be created');
	}

	public function testSubscribeFormRejectsMissingCsrfToken(): void
	{
		// Arrange
		$this->createAuthenticatedUser();
		$plan = PlanFactory::createOne();

		// Act - submit without CSRF token
		$this->client->request('POST', '/subscription/subscribe/plan/' . $plan->getId(), [
			'billing_period' => 'monthly',
			'id' => $plan->getId(),
		]);

		// Assert
		$this->assertResponseStatusCodeSame(403);
		$this->assertSame(0, SubscriptionFactory::repository()->count(), 'No subscription should be created');
	}

	public function testSubscribeFormRejectsInvalidCsrfToken(): void
	{
		// Arrange
		$this->createAuthenticatedUser();
		$plan = PlanFactory::createOne();

		// Act - submit with invalid CSRF token
		$this->client->request('POST', '/subscription/subscribe/plan/' . $plan->getId(), [
			'billing_period' => 'monthly',
			'id' => $plan->getId(),
			'_token' => 'invalid_token_value',
		]);

		// Assert
		$this->assertResponseStatusCodeSame(403);
		$this->assertSame(0, SubscriptionFactory::repository()->count(), 'No subscription should be created');
	}

	/**
	 * Test subscription management
	 */

	/**
	 * @dataProvider subscriptionActionProvider
	 */
	public function testSubscriptionActions(
		string $action,
		SubscriptionStatus $initialStatus,
		SubscriptionStatus $expectedStatus,
		bool $expectedAutoRenew
	): void {
		// Arrange
		$user = $this->createAuthenticatedUser();
		$plan = PlanFactory::createOne();
		SubscriptionFactory::createOne([
			'user' => $user,
			'plan' => $plan,
			'status' => $initialStatus,
		]);

		// Act
		$this->submitManageSubscriptionForm($action);
		$this->client->followRedirect();

		// Assert
		$subscription = $this->assertHasSubscription($user, $plan, $expectedStatus);
		$this->assertSame($expectedAutoRenew, $subscription->isAutoRenew());
	}

	public function testManageRedirectsWhenNoSubscription(): void
	{
		// Arrange
		$this->createAuthenticatedUser();

		// Act
		$this->client->request('GET', '/subscription/manage');
		$this->client->followRedirect();

		// Assert
		$this->assertSelectorExists('[data-testid="flash-error"]');
	}

	public function testManageRequiresAuthentication(): void
	{
		// Act
		$this->client->request('GET', '/subscription/manage');

		// Assert
		$this->assertResponseRedirects('/login', 302);
	}

	/**
	 * @dataProvider subscriptionActionMethodProvider
	 */
	public function testManageActionRequiresCsrfToken(
		string $action,
	): void {
		// Arrange
		$user = $this->createAuthenticatedUser();
		$plan = PlanFactory::createOne();
		SubscriptionFactory::createOne([
			'user' => $user,
			'plan' => $plan,
			'status' => SubscriptionStatus::ACTIVE,
		]);

		// Act - submit with wrong method
		$this->client->request('POST', '/subscription/' . $action);

		// Assert
		$this->assertResponseStatusCodeSame(403);
		$this->assertHasSubscription($user, $plan, SubscriptionStatus::ACTIVE);
	}

	/**
	 * @dataProvider subscriptionActionMethodProvider
	 */
	public function testManageActionRequiresAuthentication(
		string $action,
	): void {
		// Act
		$this->client->request('POST', '/subscription/' . $action);
		// Assert -> redirected to login
		$this->assertResponseRedirects('/login', 302);
	}

	/**
	 * @dataProvider subscriptionActionMethodProvider
	 */
	public function testManageActionRequiresSubscription(
		string $action,
	): void {
		// Arrange
		$this->createAuthenticatedUser();

		// Act
		$this->client->request('POST', '/subscription/' . $action);

		// Assert
		$this->assertResponseStatusCodeSame(403);
	}


	/**
	 * Utils
	 */

	/**
	 * Creates an authenticated user and logs them in.
	 */
	private function createAuthenticatedUser(
		string $email = 'subscriber@example.com',
		string $password = 'securepassword'
	): User {
		$user = UserFactory::createOne([
			'email' => $email,
			'password' => $password,
		]);
		$this->client->loginUser($user->_real());
		return $user->_real();
	}

	/**
	 * Refreshes an entity from the database by clearing the EntityManager and reloading.
	 */
	private function refreshEntity(object $entity): object
	{
		$this->entityManager->clear();
		return $this->entityManager->find($entity::class, $entity->getId());
	}

	/**
	 * Asserts that a user has a subscription with the expected plan and status.
	 * Returns the subscription for further assertions.
	 */
	private function assertHasSubscription(User $user, Plan $plan, SubscriptionStatus $expectedStatus): Subscription
	{
		$subscription = $this->refreshEntity($user)->getSubscription();

		$this->assertNotNull($subscription, 'User should have a subscription');
		$this->assertSame($plan->getId(), $subscription->getPlan()->getId());
		$this->assertSame($expectedStatus, $subscription->getStatus());
		return $subscription;
	}

	/**
	 * Submits a subscription form for the given plan with the specified billing period.
	 */
	private function submitSubscriptionForm(Plan $plan, string $billingPeriod = 'monthly'): void
	{
		$crawler = $this->client->request('GET', '/subscription/subscribe');
		$form = $crawler->filter(
			'form[data-testid="subscription-form-' . $plan->getId() . '"]'
		)->form();

		$this->client->submit($form, ['billing_period' => $billingPeriod]);
	}

	/**
	 * Submits a subscription management form (cancel or renew).
	 */
	private function submitManageSubscriptionForm(string $action): void
	{
		$crawler = $this->client->request('GET', '/subscription/manage');
		$form = $crawler->filter(
			'form[data-testid="' . $action . '-subscription-form"]'
		)->form();

		$this->client->submit($form);
	}

	// Data providers
	public static function subscriptionActionProvider(): array
	{
		return [
			'cancel_active_subscription' => [
				'action' => 'cancel',
				'initialStatus' => SubscriptionStatus::ACTIVE,
				'expectedStatus' => SubscriptionStatus::CANCELED,
				'expectedAutoRenew' => false
			],
			'cancel_renewing_subscription' => [
				'action' => 'cancel',
				'initialStatus' => SubscriptionStatus::RENEWING,
				'expectedStatus' => SubscriptionStatus::CANCELED,
				'expectedAutoRenew' => false
			],
			'renew_canceled_subscription' => [
				'action' => 'renew',
				'initialStatus' => SubscriptionStatus::CANCELED,
				'expectedStatus' => SubscriptionStatus::RENEWING,
				'expectedAutoRenew' => true
			],
		];
	}

	public static function subscriptionActionMethodProvider(): array
	{
		return [
			'cancel_via_POST' => [
				'action' => 'cancel',
			],
			'renew_via_POST' => [
				'action' => 'renew',
			],
		];
	}
}
