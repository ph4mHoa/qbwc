# QuickBooks Web Connector - Makefile
# Convenient shortcuts for development tasks

.PHONY: help install test test-unit test-coverage quality phpstan phpcs phpcbf fix ci clean

# Default target
.DEFAULT_GOAL := help

# Colors
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m # No Color

help: ## Show this help message
	@echo "$(GREEN)QuickBooks Web Connector - Development Commands$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(BLUE)%-20s$(NC) %s\n", $$1, $$2}'

install: ## Install dependencies
	@echo "$(GREEN)Installing dependencies...$(NC)"
	composer install --prefer-dist

update: ## Update dependencies
	@echo "$(GREEN)Updating dependencies...$(NC)"
	composer update

test: ## Run unit tests
	@echo "$(GREEN)Running unit tests...$(NC)"
	./scripts/run-tests.sh --unit

test-coverage: ## Run tests with coverage report
	@echo "$(GREEN)Running tests with coverage...$(NC)"
	./scripts/run-tests.sh --coverage

quality: ## Run all quality checks (PHPStan + PHPCS)
	@echo "$(GREEN)Running quality checks...$(NC)"
	./scripts/run-tests.sh --quality

phpstan: ## Run PHPStan static analysis
	@echo "$(GREEN)Running PHPStan...$(NC)"
	vendor/bin/phpstan analyse --memory-limit=1G

phpcs: ## Run PHP_CodeSniffer
	@echo "$(GREEN)Running PHPCS...$(NC)"
	vendor/bin/phpcs --standard=PSR12 --colors \
		magento-module/Vendor/QuickbooksConnector/Model \
		magento-module/Vendor/QuickbooksConnector/Controller \
		magento-module/Vendor/QuickbooksConnector/Api

phpcbf: ## Auto-fix code style issues
	@echo "$(GREEN)Auto-fixing code style...$(NC)"
	./scripts/run-tests.sh --fix

fix: phpcbf ## Alias for phpcbf

ci: ## Run full CI suite (tests + quality)
	@echo "$(GREEN)Running full CI suite...$(NC)"
	./scripts/run-tests.sh

clean: ## Clean generated files and caches
	@echo "$(YELLOW)Cleaning generated files...$(NC)"
	rm -rf magento-module/Vendor/QuickbooksConnector/Test/coverage
	rm -rf vendor
	rm -f composer.lock
	@echo "$(GREEN)Clean complete!$(NC)"

watch: ## Watch for file changes and run tests
	@echo "$(GREEN)Watching for changes...$(NC)"
	@echo "$(YELLOW)Press Ctrl+C to stop$(NC)"
	@while true; do \
		inotifywait -q -r -e modify magento-module/Vendor/QuickbooksConnector/Model \
			magento-module/Vendor/QuickbooksConnector/Controller \
			magento-module/Vendor/QuickbooksConnector/Test \
			2>/dev/null || fswatch -1 -r magento-module/Vendor/QuickbooksConnector 2>/dev/null || \
			(echo "$(YELLOW)Install inotifywait or fswatch for file watching$(NC)" && exit 1); \
		clear; \
		$(MAKE) test; \
	done

coverage-report: ## Open coverage report in browser
	@if [ -f "magento-module/Vendor/QuickbooksConnector/Test/coverage/html/index.html" ]; then \
		echo "$(GREEN)Opening coverage report...$(NC)"; \
		open magento-module/Vendor/QuickbooksConnector/Test/coverage/html/index.html 2>/dev/null || \
		xdg-open magento-module/Vendor/QuickbooksConnector/Test/coverage/html/index.html 2>/dev/null || \
		echo "$(YELLOW)Coverage report: magento-module/Vendor/QuickbooksConnector/Test/coverage/html/index.html$(NC)"; \
	else \
		echo "$(YELLOW)No coverage report found. Run 'make test-coverage' first$(NC)"; \
	fi

version: ## Show version information
	@echo "$(BLUE)Environment Information:$(NC)"
	@echo "PHP Version: $$(php -v | head -n 1)"
	@echo "Composer Version: $$(composer --version 2>/dev/null || echo 'Not installed')"
	@echo "PHPUnit Version: $$(vendor/bin/phpunit --version 2>/dev/null || echo 'Not installed')"
	@echo "PHPStan Version: $$(vendor/bin/phpstan --version 2>/dev/null || echo 'Not installed')"
	@echo "PHPCS Version: $$(vendor/bin/phpcs --version 2>/dev/null || echo 'Not installed')"

check-deps: ## Check if all dependencies are installed
	@echo "$(BLUE)Checking dependencies...$(NC)"
	@command -v php >/dev/null 2>&1 || { echo "$(YELLOW)PHP not found$(NC)"; exit 1; }
	@command -v composer >/dev/null 2>&1 || { echo "$(YELLOW)Composer not found$(NC)"; exit 1; }
	@[ -d "vendor" ] || { echo "$(YELLOW)Vendor directory not found. Run 'make install'$(NC)"; exit 1; }
	@echo "$(GREEN)All dependencies OK!$(NC)"

# Shortcuts
t: test ## Shortcut for test
tc: test-coverage ## Shortcut for test-coverage
q: quality ## Shortcut for quality
c: clean ## Shortcut for clean
i: install ## Shortcut for install
