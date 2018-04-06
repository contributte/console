<?php

/**
 * Test: Command\Command.HelperSet
 */

use Contributte\Console\DI\ConsoleExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Tester\Assert;
use Tester\FileMock;
use Tests\Fixtures\HelperSetCommand;

require_once __DIR__ . '/../../bootstrap.php';

// Test auto filling helperSet in command
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension(TRUE));
		$compiler->loadConfig(FileMock::create('
		console:
			lazy: off
		services:
			- Tests\Fixtures\HelperSetCommand
		', 'neon'));
	}, [getmypid(), 1]);

	/** @var Container $container */
	$container = new $class;

	/** @var Application $application */
	$application = $container->getByType(Application::class);

	/** @var HelperSetCommand $command */
	$command = $container->getByType(HelperSetCommand::class);

	$application->setDefaultCommand($command->getName(), TRUE);
	$application->setAutoExit(FALSE);
	$application->run();

	Assert::type(HelperSet::class, $command->getHelperSet());
});
