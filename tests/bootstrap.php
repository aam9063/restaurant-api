<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

// Configurar entorno de testing
$_SERVER['APP_ENV'] = 'test';
$_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';

// Limpiar output buffers previos
if (ob_get_level()) {
    ob_end_clean();
}

// Configurar timezone por defecto
date_default_timezone_set('UTC');

// Configurar error reporting para tests
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '0');
