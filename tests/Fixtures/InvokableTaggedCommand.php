<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Invokable command registered purely via the "console.command" tag, without the #[AsCommand] attribute
 * and without extending Symfony's Command class.
 */
final class InvokableTaggedCommand
{

	public function __invoke(InputInterface $input, OutputInterface $output): int
	{
		$output->write('invoked-tagged');

		return Command::SUCCESS;
	}

}
