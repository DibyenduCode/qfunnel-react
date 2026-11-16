<?php

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function isSecure(): bool
    {
        return (bool) Env::get('SESSION_SECURE', false)
            || (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    }

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        if (!headers_sent()) {
            $lifetime = (int) Env::get('SESSION_LIFETIME', 7200);
            $secure = self::isSecure();
            $samesite = Env::get('SESSION_SAME_SITE', 'Lax');

            @ini_set('session.gc_maxlifetime', (string) $lifetime);
            @ini_set('session.use_only_cookies', '1');
            @ini_set('session.use_strict_mode', '1');

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => $samesite,
            ]);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        self::$started = true;

        // Session Hijacking Protection: User-Agent Fingerprint Check
        $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI_ENVIRONMENT';
        $uaHash = hash('sha256', $currentUa);
        if (isset($_SESSION['user']) && isset($_SESSION['_user_agent_hash'])) {
            if (!hash_equals($_SESSION['_user_agent_hash'], $uaHash)) {
                Logger::warning("Potential session hijacking detected: User-Agent mismatch for user ID " . ($_SESSION['user']['id'] ?? 'unknown'));
                self::destroy();
                return;
            }
        } elseif (isset($_SESSION['user']) && !isset($_SESSION['_user_agent_hash'])) {
            $_SESSION['_user_agent_hash'] = $uaHash;
        }

        // If remember_me was selected, keep refreshing the persistent cookie
        if (!empty($_SESSION['remember_me']) && !empty($_SESSION['remember_expires']) && $_SESSION['remember_expires'] > time() && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => (int)$_SESSION['remember_expires'],
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => self::isSecure(),
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        // Auto-generate CSRF token if not set
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function regenerate(): void
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI_ENVIRONMENT';
        $_SESSION['_user_agent_hash'] = hash('sha256', $currentUa);
    }

    public static function extendLifetime(int $seconds = 2592000): void
    {
        self::start();
        $_SESSION['remember_me'] = true;
        $_SESSION['remember_expires'] = time() + $seconds;

        if (!headers_sent() && ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => time() + $seconds,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => self::isSecure(),
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies') && !headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => self::isSecure(),
                    'httponly' => true,
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]);
            }
            @session_destroy();
            self::$started = false;
        }
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        self::start();
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    public static function setFlash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        if (isset($_SESSION['_flash'][$key])) {
            $val = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $val;
        }
        return $default;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$key]);
    }
}
