<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $content = '';

    public function __construct(mixed $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public static function json(array $data, int $statusCode = 200): self
    {
        $response = new self(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $statusCode);
        $response->header('Content-Type', 'application/json; charset=utf-8');
        return $response;
    }

    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function error(string $message = 'Error', string $errorCode = 'ERROR', mixed $data = null, int $statusCode = 400): self
    {
        return self::json([
            'success' => false,
            'error_code' => $errorCode,
            'error' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function html(string $html, int $statusCode = 200): self
    {
        $response = new self($html, $statusCode);
        $response->header('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        $response = new self('', $statusCode);
        $response->header('Location', $url);
        return $response;
    }

    public function send(): void
    {
        // For JSON API responses, discard any stray PHP notices, warnings, or deprecations
        if (str_contains($this->headers['Content-Type'] ?? '', 'application/json')) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        if (!headers_sent()) {
            http_response_code($this->statusCode);

            // Default security headers
            if (!isset($this->headers['X-Content-Type-Options'])) {
                header('X-Content-Type-Options: nosniff');
            }
            if (!isset($this->headers['X-Frame-Options'])) {
                header('X-Frame-Options: SAMEORIGIN');
            }
            if (!isset($this->headers['X-XSS-Protection'])) {
                header('X-XSS-Protection: 1; mode=block');
            }

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
        exit;
    }
}
