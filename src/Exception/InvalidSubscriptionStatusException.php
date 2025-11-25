<?php

namespace App\Exception;

use App\Enum\SubscriptionStatus;

class InvalidSubscriptionStatusException extends SubscriptionException
{
	public function __construct(SubscriptionStatus $current, string $action)
	{
		parent::__construct(
			sprintf('Can\'t %s already %s subscription', $action, $current->value)
		);
	}
}
