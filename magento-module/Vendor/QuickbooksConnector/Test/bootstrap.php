<?php
/**
 * PHPUnit Bootstrap File
 *
 * This file sets up the test environment for running PHPUnit tests.
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */

// Define Magento root directory
$magentoRoot = dirname(dirname(dirname(dirname(dirname(__DIR__)))));

// Include Magento's autoloader if available
if (file_exists($magentoRoot . '/vendor/autoload.php')) {
    require_once $magentoRoot . '/vendor/autoload.php';
} elseif (file_exists($magentoRoot . '/autoload.php')) {
    require_once $magentoRoot . '/autoload.php';
}

// Define constants for testing
if (!defined('BP')) {
    define('BP', $magentoRoot);
}

if (!defined('TESTS_TEMP_DIR')) {
    define('TESTS_TEMP_DIR', dirname(__DIR__) . '/tmp');
}

// Create temp directory if it doesn't exist
if (!is_dir(TESTS_TEMP_DIR)) {
    mkdir(TESTS_TEMP_DIR, 0777, true);
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHPUnit Bootstrap Loaded\n";
echo "Magento Root: " . $magentoRoot . "\n";
echo "Test Temp Dir: " . TESTS_TEMP_DIR . "\n";
