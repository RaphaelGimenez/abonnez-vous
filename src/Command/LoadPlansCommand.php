<?php

namespace App\Command;

use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:load-plans')]
class LoadPlansCommand extends Command
{
  public function __construct(
    private EntityManagerInterface $em,
    private AppFixtures $fixtures
  ) {
    parent::__construct();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $count = $this->em->getRepository(\App\Entity\Plan::class)->count([]);

    if ($count === 0) {
      $this->fixtures->load($this->em);
      $output->writeln('<info>Plans table populated.</info>');
    } else {
      $output->writeln('<comment>Plans table already has data.</comment>');
    }

    return Command::SUCCESS;
  }
}
