<?php

namespace App\Enum;

enum SubscriptionStatus: string
{
  case ACTIVE = 'active';
  case EXPIRED = 'expired';
  case CANCELED = 'canceled';
}
