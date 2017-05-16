# Console

## Content

- [Usage - how to register](#usage)
- [Configuration - how to configure](#configuration)
- [Command - example command](#command)
- [Entrypoint - console script](#entrypoint)

## Usage

```yaml
extensions:
    console: Contributte\Console\DI\ConsoleExtension
    
```

The extension will look for all commands extending from [`Contributte\Console\Command\AbstractCommand`](https://github.com/contributte/console/blob/master/src/Command/AbstractCommand.php) and automatically add them to the console application. 
That's all. You don't have to be worried about anything else.

## Configuration

```yaml
console:
    name: Acme Project
    version: 1.0
    catchExceptions: true / false
    autoExit: true / false
    url: https://contributte.com
    helperSet: @customHelperSet
```

In fact in console mode / SAPI mode is no http request and thus no URL address. It is inconvenience you have to solve by yoursolve.
 
```yaml
console:
    url: https://contributte.org
```

## Command

```php
use Contributte\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FooCommand extends AbstractCommand
{

	/**
	 * Configure command
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this->setName('foo');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Some magic..
	}

}
```

## Entrypoint

The very last piece of puzzle is console entrypoit. It is simple scripts loading DI container and fire `Contributte\Console\Application::run`.

You can copy & paste to your projects, for example to `<root>/bin/console`.

Make sure you set as executable. `chmod +x <root>/bin/console`.

```php
#!/usr/bin/env php
<?php

/** @var Nette\DI\Container $container */
$container = require __DIR__ . '/../app/bootstrap.php';

// Run application.
$container->getByType(Contributte\Console\Application::class)->run();
```
