<?php

namespace App\Services;

use App\Core\Logger;
use App\Core\Database;
use RuntimeException;

class CashfreeService
{
    public const API_VERSION = '2023-08-01';
    public const SANDBOX_URL = 'https://sandbox.cashfree.com/pg';
    public const PRODUCTION_URL = 'https://api.cashfree.com/pg';

    /**
     * Retrieve Cashfree gateway settings.
     */
    public static function getConfig(): array
    {
        $gateways = PlatformSettingService::getPaymentGateways();
        return $gateways['cashfree'] ?? [
            'enabled' => false,
            'mode' => 'sandbox',
            'app_id' => '',
            'secret_key' => '',
            'api_version' => self::API_VERSION,
        ];
    }

    /**
     * Check if Cashfree is enabled and configured.
     */
    public static function isConfigured(): bool
    {
        $cfg = self::getConfig();
        return !empty($cfg['enabled']) && !empty($cfg['app_id']) && !empty($cfg['secret_key']);
    }

    /**
     * Get base URL depending on environment mode.
     */
    public static function getBaseUrl(?string $mode = null): string
    {
        if ($mode === null) {
            $cfg = self::getConfig();
            $mode = $cfg['mode'] ?? 'sandbox';
        }
        return strtolower($mode) === 'production' ? self::PRODUCTION_URL : self::SANDBOX_URL;
    }

