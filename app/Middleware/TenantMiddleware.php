<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\TenantContextService;

class TenantMiddleware
{
    public function handle(Request $request): ?Response
    {
        $tenant = TenantContextService::getActiveTenant();
        if (!$tenant) {
            $user = current_user();
            if ($user) {
                $tenants = TenantContextService::getUserTenants((int)$user['id']);
                if (!empty($tenants)) {
                    TenantContextService::setActiveTenant((int)$tenants[0]['id'], (int)$user['id']);
                    $tenant = TenantContextService::getActiveTenant();
                }
            }
        }

        if (!$tenant) {
            if ($request->isAjax()) {
                return Response::error("No active workspace found for user.", "FORBIDDEN", null, 403);
            }
            if (is_platform_admin()) {
                return Response::redirect(url('/admin/platform'));
            }
            return Response::redirect(url('/login'));
        }

        // Handle terminated tenant
        if (($tenant['status'] ?? 'active') === 'terminated') {
            if (is_platform_admin()) {
                return null; // Platform admin can inspect
            }
            if ($request->isAjax()) {
                return Response::error("This workspace has been terminated.", "WORKSPACE_TERMINATED", null, 403);
            }
            \App\Core\Session::setFlash('error', "This workspace has been terminated. Please contact your platform administrator.");
            return Response::redirect(url('/login'));
        }

        // Handle suspended tenant (Data stays 100% safe, locked to suspended notice or billing upgrade)
        if (($tenant['status'] ?? 'active') === 'suspended') {
            $uri = $request->path();
            $allowedSuspendedPaths = [
                '/admin/workspace/suspended',
                '/admin/billing',
                '/admin/billing/upgrade',
                '/admin/switch-workspace',
                '/logout'
            ];

            // Platform admins can freely inspect
            if (is_platform_admin()) {
                return null;
            }

            if (!in_array($uri, $allowedSuspendedPaths, true)) {
                if ($request->isAjax()) {
                    return Response::error(
                        "Workspace is suspended. All data is securely preserved. Please contact administrator or renew your subscription.",
                        "WORKSPACE_SUSPENDED",
                        ['reason' => $tenant['suspension_reason'] ?? 'Billing overdue'],
                        423
                    );
                }
                return Response::redirect(url('/admin/workspace/suspended'));
            }
        }

        return null;
    }
}
