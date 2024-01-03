<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'throwing'
)]
final class ThrowingCommand extends Command
{

	public const ERROR_MESSAGE = 'I am internally broken.';

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		throw new Exception(self::ERROR_MESSAGE);
	}

}
