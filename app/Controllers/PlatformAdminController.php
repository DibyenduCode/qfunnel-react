<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Services\AuditLogService;
use App\Services\PlatformSettingService;
use App\Services\UsageLimitService;
use App\Services\LegalPageService;
use App\Services\SubscriptionService;

class PlatformAdminController
{
    /**
     * 1. Super Admin Overview Dashboard
     */
    public function index(Request $request): Response
    {
        $totalTenants = (int) Database::fetchColumn("SELECT COUNT(*) FROM tenants");
        $activeTenants = (int) Database::fetchColumn("SELECT COUNT(*) FROM tenants WHERE status = 'active'");
        $suspendedTenants = (int) Database::fetchColumn("SELECT COUNT(*) FROM tenants WHERE status = 'suspended'");
        $terminationDueCount = count(SubscriptionService::getTerminationDueWorkspaces());
        
        $totalUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users");
        $activeUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active'");
        
        $totalFunnels = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE status != 'archived'");
        $publishedFunnels = (int) Database::fetchColumn("SELECT COUNT(*) FROM funnels WHERE status = 'published'");
        $totalSubmissions = (int) Database::fetchColumn("SELECT COUNT(*) FROM quiz_sessions");

        // Plan distribution
        $planStats = Database::query(
            "SELECT p.id, p.name, p.slug, p.price, COUNT(ts.id) as subscriber_count
             FROM plans p
             LEFT JOIN tenant_subscriptions ts ON ts.plan_id = p.id AND ts.status IN ('active', 'trial')
             GROUP BY p.id, p.name, p.slug, p.price
             ORDER BY p.price ASC, p.id ASC"
        );

        // Recent Workspaces
        $recentTenants = Database::query(
            "SELECT t.*, 
                    (SELECT COUNT(*) FROM funnels f WHERE f.tenant_id = t.id) as funnels_count,
                    (SELECT u.name FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_name,
                    p.name as plan_name
             FROM tenants t
             LEFT JOIN tenant_subscriptions ts ON ts.tenant_id = t.id AND ts.status IN ('active', 'trial')
             LEFT JOIN plans p ON ts.plan_id = p.id
             ORDER BY t.created_at DESC
             LIMIT 5"
        );

        $recentLogs = AuditLogService::listLogs(null, 8);

        // Payment gateway status overview
        $gateways = PlatformSettingService::getPaymentGateways();
        $activeGatewaysCount = 0;
        foreach ($gateways as $gw) {
            if (!empty($gw['enabled'])) {
                $activeGatewaysCount++;
            }
        }

        return view('platform.index', [
            'title' => 'Platform Overview — Super Admin Control',
            'stats' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'termination_due_count' => $terminationDueCount,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_funnels' => $totalFunnels,
                'published_funnels' => $publishedFunnels,
                'total_submissions' => $totalSubmissions,
                'active_gateways_count' => $activeGatewaysCount,
            ],
            'planStats' => $planStats,
            'recentTenants' => $recentTenants,
            'recentLogs' => $recentLogs,
            'systemInfo' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Web Server',
                'db_driver' => 'MySQL / MariaDB via PDO',
                'max_upload' => ini_get('upload_max_filesize'),
                'timezone' => date_default_timezone_get(),
            ]
        ], 'superadmin');
    }

    /**
     * 2. Customers & Users Management
     */
    public function customers(Request $request): Response
    {
        $search = trim((string)$request->query('q', ''));
        $statusFilter = trim((string)$request->query('status', ''));

        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM funnels f WHERE f.tenant_id = t.id AND f.status != 'archived') as funnels_count,
                       (SELECT COUNT(*) FROM quiz_sessions qs WHERE qs.tenant_id = t.id) as sessions_count,
                       (SELECT u.id FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_id,
                       (SELECT u.name FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_name,
                       (SELECT u.email FROM tenant_memberships tm JOIN users u ON tm.user_id = u.id WHERE tm.tenant_id = t.id AND tm.role = 'owner' LIMIT 1) as owner_email,
                       p.id as current_plan_id,
                       p.name as plan_name,
                       p.slug as plan_slug,
                       p.price as plan_price,
                       ts.id as subscription_id,
                       ts.status as subscription_status,
                       ts.billing_interval,
                       ts.current_period_start,
                       ts.current_period_end,
                       ts.next_billing_date,
                       ts.ends_at as subscription_ends_at,
                       ts.auto_renew,
                       ts.amount as subscription_amount,
                       ts.currency as subscription_currency
                FROM tenants t
                LEFT JOIN tenant_subscriptions ts ON ts.tenant_id = t.id AND ts.id = (SELECT MAX(id) FROM tenant_subscriptions WHERE tenant_id = t.id)
                LEFT JOIN plans p ON ts.plan_id = p.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (t.name LIKE ? OR t.slug LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($statusFilter !== '') {
            $sql .= " AND t.status = ?";
            $params[] = $statusFilter;
        }

        $page = max(1, (int)$request->query('page', 1));
        $perPage = 10;

        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_tenants";
        $totalTenants = (int) Database::fetchColumn($countSql, $params);

        $offset = ($page - 1) * $perPage;
        $sql .= " ORDER BY t.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $tenantItems = Database::query($sql, $params);

        $tenantsPagination = [
            'items' => $tenantItems,
            'total' => $totalTenants,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($totalTenants / $perPage),
        ];

        // Termination Due list for Super Admin Manual Review Queue
        $terminationDueList = SubscriptionService::getTerminationDueWorkspaces();

        // Platform invoices (last 50)
        $invoices = SubscriptionService::getAllInvoices(50);

        // All Users with pagination
        $userPage = max(1, (int)$request->query('user_page', 1));
        $userPerPage = 10;
        $totalUsers = (int) Database::fetchColumn("SELECT COUNT(*) FROM users");
        $userOffset = ($userPage - 1) * $userPerPage;
        $userItems = Database::query(
            "SELECT u.*, 
                    (SELECT COUNT(*) FROM tenant_memberships tm WHERE tm.user_id = u.id) as workspace_count,
                    (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM tenant_memberships tm JOIN tenants t ON tm.tenant_id = t.id WHERE tm.user_id = u.id) as workspace_names
             FROM users u
             ORDER BY u.created_at DESC
             LIMIT {$userPerPage} OFFSET {$userOffset}"
        );

        $usersPagination = [
            'items' => $userItems,
            'total' => $totalUsers,
            'page' => $userPage,
            'per_page' => $userPerPage,
            'total_pages' => (int)ceil($totalUsers / $userPerPage),
        ];

        // Plans for assignment and billing cycle modal
        $plans = Database::query("SELECT id, name, slug, price, billing_interval FROM plans WHERE is_active = 1 ORDER BY price ASC, id ASC");

        return view('platform.customers', [
            'title' => 'Customer & Billing Management — Super Admin',
            'tenants' => $tenantsPagination,
            'terminationDueList' => $terminationDueList,
            'invoices' => $invoices,
            'users' => $usersPagination,
            'plans' => $plans,
            'search' => $search,
            'statusFilter' => $statusFilter,
        ], 'superadmin');
    }

    /**
     * 3. Suspend / Reactivate Customer (Tenant)
     */
    public function toggleTenantStatus(Request $request, string $id): Response
    {
        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [(int)$id]);
        if ($tenant) {
            $newStatus = $tenant['status'] === 'active' ? 'suspended' : 'active';
            Database::execute("UPDATE tenants SET status = ? WHERE id = ?", [$newStatus, $tenant['id']]);
            AuditLogService::log($tenant['id'], (int)current_user()['id'], 'toggle_tenant_status', 'tenant', $tenant['id'], [
                'previous_status' => $tenant['status'],
                'new_status' => $newStatus
            ]);
            Session::setFlash('success', "Workspace '{$tenant['name']}' has been " . ($newStatus === 'active' ? 'reactivated.' : 'suspended.'));
        }

        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 3b. Suspend / Reactivate User
     */
    public function toggleUserStatus(Request $request, string $id): Response
    {
        $userId = (int)$id;
        $currentUser = current_user();

        if ($userId === (int)($currentUser['id'] ?? 0)) {
            Session::setFlash('error', "You cannot suspend your own administrative account.");
            return Response::redirect(url('/admin/platform/customers'));
        }

        $targetUser = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$targetUser) {
            Session::setFlash('error', "Target user not found.");
            return Response::redirect(url('/admin/platform/customers'));
        }

        $newStatus = ($targetUser['status'] === 'active') ? 'suspended' : 'active';
        Database::execute("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);

        AuditLogService::log(null, (int)$currentUser['id'], 'toggle_user_status', 'user', $userId, [
            'user_email' => $targetUser['email'],
            'new_status' => $newStatus
        ]);

        Session::setFlash('success', "User '{$targetUser['name']}' ({$targetUser['email']}) is now " . ($newStatus === 'active' ? 'active.' : 'suspended.'));
        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 4. Assign Plan to Customer
     */
    public function assignPlan(Request $request, string $id): Response
    {
        $tenantId = (int)$id;
        $planId = (int)$request->input('plan_id');
        $status = (string)$request->input('status', 'active');

        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);

        if (!$tenant || !$plan) {
            Session::setFlash('error', "Invalid tenant workspace or plan selected.");
            return Response::redirect(url('/admin/platform/customers'));
        }

        // Deactivate previous active subscriptions for this tenant
        Database::execute("UPDATE tenant_subscriptions SET status = 'cancelled' WHERE tenant_id = ? AND status IN ('active', 'trial')", [$tenantId]);

        // Insert new subscription
        Database::execute(
            "INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, starts_at, ends_at, provider, provider_subscription_id) 
             VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 'manual_superadmin', ?)",
            [$tenantId, $planId, $status, 'assigned_by_admin_' . current_user()['id']]
        );

        AuditLogService::log($tenantId, (int)current_user()['id'], 'assign_plan', 'tenant', $tenantId, [
            'plan_id' => $planId,
            'plan_name' => $plan['name'],
            'status' => $status
        ]);

        Session::setFlash('success', "Assigned plan '{$plan['name']}' to workspace '{$tenant['name']}'.");
        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 4b. Modify Billing Cycle (Interval, Dates, Plan, Auto-Renew)
     */
    public function updateBillingCycle(Request $request, string $id): Response
    {
        $tenantId = (int)$id;
        $adminUserId = (int)current_user()['id'];

        try {
            SubscriptionService::modifyBillingCycle($tenantId, [
                'plan_id' => (int)$request->input('plan_id'),
                'billing_interval' => (string)$request->input('billing_interval', 'monthly'),
                'current_period_start' => (string)$request->input('current_period_start'),
                'current_period_end' => (string)$request->input('current_period_end'),
                'status' => (string)$request->input('status', 'active'),
                'auto_renew' => !empty($request->input('auto_renew')),
                'amount' => (float)$request->input('amount', 0),
                'currency' => (string)$request->input('currency', 'INR'),
            ], $adminUserId);

            Session::setFlash('success', "Billing cycle and subscription terms successfully updated.");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Failed to update billing cycle: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 4c. Record Offline/Manual Payment (Cash, UPI, Bank Transfer, Invoice)
     */
    public function recordPayment(Request $request, string $id): Response
    {
        $tenantId = (int)$id;
        $adminUserId = (int)current_user()['id'];

        try {
            $invoice = SubscriptionService::recordManualPayment($tenantId, [
                'plan_id' => (int)$request->input('plan_id'),
                'amount' => (float)$request->input('amount', 0),
                'currency' => (string)$request->input('currency', 'INR'),
                'billing_interval' => (string)$request->input('billing_interval', 'monthly'),
                'payment_method' => (string)$request->input('payment_method', 'bank_transfer'),
                'transaction_reference' => (string)$request->input('transaction_reference', ''),
                'notes' => (string)$request->input('notes', ''),
            ], $adminUserId);

            Session::setFlash('success', "Payment recorded! Invoice #{$invoice['invoice_number']} generated and billing cycle extended.");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Failed to record payment: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 4d. Suspend Workspace (Zero data deletion - Data Stays)
     */
    public function suspendTenant(Request $request, string $id): Response
    {
        $tenantId = (int)$id;
        $adminUserId = (int)current_user()['id'];
        $reason = trim((string)$request->input('reason', 'Overdue subscription payment'));
        $notes = trim((string)$request->input('notes', ''));

        try {
            SubscriptionService::suspendWorkspace($tenantId, $reason, $notes, $adminUserId);
            Session::setFlash('success', "Workspace suspended. All customer funnels and lead data remain 100% securely preserved.");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Failed to suspend workspace: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 4e. Unsuspend (Reactivate) Workspace
     */
    public function unsuspendTenant(Request $request, string $id): Response
    {
        $tenantId = (int)$id;
        $adminUserId = (int)current_user()['id'];
        $extendUntil = trim((string)$request->input('extend_until', ''));

        try {
            SubscriptionService::unsuspendWorkspace($tenantId, !empty($extendUntil) ? $extendUntil : null, $adminUserId);
            Session::setFlash('success', "Workspace successfully reactivated! Normal service resumed.");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Failed to unsuspend workspace: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 4f. Flag for Termination Due Review Queue
     */
    public function markTerminationDue(Request $request, string $id): Response
    {
        $tenantId = (int)$id;
        $adminUserId = (int)current_user()['id'];
        $reason = trim((string)$request->input('reason', 'Account marked for termination review'));

        try {
            SubscriptionService::markTerminationDue($tenantId, $reason, $adminUserId);
            Session::setFlash('success', "Workspace added to Termination Due review queue.");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Error: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 4g. Dismiss / Postpone Termination Due
     */
    public function dismissTerminationDue(Request $request, string $id): Response
    {
        $tenantId = (int)$id;
        $adminUserId = (int)current_user()['id'];

        try {
            SubscriptionService::dismissTerminationDue($tenantId, $adminUserId);
            Session::setFlash('success', "Workspace dismissed from Termination Due queue.");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Error: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 4h. Manual Workspace Termination (Super Admin ONLY - Never automated)
     */
    public function terminateTenant(Request $request, string $id): Response
    {
        $tenantId = (int)$id;
        $adminUserId = (int)current_user()['id'];
        $confirmSlug = trim((string)$request->input('confirm_slug', ''));
        $reason = trim((string)$request->input('reason', 'Administrative manual contract termination'));

        $tenant = Database::fetchOne("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        if (!$tenant) {
            Session::setFlash('error', "Workspace not found.");
            return Response::redirect(url('/admin/platform/customers'));
        }

        if (strtolower($confirmSlug) !== strtolower($tenant['slug'])) {
            Session::setFlash('error', "Confirmation failed: You must type '{$tenant['slug']}' to confirm manual termination.");
            return Response::redirect(url('/admin/platform/customers'));
        }

        try {
            SubscriptionService::terminateWorkspace($tenantId, $reason, $adminUserId);
            Session::setFlash('success', "Workspace '{$tenant['name']}' has been manually terminated. Historical data is archived.");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Failed to terminate workspace: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/platform/customers'));
    }

    /**
     * 5. Plans Management (Make plans for users)
     */
    public function plans(Request $request): Response
    {
        $plans = Database::query(
            "SELECT p.*, 
                    (SELECT COUNT(*) FROM tenant_subscriptions ts WHERE ts.plan_id = p.id AND ts.status IN ('active', 'trial')) as active_subscribers_count
             FROM plans p
             ORDER BY p.price ASC, p.id ASC"
        );

        return view('platform.plans', [
            'title' => 'Subscription Plans — Super Admin Control',
            'plans' => $plans,
            'featureCatalog' => UsageLimitService::getFeatureCatalog(),
        ], 'superadmin');
    }

    /**
     * 5b. Create New Plan
     */
    public function createPlan(Request $request): Response
    {
        $name = trim((string)$request->input('name', ''));
        $slug = trim((string)$request->input('slug', ''));
        $price = (float)$request->input('price', 0);
        $interval = (string)$request->input('billing_interval', 'monthly');
        $description = trim((string)$request->input('description', ''));
        
        $maxFunnels = (int)$request->input('max_funnels', 1);
        $maxSubmissions = (int)$request->input('max_submissions_per_month', 100);
        $maxTeamMembers = (int)$request->input('max_team_members', 1);

        if (empty($name)) {
            Session::setFlash('error', "Plan name is required.");
            return Response::redirect(url('/admin/platform/plans'));
        }

        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
        }

        $existing = Database::fetchOne("SELECT id FROM plans WHERE slug = ?", [$slug]);
        if ($existing) {
            Session::setFlash('error', "A plan with slug '{$slug}' already exists. Please choose a different slug.");
            return Response::redirect(url('/admin/platform/plans'));
        }

        $limits = [
            'max_funnels' => $maxFunnels,
            'max_submissions_per_month' => $maxSubmissions,
            'max_team_members' => $maxTeamMembers,
        ];

        // Read all system features dynamically from catalog
        $catalog = UsageLimitService::getFeatureCatalog();
        $features = [];
        foreach ($catalog as $key => $meta) {
            $features[$key] = !empty($request->input("feature_{$key}"));
        }

        Database::execute(
            "INSERT INTO plans (name, slug, price, billing_interval, description, limits_json, features_json, is_active, is_visible) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $name,
                $slug,
                $price,
                $interval,
                $description,
                json_encode($limits),
                json_encode($features),
                !empty($request->input('is_active')) ? 1 : 0,
                !empty($request->input('is_visible')) ? 1 : 0
            ]
        );

        $newPlanId = (int)Database::lastInsertId();
        AuditLogService::log(null, (int)current_user()['id'], 'create_plan', 'plan', $newPlanId, ['name' => $name, 'slug' => $slug]);

        Session::setFlash('success', "Plan '{$name}' created successfully.");
        return Response::redirect(url('/admin/platform/plans'));
    }

    /**
     * 5c. Update Existing Plan
     */
    public function updatePlan(Request $request, string $id): Response
    {
        $planId = (int)$id;
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            Session::setFlash('error', "Plan not found.");
            return Response::redirect(url('/admin/platform/plans'));
        }

        $name = trim((string)$request->input('name', ''));
        $price = (float)$request->input('price', 0);
        $interval = (string)$request->input('billing_interval', 'monthly');
        $description = trim((string)$request->input('description', ''));

        $maxFunnels = (int)$request->input('max_funnels', 1);
        $maxSubmissions = (int)$request->input('max_submissions_per_month', 100);
        $maxTeamMembers = (int)$request->input('max_team_members', 1);

        if (empty($name)) {
            Session::setFlash('error', "Plan name cannot be empty.");
            return Response::redirect(url('/admin/platform/plans'));
        }

        $limits = [
            'max_funnels' => $maxFunnels,
            'max_submissions_per_month' => $maxSubmissions,
            'max_team_members' => $maxTeamMembers,
        ];

        // Read all system features dynamically from catalog
        $catalog = UsageLimitService::getFeatureCatalog();
        $features = [];
        foreach ($catalog as $key => $meta) {
            $features[$key] = !empty($request->input("feature_{$key}"));
        }

        Database::execute(
            "UPDATE plans SET name = ?, price = ?, billing_interval = ?, description = ?, limits_json = ?, features_json = ?, is_active = ?, is_visible = ? WHERE id = ?",
            [
                $name,
                $price,
                $interval,
                $description,
                json_encode($limits),
                json_encode($features),
                !empty($request->input('is_active')) ? 1 : 0,
                !empty($request->input('is_visible')) ? 1 : 0,
                $planId
            ]
        );

        AuditLogService::log(null, (int)current_user()['id'], 'update_plan', 'plan', $planId, ['name' => $name]);
        Session::setFlash('success', "Plan '{$name}' updated successfully.");
        return Response::redirect(url('/admin/platform/plans'));
    }

    /**
     * 5d. Toggle Frontend Visibility (Show/Hide plan on homepage)
     */
    public function togglePlanVisibility(Request $request, string $id): Response
    {
        $planId = (int)$id;
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            Session::setFlash('error', "Plan not found.");
            return Response::redirect(url('/admin/platform/plans'));
        }

        $newVal = empty($plan['is_visible']) ? 1 : 0;
        Database::execute("UPDATE plans SET is_visible = ? WHERE id = ?", [$newVal, $planId]);

        $userId = (int)(current_user()['id'] ?? 0);
        AuditLogService::log(null, $userId, 'toggle_plan_visibility', 'plan', $planId, [
            'name' => $plan['name'],
            'is_visible' => $newVal,
        ]);

        $statusMsg = $newVal 
            ? "Plan '{$plan['name']}' is now VISIBLE to users on the frontend home page."
            : "Plan '{$plan['name']}' is now HIDDEN from users on the frontend home page.";

        Session::setFlash('success', $statusMsg);
        return Response::redirect(url('/admin/platform/plans'));
    }

    /**
     * 5e. Delete / Deactivate Plan
     */
    public function deletePlan(Request $request, string $id): Response
    {
        $planId = (int)$id;
        $plan = Database::fetchOne("SELECT * FROM plans WHERE id = ?", [$planId]);
        if (!$plan) {
            Session::setFlash('error', "Plan not found.");
            return Response::redirect(url('/admin/platform/plans'));
        }

        $activeSubCount = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM tenant_subscriptions WHERE plan_id = ? AND status IN ('active', 'trial')",
            [$planId]
        );

        if ($activeSubCount > 0) {
            // Cannot hard delete active plan, deactivate instead
            Database::execute("UPDATE plans SET is_active = 0 WHERE id = ?", [$planId]);
            Session::setFlash('success', "Plan '{$plan['name']}' has {$activeSubCount} active subscriptions and has been deactivated instead of deleted.");
            return Response::redirect(url('/admin/platform/plans'));
        }

        Database::execute("DELETE FROM plans WHERE id = ?", [$planId]);
        AuditLogService::log(null, (int)current_user()['id'], 'delete_plan', 'plan', $planId, ['name' => $plan['name']]);

        Session::setFlash('success', "Plan '{$plan['name']}' was removed.");
        return Response::redirect(url('/admin/platform/plans'));
    }

    /**
     * 6. Payment Gateways Settings (Auto and Manual both)
     */
    public function paymentGateways(Request $request): Response
    {
        $gateways = PlatformSettingService::getPaymentGateways();

        return view('platform.payment_gateways', [
            'title' => 'Payment Gateway Settings — Super Admin',
            'gateways' => $gateways,
        ], 'superadmin');
    }

    /**
     * 6b. Save Payment Gateways Settings
     */
    public function updatePaymentGateways(Request $request): Response
    {
        $payload = [
            'stripe' => [
                'enabled' => !empty($request->input('stripe_enabled')),
                'mode' => (string)$request->input('stripe_mode', 'test'),
                'publishable_key' => trim((string)$request->input('stripe_publishable_key', '')),
                'secret_key' => trim((string)$request->input('stripe_secret_key', '')),
                'webhook_secret' => trim((string)$request->input('stripe_webhook_secret', '')),
            ],
            'paypal' => [
                'enabled' => !empty($request->input('paypal_enabled')),
                'mode' => (string)$request->input('paypal_mode', 'sandbox'),
                'client_id' => trim((string)$request->input('paypal_client_id', '')),
                'client_secret' => trim((string)$request->input('paypal_client_secret', '')),
            ],
            'razorpay' => [
                'enabled' => !empty($request->input('razorpay_enabled')),
                'key_id' => trim((string)$request->input('razorpay_key_id', '')),
                'key_secret' => trim((string)$request->input('razorpay_key_secret', '')),
            ],
            'bank_transfer' => [
                'enabled' => !empty($request->input('bank_enabled')),
                'bank_name' => trim((string)$request->input('bank_name', '')),
                'account_holder' => trim((string)$request->input('bank_account_holder', '')),
                'account_number' => trim((string)$request->input('bank_account_number', '')),
                'routing_code' => trim((string)$request->input('bank_routing_code', '')),
                'instructions' => trim((string)$request->input('bank_instructions', '')),
            ],
            'manual_custom' => [
                'enabled' => !empty($request->input('manual_enabled')),
                'title' => trim((string)$request->input('manual_title', 'Offline / Cash / Cheque Payment')),
                'instructions' => trim((string)$request->input('manual_instructions', '')),
                'qr_code_url' => trim((string)$request->input('manual_qr_code_url', '')),
            ],
        ];

        $warnings = [];
        if (!empty($payload['stripe']['enabled']) && (empty($payload['stripe']['publishable_key']) || empty($payload['stripe']['secret_key']))) {
            $warnings[] = "Stripe is enabled with missing keys.";
        }
        if (!empty($payload['razorpay']['enabled']) && (empty($payload['razorpay']['key_id']) || empty($payload['razorpay']['key_secret']))) {
            $warnings[] = "Razorpay is enabled without complete credentials.";
        }
        if (!empty($payload['paypal']['enabled']) && empty($payload['paypal']['client_id'])) {
            $warnings[] = "PayPal is enabled without Client ID.";
        }
        if (!empty($payload['bank_transfer']['enabled']) && empty($payload['bank_transfer']['account_number'])) {
            $warnings[] = "Bank Transfer is enabled without account number.";
        }

        PlatformSettingService::set('payment_gateways', $payload);

        AuditLogService::log(null, (int)current_user()['id'], 'update_payment_gateways', 'settings', null, [
            'stripe_enabled' => $payload['stripe']['enabled'],
            'paypal_enabled' => $payload['paypal']['enabled'],
            'razorpay_enabled' => $payload['razorpay']['enabled'],
            'bank_enabled' => $payload['bank_transfer']['enabled'],
            'manual_enabled' => $payload['manual_custom']['enabled'],
        ]);

        if (!empty($warnings)) {
            Session::setFlash('success', "Settings saved with warnings: " . implode(" ", $warnings));
        } else {
            Session::setFlash('success', "Payment gateway settings updated successfully.");
        }
        return Response::redirect(url('/admin/platform/payment-gateways'));
    }

    /**
     * 7. Super Admin Profile Option (Change Email & Password)
     */
    public function profile(Request $request): Response
    {
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [(int)current_user()['id']]);

        return view('platform.profile', [
            'title' => 'Admin Profile Settings — Super Admin',
            'adminUser' => $user ?: current_user(),
        ], 'superadmin');
    }

    /**
     * 7b. Update Profile Info (Name & Email)
     */
    public function updateProfile(Request $request): Response
    {
        $userId = (int)current_user()['id'];
        $name = trim((string)$request->input('name', ''));
        $email = trim(strtolower((string)$request->input('email', '')));

        if (empty($name) || empty($email)) {
            Session::setFlash('error', "Name and email are required fields.");
            return Response::redirect(url('/admin/platform/profile'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', "Invalid email address format.");
            return Response::redirect(url('/admin/platform/profile'));
        }

        // Check if email already used by another user
        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($existing) {
            Session::setFlash('error', "Email address '{$email}' is already in use by another account.");
            return Response::redirect(url('/admin/platform/profile'));
        }

        Database::execute("UPDATE users SET name = ?, email = ? WHERE id = ?", [$name, $email, $userId]);

        // Refresh current user session
        $updatedUser = Database::fetchOne("SELECT id, name, email, is_platform_admin, status FROM users WHERE id = ?", [$userId]);
        Session::set('user', $updatedUser);

        AuditLogService::log(null, $userId, 'update_admin_profile', 'user', $userId, ['email' => $email, 'name' => $name]);

        Session::setFlash('success', "Profile details successfully updated.");
        return Response::redirect(url('/admin/platform/profile'));
    }

    /**
     * 7c. Update Password
     */
    public function updatePassword(Request $request): Response
    {
        $userId = (int)current_user()['id'];
        $currentPassword = (string)$request->input('current_password', '');
        $newPassword = (string)$request->input('new_password', '');
        $confirmPassword = (string)$request->input('confirm_password', '');

        if (empty($currentPassword) || empty($newPassword)) {
            Session::setFlash('error', "All password fields are required.");
            return Response::redirect(url('/admin/platform/profile'));
        }

        $user = Database::fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            Session::setFlash('error', "Current password is incorrect.");
            return Response::redirect(url('/admin/platform/profile'));
        }

        if (strlen($newPassword) < 8) {
            Session::setFlash('error', "New password must be at least 8 characters long.");
            return Response::redirect(url('/admin/platform/profile'));
        }

        if ($newPassword !== $confirmPassword) {
            Session::setFlash('error', "New password and password confirmation do not match.");
            return Response::redirect(url('/admin/platform/profile'));
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        Database::execute("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $userId]);

        AuditLogService::log(null, $userId, 'change_admin_password', 'user', $userId, []);

        Session::setFlash('success', "Password changed successfully.");
        return Response::redirect(url('/admin/platform/profile'));
    }

    /**
     * 8. System Audit Logs
     */
    public function logs(Request $request): Response
    {
        $page = max(1, (int)$request->query('page', 1));
        $logs = AuditLogService::listLogsPaginated(null, $page, 20);

        return view('platform.logs', [
            'title' => 'Platform Audit Logs — Super Admin Control',
            'logs' => $logs,
        ], 'superadmin');
    }

    /**
     * 9. Legal Pages & Compliance CMS
     */
    public function legalPages(Request $request): Response
    {
        $pages = LegalPageService::getAllPages();

        $selectedId = (int)$request->query('id', 0);
        $activePage = null;

        if ($selectedId > 0) {
            foreach ($pages as $p) {
                if ((int)$p['id'] === $selectedId) {
                    $activePage = $p;
                    break;
                }
            }
        }

        if (!$activePage && !empty($pages)) {
            $activePage = $pages[0];
        }

        return view('platform.legal_pages', [
            'title' => 'Legal Pages & Policies CMS — Super Admin Control',
            'pages' => $pages,
            'activePage' => $activePage,
        ], 'superadmin');
    }

    /**
     * 9b. Update Legal Page
     */
    public function updateLegalPage(Request $request, string $id): Response
    {
        $pageId = (int)$id;

        try {
            LegalPageService::updatePage($pageId, [
                'title' => (string)$request->input('title'),
                'slug' => (string)$request->input('slug'),
                'meta_description' => (string)$request->input('meta_description'),
                'content' => (string)$request->input('content'),
                'is_published' => (int)$request->input('is_published', 1),
            ]);

            $currentUser = current_user();
            AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'update_legal_page', 'legal_page', $pageId, [
                'title' => $request->input('title'),
                'slug' => $request->input('slug'),
            ]);

            Session::setFlash('success', "Legal policy page successfully updated and live.");
        } catch (\Exception $e) {
            Session::setFlash('error', "Error updating legal page: " . $e->getMessage());
        }

        return Response::redirect(url('/admin/platform/legal-pages?id=' . $pageId));
    }

    /**
     * 9c. Create New Legal Page
     */
    public function createLegalPage(Request $request): Response
    {
        try {
            $newPageId = LegalPageService::createPage([
                'title' => (string)$request->input('title'),
                'slug' => (string)$request->input('slug'),
                'meta_description' => (string)$request->input('meta_description'),
                'content' => (string)$request->input('content'),
                'is_published' => (int)$request->input('is_published', 1),
            ]);

            $currentUser = current_user();
            AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'create_legal_page', 'legal_page', $newPageId, [
                'title' => $request->input('title'),
                'slug' => $request->input('slug'),
            ]);

            Session::setFlash('success', "New legal policy page created successfully.");
            return Response::redirect(url('/admin/platform/legal-pages?id=' . $newPageId));
        } catch (\Exception $e) {
            Session::setFlash('error', "Failed to create legal page: " . $e->getMessage());
            return Response::redirect(url('/admin/platform/legal-pages'));
        }
    }

    /**
     * 9d. Delete Legal Page
     */
    public function deleteLegalPage(Request $request, string $id): Response
    {
        $pageId = (int)$id;
        $page = LegalPageService::getPageById($pageId);

        if (!$page) {
            Session::setFlash('error', "Legal page not found.");
            return Response::redirect(url('/admin/platform/legal-pages'));
        }

        if (in_array($page['slug'], ['privacy', 'terms', 'refund-policy', 'cookie-policy'])) {
            Session::setFlash('error', "Cannot delete core system legal policy '{$page['title']}'. You may edit or unpublish it instead.");
            return Response::redirect(url('/admin/platform/legal-pages?id=' . $pageId));
        }

        LegalPageService::deletePage($pageId);

        $currentUser = current_user();
        AuditLogService::log(null, (int)($currentUser['id'] ?? 0), 'delete_legal_page', 'legal_page', $pageId, [
            'title' => $page['title'],
            'slug' => $page['slug'],
        ]);

        Session::setFlash('success', "Legal page '{$page['title']}' permanently deleted.");
        return Response::redirect(url('/admin/platform/legal-pages'));
    }

    /**
     * 10. API, Webhook & Embedding Documentation Hub
     */
    public function docs(Request $request): Response
    {
        $sampleTenant = Database::fetchOne("SELECT id, name, slug, api_key FROM tenants WHERE api_key IS NOT NULL AND api_key != '' LIMIT 1");
        $sampleFunnel = Database::fetchOne("SELECT id, name, slug FROM funnels WHERE status = 'published' LIMIT 1");
        if (!$sampleFunnel) {
            $sampleFunnel = Database::fetchOne("SELECT id, name, slug FROM funnels LIMIT 1");
        }

        $baseUrl = rtrim(url('/'), '/');

        return view('platform.docs', [
            'title' => 'API, Webhooks & Embedding Documentation',
            'baseUrl' => $baseUrl,
            'sampleApiKey' => $sampleTenant['api_key'] ?? 'qfn_live_abc123xyz789demo',
            'sampleFunnelSlug' => $sampleFunnel['slug'] ?? 'sample-lead-quiz',
            'sampleFunnelName' => $sampleFunnel['name'] ?? 'Sample Quiz Funnel',
            'sampleFunnelId' => $sampleFunnel['id'] ?? 1,
        ], 'superadmin');
    }
}
