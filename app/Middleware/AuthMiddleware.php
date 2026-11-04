<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        $user = current_user();
        if (!$user) {
            if ($request->isAjax()) {
                return Response::error("Unauthenticated. Please log in.", "UNAUTHENTICATED", null, 401);
            }
            Session::setFlash('error', 'Please log in to continue.');
            return Response::redirect(url('/login'));
        }
        return null;
    }
}
