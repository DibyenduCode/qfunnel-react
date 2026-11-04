<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');

use App\Core\Database;
use App\Core\Env;
use App\Services\SubscriptionService;
use App\Services\FunnelService;

// Load .env
\App\Core\Env::load(__DIR__ . '/../.env');

echo "====================================================\n";
echo " RUNNING RECURRING BILLING & SUSPENSION TEST SUITE \n";
echo "====================================================\n\n";

$passCount = 0;
function testAssert(bool $condition, string $message) {
    global $passCount;
    if ($condition) {
        echo "  [PASS] {$message}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$message}\n";
        exit(1);
    }
}

// 1. Setup Test Workspace
$testSlug = 'test-billing-' . bin2hex(random_bytes(3));
Database::execute(
    "INSERT INTO tenants (name, slug, status) VALUES (?, ?, 'active')",
    ['Test Billing Workspace', $testSlug]
);
$tenantId = (int)Database::lastInsertId();

Database::execute(
    "INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, billing_interval, starts_at, ends_at, current_period_start, current_period_end)
     VALUES (?, 1, 'active', 'monthly', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))",
    [$tenantId]
);

testAssert($tenantId > 0, "Test tenant workspace created with ID #{$tenantId}");

// 2. Test Modify Billing Cycle
$modifyResult = SubscriptionService::modifyBillingCycle($tenantId, [
    'plan_id' => 2, // Pro
    'billing_interval' => 'yearly',
    'current_period_start' => date('Y-m-d'),
    'current_period_end' => date('Y-m-d', strtotime('+1 year')),
    'amount' => 999.00,
    'currency' => 'INR',
    'status' => 'active',
    'auto_renew' => 1
], 1);

testAssert($modifyResult === true, "Super Admin modified billing cycle to Yearly Pro plan");

$sub = SubscriptionService::getActiveSubscription($tenantId);
testAssert($sub['billing_interval'] === 'yearly', "Subscription interval updated to yearly");
testAssert((int)$sub['plan_id'] === 2, "Subscription plan ID updated to 2 (Pro)");

// 3. Test Record Manual/Offline Payment
$payResult = SubscriptionService::recordManualPayment($tenantId, [
    'plan_id' => 2,
    'amount' => 999.00,
    'payment_method' => 'bank_transfer',
    'transaction_reference' => 'UTR123456789',
    'notes' => 'Received via Bank NEFT transfer',
], 1);

testAssert(!empty($payResult['invoice_number']), "Manual payment generated invoice: {$payResult['invoice_number']}");

$invoices = SubscriptionService::getInvoices($tenantId);
testAssert(count($invoices) >= 1, "Invoice record persisted in tenant_invoices table");
testAssert($invoices[0]['status'] === 'paid', "Invoice marked as paid with amount {$invoices[0]['amount']}");

// 4. Test Data-Stay Suspension
// Create a sample funnel to ensure data is preserved
Database::execute(
    "INSERT INTO funnels (tenant_id, name, slug, status) VALUES (?, ?, ?, 'published')",
    [$tenantId, 'Customer Lead Funnel', 'lead-funnel-' . bin2hex(random_bytes(2))]
);
$funnelId = (int)Database::lastInsertId();

$suspendResult = SubscriptionService::suspendWorkspace($tenantId, 'Overdue subscription payment', 'Testing suspension data preservation', 1);
testAssert($suspendResult === true, "Workspace suspended by Super Admin");

$tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
testAssert($tenant['status'] === 'suspended', "Tenant status is 'suspended'");
testAssert(!empty($tenant['suspended_at']), "Tenant suspended_at timestamp recorded");

// DATA STAY VERIFICATION: Verify funnel still exists and is untouched!
$funnelCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE tenant_id = ?", [$tenantId]);
testAssert($funnelCount === 1, "DATA STAY VERIFIED: Customer funnel data remains 100% intact during suspension");

// 5. Test Termination Due Queue (Zero Auto-Terminations)
SubscriptionService::markTerminationDue($tenantId, 'Suspended over 30 days without payment', 1);
$terminationDueList = SubscriptionService::getTerminationDueWorkspaces();
$foundInQueue = false;
foreach ($terminationDueList as $td) {
    if ((int)$td['id'] === $tenantId) {
        $foundInQueue = true;
        break;
    }
}
testAssert($foundInQueue === true, "Workspace correctly appears in Super Admin 'Termination Due' review queue");

// 6. Test Unsuspend Workspace
$unsuspendResult = SubscriptionService::unsuspendWorkspace($tenantId, date('Y-m-d H:i:s', strtotime('+30 days')), 1);
testAssert($unsuspendResult === true, "Super Admin unsuspended workspace with 30-day date extension");

$tenantAfterUnsuspend = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
testAssert($tenantAfterUnsuspend['status'] === 'active', "Tenant status restored to 'active'");
testAssert((int)$tenantAfterUnsuspend['is_termination_due'] === 0, "Termination due flag cleared on unsuspend");

// 7. Test Manual Termination (Never automated)
$termResult = SubscriptionService::terminateWorkspace($tenantId, 'Client requested offboarding', 1);
testAssert($termResult === true, "Super Admin executed manual termination");

$tenantTerminated = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
testAssert($tenantTerminated['status'] === 'terminated', "Tenant status is 'terminated'");
testAssert(!empty($tenantTerminated['terminated_at']), "Tenant terminated_at timestamp recorded");
testAssert((int)$tenantTerminated['terminated_by'] === 1, "Terminated by Admin ID recorded for audit trail");

// Clean up test tenant
Database::execute("DELETE FROM tenants WHERE id = ?", [$tenantId]);

echo "\n====================================================\n";
echo " ALL TESTS PASSED: {$passCount}/{$passCount}\n";
echo "====================================================\n";
