<?php

namespace App\Core;

class Logger
{
    private static string $logFile = '';

    public static function init(string $logDir): void
    {
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        self::$logFile = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR . 'app.log';
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        if (empty(self::$logFile)) {
            self::init(__DIR__ . '/../../storage/logs');
        }

        // Redact sensitive keys
        $sanitizedContext = self::sanitize($context);
        $contextStr = !empty($sanitizedContext) ? ' ' . json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES) : '';

        $time = date('Y-m-d H:i:s');
        $entry = sprintf("[%s] [%s] %s%s\n", $time, strtoupper($level), $message, $contextStr);

        @file_put_contents(self::$logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (Env::get('APP_DEBUG', false)) {
            self::log('DEBUG', $message, $context);
        }
    }

    private static function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'auth', 'card', 'cvv'];
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::sanitize($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys, true)) {
                $clean[$key] = '***REDACTED***';
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }
}
