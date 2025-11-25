<?php

namespace App\Exception;

class AlreadySubscribedException extends SubscriptionException
{
	public function __construct(string $message = 'L\'utilisateur est déjà abonné')
	{
		parent::__construct($message);
	}
}
