<?php declare(strict_types = 1);

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
use Tester\FileMock;
use Tests\Fixtures\FooCommand;

require_once __DIR__ . '/../../../bootstrap.php';

// 1 command of type FooCommand lazy-loading
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			lazy: true
		services:
			foo: Tests\Fixtures\FooCommand
		', 'neon'));
	}, [getmypid(), 1]);

	/** @var Container $container */
	$container = new $class();

	Assert::type(Application::class, $container->getByType(Application::class));
	Assert::false($container->isCreated('foo'));
	Assert::count(1, $container->findByType(Command::class));
	Assert::type(FooCommand::class, $container->getByType(Command::class));
});
