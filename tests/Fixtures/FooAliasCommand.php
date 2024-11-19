<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'app:foo',
	description: 'Foo command',
	aliases: ['foo', 'bar']
)]
final class FooAliasCommand extends Command
{

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		return 0;
	}

}
