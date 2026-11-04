<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class CsrfMiddleware
{
    public function handle(Request $request): ?Response
    {
        $method = $request->method();
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            // Check CSRF token from header or body
            $token = $request->header('x-csrf-token') ?? $request->input('_csrf_token');

            if (!Session::validateCsrfToken($token)) {
                if ($request->isAjax()) {
                    return Response::error("CSRF token verification failed.", "CSRF_ERROR", null, 403);
                }
                Session::setFlash('error', 'Your session expired or token was invalid. Please try again.');
                return Response::redirect($request->uri());
            }
        }

        return null;
    }
}
