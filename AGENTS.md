# Contributte Console

Instructions for AI coding agents working in this repository.

## Overview

`contributte/console` integrates Symfony Console into Nette Framework. Every service that extends
`Symfony\Component\Console\Command\Command` becomes a lazy-loaded command of one `Contributte\Console\Application`.
It is a library with one DI extension, not an application or a set of ready-made commands.

- **PHP**: 8.2 to 8.5 (`>=8.2` in `composer.json`)
- **Package**: `contributte/console`, namespace `Contributte\Console\`
- **Extension**: `Contributte\Console\DI\ConsoleExtension`
- **Integrates**: `symfony/console` 7.2+ and 8.x, `nette/di` 3.1.8+
- **Optional**: `nette/http` for `console.url`, `symfony/event-dispatcher` for console events (both only in
  `require-dev`)

## Documentation

- `.docs/README.md` is the user documentation and the page on contributte.org. Update it in the same pull request
  when configuration or behaviour changes.
- Organization rules for code, tests and tooling are in
  [contributte/contributte specs](https://github.com/contributte/contributte/tree/master/specs).

## Commands

```bash
# Install dependencies
make install

# Run all checks (PHPStan level 9 + code style), does not run tests
make qa

# Fix code style
make csf

# Run all tests, or one file
make tests
vendor/bin/tester -s -p php --colors 1 -C tests/Cases/DI/ConsoleExtension.phpt

# Generate code coverage (coverage.html)
make coverage
```

CI runs the tests on PHP 8.2 to 8.5 and once on PHP 8.2 with `--prefer-lowest`.

## Conventions

- Tests are Nette Tester `.phpt` files in `tests/Cases`, with `Toolkit::test()` and containers built by
  `ContainerBuilder` from `contributte/tester`. Test commands, helpers and request factories live in
  `tests/Fixtures`.
- Extension tests are split by feature: `ConsoleExtension.lazy.phpt`, `ConsoleExtension.tags.phpt`,
  `ConsoleExtension.dynamic.phpt`, `ConsoleExtension.EventDispatcher.phpt`.
- `ConsoleExtension`, `Application`, `ContainerCommandLoader` and `ConsoleRequestFactory` are not `final`. They are
  public API that users may extend, so keep them open until a major release.
- Exception messages are asserted in tests. Changing a message means changing its test.

## Traps

- **The extension does nothing outside CLI mode.** It is registered as `ConsoleExtension(%consoleMode%)`; with
  `false` no service is added, so every test constructs it with `true`.
- **Command names are resolved at compile time.** `beforeCompile()` reads the `console.command` tag (a string or
  `{name: ...}`), then `#[AsCommand]`; a command with neither fails the container build with
  `ServiceCreationException`, not the first run.
- **The `#[AsCommand]` name is split on `|`.** The first part is the name, the rest are aliases, and an empty first
  part means a hidden command. See `tests/Fixtures/FooAliasCommand.php` and `FooHiddenCommand.php`.
- **A `console.command` tag skips the attribute completely.** Aliases from `#[AsCommand]` are ignored, and a tag
  name that differs from the command's own name makes `Application::all()` throw (`ConsoleExtension.tags.phpt`).
- **Commands are lazy.** `ContainerCommandLoader` gets a command from the container only when it runs. Don't add
  code that needs every command instance at boot; tests assert `isCreated()` is `false`.
- **`console.url` replaces `http.requestFactory` only when it is the default `RequestFactory`.** With a custom
  factory the build fails on purpose; keep that error. Without `nette/http` the option is silently ignored.
- **The event dispatcher is picked up by type.** `beforeCompile()` calls `getDefinitionByType()` for
  `EventDispatcherInterface` and ignores `MissingServiceException`, so the package must keep working without
  `symfony/event-dispatcher`.
- **Only `url`, `name`, `version` and `catchExceptions` accept dynamic parameters.** `autoExit`, `helperSet` and
  `helpers` are read at compile time. The dynamic cases are tested in `ConsoleExtension.phpt` and
  `ConsoleExtension.dynamic.phpt`.
- Usage, configuration and examples for users live in `.docs/README.md`, not here.
