<?php

namespace App\Core;

class Env
{
    private static array $variables = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Strip quotes if wrapped
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                self::$variables[$key] = $value;
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$variables[$key])) {
            $val = self::$variables[$key];
        } elseif (isset($_ENV[$key])) {
            $val = $_ENV[$key];
        } elseif (isset($_SERVER[$key])) {
            $val = $_SERVER[$key];
        } else {
            $val = getenv($key);
            if ($val === false) {
                return $default;
            }
        }

        if (is_string($val)) {
            $lower = strtolower($val);
            if ($lower === 'true' || $lower === '(true)') return true;
            if ($lower === 'false' || $lower === '(false)') return false;
            if ($lower === 'empty' || $lower === '(empty)') return '';
            if ($lower === 'null' || $lower === '(null)') return null;
        }

        return $val;
    }
}
