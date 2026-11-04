<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

class HomeController
{
    public function index(Request $request): Response
    {
        $plans = [];
        try {
            $plans = Database::query("SELECT * FROM plans WHERE is_active = 1 AND is_visible = 1 ORDER BY price ASC, id ASC");
        } catch (\Throwable $e) {
            $plans = [];
        }

        return view('home.index', [
            'title' => 'QFunnel — Interactive Quiz Funnel & Lead Qualification SaaS',
            'plans' => $plans,
            'featureCatalog' => \App\Services\UsageLimitService::getFeatureCatalog(),
            'currentUser' => current_user(),
        ], null);
    }

    public function docs(Request $request): Response
    {
        $sampleTenant = Database::fetchOne("SELECT id, name, slug, api_key FROM tenants WHERE api_key IS NOT NULL AND api_key != '' LIMIT 1");
        $sampleFunnel = Database::fetchOne("SELECT id, name, slug FROM funnels WHERE status = 'published' LIMIT 1");
        if (!$sampleFunnel) {
            $sampleFunnel = Database::fetchOne("SELECT id, name, slug FROM funnels LIMIT 1");
        }

        $baseUrl = rtrim(url('/'), '/');

        return view('docs.index', [
            'title' => 'API, Webhooks & Embedding Documentation — QFunnel',
            'baseUrl' => $baseUrl,
            'sampleApiKey' => $sampleTenant['api_key'] ?? 'qfn_live_abc123xyz789demo',
            'sampleFunnelSlug' => $sampleFunnel['slug'] ?? 'sample-lead-quiz',
            'sampleFunnelName' => $sampleFunnel['name'] ?? 'Sample Quiz Funnel',
            'sampleFunnelId' => $sampleFunnel['id'] ?? 1,
            'currentUser' => current_user(),
        ], null);
    }
}

