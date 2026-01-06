<?php declare(strict_types = 1);

use Contributte\Console\Application;
use Contributte\Console\CommandLoader\ContainerCommandLoader;
use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Tester\Assert;
use Tests\Fixtures\FooCommand;

require_once __DIR__ . '/../../bootstrap.php';

// 1 command of type FooCommand lazy-loading
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

	$loader = $container->getByType(CommandLoaderInterface::class);
	Assert::type(ContainerCommandLoader::class, $loader);
	Assert::same(['app:foo'], $loader->getNames());
});
