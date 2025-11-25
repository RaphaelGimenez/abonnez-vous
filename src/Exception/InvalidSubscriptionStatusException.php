<?php

namespace App\Exception;

use App\Enum\SubscriptionStatus;

class InvalidSubscriptionStatusException extends SubscriptionException
{
	public function __construct(SubscriptionStatus $current, string $action)
	{
		parent::__construct(
			sprintf('Impossible de %s un abonnement avec le statut %s', $action, $current->value)
		);
	}
}
