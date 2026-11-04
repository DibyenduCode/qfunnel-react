<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AnalyticsService;
use App\Services\UsageLimitService;
use App\Services\TenantContextService;

class DashboardController
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $metrics = AnalyticsService::getDashboardMetrics($tenantId);
        $usage = UsageLimitService::getUsageOverview($tenantId);

        return view('dashboard.index', [
            'title' => 'Dashboard Overview — Quiz Funnel SaaS',
            'metrics' => $metrics,
            'usage' => $usage,
        ]);
    }

    public function workspaceSuspended(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $tenant = TenantContextService::getActiveTenant();

        if (!$tenant || $tenant['status'] !== 'suspended') {
            return Response::redirect(url('/admin/dashboard'));
        }

        $billing = \App\Services\SubscriptionService::getBillingSummary($tenantId);
        $userTenants = TenantContextService::getUserTenants((int)current_user()['id']);

        return view('dashboard.suspended', [
            'title' => 'Workspace Suspended — Quiz Funnel SaaS',
            'tenant' => $tenant,
            'billing' => $billing,
            'userTenants' => $userTenants,
        ], 'auth'); // Clean focused layout
    }
}
