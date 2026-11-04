<?php
$tenantItems = $tenants['items'] ?? $tenants;
$userItems = $users['items'] ?? $users;
$totalTenantsCount = $tenants['total'] ?? count($tenantItems);
$totalUsersCount = $users['total'] ?? count($userItems);
$terminationDueList = $terminationDueList ?? [];
$invoices = $invoices ?? [];
$terminationDueCount = count($terminationDueList);
$invoicesCount = count($invoices);
?>
<div class="space-y-6">

    <!-- Header & Search/Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Customer & Subscription Management</h2>
            <p class="text-xs text-slate-500 mt-0.5">Control customer workspaces, recurring billing cycles, suspension states, and termination reviews.</p>
        </div>
        
        <!-- Search and Filter Form -->
        <form method="GET" action="<?= url('/admin/platform/customers') ?>" class="flex items-center gap-2">
            <div class="relative">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name or slug..." 
                       class="w-56 px-3 py-1.5 pl-8 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white shadow-sm">
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="status" class="text-xs px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended Only</option>
                <option value="terminated" <?= $statusFilter === 'terminated' ? 'selected' : '' ?>>Terminated Only</option>
            </select>

            <?php if ($search !== '' || $statusFilter !== ''): ?>
                <a href="<?= url('/admin/platform/customers') ?>" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-[#E2E8F0] flex gap-2 sm:gap-6 text-xs font-semibold overflow-x-auto" id="mgmtTabs">
        <button type="button" onclick="switchMgmtTab('tab-tenants')" id="btn-tab-tenants" class="pb-3 border-b-2 border-indigo-600 text-indigo-600 font-bold transition-all whitespace-nowrap">
            Customer Workspaces (<?= $totalTenantsCount ?>)
        </button>
        <button type="button" onclick="switchMgmtTab('tab-termination')" id="btn-tab-termination" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center gap-1.5 whitespace-nowrap">
            <span>⚠️ Termination Due Queue</span>
            <?php if ($terminationDueCount > 0): ?>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-600 text-white animate-pulse">
                    <?= $terminationDueCount ?>
                </span>
            <?php else: ?>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-500">0</span>
            <?php endif; ?>
        </button>
        <button type="button" onclick="switchMgmtTab('tab-invoices')" id="btn-tab-invoices" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-all whitespace-nowrap">
            Billing & Invoices (<?= $invoicesCount ?>)
        </button>
        <button type="button" onclick="switchMgmtTab('tab-users')" id="btn-tab-users" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-all whitespace-nowrap">
            Platform Users (<?= $totalUsersCount ?>)
        </button>
    </div>

    <!-- TAB 1: Customer Workspaces (Tenants) -->
    <div id="tab-tenants" class="space-y-4">
        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#E2E8F0] bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Workspace / Slug</th>
                            <th class="py-3 px-4">Owner Contact</th>
                            <th class="py-3 px-4">Plan & Billing Cycle</th>
                            <th class="py-3 px-4">Next Due Date</th>
                            <th class="py-3 px-4 text-center">Funnels / Leads</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Administrative Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E8F0]">
                        <?php if (empty($tenantItems)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No customer workspaces found matching criteria.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($tenantItems as $t): 
                            $isSuspended = ($t['status'] === 'suspended');
                            $isTerminated = ($t['status'] === 'terminated');
                            $isDue = !empty($t['is_termination_due']);
                            $endDate = !empty($t['current_period_end']) ? $t['current_period_end'] : (!empty($t['subscription_ends_at']) ? $t['subscription_ends_at'] : null);
                            $isPastDue = $endDate && strtotime($endDate) < time() && !$isSuspended && !$isTerminated;
                        ?>
                            <tr class="hover:bg-slate-50/70 transition-colors <?= $isSuspended ? 'bg-rose-50/20' : ($isTerminated ? 'bg-slate-100/40 opacity-70' : '') ?>">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-1.5">
                                        <span><?= e($t['name']) ?></span>
                                        <?php if ($isDue): ?>
                                            <span class="inline-flex px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Due for Review</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="block text-[10px] text-slate-400 font-mono">slug: <?= e($t['slug']) ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <span class="font-medium"><?= e($t['owner_name'] ?: 'N/A') ?></span>
                                    <span class="block text-[11px] text-slate-400"><?= e($t['owner_email'] ?: 'No owner linked') ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            <?= e($t['plan_name'] ?: 'Starter') ?>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 capitalize">
                                            <?= e($t['billing_interval'] ?? 'monthly') ?>
                                        </span>
                                    </div>
                                    <span class="block text-[10px] text-slate-400 mt-0.5">
                                        ₹<?= number_format(((float)$t['subscription_amount'] > 0 ? (float)$t['subscription_amount'] : (float)($t['plan_price'] ?? 0)), 2) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($endDate): ?>
                                        <span class="font-medium <?= $isPastDue ? 'text-rose-600 font-bold' : 'text-slate-700' ?>">
                                            <?= date('M d, Y', strtotime($endDate)) ?>
                                        </span>
                                        <?php if ($isPastDue): ?>
                                            <span class="block text-[10px] text-rose-500 font-bold">Past Due (Grace)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">No expiry set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center text-slate-800">
                                    <span class="font-bold"><?= (int)$t['funnels_count'] ?></span>
                                    <span class="text-slate-400">/</span>
                                    <span class="text-slate-600"><?= (int)$t['sessions_count'] ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($isTerminated): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200 text-slate-800 border border-slate-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span> Terminated
                                        </span>
                                    <?php elseif ($isSuspended): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200" title="<?= e($t['suspension_reason'] ?? '') ?>">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Suspended
                                        </span>
                                        <?php if (!empty($t['suspension_reason'])): ?>
                                            <span class="block text-[9px] text-rose-500 truncate max-w-[110px]" title="<?= e($t['suspension_reason']) ?>">
                                                <?= e($t['suspension_reason']) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($isPastDue): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Past Due
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Billing & Cycle Control Modal Trigger -->
                                        <button type="button" 
                                                onclick='openBillingModal(<?= json_encode($t) ?>)'
                                                class="px-2.5 py-1 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold border border-indigo-200 transition-colors text-[11px]">
                                            Billing Cycle
                                        </button>

                                        <!-- Record Payment Button -->
                                        <button type="button" 
                                                onclick='openRecordPaymentModal(<?= json_encode($t) ?>)'
                                                class="px-2 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold border border-emerald-200 transition-colors text-[11px]">
                                            Record Payment
                                        </button>

                                        <!-- Suspend / Unsuspend Actions -->
                                        <?php if ($t['status'] === 'active'): ?>
                                            <button type="button" 
                                                    onclick="openSuspendModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['name'])) ?>')"
                                                    class="px-2 py-1 rounded bg-rose-50 text-rose-700 font-semibold border border-rose-200 hover:bg-rose-100 transition-colors text-[11px]">
                                                Suspend
                                            </button>
                                        <?php elseif ($t['status'] === 'suspended'): ?>
                                            <button type="button" 
                                                    onclick="openUnsuspendModal(<?= (int)$t['id'] ?>, '<?= e(addslashes($t['name'])) ?>')"
                                                    class="px-2 py-1 rounded bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200 hover:bg-emerald-100 transition-colors text-[11px]">
                                                Unsuspend
                                            </button>
                                        <?php endif; ?>

                                        <!-- Flag for Termination Due (if not already) -->
                                        <?php if ($t['status'] !== 'terminated'): ?>
                                            <?php if (!$isDue): ?>
                                                <form method="POST" action="<?= url("/admin/platform/tenants/{$t['id']}/mark-termination-due") ?>" class="inline" onsubmit="return confirm('Flag workspace <?= e(addslashes($t['name'])) ?> for termination review?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="reason" value="Super Admin flagged for review">
                                                    <button type="submit" class="px-1.5 py-1 text-slate-400 hover:text-amber-600 text-[11px]" title="Flag for Termination Review">
                                                        ⚠️
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($tenants['total_pages'])): ?>
                <?php
                $pagination = $tenants;
                $queryParams = array_filter(['q' => $search ?? '', 'status' => $statusFilter ?? '']);
                require __DIR__ . '/../partials/pagination.php';
                ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 2: Termination Due Review Queue (Super Admin Manual Review) -->
    <div id="tab-termination" class="hidden space-y-4">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5 font-bold">
                ⚠️
            </div>
            <div>
                <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider">Manual Termination Queue (Zero Auto-Terminations)</h3>
                <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                    These workspaces are either suspended for more than 30 days without payment or flagged for contract cancellation. 
                    <strong>The system never terminates accounts automatically.</strong> Please review each workspace below and manually decide to terminate, grant an extension, or unsuspend.
                </p>
            </div>
        </div>

        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#E2E8F0] bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Workspace & Slug</th>
                            <th class="py-3 px-4">Owner Contact</th>
                            <th class="py-3 px-4">Days Suspended</th>
                            <th class="py-3 px-4">Reason / Notes</th>
                            <th class="py-3 px-4 text-center">Funnels / Leads</th>
                            <th class="py-3 px-4 text-right">Super Admin Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E8F0]">
                        <?php if (empty($terminationDueList)): ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    ✓ No workspaces currently due for termination review. All accounts healthy.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($terminationDueList as $td): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <?= e($td['name']) ?>
                                    <span class="block text-[10px] text-slate-400 font-mono"><?= e($td['slug']) ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <span class="font-medium"><?= e($td['owner_name'] ?: 'N/A') ?></span>
                                    <span class="block text-[11px] text-slate-400"><?= e($td['owner_email'] ?: 'No email') ?></span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-rose-600">
                                    <?= (int)($td['days_suspended'] ?? 0) ?> days suspended
                                    <span class="block text-[10px] text-slate-400">Since <?= date('M d, Y', strtotime($td['suspended_at'] ?? 'now')) ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 max-w-xs">
                                    <span class="line-clamp-2"><?= e($td['termination_reason'] ?: ($td['suspension_reason'] ?: 'Prolonged non-payment')) ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-center text-slate-700 font-bold">
                                    <?= (int)$td['funnels_count'] ?> funnels / <?= (int)$td['sessions_count'] ?> leads
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Postpone / Hold -->
                                        <form method="POST" action="<?= url("/admin/platform/tenants/{$td['id']}/dismiss-termination-due") ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px]">
                                                Hold / Postpone
                                            </button>
                                        </form>

                                        <!-- Unsuspend -->
                                        <button type="button" 
                                                onclick="openUnsuspendModal(<?= (int)$td['id'] ?>, '<?= e(addslashes($td['name'])) ?>')"
                                                class="px-2 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold border border-emerald-200 text-[11px]">
                                            Unsuspend
                                        </button>

                                        <!-- Manual Terminate (Red Confirm Button) -->
                                        <button type="button" 
                                                onclick="openTerminateModal(<?= (int)$td['id'] ?>, '<?= e(addslashes($td['name'])) ?>', '<?= e(addslashes($td['slug'])) ?>')"
                                                class="px-2.5 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white font-bold text-[11px] shadow-sm transition-colors">
                                            Terminate Account
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: Billing & Invoices Ledger -->
    <div id="tab-invoices" class="hidden space-y-4">
        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-[#E2E8F0] flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Platform Invoices & Payment Ledger</h3>
                    <p class="text-xs text-slate-500">Historical records of paid and pending subscription invoices across all workspaces.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#E2E8F0] bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Customer Workspace</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Billing Cycle</th>
                            <th class="py-3 px-4">Payment Method</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Issue Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E8F0]">
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No recorded invoices found.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($invoices as $inv): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-indigo-600">
                                    <?= e($inv['invoice_number']) ?>
                                    <?php if (!empty($inv['transaction_reference'])): ?>
                                        <span class="block text-[9px] text-slate-400 font-sans truncate max-w-[120px]">Ref: <?= e($inv['transaction_reference']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    <?= e($inv['tenant_name']) ?>
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <?= e($inv['currency']) ?> <?= number_format((float)$inv['amount'], 2) ?>
                                </td>
                                <td class="py-3 px-4 capitalize text-slate-700">
                                    <?= e($inv['billing_interval']) ?>
                                    <span class="block text-[10px] text-slate-400">
                                        <?= date('M d', strtotime($inv['period_start'])) ?> - <?= date('M d, Y', strtotime($inv['period_end'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 capitalize text-slate-700">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px] font-semibold">
                                        <?= str_replace('_', ' ', e($inv['payment_method'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($inv['status'] === 'paid'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✓ Paid
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <?= ucfirst(e($inv['status'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-slate-500 font-mono">
                                    <?= date('M d, Y', strtotime($inv['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: Platform Users -->
    <div id="tab-users" class="hidden space-y-4">
        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-[#E2E8F0] flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Platform Registered Users</h3>
                    <p class="text-xs text-slate-500">View all registered accounts and suspend or reactivate individual user logins.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#E2E8F0] bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">User Details</th>
                            <th class="py-3 px-4">Role / Workspaces</th>
                            <th class="py-3 px-4">Last Login</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E8F0]">
                        <?php foreach ($userItems as $u): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    <?= e($u['name']) ?>
                                    <span class="block text-[11px] text-slate-400 font-normal"><?= e($u['email']) ?></span>
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    <span class="font-medium"><?= (int)$u['workspace_count'] ?> workspace(s)</span>
                                    <span class="block text-[10px] text-slate-400 truncate max-w-xs"><?= e($u['workspace_names'] ?: 'None') ?></span>
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    <?= $u['last_login_at'] ? date('M d, Y h:i A', strtotime($u['last_login_at'])) : 'Never' ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                                        <form method="POST" action="<?= url("/admin/platform/users/{$u['id']}/toggle-status") ?>" class="inline" onsubmit="return confirm('Toggle status for this user?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="px-2.5 py-1 rounded text-[11px] font-semibold border <?= $u['status'] === 'active' ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' ?>">
                                                <?= $u['status'] === 'active' ? 'Suspend User' : 'Reactivate' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[11px] italic">Current Account</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($users['total_pages'])): ?>
                <?php
                $pagination = $users;
                $pageParam = 'user_page';
                $queryParams = array_filter(['q' => $search ?? '', 'status' => $statusFilter ?? '']);
                require __DIR__ . '/../partials/pagination.php';
                ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL 1: Modify Billing Cycle & Expiration -->
    <div id="billingCycleModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-[#E2E8F0]">
            <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Modify Billing Cycle & Terms
                </h3>
                <button type="button" onclick="closeBillingModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="billingCycleForm" method="POST" action="" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Workspace</label>
                    <input type="text" id="bcModalTenantName" readonly class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 font-bold text-slate-800 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Plan Tier</label>
                        <select name="plan_id" id="bcModalPlan" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= (int)$p['id'] ?>">
                                    <?= e($p['name']) ?> (₹<?= number_format((float)$p['price'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Billing Interval</label>
                        <select name="billing_interval" id="bcModalInterval" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="lifetime">Lifetime / Exempt</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Period Start</label>
                        <input type="date" name="current_period_start" id="bcModalStart" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Period End / Next Due</label>
                        <input type="date" name="current_period_end" id="bcModalEnd" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Quick Date Extension Shortcuts -->
                <div class="flex items-center gap-2 pt-1">
                    <span class="text-[10px] font-semibold text-slate-500 uppercase">Extend:</span>
                    <button type="button" onclick="extendPeriodDays(14)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200">
                        +14 Days (Trial)
                    </button>
                    <button type="button" onclick="extendPeriodDays(30)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200">
                        +1 Month
                    </button>
                    <button type="button" onclick="extendPeriodDays(365)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200">
                        +1 Year
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Custom Amount (INR)</label>
                        <input type="number" step="0.01" name="amount" id="bcModalAmount" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" id="bcModalStatus" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="active">Active (Paid)</option>
                            <option value="trial">Trial</option>
                            <option value="past_due">Past Due</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="auto_renew" id="bcModalAutoRenew" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <span>Enable automatic recurring cycle renewal</span>
                    </label>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeBillingModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition-colors">
                        Save Billing Terms
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Record Offline Payment & Generate Invoice -->
    <div id="recordPaymentModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-[#E2E8F0]">
            <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between bg-emerald-50">
                <h3 class="text-sm font-bold text-emerald-950 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Record Manual / Offline Payment
                </h3>
                <button type="button" onclick="closeRecordPaymentModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="recordPaymentForm" method="POST" action="" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Workspace</label>
                    <input type="text" id="rpModalTenantName" readonly class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 font-bold text-slate-800 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="bank_transfer">Bank Transfer (NEFT/RTGS)</option>
                            <option value="upi">UPI / GPay / PhonePe</option>
                            <option value="cash">Cash Received</option>
                            <option value="cheque">Cheque Deposit</option>
                            <option value="stripe_manual">Stripe (Offline/External)</option>
                            <option value="complimentary">Complimentary / Zero</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Advance Interval</label>
                        <select name="billing_interval" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="monthly">+1 Month</option>
                            <option value="yearly">+1 Year</option>
                            <option value="quarterly">+3 Months</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (INR)</label>
                        <input type="number" step="0.01" name="amount" id="rpModalAmount" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="e.g. 1999.00">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Ref / UTR</label>
                        <input type="text" name="transaction_reference" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="UTR or Cheque No.">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Internal Note / Memo</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="e.g. Received via Silicon Valley Bank transfer"></textarea>
                </div>

                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[11px] text-emerald-800">
                    💡 This will create a paid platform invoice, advance the next billing date, and automatically unsuspend the workspace if it was on hold.
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeRecordPaymentModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-colors">
                        Record Payment & Generate Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Suspend Workspace (Data Stays) -->
    <div id="suspendModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-rose-200">
            <div class="px-6 py-4 border-b border-rose-100 flex items-center justify-between bg-rose-50">
                <h3 class="text-sm font-bold text-rose-950 flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Suspend Customer Workspace
                </h3>
                <button type="button" onclick="closeSuspendModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="suspendForm" method="POST" action="" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Workspace</label>
                    <input type="text" id="suspendModalTenantName" readonly class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 font-bold text-slate-800 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Suspension</label>
                    <select name="reason" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="Overdue subscription payment">Overdue subscription payment</option>
                        <option value="Violation of Terms of Service">Violation of Terms of Service</option>
                        <option value="Customer requested temporary pause">Customer requested temporary pause</option>
                        <option value="Billing dispute or chargeback">Billing dispute or chargeback</option>
                        <option value="Spam / Abusive funnel content">Spam / Abusive funnel content</option>
                        <option value="Administrative review hold">Administrative review hold</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Internal Note (Optional)</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="Details about this suspension action..."></textarea>
                </div>

                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[11px] text-emerald-800 flex items-start gap-2">
                    <span>🛡️</span>
                    <span><strong>Data Stay Guarantee:</strong> All customer funnels, question flows, and lead submissions remain 100% intact in the database. Public visitor quizzes will show a polite pause notice.</span>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeSuspendModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-500 text-white shadow-sm transition-colors">
                        Confirm Suspension
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Unsuspend (Reactivate) Workspace -->
    <div id="unsuspendModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-emerald-200">
            <div class="px-6 py-4 border-b border-emerald-100 flex items-center justify-between bg-emerald-50">
                <h3 class="text-sm font-bold text-emerald-950 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Reactivate / Unsuspend Workspace
                </h3>
                <button type="button" onclick="closeUnsuspendModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="unsuspendForm" method="POST" action="" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Workspace</label>
                    <input type="text" id="unsuspendModalTenantName" readonly class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 font-bold text-slate-800 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Extend Billing Period Until (Optional)</label>
                    <input type="date" name="extend_until" id="unsuspendModalExtend" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <span class="block text-[10px] text-slate-400 mt-1">Set a future date (e.g. +30 days) to prevent the customer from immediately expiring again.</span>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeUnsuspendModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-colors">
                        Reactivate Workspace Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: Manual Termination (Never automatic - Requires Slug Confirmation) -->
    <div id="terminateModal" class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-rose-300">
            <div class="px-6 py-4 border-b border-rose-200 flex items-center justify-between bg-rose-600 text-white">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Manual Workspace Termination
                </h3>
                <button type="button" onclick="closeTerminateModal()" class="text-rose-200 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="terminateForm" method="POST" action="" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900">
                    <strong>Critical Warning:</strong> This will terminate the customer workspace. All active funnel sessions will immediately cease. Historical data will be locked and archived.
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Workspace to Terminate</label>
                    <input type="text" id="terminateModalTenantName" readonly class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 font-bold text-slate-800 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Termination</label>
                    <input type="text" name="reason" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="e.g. Contract expired, client requested termination">
                </div>

                <div>
                    <label class="block text-xs font-bold text-rose-700 mb-1">
                        Type "<span id="terminateModalExpectedSlug" class="font-mono underline"></span>" to confirm:
                    </label>
                    <input type="text" name="confirm_slug" id="terminateModalConfirmInput" required class="w-full px-3 py-2 text-xs rounded-lg border border-rose-300 bg-rose-50/50 font-mono focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="Type slug here...">
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeTerminateModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition-colors">
                        Confirm Manual Termination
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
// Tab Switching logic
function switchMgmtTab(tabId) {
    const tabs = ['tab-tenants', 'tab-termination', 'tab-invoices', 'tab-users'];
    tabs.forEach(t => {
        const el = document.getElementById(t);
        if (el) el.classList.add('hidden');
        const btn = document.getElementById('btn-' + t);
        if (btn) {
            btn.classList.remove('border-indigo-600', 'text-indigo-600', 'font-bold');
            btn.classList.add('border-transparent', 'text-slate-500');
        }
    });

    const targetTab = document.getElementById(tabId);
    if (targetTab) targetTab.classList.remove('hidden');

    const targetBtn = document.getElementById('btn-' + tabId);
    if (targetBtn) {
        targetBtn.classList.add('border-indigo-600', 'text-indigo-600', 'font-bold');
        targetBtn.classList.remove('border-transparent', 'text-slate-500');
    }
}

// 1. Billing Cycle Modal
function openBillingModal(tenant) {
    const modal = document.getElementById('billingCycleModal');
    const form = document.getElementById('billingCycleForm');
    form.action = '<?= url("/admin/platform/tenants") ?>/' + tenant.id + '/billing-cycle';

    document.getElementById('bcModalTenantName').value = tenant.name + ' (' + tenant.slug + ')';
    if (tenant.current_plan_id) document.getElementById('bcModalPlan').value = tenant.current_plan_id;
    if (tenant.billing_interval) document.getElementById('bcModalInterval').value = tenant.billing_interval;
    if (tenant.current_period_start) document.getElementById('bcModalStart').value = tenant.current_period_start.split(' ')[0];
    if (tenant.current_period_end) document.getElementById('bcModalEnd').value = tenant.current_period_end.split(' ')[0];
    else if (tenant.subscription_ends_at) document.getElementById('bcModalEnd').value = tenant.subscription_ends_at.split(' ')[0];
    
    const parsedBcAmount = parseFloat(tenant.subscription_amount || 0);
    document.getElementById('bcModalAmount').value = parsedBcAmount > 0 ? parsedBcAmount : (parseFloat(tenant.plan_price || 0) || 0);
    document.getElementById('bcModalStatus').value = tenant.subscription_status || 'active';
    document.getElementById('bcModalAutoRenew').checked = tenant.auto_renew !== 0;

    modal.classList.remove('hidden');
}

function closeBillingModal() {
    document.getElementById('billingCycleModal').classList.add('hidden');
}

function extendPeriodDays(days) {
    const endInput = document.getElementById('bcModalEnd');
    let base = endInput.value ? new Date(endInput.value) : new Date();
    if (isNaN(base.getTime())) base = new Date();
    base.setDate(base.getDate() + days);
    endInput.value = base.toISOString().split('T')[0];
}

// 2. Record Payment Modal
function openRecordPaymentModal(tenant) {
    const modal = document.getElementById('recordPaymentModal');
    const form = document.getElementById('recordPaymentForm');
    form.action = '<?= url("/admin/platform/tenants") ?>/' + tenant.id + '/record-payment';

    document.getElementById('rpModalTenantName').value = tenant.name;
    const parsedRpAmount = parseFloat(tenant.subscription_amount || 0);
    document.getElementById('rpModalAmount').value = parsedRpAmount > 0 ? parsedRpAmount : (parseFloat(tenant.plan_price || 0) || 0);

    modal.classList.remove('hidden');
}

function closeRecordPaymentModal() {
    document.getElementById('recordPaymentModal').classList.add('hidden');
}

// 3. Suspend Modal
function openSuspendModal(tenantId, tenantName) {
    const modal = document.getElementById('suspendModal');
    const form = document.getElementById('suspendForm');
    form.action = '<?= url("/admin/platform/tenants") ?>/' + tenantId + '/suspend';
    document.getElementById('suspendModalTenantName').value = tenantName;
    modal.classList.remove('hidden');
}

function closeSuspendModal() {
    document.getElementById('suspendModal').classList.add('hidden');
}

// 4. Unsuspend Modal
function openUnsuspendModal(tenantId, tenantName) {
    const modal = document.getElementById('unsuspendModal');
    const form = document.getElementById('unsuspendForm');
    form.action = '<?= url("/admin/platform/tenants") ?>/' + tenantId + '/unsuspend';
    document.getElementById('unsuspendModalTenantName').value = tenantName;
    
    // Default extend date +30 days
    const nextMonth = new Date();
    nextMonth.setDate(nextMonth.getDate() + 30);
    document.getElementById('unsuspendModalExtend').value = nextMonth.toISOString().split('T')[0];

    modal.classList.remove('hidden');
}

function closeUnsuspendModal() {
    document.getElementById('unsuspendModal').classList.add('hidden');
}

// 5. Terminate Modal (Manual only)
function openTerminateModal(tenantId, tenantName, slug) {
    const modal = document.getElementById('terminateModal');
    const form = document.getElementById('terminateForm');
    form.action = '<?= url("/admin/platform/tenants") ?>/' + tenantId + '/terminate';
    document.getElementById('terminateModalTenantName').value = tenantName;
    document.getElementById('terminateModalExpectedSlug').textContent = slug;
    document.getElementById('terminateModalConfirmInput').value = '';
    modal.classList.remove('hidden');
}

function closeTerminateModal() {
    document.getElementById('terminateModal').classList.add('hidden');
}

// Auto tab select if parameter present
if (new URLSearchParams(window.location.search).has('user_page')) {
    switchMgmtTab('tab-users');
}
</script>
