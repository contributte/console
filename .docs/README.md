# Contributte Console

Integration of [Symfony Console](https://symfony.com/doc/current/console.html) into Nette Framework.

## Content

- [Getting started](#getting-started)
  - [Setup](#setup)
  - [Configuration](#configuration)
  - [Entrypoint](#entrypoint)
- [Commands](#commands)
  - [Example command](#example-command)
  - [Invokable commands](#invokable-commands)
- [UI](#ui)
  - [Styled output](#styled-output)
  - [Cursor control](#cursor-control)
  - [Tree display](#tree-display)
- [Advanced](#advanced)
  - [Shell completion](#shell-completion)
  - [Signal handling](#signal-handling)
  - [Console events](#console-events)
- [Testing](#testing)

---

# Getting started

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

```neon
console:
	helpers:
		- App\Model\MyReallyGreatHelper
```

> See [Console Helpers](https://symfony.com/doc/current/components/console/helpers/index.html) in Symfony docs.

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

---

# Commands

## Example command

In case of having `console.php` as entrypoint (see above), this would add a user with username `john.doe`:

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

> See [Console Commands](https://symfony.com/doc/current/console.html) in Symfony docs.

## Invokable commands

Since Symfony 6.4, you can use `#[Argument]` and `#[Option]` attributes to define command inputs directly on the `__invoke()` method. This approach reduces boilerplate code significantly.

```php
namespace App\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user',
)]
final class CreateUserCommand extends Command
{

    public function __construct(
        private UserFacade $userFacade,
    )
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Username for the new user')]
        string $username,
        #[Argument(description: 'Email address')]
        string $email,
        #[Option(description: 'Grant admin privileges')]
        bool $admin = false,
        #[Option(name: 'send-email', description: 'Send welcome email')]
        bool $sendEmail = true,
    ): int
    {
        $this->userFacade->create($username, $email, $admin);

        $io->success(sprintf('User "%s" created successfully!', $username));

        if ($sendEmail) {
            $io->note('Welcome email has been sent.');
        }

        return Command::SUCCESS;
    }

}
```

Usage:

```bash
php bin/console app:create-user john john@example.com --admin --no-send-email
```

The `#[Argument]` and `#[Option]` attributes support these parameters:

- `name` - Override the argument/option name (defaults to parameter name)
- `description` - Help text shown in `--help`
- `mode` - For arguments: `REQUIRED`, `OPTIONAL`, `IS_ARRAY`
- `shortcut` - For options: single letter shortcut (e.g., `-a` for `--admin`)
- `default` - Default value (can also use PHP default parameter value)

> See [Console Input](https://symfony.com/doc/current/console/input.html) in Symfony docs.

---

# UI

## Styled output

`SymfonyStyle` provides a consistent, beautiful output formatting API. It reduces boilerplate and ensures your commands have a professional look.

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo')]
final class DemoCommand extends Command
{

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Titles and sections
        $io->title('Application Setup');
        $io->section('Step 1: Configuration');

        // Text output
        $io->text('Processing configuration files...');
        $io->text(['Line 1', 'Line 2', 'Line 3']);

        // Lists
        $io->listing(['First item', 'Second item', 'Third item']);

        // Tables
        $io->table(
            ['Name', 'Email', 'Role'],
            [
                ['Alice', 'alice@example.com', 'Admin'],
                ['Bob', 'bob@example.com', 'User'],
            ]
        );

        // Admonition blocks
        $io->note('This is additional information.');
        $io->caution('Be careful with this operation!');
        $io->warning('This action cannot be undone.');

        // Result blocks
        $io->success('All tasks completed successfully!');
        $io->error('Something went wrong.');
        $io->info('Operation finished.');

        return Command::SUCCESS;
    }

}
```

### Interactive prompts

`SymfonyStyle` also simplifies user interaction:

```php
// Simple question
$name = $io->ask('What is your name?', 'Anonymous');

// Hidden input (for passwords)
$password = $io->askHidden('Enter password');

// Confirmation
if ($io->confirm('Do you want to continue?', true)) {
    // ...
}

// Choice selection
$color = $io->choice('Select a color', ['red', 'green', 'blue'], 'blue');
```

### Progress bars

```php
$io->progressStart(100);

for ($i = 0; $i < 100; $i++) {
    // Process item...
    $io->progressAdvance();
}

$io->progressFinish();
```

> See [How to Style a Console Command](https://symfony.com/doc/current/console/style.html) and [Progress Bar](https://symfony.com/doc/current/components/console/helpers/progressbar.html) in Symfony docs.

## Cursor control

The `Cursor` class allows direct manipulation of the terminal cursor position. This is useful for building interactive TUIs, dashboards, or real-time displays.

```php
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Output\OutputInterface;

$cursor = new Cursor($output);

// Movement
$cursor->moveUp(2);          // Move 2 lines up
$cursor->moveDown(1);        // Move 1 line down
$cursor->moveLeft(5);        // Move 5 columns left
$cursor->moveRight(3);       // Move 3 columns right
$cursor->moveToPosition(10, 5); // Move to column 10, row 5

// Visibility
$cursor->hide();             // Hide cursor
$cursor->show();             // Show cursor

// Save/restore position
$cursor->savePosition();     // Save current position
// ... do something ...
$cursor->restorePosition();  // Return to saved position

// Clearing
$cursor->clearLine();        // Clear entire current line
$cursor->clearLineAfter();   // Clear from cursor to end of line
$cursor->clearOutput();      // Clear from cursor to end of screen
$cursor->clearScreen();      // Clear entire screen

// Get current position (returns [column, row])
[$column, $row] = $cursor->getCurrentPosition();
```

### Example: Real-time status display

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:monitor')]
final class MonitorCommand extends Command
{

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cursor = new Cursor($output);
        $cursor->hide();

        $output->writeln('System Monitor');
        $output->writeln('==============');
        $output->writeln('CPU:    ');
        $output->writeln('Memory: ');
        $output->writeln('');
        $output->writeln('Press Ctrl+C to stop');

        for ($i = 0; $i < 100; $i++) {
            $cursor->moveToPosition(8, 3);
            $output->write(sprintf('%3d%%', rand(0, 100)));

            $cursor->moveToPosition(8, 4);
            $output->write(sprintf('%3d%%', rand(0, 100)));

            usleep(500000);
        }

        $cursor->show();

        return Command::SUCCESS;
    }

}
```

> See [Cursor Helper](https://symfony.com/doc/current/components/console/helpers/cursor.html) in Symfony docs.

## Tree display

The `TreeHelper` (Symfony 7.3+) renders hierarchical data as ASCII trees, useful for displaying file structures, dependency trees, or any nested data.

```php
use Symfony\Component\Console\Helper\TreeHelper;
use Symfony\Component\Console\Helper\TreeNode;
use Symfony\Component\Console\Output\OutputInterface;

// Create root node
$root = new TreeNode('src/');

// Add children
$root->addChild(new TreeNode('Console/'))
    ->addChild(new TreeNode('Command/'))
    ->addChild(new TreeNode('CreateUserCommand.php'))
    ->addChild(new TreeNode('ImportCommand.php'));

$root->addChild(new TreeNode('Model/'))
    ->addChild(new TreeNode('User.php'))
    ->addChild(new TreeNode('UserFacade.php'));

$root->addChild(new TreeNode('bootstrap.php'));

// Render
TreeHelper::render($output, $root);
```

Output:
```
src/
├── Console/
│   └── Command/
│       ├── CreateUserCommand.php
│       └── ImportCommand.php
├── Model/
│   ├── User.php
│   └── UserFacade.php
└── bootstrap.php
```

### Building trees from arrays

```php
function buildTree(array $items, TreeNode $parent): void
{
    foreach ($items as $key => $value) {
        if (is_array($value)) {
            $node = new TreeNode($key . '/');
            $parent->addChild($node);
            buildTree($value, $node);
        } else {
            $parent->addChild(new TreeNode($value));
        }
    }
}

$structure = [
    'app' => [
        'Commands' => ['FooCommand.php', 'BarCommand.php'],
        'Models' => ['User.php'],
    ],
    'config' => ['app.neon', 'services.neon'],
];

$root = new TreeNode('project/');
buildTree($structure, $root);
TreeHelper::render($output, $root);
```

> See [Tree Helper](https://symfony.com/doc/current/components/console/helpers/tree.html) in Symfony docs.

---

# Advanced

## Shell completion

Symfony Console provides built-in shell completion for Bash, Zsh, and Fish shells. This allows tab-completion of command names, options, and even argument values.

### Installation

Run the completion command with your shell name to get installation instructions:

```bash
# For Bash
php bin/console completion bash

# For Zsh
php bin/console completion zsh

# For Fish
php bin/console completion fish
```

Each shell has specific setup requirements:

**Bash** - Install the `bash-completion` package first:
```bash
# Debian/Ubuntu
apt install bash-completion

# macOS with Homebrew
brew install bash-completion
```

**Zsh** - Usually works out of the box with Oh My Zsh or similar frameworks.

**Fish** - Automatically discovers completions in `~/.config/fish/completions/`.

### Custom completion values

You can provide custom completion suggestions for your command arguments and options by implementing the `complete()` method:

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;

#[AsCommand(name: 'app:greet')]
final class GreetCommand extends Command
{

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('name')) {
            $suggestions->suggestValues(['Alice', 'Bob', 'Charlie']);
        }

        if ($input->mustSuggestOptionValuesFor('format')) {
            $suggestions->suggestValues(['json', 'xml', 'csv']);
        }
    }

}
```

Now pressing Tab after the command will suggest `Alice`, `Bob`, or `Charlie` for the `name` argument.

> See [How to Add Console Command Completion](https://symfony.com/doc/current/console/completion.html) in Symfony docs.

## Signal handling

For long-running commands (workers, daemons, queue consumers), you may need to handle OS signals like `SIGINT` (Ctrl+C) or `SIGTERM` for graceful shutdown. Implement `SignalableCommandInterface` to subscribe to signals:

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:worker')]
final class WorkerCommand extends Command implements SignalableCommandInterface
{

    private bool $shouldStop = false;

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->shouldStop = true;

        // Return false to continue execution (graceful shutdown)
        // Return an integer to exit immediately with that code
        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Worker started. Press Ctrl+C to stop gracefully.');

        while (!$this->shouldStop) {
            // Process jobs from queue
            $this->processNextJob();

            // Small sleep to prevent CPU spinning
            usleep(100000);
        }

        $output->writeln('Shutting down gracefully...');
        $this->cleanup();

        return Command::SUCCESS;
    }

}
```

Common signals:
- `SIGINT` - Interrupt (Ctrl+C)
- `SIGTERM` - Termination request (default kill signal)
- `SIGQUIT` - Quit with core dump (Ctrl+\)
- `SIGUSR1`, `SIGUSR2` - User-defined signals

> [!NOTE]
> Signal handling requires the `pcntl` PHP extension to be installed.

> See [Console Signals](https://symfony.com/doc/current/components/console/events.html#console-events-signal) in Symfony docs.

## Console events

Symfony Console dispatches events during command execution. You can use these events for logging, profiling, error handling, and more. This extension automatically registers the EventDispatcher if available in the container.

### Available events

| Event | When dispatched |
|-------|-----------------|
| `ConsoleEvents::COMMAND` | Before command execution |
| `ConsoleEvents::TERMINATE` | After command execution (including exceptions) |
| `ConsoleEvents::ERROR` | When an exception is thrown |
| `ConsoleEvents::SIGNAL` | When a signal is received |

### Setup with Nette

First, install the Symfony EventDispatcher:

```bash
composer require symfony/event-dispatcher
```

Register it as a service:

```neon
services:
	eventDispatcher:
		class: Symfony\Component\EventDispatcher\EventDispatcher
```

The console extension will automatically detect and use it.

### Creating event subscribers

```php
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConsoleEventSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private Logger $logger,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
            ConsoleEvents::TERMINATE => 'onTerminate',
            ConsoleEvents::ERROR => 'onError',
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        $this->logger->info('Executing command: ' . $command?->getName());
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $exitCode = $event->getExitCode();
        $this->logger->info('Command finished with exit code: ' . $exitCode);
    }

    public function onError(ConsoleErrorEvent $event): void
    {
        $error = $event->getError();
        $this->logger->error('Command error: ' . $error->getMessage());

        // Optionally change the exit code
        $event->setExitCode(1);
    }

}
```

Register the subscriber:

```neon
services:
	- App\Console\ConsoleEventSubscriber

	eventDispatcher:
		class: Symfony\Component\EventDispatcher\EventDispatcher
		setup:
			- addSubscriber(@App\Console\ConsoleEventSubscriber)
```

> See [Using Console Events](https://symfony.com/doc/current/components/console/events.html) in Symfony docs.

---

# Testing

Symfony Console provides `CommandTester` and `ApplicationTester` for testing commands without executing them in a real terminal.

## Testing a single command

```php
use App\Console\CreateUserCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateUserCommandTest extends TestCase
{

    public function testExecute(): void
    {
        $command = new CreateUserCommand(/* dependencies */);
        $tester = new CommandTester($command);

        $tester->execute([
            'username' => 'john',
            '--admin' => true,
        ]);

        // Assert exit code
        $tester->assertCommandIsSuccessful();
        // or
        $this->assertSame(0, $tester->getStatusCode());

        // Assert output contains expected text
        $output = $tester->getDisplay();
        $this->assertStringContainsString('User "john" created', $output);
    }

}
```

## Testing with Nette DI

For commands with dependencies, use Nette's container:

```php
use Contributte\Tester\Utils\ContainerBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tester\Assert;
use Tester\TestCase;

final class CreateUserCommandTest extends TestCase
{

    public function testCommand(): void
    {
        $container = ContainerBuilder::of()
            ->withCompiler(function ($compiler) {
                $compiler->addConfig(__DIR__ . '/config.neon');
            })
            ->build();

        $application = $container->getByType(Application::class);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(['command' => 'app:create-user', 'username' => 'john']);

        Assert::same(0, $tester->getStatusCode());
        Assert::contains('User "john" created', $tester->getDisplay());
    }

}
```

## Testing interactive commands

For commands with user prompts, use `setInputs()`:

```php
$tester = new CommandTester($command);

// Simulate user typing "yes" then "john@example.com"
$tester->setInputs(['yes', 'john@example.com']);

$tester->execute(['username' => 'john']);
```

## Useful assertions

```php
// Check exit code
$tester->assertCommandIsSuccessful();

// Get output
$output = $tester->getDisplay();
$output = $tester->getDisplay(true); // Normalized (no decorations)

// Get error output (stderr)
$errorOutput = $tester->getErrorOutput();

// Get status code
$exitCode = $tester->getStatusCode();

// Get input used
$input = $tester->getInput();
```

> See [How to Test Commands](https://symfony.com/doc/current/console.html#testing-commands) in Symfony docs.
