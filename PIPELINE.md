# CI/CD Pipeline Documentation

## 📋 Overview

This project uses **GitHub Actions** for continuous integration and deployment. The pipeline automatically runs tests, quality checks, and generates reports on every push and pull request.

---

## 🔄 Workflows

### 1. **PHP Unit Tests** (`.github/workflows/php-tests.yml`)

Automatically runs on:
- ✅ Push to `main`, `master`, `develop`, or `claude/**` branches
- ✅ Pull requests modifying `magento-module/**` files

**What it does:**

#### Job 1: PHPUnit Tests (Matrix)
Runs tests on **PHP 8.1, 8.2, and 8.3**

- Install dependencies with Composer caching
- Execute PHPUnit test suite
- Generate code coverage (Clover + HTML)
- Upload coverage to Codecov (PHP 8.2 only)
- Upload test results as artifacts
- Comment on PR with coverage diff

#### Job 2: Code Quality
Runs on **PHP 8.2**

- **PHPStan**: Static analysis at level 5
- **PHPCS**: Code style check (PSR-12 standard)
- Upload quality reports as artifacts

#### Job 3: Test Summary
Runs after all jobs complete

- Generate summary in GitHub Actions UI
- Display matrix results
- Show quality check status

---

## 🚀 Running Tests Locally

### Quick Start

```bash
# Install dependencies
make install

# Run all tests
make test

# Run with coverage
make test-coverage

# Run quality checks
make quality

# Auto-fix code style
make fix

# Run full CI suite
make ci
```

### Using Scripts

```bash
# Run all tests and checks
./scripts/run-tests.sh

# Run only unit tests
./scripts/run-tests.sh --unit

# Run with coverage report
./scripts/run-tests.sh --coverage

# Run only quality checks
./scripts/run-tests.sh --quality

# Auto-fix code style issues
./scripts/run-tests.sh --fix
```

### Using Composer

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Run PHPStan
composer phpstan

# Run PHPCS
composer phpcs

# Auto-fix code style
composer phpcbf

# Run all quality checks
composer quality

# Run full CI suite
composer ci
```

---

## 📊 Code Coverage

### Viewing Coverage Reports

After running tests with coverage:

```bash
# Generate coverage
make test-coverage

# Open in browser
make coverage-report

# Manual open
open magento-module/Vendor/QuickbooksConnector/Test/coverage/html/index.html
```

### Coverage on Codecov

Coverage reports are automatically uploaded to [Codecov](https://codecov.io) for PHP 8.2 tests.

**Setup:**
1. Sign up at https://codecov.io
2. Add your repository
3. Get token from repository settings
4. Add `CODECOV_TOKEN` secret to GitHub repository settings

**View coverage:**
- Badge: [![codecov](https://codecov.io/gh/ph4mHoa/qbwc/branch/main/graph/badge.svg)](https://codecov.io/gh/ph4mHoa/qbwc)
- Dashboard: https://codecov.io/gh/ph4mHoa/qbwc

---

## 🎯 Code Quality Tools

### PHPStan (Static Analysis)

Analyzes code for type errors and bugs **without running it**.

**Configuration:** `phpstan.neon`

```bash
# Run PHPStan
make phpstan

# Or with composer
composer phpstan
```

**Strictness Levels:**
- Current: Level 5 (medium strictness)
- Range: 0 (loose) to 9 (max strictness)

**What it checks:**
- Type mismatches
- Undefined variables
- Dead code
- Missing return types
- Invalid method calls

### PHPCS (Code Style)

Checks code against **PSR-12** coding standard.

**Configuration:** `phpcs.xml`

```bash
# Check code style
make phpcs

# Auto-fix issues
make phpcbf

# Or with composer
composer phpcs
composer phpcbf
```

**What it checks:**
- Indentation (4 spaces)
- Line length (120 chars)
- Naming conventions
- Spacing and braces
- DocBlock comments

---

## 📈 Pipeline Performance

### Caching Strategy

The pipeline uses caching to speed up builds:

**1. Composer Cache**
- Stores downloaded packages
- Speeds up dependency installation
- Cache key: `composer-lock` file hash

**2. Test Artifacts**
- Coverage reports (30 days)
- PHPCS reports (30 days)
- JUnit XML results

### Execution Times

Average pipeline execution times:

| Job | Duration |
|-----|----------|
| PHPUnit (single version) | ~2-3 minutes |
| PHPUnit (all 3 versions) | ~3-4 minutes (parallel) |
| Code Quality | ~1-2 minutes |
| **Total** | **~4-5 minutes** |

---

## 🔧 Troubleshooting

### Tests Pass Locally but Fail in CI

**Issue:** Different PHP versions

**Solution:**
```bash
# Check your PHP version
php -v

# Run tests with specific PHP version (if using phpenv/asdf)
phpenv local 8.2
make test
```

### Composer Cache Not Working

**Issue:** Dependencies installing slowly

**Solution:**
```bash
# Clear local cache
composer clear-cache