    /**
     * Create an order in Cashfree.
     */
    public static function createOrder(int $tenantId, array $user, array $plan, ?string $returnUrl = null): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['enabled'])) {
            throw new RuntimeException("Cashfree payment gateway is currently disabled.");
        }
        if (empty($cfg['app_id']) || empty($cfg['secret_key'])) {
            throw new RuntimeException("Cashfree payment gateway credentials are not configured.");
        }

        $appId = trim($cfg['app_id']);
        $secretKey = trim($cfg['secret_key']);
        $mode = $cfg['mode'] ?? 'sandbox';
        $baseUrl = self::getBaseUrl($mode);

        $planId = (int)$plan['id'];
        $amount = round((float)$plan['price'], 2);
        $currency = 'INR';

        // Unique order ID format: order_t{tenant_id}_p{plan_id}_{timestamp}_{random}
        $orderId = 'cf_t' . $tenantId . '_p' . $planId . '_' . time() . '_' . substr(bin2hex(random_bytes(2)), 0, 4);

        $defaultReturnUrl = url('/admin/billing?cashfree_order_id=' . $orderId);
        $finalReturnUrl = $returnUrl ?: $defaultReturnUrl;
        $notifyUrl = url('/api/webhook/cashfree');

        // Customer details
        $customerPhone = '9999999999';
        $customerName = !empty($user['name']) ? trim($user['name']) : 'Workspace Owner';
        $customerEmail = !empty($user['email']) ? trim($user['email']) : 'billing@filterfunnel.in';
        $customerId = 'cust_' . ($user['id'] ?? $tenantId);

        $payload = [
            'order_id' => $orderId,
            'order_amount' => $amount,
            'order_currency' => $currency,
            'customer_details' => [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
            ],
            'order_meta' => [
                'return_url' => $finalReturnUrl,
                'notify_url' => $notifyUrl,
                'payment_methods' => 'cc,dc,upi,nb,app',
            ],
            'order_note' => 'Subscription: ' . ($plan['name'] ?? 'Plan Upgrade'),
        ];

        $ch = curl_init($baseUrl . '/orders');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => [
                'x-client-id: ' . $appId,
                'x-client-secret: ' . $secretKey,
                'x-api-version: ' . self::API_VERSION,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            Logger::error("Cashfree order creation cURL error: " . $curlError);
            throw new RuntimeException("Failed to connect to Cashfree payment server: " . $curlError);
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 400 || empty($decoded['payment_session_id'])) {
            $msg = $decoded['message'] ?? ($decoded['error'] ?? 'Cashfree API rejected order creation (HTTP ' . $httpCode . ')');
            Logger::error("Cashfree order rejected: " . $response);
            throw new RuntimeException($msg);
        }

        return [
            'order_id' => $orderId,
            'cf_order_id' => $decoded['cf_order_id'] ?? null,
            'payment_session_id' => $decoded['payment_session_id'],
            'order_status' => $decoded['order_status'] ?? 'ACTIVE',
            'amount' => $amount,
            'currency' => $currency,
            'mode' => $mode,
            'plan_name' => $plan['name'],
            'plan_id' => $planId,
        ];
    }

    /**
     * Retrieve order details and verify payment status from Cashfree.
     */
    public static function getOrderDetails(string $orderId): ?array
    {
        $cfg = self::getConfig();
        $appId = trim($cfg['app_id'] ?? '');
        $secretKey = trim($cfg['secret_key'] ?? '');
        $mode = $cfg['mode'] ?? 'sandbox';
        $baseUrl = self::getBaseUrl($mode);

        if (empty($appId) || empty($secretKey)) {
            return null;
        }

        $ch = curl_init($baseUrl . '/orders/' . urlencode($orderId));
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'x-client-id: ' . $appId,
                'x-client-secret: ' . $secretKey,
                'x-api-version: ' . self::API_VERSION,
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Verify payment status and activate subscription if paid.
     */
    public static function verifyAndActivate(int $tenantId, int $planId, string $orderId, int $userId): array
    {
        $order = self::getOrderDetails($orderId);
        if (!$order) {
            throw new RuntimeException("Could not retrieve order details from Cashfree. Please contact support with Order ID: {$orderId}");
        }

        $status = strtoupper($order['order_status'] ?? '');
        if ($status !== 'PAID') {
            throw new RuntimeException("Order is currently in '{$status}' status. Payment was not successfully completed.");
        }

        // Check if invoice already generated for this transaction to prevent double activation
        $existingInvoice = Database::fetchOne(
            "SELECT id, invoice_number FROM tenant_invoices WHERE transaction_reference = ?",
            [$orderId]
        );

        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            throw new RuntimeException("Associated subscription plan not found.");
        }

        $amount = (float)($order['order_amount'] ?? $plan['price']);
        $currency = $order['order_currency'] ?? 'INR';
        $interval = (string)($plan['billing_interval'] ?? ($plan['billing_cycle'] ?? 'monthly'));

        $periodStart = date('Y-m-d');
        if ($interval === 'yearly') {
            $periodEnd = date('Y-m-d', strtotime('+1 year'));
        } elseif ($interval === 'semi_annual') {
            $periodEnd = date('Y-m-d', strtotime('+6 months'));
        } elseif ($interval === 'quarterly') {
            $periodEnd = date('Y-m-d', strtotime('+3 months'));
        } else {
            $periodEnd = date('Y-m-d', strtotime('+1 month'));
        }

        // Assign plan if not already updated
        SubscriptionService::assignPlan($tenantId, $planId, 30, $userId, 'Cashfree Gateway: ' . $orderId);

        $invoiceNumber = $existingInvoice['invoice_number'] ?? null;
        if (!$existingInvoice) {
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string)$tenantId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(2)));

            Database::execute(
                "INSERT INTO tenant_invoices 
                 (tenant_id, invoice_number, amount, currency, status, billing_interval, period_start, period_end, payment_method, transaction_reference, notes, paid_at) 
                 VALUES (?, ?, ?, ?, 'paid', ?, ?, ?, 'cashfree', ?, 'Online Cashfree Payment', NOW())",
                [
                    $tenantId,
                    $invoiceNumber,
                    $amount,
                    $currency,
                    $interval,
                    $periodStart,
                    $periodEnd,
                    $orderId,
                ]
            );
        }

        // Unsuspend workspace if it was suspended
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if ($tenant && $tenant['status'] === 'suspended') {
            SubscriptionService::unsuspendWorkspace($tenantId, $periodEnd . ' 23:59:59', $userId);
        }

        // Dispatch invoice receipt email
        try {
            $owner = Database::fetchOne(
                "SELECT u.name, u.email 
                 FROM tenant_memberships tm 
                 JOIN users u ON tm.user_id = u.id 
                 WHERE tm.tenant_id = ? AND tm.role = 'owner' 
                 LIMIT 1",
                [$tenantId]
            );
            if ($owner && !empty($owner['email'])) {
                MailService::sendTemplate('invoice_receipt', $owner['email'], $owner['name'], [
                    'tenant_name' => $tenant['name'] ?? 'Workspace',
                    'invoice_number' => $invoiceNumber,
                    'plan_name' => $plan['name'],
                    'amount' => number_format($amount, 2),
                    'currency_symbol' => PlatformSettingService::getCurrencySymbol(),
                    'payment_method' => 'Cashfree Online',
                    'billing_interval' => $interval,
                    'paid_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            Logger::error("Failed to send Cashfree receipt email: " . $e->getMessage());
        }

        AuditLogService::log($tenantId, $userId, 'cashfree_payment_success', 'tenant_invoices', null, [
            'order_id' => $orderId,
            'amount' => $amount,
            'plan_name' => $plan['name'],
        ]);

        return [
            'success' => true,
            'order_id' => $orderId,
            'plan_name' => $plan['name'],
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'status' => 'PAID',
        ];
    }
}
