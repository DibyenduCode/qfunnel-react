<?php

namespace App\Core;

class Autoloader
{
    private static array $prefixes = [];

    public static function register(string $appDir): void
    {
        $base = dirname(rtrim($appDir, '/\\'));
        self::addNamespace('App\\', $appDir);
        self::addNamespace('Database\\', $base . DIRECTORY_SEPARATOR . 'database');

        spl_autoload_register(function (string $class) {
            foreach (self::$prefixes as $prefix => $baseDir) {
                $len = strlen($prefix);
                if (strncmp($prefix, $class, $len) === 0) {
                    $relativeClass = substr($class, $len);
                    $file = $baseDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
                    if (file_exists($file)) {
                        require_once $file;
                        return;
                    }
                }
            }
        });
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/\\');
        self::$prefixes[$prefix] = $baseDir;
    }
}
