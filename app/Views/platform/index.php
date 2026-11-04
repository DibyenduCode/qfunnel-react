<div class="space-y-6">

    <!-- Super Admin Welcome Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white border border-slate-800 shadow-sm relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 mb-2">
                    Super Admin Console
                </span>
                <h2 class="text-xl font-bold tracking-tight">Platform Master Control</h2>
                <p class="text-sm text-slate-300 mt-1">Manage global SaaS tenants, subscription plans, platform users, and billing gateways.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/admin/platform/customers') ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Manage Customers
                </a>
                <a href="<?= url('/admin/platform/plans') ?>" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-lg border border-slate-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Configure Plans
                </a>
            </div>
        </div>
    </div>

    <!-- Platform KPI Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Customers / Workspaces -->
        <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm hover:border-indigo-300 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer Workspaces</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-slate-900"><?= (int)$stats['total_tenants'] ?></span>
                <span class="text-xs font-medium text-emerald-600"><?= (int)$stats['active_tenants'] ?> active</span>
            </div>
            <?php if ($stats['suspended_tenants'] > 0): ?>
                <span class="text-[11px] text-rose-500 block mt-1"><?= (int)$stats['suspended_tenants'] ?> suspended</span>
            <?php endif; ?>
            <?php if (!empty($stats['termination_due_count'])): ?>
                <a href="<?= url('/admin/platform/customers') ?>" class="text-[11px] text-amber-600 font-bold block hover:underline">⚠️ <?= (int)$stats['termination_due_count'] ?> in termination queue</a>
            <?php endif; ?>
        </div>

        <!-- Total Users -->
        <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm hover:border-indigo-300 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Platform Users</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-slate-900"><?= (int)$stats['total_users'] ?></span>
                <span class="text-xs font-medium text-slate-500"><?= (int)$stats['active_users'] ?> active</span>
            </div>
        </div>

        <!-- Funnels Across Platform -->
        <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm hover:border-indigo-300 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Funnels Created</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-slate-900"><?= (int)$stats['total_funnels'] ?></span>
                <span class="text-xs font-medium text-purple-600"><?= (int)$stats['published_funnels'] ?> live</span>
            </div>
        </div>

        <!-- Total Quiz Sessions / Leads -->
        <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm hover:border-indigo-300 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Leads & Sessions</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-emerald-600"><?= (int)$stats['total_submissions'] ?></span>
                <span class="text-xs font-medium text-slate-500">responses</span>
            </div>
        </div>
    </div>

    <!-- Active Plans Breakdown & Payment Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Subscription Plans Distribution -->
        <div class="lg:col-span-2 bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#E2E8F0]">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Subscription Plans Distribution</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Active customer allocations across tiers</p>
                </div>
                <a href="<?= url('/admin/platform/plans') ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                    Manage Plans →
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <?php foreach ($planStats as $p): ?>
                    <div class="p-3.5 rounded-lg border border-[#E2E8F0] bg-slate-50/50 hover:bg-slate-50 transition-colors">
                        <span class="block text-xs font-bold text-slate-800"><?= e($p['name']) ?></span>
                        <span class="text-[11px] text-slate-500 font-mono">₹<?= number_format((float)$p['price'], 2) ?>/mo</span>
                        <div class="mt-3 flex items-baseline justify-between">
                            <span class="text-xl font-extrabold text-indigo-600"><?= (int)$p['subscriber_count'] ?></span>
                            <span class="text-[10px] uppercase font-semibold text-slate-400">Tenants</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment Gateway Health Card -->
        <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-[#E2E8F0]">
                    <h3 class="text-sm font-bold text-slate-900">Payment Gateways</h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $stats['active_gateways_count'] > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                        <?= $stats['active_gateways_count'] ?> Enabled
                    </span>
                </div>
                <p class="text-xs text-slate-600 mb-4">
                    Both Auto (Stripe, PayPal, Razorpay) and Manual (Bank Transfer, Offline) checkout gateways can be toggled and configured.
                </p>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Automated Processing</span>
                        <span class="font-semibold text-slate-800">Stripe / PayPal / Razorpay</span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-600">Manual / Offline</span>
                        <span class="font-semibold text-slate-800">Bank Wire / Custom</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-[#E2E8F0]">
                <a href="<?= url('/admin/platform/payment-gateways') ?>" class="w-full py-2 px-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center justify-center gap-2 transition-colors">
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                    Gateway Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Workspaces Table -->
    <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E2E8F0] flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Recent Customer Workspaces</h3>
                <p class="text-xs text-slate-500">Latest registered tenants on the platform</p>
            </div>
            <a href="<?= url('/admin/platform/customers') ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                View All Customers →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#E2E8F0] bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Workspace</th>
                        <th class="py-3 px-4">Owner</th>
                        <th class="py-3 px-4">Active Plan</th>
                        <th class="py-3 px-4 text-center">Funnels</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Quick Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2E8F0]">
                    <?php foreach ($recentTenants as $t): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-semibold text-slate-900">
                                <?= e($t['name']) ?>
                                <span class="block text-[10px] text-slate-400 font-mono"><?= e($t['slug']) ?></span>
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                <?= e($t['owner_name'] ?: 'Owner Unassigned') ?>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <?= e($t['plan_name'] ?: 'Starter') ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800">
                                <?= (int)$t['funnels_count'] ?>
                            </td>
                            <td class="py-3 px-4">
                                <?php if ($t['status'] === 'active'): ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="<?= url('/admin/platform/customers?q=' . urlencode($t['name'])) ?>" class="px-2.5 py-1 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded border border-slate-200 transition-colors">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Platform Environment Info -->
    <div class="bg-white border border-[#E2E8F0] rounded-xl p-4 shadow-sm text-xs">
        <h4 class="font-bold text-slate-800 mb-2">Platform System Diagnostics</h4>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-slate-600">
            <div>
                <span class="text-slate-400 block text-[10px] uppercase">PHP Version</span>
                <span class="font-mono font-medium text-slate-800"><?= e($systemInfo['php_version']) ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase">Database</span>
                <span class="font-medium text-slate-800"><?= e($systemInfo['db_driver']) ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase">Max Upload Limit</span>
                <span class="font-medium text-slate-800"><?= e($systemInfo['max_upload']) ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase">Default Timezone</span>
                <span class="font-medium text-slate-800"><?= e($systemInfo['timezone']) ?></span>
            </div>
        </div>
    </div>

</div>
