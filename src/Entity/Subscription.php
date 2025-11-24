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
  private SubscriptionStatus $status = SubscriptionStatus::ACTIVE;

  #[ORM\Column]
  private SubscriptionBillingPeriod $billingPeriod = SubscriptionBillingPeriod::MONTHLY;

  #[ORM\Column]
  private ?\DateTimeImmutable $startDate = null;

  #[ORM\Column]
  private ?\DateTimeImmutable $endDate = null;

  #[ORM\Column]
  private ?bool $autoRenew = null;

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

  public function getStartDate(): ?\DateTimeImmutable
  {
    return $this->startDate;
  }

  public function setStartDate(\DateTimeImmutable $startDate): static
  {
    $this->startDate = $startDate;

    return $this;
  }

  public function getEndDate(): ?\DateTimeImmutable
  {
    return $this->endDate;
  }

  public function setEndDate(\DateTimeImmutable $endDate): static
  {
    $this->endDate = $endDate;

    return $this;
  }

  public function isAutoRenew(): ?bool
  {
    return $this->autoRenew;
  }

  public function setAutoRenew(bool $autoRenew): static
  {
    $this->autoRenew = $autoRenew;

    return $this;
  }
}
