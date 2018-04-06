<?php

/**
 * Test: DI\ConsoleExtension [lazy]
 */

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Symfony\Component\Console\Command\Command;
use Tester\Assert;
use Tester\Environment;
use Tester\FileMock;
use Tests\Fixtures\FooCommand;

require_once __DIR__ . '/../../bootstrap.php';

if (!interface_exists('Symfony\Component\Console\CommandLoader\CommandLoaderInterface', TRUE)) {
	Environment::skip('CommandLoaderInterface is available from symfony/console 3.4');
}

// 1 command of type FooCommand lazy-loading
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension(TRUE));
		$compiler->loadConfig(FileMock::create('
		console:
			lazy: on
		services:
			foo: Tests\Fixtures\FooCommand
		', 'neon'));
	}, [getmypid(), 1]);

	/** @var Container $container */
	$container = new $class;

	Assert::type(Application::class, $container->getByType(Application::class));
	Assert::false($container->isCreated('foo'));
	Assert::count(1, $container->findByType(Command::class));
	Assert::type(FooCommand::class, $container->getByType(Command::class));
});
