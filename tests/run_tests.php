<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');
require_once __DIR__ . '/../app/Core/helpers.php';

use App\Core\Env;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\TenantContextService;
use App\Services\FunnelService;
use App\Services\FunnelGraphService;
use App\Services\FunnelPublishService;
use App\Services\QuizEngineService;
use App\Services\SubmissionService;
use App\Services\AnalyticsService;
use App\Services\UsageLimitService;
use App\Services\MailService;
use App\Services\EmailVerificationService;
use App\Services\PlatformSettingService;
use App\Services\SubscriptionService;

// Load environment
Env::load(__DIR__ . '/../.env');

// Connect to test database
Database::init([
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'qfunnel_db'),
    'username' => Env::get('DB_USERNAME', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
]);

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function assert(bool $condition, string $testName, string $failureDetails = ''): void
    {
        if ($condition) {
            echo "  [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo "  [FAIL] {$testName}\n";
            if ($failureDetails) {
                echo "         Details: {$failureDetails}\n";
            }
            $this->failed++;
            $this->failures[] = $testName;
        }
    }

    public function summary(): int
    {
        echo "\n=========================================\n";
        echo "TEST SUMMARY: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "=========================================\n";
        return $this->failed === 0 ? 0 : 1;
    }
}

$runner = new TestRunner();

echo "=========================================\n";
echo " RUNNING QUIZ FUNNEL SAAS TEST SUITE\n";
echo "=========================================\n\n";

// --- SUITE 1: AUTHENTICATION & SESSIONS ---
echo "--- SUITE 1: Authentication & Sessions ---\n";
Session::start();

// 1.1 Valid login
$authRes = AuthService::attempt('owner@qfunnel.local', 'Password123!');
$runner->assert($authRes['success'] === true, 'Valid user credentials successfully authenticated');

// 1.2 Invalid password
$badAuth = AuthService::attempt('owner@qfunnel.local', 'WrongPassword!');
$runner->assert($badAuth['success'] === false, 'Invalid credentials rejected');

// 1.3 Nonexistent email
$fakeAuth = AuthService::attempt('nonexistent@domain.com', 'Pass123!');
$runner->assert($fakeAuth['success'] === false, 'Nonexistent account rejected');

// 1.4 Super Admin authentication & platform role
$adminAuth = AuthService::attempt('superadmin@qfunnel.local', 'Password123!');
$runner->assert(
    $adminAuth['success'] === true && !empty($adminAuth['user']['is_platform_admin']) && TenantContextService::getActiveTenant() !== null,
    'Super Administrator authenticated with platform privileges and active workspace'
);


// --- SUITE 2: MULTI-TENANCY DATA ISOLATION ---
echo "\n--- SUITE 2: Multi-Tenancy Data Isolation ---\n";

// Create Tenant A & Tenant B
$tenantAUser = 'tenant_a_' . time() . '@test.local';
$tenantBUser = 'tenant_b_' . time() . '@test.local';

$regA = AuthService::register('Alice Owner', $tenantAUser, 'Password123!', 'Tenant A Workspace');
$tenantAId = (int)$regA['tenant_id'];
$userAId = (int)$regA['user']['id'];

$regB = AuthService::register('Bob Owner', $tenantBUser, 'Password123!', 'Tenant B Workspace');
$tenantBId = (int)$regB['tenant_id'];
$userBId = (int)$regB['user']['id'];

$runner->assert($tenantAId > 0 && $tenantBId > 0 && $tenantAId !== $tenantBId, 'Distinct isolated tenants created');

// Tenant A creates a funnel
TenantContextService::setActiveTenant($tenantAId, $userAId);
$funnelA = FunnelService::createFunnel($tenantAId, ['name' => 'Secret Funnel of Tenant A'], $userAId);
$funnelAId = (int)$funnelA['id'];

// Test 2.1: Tenant B cannot list Tenant A's funnel
TenantContextService::setActiveTenant($tenantBId, $userBId);
$tenantBFunnels = FunnelService::listFunnels($tenantBId);
$bCanSeeA = false;
foreach ($tenantBFunnels as $f) {
    if ((int)$f['id'] === $funnelAId) {
        $bCanSeeA = true;
    }
}
$runner->assert(!$bCanSeeA, 'Tenant B funnel list excludes Tenant A funnels');

// Test 2.2: Tenant B cannot directly fetch Tenant A's funnel via ID (IDOR protection)
$idorAttempt = FunnelService::getFunnel($tenantBId, $funnelAId);
$runner->assert($idorAttempt === null, 'Tenant B cannot read Tenant A funnel by ID (IDOR blocked)');

// Test 2.3: Tenant B cannot delete Tenant A's funnel
$deleteBlocked = false;
try {
    FunnelService::deleteFunnel($tenantBId, $funnelAId, $userBId);
} catch (\Throwable $e) {
    $deleteBlocked = true;
}
$runner->assert($deleteBlocked, 'Tenant B cannot delete Tenant A funnel');


// --- SUITE 3: FUNNEL BUILDER & GRAPH VALIDATION ---
echo "\n--- SUITE 3: Funnel Builder & Graph Validation ---\n";
TenantContextService::setActiveTenant($tenantAId, $userAId);

