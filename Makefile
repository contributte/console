.PHONY: qa cs csf phpstan tests coverage-clover coverage-html

all:
	@awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"}'
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# QA

qa: cs phpstan ## Check code quality - coding style and static analysis

cs: ## Check PHP files coding style
	vendor/bin/phpcs --cache=var/tmp/codesniffer.dat --standard=ruleset.xml --extensions=php,phpt --colors -nsp src tests

csf: ## Fix PHP files coding style
	vendor/bin/phpcbf --cache=var/tmp/codesniffer.dat --standard=ruleset.xml --extensions=php,phpt --colors -nsp src tests

phpstan: ## Analyse code with PHPStan
	vendor/bin/phpstan analyse -l max -c phpstan.neon src

# Tests

tests: ## Run all tests
	vendor/bin/tester -s -p php --colors 1 -C tests/cases

coverage-clover: ## Generate code coverage in Clover XML format
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./var/tmp/coverage.xml --coverage-src ./src tests/cases

coverage-html: ## Generate code coverage in HTML format
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./var/tmp/coverage.html --coverage-src ./src tests/cases
