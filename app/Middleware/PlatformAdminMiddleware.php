<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthorizationService;

class PlatformAdminMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (!AuthorizationService::isPlatformAdmin()) {
            if ($request->isAjax()) {
                return Response::error("Access denied. Platform administrator privilege required.", "FORBIDDEN", null, 403);
            }
            return Response::html("<h1>403 Forbidden</h1><p>Platform administrator privilege required.</p>", 403);
        }
        return null;
    }
}
