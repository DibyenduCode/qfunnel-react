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
if (file_exists(__DIR__ . '/../.env')) {
    Env::load(__DIR__ . '/../.env');
} elseif (file_exists(__DIR__ . '/../.env.production')) {
    Env::load(__DIR__ . '/../.env.production');
}

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
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
Logger::init($logDir);

// Register fatal shutdown handler to catch White Screen of Death
register_shutdown_function(function () use ($debug) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
        Logger::error("Fatal PHP Shutdown Error: " . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        if ($debug) {
            echo "<h1>Application Fatal Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($error['message']) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($error['file']) . " on line " . $error['line'] . "</p>";
        } else {
            echo "<h1>Server Error</h1><p>A fatal system error occurred. Please verify your cPanel file extraction, PHP version (8.1+ required), and .env settings. Check storage/logs/app.log for details.</p>";
        }
    }
});

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
View::setRequest($request);

// 8. Serve React Single Page Application (SPA), Dynamic SEO, Robots & Sitemap
$path = $request->path();
$isApiRoute = str_starts_with($path, '/api') || str_starts_with($path, '/qfunnel2/api');

if (!$isApiRoute) {
    // Serve static assets if routed through index.php
    if (preg_match('#^/(?:(?:qfunnel2/)?(?:dist/)?)(assets/.+)$#', $path, $matches)) {
        $relativePath = $matches[1];
        $targetFile = null;
        if (file_exists(__DIR__ . '/' . $relativePath)) {
            $targetFile = __DIR__ . '/' . $relativePath;
        } elseif (file_exists(__DIR__ . '/dist/' . $relativePath)) {
            $targetFile = __DIR__ . '/dist/' . $relativePath;
        }

        if ($targetFile) {
            $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $mimeMap = [
                'js' => 'application/javascript; charset=UTF-8',
                'css' => 'text/css; charset=UTF-8',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'ico' => 'image/x-icon',
                'woff2' => 'font/woff2',
                'woff' => 'font/woff',
            ];
            header('Content-Type: ' . ($mimeMap[$ext] ?? 'application/octet-stream'));
            header('Cache-Control: public, max-age=31536000, immutable');
            readfile($targetFile);
            exit;
        }
    }

    // Serve uploaded media (avatars, logos, gateways) even if prefixed with /qfunnel2/
    if (preg_match('#^/(?:qfunnel2/)?(?:dist/)?(uploads/.+)$#', $path, $matches)) {
        $relativePath = $matches[1];
        $targetFile = null;
        if (file_exists(__DIR__ . '/' . $relativePath)) {
            $targetFile = __DIR__ . '/' . $relativePath;
        } elseif (file_exists(__DIR__ . '/../' . $relativePath)) {
            $targetFile = __DIR__ . '/../' . $relativePath;
        }

        if ($targetFile && file_exists($targetFile)) {
            $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $mimeMap = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'gif' => 'image/gif',
                'ico' => 'image/x-icon',
            ];
            header('Content-Type: ' . ($mimeMap[$ext] ?? 'application/octet-stream'));
            header('Cache-Control: public, max-age=86400');
            readfile($targetFile);
            exit;
        }
    }

    // Dynamic XML Sitemap
    if ($path === '/sitemap.xml' || $path === '/qfunnel2/sitemap.xml') {
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        echo \App\Services\SeoService::generateSitemapXml();
        exit;
    }

    // Dynamic Robots.txt
    if ($path === '/robots.txt' || $path === '/qfunnel2/robots.txt') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo \App\Services\SeoService::generateRobotsTxt();
        exit;
    }

    $spaIndex = __DIR__ . '/dist/index.html';
    if (file_exists($spaIndex)) {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
        $html = (string) file_get_contents($spaIndex);
        echo \App\Services\SeoService::injectMetadataIntoHtml($html, $path);
        exit;
    }
}

// 9. Route and Dispatch REST API Requests
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
