<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');

use App\Core\Database;
use App\Core\Env;
use App\Services\LeadStageService;
use App\Services\SubmissionService;

\App\Core\Env::load(__DIR__ . '/../.env');

echo "=========================================\n";
echo " RUNNING CUSTOM LEAD STAGES TEST SUITE\n";
echo "=========================================\n";

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

// 1. Setup a test tenant
$testSlug = 'stage-test-' . bin2hex(random_bytes(3));
Database::execute(
    "INSERT INTO tenants (name, slug, status) VALUES (?, ?, 'active')",
    ['Test Stage Tenant', $testSlug]
);
$tenantId = (int)Database::lastInsertId();
testAssert($tenantId > 0, "Test tenant workspace created with ID #{$tenantId}");

// 2. Verify default stages auto-seed
$stages = LeadStageService::getStagesForTenant($tenantId);
testAssert(count($stages) === 5, "Expected 5 default stages to be seeded");

$slugs = array_column($stages, 'slug');
testAssert(in_array('new', $slugs), "Default stage 'new' exists");
testAssert(in_array('contacted', $slugs), "Default stage 'contacted' exists");
testAssert(in_array('qualified', $slugs), "Default stage 'qualified' exists");
testAssert(in_array('converted', $slugs), "Default stage 'converted' exists");
testAssert(in_array('junk', $slugs), "Default stage 'junk' exists");

// Verify stage emojis
testAssert(LeadStageService::getStageEmoji('new') === '🔵', "Stage 'new' renders 🔵");
testAssert(LeadStageService::getStageEmoji('contacted') === '🟡', "Stage 'contacted' renders 🟡");
testAssert(LeadStageService::getStageEmoji('qualified') === '🟢', "Stage 'qualified' renders 🟢");
testAssert(LeadStageService::getStageEmoji('converted') === '🟣', "Stage 'converted' renders 🟣");
testAssert(LeadStageService::getStageEmoji('junk') === '⚪', "Stage 'junk' renders ⚪");
testAssert(LeadStageService::getStageEmoji('custom', '#f97316') === '🟠', "Orange hex maps to 🟠");
testAssert(LeadStageService::getStageEmoji('custom', '#ef4444') === '🔴', "Red hex maps to 🔴");

// 3. Create custom stage with custom color
$customStage = LeadStageService::createStage($tenantId, 'Follow Up Needed', '#f97316');
testAssert($customStage !== null, "Custom stage creation should succeed");
testAssert($customStage['slug'] === 'follow-up-needed', "Slug normalized to follow-up-needed");
testAssert($customStage['color'] === '#f97316', "Color stored as #f97316");
testAssert((int)$customStage['is_system'] === 0, "Custom stage has is_system = 0");

// 4. Test duplicate stage name rejection
$duplicateThrown = false;
try {
    LeadStageService::createStage($tenantId, 'Follow Up Needed', '#3b82f6');
} catch (RuntimeException $e) {
    $duplicateThrown = true;
}
testAssert($duplicateThrown, "Duplicate stage name/slug throws clean RuntimeException");

// 5. Test system stage protection against deletion
$systemStage = $stages[0]; // 'new'
$systemDeleteBlocked = false;
try {
    LeadStageService::deleteStage($tenantId, (int)$systemStage['id']);
} catch (RuntimeException $e) {
    $systemDeleteBlocked = true;
}
testAssert($systemDeleteBlocked, "System default stages protected from deletion");

// 6. Create a test funnel and lead session with custom status
$funnelSlug = 'stage-funnel-' . bin2hex(random_bytes(3));
Database::execute(
    "INSERT INTO funnels (tenant_id, name, slug, status) VALUES (?, 'Stage Test Funnel', ?, 'published')",
    [$tenantId, $funnelSlug]
);
$funnelId = (int)Database::lastInsertId();

Database::execute(
    "INSERT INTO funnel_versions (tenant_id, funnel_id, version_number) VALUES (?, ?, 1)",
    [$tenantId, $funnelId]
);
$versionId = (int)Database::lastInsertId();

$tokenHash = hash('sha256', bin2hex(random_bytes(16)));
Database::execute(
    "INSERT INTO quiz_sessions (tenant_id, funnel_id, funnel_version_id, public_token_hash, status, lead_status, expires_at) 
     VALUES (?, ?, ?, ?, 'completed', 'new', DATE_ADD(NOW(), INTERVAL 1 DAY))",
    [$tenantId, $funnelId, $versionId, $tokenHash]
);
$sessionId = (int)Database::lastInsertId();
testAssert($sessionId > 0, "Test quiz session created with ID #{$sessionId}");

// 7. Update lead to custom stage
$updateResult = SubmissionService::updateLeadStatus($tenantId, $sessionId, $customStage['slug']);
testAssert($updateResult === true, "Updating lead status to custom stage 'follow-up-needed' succeeds");

$lead = Database::fetchOne("SELECT lead_status FROM quiz_sessions WHERE id = ?", [$sessionId]);
testAssert($lead['lead_status'] === 'follow-up-needed', "Lead record reflects custom lead_status");

// 8. Delete custom stage and verify lead safely reverts to 'new'
$deletedCustom = LeadStageService::deleteStage($tenantId, (int)$customStage['id']);
testAssert($deletedCustom === true, "Custom stage deleted successfully");

$leadAfterDelete = Database::fetchOne("SELECT lead_status FROM quiz_sessions WHERE id = ?", [$sessionId]);
testAssert($leadAfterDelete['lead_status'] === 'new', "Leads associated with deleted stage automatically reset to 'new'");

// Cleanup
Database::execute("DELETE FROM quiz_sessions WHERE tenant_id = ?", [$tenantId]);
Database::execute("DELETE FROM funnel_versions WHERE tenant_id = ?", [$tenantId]);
Database::execute("DELETE FROM funnels WHERE tenant_id = ?", [$tenantId]);
Database::execute("DELETE FROM tenant_lead_stages WHERE tenant_id = ?", [$tenantId]);
Database::execute("DELETE FROM tenants WHERE id = ?", [$tenantId]);

echo "\n=========================================\n";
echo " TEST SUMMARY: {$passCount} Passed, 0 Failed\n";
echo " ALL CUSTOM LEAD STAGE TESTS PASSED!\n";
echo "=========================================\n";
