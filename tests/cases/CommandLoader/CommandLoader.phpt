<?php declare(strict_types = 1);

use Contributte\Console\CommandLoader\ContainerCommandLoader;
use Contributte\Console\Exception\Runtime\CommandNotFoundException;
use Contributte\Tester\Toolkit;
use Nette\DI\Container;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Empty loader
Toolkit::test(function (): void {
	$container = new Container();
	$loader = new ContainerCommandLoader($container, []);

	Assert::exception(function () use ($loader): void {
		$loader->get('foo');
	}, CommandNotFoundException::class, 'Command "foo" does not exist.');
});
