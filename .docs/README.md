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
    helpers:
      - Contributte\Console\Helper\ContainerHelper
```

In fact in console mode / SAPI mode is no http request and thus no URL address. This inconvenience you have to solve by yoursolve.
 
```yaml
console:
    url: https://contributte.org
```

You could also define you custom `helperSet` just in case. There are 2 possible approaches. You can register your
`App\Model\MyCustomHelperSet` as services under `services` section or provide it directly to extesion config `helperSet`.

Already defined service:

```yaml
services:
  customHelperSet: App\Model\MyCustomHelperSet

console:
    helperSet: @customHelperSet
```

Directly defined helperSet:

```yaml
console:
    helperSet: App\Model\MyCustomHelperSet
```

By default helperSet contains 5 helpers, 4 defined in `Symfony\Component\Console\Application` by default and 1 defined
by extension itself. In case of need you're able to add more helpers.

```yaml
console:
    helpers:
      - App\Model\MyReallyGreatHelper
```

## Command

### First, you have to define the command class:

```php

namespace App\Console;

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

### Second, the command needs to be registered in the `dic` (`config.neon`):

```yml
services:
    - App\Console\FooCommand
```

Maybe you will have to flush `temp/cache` directory.

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
