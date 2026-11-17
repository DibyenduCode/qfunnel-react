<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Core\Logger;
use RuntimeException;

class AuthService
{
    public static function attempt(string $email, string $password): array
    {
        $email = trim(strtolower($email));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Rate Limiting Check (Max 5 failed attempts per 15-minute window per IP or email)
        $maxAttempts = 5;
        $lockoutMinutes = 15;

        try {
            // Clean up stale attempts older than 15 minutes
            Database::execute(
                "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
                [$lockoutMinutes]
            );

            $recentFails = Database::fetchOne(
                "SELECT COUNT(*) as fail_count FROM login_attempts WHERE (ip_address = ? OR email = ?) AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)",
                [$ip, $email, $lockoutMinutes]
            );

            $failCount = (int)($recentFails['fail_count'] ?? 0);
            if ($failCount >= $maxAttempts) {
                Logger::warning("Rate limit exceeded for login attempt: {$email} from IP {$ip}");
                return [
                    'success' => false,
                    'error' => "Too many failed login attempts. For security reasons, please wait {$lockoutMinutes} minutes before trying again."
                ];
            }
        } catch (\Throwable $e) {
            Logger::error("Rate limit check error: " . $e->getMessage());
        }

        $user = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

        if (!$user) {
            self::recordFailedLogin($ip, $email);
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Account is suspended or disabled.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            self::recordFailedLogin($ip, $email);
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        // Login success: clear failed attempts
        try {
            Database::execute("DELETE FROM login_attempts WHERE ip_address = ? OR email = ?", [$ip, $email]);
        } catch (\Throwable $e) {}

        // Login success: regenerate session
        Session::regenerate();

        // Update last login
        Database::execute("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);

        // Store user in session (excluding password hash)
        unset($user['password_hash']);
        Session::set('user', $user);

        // Auto-select tenant
        $tenants = TenantContextService::getUserTenants((int)$user['id']);
        if (!empty($tenants)) {
            TenantContextService::setActiveTenant((int)$tenants[0]['id'], (int)$user['id']);
        }

        Logger::info("User login successful: {$user['email']}");

        return ['success' => true, 'user' => $user];
    }

    private static function recordFailedLogin(string $ip, string $email): void
    {
        try {
            Database::execute(
                "INSERT INTO login_attempts (ip_address, email, attempted_at) VALUES (?, ?, NOW())",
                [$ip, $email]
            );
        } catch (\Throwable $e) {
            Logger::error("Failed to record login attempt: " . $e->getMessage());
        }
    }

    public static function register(string $name, string $email, string $password, string $workspaceName, bool $autoVerify = false): array
    {
        $name = trim($name);
        $email = trim(strtolower($email));
        $workspaceName = trim($workspaceName);

        if (empty($name) || empty($email) || empty($password) || empty($workspaceName)) {
            return ['success' => false, 'error' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address format.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
        }

        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'error' => 'An account with this email already exists.'];
        }

        // Generate tenant slug
        $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $workspaceName));
        $baseSlug = trim($baseSlug, '-');
        if (empty($baseSlug)) {
            $baseSlug = 'workspace';
        }

        $slug = $baseSlug;
        $counter = 1;
        while (Database::fetchOne("SELECT id FROM tenants WHERE slug = ?", [$slug])) {
            $slug = "{$baseSlug}-" . (++$counter);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        return Database::transaction(function () use ($name, $email, $passwordHash, $workspaceName, $slug, $autoVerify) {
            // 1. Create User
            $verifiedAt = $autoVerify ? date('Y-m-d H:i:s') : null;
            Database::execute(
                "INSERT INTO users (name, email, password_hash, is_platform_admin, status, email_verified_at) VALUES (?, ?, ?, 0, 'active', ?)",
                [$name, $email, $passwordHash, $verifiedAt]
            );
            $userId = (int) Database::lastInsertId();

            // 2. Create Tenant
            Database::execute(
                "INSERT INTO tenants (name, slug, status) VALUES (?, ?, ?)",
                [$workspaceName, $slug, 'active']
            );
            $tenantId = (int) Database::lastInsertId();

            // 3. Create Tenant Membership (Owner)
            Database::execute(
                "INSERT INTO tenant_memberships (tenant_id, user_id, role) VALUES (?, ?, 'owner')",
                [$tenantId, $userId]
            );

            // 4. Assign Starter Plan
            $starterPlan = Database::fetchOne("SELECT id FROM plans WHERE slug = 'starter'");
            if ($starterPlan) {
                Database::execute(
                    "INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, starts_at, ends_at) VALUES (?, ?, 'trial', NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY))",
                    [$tenantId, $starterPlan['id']]
                );
            }

            // Auto-login
            Session::regenerate();
            $user = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'is_platform_admin' => 0,
                'status' => 'active',
                'email_verified_at' => $verifiedAt,
            ];
            Session::set('user', $user);
            TenantContextService::setActiveTenant($tenantId, $userId);

            $otpData = null;
            if (!$autoVerify) {
                $otpData = EmailVerificationService::generateOtp($userId, $email, $name);
            }

            Logger::info("New tenant registered: {$workspaceName} ({$email})");

            return [
                'success' => true,
                'user' => $user,
                'tenant_id' => $tenantId,
                'requires_verification' => !$autoVerify,
                'otp_data' => $otpData,
            ];
        });
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
