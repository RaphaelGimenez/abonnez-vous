<?php

namespace App\Exception;

class NoSubscriptionException extends SubscriptionException
{
	public function __construct(string $message = 'L\'utilisateur n\'a pas d\'abonnement actif')
	{
		parent::__construct($message);
	}
}
