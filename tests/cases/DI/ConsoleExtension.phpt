<?php declare(strict_types = 1);

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
use Nette\DI\Extensions\DIExtension;
use Nette\DI\MissingServiceException;
use Nette\DI\ServiceCreationException;
use Symfony\Component\Console\Command\Command;
use Tester\Assert;
use Tester\FileMock;
use Tests\Fixtures\FooCommand;

require_once __DIR__ . '/../../bootstrap.php';

// No commands
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
	}, [getmypid(), 1]);

	/** @var Container $container */
	$container = new $class();

	Assert::count(0, $container->findByType(Command::class));
});

// 1 command of type FooCommand
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			lazy: false
		services:
			foo: Tests\Fixtures\FooCommand
		', 'neon'));
	}, [getmypid(), 2]);

	/** @var Container $container */
	$container = new $class();

	Assert::type(Application::class, $container->getByType(Application::class));
	Assert::true($container->isCreated('foo'));
	Assert::count(1, $container->findByType(Command::class));
	Assert::type(FooCommand::class, $container->getByType(Command::class));
});

// Provide URL
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->addExtension('http', new HttpExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			url: https://contributte.org/
		', 'neon'));
	}, [getmypid(), 3]);

	/** @var Container $container */
	$container = new $class();

	Assert::equal('https://contributte.org/', (string) $container->getService('http.request')->getUrl());
});

// No mode provided
test(function (): void {
	Assert::exception(function (): void {
		new ConsoleExtension();
	}, InvalidArgumentException::class, 'Provide CLI mode, e.q. Contributte\Console\DI\ConsoleExtension(%consoleMode%).');
});

// Non-CLI mode
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(false));
	}, [getmypid(), 4]);

	/** @var Container $container */
	$container = new $class();

	Assert::exception(static function () use ($container): void {
		$container->getByType(Application::class);
	}, MissingServiceException::class);
});

// Config
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			name: Hello world
			version: 1.0.0
			catchExceptions: false
			autoExit: false
		', 'neon'));
	}, [getmypid(), 5]);

	/** @var Container $container */
	$container = new $class();

	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $application);
	Assert::same('Hello world', $application->getName());
	Assert::same('1.0.0', $application->getVersion());
	Assert::false($application->areExceptionsCaught());
	Assert::false($application->isAutoExitEnabled());
});

// Lazy commands
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			lazy: true
		services:
			defaultName: Tests\Fixtures\FooCommand
			tagNameString:
				factory: Tests\Fixtures\FooCommand
				tags: [console.command: bar]
			tagNameArray:
				factory: Tests\Fixtures\FooCommand
				tags: [console.command: [name: baz]]
		', 'neon'));
	}, [getmypid(), 6]);

	/** @var Container $container */
	$container = new $class();

	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $application);
	Assert::false($container->isCreated('defaultName'));
	Assert::count(3, $container->findByType(Command::class));
	Assert::true($application->has('app:foo'));
	Assert::true($application->has('bar'));
	Assert::true($application->has('baz'));
});

// Invalid command
test(function (): void {
	Assert::exception(function (): void {
		$loader = new ContainerLoader(TEMP_DIR, true);
		$loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->loadConfig(FileMock::create('
			console:
				lazy: true
			services:
				noName: Tests\Fixtures\NoNameCommand
		', 'neon'));
		}, [getmypid(), 7]);
	}, ServiceCreationException::class, 'Command "Tests\Fixtures\NoNameCommand" missing tag "console.command[name]" or variable "$defaultName".');
});

// Always exported
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->addExtension('di', new DIExtension());
		$compiler->loadConfig(FileMock::create('
		di:
			export:
				types: null
		', 'neon'));
	}, [getmypid(), 8]);

	/** @var Container $container */
	$container = new $class();

	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $application);
});

// URL as Dynamic parameter
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->setDynamicParameterNames(['url']);
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->addExtension('http', new HttpExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			url: %url%
		parameters:
			url: https://contributte.org/
		', 'neon'));
	}, [getmypid(), 9]);

	/** @var Container $container */
	$container = new $class();

	Assert::type(Application::class, $container->getByType(Application::class));
	Assert::equal('https://contributte.org/', (string) $container->getService('http.request')->getUrl());
});

// Name as Dynamic parameter
test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->setDynamicParameterNames(['name']);
		$compiler->addExtension('console', new ConsoleExtension(true));
		$compiler->loadConfig(FileMock::create('
		console:
			name: %name%
		parameters:
			name: Hello world
		', 'neon'));
	}, [getmypid(), 10]);

	/** @var Container $container */
	$container = new $class();

	$application = $container->getByType(Application::class);
	Assert::equal('Hello world', $application->getName());
});
