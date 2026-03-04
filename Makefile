.PHONY: help install dev serve test lint clean

# Default
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## Install production dependencies
	composer install --no-dev --optimize-autoloader

dev: ## Install all dependencies (including dev)
	composer install

serve: ## Start PHP built-in server on port 8900
	php -S 0.0.0.0:8900 -t public

test: ## Run PHPUnit tests
	./vendor/bin/phpunit

test-coverage: ## Run tests with coverage report
	XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage

lint: ## Check PHP syntax across src/ and tests/
	find src/ tests/ public/ -name '*.php' -exec php -l {} \;

clean: ## Remove generated files
	rm -rf vendor/ coverage/ .phpunit.cache/ .phpunit.result.cache

env: ## Create .env from .env.example (won't overwrite)
	@[ -f .env ] && echo ".env already exists" || (cp .env.example .env && echo ".env created — edit it with your API keys")
