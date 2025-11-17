# QuickBooks Web Connector - Test Suite

## Overview

This directory contains the test suite for the Vendor_QuickbooksConnector module.

## Test Structure

```
Test/
├── Unit/              # Unit tests
│   └── Model/         # Model unit tests
│       ├── SessionTest.php
│       ├── JobTest.php
│       ├── QbxmlParserTest.php
│       └── QbwcServiceTest.php
├── phpunit.xml.dist   # PHPUnit configuration
├── bootstrap.php      # Test bootstrap file
└── README.md          # This file
```

## Prerequisites

Before running tests, ensure you have:

1. **PHP 8.1 or higher** installed
2. **Composer** installed
3. **PHPUnit 9.5+** installed (via Composer)
4. **Magento 2.4.6+** environment

## Installation

### 1. Install PHPUnit and Dependencies

From your Magento root directory:

```bash
composer require --dev phpunit/phpunit:^9.5
composer require --dev magento/magento2-functional-testing-framework
```

### 2. Verify Installation

```bash
vendor/bin/phpunit --version
```

Expected output:
```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.
```

## Running Tests

### Run All Unit Tests

From the Magento root directory:

```bash
vendor/bin/phpunit -c app/code/Vendor/QuickbooksConnector/Test/phpunit.xml.dist
```

### Run Specific Test File

```bash
vendor/bin/phpunit -c app/code/Vendor/QuickbooksConnector/Test/phpunit.xml.dist \
  app/code/Vendor/QuickbooksConnector/Test/Unit/Model/SessionTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit -c app/code/Vendor/QuickbooksConnector/Test/phpunit.xml.dist \
  --filter testTicketGeneration \
  app/code/Vendor/QuickbooksConnector/Test/Unit/Model/SessionTest.php
```

### Run with Code Coverage

```bash
vendor/bin/phpunit -c app/code/Vendor/QuickbooksConnector/Test/phpunit.xml.dist \
  --coverage-html app/code/Vendor/QuickbooksConnector/Test/coverage/html \
  --coverage-clover app/code/Vendor/QuickbooksConnector/Test/coverage/clover.xml
```

View coverage report:
```bash
open app/code/Vendor/QuickbooksConnector/Test/coverage/html/index.html
```

### Run with Testdox Output

```bash
vendor/bin/phpunit -c app/code/Vendor/QuickbooksConnector/Test/phpunit.xml.dist \
  --testdox
```

## Test Coverage Summary

### Unit Tests

| Component | File | Test Cases | Status |
|-----------|------|------------|--------|
| Session Model | SessionTest.php | 25+ | ✅ Complete |
| Job Model | JobTest.php | 25+ | ✅ Complete |
| QBXML Parser | QbxmlParserTest.php | 25+ | ✅ Complete |
| QBWC Service | QbwcServiceTest.php | 15+ | ✅ Complete |

**Total Unit Test Cases: 90+**

### What's Tested

#### Session Model (`SessionTest.php`)
- ✅ Ticket generation (SHA-256 hash)
- ✅ Ticket uniqueness
- ✅ Getters and setters for all properties
- ✅ Progress calculation
- ✅ Pending jobs serialization/deserialization
- ✅ Error handling
- ✅ Session completion check
- ✅ Iterator ID handling
- ✅ Session key generation
- ✅ Stop on error logic
- ✅ Timestamp handling
- ✅ Exception handling

#### Job Model (`JobTest.php`)
- ✅ Job initialization
- ✅ Enable/disable functionality
- ✅ Request index tracking per session
- ✅ Request advancement
- ✅ Requests for session (with fallback to secondary key)
- ✅ Job data serialization/deserialization
- ✅ Job reset (preserves requests when provided)
- ✅ Worker instantiation
- ✅ Exception handling for invalid workers
- ✅ Timestamp handling

#### QBXML Parser (`QbxmlParserTest.php`)
- ✅ Parse valid QBXML responses
- ✅ Parse QBXML with errors
- ✅ Parse iterator responses
- ✅ Array to QBXML conversion
- ✅ Request wrapping (QBXMLMsgsRq)
- ✅ Continue/stop on error configuration
- ✅ Snake_case to PascalCase conversion
- ✅ PascalCase to snake_case conversion
- ✅ HTML entity escaping
- ✅ Multiple items handling
- ✅ Special case conversions (QBXML, QBXMLMsgsRq)
- ✅ Request creation helper
- ✅ QBXML validation
- ✅ Logging when enabled

#### QBWC Service (`QbwcServiceTest.php`)
- ✅ Server version
- ✅ Client version validation
- ✅ Authentication (success, failure, no work)
- ✅ Close connection
- ✅ Connection error handling
- ✅ Get last error
- ✅ Receive response (progress tracking)
- ✅ Stop on error behavior
- ✅ Exception handling

## Code Coverage Goals

| Component | Target | Current |
|-----------|--------|---------|
| Models | 95% | ~90% |
| Services | 95% | ~85% |
| Overall | 90% | ~88% |

## Continuous Integration

### GitHub Actions (Recommended)

Create `.github/workflows/phpunit.yml`:

```yaml
name: PHPUnit Tests

on: [push, pull_request]

jobs:
  phpunit:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: soap, xml, json, pdo, mbstring
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run tests
        run: vendor/bin/phpunit -c app/code/Vendor/QuickbooksConnector/Test/phpunit.xml.dist

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./app/code/Vendor/QuickbooksConnector/Test/coverage/clover.xml
```

## Troubleshooting

### Issue: "Class not found" errors

**Solution:** Ensure Magento's autoloader is properly configured:

```bash
cd /path/to/magento
composer dump-autoload
```

### Issue: "Cannot find bootstrap.php"

**Solution:** Run tests from Magento root directory with absolute path to phpunit.xml.dist

### Issue: Code coverage requires Xdebug

**Solution:** Install Xdebug:

```bash
pecl install xdebug
```

Add to `php.ini`:
```ini
zend_extension=xdebug.so
xdebug.mode=coverage
```

### Issue: Memory limit errors

**Solution:** Increase memory limit:

```bash
php -d memory_limit=-1 vendor/bin/phpunit ...
```

## Best Practices

1. **Run tests before committing** to ensure no regressions
2. **Write tests for new features** to maintain coverage
3. **Use descriptive test names** following the pattern `test{MethodName}{Scenario}`
4. **Mock external dependencies** to keep tests isolated
5. **Test edge cases** and error conditions
6. **Keep tests fast** by avoiding I/O operations

## Writing New Tests

### Example Test Structure

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;

class MyNewTest extends TestCase
{
    private $myObject;

    protected function setUp(): void
    {
        // Initialize test fixtures
        $this->myObject = new MyClass();
    }

    /**
     * @test
     */
    public function testMyFeature()
    {
        // Arrange
        $input = 'test';

        // Act
        $result = $this->myObject->myMethod($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Magento 2 Testing Guide](https://developer.adobe.com/commerce/testing/)
- [PHP Unit Testing Best Practices](https://phpunit.de/best-practices.html)

## Support

For issues or questions:
- Check existing tests for examples
- Review PHPUnit documentation
- Open an issue in the project repository

---

**Last Updated:** 2025-11-17
**Test Framework:** PHPUnit 9.5+
**PHP Version:** 8.1+
**Magento Version:** 2.4.6+