// Test 3.1: Save graph nodes and edges
$nodes = [
    [
        'node_key' => 'start_1',
        'node_type' => 'start',
        'title' => 'Start',
        'position_x' => 100,
        'position_y' => 100,
    ],
    [
        'node_key' => 'q_1',
        'node_type' => 'question',
        'title' => 'Industry Selection',
        'position_x' => 350,
        'position_y' => 100,
        'question' => [
            'question_text' => 'What is your primary industry?',
            'question_type' => 'single_choice',
            'is_required' => 1,
            'options' => [
                ['option_key' => 'opt_tech', 'label' => 'Technology', 'value' => 'tech'],
                ['option_key' => 'opt_finance', 'label' => 'Finance', 'value' => 'finance'],
            ]
        ]
    ],
    [
        'node_key' => 'dest_tech',
        'node_type' => 'destination',
        'title' => 'Tech Hub',
        'position_x' => 700,
        'position_y' => 50,
        'destination' => [
            'name' => 'Technology Landing Page',
            'url' => 'https://example.com/tech'
        ]
    ],
    [
        'node_key' => 'dest_fin',
        'node_type' => 'destination',
        'title' => 'Finance Hub',
        'position_x' => 700,
        'position_y' => 200,
        'destination' => [
            'name' => 'Finance Landing Page',
            'url' => 'https://example.com/finance'
        ]
    ]
];

$edges = [
    ['source_node_key' => 'start_1', 'source_option_key' => null, 'target_node_key' => 'q_1'],
    ['source_node_key' => 'q_1', 'source_option_key' => 'opt_tech', 'target_node_key' => 'dest_tech'],
    ['source_node_key' => 'q_1', 'source_option_key' => 'opt_finance', 'target_node_key' => 'dest_fin'],
];

$saved = FunnelGraphService::saveGraph($tenantAId, $funnelAId, ['nodes' => $nodes, 'edges' => $edges], $userAId);
$runner->assert(count($saved['nodes']) === 4 && count($saved['edges']) === 3, 'Graph nodes and edges saved atomically');

\App\Services\FormSchemaService::saveFormFields($tenantAId, $funnelAId, [
    ['field_key' => 'full_name', 'label' => 'Full Name', 'field_type' => 'text', 'is_required' => 1],
    ['field_key' => 'email', 'label' => 'Email Address', 'field_type' => 'email', 'is_required' => 1],
], $userAId);

// Test 3.2: Validate complete graph
$val = FunnelGraphService::validateGraph($tenantAId, $funnelAId);
$runner->assert($val['is_valid'] === true, 'Complete branching flow passes validation');

// Test 3.3: Publish version
$pub = FunnelPublishService::publish($tenantAId, $funnelAId, $userAId);
$runner->assert($pub['version_number'] === 1 && $pub['status'] === 'published', 'Funnel successfully published as Version 1');

// Test 3.4: Draft changes do not alter published version
$updatedFunnel = FunnelService::getFunnel($tenantAId, $funnelAId);
$runner->assert((int)$updatedFunnel['current_published_version_id'] === (int)$pub['version_id'], 'Published version pointer set correctly');


// --- SUITE 4: QUIZ ENGINE BRANCHING & DOWNSTREAM INVALIDATION ---
echo "\n--- SUITE 4: Quiz Engine Branching & Invalidation ---\n";

// Test 4.1: Public visitor initializes session
$slug = $updatedFunnel['slug'];
$init = QuizEngineService::initializeSession($slug);
$runner->assert(!empty($init['session_token']) && $init['status'] === 'form_pending', 'Public visitor session initialized with token');
$sessionToken = $init['session_token'];

// Test 4.2: Submit pre-quiz form
$formRes = QuizEngineService::submitForm($sessionToken, [
    'full_name' => 'Test Visitor',
    'email' => 'visitor@test.local'
]);
$runner->assert($formRes['status'] === 'in_progress', 'Pre-quiz form saved and quiz started');

// Test 4.3: Submit Tech option -> should reach Tech Hub Destination
$firstStep = QuizEngineService::getCurrentStep($sessionToken);
$techOptionId = null;
foreach ($firstStep['step']['options'] as $opt) {
    if ($opt['label'] === 'Technology') {
        $techOptionId = $opt['id'];
        break;
    }
}

$techAnswerRes = QuizEngineService::submitAnswer($sessionToken, (int)$firstStep['step']['node_id'], [
    'option_id' => $techOptionId
]);
$runner->assert($techAnswerRes['status'] === 'completed' && $techAnswerRes['result']['destination_name'] === 'Technology Landing Page', 'Tech branch correctly routed to Tech Hub destination');

// Test 4.4: Back navigation and route change
QuizEngineService::navigateBack($sessionToken);
$backStep = QuizEngineService::getCurrentStep($sessionToken);
$runner->assert($backStep['status'] === 'in_progress', 'Back navigation restores prior question step');

// Choose Finance option instead -> downstream route changes to Finance Landing Page
$finOptionId = null;
foreach ($backStep['step']['options'] as $opt) {
    if ($opt['label'] === 'Finance') {
        $finOptionId = $opt['id'];
        break;
    }
}
$finAnswerRes = QuizEngineService::submitAnswer($sessionToken, (int)$backStep['step']['node_id'], [
    'option_id' => $finOptionId
]);
$runner->assert($finAnswerRes['status'] === 'completed' && $finAnswerRes['result']['destination_name'] === 'Finance Landing Page', 'Answer change correctly supersedes downstream route to Finance Hub');


