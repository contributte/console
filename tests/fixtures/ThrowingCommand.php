<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ThrowingCommand extends Command
{

	/** @var string */
	protected static $defaultName = 'throwing';

	public const ERROR_MESSAGE = 'I am internally broken.';

	/**
	 * Configure command
	 */
	protected function configure(): void
	{
		$this->setName(self::$defaultName);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		throw new Exception(self::ERROR_MESSAGE);
	}

}
