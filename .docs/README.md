# Console

## Content

- [Setup](#usage)
- [Configuration](#configuration)
- [Example command](#command)
- [Entrypoint](#entrypoint)

## Setup

```bash
composer require contributte/console
```

```yaml
extensions:
    console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
```

The extension will look for all commands extending from [`Symfony\Component\Console\Command\Command`](https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Console/Command/Command.php) and automatically add them to the console application.
That's all. You don't have to worry about anything else.

## Configuration

```yaml
console:
    name: Acme Project
    version: '1.0'
    catchExceptions: true / false
    autoExit: true / false
    url: https://contributte.com
    lazy: false
    applicationClass: Project\Console\Application
```

In SAPI (CLI) mode there is no http request and thus no URL address. This is an inconvenience you have to solve by yourself - via the `console.url` option.

```yaml
console:
    url: https://contributte.org
```

### Helpers

You could also define you custom `helperSet` just in case. There are 2 possible approaches. You can register your
`App\Model\MyCustomHelperSet` as a service under the `services` section or provide it directly to the extension config `helperSet`.

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

By default, helperSet contains 4 helpers defined in `Symfony\Component\Console\Application`. You can add more helpers, if need them.

```yaml
console:
    helpers:
      - App\Model\MyReallyGreatHelper
```

### Lazy-loading

From version 3.4 `Symfony\Console` uses command lazy-loading. This extension fully supports this feature and
you can enable it in the NEON file.

```yaml
console:
    lazy: true
```

From this point forward, all commands are instantiated only if needed. Don't forget that listing all commands will instantiate them all.

How to define command names? Define `$defaultName` in the command or via the `console.command` tag on the service.

```php
use Symfony\Component\Console\Command\Command;

class FooCommand extends Command
{
    protected static $defaultName = 'app:foo';
}
```

Or via a service tag.

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

Maybe you will have to flush the `temp/cache` directory.

## Entrypoint

The very last piece of the puzzle is the console entrypoint. It is a simple script that loads the DI container and fires  `Contributte\Console\Application::run`.

You can copy & paste it to your project, for example to `<root>/bin/console`.

Make sure to set it as executable. `chmod +x <root>/bin/console`.

##### Nette 3.0+

```php
#!/usr/bin/env php
<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

exit(App\Bootstrap::boot()
    ->createContainer()
    ->getByType(Contributte\Console\Application::class)
    ->run());
```

##### Nette <= 2.4

```php
#!/usr/bin/env php
<?php declare(strict_types = 1);

$container = require __DIR__ . '/../app/bootstrap.php';

exit($container->getByType(Contributte\Console\Application::class)->run());
```
