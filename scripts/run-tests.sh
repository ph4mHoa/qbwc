#!/bin/bash

################################################################################
# QuickBooks Connector - Local Test Runner
#
# This script runs all tests locally before pushing to CI
#
# Usage:
#   ./scripts/run-tests.sh              # Run all tests
#   ./scripts/run-tests.sh --unit       # Run only unit tests
#   ./scripts/run-tests.sh --coverage   # Run tests with coverage
#   ./scripts/run-tests.sh --quality    # Run only quality checks
#   ./scripts/run-tests.sh --fix        # Auto-fix code style issues
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_header() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if running in project root
if [ ! -f "composer.json" ]; then
    print_error "Please run this script from the project root directory"
    exit 1
fi

# Parse arguments
RUN_UNIT=false
RUN_COVERAGE=false
RUN_QUALITY=false
RUN_FIX=false
RUN_ALL=true

while [[ $# -gt 0 ]]; do
    case $1 in
        --unit)
            RUN_UNIT=true
            RUN_ALL=false
            shift
            ;;
        --coverage)
            RUN_COVERAGE=true
            RUN_ALL=false
            shift
            ;;
        --quality)
            RUN_QUALITY=true
            RUN_ALL=false
            shift
            ;;
        --fix)
            RUN_FIX=true
            RUN_ALL=false
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --unit       Run only unit tests"
            echo "  --coverage   Run tests with coverage report"
            echo "  --quality    Run only quality checks (PHPStan + PHPCS)"
            echo "  --fix        Auto-fix code style issues"
            echo "  --help       Show this help message"
            echo ""
            echo "If no options are provided, all tests and checks will run."
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Display banner
echo -e "${GREEN}"
cat << "EOF"
   ____  ____  _       ______   ______            __
  / __ \/ __ )| |     / / __ \ /_  __/___  _____/ /______
 / / / / __  || | /| / / / / /  / / / __ \/ ___/ __/ ___/
/ /_/ / /_/ / | |/ |/ / /_/ /  / / / /_/ (__  ) /_(__  )
\___\_\_____/  |__/|__/\____/  /_/  \____/____/\__/____/

EOF
echo -e "${NC}"

print_info "Starting test suite..."
echo -e "PHP Version: $(php -v | head -n 1)"
echo -e "Composer Version: $(composer --version)"

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    print_warning "Vendor directory not found. Installing dependencies..."
    composer install --prefer-dist --no-progress
else
    print_success "Dependencies already installed"
fi

# Run Unit Tests
if [ "$RUN_ALL" = true ] || [ "$RUN_UNIT" = true ]; then
    print_header "Running PHPUnit Tests"

    if vendor/bin/phpunit -c magento-module/Vendor/QuickbooksConnector/Test/phpunit.xml.dist --testdox --colors=always; then
        print_success "All unit tests passed!"
    else
        print_error "Unit tests failed!"
        exit 1
    fi
fi

# Run Tests with Coverage
if [ "$RUN_COVERAGE" = true ]; then
    print_header "Running Tests with Code Coverage"

    # Check if Xdebug is installed
    if ! php -m | grep -q xdebug; then
        print_warning "Xdebug not found. Coverage may not work."
        print_info "Install Xdebug: pecl install xdebug"
    fi

    vendor/bin/phpunit \
        -c magento-module/Vendor/QuickbooksConnector/Test/phpunit.xml.dist \
        --testdox \
        --colors=always \
        --coverage-html magento-module/Vendor/QuickbooksConnector/Test/coverage/html \
        --coverage-text

    COVERAGE_FILE="magento-module/Vendor/QuickbooksConnector/Test/coverage/html/index.html"

    if [ -f "$COVERAGE_FILE" ]; then
        print_success "Coverage report generated!"
        print_info "Open: $COVERAGE_FILE"

        # Try to open coverage report in browser (macOS/Linux)
        if command -v open &> /dev/null; then
            open "$COVERAGE_FILE"
        elif command -v xdg-open &> /dev/null; then
            xdg-open "$COVERAGE_FILE"
        fi
    fi
fi

# Run Quality Checks
if [ "$RUN_ALL" = true ] || [ "$RUN_QUALITY" = true ]; then
    print_header "Running PHPStan (Static Analysis)"

    if vendor/bin/phpstan analyse --memory-limit=1G; then
        print_success "PHPStan analysis passed!"
    else
        print_warning "PHPStan found issues (see above)"
    fi

    print_header "Running PHP_CodeSniffer (PSR-12)"

    if vendor/bin/phpcs \
        --standard=PSR12 \
        --colors \
        --report=full \
        magento-module/Vendor/QuickbooksConnector/Model \
        magento-module/Vendor/QuickbooksConnector/Controller \
        magento-module/Vendor/QuickbooksConnector/Api; then
        print_success "Code style check passed!"
    else
        print_warning "Code style issues found (run with --fix to auto-fix)"
    fi
fi

# Auto-fix Code Style
if [ "$RUN_FIX" = true ]; then
    print_header "Auto-fixing Code Style Issues"

    vendor/bin/phpcbf \
        --standard=PSR12 \
        --colors \
        magento-module/Vendor/QuickbooksConnector/Model \
        magento-module/Vendor/QuickbooksConnector/Controller \
        magento-module/Vendor/QuickbooksConnector/Api \
        || true  # phpcbf returns exit code 1 when it fixes files

    print_success "Code style fixes applied!"
    print_info "Run --quality again to verify fixes"
fi

# Final Summary
if [ "$RUN_ALL" = true ]; then
    print_header "Test Suite Complete!"
    print_success "All checks passed! ✨"
    echo ""
    print_info "You can now push your changes to trigger CI"
    echo -e "  ${BLUE}git push${NC}"
fi

exit 0
