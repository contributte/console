<?php declare(strict_types = 1);

use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Symfony\Component\Console\Application;
use Tester\Assert;
use Tests\Fixtures\FooHelperSet;

require_once __DIR__ . '/../../bootstrap.php';

// Default helperSet
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
		})->build();

	// 4 default helpers
	Assert::count(4, $container->getByType(Application::class)->getHelperSet()->getIterator());
});

// Own helperSet
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
					helperSet: Tests\Fixtures\FooHelperSet
			NEON));
		})->build();

	// Our helper set
	Assert::type(FooHelperSet::class, $container->getByType(Application::class)->getHelperSet());
});

// Own helperSet as service
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
					helperSet: @Tests\Fixtures\FooHelperSet

				services:
					- Tests\Fixtures\FooHelperSet
			NEON));
		})->build();

	// Our helper set
	Assert::type(FooHelperSet::class, $container->getByType(Application::class)->getHelperSet());
});

// Own helper
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
					helpers:
						- Tests\Fixtures\FooHelper
			NEON));
		})->build();

	// 4 default helpers
	// 1 foo helper
	Assert::count(5, $container->getByType(Application::class)->getHelperSet()->getIterator());
});

// Null helperSet
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('console', new ConsoleExtension(true));
				$compiler->addConfig(Neonkit::load(<<<'NEON'
					console:
						helperSet: null
				NEON));
			})->build();
	}, InvalidConfigurationException::class, "~The item 'console.+helperSet' expects to be string\|Nette\\\\DI\\\\Definitions\\\\Statement, null given\.~");
});
