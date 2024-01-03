<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'helper-set'
)]
final class HelperSetCommand extends Command
{

	protected function configure(): void
	{
		$this->setName('helper-set');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		return 0;
	}

}
