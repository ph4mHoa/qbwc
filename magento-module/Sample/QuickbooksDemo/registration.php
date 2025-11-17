<?php
/**
 * Sample QuickBooks Demo Module Registration
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Sample_QuickbooksDemo',
    __DIR__
);
