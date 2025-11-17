# GitHub Actions Workflows

This directory contains CI/CD workflows for the QuickBooks Web Connector project.

## Workflows

### 1. **Ruby/Rails CI** (`ci.yml`)

Tests the Ruby on Rails gem across multiple Ruby and Rails versions.

**Triggers:**
- Push to any branch

**Matrix:**
- Ruby versions: 2.7, 3.0, 3.1, 3.2, 3.3, 3.4
- Rails versions: 5.1, 5.2, 6.0, 6.1, 7.0, 7.1, 7.2, 8.0

**Jobs:**
- Run Minitest suite
- Test gem installation
- Verify compatibility

---

### 2. **PHP Unit Tests** (`php-tests.yml`)

Tests the Magento 2 module PHP code.

**Triggers:**
- Push to main/master/develop/claude/** branches
- Pull requests
- Only when files in `magento-module/` change

**Matrix:**
- PHP versions: 8.1, 8.2, 8.3

**Jobs:**

#### `phpunit`
- ✅ Run PHPUnit tests on multiple PHP versions
- ✅ Generate code coverage (Clover + HTML)
- ✅ Upload coverage to Codecov (PHP 8.2 only)
- ✅ Upload test results as artifacts
- ✅ Comment PR with coverage report

#### `code-quality`
- ✅ Run PHPStan static analysis (level 5)
- ✅ Run PHP_CodeSniffer (PSR-12 standard)
- ✅ Upload quality reports as artifacts

#### `test-summary`
- ✅ Generate test summary in GitHub Actions UI
- ✅ Show matrix results
- ✅ Display quality check status

---

## Workflow Features

### 🚀 **Performance Optimizations**

1. **Dependency Caching**
   - Composer cache stored between runs
   - Faster dependency installation

2. **Parallel Execution**
   - Multiple PHP versions tested in parallel
   - Quality checks run independently

3. **Conditional Execution**
   - Only runs on relevant file changes
   - Skips unnecessary jobs

### 📊 **Coverage & Reporting**

1. **Code Coverage**
   - Xdebug coverage enabled
   - Clover XML + HTML reports
   - Uploaded to Codecov
   - PR comments with coverage diff

2. **Test Artifacts**
   - JUnit XML for test results
   - Coverage reports (30-day retention)
   - PHPCS reports

3. **Status Checks**
   - Branch protection rules
   - Required checks before merge
   - PR comment integration

### 🔍 **Code Quality**

1. **PHPStan** (Static Analysis)
   - Level 5 strictness
   - Detects type errors
   - Finds bugs before runtime

2. **PHP_CodeSniffer** (Code Style)
   - PSR-12 standard
   - Consistent code formatting
   - Auto-fix available locally

---

## Local Testing

Before pushing, run tests locally:

### Install Dependencies
```bash
composer install
```

### Run PHPUnit Tests
```bash
composer test
```

### Generate Coverage Report
```bash
composer test-coverage
open magento-module/Vendor/QuickbooksConnector/Test/coverage/html/index.html
```

### Run PHPStan
```bash
composer phpstan
```

### Run PHPCS
```bash
composer phpcs
```

### Auto-fix Code Style
```bash
composer phpcbf
```

### Run All Quality Checks
```bash
composer quality
```

### Run Full CI Suite
```bash
composer ci
```

---

## Status Badges

Add these to your README.md:

```markdown
[![PHP Tests](https://github.com/ph4mHoa/qbwc/actions/workflows/php-tests.yml/badge.svg)](https://github.com/ph4mHoa/qbwc/actions/workflows/php-tests.yml)
[![codecov](https://codecov.io/gh/ph4mHoa/qbwc/branch/main/graph/badge.svg)](https://codecov.io/gh/ph4mHoa/qbwc)
[![Ruby Tests](https://github.com/ph4mHoa/qbwc/actions/workflows/ci.yml/badge.svg)](https://github.com/ph4mHoa/qbwc/actions/workflows/ci.yml)
```

---

## Troubleshooting

### Issue: Tests fail locally but pass in CI
**Solution:** Ensure PHP version matches CI (8.1, 8.2, or 8.3)

### Issue: Composer cache not working
**Solution:** Clear local cache: `composer clear-cache`

### Issue: PHPStan errors
**Solution:** Check `phpstan.neon` configuration, adjust level if needed

### Issue: PHPCS failures
**Solution:** Run auto-fix: `composer phpcbf`

### Issue: Coverage not uploading to Codecov
**Solution:** Check `CODECOV_TOKEN` secret in repository settings

---

## Required Secrets

For full functionality, add these secrets in GitHub repository settings:

1. **CODECOV_TOKEN** (optional)
   - Get from https://codecov.io
   - Required for coverage uploads

---

## Workflow Maintenance

### Update PHP Versions

Edit `.github/workflows/php-tests.yml`:

```yaml
matrix:
  php-version:
    - '8.1'
    - '8.2'
    - '8.3'
    - '8.4'  # Add new version
```

### Update PHPUnit Version

Edit `composer.json`:

```json
"require-dev": {
    "phpunit/phpunit": "^10.0"  # Update version
}
```

### Update PHPStan Level

Edit `phpstan.neon`:

```neon
parameters:
    level: 6  # Increase strictness
```

---

## Best Practices

1. ✅ Run `composer ci` before pushing
2. ✅ Fix PHPCS issues with `composer phpcbf`
3. ✅ Review PHPStan warnings
4. ✅ Keep test coverage above 85%
5. ✅ Test on multiple PHP versions locally
6. ✅ Write tests for new features
7. ✅ Update workflows when adding dependencies

---

**Last Updated:** 2025-11-17
**Maintained by:** Development Team
