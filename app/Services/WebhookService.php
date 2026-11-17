<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class WebhookService
{
    /**
     * Dispatch event to all matching webhook endpoints for a tenant
     */
    public static function trigger(int $tenantId, string $eventType, array $payload, ?int $funnelId = null): array
    {
        $sql = "SELECT * FROM webhook_endpoints 
                WHERE tenant_id = ? 
                  AND is_active = 1 
                  AND (event_type = ? OR event_type = 'all')
                  AND (funnel_id IS NULL OR funnel_id = ?)";
        
        $endpoints = Database::query($sql, [$tenantId, $eventType, $funnelId]);
        if (empty($endpoints)) {
            return [];
        }

        $results = [];
        foreach ($endpoints as $endpoint) {
            $results[] = self::deliverPayload($endpoint, $eventType, $payload);
        }

        return $results;
    }

    /**
     * Deliver payload to a specific webhook endpoint with HMAC signature and response logging
     */
    public static function deliverPayload(array $endpoint, string $eventType, array $payload): array
    {
        $tenantId = (int)$endpoint['tenant_id'];
        $webhookId = (int)$endpoint['id'];
        $url = $endpoint['target_url'];
        $secret = $endpoint['secret'] ?? '';

        // OWASP A10: SSRF Protection
        if (!self::isSafeUrl($url)) {
            Logger::error("SSRF Block: Webhook dispatch aborted for unsafe destination: {$url}");
            return false;
        }

        $body = [
            'event' => $eventType,
            'timestamp' => date('c'),
            'data' => $payload,
        ];

        $jsonPayload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = !empty($secret) ? hash_hmac('sha256', $jsonPayload, $secret) : '';

        $headers = [
            'Content-Type: application/json',
            'User-Agent: FilterFunnel-Webhook-Service/1.0',
            'X-FilterFunnel-Event: ' . $eventType,
            'X-FilterFunnel-Delivery: ' . bin2hex(random_bytes(16)),
            'X-LeadCollector-Event: ' . $eventType,
            'X-QFunnel-Event: ' . $eventType,
        ];

        if (!empty($signature)) {
            $headers[] = 'X-FilterFunnel-Signature: ' . $signature;
            $headers[] = 'X-LeadCollector-Signature: ' . $signature;
            $headers[] = 'X-QFunnel-Signature: ' . $signature;
        }

        $startTime = microtime(true);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $isProd = \App\Core\Env::get('APP_ENV') === 'production';
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $isProd);

        $responseBody = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);
        $isSuccess = ($httpCode >= 200 && $httpCode < 300);

        if (!$isSuccess && !empty($curlError)) {
            $responseBody = "cURL Error: " . $curlError;
        }

        // Record delivery log in database
        try {
            Database::execute(
                "INSERT INTO webhook_deliveries (tenant_id, webhook_id, event_type, payload_json, response_code, response_body, status, duration_ms, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $tenantId,
                    $webhookId,
                    $eventType,
                    $jsonPayload,
                    $httpCode ?: null,
                    mb_substr((string)$responseBody, 0, 1000),
                    $isSuccess ? 'success' : 'failed',
                    $durationMs
                ]
            );
        } catch (\Throwable $e) {
            Logger::error("Failed to insert webhook delivery log: " . $e->getMessage());
        }

        return [
            'webhook_id' => $webhookId,
            'status' => $isSuccess ? 'success' : 'failed',
            'response_code' => $httpCode,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * Send a test ping payload to verify an endpoint URL
     */
    public static function testPing(int $tenantId, int $webhookId): array
    {
        $endpoint = Database::fetchOne("SELECT * FROM webhook_endpoints WHERE tenant_id = ? AND id = ?", [$tenantId, $webhookId]);
        if (!$endpoint) {
            throw new \RuntimeException("Webhook endpoint not found.");
        }

        $testPayload = [
            'test' => true,
            'message' => 'This is a test notification from FilterFunnel Webhook Engine.',
            'lead' => [
                'name' => 'John Doe (Test)',
                'email' => 'john.test@example.com',
                'phone' => '+15550199',
            ],
            'funnel' => [
                'id' => $endpoint['funnel_id'] ?? 1,
                'name' => 'Sample Assessment Funnel',
            ]
        ];

        return self::deliverPayload($endpoint, 'test.ping', $testPayload);
    }

    /**
     * Validate target webhook destination to prevent Server-Side Request Forgery (OWASP A10: SSRF).
     */
    public static function isSafeUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return false;
        }

        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        // Strictly reject private, reserved, and loopback IP addresses (secure by default)
        if (\App\Core\Env::get('APP_ENV') !== 'development') {
            $host = strtolower($parsed['host']);
            if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
                return false;
            }
            $ip = gethostbyname($host);
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }
}
