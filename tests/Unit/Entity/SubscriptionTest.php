<?php

namespace App\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
	public function testGetIsCancellingIsTrueWhenCancelAtIsSet(): void
	{
		$subscription = new \App\Entity\Subscription();
		$subscription->setCancelAt(new \DateTimeImmutable('+1 month'));

		$this->assertTrue($subscription->isCancelling());
	}

	public function testGetIsCancellingIsFalseWhenCancelAtIsNull(): void
	{
		$subscription = new \App\Entity\Subscription();
		$subscription->setCancelAt(null);

		$this->assertFalse($subscription->isCancelling());
	}
}
