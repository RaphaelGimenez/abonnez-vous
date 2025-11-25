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
	private ?int $yearlyPrice = null;

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

	public function getyearlyPrice(): ?int
	{
		return $this->yearlyPrice;
	}

	public function setyearlyPrice(int $yearlyPrice): static
	{
		$this->yearlyPrice = $yearlyPrice;

		return $this;
	}
}
