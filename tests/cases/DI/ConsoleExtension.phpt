<?php

/**
 * Test: DI\ConsoleExtension
 */

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Contributte\Console\Exception\Logical\InvalidArgumentException;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Symfony\Component\Console\Command\Command;
use Tester\Assert;
use Tester\FileMock;
use Tests\Fixtures\FooCommand;

require_once __DIR__ . '/../../bootstrap.php';

// No commands
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension(TRUE));
	}, [getmypid(), 1]);

	/** @var Container $container */
	$container = new $class;

	Assert::count(0, $container->findByType(Command::class));
});

// 1 command of type FooCommand
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension(TRUE));
		$compiler->loadConfig(FileMock::create('
		console:
			lazy: off
		services:
			foo: Tests\Fixtures\FooCommand
		', 'neon'));
	}, [getmypid(), 2]);

	/** @var Container $container */
	$container = new $class;

	Assert::type(Application::class, $container->getByType(Application::class));
	Assert::true($container->isCreated('foo'));
	Assert::count(1, $container->findByType(Command::class));
	Assert::type(FooCommand::class, $container->getByType(Command::class));
});

// Provide URL
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('console', new ConsoleExtension(TRUE));
		$compiler->addExtension('http', new HttpExtension(TRUE));
		$compiler->loadConfig(FileMock::create('
		console:
			url: https://contributte.org/
		', 'neon'));
	}, [getmypid(), 3]);

	/** @var Container $container */
	$container = new $class;

	Assert::equal('https://contributte.org/', (string) $container->getService('http.request')->getUrl());
});

// No CLI mode
test(function () {
	Assert::exception(function () {
		new ConsoleExtension();
	}, InvalidArgumentException::class, 'Provide CLI mode, e.q. Contributte\Console\DI\ConsoleExtension(%consoleMode%).');
});
