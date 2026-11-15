<?php
/**
 * LeadCollector SaaS - Automated Billing & Subscription Cron Runner
 *
 * Can be run via CLI / Server Crontab:
 *   php /path/to/leadcollector/cron.php
 *
 * Or via cPanel Cron Jobs / External Web Cron (curl/wget):
 *   curl -s "https://leadcollector.in/api/cron/billing?key=YOUR_KEY"
 */

require_once __DIR__ . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/app');

use App\Core\Env;
use App\Core\Logger;
use App\Services\SubscriptionService;
use App\Services\PlatformSettingService;

\App\Core\Env::load(__DIR__ . '/.env');

$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));

if (!$isCli) {
    // If accessed via browser or HTTP curl, require secret key
    $configuredKey = PlatformSettingService::get('cron_secret_key', 'leadcollector_cron_secret');
    $providedKey = $_GET['key'] ?? ($_POST['key'] ?? '');

    if (!empty($configuredKey) && $providedKey !== $configuredKey) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Forbidden: Invalid or missing cron security key.']);
        exit;
    }
}

try {
    $result = SubscriptionService::runBillingCycleEvaluation();

    if ($isCli) {
        echo "====================================================\n";
        echo " LeadCollector SaaS Billing & Cycle Evaluation\n";
        echo "====================================================\n";
        echo "Timestamp: " . ($result['evaluated_at'] ?? date('Y-m-d H:i:s')) . "\n";
        echo "Workspaces Suspended (Past Grace): " . (int)($result['suspended_count'] ?? 0) . "\n";
        echo "Flagged for Termination Due Queue: " . (int)($result['flagged_due_count'] ?? 0) . "\n";
        echo "Renewal Reminders Dispatched:      " . (int)($result['renewal_reminders_sent'] ?? 0) . "\n";
        echo "Status: SUCCESS\n\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Billing cycle evaluation executed successfully.',
            'data' => $result,
        ]);
    }
} catch (\Throwable $e) {
    Logger::error("Cron evaluation failed: " . $e->getMessage());

    if ($isCli) {
        echo "[ERROR] Billing evaluation failed: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
