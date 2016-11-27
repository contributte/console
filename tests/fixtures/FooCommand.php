<?php

namespace Tests\Fixtures;

use Contributte\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class FooCommand extends BaseCommand
{

	/**
	 * Configure command
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this->setName('foo');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Some magic..
	}

}
