<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Services\TenantContextService;
use App\Services\AuthorizationService;
use App\Services\SubscriptionService;
use App\Services\UsageLimitService;
use App\Services\PlatformSettingService;
use App\Services\AuditLogService;

class BillingController
{
    /**
     * User/Customer Billing & Upgrade Dashboard
     */
    public function index(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $tenant = TenantContextService::getActiveTenant();

        $sub = SubscriptionService::getActiveSubscription($tenantId);
        $billing = SubscriptionService::getBillingSummary($tenantId);
        $usage = UsageLimitService::getUsageOverview($tenantId);

        // Fetch all active visible plans for self-service upgrading (plus current plan if already enrolled)
        $currentPlanId = $sub['plan_id'] ?? 0;
        $plans = Database::query("SELECT * FROM plans WHERE is_active = 1 AND (is_visible = 1 OR id = ?) ORDER BY price ASC, id ASC", [$currentPlanId]);
        $featureCatalog = UsageLimitService::getFeatureCatalog();
        $gateways = PlatformSettingService::getPaymentGateways();
        $invoices = SubscriptionService::getInvoices($tenantId);

        return view('billing.index', [
            'title' => 'Subscription & Plans — Quiz Funnel SaaS',
            'tenant' => $tenant,
            'subscription' => $sub,
            'billing' => $billing,
            'usage' => $usage,
            'plans' => $plans,
            'featureCatalog' => $featureCatalog,
            'gateways' => $gateways,
            'invoices' => $invoices,
            'canManageBilling' => in_array(TenantContextService::getCurrentRole(), ['owner', 'admin'], true) || AuthorizationService::isPlatformAdmin(),
        ]);
    }

    /**
     * Handle Plan Upgrade Request by Customer
     */
    public function upgrade(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $user = current_user();
        $role = TenantContextService::getCurrentRole();

        if (!in_array($role, ['owner', 'admin'], true) && !AuthorizationService::isPlatformAdmin()) {
            Session::setFlash('error', "You need owner or admin permission to modify subscription plans.");
            return Response::redirect(url('/admin/billing'));
        }

        $planId = (int)$request->input('plan_id');
        $interval = (string)$request->input('billing_interval', 'monthly');
        $paymentMethod = (string)$request->input('payment_method', 'direct_activation');
        $txnRef = trim((string)$request->input('transaction_reference', ''));

        $sub = SubscriptionService::getActiveSubscription($tenantId);
        $currentPlanId = $sub['plan_id'] ?? 0;
        $targetPlan = Database::fetchOne("SELECT * FROM plans WHERE id = ? AND is_active = 1 AND (is_visible = 1 OR id = ?)", [$planId, $currentPlanId]);
        if (!$targetPlan) {
            Session::setFlash('error', "Selected plan is invalid or currently inactive.");
            return Response::redirect(url('/admin/billing'));
        }

        // Enforce Upgrade-Only Policy (Downgrades and re-selecting current tier are prohibited)
        $existingSub = SubscriptionService::getActiveSubscription($tenantId);
        $currentPlanPrice = 0.0;
        if ($existingSub && !empty($existingSub['plan_id'])) {
            $currentPlan = Database::fetchOne("SELECT price FROM plans WHERE id = ?", [$existingSub['plan_id']]);
            if ($currentPlan) {
                $currentPlanPrice = (float)$currentPlan['price'];
            }
        }

        $targetPrice = (float)$targetPlan['price'];

        if ($existingSub && !empty($existingSub['plan_id'])) {
            $isTenantSuspended = false;
            $tenantRow = Database::fetchOne("SELECT status FROM tenants WHERE id = ?", [$tenantId]);
            if ($tenantRow && $tenantRow['status'] === 'suspended') {
                $isTenantSuspended = true;
            }

            if (!$isTenantSuspended && (int)$targetPlan['id'] === (int)$existingSub['plan_id']) {
                Session::setFlash('error', "You are already active on the '{$targetPlan['name']}' plan. You can only upgrade to a higher tier plan.");
                return Response::redirect(url('/admin/billing'));
            }

            if ($targetPrice < $currentPlanPrice) {
                Session::setFlash('error', "Downgrading to a lower tier plan is not permitted. You can only upgrade to a higher tier plan.");
                return Response::redirect(url('/admin/billing'));
            }
        }

        $price = $targetPrice;
        if ($interval === 'yearly') {
            $amount = $price * 12 * 0.85; // 15% discount for annual billing
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 year'));
        } else {
            $amount = $price;
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
        }

        try {
            // Generate unique invoice number: INV-YEAR-TENANTID-RAND
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string)$tenantId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $periodStart = date('Y-m-d');
            $periodEnd = date('Y-m-d', strtotime($currentPeriodEnd));

            // 1. Create paid invoice record
            Database::execute(
                "INSERT INTO tenant_invoices 
                 (tenant_id, invoice_number, amount, currency, status, billing_interval, period_start, period_end, payment_method, transaction_reference, notes, paid_at)
                 VALUES (?, ?, ?, 'INR', 'paid', ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $tenantId,
                    $invoiceNumber,
                    $amount,
                    $interval,
                    $periodStart,
                    $periodEnd,
                    $paymentMethod,
                    $txnRef ?: 'Self-Service Customer Upgrade',
                    "Upgraded to {$targetPlan['name']} plan by {$user['name']} ({$user['email']})"
                ]
            );
            $invoiceId = (int)Database::lastInsertId();

            // 2. Update active subscription
            $existingSub = SubscriptionService::getActiveSubscription($tenantId);
            if ($existingSub) {
                Database::execute(
                    "UPDATE tenant_subscriptions 
                     SET plan_id = ?, status = 'active', billing_interval = ?, current_period_start = NOW(), 
                         current_period_end = ?, ends_at = ?, next_billing_date = ?, amount = ?, currency = 'INR', provider = ?
                     WHERE id = ?",
                    [
                        $planId, $interval, $currentPeriodEnd, $currentPeriodEnd, $currentPeriodEnd, 
                        $amount, $paymentMethod, $existingSub['id']
                    ]
                );
            } else {
                Database::execute(
                    "INSERT INTO tenant_subscriptions 
                     (tenant_id, plan_id, status, billing_interval, starts_at, ends_at, current_period_start, current_period_end, next_billing_date, amount, currency, provider)
                     VALUES (?, ?, 'active', ?, NOW(), ?, NOW(), ?, ?, ?, 'INR', ?)",
                    [
                        $tenantId, $planId, $interval, $currentPeriodEnd, $currentPeriodEnd, $currentPeriodEnd, $amount, $paymentMethod
                    ]
                );
            }

            // 3. If tenant was suspended, auto unsuspend it!
            $tenant = Database::fetchOne("SELECT status FROM tenants WHERE id = ?", [$tenantId]);
            if ($tenant && $tenant['status'] === 'suspended') {
                SubscriptionService::unsuspendWorkspace($tenantId, $currentPeriodEnd, (int)$user['id']);
            }

            AuditLogService::log($tenantId, (int)$user['id'], 'upgrade_subscription_plan', 'tenant_subscription', $planId, [
                'plan_name' => $targetPlan['name'],
                'interval' => $interval,
                'amount' => $amount,
                'invoice_number' => $invoiceNumber
            ]);

            Session::setFlash('success', "🎉 Congratulations! Your workspace has been upgraded to the '{$targetPlan['name']}' plan. All features and quotas have been unlocked!");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Failed to process plan upgrade: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/billing'));
    }
}
