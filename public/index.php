<?php

declare(strict_types=1);

// Start output buffering to prevent stray notices/deprecations from corrupting responses
if (ob_get_level() === 0) {
    ob_start();
}

// Set timezone to UTC for database consistency
date_default_timezone_set('UTC');

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');

require_once __DIR__ . '/../app/Core/helpers.php';

use App\Core\Env;
use App\Core\Logger;
use App\Core\Database;
use App\Core\Session;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;

// 1. Load Environment Variables
Env::load(__DIR__ . '/../.env');

// 2. Configure Error Reporting
$debug = (bool) Env::get('APP_DEBUG', false);
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 3. Initialize Logger
Logger::init(__DIR__ . '/../storage/logs');

// 4. Initialize Database Connection
try {
    Database::init([
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'qfunnel2_db'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ]);
} catch (\Throwable $e) {
    Logger::error("Fatal DB init error: " . $e->getMessage());
    if ($debug) {
        die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
    }
    http_response_code(500);
    die("Service temporarily unavailable. Please verify database configuration.");
}

// 5. CORS & Preflight Handling for SPA (Vite Dev & Production)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173', 'http://localhost:3000'];
if (in_array($origin, $allowedOrigins, true) || Env::get('APP_ENV') === 'development' || !empty($origin)) {
    if ($origin) {
        header("Access-Control-Allow-Origin: {$origin}");
    }
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, X-Api-Key");
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 6. Start Secure Session
Session::start();

// 7. Handle HTTP Request
$request = new Request();
View::init(__DIR__ . '/../app/Views', $request);

// 8. Route and Dispatch
$router = new Router();
require_once __DIR__ . '/../routes/web.php';

try {
    $response = $router->dispatch($request);
    $response->send();
} catch (\Throwable $e) {
    Logger::error("Unhandled Exception: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'path' => $request->path(),
    ]);

    if ($request->isAjax()) {
        $msg = $debug ? $e->getMessage() : "An unexpected server error occurred.";
        Response::error($msg, "INTERNAL_ERROR", null, 500)->send();
    } else {
        if ($debug) {
            echo "<h1>Application Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " on line " . $e->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            exit;
        } else {
            Response::html("<h1>500 Server Error</h1><p>An unexpected error occurred. Please try again later.</p>", 500)->send();
        }
    }
}
