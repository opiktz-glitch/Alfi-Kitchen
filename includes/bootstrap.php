<?php
/**
 * Load the legacy root configuration when present, otherwise use the tracked
 * environment-based configuration so fresh checkouts work without config.php.
 */
$rootConfig = dirname(__DIR__) . '/config.php';

if (is_file($rootConfig)) {
    require_once $rootConfig;
    return;
}

require_once __DIR__ . '/config.php';
