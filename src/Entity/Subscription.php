<?php

namespace App\Entity;

use App\Enum\SubscriptionBillingPeriod;
use App\Enum\SubscriptionStatus;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Subscriptions entity representing user's current subscription details.
 * Each user can only have one subscription at a time.
 * TODO: When canceled or expired subscriptions could be archived in a separate table.
 */
#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\OneToOne(targetEntity: User::class, inversedBy: 'subscription')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $user = null;

	#[ORM\ManyToOne(targetEntity: Plan::class)]
	#[ORM\JoinColumn(nullable: false)]
	private ?Plan $plan = null;

	#[ORM\Column(length: 255)]
	private string $stripeSubscriptionId;

	#[ORM\Column(length: 255)]
	private SubscriptionStatus $status = SubscriptionStatus::ACTIVE;

	#[ORM\Column]
	private SubscriptionBillingPeriod $billingPeriod = SubscriptionBillingPeriod::MONTHLY;

	#[ORM\Column(type: 'datetime_immutable', nullable: true)]
	private ?\DateTimeImmutable $currentPeriodStart = null;

	#[ORM\Column(type: 'datetime_immutable', nullable: true)]
	private ?\DateTimeImmutable $currentPeriodEnd = null;

	/**
	 * Cancellation reason provided by the user when requesting cancellation.
	 */
	#[ORM\Column(length: 255, nullable: true)]
	private ?string $cancellationReason = null;

	/**
	 * Cancellation effective date given by Stripe.
	 */
	#[ORM\Column(type: 'datetime_immutable', nullable: true)]
	private ?\DateTimeImmutable $cancelAt = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(User $user): static
	{
		$this->user = $user;

		return $this;
	}

	public function getPlan(): ?Plan
	{
		return $this->plan;
	}

	public function setPlan(Plan $plan): static
	{
		$this->plan = $plan;

		return $this;
	}

	public function getStripeSubscriptionId(): string
	{
		return $this->stripeSubscriptionId;
	}

	public function setStripeSubscriptionId(string $stripeSubscriptionId): static
	{
		$this->stripeSubscriptionId = $stripeSubscriptionId;

		return $this;
	}

	public function getStatus(): SubscriptionStatus
	{
		return $this->status;
	}

	public function setStatus(SubscriptionStatus $status): static
	{
		$this->status = $status;

		return $this;
	}

	public function getBillingPeriod(): SubscriptionBillingPeriod
	{
		return $this->billingPeriod;
	}

	public function setBillingPeriod(SubscriptionBillingPeriod $billingPeriod): static
	{
		$this->billingPeriod = $billingPeriod;

		return $this;
	}

	public function getCurrentPeriodStart(): ?\DateTimeImmutable
	{
		return $this->currentPeriodStart;
	}

	public function setCurrentPeriodStart(?\DateTimeImmutable $currentPeriodStart): static
	{
		$this->currentPeriodStart = $currentPeriodStart;

		return $this;
	}
	public function getCurrentPeriodEnd(): ?\DateTimeImmutable
	{
		return $this->currentPeriodEnd;
	}

	public function setCurrentPeriodEnd(?\DateTimeImmutable $currentPeriodEnd): static
	{
		$this->currentPeriodEnd = $currentPeriodEnd;

		return $this;
	}

	public function getCancellationReason(): ?string
	{
		return $this->cancellationReason;
	}


	public function setCancellationReason(?string $cancellationReason): static
	{
		$this->cancellationReason = $cancellationReason;

		return $this;
	}

	public function getCancelAt(): ?\DateTimeImmutable
	{
		return $this->cancelAt;
	}

	public function setCancelAt(?\DateTimeImmutable $cancelAt): static
	{
		$this->cancelAt = $cancelAt;

		return $this;
	}

	/**
	 * Helpers
	 */
	public function isCancelling(): bool
	{
		return $this->cancelAt !== null;
	}
}