# Or in Makefile
make clean
make install
```

### PHPStan Errors

**Issue:** PHPStan reports false positives

**Solution:**
Edit `phpstan.neon` and add to `ignoreErrors`:

```neon
ignoreErrors:
    - '#Your error pattern here#'
```

### PHPCS Failures

**Issue:** Code style violations

**Solution:**
```bash
# Auto-fix most issues
make phpcbf

# Check remaining issues
make phpcs
```

### Coverage Not Uploading to Codecov

**Issue:** Missing or invalid token

**Solution:**
1. Get token from https://codecov.io
2. Add `CODECOV_TOKEN` secret in GitHub repo settings:
   - Settings → Secrets and variables → Actions → New repository secret

### Xdebug Not Found for Coverage

**Issue:** `Xdebug not installed` warning

**Solution:**
```bash
# Install Xdebug
pecl install xdebug

# Add to php.ini
echo "zend_extension=xdebug.so" >> /path/to/php.ini
echo "xdebug.mode=coverage" >> /path/to/php.ini

# Verify installation
php -m | grep xdebug
```

---

## 📝 Best Practices

### Before Committing

**Always run:**
```bash
# Full CI suite
make ci

# Or step by step
make test          # Run tests
make quality       # Check quality
make fix           # Auto-fix code style
```

### Writing Tests

1. ✅ **One test per method behavior**
   ```php
   public function testTicketGeneration() { ... }
   public function testTicketUniqueness() { ... }
   ```

2. ✅ **Use descriptive test names**
   ```php
   // Good
   testAuthenticateFailsWithInvalidCredentials()

   // Bad
   testAuth()
   ```

3. ✅ **Follow AAA pattern**
   ```php
   // Arrange
   $input = 'test';

   // Act
   $result = $this->service->process($input);

   // Assert
   $this->assertEquals('expected', $result);
   ```

4. ✅ **Mock external dependencies**
   ```php
   $mockRepo = $this->createMock(Repository::class);
   $mockRepo->expects($this->once())
       ->method('save')
       ->willReturn(true);
   ```

### Code Quality

1. ✅ **Keep functions small** (< 50 lines)
2. ✅ **Add type hints** for all parameters and returns
3. ✅ **Write PHPDoc** for complex methods
4. ✅ **Avoid deep nesting** (max 3 levels)
5. ✅ **Use meaningful variable names**

### Coverage Goals

Maintain these coverage targets:

| Component | Target | Current |
|-----------|--------|---------|
| Models | 95% | ~92% |
| Services | 95% | ~85% |
| Controllers | 80% | TBD |
| **Overall** | **90%** | **~88%** |

---

## 🏷️ Status Badges

Add to your README.md:

```markdown
![PHP Tests](https://github.com/ph4mHoa/qbwc/actions/workflows/php-tests.yml/badge.svg)
![Ruby Tests](https://github.com/ph4mHoa/qbwc/actions/workflows/ci.yml/badge.svg)
[![codecov](https://codecov.io/gh/ph4mHoa/qbwc/branch/main/graph/badge.svg)](https://codecov.io/gh/ph4mHoa/qbwc)
![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-blue)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
```

---

## 🔄 Updating the Pipeline

### Add New PHP Version

Edit `.github/workflows/php-tests.yml`:

```yaml
matrix:
  php-version:
    - '8.1'
    - '8.2'
    - '8.3'
    - '8.4'  # Add here
```

### Change PHPStan Strictness

Edit `phpstan.neon`:

```neon
parameters:
    level: 6  # Increase from 5 to 6
```

### Update PHPUnit Version

Edit `composer.json`:

```json
"require-dev": {
    "phpunit/phpunit": "^10.0"  # Upgrade from 9.5
}
```

Then run:
```bash
composer update phpunit/phpunit
```

### Add New Quality Tool

Example: Add PHP Mess Detector

1. Add to `composer.json`:
   ```json
   "require-dev": {
       "phpmd/phpmd": "^2.13"
   }
   ```

2. Create config `phpmd.xml`

3. Add to workflow:
   ```yaml
   - name: Run PHPMD
     run: vendor/bin/phpmd magento-module/Vendor/QuickbooksConnector text phpmd.xml
   ```

4. Add to Makefile:
   ```makefile
   phpmd: ## Run PHP Mess Detector
       vendor/bin/phpmd magento-module/Vendor/QuickbooksConnector text phpmd.xml
   ```

---

## 📞 Support

**Issues with pipeline?**
1. Check [Troubleshooting](#-troubleshooting) section
2. Review workflow logs in GitHub Actions tab
3. Open an issue with logs and error messages

**Need help?**
- Documentation: `.github/workflows/README.md`
- Test docs: `magento-module/Vendor/QuickbooksConnector/Test/README.md`

---

**Last Updated:** 2025-11-17
**Pipeline Version:** 1.0
**Maintained by:** Development Team