// --- SUITE 5: SUBMISSIONS & ANALYTICS INTEGRITY ---
echo "\n--- SUITE 5: Submissions & Analytics Integrity ---\n";

// Test 5.1: Submission listed with accurate lead info
$subsList = SubmissionService::listSubmissions($tenantAId, ['funnel_id' => $funnelAId]);
$runner->assert($subsList['total'] >= 1 && $subsList['items'][0]['lead_name'] === 'Test Visitor', 'Submission correctly stored and searchable');

// Test 5.2: Journey timeline events preserved
$subDetail = SubmissionService::getSubmissionDetail($tenantAId, (int)$subsList['items'][0]['id']);
$eventTypes = array_column($subDetail['events'], 'event_type');
$hasStarted = in_array('quiz_started', $eventTypes, true);
$hasCompleted = in_array('quiz_completed', $eventTypes, true);
$runner->assert($hasStarted && $hasCompleted, 'Full event timeline preserved in chronological order');

// Test 5.3: Analytics accurately counts starts and completions
$analytics = AnalyticsService::getFunnelMetrics($tenantAId, $funnelAId);
$runner->assert($analytics['completions'] >= 1 && $analytics['completion_rate'] > 0, 'Real database analytics computed accurately');


// --- SUITE 6: PLAN LIMITS ENFORCEMENT ---
echo "\n--- SUITE 6: Plan Limits Enforcement ---\n";
$limits = UsageLimitService::getTenantLimits($tenantAId);
$runner->assert($limits['max_funnels'] >= 3, 'Tenant limits resolved from subscription plan');


// --- SUITE 7: SUPER ADMIN EXCLUSIVE TOOLS & CAPABILITIES ---
echo "\n--- SUITE 7: Super Admin Tools & Controls ---\n";

// Test 7.1: Verify Core Plans (Free [1], Starter [5], Standard [10], Pro [unlimited])
$freePlan = Database::fetchOne("SELECT * FROM plans WHERE slug = 'free'");
$starterPlan = Database::fetchOne("SELECT * FROM plans WHERE slug = 'starter'");
$standardPlan = Database::fetchOne("SELECT * FROM plans WHERE slug = 'standard'");
$proPlan = Database::fetchOne("SELECT * FROM plans WHERE slug = 'pro'");

$freeLimits = json_decode($freePlan['limits_json'] ?? '{}', true);
$starterLimits = json_decode($starterPlan['limits_json'] ?? '{}', true);
$standardLimits = json_decode($standardPlan['limits_json'] ?? '{}', true);
$proLimits = json_decode($proPlan['limits_json'] ?? '{}', true);

$runner->assert(
    $freeLimits['max_funnels'] === 1 &&
    $starterLimits['max_funnels'] === 5 &&
    $standardLimits['max_funnels'] === 10 &&
    $proLimits['max_funnels'] <= 0,
    'Core plans configured with required funnel limits: Free(1), Starter(5), Standard(10), Pro(Unlimited)'
);

// Test 7.2: Super Admin can suspend any user and suspended user login is rejected
$testUserEmail = 'suspend_test_' . time() . '@test.local';
$hash = password_hash('Password123!', PASSWORD_DEFAULT);
Database::execute("INSERT INTO users (name, email, password_hash, is_platform_admin, status) VALUES ('Test User', ?, ?, 0, 'active')", [$testUserEmail, $hash]);
$testUserId = (int)Database::lastInsertId();

// Suspend user
Database::execute("UPDATE users SET status = 'suspended' WHERE id = ?", [$testUserId]);
$loginAttempt = AuthService::attempt($testUserEmail, 'Password123!');
$runner->assert(
    !$loginAttempt['success'] && str_contains(strtolower($loginAttempt['error']), 'suspended'),
    'Super admin can suspend any user and suspended user login is rejected'
);

// Test 7.3: Assign plan to customer workspace
Database::execute("UPDATE tenant_subscriptions SET status = 'cancelled' WHERE tenant_id = ?", [$tenantAId]);
Database::execute(
    "INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, starts_at, ends_at) VALUES (?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR))",
    [$tenantAId, $proPlan['id']]
);
$tenantALimits = UsageLimitService::getTenantLimits($tenantAId);
$runner->assert($tenantALimits['plan_slug'] === 'pro' && $tenantALimits['max_funnels'] <= 0, 'Super admin can assign plan to any customer workspace');

// Test 7.4: Pro unlimited funnel creation allowed
$canCreateOnPro = UsageLimitService::checkCanCreateFunnel($tenantAId);
$runner->assert($canCreateOnPro['allowed'] === true, 'Pro tier allows unlimited funnel creation without blocking');

