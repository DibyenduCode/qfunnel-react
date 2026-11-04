<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Services\TenantContextService;
use App\Services\AuthorizationService;
use App\Services\UsageLimitService;
use App\Services\AuditLogService;

class SettingsController
{
    public function workspace(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $tenant = TenantContextService::getActiveTenant();

        $members = Database::query(
            "SELECT tm.id as membership_id, tm.role, tm.created_at as joined_at, u.id as user_id, u.name, u.email, u.last_login_at 
             FROM tenant_memberships tm
             JOIN users u ON tm.user_id = u.id
             WHERE tm.tenant_id = ?
             ORDER BY tm.created_at ASC",
            [$tenantId]
        );

        $usage = UsageLimitService::getUsageOverview($tenantId);
        $hasApiAccess = UsageLimitService::hasFeature($tenantId, 'api_access') || AuthorizationService::isPlatformAdmin();

        return view('settings.workspace', [
            'title' => 'Workspace Settings — Quiz Funnel SaaS',
            'tenant' => $tenant,
            'members' => $members,
            'usage' => $usage,
            'hasApiAccess' => $hasApiAccess,
        ]);
    }

    public function updateWorkspace(Request $request): Response
    {
        if (!AuthorizationService::canManageWorkspace()) {
            Session::setFlash('error', 'Only workspace owners can update workspace settings.');
            return Response::redirect(url('/admin/settings/workspace'));
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $name = trim($request->input('name', ''));

        if (!empty($name)) {
            Database::execute("UPDATE tenants SET name = ? WHERE id = ?", [$name, $tenantId]);
            Session::set('tenant_name', $name);
            Session::setFlash('success', 'Workspace updated successfully.');
        }

        return Response::redirect(url('/admin/settings/workspace'));
    }

    public function inviteMember(Request $request): Response
    {
        if (!AuthorizationService::canManageTeam()) {
            Session::setFlash('error', 'Permission denied.');
            return Response::redirect(url('/admin/settings/workspace'));
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $name = trim($request->input('name', ''));
        $email = trim(strtolower($request->input('email', '')));
        $role = $request->input('role', 'editor');
        $password = $request->input('password', 'Password123!');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Valid email required.');
            return Response::redirect(url('/admin/settings/workspace'));
        }

        if (!in_array($role, ['admin', 'editor', 'analyst'], true)) {
            $role = 'editor';
        }

        // Check if user exists
        $user = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if (!$user) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            Database::execute(
                "INSERT INTO users (name, email, password_hash, is_platform_admin, status) VALUES (?, ?, ?, 0, 'active')",
                [$name ?: 'Team Member', $email, $passwordHash]
            );
            $userId = (int) Database::lastInsertId();
        } else {
            $userId = (int)$user['id'];
        }

        // Add membership
        $existing = Database::fetchOne("SELECT id FROM tenant_memberships WHERE tenant_id = ? AND user_id = ?", [$tenantId, $userId]);
        if ($existing) {
            Database::execute("UPDATE tenant_memberships SET role = ? WHERE id = ?", [$role, $existing['id']]);
            Session::setFlash('success', "Member role updated to {$role}.");
        } else {
            Database::execute("INSERT INTO tenant_memberships (tenant_id, user_id, role) VALUES (?, ?, ?)", [$tenantId, $userId, $role]);
            Session::setFlash('success', "Member added to workspace with role '{$role}'.");
        }

        AuditLogService::log($tenantId, (int)current_user()['id'], 'invite_member', 'user', $userId, ['email' => $email, 'role' => $role]);

        return Response::redirect(url('/admin/settings/workspace'));
    }

    public function removeMember(Request $request, string $id): Response
    {
        if (!AuthorizationService::canManageTeam()) {
            Session::setFlash('error', 'Permission denied.');
            return Response::redirect(url('/admin/settings/workspace'));
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $membership = Database::fetchOne("SELECT * FROM tenant_memberships WHERE id = ? AND tenant_id = ?", [(int)$id, $tenantId]);

        if ($membership && $membership['role'] !== 'owner') {
            Database::execute("DELETE FROM tenant_memberships WHERE id = ?", [$membership['id']]);
            Session::setFlash('success', 'Team member removed.');
        } else {
            Session::setFlash('error', 'Cannot remove workspace owner.');
        }

        return Response::redirect(url('/admin/settings/workspace'));
    }

    public function profile(Request $request): Response
    {
        return view('settings.profile', [
            'title' => 'Profile Settings — Quiz Funnel SaaS',
            'user' => current_user(),
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $user = current_user();
        $name = trim($request->input('name', ''));
        $currentPassword = $request->input('current_password');
        $newPassword = $request->input('new_password');

        if (empty($name)) {
            Session::setFlash('error', 'Name cannot be empty.');
            return Response::redirect(url('/admin/settings/profile'));
        }

        Database::execute("UPDATE users SET name = ? WHERE id = ?", [$name, $user['id']]);
        $user['name'] = $name;
        Session::set('user', $user);

        if (!empty($newPassword)) {
            $userDb = Database::fetchOne("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
            if (!password_verify($currentPassword, $userDb['password_hash'])) {
                Session::setFlash('error', 'Current password is incorrect.');
                return Response::redirect(url('/admin/settings/profile'));
            }

            if (strlen($newPassword) < 8) {
                Session::setFlash('error', 'New password must be at least 8 characters long.');
                return Response::redirect(url('/admin/settings/profile'));
            }

            Database::execute("UPDATE users SET password_hash = ? WHERE id = ?", [password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
            Session::setFlash('success', 'Profile and password updated successfully.');
            return Response::redirect(url('/admin/settings/profile'));
        }

        Session::setFlash('success', 'Profile updated successfully.');
        return Response::redirect(url('/admin/settings/profile'));
    }
}
