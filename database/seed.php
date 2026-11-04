<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');
require_once __DIR__ . '/../app/Core/helpers.php';

use App\Core\Env;
use App\Core\Database;
use Database\Seeders\DatabaseSeeder;

// Load .env
Env::load(__DIR__ . '/../.env');

// Initialize database
Database::init([
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'qfunnel_db'),
    'username' => Env::get('DB_USERNAME', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
]);

DatabaseSeeder::run();
