<?php declare(strict_types = 1);

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI\Compiler;
use Nette\DI\Extensions\DIExtension;
use Nette\DI\MissingServiceException;
use Nette\DI\ServiceCreationException;
use Symfony\Component\Console\Command\Command;
use Tester\Assert;
use Tests\Fixtures\FooAliasCommand;
use Tests\Fixtures\FooCommand;
use Tests\Fixtures\FooHiddenCommand;
use Tests\Fixtures\FooRequestFactory;

require_once __DIR__ . '/../../bootstrap.php';

// No commands
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
		})->build();

	Assert::count(0, $container->findByType(Command::class));
});

// 1 command of type FooCommand
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					foo: Tests\Fixtures\FooCommand
			NEON));
		})->build();

	Assert::type(Application::class, $container->getByType(Application::class));
	Assert::false($container->isCreated('foo'));
	Assert::count(1, $container->findByType(Command::class));
	Assert::type(FooCommand::class, $container->getByType(Command::class));
});

// Provide URL using default request factory
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addExtension('http', new HttpExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
					url: https://contributte.org/
			NEON));
		})->build();

	Assert::equal('https://contributte.org/', (string) $container->getService('http.request')->getUrl());
});

// Non-CLI mode
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(false));
		})->build();

	Assert::exception(static function () use ($container): void {
		$container->getByType(Application::class);
	}, MissingServiceException::class);
});

// Config
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
					name: Hello world
					version: 1.0.0
					catchExceptions: false
					autoExit: false
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $application);
	Assert::same('Hello world', $application->getName());
	Assert::same('1.0.0', $application->getVersion());
	Assert::false($application->areExceptionsCaught());
	Assert::false($application->isAutoExitEnabled());
});

// Lazy commands
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					defaultName: Tests\Fixtures\FooCommand
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $application);
	Assert::false($container->isCreated('defaultName'));
	Assert::count(1, $container->findByType(Command::class));
	Assert::true($application->has('app:foo'));
});

// Invalid command
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('console', new ConsoleExtension(true));
				$compiler->addConfig(Neonkit::load(<<<'NEON'
					console:
					services:
						noName: Tests\Fixtures\NoNameCommand
				NEON));
			})->build();
	}, ServiceCreationException::class, 'Command "Tests\Fixtures\NoNameCommand" missing #[AsCommand] attribute');
});

// Always exported
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addExtension('di', new DIExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				di:
					export:
						types: null
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $application);
});

// URL as Dynamic parameter
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->setDynamicParameterNames(['url']);
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addExtension('http', new HttpExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
					url: %url%
				parameters:
					url: https://contributte.org/
			NEON));
		})->build();

	Assert::type(Application::class, $container->getByType(Application::class));
	Assert::equal('https://contributte.org/', (string) $container->getService('http.request')->getUrl());
});

// Name as Dynamic parameter
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->setDynamicParameterNames(['name']);
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
					name: %name%
				parameters:
					name: Hello world
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	Assert::equal('Hello world', $application->getName());
});

// Use custom request Factory
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addExtension('http', new HttpExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					http.requestFactory: Tests\Fixtures\FooRequestFactory
			NEON));
		})->build();

	Assert::equal(FooRequestFactory::CUSTOM_URL, (string) $container->getService('http.request')->getUrl());
});

// Throw error on custom factory and console.url set
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('console', new ConsoleExtension(true));
				$compiler->addExtension('http', new HttpExtension(true));
				$compiler->addConfig(Neonkit::load(<<<'NEON'
					services:
						http.requestFactory: Tests\Fixtures\FooRequestFactory
					console:
						url: https://contributte.org/
				NEON));
			})->build();
	}, ServiceCreationException::class, 'Custom http.requestFactory is used, argument console.url should be removed.');
});

// 1 command with aliases
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					foo: Tests\Fixtures\FooAliasCommand
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $application);
	Assert::false($container->isCreated('foo'));
	Assert::count(1, $container->findByType(Command::class));
	Assert::type(FooAliasCommand::class, $container->getByType(Command::class));
	$application->all();
});

// 1 hidden command
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
				services:
					foo: Tests\Fixtures\FooHiddenCommand
			NEON));
		})->build();

	$application = $container->getByType(Application::class);
	Assert::type(Application::class, $application);
	Assert::false($container->isCreated('foo'));
	Assert::count(1, $container->findByType(Command::class));
	Assert::type(FooHiddenCommand::class, $container->getByType(Command::class));
	$application->all();
});
