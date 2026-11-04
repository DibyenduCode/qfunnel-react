<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');
\App\Core\Env::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/Core/helpers.php';

use App\Core\Database;
use App\Services\SubscriptionService;
use App\Services\UsageLimitService;
use App\Services\TenantContextService;
use App\Controllers\BillingController;
use App\Core\Request;

echo "\n====================================================\n";
echo " RUNNING SELF-SERVICE USER PLAN UPGRADE TEST SUITE \n";
echo "====================================================\n\n";

$passCount = 0;
function testAssert(bool $condition, string $label): void {
    global $passCount;
    if ($condition) {
        echo "  [PASS] {$label}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$label}\n";
        exit(1);
    }
}

// 1. Create a test tenant and user
$timestamp = time();
$email = "upgrade_test_{$timestamp}@test.local";
Database::execute(
    "INSERT INTO users (name, email, password_hash, is_platform_admin, status) VALUES ('Upgrade Tester', ?, 'hash', 0, 'active')",
    [$email]
);
$userId = (int)Database::lastInsertId();

Database::execute(
    "INSERT INTO tenants (name, slug, status) VALUES ('Upgrade Test Workspace', 'upgrade-workspace-{$timestamp}', 'active')"
);
$tenantId = (int)Database::lastInsertId();

Database::execute(
    "INSERT INTO tenant_memberships (tenant_id, user_id, role) VALUES (?, ?, 'owner')",
    [$tenantId, $userId]
);

// Assign Starter Plan initially (monthly)
$starterPlan = Database::fetchOne("SELECT id FROM plans WHERE slug = 'starter'");
Database::execute(
    "INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, billing_interval, starts_at, ends_at, current_period_start, current_period_end)
     VALUES (?, ?, 'active', 'monthly', NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY))",
    [$tenantId, $starterPlan['id']]
);

// Set active context & session
$_SESSION['user'] = [
    'id' => $userId,
    'name' => 'Upgrade Tester',
    'email' => $email,
    'is_platform_admin' => 0,
    'status' => 'active'
];
TenantContextService::setActiveTenant($tenantId, $userId);

testAssert(TenantContextService::getActiveTenantId() === $tenantId, "Tenant workspace #{$tenantId} initialized with owner user #{$userId}");

// 2. Verify Initial Usage Limits on Starter Plan
$initialLimits = UsageLimitService::getTenantLimits($tenantId);
testAssert($initialLimits['plan_slug'] === 'starter', "Initial workspace plan is Starter");
testAssert($initialLimits['max_funnels'] == 5, "Starter max funnels is 5");

// 3. Find Pro Plan
$proPlan = Database::fetchOne("SELECT * FROM plans WHERE slug = 'pro' OR slug = 'enterprise' ORDER BY id DESC LIMIT 1");
testAssert(!empty($proPlan), "Target upgrade plan (Pro/Enterprise) found with ID #{$proPlan['id']}");

// 4. Simulate Customer Upgrading via BillingController::upgrade
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'plan_id' => $proPlan['id'],
    'billing_interval' => 'yearly',
    'payment_method' => 'upi',
    'transaction_reference' => 'UPI-UTR-99887766'
];

$controller = new BillingController();
$response = $controller->upgrade(new Request());

// 5. Verify Subscription Updated
$subAfterUpgrade = SubscriptionService::getActiveSubscription($tenantId);
testAssert((int)$subAfterUpgrade['plan_id'] === (int)$proPlan['id'], "Subscription upgraded to target plan ID #{$proPlan['id']} ({$proPlan['name']})");
testAssert($subAfterUpgrade['billing_interval'] === 'yearly', "Subscription billing interval updated to yearly");
testAssert($subAfterUpgrade['status'] === 'active', "Subscription status remains active");

// 6. Verify Invoice Generated
$invoices = SubscriptionService::getInvoices($tenantId);
testAssert(count($invoices) === 1, "Self-service upgrade generated 1 paid invoice");
$invoice = $invoices[0];
testAssert($invoice['status'] === 'paid', "Generated invoice #{$invoice['invoice_number']} is marked as paid");
testAssert($invoice['payment_method'] === 'upi', "Invoice recorded payment method as UPI");
testAssert($invoice['transaction_reference'] === 'UPI-UTR-99887766', "Transaction UTR recorded on invoice");

// 7. Verify Limits Unlocked
$limitsAfterUpgrade = UsageLimitService::getTenantLimits($tenantId);
testAssert($limitsAfterUpgrade['max_funnels'] == -1 || $limitsAfterUpgrade['max_funnels'] >= 50, "Upgraded limits reflect unlocked funnel capacity");

