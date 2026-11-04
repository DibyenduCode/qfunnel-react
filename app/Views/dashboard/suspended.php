<div class="w-full max-w-xl mx-auto my-8 bg-white border border-rose-200 rounded-2xl shadow-xl overflow-hidden">
    <!-- Header with Amber/Rose Alert -->
    <div class="bg-gradient-to-r from-rose-600 via-rose-500 to-amber-600 px-6 py-6 text-white text-center">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white mb-3 shadow-inner">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h1 class="text-xl font-extrabold tracking-tight">Workspace Temporarily Suspended</h1>
        <p class="text-xs text-rose-100 mt-1">
            Access to workspace <strong><?= e($tenant['name']) ?></strong> is currently on hold.
        </p>
    </div>

    <div class="p-6 space-y-6">
        <!-- DATA PRESERVATION ASSURANCE BOX -->
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Your Data is 100% Safe & Intact</h3>
                <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">
                    All your published funnels, question flows, leads, form submissions, and analytics remain securely preserved in our database. <strong>Zero data has been deleted.</strong> Once reactivated, your workspace and funnels will resume immediately.
                </p>
            </div>
        </div>

        <!-- Suspension Details -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs space-y-2.5">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="text-slate-500 font-medium">Workspace:</span>
                <span class="font-bold text-slate-800"><?= e($tenant['name']) ?> (slug: <?= e($tenant['slug']) ?>)</span>
            </div>
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="text-slate-500 font-medium">Reason for Suspension:</span>
                <span class="font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                    <?= e($tenant['suspension_reason'] ?: 'Subscription payment overdue or pending administrative review') ?>
                </span>
            </div>
            <?php if (!empty($tenant['suspended_at'])): ?>
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="text-slate-500 font-medium">Suspended On:</span>
                <span class="text-slate-700 font-mono"><?= date('M d, Y h:i A', strtotime($tenant['suspended_at'])) ?></span>
            </div>
            <?php endif; ?>
            <div class="flex items-center justify-between">
                <span class="text-slate-500 font-medium">Plan & Billing Interval:</span>
                <span class="text-slate-800 font-semibold">
                    <?= e($billing['subscription']['plan_name'] ?? 'Standard') ?> 
                    (<?= ucfirst(e($billing['subscription']['billing_interval'] ?? 'monthly')) ?>)
                </span>
            </div>
        </div>

        <!-- How to Reactivate -->
        <div class="border border-indigo-100 bg-indigo-50/50 rounded-xl p-4 text-xs space-y-2">
            <h4 class="font-bold text-indigo-950 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                How to Reactivate Your Workspace:
            </h4>
            <p class="text-indigo-900 leading-relaxed">
                To clear past-due payments, update your billing cycle, or resolve suspension holds, please reach out to our platform support team at 
                <a href="mailto:support@qfunnel.local" class="font-semibold underline text-indigo-700">support@qfunnel.local</a> or contact your platform Super Admin.
            </p>
        </div>

        <!-- Workspace Switching (If user has multiple workspaces) -->
        <?php if (!empty($userTenants) && count($userTenants) > 1): ?>
        <div class="pt-2 border-t border-slate-100">
            <p class="text-xs font-semibold text-slate-700 mb-2">Switch to another workspace:</p>
            <form method="POST" action="<?= url('/admin/switch-workspace') ?>" class="flex items-center gap-2">
                <?= csrf_field() ?>
                <select name="tenant_id" class="flex-1 px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500">
                    <?php foreach ($userTenants as $ut): ?>
                        <option value="<?= (int)$ut['id'] ?>" <?= (int)$ut['id'] === (int)$tenant['id'] ? 'disabled' : '' ?>>
                            <?= e($ut['name']) ?> (<?= ucfirst(e($ut['status'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-lg transition-colors">
                    Switch
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Footer Actions -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <a href="<?= url('/admin/billing') ?>" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Renew / Upgrade Plan Now
                </a>
                <a href="mailto:billing@qfunnel.local?subject=Reactivation%20Request%20for%20Workspace%20<?= urlencode($tenant['name']) ?>" 
                   class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg transition-colors flex items-center gap-1">
                    Support
                </a>
            </div>

            <a href="<?= url('/logout') ?>" class="text-xs font-semibold text-rose-600 hover:text-rose-800 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Sign Out
            </a>
        </div>
    </div>
</div>
