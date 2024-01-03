<?php declare(strict_types = 1);

use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\DI\InvalidConfigurationException;
use Symfony\Component\Console\Application;
use Tester\Assert;
use Tester\FileMock;
use Tests\Fixtures\FooHelperSet;

require_once __DIR__ . '/../../bootstrap.php';

// Default helperSet
Toolkit::test(function (): void {
	$loader = new ContainerLoader(Environment::getTestDir(), true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
	}, [getmypid(), 1]);

	/** @var Container $container */
	$container = new $class();

	// 4 default helpers
	Assert::count(4, $container->getByType(Application::class)->getHelperSet()->getIterator());
});

// Own helperSet
Toolkit::test(function (): void {
	$loader = new ContainerLoader(Environment::getTestDir(), true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			helperSet: Tests\Fixtures\FooHelperSet
		', 'neon'));
	}, [getmypid(), 2]);

	/** @var Container $container */
	$container = new $class();

	// Our helper set
	Assert::type(FooHelperSet::class, $container->getByType(Application::class)->getHelperSet());
});

// Own helperSet as service
Toolkit::test(function (): void {
	$loader = new ContainerLoader(Environment::getTestDir(), true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			helperSet: @Tests\Fixtures\FooHelperSet

		services:
			- Tests\Fixtures\FooHelperSet
		', 'neon'));
	}, [getmypid(), 3]);

	/** @var Container $container */
	$container = new $class();

	// Our helper set
	Assert::type(FooHelperSet::class, $container->getByType(Application::class)->getHelperSet());
});

// Own helper
Toolkit::test(function (): void {
	$loader = new ContainerLoader(Environment::getTestDir(), true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			helpers:
				- Tests\Fixtures\FooHelper
		', 'neon'));
	}, [getmypid(), 4]);

	/** @var Container $container */
	$container = new $class();

	// 4 default helpers
	// 1 foo helper
	Assert::count(5, $container->getByType(Application::class)->getHelperSet()->getIterator());
});

// Null helperSet
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$loader = new ContainerLoader(Environment::getTestDir(), true);
		$loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->loadConfig(FileMock::create('
		console:
			helperSet: null
		', 'neon'));
		}, [getmypid(), 5]);
	}, InvalidConfigurationException::class, "The item 'console › helperSet' expects to be string|Nette\DI\Definitions\Statement, null given.");
});
