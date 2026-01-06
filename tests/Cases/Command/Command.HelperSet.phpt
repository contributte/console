<?php declare(strict_types = 1);

use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Tester\Assert;
use Tests\Fixtures\HelperSetCommand;

require_once __DIR__ . '/../../bootstrap.php';

// Test auto filling helperSet in command
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					- Tests\Fixtures\HelperSetCommand
			NEON));
		})->build();

	/** @var Application $application */
	$application = $container->getByType(Application::class);

	/** @var HelperSetCommand $command */
	$command = $container->getByType(HelperSetCommand::class);

	$application->setDefaultCommand($command->getName(), true);
	$application->setAutoExit(false);
	$application->run();

	Assert::type(HelperSet::class, $command->getHelperSet());
});
