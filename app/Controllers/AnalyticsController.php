<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AnalyticsService;
use App\Services\FunnelService;
use App\Services\TenantContextService;

class AnalyticsController
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $funnels = FunnelService::listFunnels($tenantId);

        $selectedFunnelId = $request->query('funnel_id') ? (int)$request->query('funnel_id') : (!empty($funnels) ? (int)$funnels[0]['id'] : null);

        $analyticsData = [];
        if ($selectedFunnelId) {
            $analyticsData = AnalyticsService::getFunnelMetrics($tenantId, $selectedFunnelId);
        }

        return view('analytics.index', [
            'title' => 'Funnel Analytics & Journey Progression — Quiz Funnel SaaS',
            'funnels' => $funnels,
            'selectedFunnelId' => $selectedFunnelId,
            'analytics' => $analyticsData,
        ]);
    }
}
