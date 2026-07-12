<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Invokable command with the hidden flag set via #[AsCommand], to cover the pipe-encoded
 * name|hidden decoding for services which don't extend Command.
 */
#[AsCommand(
	name: 'app:invokable-hidden',
	hidden: true
)]
final class InvokableHiddenCommand
{

	public function __invoke(InputInterface $input, OutputInterface $output): int
	{
		$output->write('invoked-hidden');

		return Command::SUCCESS;
	}

}
