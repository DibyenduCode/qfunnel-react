<?php

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private string $basePath;
    private array $query;
    private array $body;
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->query = $_GET ?? [];
        $this->headers = $this->parseHeaders();
        $this->basePath = $this->detectBasePath();
        $this->path = $this->calculatePath();
        $this->body = $this->parseBody();

        // Support _method field for form method spoofing (PUT, DELETE, etc.)
        if ($this->method === 'POST' && isset($this->body['_method'])) {
            $this->method = strtoupper($this->body['_method']);
        }
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    private function detectBasePath(): string
    {
        $urlParts = parse_url($this->uri);
        $uriPath = $urlParts['path'] ?? '/';
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = rtrim(dirname($scriptName), '/\\');

        if ($scriptDir !== '' && $scriptDir !== '.' && str_starts_with($uriPath, $scriptDir)) {
            return $scriptDir;
        }

        if (str_ends_with($scriptDir, '/public')) {
            $parentDir = rtrim(dirname($scriptDir), '/\\');
            if ($parentDir !== '' && $parentDir !== '.' && str_starts_with($uriPath, $parentDir)) {
                return $parentDir;
            }
        }

        return '';
    }

    private function calculatePath(): string
    {
        $urlParts = parse_url($this->uri);
        $path = $urlParts['path'] ?? '/';

        // Strip base path if request is under a subdirectory (e.g., /qfunnel or /qfunnel/public)
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
        }

        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    private function parseBody(): array
    {
        $contentType = $this->header('content-type', '');

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }

        return $_POST ?? [];
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return array_merge($this->query, $this->body);
        }
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $name = strtolower($key);
        return $this->headers[$name] ?? $default;
    }

    public function isAjax(): bool
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest' ||
               str_contains($this->header('accept', ''), 'application/json') ||
               str_contains($this->header('content-type', ''), 'application/json');
    }

    public function file(string $key): ?array
    {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE) {
            return $_FILES[$key];
        }
        return null;
    }

    public function files(): array
    {
        return $_FILES ?? [];
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
