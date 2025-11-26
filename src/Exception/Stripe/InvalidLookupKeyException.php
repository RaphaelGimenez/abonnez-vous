<?php

namespace App\Exception\Stripe;

class InvalidLookupKeyException extends \RuntimeException
{
	public function __construct(string $message = 'La clé de recherche Stripe fournie est invalide')
	{
		parent::__construct($message);
	}
}
