<?php declare(strict_types = 1);

/**
 * Test: CommandLoader/CommandLoader
 */

use Contributte\Console\CommandLoader\ContainerCommandLoader;
use Contributte\Console\Exception\Runtime\CommandNotFoundException;
use Nette\DI\Container;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

// Empty loader
test(function (): void {
	$container = new Container();
	$loader = new ContainerCommandLoader($container, []);

	Assert::exception(function () use ($loader): void {
		$loader->get('foo');
	}, CommandNotFoundException::class, 'Command "foo" does not exist.');
});
