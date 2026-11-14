<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class SubscriptionService
{
    /**
     * Get the latest subscription record for a tenant.
     */
    public static function getActiveSubscription(int $tenantId): ?array
    {
        return Database::fetchOne(
            "SELECT ts.*, p.name as plan_name, p.slug as plan_slug, p.price as plan_price, p.limits_json, p.features_json
             FROM tenant_subscriptions ts
             JOIN plans p ON ts.plan_id = p.id
             WHERE ts.tenant_id = ?
             ORDER BY ts.id DESC LIMIT 1",
            [$tenantId]
        );
    }

    /**
     * Assign or upgrade subscription plan for a tenant workspace.
     */
    public static function assignPlan(int $tenantId, int $planId, int $durationDays = 30, int $adminUserId = 1, string $notes = ''): bool
    {
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);

        if (!$tenant || !$plan) {
            return false;
        }

        // Deactivate previous active / trial subscriptions
        Database::execute(
            "UPDATE tenant_subscriptions SET status = 'cancelled' WHERE tenant_id = ? AND status IN ('active', 'trial')",
            [$tenantId]
        );

        $amount = (float)($plan['price'] ?? 0);
        $currency = $plan['currency'] ?? 'INR';
        $interval = $plan['billing_cycle'] ?? 'monthly';

        Database::execute(
            "INSERT INTO tenant_subscriptions 
             (tenant_id, plan_id, status, billing_interval, starts_at, current_period_start, ends_at, current_period_end, amount, currency, provider, provider_subscription_id) 
             VALUES (?, ?, 'active', ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), DATE_ADD(NOW(), INTERVAL ? DAY), ?, ?, 'self_service', ?)",
            [$tenantId, $planId, $interval, $durationDays, $durationDays, $amount, $currency, 'sub_upgrade_' . time()]
        );

        Logger::info("Plan #{$planId} assigned to Tenant #{$tenantId} by User #{$adminUserId}. Notes: {$notes}");
        return true;
    }

    /**
     * Get comprehensive billing summary for a tenant.
     */
    public static function getBillingSummary(int $tenantId): array
    {
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if (!$tenant) {
            throw new RuntimeException("Tenant not found.");
        }

        $sub = self::getActiveSubscription($tenantId);
        $invoices = self::getInvoices($tenantId);

        $now = time();
        $endsAtTs = !empty($sub['current_period_end']) ? strtotime($sub['current_period_end']) : (!empty($sub['ends_at']) ? strtotime($sub['ends_at']) : null);
        $daysRemaining = $endsAtTs ? (int)ceil(($endsAtTs - $now) / 86400) : 0;
        
        $graceDays = (int)($sub['grace_period_days'] ?? 7);
        $graceDeadlineTs = $endsAtTs ? ($endsAtTs + ($graceDays * 86400)) : null;
        $isGraceActive = ($endsAtTs && $now > $endsAtTs && $graceDeadlineTs && $now <= $graceDeadlineTs);

        return [
            'tenant' => $tenant,
            'subscription' => $sub,
            'days_remaining' => $daysRemaining,
            'is_expired' => $endsAtTs && $now > $endsAtTs,
            'is_grace_active' => $isGraceActive,
            'grace_deadline' => $graceDeadlineTs ? date('Y-m-d H:i:s', $graceDeadlineTs) : null,
            'invoices' => $invoices,
        ];
    }

    /**
     * Super Admin power: Modify billing cycle, plan tier, dates, and auto-renew.
     */
    public static function modifyBillingCycle(int $tenantId, array $data, int $adminUserId): bool
    {
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if (!$tenant) {
            throw new RuntimeException("Tenant not found.");
        }

        $sub = self::getActiveSubscription($tenantId);
        $planId = !empty($data['plan_id']) ? (int)$data['plan_id'] : (int)($sub['plan_id'] ?? 1);
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            throw new RuntimeException("Selected plan does not exist.");
        }

        $interval = (string)($data['billing_interval'] ?? ($sub['billing_interval'] ?? 'monthly'));
        $currentPeriodStart = !empty($data['current_period_start']) ? date('Y-m-d H:i:s', strtotime($data['current_period_start'])) : date('Y-m-d H:i:s');
        
        // Calculate or read current_period_end
        if (!empty($data['current_period_end'])) {
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime($data['current_period_end']));
        } elseif ($interval === 'yearly') {
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($currentPeriodStart)));
        } elseif ($interval === 'quarterly') {
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+3 months', strtotime($currentPeriodStart)));
        } elseif ($interval === 'lifetime') {
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+50 years', strtotime($currentPeriodStart)));
        } else {
            $currentPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($currentPeriodStart)));
        }

        $nextBillingDate = $currentPeriodEnd;
        $status = (string)($data['status'] ?? 'active');
        $autoRenew = isset($data['auto_renew']) ? (int)(bool)$data['auto_renew'] : 1;
        $amount = isset($data['amount']) ? (float)$data['amount'] : (float)$plan['price'];
        $currency = (string)($data['currency'] ?? 'INR');

        if ($sub) {
            Database::execute(
                "UPDATE tenant_subscriptions 
                 SET plan_id = ?, status = ?, billing_interval = ?, current_period_start = ?, current_period_end = ?, 
                     ends_at = ?, next_billing_date = ?, auto_renew = ?, amount = ?, currency = ?
                 WHERE id = ?",
                [
                    $planId, $status, $interval, $currentPeriodStart, $currentPeriodEnd,
                    $currentPeriodEnd, $nextBillingDate, $autoRenew, $amount, $currency,
                    $sub['id']
                ]
            );
        } else {
            Database::execute(
                "INSERT INTO tenant_subscriptions 
                 (tenant_id, plan_id, status, billing_interval, starts_at, ends_at, current_period_start, current_period_end, next_billing_date, auto_renew, amount, currency, provider)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'manual_superadmin')",
                [
                    $tenantId, $planId, $status, $interval, $currentPeriodStart, $currentPeriodEnd,
                    $currentPeriodStart, $currentPeriodEnd, $nextBillingDate, $autoRenew, $amount, $currency
                ]
            );
        }

        AuditLogService::log($tenantId, $adminUserId, 'modify_billing_cycle', 'tenant_subscription', $tenantId, [
            'plan_id' => $planId,
            'plan_name' => $plan['name'],
            'interval' => $interval,
            'period_end' => $currentPeriodEnd,
            'status' => $status
        ]);

        return true;
    }

    /**
     * Record a manual payment (Cash, Bank Transfer, Cheque, UPI, Manual Stripe).
     * Automatically generates a tenant invoice and advances the billing cycle.
     */
    public static function recordManualPayment(int $tenantId, array $data, int $adminUserId): array
    {
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if (!$tenant) {
            throw new RuntimeException("Workspace not found.");
        }

        $sub = self::getActiveSubscription($tenantId);
        $planId = !empty($data['plan_id']) ? (int)$data['plan_id'] : ($sub ? (int)$sub['plan_id'] : 1);
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);

        $amount = (float)($data['amount'] ?? ($plan['price'] ?? 0.00));
        $currency = (string)($data['currency'] ?? 'INR');
        $interval = (string)($data['billing_interval'] ?? ($sub['billing_interval'] ?? 'monthly'));
        $paymentMethod = (string)($data['payment_method'] ?? 'bank_transfer');
        $txnRef = trim((string)($data['transaction_reference'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));

        // Generate unique invoice number: INV-YEAR-TENANTID-RAND
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string)$tenantId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(2)));

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

        // Insert Invoice
        Database::execute(
            "INSERT INTO tenant_invoices 
             (tenant_id, subscription_id, invoice_number, amount, currency, status, billing_interval, period_start, period_end, payment_method, transaction_reference, notes, paid_at)
             VALUES (?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?, ?, ?, NOW())",
            [
                $tenantId,
                $sub['id'] ?? null,
                $invoiceNumber,
                $amount,
                $currency,
                $interval,
                $periodStart,
                $periodEnd,
                $paymentMethod,
                $txnRef,
                $notes
            ]
        );
        $invoiceId = (int)Database::lastInsertId();

        // Update/advance subscription
        $currentPeriodEnd = $periodEnd . ' 23:59:59';
        if ($sub) {
            Database::execute(
                "UPDATE tenant_subscriptions 
                 SET plan_id = ?, status = 'active', billing_interval = ?, current_period_start = NOW(), 
                     current_period_end = ?, ends_at = ?, next_billing_date = ?
                 WHERE id = ?",
                [$planId, $interval, $currentPeriodEnd, $currentPeriodEnd, $currentPeriodEnd, $sub['id']]
            );
        } else {
            Database::execute(
                "INSERT INTO tenant_subscriptions 
                 (tenant_id, plan_id, status, billing_interval, starts_at, ends_at, current_period_start, current_period_end, next_billing_date, provider)
                 VALUES (?, ?, 'active', ?, NOW(), ?, NOW(), ?, ?, 'manual_invoice')",
                [$tenantId, $planId, $interval, $currentPeriodEnd, $currentPeriodEnd, $currentPeriodEnd]
            );
        }

        // If the tenant was suspended, auto unsuspend it!
        if ($tenant['status'] === 'suspended') {
            self::unsuspendWorkspace($tenantId, $currentPeriodEnd, $adminUserId);
        }

        AuditLogService::log($tenantId, $adminUserId, 'record_manual_payment', 'tenant_invoices', $invoiceId, [
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'period_end' => $periodEnd
        ]);

        // Dispatch Invoice Receipt Email via Global SMTP
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
                    'tenant_name' => $tenant['name'],
                    'invoice_number' => $invoiceNumber,
                    'plan_name' => $plan['name'] ?? 'Custom Plan',
                    'amount' => number_format((float)$amount, 2),
                    'currency_symbol' => PlatformSettingService::getCurrencySymbol(),
                    'payment_method' => str_replace('_', ' ', $paymentMethod),
                    'billing_interval' => $interval,
                    'paid_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            Logger::error("Failed to send invoice receipt email: " . $e->getMessage());
        }

        return [
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'currency' => $currency,
            'period_end' => $periodEnd,
        ];
    }

    /**
     * Suspend workspace. All customer data (funnels, responses, forms) is 100% PRESERVED.
     */
    public static function suspendWorkspace(int $tenantId, string $reason, string $notes, int $adminUserId): bool
    {
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if (!$tenant) {
            throw new RuntimeException("Workspace not found.");
        }

        Database::execute(
            "UPDATE tenants 
             SET status = 'suspended', suspension_reason = ?, suspension_notes = ?, suspended_at = NOW() 
             WHERE id = ?",
            [$reason, $notes, $tenantId]
        );

        Database::execute(
            "UPDATE tenant_subscriptions SET status = 'past_due' WHERE tenant_id = ? AND status = 'active'",
            [$tenantId]
        );

        AuditLogService::log($tenantId, $adminUserId, 'suspend_workspace', 'tenant', $tenantId, [
            'reason' => $reason,
            'notes' => $notes,
            'data_preserved' => true
        ]);

        Logger::info("Tenant suspended: #{$tenantId} - {$tenant['name']}. Reason: {$reason}");

        // Dispatch account_suspended email to workspace owner
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
                MailService::sendTemplate('account_suspended', $owner['email'], $owner['name'], [
                    'tenant_name' => $tenant['name'],
                    'suspension_reason' => $reason,
                ]);
            }
        } catch (\Throwable $e) {
            Logger::error("Failed to send suspension email: " . $e->getMessage());
        }

        return true;
    }

    /**
     * Unsuspend workspace. Restores active status and unfreezes access immediately.
     */
    public static function unsuspendWorkspace(int $tenantId, ?string $extendUntil, int $adminUserId): bool
    {
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if (!$tenant) {
            throw new RuntimeException("Workspace not found.");
        }

        Database::execute(
            "UPDATE tenants 
             SET status = 'active', suspension_reason = NULL, is_termination_due = 0 
             WHERE id = ?",
            [$tenantId]
        );

        // If date extension provided (e.g. +30 days), apply it so tenant doesn't immediately expire
        if (!empty($extendUntil)) {
            $formattedEnd = date('Y-m-d H:i:s', strtotime($extendUntil));
            Database::execute(
                "UPDATE tenant_subscriptions 
                 SET status = 'active', current_period_end = ?, ends_at = ?, next_billing_date = ? 
                 WHERE tenant_id = ?",
                [$formattedEnd, $formattedEnd, $formattedEnd, $tenantId]
            );
        } else {
            Database::execute(
                "UPDATE tenant_subscriptions SET status = 'active' WHERE tenant_id = ?",
                [$tenantId]
            );
        }

        AuditLogService::log($tenantId, $adminUserId, 'unsuspend_workspace', 'tenant', $tenantId, [
            'extended_until' => $extendUntil
        ]);

        Logger::info("Tenant unsuspended: #{$tenantId} - {$tenant['name']}");
        return true;
    }

    /**
     * Flag workspace as Termination Due (places it into the Super Admin Manual Review Queue).
     */
    public static function markTerminationDue(int $tenantId, string $reason, int $adminUserId): bool
    {
        Database::execute(
            "UPDATE tenants 
             SET is_termination_due = 1, termination_due_at = NOW(), termination_reason = ? 
             WHERE id = ?",
            [$reason, $tenantId]
        );

        AuditLogService::log($tenantId, $adminUserId, 'mark_termination_due', 'tenant', $tenantId, [
            'reason' => $reason
        ]);

        return true;
    }

    /**
     * Dismiss from Termination Due list (Super Admin decides to hold or give extra time).
     */
    public static function dismissTerminationDue(int $tenantId, int $adminUserId): bool
    {
        Database::execute(
            "UPDATE tenants 
             SET is_termination_due = 0, termination_due_at = NULL 
             WHERE id = ?",
            [$tenantId]
        );

        AuditLogService::log($tenantId, $adminUserId, 'dismiss_termination_due', 'tenant', $tenantId, []);
        return true;
    }

    /**
     * Super Admin manual termination ONLY. Never runs automatically.
     * All customer data is archived and preserved for historical compliance.
     */
    public static function terminateWorkspace(int $tenantId, string $reason, int $adminUserId): bool
    {
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if (!$tenant) {
            throw new RuntimeException("Workspace not found.");
        }

        Database::execute(
            "UPDATE tenants 
             SET status = 'terminated', is_termination_due = 0, terminated_at = NOW(), 
                 terminated_by = ?, termination_reason = ? 
             WHERE id = ?",
            [$adminUserId, $reason, $tenantId]
        );

        Database::execute(
            "UPDATE tenant_subscriptions SET status = 'cancelled' WHERE tenant_id = ?",
            [$tenantId]
        );

        AuditLogService::log($tenantId, $adminUserId, 'terminate_workspace', 'tenant', $tenantId, [
            'tenant_name' => $tenant['name'],
            'reason' => $reason,
            'data_archived' => true
        ]);

        Logger::warning("Tenant manually terminated by Admin #{$adminUserId}: #{$tenantId} - {$tenant['name']}");
        return true;
    }

    /**
     * Get all workspaces due for manual termination review by Super Admin.
     */
    public static function getTerminationDueWorkspaces(): array
    {
        // Workspaces flagged explicitly OR suspended for 30+ days without resolution
        return Database::query(
            "SELECT t.*,
                    DATEDIFF(NOW(), t.suspended_at) as days_suspended,
                    (SELECT COUNT(*) FROM funnels f WHERE f.tenant_id = t.id AND f.status != 'archived') as funnels_count,
                    (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.tenant_id = t.id) as sessions_count,
                    (SELECT u.name FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_name,
                    (SELECT u.email FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_email,
                    p.name as plan_name,
                    ts.ends_at as subscription_ends_at
             FROM tenants t
             LEFT JOIN tenant_subscriptions ts ON ts.tenant_id = t.id
             LEFT JOIN plans p ON ts.plan_id = p.id
             WHERE t.status = 'suspended' 
                AND (t.is_termination_due = 1 OR (t.suspended_at IS NOT NULL AND t.suspended_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)))
             ORDER BY t.suspended_at ASC"
        );
    }

    /**
     * Get invoices for a tenant.
     */
    public static function getInvoices(int $tenantId): array
    {
        return Database::query(
            "SELECT * FROM tenant_invoices WHERE tenant_id = ? ORDER BY id DESC",
            [$tenantId]
        );
    }

    /**
     * Get all platform invoices across all tenants.
     */
    public static function getAllInvoices(int $limit = 100): array
    {
        return Database::query(
            "SELECT ti.*, t.name as tenant_name, t.slug as tenant_slug 
             FROM tenant_invoices ti
             JOIN tenants t ON ti.tenant_id = t.id
             ORDER BY ti.id DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Cron/CLI background evaluator.
     * Evaluates subscriptions past their due date:
     * - Grace period check: sets status='past_due'
     * - Past grace period: sets status='suspended' (Zero data loss, public quizzes paused)
     * - Suspended > 30 days: sets is_termination_due = 1 for Super Admin manual review
     * - STRICT RULE: Never automatically terminates!
     */
    public static function runBillingCycleEvaluation(): array
    {
        $now = date('Y-m-d H:i:s');
        $suspendedCount = 0;
        $flaggedDueCount = 0;

        // 1. Find active subscriptions that have passed their current_period_end + grace_period_days
        $expiredSubs = Database::query(
            "SELECT ts.*, t.name as tenant_name 
             FROM tenant_subscriptions ts
             JOIN tenants t ON ts.tenant_id = t.id
             WHERE t.status = 'active' 
               AND ts.current_period_end IS NOT NULL 
               AND NOW() > DATE_ADD(ts.current_period_end, INTERVAL ts.grace_period_days DAY)"
        );

        foreach ($expiredSubs as $sub) {
            $tenantId = (int)$sub['tenant_id'];
            self::suspendWorkspace($tenantId, 'Overdue subscription payment past grace period', 'System automated billing cycle check', 0);
            $suspendedCount++;
        }

        // 2. Find workspaces suspended for > 30 days and flag them as Termination Due for Super Admin review
        $agedSuspended = Database::query(
            "SELECT id FROM tenants 
             WHERE status = 'suspended' 
               AND is_termination_due = 0 
               AND suspended_at IS NOT NULL 
               AND suspended_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        foreach ($agedSuspended as $t) {
            self::markTerminationDue((int)$t['id'], 'Workspace suspended for more than 30 days without payment or resolution', 0);
            $flaggedDueCount++;
        }

        // 3. Find active subscriptions due for renewal within the next 3 days
        $upcomingRenewals = Database::query(
            "SELECT ts.*, t.name as tenant_name, p.name as plan_name, p.price as plan_price,
                    u.name as owner_name, u.email as owner_email
             FROM tenant_subscriptions ts
             JOIN tenants t ON ts.tenant_id = t.id
             LEFT JOIN plans p ON ts.plan_id = p.id
             JOIN tenant_memberships tm ON tm.tenant_id = t.id AND tm.role = 'owner'
             JOIN users u ON tm.user_id = u.id
             WHERE t.status = 'active'
               AND ts.current_period_end IS NOT NULL
               AND ts.current_period_end BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)"
        );

        $reminderCount = 0;
        foreach ($upcomingRenewals as $ren) {
            if (!empty($ren['owner_email'])) {
                try {
                    MailService::sendTemplate('renewal_reminder', $ren['owner_email'], $ren['owner_name'], [
                        'tenant_name' => $ren['tenant_name'],
                        'plan_name' => $ren['plan_name'] ?? 'Subscription Plan',
                        'amount' => number_format((float)($ren['plan_price'] ?? 0), 2),
                        'currency_symbol' => PlatformSettingService::getCurrencySymbol(),
                        'due_date' => date('Y-m-d', strtotime($ren['current_period_end'])),
                        'billing_interval' => $ren['billing_interval'] ?? 'monthly',
                    ]);
                    $reminderCount++;
                } catch (\Throwable $e) {
                    Logger::error("Failed to send renewal reminder to {$ren['owner_email']}: " . $e->getMessage());
                }
            }
        }

        return [
            'suspended_count' => $suspendedCount,
            'flagged_due_count' => $flaggedDueCount,
            'renewal_reminders_sent' => $reminderCount,
            'evaluated_at' => $now,
        ];
    }
}
