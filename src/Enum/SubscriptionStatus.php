<?php

namespace App\Enum;

enum SubscriptionStatus: string
{
	case ACTIVE = 'active';
	case RENEWING = 'renewing';
	case EXPIRED = 'expired';
	case CANCELED = 'canceled';
}
