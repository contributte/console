<?php declare(strict_types = 1);

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\ServiceCreationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Invokable command discovered via #[AsCommand] attribute (does not extend Command)
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					invokable: Tests\Fixtures\InvokableCommand
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	assert($application instanceof Application);

	// A synthetic "console.invokable.command" Command service wraps the invokable service.
	// The wrapper is the container's only Command service, as the original doesn't extend Command.
	Assert::count(1, $container->findByType(Command::class));
	Assert::false($container->isCreated('invokable'));

	// Application::has() triggers lazy loading through the command loader,
	// instantiating the invokable service in the process.
	Assert::true($application->has('app:invokable'));
	Assert::true($container->isCreated('invokable'));

	$command = $application->find('app:invokable');
	Assert::type(Command::class, $command);
	Assert::same('app:invokable', $command->getName());
	Assert::same('Invokable command', $command->getDescription());
	Assert::same(['app:invokable-alias'], $command->getAliases());
	Assert::same('Invokable command help', $command->getHelp());
	Assert::same(['app:invokable --foo', 'app:invokable --bar'], $command->getUsages());

	// The alias resolves to the very same wrapped command
	Assert::same($command, $application->find('app:invokable-alias'));

	$tester = new CommandTester($command);
	$tester->execute([]);
	Assert::same('invoked', $tester->getDisplay());
	Assert::same(Command::SUCCESS, $tester->getStatusCode());
});

// Invokable command with the hidden flag set via #[AsCommand].
// A plain, unrelated service ("other") is registered alongside it to verify it's skipped, not treated as a command.
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					invokable: Tests\Fixtures\InvokableHiddenCommand
					other: stdClass
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	assert($application instanceof Application);

	Assert::count(1, $container->findByType(Command::class));

	$command = $application->find('app:invokable-hidden');
	Assert::same('app:invokable-hidden', $command->getName());
	Assert::true($command->isHidden());
});

// Invokable command discovered purely via the "console.command" tag (no attribute, does not extend Command)
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					invokable:
						class: Tests\Fixtures\InvokableTaggedCommand
						tags: [console.command: app:invokable-tagged]
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	assert($application instanceof Application);

	Assert::true($application->has('app:invokable-tagged'));

	$command = $application->find('app:invokable-tagged');
	$tester = new CommandTester($command);
	$tester->execute([]);
	Assert::same('invoked-tagged', $tester->getDisplay());
});

// Tagged as console command, but neither extends Command nor has __invoke()
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('console', new ConsoleExtension(true));
				$compiler->addConfig(Neonkit::load(<<<'NEON'
					console:
					services:
						invokable:
							class: stdClass
							tags: [console.command: app:invalid]
				NEON));
			})->build();
	}, ServiceCreationException::class);
});
