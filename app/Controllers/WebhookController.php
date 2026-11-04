<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Session;
use App\Services\TenantContextService;
use App\Services\WebhookService;
use App\Services\FunnelService;
use App\Services\AuditLogService;
use App\Services\UsageLimitService;
use App\Services\AuthorizationService;

class WebhookController
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $hasWebhooks = UsageLimitService::hasFeature($tenantId, 'webhooks') || AuthorizationService::isPlatformAdmin();

        $endpoints = Database::query(
            "SELECT we.*, f.name as funnel_name,
                    (SELECT COUNT(*) FROM webhook_deliveries wd WHERE wd.webhook_id = we.id) as total_deliveries,
                    (SELECT COUNT(*) FROM webhook_deliveries wd WHERE wd.webhook_id = we.id AND wd.status = 'success') as successful_deliveries,
                    (SELECT MAX(created_at) FROM webhook_deliveries wd WHERE wd.webhook_id = we.id) as last_delivery_at
             FROM webhook_endpoints we
             LEFT JOIN funnels f ON we.funnel_id = f.id
             WHERE we.tenant_id = ?
             ORDER BY we.id DESC",
            [$tenantId]
        );

        $funnels = Database::query(
            "SELECT id, name FROM funnels WHERE tenant_id = ? ORDER BY name ASC",
            [$tenantId]
        );

        $recentDeliveries = Database::query(
            "SELECT wd.*, we.name as webhook_name, we.target_url
             FROM webhook_deliveries wd
             JOIN webhook_endpoints we ON wd.webhook_id = we.id
             WHERE wd.tenant_id = ?
             ORDER BY wd.id DESC
             LIMIT 15",
            [$tenantId]
        );

        return view('webhooks.index', [
            'title' => 'Webhooks & Integrations',
            'endpoints' => $endpoints,
            'funnels' => $funnels,
            'recentDeliveries' => $recentDeliveries,
            'hasWebhooks' => $hasWebhooks,
        ]);
    }

    public function store(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();

        if (!UsageLimitService::hasFeature($tenantId, 'webhooks') && !AuthorizationService::isPlatformAdmin()) {
            Session::setFlash('error', 'Outbound Webhooks are not included in your current subscription plan. Please upgrade your plan to connect webhooks.');
            return Response::redirect(url('/admin/webhooks'));
        }

        $name = trim($request->input('name', ''));
        $targetUrl = trim($request->input('target_url', ''));
        $eventType = trim($request->input('event_type', 'lead.captured'));
        $funnelId = !empty($request->input('funnel_id')) ? (int)$request->input('funnel_id') : null;
        $secret = trim($request->input('secret', ''));

        if (empty($name)) {
            Session::setFlash('error', 'Please provide a name for this webhook.');
            return Response::redirect(url('/admin/webhooks'));
        }

        if (empty($targetUrl) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            Session::setFlash('error', 'Please enter a valid target URL (e.g. Zapier, Make, or CRM webhook URL).');
            return Response::redirect(url('/admin/webhooks'));
        }

        if (empty($secret)) {
            $secret = bin2hex(random_bytes(16));
        }

        try {
            Database::execute(
                "INSERT INTO webhook_endpoints (tenant_id, funnel_id, name, target_url, secret, event_type, is_active, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, 1, NOW())",
                [$tenantId, $funnelId, $name, $targetUrl, $secret, $eventType]
            );

            AuditLogService::log('webhook.created', "Created webhook endpoint '{$name}' targeting {$targetUrl}");
            Session::setFlash('success', "Webhook '{$name}' created successfully.");
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
        }

        return Response::redirect(url('/admin/webhooks'));
    }

    public function update(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();
        $webhookId = (int)$id;

        $name = trim($request->input('name', ''));
        $targetUrl = trim($request->input('target_url', ''));
        $eventType = trim($request->input('event_type', 'lead.captured'));
        $funnelId = !empty($request->input('funnel_id')) ? (int)$request->input('funnel_id') : null;
        $secret = trim($request->input('secret', ''));
        $isActive = !empty($request->input('is_active')) ? 1 : 0;

        if (empty($name) || empty($targetUrl) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            Session::setFlash('error', 'Name and a valid target URL are required.');
            return Response::redirect(url('/admin/webhooks'));
        }

        try {
            Database::execute(
                "UPDATE webhook_endpoints 
                 SET name = ?, target_url = ?, event_type = ?, funnel_id = ?, secret = ?, is_active = ?, updated_at = NOW() 
                 WHERE tenant_id = ? AND id = ?",
                [$name, $targetUrl, $eventType, $funnelId, $secret, $isActive, $tenantId, $webhookId]
            );

            AuditLogService::log('webhook.updated', "Updated webhook endpoint #{$webhookId}");
            Session::setFlash('success', 'Webhook updated successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
        }

        return Response::redirect(url('/admin/webhooks'));
    }

    public function delete(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $webhookId = (int)$id;

        try {
            Database::execute("DELETE FROM webhook_endpoints WHERE tenant_id = ? AND id = ?", [$tenantId, $webhookId]);
            AuditLogService::log('webhook.deleted', "Deleted webhook endpoint #{$webhookId}");
            Session::setFlash('success', 'Webhook deleted successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
        }

        return Response::redirect(url('/admin/webhooks'));
    }

    public function test(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $webhookId = (int)$id;

        try {
            $result = WebhookService::testPing($tenantId, $webhookId);
            if ($result['status'] === 'success') {
                Session::setFlash('success', "Test ping sent successfully! Response code: {$result['response_code']} ({$result['duration_ms']}ms)");
            } else {
                Session::setFlash('error', "Test ping failed or destination rejected the request. HTTP Code: {$result['response_code']}");
            }
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
        }

        return Response::redirect(url('/admin/webhooks'));
    }

    public function retryDelivery(Request $request, string $id): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $deliveryId = (int)$id;

        $delivery = Database::fetchOne(
            "SELECT wd.*, we.target_url, we.secret, we.name as endpoint_name 
             FROM webhook_deliveries wd 
             JOIN webhook_endpoints we ON wd.webhook_id = we.id 
             WHERE wd.tenant_id = ? AND wd.id = ?",
            [$tenantId, $deliveryId]
        );

        if (!$delivery) {
            Session::setFlash('error', 'Delivery log record not found.');
            return Response::redirect(url('/admin/webhooks'));
        }

        $endpoint = [
            'tenant_id' => $tenantId,
            'id' => (int)$delivery['webhook_id'],
            'target_url' => $delivery['target_url'],
            'secret' => $delivery['secret'] ?? '',
        ];

        $payload = json_decode($delivery['payload_json'] ?? '{}', true) ?: [];
        $data = $payload['data'] ?? $payload;

        $res = WebhookService::deliverPayload($endpoint, $delivery['event_type'], $data);

        if ($res['status'] === 'success') {
            Session::setFlash('success', "Webhook re-sent successfully to '{$delivery['endpoint_name']}' (HTTP {$res['response_code']} in {$res['duration_ms']}ms).");
        } else {
            Session::setFlash('error', "Webhook retry failed (HTTP {$res['response_code']}). Check endpoint URL.");
        }

        return Response::redirect(url('/admin/webhooks'));
    }
}