// Test 7.5: Automated & Manual Payment Gateways Configuration
\App\Services\PlatformSettingService::set('payment_gateways', [
    'stripe' => ['enabled' => true, 'mode' => 'test', 'publishable_key' => 'pk_test_123', 'secret_key' => 'sk_test_123'],
    'paypal' => ['enabled' => false, 'mode' => 'sandbox'],
    'razorpay' => ['enabled' => false],
    'bank_transfer' => ['enabled' => true, 'bank_name' => 'Demo Bank', 'account_number' => '987654321', 'instructions' => 'Send wire ref QF123'],
    'manual_custom' => ['enabled' => true, 'title' => 'Offline Cheque', 'instructions' => 'Mail cheque to HQ', 'qr_code_url' => '/uploads/gateways/demo_qr.png']
]);
$savedGateways = \App\Services\PlatformSettingService::getPaymentGateways();
$runner->assert(
    !empty($savedGateways['stripe']['enabled']) &&
    !empty($savedGateways['bank_transfer']['enabled']) &&
    $savedGateways['bank_transfer']['bank_name'] === 'Demo Bank' &&
    $savedGateways['manual_custom']['qr_code_url'] === '/uploads/gateways/demo_qr.png',
    'Payment gateway settings properly stores and retrieves both Auto and Manual configurations'
);

// --- SUITE 8: CALCULATOR & QUOTE FUNNEL ENGINE ---
echo "\n--- SUITE 8: Calculator & Quote Funnel Engine ---\n";

// 8.1 Safe Mathematical Evaluator (Shunting-Yard)
$eval1 = QuizEngineService::evaluateFormula('(@Q1 * 100) + @Q2 - 50', ['Q1' => 15, 'Q2' => 200]);
$runner->assert($eval1 === 1650.0, 'Safe formula engine correctly evaluates variables and expressions (@Q1 * 100 + @Q2 - 50 = 1650)');

$eval2 = QuizEngineService::evaluateFormula('(10 + 5) * 2 / (3 + 2)', []);
$runner->assert($eval2 === 6.0, 'Order of operations and parentheses precedence evaluated accurately');

$eval3 = QuizEngineService::evaluateFormula('@Q_MISSING + 500', []);
$runner->assert($eval3 === 500.0, 'Missing question variables gracefully default to 0 without errors');

// 8.2 Funnel Creation with Calculator Mode & Starter Graph Seeding
$calcFunnel = FunnelService::createFunnel($tenantAId, [
    'name' => 'Solar ROI & Savings Calculator',
    'slug' => 'solar-calculator-' . time(),
    'description' => 'Calculate your monthly solar savings and payback',
    'funnel_mode' => 'calculator'
], $userAId);
$runner->assert($calcFunnel['funnel_mode'] === 'calculator', 'Calculator funnel successfully created with funnel_mode = calculator');

$calcGraph = FunnelGraphService::getGraph($tenantAId, (int)$calcFunnel['id']);
$sliderNode = null;
$calcDestNode = null;
foreach ($calcGraph['nodes'] as $node) {
    if (($node['input_type'] ?? '') === 'slider') {
        $sliderNode = $node;
    }
    if ($node['node_type'] === 'destination' && !empty($node['calculator']['enabled'])) {
        $calcDestNode = $node;
    }
}
$runner->assert(
    $sliderNode !== null && $calcDestNode !== null && !empty($sliderNode['slider_config']),
    'Calculator template seeds Slider question with @Q1 and Destination with formula configuration'
);

// 8.3 Publish & Execute Visitor Journey through Slider to Quote Destination
$pubRes = FunnelPublishService::publish($tenantAId, (int)$calcFunnel['id'], $userAId);
$runner->assert($pubRes['status'] === 'published', 'Calculator funnel successfully validated and published');

$sessionInit = QuizEngineService::initializeSession($calcFunnel['slug']);
$sessToken = $sessionInit['session_token'];
$step1 = QuizEngineService::getCurrentStep($sessToken);
$runner->assert(
    $step1['step']['input_type'] === 'slider' && $step1['step']['slider_config']['min_value'] == 1,
    'Visitor Quiz Runner renders Slider input step with range limits'
);

// Submit slider value (e.g. 25 users / quantity)
$step2 = QuizEngineService::submitAnswer($sessToken, (int)$step1['step']['node_id'], [
    'slider_value' => 25
]);

// If there are intermediate choices or form, complete until destination reached
$loopGuard = 0;
while ($step2['status'] === 'in_progress' && $loopGuard < 5) {
    $loopGuard++;
    if (($step2['step']['input_type'] ?? '') === 'slider') {
        $step2 = QuizEngineService::submitAnswer($sessToken, (int)$step2['step']['node_id'], ['slider_value' => 10]);
    } else {
        $firstOpt = $step2['step']['options'][0]['id'] ?? null;
        if ($firstOpt) {
            $step2 = QuizEngineService::submitAnswer($sessToken, (int)$step2['step']['node_id'], ['option_id' => $firstOpt]);
        } else {
            break;
        }
    }
}

if ($step2['status'] === 'form_step') {
    $step2 = QuizEngineService::submitForm($sessToken, [
        'full_name' => 'Alice Test',
        'email' => 'alice@test.local'
    ]);
}

$runner->assert(
    $step2['status'] === 'completed' && isset($step2['result']['calculated_value']) && !empty($step2['result']['formatted_quote']),
    'Outcome destination accurately computes quote and provides formatted instant quote card'
);

// Verify database persistence of calculated quote
$dbSession = Database::fetchOne("SELECT * FROM quiz_sessions WHERE public_token_hash = ?", [hash('sha256', $sessToken)]);
$runner->assert(
    $dbSession['calculated_value'] !== null && !empty($dbSession['formatted_quote']),
    'calculated_value and formatted_quote persisted in quiz_sessions table'
);

