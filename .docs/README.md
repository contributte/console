# Console

## Content

- [Setup](#usage)
- [Configuration](#configuration)
- [Example command](#example)
- [Entrypoint](#entrypoint)

## Setup

```bash
composer require contributte/console
```

```neon
extensions:
	console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
```

The extension will look for all commands extending from [`Symfony\Component\Console\Command\Command`](https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Console/Command/Command.php) and automatically add them to the console application.
That's all. You don't have to worry about anything else.

## Configuration

```neon
console:
	name: Acme Project
	version: '1.0'
	catchExceptions: true / false
	autoExit: true / false
	url: https://example.com
	lazy: false
```

In SAPI (CLI) mode, there is no HTTP request and thus no URL address.
You have to set base URL on your own so that link generator works. Use `console.url` option:

```neon
console:
	url: https://example.com
```

### Helpers

You could also define you custom `helperSet` just in case. There are 2 possible approaches. You can register your
`App\Model\MyCustomHelperSet` as a service under the `services` section or provide it directly to the extension config `helperSet`.

Already defined service:

```neon
services:
	customHelperSet: App\Model\MyCustomHelperSet

console:
	helperSet: @customHelperSet
```

Directly defined helperSet:

```neon
console:
	helperSet: App\Model\MyCustomHelperSet
```

By default, helperSet contains 4 helpers defined in `Symfony\Component\Console\Application`. You can add more helpers, if need them.

```neon
console:
	helpers:
		- App\Model\MyReallyGreatHelper
```

### Lazy-loading

From version 3.4 `Symfony\Console` uses command lazy-loading. This extension fully supports this feature and
you can enable it in the NEON file.

```neon
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

```neon
services:
	commands.foo:
		class: App\FooCommand
		tags: [console.command: app:foo]
```

## Example command

In case of having `console.php` as entrypoint (see below), this would add a user with username `john.doe` to database:

> `php console.php user:add john.doe`

```php
namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AddUserCommand extends Command
{

	private UsersModel $usersModel;

	/**
	 * Pass dependencies with constructor injection
	 */
	public function __construct(UsersModel $usersModel)
	{
		parent::__construct(); // don't forget parent call as we extends from Command
		$this->usersModel = $usersModel;
	}

	protected function configure(): void
	{
		// choose command name
		$this->setName('user:add')
			// description (optional)
			->setDescription('Adds user with given username to database')
			// arguments (maybe required or not)
			->setArgument('username', InputArgument::REQUIRED, 'User\'s username');
			// you can list options as well (refer to symfony/console docs for more info)
	}

	/**
	 * Don't forget to return 0 for success or non-zero for error
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// retrieve passed arguments/options
		$username = $input->getArgument('username');

		// you can use symfony/console output
		$output->writeln(\sprintf('Adding user %s…', $username));

		try {
			// do your logic
			$this->usersModel->add($username);
			// styled output as well
			$output->writeln('<success>✔ Successfully added</success>');
			return 0;

		} catch (\Exception $e) {
			// handle error
			$output->writeln(\sprintf(
				'<error>❌ Error occurred: </error>',
				$e->getMessage(),
			));
			return 1;
		}
	}

}
```

### Register command

```neon
services:
	- App\Console\AddUserCommand
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
