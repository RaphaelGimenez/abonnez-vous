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
}