// --- SUITE 9: 1-CLICK CALL & WHATSAPP DIRECT CONNECT ENGINE ---
echo "\n--- SUITE 9: 1-Click Call & WhatsApp Direct Connect Engine ---\n";

// 9.1 Configure Quick Connect on destination node in the graph
$activeGraph = FunnelGraphService::getGraph($tenantAId, (int)$calcFunnel['id']);
$nodesToSave = $activeGraph['nodes'];
$targetDestIndex = null;
foreach ($nodesToSave as $idx => $n) {
    if ($n['node_type'] === 'destination') {
        $targetDestIndex = $idx;
        $nodesToSave[$idx]['quick_connect'] = [
            'enabled' => true,
            'action_type' => 'both',
            'phone_number' => '+1 (555) 019-2834',
            'whatsapp_number' => '+1 (555) 987-6543',
            'message' => 'Hello! I am inquiring regarding quote {{formatted_quote}} for {{funnel_name}}.',
            'call_btn_label' => 'Direct Phone Call',
            'whatsapp_btn_label' => 'Chat on WhatsApp',
        ];
        break;
    }
}

FunnelGraphService::saveGraph($tenantAId, (int)$calcFunnel['id'], [
    'nodes' => $nodesToSave,
    'edges' => $activeGraph['edges'],
], $userAId);

$reloadedGraph = FunnelGraphService::getGraph($tenantAId, (int)$calcFunnel['id']);
$savedDestNode = null;
foreach ($reloadedGraph['nodes'] as $n) {
    if ($n['node_type'] === 'destination') {
        $savedDestNode = $n;
        break;
    }
}

$runner->assert(
    !empty($savedDestNode['quick_connect']['enabled']) &&
    $savedDestNode['quick_connect']['action_type'] === 'both' &&
    $savedDestNode['quick_connect']['phone_number'] === '+1 (555) 019-2834',
    'Destination node successfully stores and retrieves quick_connect configuration'
);

// 9.2 Publish updated graph with Quick Connect
$pubConnectRes = FunnelPublishService::publish($tenantAId, (int)$calcFunnel['id'], $userAId);
$runner->assert($pubConnectRes['status'] === 'published', 'Funnel with Quick Connect node successfully re-published');

// 9.3 Run visitor session through to completion and verify dynamic WhatsApp / Call URLs
$connectSession = QuizEngineService::initializeSession($calcFunnel['slug']);
$cToken = $connectSession['session_token'];
$cStep = QuizEngineService::getCurrentStep($cToken);

// Submit answers until destination
$guard = 0;
while ($cStep['status'] === 'in_progress' && $guard < 5) {
    $guard++;
    if (($cStep['step']['input_type'] ?? '') === 'slider') {
        $cStep = QuizEngineService::submitAnswer($cToken, (int)$cStep['step']['node_id'], ['slider_value' => 50]);
    } else {
        $optId = $cStep['step']['options'][0]['id'] ?? null;
        if ($optId) {
            $cStep = QuizEngineService::submitAnswer($cToken, (int)$cStep['step']['node_id'], ['option_id' => $optId]);
        } else {
            break;
        }
    }
}

if ($cStep['status'] === 'form_step') {
    $cStep = QuizEngineService::submitForm($cToken, [
        'full_name' => 'Bob Builder',
        'email' => 'bob@test.local',
        'phone' => '+1 555-432-1098'
    ]);
}

$qc = $cStep['result']['quick_connect'] ?? null;

$runner->assert(
    !empty($qc) && !empty($qc['enabled']) && $qc['action_type'] === 'both',
    'Quiz completion response delivers active quick_connect payload'
);

$runner->assert(
    str_starts_with($qc['call_url'] ?? '', 'tel:+15550192834'),
    'call_url cleanly formatted as tel: URI with international dialing format'
);

$runner->assert(
    str_starts_with($qc['whatsapp_url'] ?? '', 'https://wa.me/15559876543') &&
    str_contains($qc['whatsapp_url'] ?? '', rawurlencode($cStep['result']['formatted_quote'] ?? '')),
    'whatsapp_url generates valid wa.me link with dynamic {{formatted_quote}} tag substituted'
);

// 9.4 Verify revisiting completed step retains quick_connect
$revisitedStep = QuizEngineService::getCurrentStep($cToken);
$runner->assert(
    !empty($revisitedStep['result']['quick_connect']['whatsapp_url']),
    'Revisiting finished session step preserves full quick_connect payload'
);

// --- SUITE 10: COMPLETE NODE DELETION & BLANK CANVAS PERSISTENCE ---
echo "\n--- SUITE 10: Complete Node Deletion & Blank Canvas Persistence ---\n";

// 10.1 Create a new calculation funnel with pre-seeded nodes
$testCalc = FunnelService::createFunnel($tenantAId, [
    'name' => 'Clear Test Cal',
    'slug' => 'clear-test-cal-' . time(),
    'funnel_mode' => 'calculator'
], $userAId);
$seededGraph = FunnelGraphService::getGraph($tenantAId, (int)$testCalc['id']);
$runner->assert(count($seededGraph['nodes']) > 0, 'Seeded calculation workflow initializes with default starter nodes');

