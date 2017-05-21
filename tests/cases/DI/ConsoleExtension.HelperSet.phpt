<?php

/**
 * Test: DI\ConsoleExtension.HelperSet
 */

use Contributte\Console\DI\ConsoleExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Symfony\Component\Console\Application;
use Tester\Assert;
use Tester\FileMock;

require_once __DIR__ . '/../../bootstrap.php';

// Default helperSet
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension());
	}, [microtime(), 1]);

	/** @var Container $container */
	$container = new $class;

	// 4 default helpers
	// 1 container helper
	Assert::count(5, $container->getByType(Application::class)->getHelperSet()->getIterator());
});

// Own helperSet
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension());
		$compiler->loadConfig(FileMock::create('
		console:
			helperSet: Tests\Fixtures\FooHelperSet
		', 'neon'));
	}, [microtime(), 3]);

	/** @var Container $container */
	$container = new $class;

	// 1 container helper
	Assert::count(1, $container->getByType(Application::class)->getHelperSet()->getIterator());
});

// Own helperSet as service
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension());
		$compiler->loadConfig(FileMock::create('
		console:
			helperSet: @Tests\Fixtures\FooHelperSet
			
		services:
			- Tests\Fixtures\FooHelperSet
		', 'neon'));
	}, [microtime(), 3]);

	/** @var Container $container */
	$container = new $class;

	// 1 container helper
	Assert::count(1, $container->getByType(Application::class)->getHelperSet()->getIterator());
});

// Own helper
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension());
		$compiler->loadConfig(FileMock::create('
		console:
			helpers:
				- Tests\Fixtures\FooHelper
		', 'neon'));
	}, [microtime(), 4]);

	/** @var Container $container */
	$container = new $class;

	// 4 default helpers
	// 1 container helper
	// 1 foo helper
	Assert::count(6, $container->getByType(Application::class)->getHelperSet()->getIterator());
});
