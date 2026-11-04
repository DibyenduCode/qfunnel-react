<?php

namespace App\Services;

class AuthorizationService
{
    public static function isPlatformAdmin(?array $user = null): bool
    {
        if ($user === null) {
            $user = current_user();
        }
        return !empty($user['is_platform_admin']);
    }

    public static function canManageWorkspace(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return $role === 'owner' || self::isPlatformAdmin();
    }

    public static function canManageTeam(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return $role === 'owner' || self::isPlatformAdmin();
    }

    public static function canCreateFunnel(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return in_array($role, ['owner', 'admin', 'editor'], true) || self::isPlatformAdmin();
    }

    public static function canEditFunnel(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return in_array($role, ['owner', 'admin', 'editor'], true) || self::isPlatformAdmin();
    }

    public static function canPublishFunnel(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return in_array($role, ['owner', 'admin'], true) || self::isPlatformAdmin();
    }

    public static function canDeleteFunnel(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return in_array($role, ['owner', 'admin'], true) || self::isPlatformAdmin();
    }

    public static function canViewSubmissions(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return in_array($role, ['owner', 'admin', 'editor', 'analyst'], true) || self::isPlatformAdmin();
    }

    public static function canExportSubmissions(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return in_array($role, ['owner', 'admin'], true) || self::isPlatformAdmin();
    }

    public static function canViewAnalytics(?string $role = null): bool
    {
        $role = $role ?? TenantContextService::getCurrentRole();
        return in_array($role, ['owner', 'admin', 'editor', 'analyst'], true) || self::isPlatformAdmin();
    }
}
