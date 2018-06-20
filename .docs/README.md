# Console

## Content

- [Usage - how to register](#usage)
- [Configuration - how to configure](#configuration)
- [Command - example command](#command)
- [Entrypoint - console script](#entrypoint)

## Usage

```yaml
extensions:
    console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
```

The extension will look for all commands extending from [`Symfony\Component\Console\Command\Command`](https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Console/Command/Command.php) and automatically add them to the console application.
That's all. You don't have to be worried about anything else.

## Configuration

```yaml
console:
    name: Acme Project
    version: 1.0
    catchExceptions: true / false
    autoExit: true / false
    url: https://contributte.com
    lazy: false
    helperSet: @customHelperSet
    helpers:
      - Contributte\Console\Helper\ContainerHelper
```

In fact in SAPI (CLI) mode there is no http request and thus no URL address. This inconvenience you have to solve by yourselve. Well, via `console.url` option.

```yaml
console:
    url: https://contributte.org
```

### HelperSet

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

### Lazy-loading

From version 3.4 of Symfony\Console uses command lazy-loading. This extension fully supports this feature and
you can enable it in NEON file.

```yaml
console:
    lazy: true
```

From this point, all commands are instanced only if needed. Don't forget, that listing all commands will instance all of them.

How to define command names? Define `$defaultName` in command or via `console.command` tag on the service.

```php
use Symfony\Component\Console\Command\Command;

class FooCommand extends Command
{
    protected static $defaultName = 'app:foo';
}
```

Or via service tag.

```yaml
services:
    commands.foo:
        class: App\FooCommand
        tags: [console.command: app:foo]
```

## Command

### Create command

```php

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FooCommand extends Command
{

	protected function configure(): void
	{
		$this->setName('foo');
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		// Some magic..
	}

}
```

### Register command

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

// Get application from DI container.
$application = $container->getByType(Contributte\Console\Application::class);

// Run application.
exit($application->run());
```
