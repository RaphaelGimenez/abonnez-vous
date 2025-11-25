<?php

namespace App\Entity;

use App\Repository\PlanRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Plans entity representing different subscription plans available to users.
 */
#[ORM\Entity(repositoryClass: PlanRepository::class)]
#[ORM\Table(name: '`plan`')]
class Plan
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	private ?string $name = null;

	#[ORM\Column]
	private ?int $monthlyPrice = null;

	#[ORM\Column]
	private ?int $annualPrice = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): static
	{
		$this->name = $name;

		return $this;
	}

	public function getMonthlyPrice(): ?int
	{
		return $this->monthlyPrice;
	}

	public function setMonthlyPrice(int $monthlyPrice): static
	{
		$this->monthlyPrice = $monthlyPrice;

		return $this;
	}

	public function getAnnualPrice(): ?int
	{
		return $this->annualPrice;
	}

	public function setAnnualPrice(int $annualPrice): static
	{
		$this->annualPrice = $annualPrice;

		return $this;
	}
}
