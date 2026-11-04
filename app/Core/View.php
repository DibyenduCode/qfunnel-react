<?php

namespace App\Core;

class View
{
    private static string $viewsDir = '';
    private static ?Request $currentRequest = null;

    public static function init(string $viewsDir, ?Request $request = null): void
    {
        self::$viewsDir = rtrim($viewsDir, '/\\') . DIRECTORY_SEPARATOR;
        self::$currentRequest = $request;
    }

    public static function setRequest(Request $request): void
    {
        self::$currentRequest = $request;
    }

    public static function render(string $template, array $data = [], ?string $layout = 'admin'): Response
    {
        $templateFile = self::$viewsDir . str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("View template not found: {$template}");
        }

        // Extract variables for view
        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = self::$viewsDir . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
            if (file_exists($layoutFile)) {
                ob_start();
                require $layoutFile;
                $content = ob_get_clean();
            }
        }

        return Response::html($content);
    }

    public static function url(string $path = ''): string
    {
        $base = self::$currentRequest ? self::$currentRequest->basePath() : '';
        $path = '/' . ltrim($path, '/');
        return ($base !== '' ? $base : '') . $path;
    }

    public static function fullUrl(string $path = ''): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ? 'https://'
            : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $relative = self::url($path);
        return $protocol . $host . $relative;
    }

    public static function asset(string $path): string
    {
        $base = self::$currentRequest ? self::$currentRequest->basePath() : '';
        $path = '/' . ltrim($path, '/');
        // If public folder is directly served or in path
        return ($base !== '' ? $base : '') . '/assets' . $path;
    }

    public static function csrfToken(): string
    {
        return Session::csrfToken();
    }

    public static function csrfField(): string
    {
        $token = htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function flash(string $key, mixed $default = null): mixed
    {
        return Session::getFlash($key, $default);
    }
}
