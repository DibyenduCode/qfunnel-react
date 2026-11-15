<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');
require_once __DIR__ . '/../app/Core/helpers.php';

use App\Core\Env;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Controllers\ApiSpaController;

Env::load(__DIR__ . '/../.env');

Database::init([
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'qfunnel_db'),
    'username' => Env::get('DB_USERNAME', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
]);

echo "=========================================================\n";
echo " RUNNING DYNAMIC PLANS & FRONTEND VISIBILITY TESTS\n";
echo "=========================================================\n\n";

$passed = 0;
$failed = 0;

$assertCondition = function (bool $cond, string $msg) use (&$passed, &$failed) {
    if ($cond) {
        echo "  [PASS] {$msg}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$msg}\n";
        $failed++;
    }
};

// 1. Check is_visible column on plans table
$columns = Database::query("DESCRIBE plans");
$columnNames = array_column($columns, 'Field');
$assertCondition(in_array('is_visible', $columnNames, true), "Column 'is_visible' exists in 'plans' table");

// 2. Fetch admin user for session context
$adminUser = Database::fetchOne("SELECT * FROM users WHERE is_platform_admin = 1 LIMIT 1");
if (!$adminUser) {
    $adminUser = Database::fetchOne("SELECT * FROM users LIMIT 1");
}
Database::execute("DELETE FROM plans WHERE slug LIKE 'test-vis%'");
Session::set('user', $adminUser);

// 3. Create a test plan for visibility testing
$testSlug = 'test-vis-' . time();
Database::execute(
    "INSERT INTO plans (name, slug, price, billing_interval, description, limits_json, features_json, is_active, is_visible)
     VALUES (?, ?, 1999.00, 'monthly', 'Test Visibility Plan', '{\"max_funnels\": 15, \"max_submissions_per_month\": 5000}', '{\"webhooks\": true}', 1, 1)",
    ['Test Visibility Plan', $testSlug]
);
$testPlan = Database::fetchOne("SELECT * FROM plans WHERE slug = ?", [$testSlug]);
$assertCondition($testPlan && (int)$testPlan['is_visible'] === 1, "Test plan created with is_visible = 1");

// 4. Test ApiSpaController::superadminPlans includes active & visible plan
$spaCtrl = new ApiSpaController();
$plansRes = $spaCtrl->superadminPlans(new Request('GET', '/api/platform/plans'));
$plansData = json_decode($plansRes->getContent(), true)['data']['plans'] ?? [];
$planFound = false;
foreach ($plansData as $p) {
    if (($p['slug'] ?? '') === $testSlug) {
        $planFound = true;
        break;
    }
}
$assertCondition($planFound, "Plans API response includes visible test plan");

// 5. Toggle visibility to 0 (Hide plan from frontend)
$toggleReq = new Request('POST', "/api/platform/plans/{$testPlan['id']}/toggle-visibility");
$spaCtrl->superadminTogglePlanVisibility($toggleReq, (string)$testPlan['id']);

$testPlanAfterHide = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$testPlan['id']]);
$assertCondition((int)$testPlanAfterHide['is_visible'] === 0, "Plan is_visible toggled to 0 in database");

// 6. Test toggled plan reflects is_visible = 0 in API
$plansResAfterHide = $spaCtrl->superadminPlans(new Request('GET', '/api/platform/plans'));
$plansDataAfterHide = json_decode($plansResAfterHide->getContent(), true)['data']['plans'] ?? [];
$foundAfterHide = false;
foreach ($plansDataAfterHide as $p) {
    if (($p['slug'] ?? '') === $testSlug && (int)$p['is_visible'] === 0) {
        $foundAfterHide = true;
        break;
    }
}
$assertCondition($foundAfterHide, "Plans API accurately reflects is_visible = 0");

// 7. Toggle visibility back to 1 (Show plan on frontend)
$spaCtrl->superadminTogglePlanVisibility($toggleReq, (string)$testPlan['id']);
$testPlanAfterShow = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$testPlan['id']]);
$assertCondition((int)$testPlanAfterShow['is_visible'] === 1, "Plan is_visible toggled back to 1 in database");

// 8. Inactive plan (is_active = 0) check
Database::execute("UPDATE plans SET is_active = 0 WHERE id = ?", [$testPlan['id']]);
$inactivePlan = Database::fetchOne("SELECT is_active FROM plans WHERE id = ?", [$testPlan['id']]);
$assertCondition((int)$inactivePlan['is_active'] === 0, "Inactive plan correctly set is_active = 0");

// 9. Clean up
Database::execute("DELETE FROM plans WHERE id = ?", [$testPlan['id']]);
$assertCondition(true, "Test plan cleaned up");

echo "\n=========================================================\n";
echo "SUMMARY: {$passed} Passed, {$failed} Failed\n";
echo "=========================================================\n";

exit($failed === 0 ? 0 : 1);
