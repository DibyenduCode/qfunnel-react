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
use App\Controllers\HomeController;
use App\Controllers\PlatformAdminController;

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
    // If no admin user, create or pick first user
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

// 4. Test HomeController::index includes active & visible plan
$homeCtrl = new HomeController();
$homeReq = new Request('GET', '/');
\App\Core\View::init(__DIR__ . '/../app/Views', $homeReq);

$res = $homeCtrl->index($homeReq);
$html = $res->getContent();

$assertCondition(str_contains($html, 'Test Visibility Plan'), "Home page includes visible test plan");
$assertCondition(str_contains($html, 'dynamic-plan-price'), "Home page contains dynamic-plan-price CSS selector");
$assertCondition(str_contains($html, 'data-monthly="₹1,999"'), "Home page prices contains calculated monthly data attribute");
$assertCondition(str_contains($html, 'data-yearly="₹1,599"'), "Home page prices contains calculated yearly discount data attribute");

// 5. Toggle visibility to 0 (Hide plan from frontend)
$adminCtrl = new PlatformAdminController();
$toggleReq = new Request('POST', "/admin/platform/plans/{$testPlan['id']}/toggle-visibility");
$adminCtrl->togglePlanVisibility($toggleReq, (string)$testPlan['id']);

$testPlanAfterHide = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$testPlan['id']]);
$assertCondition((int)$testPlanAfterHide['is_visible'] === 0, "Plan is_visible toggled to 0 in database");

// 6. Test HomeController::index now HIDES the plan
$resHidden = $homeCtrl->index($homeReq);
$htmlHidden = $resHidden->getContent();
$assertCondition(!str_contains($htmlHidden, 'Test Visibility Plan'), "Home page completely excludes hidden plan (is_visible = 0)");

// 7. Toggle visibility back to 1 (Show plan on frontend)
$adminCtrl->togglePlanVisibility($toggleReq, (string)$testPlan['id']);
$testPlanAfterShow = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$testPlan['id']]);
$assertCondition((int)$testPlanAfterShow['is_visible'] === 1, "Plan is_visible toggled back to 1 in database");

$resShown = $homeCtrl->index($homeReq);
$htmlShown = $resShown->getContent();
$assertCondition(str_contains($htmlShown, 'Test Visibility Plan'), "Home page displays plan again after making visible");

// 8. Test that inactive plans (is_active = 0) are NOT shown even if is_visible = 1
Database::execute("UPDATE plans SET is_active = 0 WHERE id = ?", [$testPlan['id']]);
$resInactive = $homeCtrl->index($homeReq);
$htmlInactive = $resInactive->getContent();
$assertCondition(!str_contains($htmlInactive, 'Test Visibility Plan'), "Inactive plan (is_active = 0) is hidden from home page even if is_visible = 1");

// 9. Clean up test plan
Database::execute("DELETE FROM plans WHERE id = ?", [$testPlan['id']]);
$assertCondition(true, "Test plan cleaned up");

echo "\n=========================================================\n";
echo "SUMMARY: {$passed} Passed, {$failed} Failed\n";
echo "=========================================================\n";

exit($failed === 0 ? 0 : 1);
