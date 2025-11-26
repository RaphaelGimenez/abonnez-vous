<?php

namespace App\Tests\Integration\Controller;

use App\Enum\SubscriptionStatus;
use App\Factory\PlanFactory;
use App\Factory\UserFactory;
use App\Repository\SubscriptionRepository;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StripeWebhookControllerTest extends WebTestCase
{
	use Factories, ResetDatabase;
	private KernelBrowser $client;

	protected function setUp(): void
	{
		$this->client = static::createClient();
		$this->client->enableProfiler();
	}

	public function testStripeWebhookRejectsInvalidSignature(): void
	{

		$payload = json_encode(['type' => 'test.event']);

		$this->client->request('POST', '/webhook/stripe', [], [], [
			'HTTP_STRIPE_SIGNATURE' => 'invalid_signature',
		], $payload);

		$this->assertResponseStatusCodeSame(400);
	}

	public function testStripeWebhookAcceptsValidSignature(): void
	{
		$payload = json_encode([
			'type' => 'unhandled.event',
			'data' => ['object' => ['id' => 'cs_test_123']],
		]);

		$signatureHeader = $this->generateSignature($payload);

		$crawler = $this->client->request('POST', '/webhook/stripe', [], [], [
			'HTTP_STRIPE_SIGNATURE' => $signatureHeader,
		], $payload);
		$response = $this->client->getResponse();

		$this->assertResponseStatusCodeSame(400);
		$this->assertStringContainsString('Unhandled event type', $response->getContent());
	}

	public function testSubscriptionCreatedEventCreatesASubscriptionForUser(): void
	{
		// Arrange
		// Create test data
		UserFactory::createOne(
			[
				'email' => 'test@example.com',
				'stripeCustomerId' => 'cus_test_123'
			],
		);
		$plan = PlanFactory::createOne([
			'name' => 'Basic',
			'stripeMonthlyLookupKey' => 'price_123',
		]);

		// Create webhook payload
		$session = new Session('cs_test_123');
		$session->customer = 'cus_test_123';
		$session->subscription = 'sub_test_123';
		$session->metadata = (object)[
			'planId' => $plan->getId(),
			'billingPeriod' => 'monthly',
		];
		$payload = json_encode([
			'type' => 'checkout.session.completed',
			'data' => ['object' => $session],
		]);

		$signatureHeader = $this->generateSignature($payload);

		// Act
		$this->client->request('POST', '/webhook/stripe', [], [], [
			'HTTP_STRIPE_SIGNATURE' => $signatureHeader,
		], $payload);

		// Assert
		$this->assertResponseIsSuccessful();

		$subscriptionRepo = static::getContainer()->get(SubscriptionRepository::class);
		$subscription = $subscriptionRepo->findOneBy(['stripeSubscriptionId' => 'sub_test_123']);
		$user = $subscription->getUser();

		$this->assertNotNull($subscription);
		$this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->getStatus());
		$this->assertEquals('sub_test_123', $subscription->getStripeSubscriptionId());
		$this->assertEquals($plan->getStripeMonthlyLookupKey(), $subscription->getPlan()->getStripeMonthlyLookupKey());
		$this->assertEquals('cus_test_123', $user->getStripeCustomerId());
	}

	public function generateSignature(string $payload)
	{
		$webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];
		$timestamp = time();
		$signedPayload = $timestamp . '.' . $payload;
		$signature = hash_hmac('sha256', $signedPayload, $webhookSecret);
		$signatureHeader = "t={$timestamp},v1={$signature}";
		return $signatureHeader;
	}
}
