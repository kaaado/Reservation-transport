<?php
// core/env.php

function loadEnvHelper($file) {
    if (!file_exists($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"");
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
loadEnvHelper(__DIR__ . '/../.env');

// Set platform variables
if (!defined('APP_COMMISSION')) define('APP_COMMISSION', $_ENV['APP_COMMISSION'] ?? 0.20);
if (!defined('APP_RIP_ACCOUNT')) define('APP_RIP_ACCOUNT', $_ENV['APP_RIP_ACCOUNT'] ?? '07999999999999999999');
