<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class RateLimiterService
{
    /**
     * Check if an action is within allowed rate limits.
     * Records the request and returns true if allowed, false if limit exceeded.
     *
     * @param string $action Logical action name (e.g., 'api_v1', 'register', 'support_ticket', 'quiz_init')
     * @param string $identifier Unique identity for client (IP address, user ID, or API key)
     * @param int $maxAttempts Maximum allowed attempts in the time window
     * @param int $windowSeconds Length of sliding time window in seconds
     * @return bool True if permitted, False if rate-limited
     */
    public static function check(string $action, string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        try {
            $ip = substr($identifier, 0, 45);
            $bucketKey = 'rl:' . substr(hash('sha256', "{$action}:{$identifier}"), 0, 40);

            // Purge expired records for this bucket
            Database::execute(
                "DELETE FROM login_attempts WHERE email = ? AND attempted_at < DATE_SUB(NOW(), INTERVAL ? SECOND)",
                [$bucketKey, $windowSeconds]
            );

            // Count attempts within active window
            $countRow = Database::fetchOne(
                "SELECT COUNT(*) as attempts FROM login_attempts WHERE email = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)",
                [$bucketKey, $windowSeconds]
            );

            $attempts = (int)($countRow['attempts'] ?? 0);
            if ($attempts >= $maxAttempts) {
                Logger::warning("API Rate Limit exceeded: action={$action}, id={$identifier}, limit={$maxAttempts}/{$windowSeconds}s");
                return false;
            }

            // Record this request
            Database::execute(
                "INSERT INTO login_attempts (ip_address, email, attempted_at) VALUES (?, ?, NOW())",
                [$ip, $bucketKey]
            );

            return true;
        } catch (\Throwable $e) {
            Logger::error("RateLimiterService error: " . $e->getMessage());
            return true; // Fail-open to avoid service disruption if DB check encounters an issue
        }
    }
}
