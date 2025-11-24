<?php

namespace App\Enum;

enum SubscriptionBillingPeriod: string
{
  case MONTHLY = 'monthly';
  case ANNUAL = 'annual';
}