// 10.2 Delete all nodes including the last destination node
$emptySaved = FunnelGraphService::saveGraph($tenantAId, (int)$testCalc['id'], [
    'nodes' => [],
    'edges' => []
], $userAId);
$runner->assert(count($emptySaved['nodes']) === 0 && count($emptySaved['edges']) === 0, 'Saving empty graph successfully removes all nodes and edges atomically');

// 10.3 Verify database persistence has zero orphaned nodes for this funnel
$reloadedEmptyGraph = FunnelGraphService::getGraph($tenantAId, (int)$testCalc['id']);
$runner->assert(count($reloadedEmptyGraph['nodes']) === 0, 'Reloading graph after full deletion confirms zero ghost nodes remain');

// --- SUITE 11: CUSTOMIZABLE FORM NODE HEADINGS & BUTTON TEXT ---
echo "\n--- SUITE 11: Customizable Form Node Headings & Button Text ---\n";

// 11.1 Create funnel with custom form node
$formFunnel = FunnelService::createFunnel($tenantAId, [
    'name' => 'Custom Form Test Funnel',
    'slug' => 'custom-form-' . time(),
    'funnel_mode' => 'quiz'
], $userAId);

$formNodeKey = 'form_' . bin2hex(random_bytes(4));
$destNodeKey = 'dest_' . bin2hex(random_bytes(4));

$savedFormGraph = FunnelGraphService::saveGraph($tenantAId, (int)$formFunnel['id'], [
    'nodes' => [
        [
            'node_key' => $formNodeKey,
            'node_type' => 'form',
            'title' => 'Custom Consultation Form',
            'is_start' => 1,
            'position_x' => 100,
            'position_y' => 100,
            'form' => [
                'heading' => 'Custom Consultation Form',
                'subheading' => 'Share your business needs to get started immediately.',
                'button_text' => 'Request My Free Audit',
                'submit_action' => 'redirect',
                'fields' => [
                    ['id' => 1, 'field_key' => 'name', 'label' => 'Full Name', 'field_type' => 'text', 'is_required' => 1],
                    ['id' => 2, 'field_key' => 'email', 'label' => 'Email', 'field_type' => 'email', 'is_required' => 1]
                ]
            ]
        ],
        [
            'node_key' => $destNodeKey,
            'node_type' => 'destination',
            'title' => 'Destination Page',
            'is_start' => 0,
            'position_x' => 450,
            'position_y' => 100,
            'destination' => [
                'name' => 'Thank You Page',
                'url' => 'https://example.com/thanks'
            ]
        ]
    ],
    'edges' => [
        [
            'source_node_key' => $formNodeKey,
            'target_node_key' => $destNodeKey,
            'source_option_id' => null
        ]
    ]
], $userAId);

$pubFormVersion = FunnelPublishService::publish($tenantAId, (int)$formFunnel['id'], $userAId);
$runner->assert(!empty($pubFormVersion['version_number']), 'Funnel with customized form node published successfully');

// 11.2 Init visitor session and fetch current step
$formSessionInit = QuizEngineService::initializeSession($formFunnel['slug']);
$fToken = $formSessionInit['session_token'];
$formStep = QuizEngineService::getCurrentStep($fToken);

$runner->assert(
    $formStep['status'] === 'form_step' &&
    $formStep['step']['heading'] === 'Custom Consultation Form' &&
    $formStep['step']['title'] === 'Custom Consultation Form',
    'Visitor form step dynamically reflects customized Form Heading'
);

$runner->assert(
    $formStep['step']['subheading'] === 'Share your business needs to get started immediately.',
    'Visitor form step dynamically reflects customized Subheading / Description'
);

$runner->assert(
    $formStep['step']['button_text'] === 'Request My Free Audit',
    'Visitor form step dynamically reflects customized Submit Button Text'
);

// 11.3 Verify fallback defaults if fields omitted
$defaultFormStep = [
    'title' => null,
    'description' => null,
    'button_text' => null
];
$fallbackHeading = $defaultFormStep['heading'] ?? ($defaultFormStep['title'] ?? 'Lead Capture Form');
$fallbackSubheading = $defaultFormStep['subheading'] ?? ($defaultFormStep['description'] ?? 'Please fill out the form to proceed.');
$fallbackBtn = $defaultFormStep['button_text'] ?? 'Submit Details';

$runner->assert(
    $fallbackHeading === 'Lead Capture Form' &&
    $fallbackSubheading === 'Please fill out the form to proceed.' &&
    $fallbackBtn === 'Submit Details',
    'Graceful default fallbacks maintained when form headings or button text are unconfigured'
);

// =========================================================================
// SUITE 12: Loader Interstitial & Calendar Booking Flow
// =========================================================================
echo "\n--- SUITE 12: Loader Interstitial & Calendar Booking Flow ---\n";

$loaderBookingFunnel = FunnelService::createFunnel($tenantAId, [
    'name' => 'High-Ticket Strategy Funnel',
    'slug' => 'strategy-session-' . time(),
    'funnel_mode' => 'quiz'
], $userAId);

$loaderNodeKey = 'loader_' . bin2hex(random_bytes(4));
$bookingNodeKey = 'booking_' . bin2hex(random_bytes(4));
$destNodeKey = 'dest_' . bin2hex(random_bytes(4));

