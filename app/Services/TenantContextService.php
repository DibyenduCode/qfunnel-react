<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

class TenantContextService
{
    private static ?array $activeTenant = null;

    public static function getActiveTenant(): ?array
    {
        if (self::$activeTenant !== null) {
            return self::$activeTenant;
        }

        $tenantId = Session::get('tenant_id');
        if (!$tenantId) {
            return null;
        }

        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ? AND status != 'deleted'", [$tenantId]);
        if ($tenant) {
            self::$activeTenant = $tenant;
        }

        return self::$activeTenant;
    }

    public static function getActiveTenantId(): ?int
    {
        $tenant = self::getActiveTenant();
        return $tenant ? (int)$tenant['id'] : null;
    }

    public static function setActiveTenant(int $tenantId, int $userId): bool
    {
        $user = Database::fetchOne("SELECT is_platform_admin FROM users WHERE id = ?", [$userId]);
        $isPlatformAdmin = $user && !empty($user['is_platform_admin']);

        if ($isPlatformAdmin) {
            $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ? AND status != 'deleted'", [$tenantId]);
            if (!$tenant) {
                return false;
            }
            $membership = [
                'tenant_id' => $tenant['id'],
                'role' => 'owner',
                'tenant_name' => $tenant['name'],
                'tenant_slug' => $tenant['slug'],
                'tenant_status' => $tenant['status'],
            ];
        } else {
            $membership = Database::fetchOne(
                "SELECT tm.*, t.name as tenant_name, t.slug as tenant_slug, t.status as tenant_status 
                 FROM tenant_memberships tm 
                 JOIN tenants t ON tm.tenant_id = t.id 
                 WHERE tm.tenant_id = ? AND tm.user_id = ? AND t.status != 'deleted'",
                [$tenantId, $userId]
            );
        }

        if (!$membership) {
            return false;
        }

        Session::set('tenant_id', $tenantId);
        Session::set('tenant_role', $membership['role']);
        Session::set('tenant_name', $membership['tenant_name']);
        self::$activeTenant = [
            'id' => (int)$membership['tenant_id'],
            'name' => $membership['tenant_name'],
            'slug' => $membership['tenant_slug'],
            'status' => $membership['tenant_status'],
            'role' => $membership['role'],
        ];

        return true;
    }

    public static function getCurrentRole(): ?string
    {
        return Session::get('tenant_role');
    }

    public static function getUserTenants(int $userId): array
    {
        $user = Database::fetchOne("SELECT is_platform_admin FROM users WHERE id = ?", [$userId]);
        if ($user && !empty($user['is_platform_admin'])) {
            return Database::query(
                "SELECT t.id, t.name, t.slug, t.status, 'owner' as role, t.created_at as joined_at 
                 FROM tenants t 
                 WHERE t.status != 'deleted' 
                 ORDER BY t.name ASC"
            );
        }

        return Database::query(
            "SELECT t.id, t.name, t.slug, t.status, tm.role, tm.created_at as joined_at 
             FROM tenant_memberships tm 
             JOIN tenants t ON tm.tenant_id = t.id 
             WHERE tm.user_id = ? AND t.status != 'deleted' 
             ORDER BY t.name ASC",
            [$userId]
        );
    }
}
