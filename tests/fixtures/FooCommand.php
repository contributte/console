<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FooCommand extends Command
{

	/** @var string */
	protected static $defaultName = 'app:foo';

	/**
	 * Configure command
	 */
	protected function configure(): void
	{
		$this->setName('foo');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		return 0;
	}

}
