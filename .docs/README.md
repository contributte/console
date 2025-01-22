# Contributte Console

Integration of [Symfony Console](https://symfony.com/doc/current/components/console.html) into Nette Framework.

## Content

- [Setup](#usage)
- [Configuration](#configuration)
- [Example command](#example-command)
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
```

In SAPI (CLI) mode, there is no HTTP request and thus no URL address.
You have to set base URL on your own so that link generator works. Use `console.url` option:

```neon
console:
	url: https://example.com
```

### Helpers

You have the option to define your own helperSet if needed. There are two methods to do this. One way is to register your `App\Model\MyCustomHelperSet` as a service in the services section.
Alternatively, you can directly provide it to the extension configuration helperSet.

```neon
console:
  # directly
	helperSet: App\Model\MyCustomHelperSet

	# or reference service
	helperSet: @customHelperSet

services:
	customHelperSet: App\Model\MyCustomHelperSet
```

By default, helperSet contains 4 helpers defined in `Symfony\Component\Console\Application`. You can add your own helpers to the helperSet.

```php

```neon
console:
	helpers:
		- App\Model\MyReallyGreatHelper
```

### Lazy-loading

By default, all commands are registered in the console application during the extension registration. This means that all commands are instantiated and their dependencies are injected.
This can be a problem if you have a lot of commands and you don't need all of them at once. In this case, this extension setup lazy-loading of commands.
This means that commands are instantiated only when they are needed.

```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:foo')]
class FooCommand extends Command
{
}
```

Or via a service tag.

```neon
services:
	commands.foo:
		class: App\FooCommand
		tags: [console.command: app:foo]
		# or
		tags: [console.command: {name: app:foo}]
```

## Example command

In case of having `console.php` as entrypoint (see below), this would add a user with username `john.doe`:

> `php console.php user:add john.doe`

```php
namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:foo',
    description: 'Adds user with given username to database',
)]
final class AddUserCommand extends Command
{

	private UserFacade $userFacade;

	public function __construct(UserFacade $userFacade)
	{
		parent::__construct();
		$this->userFacade = $usersFacade;
	}

	protected function configure(): void
	{
	  $this->addArgument('username', InputArgument::REQUIRED, "User's username");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// retrieve passed arguments/options
		$username = $input->getArgument('username');

		// you can use symfony/console output
		$output->writeln(\sprintf('Adding user %s…', $username));

		try {
			// do your logic
			$this->usersModel->add($username);
			// styled output is supported as well
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

Register your command as a service in NEON file.

```neon
services:
	- App\Console\AddUserCommand
```

> [!IMPORTANT]
> Remember! Flush `temp/cache` directory before running the command.

## Entrypoint

The very last piece of the puzzle is the console entrypoint. It is a simple script that loads the DI container and fires  `Contributte\Console\Application::run`.

You can copy & paste it to your project, for example to `<root>/bin/console`.

Make sure to set it as executable. `chmod +x <root>/bin/console`.

```php
#!/usr/bin/env php
<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

exit(App\Bootstrap::boot()
	->createContainer()
	->getByType(Symfony\Component\Console\Application::class)
	->run());
```
