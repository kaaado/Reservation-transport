<?php
// /core/paths.php

// 1. Physical Path (C:\xampp\htdocs\Reservation-transport\)
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

require_once __DIR__ . '/env.php';

// 2. Browser URL (http://localhost/Reservation-transport/)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
define('URL_ROOT', $protocol . $domainName . '/Reservation-transport/');

// 3. Easy Access Constants
define('INC_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('FUNC_PATH', ROOT_PATH . 'functions' . DIRECTORY_SEPARATOR);
define('CONF_PATH', ROOT_PATH . 'core' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);
define('CSS_URL', URL_ROOT . 'public/css/');
define('JS_URL', URL_ROOT . 'public/js/');
define('ASSETS_URL', URL_ROOT . 'public/assets/');
