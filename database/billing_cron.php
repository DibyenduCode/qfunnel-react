<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');

use App\Core\Env;
use App\Core\Logger;
use App\Services\SubscriptionService;

// Load .env
\App\Core\Env::load(__DIR__ . '/../.env');

echo "=== QuizFunnel SaaS Billing & Subscription Cycle Evaluator ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $result = SubscriptionService::runBillingCycleEvaluation();
    
    echo "[✓] Evaluation Complete:\n";
    echo "  - Workspaces suspended past grace period: " . (int)$result['suspended_count'] . "\n";
    echo "  - Workspaces flagged for Termination Due queue: " . (int)$result['flagged_due_count'] . "\n";
    echo "  - Note: System policy strictly enforces manual termination by Super Admin (0 auto-terminations).\n\n";
} catch (\Throwable $e) {
    echo "[✗] Fatal Error during billing evaluation: " . $e->getMessage() . "\n";
    exit(1);
}
