![](https://heatbadger.now.sh/github/readme/contributte/console/)

<p align=center>
  <a href="https://github.com/contributte/console/actions"><img src="https://badgen.net/github/checks/contributte/console/master?cache=300"></a>
  <a href="https://codecov.io/gh/contributte/console"><img src="https://badgen.net/codecov/c/github/contributte/console"></a>
  <a href="https://packagist.org/packages/contributte/console"><img src="https://badgen.net/packagist/dm/contributte/console"></a>
  <a href="https://packagist.org/packages/contributte/console"><img src="https://badgen.net/packagist/v/contributte/console"></a>
</p>
<p align=center>
  <a href="https://packagist.org/packages/contributte/console"><img src="https://badgen.net/packagist/php/contributte/console"></a>
  <a href="https://github.com/contributte/console"><img src="https://badgen.net/github/license/contributte/console"></a>
  <a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
  <a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
  <a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/sponsor/donations/F96854"></a>
</p>

<p align=center>
Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

Contributte Console integrates [Symfony Console](https://symfony.com/doc/current/console.html) into Nette Framework.
Every service that extends `Symfony\Component\Console\Command\Command` becomes a command, created only when it runs.
Adding a command means writing one class and registering it as a service.

<p align=center>
  <img src="https://github.com/contributte/console/blob/master/.docs/assets/console.png">
</p>

## Usage

To install the latest version of `contributte/console`, use [Composer](https://getcomposer.org):

```bash
composer require contributte/console
```

Requires PHP 8.2 or later, `nette/di` 3.1.8 or later and `symfony/console` 7.2 or later.

Register the extension in your `config.neon`. It registers services only in CLI mode, so pass `%consoleMode%`:

```neon
extensions:
	console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
```

Write a command and name it with `#[AsCommand]`:

```php
namespace App\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:hello', description: 'Prints a greeting')]
final class HelloCommand extends Command
{

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('Hello from Nette');

		return Command::SUCCESS;
	}

}
```

Register it as a service:

```neon
services:
	- App\Console\HelloCommand
```

With the entrypoint from the [documentation](.docs/README.md#entrypoint) saved as `bin/console`, run the command:

```bash
php bin/console app:hello
```

## Documentation

For details on how to use this package, check out the [documentation](.docs).

## Versions

| State  | Version   | Branch   | Nette  | PHP     |
|--------|-----------|----------|--------|---------|
| dev    | `^0.12.0` | `master` | `3.2+` | `>=8.2` |
| stable | `^0.11.0` | `master` | `3.2+` | `>=8.2` |

## Development

See [how to contribute](https://contributte.org/contributing.html) to this package.

This package is currently maintained by these authors.

<a href="https://github.com/f3l1x">
  <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>

-----

Consider [supporting](https://contributte.org/partners.html) the **contributte** development team.
Thank you for using this package.