// 8. Test Auto-Unsuspend on Plan Upgrade:
// Let's suspend the workspace first
Database::execute("UPDATE tenants SET status = 'suspended', suspension_reason = 'Payment overdue' WHERE id = ?", [$tenantId]);
$suspendedTenant = Database::fetchOne("SELECT status FROM tenants WHERE id = ?", [$tenantId]);
testAssert($suspendedTenant['status'] === 'suspended', "Tenant intentionally suspended to test self-service reactivation");

// User upgrades / renews plan again
$_POST = [
    'plan_id' => $proPlan['id'],
    'billing_interval' => 'monthly',
    'payment_method' => 'direct_activation',
    'transaction_reference' => 'AUTOREACTIVATE'
];
$controller->upgrade(new Request());

$reactivatedTenant = Database::fetchOne("SELECT status FROM tenants WHERE id = ?", [$tenantId]);
testAssert($reactivatedTenant['status'] === 'active', "Upgrading / renewing immediately unsuspends the tenant to 'active'");

// 9. Verify DOWNGRADE PREVENTION (User cannot downgrade to lower tier plan)
// User is now on Pro plan. Attempt to downgrade to Starter plan (ID 1, 749.00 vs Pro 1499.00)
$_POST = [
    'plan_id' => $starterPlan['id'],
    'billing_interval' => 'monthly',
    'payment_method' => 'direct_activation',
    'transaction_reference' => 'ILLEGAL_DOWNGRADE'
];
$controller->upgrade(new Request());
$flashError = \App\Core\Session::getFlash('error');
testAssert(str_contains($flashError ?? '', 'Downgrading to a lower tier plan is not permitted'), "Downgrade attempt to Starter is strictly blocked");

// Attempt to re-select same plan while active
$_POST = [
    'plan_id' => $proPlan['id'],
    'billing_interval' => 'monthly',
    'payment_method' => 'direct_activation',
    'transaction_reference' => 'SAME_PLAN'
];
$controller->upgrade(new Request());
$flashErrorSame = \App\Core\Session::getFlash('error');
testAssert(str_contains($flashErrorSame ?? '', 'already active'), "Selecting current plan while active is rejected with informative notice");

// Attempt to downgrade to Free plan (0.00)
$freePlan = Database::fetchOne("SELECT * FROM plans WHERE slug = 'free'");
if ($freePlan) {
    $_POST = [
        'plan_id' => $freePlan['id'],
        'billing_interval' => 'monthly',
        'payment_method' => 'direct_activation',
        'transaction_reference' => 'FREE_DOWNGRADE'
    ];
    $controller->upgrade(new Request());
    $flashErrorFree = \App\Core\Session::getFlash('error');
    testAssert(str_contains($flashErrorFree ?? '', 'Downgrading to a lower tier plan is not permitted'), "Downgrade attempt to Free plan is strictly blocked");
}

// 10. Verify UPGRADE TO EVEN HIGHER TIER IS ALLOWED (Pro -> Enterprise)
$enterprisePlan = Database::fetchOne("SELECT * FROM plans WHERE slug = 'enterprise'");
if ($enterprisePlan && (int)$proPlan['id'] !== (int)$enterprisePlan['id']) {
    $_POST = [
        'plan_id' => $enterprisePlan['id'],
        'billing_interval' => 'yearly',
        'payment_method' => 'direct_activation',
        'transaction_reference' => 'ENTERPRISE_UPGRADE'
    ];
    $controller->upgrade(new Request());
    $subAfterEnterprise = SubscriptionService::getActiveSubscription($tenantId);
    testAssert((int)$subAfterEnterprise['plan_id'] === (int)$enterprisePlan['id'], "Legitimate higher-tier upgrade (Pro -> Enterprise) is permitted");
}

// 11. Verify Audit Trail
$logs = Database::query("SELECT * FROM audit_logs WHERE tenant_id = ? AND action = 'upgrade_subscription_plan'", [$tenantId]);
testAssert(count($logs) >= 1, "Audit log securely captured customer upgrade events");

// Cleanup
Database::execute("DELETE FROM tenant_invoices WHERE tenant_id = ?", [$tenantId]);
Database::execute("DELETE FROM tenant_subscriptions WHERE tenant_id = ?", [$tenantId]);
Database::execute("DELETE FROM tenant_memberships WHERE tenant_id = ?", [$tenantId]);
Database::execute("DELETE FROM audit_logs WHERE tenant_id = ?", [$tenantId]);
Database::execute("DELETE FROM tenants WHERE id = ?", [$tenantId]);
Database::execute("DELETE FROM users WHERE id = ?", [$userId]);

echo "\n====================================================\n";
echo " ALL TESTS PASSED: {$passCount}/{$passCount}\n";
echo "====================================================\n";
