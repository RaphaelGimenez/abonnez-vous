<?php

namespace App\Service;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriod;
use App\Enum\SubscriptionStatus;
use App\Exception\AlreadySubscribedException;
use App\Exception\InvalidSubscriptionStatusException;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionService
{
	public function __construct(private EntityManagerInterface $entityManager) {}

	/**
	 * Creates a new subscription for a user.
	 *
	 * @throws AlreadySubscribedException if user already has an active subscription
	 */
	public function createSubscription(
		User $user,
		Plan $plan,
		SubscriptionBillingPeriod $billingPeriod
	): Subscription {
		$this->ensureUserCanSubscribe($user);

		$subscription = new Subscription();
		$subscription->setUser($user);
		$subscription->setPlan($plan);
		$subscription->setStatus(SubscriptionStatus::ACTIVE);
		$subscription->setBillingPeriod($billingPeriod);
		$subscription->setStartDate(new \DateTimeImmutable());
		$subscription->setEndDate($this->calculateEndDate($subscription->getStartDate(), $billingPeriod));
		$subscription->setAutoRenew(true);

		$this->entityManager->persist($subscription);
		$this->entityManager->flush();

		return $subscription;
	}

	/**
	 * Cancels an active subscription.
	 *
	 * @throws InvalidSubscriptionStatusException if subscription is already canceled
	 * @throws InvalidSubscriptionStatusException if subscription is expired
	 */
	public function cancelSubscription(Subscription $subscription): void
	{
		if ($subscription->getStatus() === SubscriptionStatus::CANCELED) {
			throw new InvalidSubscriptionStatusException($subscription->getStatus(), 'cancel');
		}
		if ($subscription->getStatus() === SubscriptionStatus::EXPIRED) {
			throw new InvalidSubscriptionStatusException($subscription->getStatus(), 'cancel');
		}

		$subscription->setStatus(SubscriptionStatus::CANCELED);
		$subscription->setAutoRenew(false);

		$this->entityManager->flush();
	}

	/**
	 * Renews a canceled subscription.
	 *
	 * @throws InvalidSubscriptionStatusException if subscription is not canceled
	 */
	public function renewSubscription(Subscription $subscription): void
	{
		if ($subscription->getStatus() !== SubscriptionStatus::CANCELED) {
			throw new InvalidSubscriptionStatusException($subscription->getStatus(), 'renew');
		}

		$subscription->setStatus(SubscriptionStatus::RENEWING);
		$subscription->setAutoRenew(true);

		$this->entityManager->flush();
	}

	/**
	 * @throws AlreadySubscribedException if user already has an active subscription
	 */
	private function ensureUserCanSubscribe(User $user): void
	{
		$subscription = $user->getSubscription();
		if ($subscription && $subscription->getStatus() === SubscriptionStatus::ACTIVE) {
			throw new AlreadySubscribedException();
		}
	}

	private function calculateEndDate(
		\DateTimeImmutable $start,
		SubscriptionBillingPeriod $billingPeriod
	): \DateTimeImmutable {
		return match ($billingPeriod) {
			SubscriptionBillingPeriod::MONTHLY => $start->modify('+1 month'),
			SubscriptionBillingPeriod::YEARLY => $start->modify('+1 year'),
		};
	}
}
