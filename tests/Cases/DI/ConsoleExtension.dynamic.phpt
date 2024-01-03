<?php declare(strict_types = 1);

use Contributte\Console\Application;
use Contributte\Console\DI\ConsoleExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// catchException
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('console', new ConsoleExtension(true));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				console:
					catchExceptions: %catchExceptions%
			NEON
			));
			$compiler->setDynamicParameterNames(['catchExceptions']);
		})->buildWith([
			'catchExceptions' => false,
		]);

	$application = $container->getByType(Application::class);
	assert($application instanceof Application);
	Assert::false($application->areExceptionsCaught());
});
