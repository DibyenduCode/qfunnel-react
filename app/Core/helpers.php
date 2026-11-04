<?php

use App\Core\View;
use App\Core\Response;
use App\Core\Session;
use App\Services\TenantContextService;

if (!function_exists('view')) {
    function view(string $template, array $data = [], ?string $layout = 'admin'): Response
    {
        return View::render($template, $data, $layout);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return View::url($path);
    }
}

if (!function_exists('full_url')) {
    function full_url(string $path = ''): string
    {
        return View::fullUrl($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return View::asset($path);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return View::escape($value);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return View::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return View::csrfField();
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $default = null): mixed
    {
        return View::flash($key, $default);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::get('_old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return Session::get('user');
    }
}

if (!function_exists('current_tenant')) {
    function current_tenant(): ?array
    {
        return TenantContextService::getActiveTenant();
    }
}

if (!function_exists('is_platform_admin')) {
    function is_platform_admin(): bool
    {
        $user = current_user();
        return !empty($user['is_platform_admin']);
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return '₹';
    }
}

if (!function_exists('currency_code')) {
    function currency_code(): string
    {
        return 'INR';
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float|int|string $amount, bool $showSymbol = true): string
    {
        $formatted = number_format((float)$amount, 2);
        return $showSymbol ? '₹' . $formatted : $formatted;
    }
}
