<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Services\UsageLimitService;

class ApiController
{
    /**
     * Authenticate tenant via Bearer token or X-API-Key header
     */
    private function authenticate(Request $request): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $apiKey = '';

        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            $apiKey = $matches[1];
        } elseif (!empty($_SERVER['HTTP_X_API_KEY'])) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'];
        } elseif ($request->query('api_key')) {
            $apiKey = (string)$request->query('api_key');
        }

        if (empty($apiKey)) {
            return null;
        }

        return Database::fetchOne("SELECT * FROM tenants WHERE api_key = ? AND status = 'active'", [$apiKey]);
    }

    /**
     * GET /api/v1/me
     */
    public function me(Request $request): Response
    {
        $tenant = $this->authenticate($request);
        if (!$tenant) {
            return Response::json(['error' => 'Unauthorized: Invalid or missing API Key. Pass via Authorization: Bearer <key> or X-API-Key header.'], 401);
        }

        $tenantId = (int)$tenant['id'];
        if (!UsageLimitService::hasFeature($tenantId, 'api_access')) {
            return Response::json(['error' => 'Forbidden: REST API access is a Pro subscription feature. Please upgrade your plan.'], 403);
        }

        $limits = UsageLimitService::getTenantLimits($tenantId);
        $features = UsageLimitService::getTenantFeatures($tenantId);

        return Response::json([
            'status' => 'success',
            'tenant' => [
                'id' => $tenantId,
                'name' => $tenant['name'],
                'slug' => $tenant['slug'],
                'created_at' => $tenant['created_at'],
            ],
            'subscription' => [
                'plan_name' => $limits['plan_name'],
                'plan_slug' => $limits['plan_slug'],
                'status' => $limits['status'],
                'limits' => $limits,
                'features' => $features,
            ]
        ]);
    }

    /**
     * GET /api/v1/funnels
     */
    public function funnels(Request $request): Response
    {
        $tenant = $this->authenticate($request);
        if (!$tenant) {
            return Response::json(['error' => 'Unauthorized: Invalid or missing API Key.'], 401);
        }

        $tenantId = (int)$tenant['id'];
        if (!UsageLimitService::hasFeature($tenantId, 'api_access')) {
            return Response::json(['error' => 'Forbidden: REST API access is a Pro subscription feature.'], 403);
        }

        $funnels = Database::query(
            "SELECT id, name, slug, status, description, created_at, updated_at 
             FROM funnels 
             WHERE tenant_id = ? AND status != 'archived' 
             ORDER BY id DESC",
            [$tenantId]
        );

        return Response::json([
            'status' => 'success',
            'count' => count($funnels),
            'data' => $funnels
        ]);
    }

    /**
     * GET /api/v1/submissions
     */
    public function submissions(Request $request): Response
    {
        $tenant = $this->authenticate($request);
        if (!$tenant) {
            return Response::json(['error' => 'Unauthorized: Invalid or missing API Key.'], 401);
        }

        $tenantId = (int)$tenant['id'];
        if (!UsageLimitService::hasFeature($tenantId, 'api_access')) {
            return Response::json(['error' => 'Forbidden: REST API access is a Pro subscription feature.'], 403);
        }

        $funnelId = $request->query('funnel_id') ? (int)$request->query('funnel_id') : null;
        $limit = min(100, max(1, (int)$request->query('limit', 25)));

        $sql = "SELECT qs.id as session_id, qs.funnel_id, f.name as funnel_name, qs.status, qs.started_at, qs.completed_at, qs.created_at,
                       fs.id as form_submission_id, fs.submitted_at
                FROM quiz_sessions qs
                JOIN funnels f ON qs.funnel_id = f.id
                LEFT JOIN form_submissions fs ON fs.session_id = qs.id
                WHERE qs.tenant_id = ?";
        
        $params = [$tenantId];
        if ($funnelId) {
            $sql .= " AND qs.funnel_id = ?";
            $params[] = $funnelId;
        }

        $sql .= " ORDER BY qs.id DESC LIMIT {$limit}";

        $sessions = Database::query($sql, $params);

        foreach ($sessions as &$sess) {
            if (!empty($sess['form_submission_id'])) {
                $sess['form_fields'] = Database::query(
                    "SELECT field_key_snapshot as field, field_label_snapshot as label, value_text as value 
                     FROM form_submission_values 
                     WHERE form_submission_id = ?",
                    [$sess['form_submission_id']]
                );
            } else {
                $sess['form_fields'] = [];
            }
        }
        unset($sess);

        return Response::json([
            'status' => 'success',
            'count' => count($sessions),
            'data' => $sessions
        ]);
    }
}
