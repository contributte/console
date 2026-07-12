<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Invokable command which does NOT extend Symfony's Command class, see
 * https://symfony.com/blog/new-in-symfony-7-3-invokable-commands-and-input-attributes
 */
#[AsCommand(
	name: 'app:invokable',
	aliases: ['app:invokable-alias'],
	description: 'Invokable command',
	help: 'Invokable command help',
	usages: ['--foo', '--bar']
)]
final class InvokableCommand
{

	public function __invoke(InputInterface $input, OutputInterface $output): int
	{
		$output->write('invoked');

		return Command::SUCCESS;
	}

}