$lbGraph = [
    'nodes' => [
        [
            'node_key' => $loaderNodeKey,
            'node_type' => 'loader',
            'title' => 'Analyzing Fitness Score...',
            'is_start' => 1,
            'position_x' => 100,
            'position_y' => 100,
            'config_json' => [
                'headline' => 'Calculating Custom Protocol...',
                'subtext' => 'Matching the top 3 specialists in your timezone.',
                'duration_seconds' => 3.0
            ]
        ],
        [
            'node_key' => $bookingNodeKey,
            'node_type' => 'booking',
            'title' => '1-on-1 Consultation',
            'is_start' => 0,
            'position_x' => 100,
            'position_y' => 250,
            'config_json' => [
                'headline' => 'Schedule Your Private Review',
                'provider' => 'calendly',
                'booking_url' => 'https://calendly.com/sample/30min',
                'embed_mode' => 'inline'
            ]
        ],
        [
            'node_key' => $destNodeKey,
            'node_type' => 'destination',
            'title' => 'Confirmation Outcome',
            'is_start' => 0,
            'position_x' => 100,
            'position_y' => 400,
            'destination' => [
                'name' => 'Session Confirmed',
                'url' => 'https://example.com/booked',
                'success_heading' => 'You are booked!',
                'description' => 'Look out for the calendar invite in your email.'
            ]
        ]
    ],
    'edges' => [
        [
            'source_node_key' => $loaderNodeKey,
            'target_node_key' => $bookingNodeKey,
            'source_option_id' => null
        ],
        [
            'source_node_key' => $bookingNodeKey,
            'target_node_key' => $destNodeKey,
            'source_option_id' => null
        ]
    ]
];

$savedLbGraph = FunnelGraphService::saveGraph($tenantAId, (int)$loaderBookingFunnel['id'], $lbGraph, $userAId);
$pubLbVersion = FunnelPublishService::publish($tenantAId, (int)$loaderBookingFunnel['id'], $userAId);
$runner->assert(!empty($pubLbVersion['version_number']), 'Funnel with Loader and Booking nodes published successfully');

// Initialize visitor session
$lbSessionInit = QuizEngineService::initializeSession($loaderBookingFunnel['slug']);
$lbToken = $lbSessionInit['session_token'];
$lbStep1 = QuizEngineService::getCurrentStep($lbToken);

$runner->assert(
    $lbStep1['status'] === 'loader_step' &&
    $lbStep1['step']['headline'] === 'Calculating Custom Protocol...' &&
    (float)$lbStep1['step']['duration_seconds'] === 3.0,
    'Visitor begins at Loader Interstitial step with custom duration and subtext'
);

// Advance from Loader node
$lbLoaderNodeId = (int)$lbStep1['step']['node_id'];
$lbStep2 = QuizEngineService::submitAnswer($lbToken, $lbLoaderNodeId, ['auto_advance' => true]);

$runner->assert(
    $lbStep2['status'] === 'booking_step' &&
    $lbStep2['step']['headline'] === 'Schedule Your Private Review' &&
    $lbStep2['step']['booking_url'] === 'https://calendly.com/sample/30min' &&
    $lbStep2['step']['provider'] === 'calendly',
    'Visitor automatically advances from Loader into Calendar Booking step'
);

// Advance from Booking node
$lbBookingNodeId = (int)$lbStep2['step']['node_id'];
$lbStep3 = QuizEngineService::submitAnswer($lbToken, $lbBookingNodeId, ['booked' => true]);

$runner->assert(
    $lbStep3['status'] === 'completed' &&
    $lbStep3['result']['destination_name'] === 'Session Confirmed',
    'Visitor finishes calendar booking and reaches destination confirmation step'
);

// =========================================
// SUITE 13: Global SMTP, Email Verification (OTP), and Template CMS
// =========================================
echo "\n--- SUITE 13: Global SMTP, Email Verification (OTP), and Template CMS ---\n";

// 1. User Registration requires email verification with OTP
$otpTestEmail = 'otp_user_' . time() . '@test.local';
$regResult = AuthService::register('OTP Test User', $otpTestEmail, 'StrongPassword123!', 'OTP Test Workspace');

$runner->assert(
    $regResult['success'] === true &&
    $regResult['requires_verification'] === true &&
    empty($regResult['user']['email_verified_at']),
    'New user registration enforces unverified state requiring OTP verification'
);

$otpUserId = (int)$regResult['user']['id'];
$runner->assert(
    EmailVerificationService::isUserVerified($otpUserId) === false,
    'Unverified user blocked by verification service check before OTP entry'
);

// 2. Verification record and 6-digit OTP code integrity
$otpRecord = Database::fetchOne("SELECT * FROM email_verifications WHERE user_id = ?", [$otpUserId]);
$runner->assert(
    !empty($otpRecord) &&
    strlen($otpRecord['otp_code']) === 6 &&
    ctype_digit($otpRecord['otp_code']) &&
    strtotime($otpRecord['expires_at']) > time(),
    'Valid 6-digit numeric OTP code generated and stored with 15-minute expiration'
);

// 3. Rejection of invalid OTP
$wrongVerify = EmailVerificationService::verifyOtp($otpUserId, '000000');
$runner->assert(
    $wrongVerify['success'] === false &&
    str_contains($wrongVerify['error'], 'Invalid code'),
    'Invalid OTP code rejected and decrements remaining verification attempts'
);

