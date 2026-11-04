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

class TeamController
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContextService::getActiveTenantId();
        $isOwner = AuthorizationService::canManageTeam();
        $currentUserId = (int)(current_user()['id'] ?? 0);

        $search = trim((string)$request->query('search', ''));
        $roleFilter = trim((string)$request->query('role', ''));
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 10;

        $sql = "SELECT tm.id as membership_id, tm.role, tm.created_at as joined_at,
                       u.id as user_id, u.name, u.email, u.status as user_status, u.last_login_at
                FROM tenant_memberships tm
                JOIN users u ON tm.user_id = u.id
                WHERE tm.tenant_id = ?";
        $params = [$tenantId];

        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($roleFilter !== '' && in_array($roleFilter, ['owner', 'admin', 'editor', 'analyst'], true)) {
            $sql .= " AND tm.role = ?";
            $params[] = $roleFilter;
        }

        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_members_table";
        $totalMembers = (int) Database::fetchColumn($countSql, $params);

        $offset = ($page - 1) * $perPage;
        $sql .= " ORDER BY CASE WHEN tm.role = 'owner' THEN 1 WHEN tm.role = 'admin' THEN 2 WHEN tm.role = 'editor' THEN 3 ELSE 4 END, tm.created_at ASC
                  LIMIT {$perPage} OFFSET {$offset}";
        $members = Database::query($sql, $params);

        $pagination = [
            'items' => $members,
            'total' => $totalMembers,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($totalMembers / $perPage),
        ];

        $usage = UsageLimitService::getUsageOverview($tenantId);

        return view('team.index', [
            'title' => 'Team Members & Permissions — Quiz Funnel SaaS',
            'members' => $pagination,
            'isOwner' => $isOwner,
            'currentUserId' => $currentUserId,
            'usage' => $usage,
            'search' => $search,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function store(Request $request): Response
    {
        if (!AuthorizationService::canManageTeam()) {
            Session::setFlash('error', 'Only workspace owners can add new team members.');
            return Response::redirect(url('/admin/team'));
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $limitCheck = UsageLimitService::checkCanAddTeamMember($tenantId);
        if (!$limitCheck['allowed']) {
            Session::setFlash('error', $limitCheck['message']);
            return Response::redirect(url('/admin/team'));
        }

        $name = trim((string)$request->input('name', ''));
        $email = trim(strtolower((string)$request->input('email', '')));
        $password = (string)$request->input('password', '');
        $role = (string)$request->input('role', 'editor');

        if (empty($name) || empty($email)) {
            Session::setFlash('error', 'Member name and email address are required.');
            return Response::redirect(url('/admin/team'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Please provide a valid email address.');
            return Response::redirect(url('/admin/team'));
        }

        if (!in_array($role, ['admin', 'editor', 'analyst'], true)) {
            $role = 'editor';
        }

        if (empty($password)) {
            $password = bin2hex(random_bytes(4)) . 'A1!';
        } elseif (strlen($password) < 8) {
            Session::setFlash('error', 'Password must be at least 8 characters long.');
            return Response::redirect(url('/admin/team'));
        }

        // Check if user already exists
        $user = Database::fetchOne("SELECT id, name FROM users WHERE email = ?", [$email]);
        if (!$user) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            Database::execute(
                "INSERT INTO users (name, email, password_hash, is_platform_admin, status) VALUES (?, ?, ?, 0, 'active')",
                [$name, $email, $passwordHash]
            );
            $userId = (int) Database::lastInsertId();
        } else {
            $userId = (int)$user['id'];
        }

        // Check if already in workspace
        $existingMembership = Database::fetchOne(
            "SELECT id FROM tenant_memberships WHERE tenant_id = ? AND user_id = ?",
            [$tenantId, $userId]
        );

        if ($existingMembership) {
            Session::setFlash('error', "User '{$email}' is already a member of this workspace.");
            return Response::redirect(url('/admin/team'));
        }

        Database::execute(
            "INSERT INTO tenant_memberships (tenant_id, user_id, role, created_at) VALUES (?, ?, ?, NOW())",
            [$tenantId, $userId, $role]
        );

        AuditLogService::log($tenantId, (int)current_user()['id'], 'add_team_member', 'user', $userId, [
            'name' => $name,
            'email' => $email,
            'role' => $role
        ]);

        Session::setFlash('success', "Team member '{$name}' successfully added with role '" . ucfirst($role) . "'.");
        return Response::redirect(url('/admin/team'));
    }

    public function update(Request $request, string $id): Response
    {
        if (!AuthorizationService::canManageTeam()) {
            Session::setFlash('error', 'Only workspace owners have authority to update member credentials and roles.');
            return Response::redirect(url('/admin/team'));
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $membership = Database::fetchOne(
            "SELECT tm.*, u.email as current_email, u.name as current_name 
             FROM tenant_memberships tm 
             JOIN users u ON tm.user_id = u.id 
             WHERE tm.id = ? AND tm.tenant_id = ?",
            [(int)$id, $tenantId]
        );

        if (!$membership) {
            Session::setFlash('error', 'Team member membership record not found.');
            return Response::redirect(url('/admin/team'));
        }

        $targetUserId = (int)$membership['user_id'];
        $name = trim((string)$request->input('name', ''));
        $email = trim(strtolower((string)$request->input('email', '')));
        $role = (string)$request->input('role', $membership['role']);
        $status = (string)$request->input('status', 'active');
        $newPassword = (string)$request->input('new_password', '');

        if (empty($name) || empty($email)) {
            Session::setFlash('error', 'Name and email address are required.');
            return Response::redirect(url('/admin/team'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Invalid email address format.');
            return Response::redirect(url('/admin/team'));
        }

        // Check if email already belongs to another user
        $emailCollision = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $targetUserId]);
        if ($emailCollision) {
            Session::setFlash('error', "Email address '{$email}' is already in use by another user account.");
            return Response::redirect(url('/admin/team'));
        }

        // Update User profile details (name, email, status)
        if (!in_array($status, ['active', 'suspended'], true)) {
            $status = 'active';
        }

        Database::execute(
            "UPDATE users SET name = ?, email = ?, status = ? WHERE id = ?",
            [$name, $email, $status, $targetUserId]
        );

        // Update password if provided
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                Session::setFlash('error', 'New password must be at least 8 characters long.');
                return Response::redirect(url('/admin/team'));
            }
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            Database::execute("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $targetUserId]);
            AuditLogService::log($tenantId, (int)current_user()['id'], 'change_user_password', 'user', $targetUserId, ['email' => $email]);
        }

        // Update role (owners cannot demote themselves to prevent ownerless workspace)
        if ($membership['role'] !== 'owner') {
            if (in_array($role, ['admin', 'editor', 'analyst'], true)) {
                Database::execute("UPDATE tenant_memberships SET role = ? WHERE id = ?", [$role, $membership['id']]);
            }
        }

        AuditLogService::log($tenantId, (int)current_user()['id'], 'update_team_member', 'user', $targetUserId, [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'status' => $status
        ]);

        Session::setFlash('success', "Team member '{$name}' credentials and permissions updated successfully.");
        return Response::redirect(url('/admin/team'));
    }

    public function remove(Request $request, string $id): Response
    {
        if (!AuthorizationService::canManageTeam()) {
            Session::setFlash('error', 'Only workspace owners can remove team members.');
            return Response::redirect(url('/admin/team'));
        }

        $tenantId = TenantContextService::getActiveTenantId();
        $membership = Database::fetchOne(
            "SELECT tm.*, u.name, u.email 
             FROM tenant_memberships tm 
             JOIN users u ON tm.user_id = u.id 
             WHERE tm.id = ? AND tm.tenant_id = ?",
            [(int)$id, $tenantId]
        );

        if (!$membership) {
            Session::setFlash('error', 'Membership record not found.');
            return Response::redirect(url('/admin/team'));
        }

        if ($membership['role'] === 'owner') {
            Session::setFlash('error', 'Cannot remove the primary workspace owner.');
            return Response::redirect(url('/admin/team'));
        }

        Database::execute("DELETE FROM tenant_memberships WHERE id = ?", [$membership['id']]);

        AuditLogService::log($tenantId, (int)current_user()['id'], 'remove_team_member', 'user', (int)$membership['user_id'], [
            'email' => $membership['email']
        ]);

        Session::setFlash('success', "Team member '{$membership['name']}' has been removed from this workspace.");
        return Response::redirect(url('/admin/team'));
    }
}
