.PHONY: install qa cs csf phpstan tests coverage

install:
	composer update

qa: phpstan cs

cs:
ifdef GITHUB_ACTION
	vendor/bin/codesniffer -q --report=checkstyle src tests | cs2pr
else
	vendor/bin/codesniffer src tests
endif

csf:
	vendor/bin/codefixer src tests

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon

tests:
	vendor/bin/tester -s -p php --colors 1 -C tests/cases

coverage:
ifdef GITHUB_ACTION
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage coverage.xml --coverage-src src tests/cases
else
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage coverage.html --coverage-src src tests/cases
endif