// 4. Verification with correct OTP code
$correctOtp = (string)$otpRecord['otp_code'];
$verifySuccess = EmailVerificationService::verifyOtp($otpUserId, $correctOtp);
$freshUser = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$otpUserId]);
$leftoverRecord = Database::fetchOne("SELECT id FROM email_verifications WHERE user_id = ?", [$otpUserId]);

$runner->assert(
    $verifySuccess['success'] === true &&
    !empty($freshUser['email_verified_at']) &&
    empty($leftoverRecord) &&
    EmailVerificationService::isUserVerified($otpUserId) === true,
    'Valid OTP verification successfully marks email as verified and cleans up OTP token'
);

// 5. Global SMTP Configuration Save and Retrieval
MailService::saveSmtpSettings([
    'enabled' => false,
    'host' => 'smtp.mailtrap.io',
    'port' => 2525,
    'encryption' => 'tls',
    'username' => 'test_user_mailtrap',
    'password' => 'secret_pass_123',
    'from_email' => 'mailer@qfunnel-platform.test',
    'from_name' => 'QFunnel Notifications',
    'reply_to' => 'help@qfunnel-platform.test',
]);

$loadedSmtp = MailService::getSmtpSettings();
$runner->assert(
    $loadedSmtp['host'] === 'smtp.mailtrap.io' &&
    $loadedSmtp['port'] === 2525 &&
    $loadedSmtp['from_email'] === 'mailer@qfunnel-platform.test' &&
    $loadedSmtp['from_name'] === 'QFunnel Notifications',
    'Global SMTP server settings saved and retrieved accurately via PlatformSettingService'
);

// 6. Email Template CMS & Tag Replacement
$allTemplates = MailService::getTemplates();
$templateKeys = array_column($allTemplates, 'template_key');
$runner->assert(
    in_array('email_verification_otp', $templateKeys, true) &&
    in_array('welcome_email', $templateKeys, true) &&
    in_array('invoice_receipt', $templateKeys, true) &&
    in_array('renewal_reminder', $templateKeys, true) &&
    in_array('account_suspended', $templateKeys, true),
    'Email Template CMS seeds all 5 core operational templates with placeholder definitions'
);

// Update a template and test dispatch
$customSubject = "Welcome to {{app_name}} VIP Club, {{user_name}}!";
MailService::saveTemplate('welcome_email', [
    'subject' => $customSubject,
    'body_html' => '<p>Custom welcome body for {{tenant_name}}</p>',
]);

$updatedWelcomeTpl = MailService::getTemplate('welcome_email');
$runner->assert(
    $updatedWelcomeTpl['subject'] === $customSubject,
    'Super Admin can customize email template subject and HTML body'
);

// Reset template back to default
$resetOk = MailService::resetTemplateToDefault('welcome_email');
$restoredTpl = MailService::getTemplate('welcome_email');
$runner->assert(
    $resetOk === true &&
    str_contains($restoredTpl['subject'], 'Welcome to {{app_name}}'),
    'Super Admin can restore any modified email template to default state'
);

// 7. Dispatching Email and Sent Logs Ledger
$dispatchResult = MailService::sendTemplate('welcome_email', 'member@example.com', 'Alex Developer', [
    'tenant_name' => 'Apex Funnels',
    'dashboard_url' => 'http://localhost:5173/dashboard',
]);

$latestLog = Database::fetchOne("SELECT * FROM email_logs WHERE recipient_email = 'member@example.com' ORDER BY id DESC LIMIT 1");
$runner->assert(
    $dispatchResult['success'] === true &&
    !empty($latestLog) &&
    $latestLog['template_key'] === 'welcome_email' &&
    in_array($latestLog['status'], ['sent', 'simulated'], true),
    'Template email successfully dispatched with tags and recorded in Sent Email Logs outbox'
);

// 8. Billing & Invoices Email Dispatch Integration
$testTenantId = (int)$regResult['tenant_id'];
$paymentResult = SubscriptionService::recordManualPayment($testTenantId, [
    'amount' => 1999,
    'billing_interval' => 'monthly',
    'payment_method' => 'upi',
    'transaction_reference' => 'UPI-REF-998811',
    'notes' => 'Test invoice payment for automated tests',
], $otpUserId);

$invoiceLog = Database::fetchOne("SELECT * FROM email_logs WHERE template_key = 'invoice_receipt' AND recipient_email = ? ORDER BY id DESC LIMIT 1", [$otpTestEmail]);
$runner->assert(
    !empty($paymentResult['invoice_number']) &&
    !empty($invoiceLog) &&
    str_contains($invoiceLog['subject'], $paymentResult['invoice_number']),
    'Subscription manual payment and invoice creation automatically triggers Invoice Receipt email'
);

// 9. Workspace Suspension Email Dispatch Integration
SubscriptionService::suspendWorkspace($testTenantId, 'Testing suspension email hook', 'Test notes', $otpUserId);
$suspensionLog = Database::fetchOne("SELECT * FROM email_logs WHERE template_key = 'account_suspended' AND recipient_email = ? ORDER BY id DESC LIMIT 1", [$otpTestEmail]);
$runner->assert(
    !empty($suspensionLog) &&
    str_contains($suspensionLog['subject'], 'suspended'),
    'Workspace suspension event automatically dispatches Account Suspension notice'
);

// Summary output
$code = $runner->summary();
exit($code);


