<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StripeWebhookControllerTest extends WebTestCase
{
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
		$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET');
		$payload = json_encode([
			'type' => 'checkout.session.completed',
			'data' => ['object' => ['id' => 'cs_test_123']],
		]);

		// Generate valid signature
		$timestamp = time();
		$signedPayload = $timestamp . '.' . $payload;
		$signature = hash_hmac('sha256', $signedPayload, $webhookSecret);
		$signatureHeader = "t={$timestamp},v1={$signature}";

		$this->client->request('POST', '/webhook/stripe', [], [], [
			'HTTP_STRIPE_SIGNATURE' => $signatureHeader,
		], $payload);

		$this->assertResponseStatusCodeSame(200);
	}
}
