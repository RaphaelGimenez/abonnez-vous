<?php

namespace App\Repository;

use App\Entity\Plan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plan>
 * @method Plan|null find($id, $lockMode = null, $lockVersion = null)
 */
class PlanRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Plan::class);
	}
}
