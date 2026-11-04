<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\FunnelService;
use App\Services\FunnelGraphService;
use App\Services\TenantContextService;
use App\Services\AuthorizationService;

class BuilderController
{
    public function index(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $funnel = FunnelService::getFunnel($tenantId, (int)$id);

        if (!$funnel) {
            Session::setFlash('error', 'Funnel not found.');
            return Response::redirect(url('/admin/funnels'));
        }

        return view('builder.index', [
            'title' => "Visual Builder: {$funnel['name']} — Quiz Funnel SaaS",
            'funnel' => $funnel,
        ], null); // Full screen custom layout
    }

    public function getGraph(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();

        try {
            $graph = FunnelGraphService::getGraph($tenantId, (int)$id);
            return Response::success($graph);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), 'GRAPH_ERROR', null, 404);
        }
    }

    public function saveGraph(Request $request, string $id): Response
    {
        if (!AuthorizationService::canEditFunnel()) {
            return Response::error("Permission denied.", "FORBIDDEN", null, 403);
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();
        $payload = $request->all();

        try {
            $savedGraph = FunnelGraphService::saveGraph($tenantId, (int)$id, $payload, (int)$user['id']);
            return Response::success($savedGraph, 'Graph saved successfully.');
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), 'SAVE_ERROR', null, 400);
        }
    }

    public function validateGraph(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();

        try {
            $validation = FunnelGraphService::validateGraph($tenantId, (int)$id);
            return Response::success($validation);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), 'VALIDATION_ERROR', null, 400);
        }
    }
}
