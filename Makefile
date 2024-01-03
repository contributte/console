.PHONY: install
install:
	composer update

.PHONY: qa
qa: phpstan cs

.PHONY: cs
cs:
ifdef GITHUB_ACTION
	vendor/bin/phpcs --standard=ruleset.xml --encoding=utf-8 --extensions="php,phpt" --colors -nsp -q --report=checkstyle src tests | cs2pr
else
	vendor/bin/phpcs --standard=ruleset.xml --encoding=utf-8 --extensions="php,phpt" --colors -nsp src tests
endif

.PHONY: csf
csf:
	vendor/bin/phpcbf --standard=ruleset.xml --encoding=utf-8 --extensions="php,phpt" --colors -nsp src tests

.PHONY: phpstan
phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon

.PHONY: tests
tests:
	vendor/bin/tester -s -p php --colors 1 -C tests/Cases

.PHONY: coverage
coverage:
ifdef GITHUB_ACTION
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage coverage.xml --coverage-src src tests/Cases
else
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage coverage.html --coverage-src src tests/Cases
endif
