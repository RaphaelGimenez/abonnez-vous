<?php

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserRepositoryTest extends KernelTestCase
{
	use ResetDatabase;

	private UserRepository $repository;
	private EntityManagerInterface $entityManager;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()
			->get('doctrine')
			->getManager();
		$this->repository = $this->entityManager->getRepository(User::class);
	}

	public function testFindOneByEmail(): void
	{
		UserFactory::createOne(['email' => 'test@example.com']);

		$found = $this->repository->findOneByEmail('test@example.com');

		$this->assertNotNull($found);
		$this->assertEquals('test@example.com', $found->getEmail());
	}

	public function testFindOneByEmailReturnsNullWhenNotFound(): void
	{
		$found = $this->repository->findOneByEmail('nonexistent@example.com');

		$this->assertNull($found);
	}
}
