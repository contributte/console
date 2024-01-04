<?php declare(strict_types = 1);

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// console.command
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					foo:
						class: Tests\Fixtures\FooCommand
						tags: [console.command: app:foo]
					bar:
						class: Tests\Fixtures\BarCommand
						tags: [console.command: {name: app:bar}]
			NEON
			));
		})->build();

	$application = $container->getByType(Application::class);
	assert($application instanceof Application);

	Assert::count(2, $container->findByType(Command::class));
	Assert::same(['help', 'list', '_complete', 'completion', 'app:foo', 'app:bar'], array_keys($application->all()));
});

// try to set command other name
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					foo:
						class: Tests\Fixtures\FooCommand
						tags: [console.command: fake]
			NEON
			));
		})->build();

	$application = $container->getByType(Application::class);
	assert($application instanceof Application);

	Assert::exception(
		fn () => $application->all(),
		CommandNotFoundException::class,
		'The "fake" command cannot be found because it is registered under multiple names. Make sure you don\'t set a different name via constructor or "setName()".'
	);
});
