<?php

/**
 * Test: CommandLoader/CommandLoader
 */

use Contributte\Console\CommandLoader\ContainerCommandLoader;
use Contributte\Console\Exception\Runtime\CommandNotFoundException;
use Nette\DI\Container;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../../bootstrap.php';

if (!interface_exists('Symfony\Component\Console\CommandLoader\CommandLoaderInterface', TRUE)) {
	Environment::skip('CommandLoaderInterface is available from symfony/console 3.4');
}

// Empty loader
test(function () {
	$container = new Container();
	$loader = new ContainerCommandLoader($container, []);

	Assert::exception(function () use ($loader) {
		$loader->get('foo');
	}, CommandNotFoundException::class, 'Command "foo" does not exist.');
});
