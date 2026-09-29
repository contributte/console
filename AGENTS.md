# Contributte Console

Symfony Console integration for Nette Framework.

## Stack

- Language: PHP >=8.2
- Framework: Nette DI 3.1, plus Symfony Console 7/8
- Tests: Nette Tester; static analysis: PHPStan (level 9); code style: Contributte coding standard

## Development

```bash
make install     # install dependencies
make qa          # PHPStan and code style
make csf         # fix code style
make tests       # run all tests
vendor/bin/tester -s -p php --colors 1 -C tests/Cases/CommandLoader/CommandLoader.phpt   # run one test file
make coverage    # code coverage
```

All targets are defined in the `Makefile`.

## Principles

- KISS: write the simplest code that works; no speculative abstractions.
- DRY: one source of truth; reuse existing code before adding new.
- YAGNI: build what is needed now, not what might be needed later.
- Small, final classes with typed properties and `declare(strict_types = 1)`.
- Every change comes with a test; `make qa tests` must pass before a commit.
