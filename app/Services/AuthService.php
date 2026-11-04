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
        $user = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Account is suspended or disabled.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

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

    public static function register(string $name, string $email, string $password, string $workspaceName): array
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

        return Database::transaction(function () use ($name, $email, $passwordHash, $workspaceName, $slug) {
            // 1. Create User
            Database::execute(
                "INSERT INTO users (name, email, password_hash, is_platform_admin, status) VALUES (?, ?, ?, 0, 'active')",
                [$name, $email, $passwordHash]
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
            ];
            Session::set('user', $user);
            TenantContextService::setActiveTenant($tenantId, $userId);

            Logger::info("New tenant registered: {$workspaceName} ({$email})");

            return ['success' => true, 'user' => $user, 'tenant_id' => $tenantId];
        });
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
