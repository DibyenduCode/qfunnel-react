<?php
$currentPlanId = (int)($subscription['plan_id'] ?? 1);
$currentPlanSlug = strtolower($subscription['plan_slug'] ?? 'starter');
$currentStatus = $subscription['status'] ?? 'trial';
$currentPlanPrice = (float)($subscription['plan_price'] ?? 0);
$periodEnd = !empty($subscription['current_period_end']) ? $subscription['current_period_end'] : ($subscription['ends_at'] ?? null);
?>

<div class="space-y-8 max-w-6xl mx-auto">

    <!-- Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Subscription Plans & Billing</h1>
            <p class="text-xs text-slate-500 mt-0.5">Upgrade or switch your subscription tier to unlock more funnels, monthly leads, and premium features.</p>
        </div>

        <a href="<?= url('/admin/settings/workspace') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1.5 self-start sm:self-auto">
            ← Back to Workspace Settings
        </a>
    </div>

    <!-- Current Plan & Quota Usage Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-lg border border-slate-800">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-white/10">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">Your Current Workspace Tier</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase <?= $currentStatus === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' ?>">
                        <?= ucfirst(e($currentStatus)) ?>
                    </span>
                </div>
                <h2 class="text-2xl font-extrabold tracking-tight text-white flex items-center gap-2">
                    <?= e($usage['plan_name'] ?: 'Starter') ?> Plan
                    <span class="text-xs font-normal text-slate-300">
                        (₹<?= number_format((float)($subscription['amount'] ?: ($subscription['plan_price'] ?? 0)), 2) ?> / <?= e($subscription['billing_interval'] ?? 'monthly') ?>)
                    </span>
                </h2>
                <?php if ($periodEnd): ?>
                    <p class="text-xs text-slate-400">
                        Renewal / Expiry Date: <strong class="text-slate-200"><?= date('F d, Y', strtotime($periodEnd)) ?></strong>
                        <?php if ($billing['days_remaining'] > 0): ?>
                            (<?= $billing['days_remaining'] ?> days remaining)
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-3">
                <a href="#plansComparison" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold text-xs shadow-md shadow-indigo-500/30 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Upgrade Plan
                </a>
            </div>
        </div>

        <!-- Quotas Progress Bars -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-6 text-xs">
            <div class="p-3.5 bg-white/5 rounded-xl border border-white/10 space-y-2">
                <div class="flex items-center justify-between text-slate-300">
                    <span class="font-medium">Active Funnels</span>
                    <strong class="text-white"><?= $usage['funnels']['used'] ?> / <?= $usage['funnels']['is_unlimited'] ? 'Unlimited' : $usage['funnels']['limit'] ?></strong>
                </div>
                <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-indigo-400 h-full rounded-full" style="width: <?= $usage['funnels']['percentage'] ?>%"></div>
                </div>
            </div>

            <div class="p-3.5 bg-white/5 rounded-xl border border-white/10 space-y-2">
                <div class="flex items-center justify-between text-slate-300">
                    <span class="font-medium">Monthly Leads Captured</span>
                    <strong class="text-white"><?= number_format($usage['submissions']['used']) ?> / <?= $usage['submissions']['is_unlimited'] ? 'Unlimited' : number_format($usage['submissions']['limit']) ?></strong>
                </div>
                <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-emerald-400 h-full rounded-full" style="width: <?= $usage['submissions']['percentage'] ?>%"></div>
                </div>
            </div>

            <div class="p-3.5 bg-white/5 rounded-xl border border-white/10 space-y-2">
                <div class="flex items-center justify-between text-slate-300">
                    <span class="font-medium">Team Member Seats</span>
                    <strong class="text-white"><?= $usage['team_members']['used'] ?> / <?= $usage['team_members']['is_unlimited'] ? 'Unlimited' : $usage['team_members']['limit'] ?></strong>
                </div>
                <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-purple-400 h-full rounded-full" style="width: <?= $usage['team_members']['percentage'] ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Plans Grid -->
    <div id="plansComparison" class="space-y-6 pt-4">
        <div class="text-center max-w-xl mx-auto space-y-2">
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Available Subscription Plans</h2>
            <p class="text-xs text-slate-500">Pick the best plan tailored for creators, growing businesses, or high-volume agencies.</p>
            <div class="pt-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Upgrades Only: You can upgrade to a higher tier plan anytime. Downgrades are not permitted.
                </span>
            </div>
        </div>

        <!-- Plans Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($plans as $p): 
                $pLimits = json_decode($p['limits_json'] ?? '{}', true) ?: [];
                $pFeatures = json_decode($p['features_json'] ?? '{}', true) ?: [];
                $planPrice = (float)$p['price'];
                $isCurrentPlan = ((int)$p['id'] === $currentPlanId);
                $isDowngrade = (!$isCurrentPlan && $planPrice < $currentPlanPrice);
                $isUpgrade = (!$isCurrentPlan && $planPrice > $currentPlanPrice);
                $isPro = in_array(strtolower($p['slug']), ['pro', 'agency', 'enterprise'], true);
                $maxFunnels = $pLimits['max_funnels'] ?? 1;
                $funnelsText = ($maxFunnels <= 0 || $maxFunnels == -1) ? 'Unlimited Funnels' : "{$maxFunnels} " . ($maxFunnels == 1 ? 'Funnel' : 'Funnels');
                $maxSubmissions = $pLimits['max_submissions_per_month'] ?? 100;
                $submissionsText = ($maxSubmissions <= 0 || $maxSubmissions == -1) ? 'Unlimited Monthly Leads' : number_format($maxSubmissions) . ' Leads / mo';
                $maxSeats = $pLimits['max_team_members'] ?? 1;
                $seatsText = ($maxSeats <= 0 || $maxSeats == -1) ? 'Unlimited Team Seats' : "{$maxSeats} Team " . ($maxSeats == 1 ? 'Seat' : 'Seats');
            ?>
                <div class="bg-white border rounded-2xl p-6 shadow-sm flex flex-col justify-between transition-all relative <?= $isCurrentPlan ? 'border-indigo-500 ring-2 ring-indigo-200' : ($isDowngrade ? 'border-slate-200 opacity-60 bg-slate-50/50' : ($isPro ? 'border-purple-300 hover:shadow-lg' : 'border-slate-200 hover:shadow-lg')) ?>">
                    
                    <?php if ($isCurrentPlan): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-indigo-600 text-white shadow-sm">
                            Current Active Plan
                        </div>
                    <?php elseif ($isDowngrade): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-200 text-slate-500 border border-slate-300">
                            Lower Tier
                        </div>
                    <?php elseif ($isPro): ?>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-sm">
                            ★ Most Popular
                        </div>
                    <?php endif; ?>

                    <div>
                        <!-- Title & Pricing -->
                        <div class="mb-4">
                            <h3 class="text-base font-extrabold text-slate-900"><?= e($p['name']) ?></h3>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2 min-h-[32px]"><?= e($p['description'] ?: 'Complete lead generation suite') ?></p>
                            
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-3xl font-extrabold text-slate-900">₹<?= number_format((float)$p['price'], 2) ?></span>
                                <span class="text-xs text-slate-400 font-medium">/<?= e($p['billing_interval'] ?? 'monthly') ?></span>
                            </div>
                        </div>

                        <!-- Core Quota Highlights -->
                        <div class="py-3 border-y border-slate-100 text-xs space-y-2">
                            <div class="flex items-center gap-2 font-semibold text-slate-800">
                                <span class="text-indigo-600">✓</span>
                                <span><?= $funnelsText ?></span>
                            </div>
                            <div class="flex items-center gap-2 font-semibold text-slate-800">
                                <span class="text-indigo-600">✓</span>
                                <span><?= $submissionsText ?></span>
                            </div>
                            <div class="flex items-center gap-2 font-semibold text-slate-800">
                                <span class="text-indigo-600">✓</span>
                                <span><?= $seatsText ?></span>
                            </div>
                        </div>

                        <!-- Key Feature Highlights -->
                        <div class="py-4 text-[11px] space-y-2.5 text-slate-600">
                            <div class="flex items-center gap-2">
                                <span class="<?= !empty($pFeatures['calculator_mode']) || $isPro ? 'text-emerald-500 font-bold' : 'text-slate-300' ?>">
                                    <?= !empty($pFeatures['calculator_mode']) || $isPro ? '✓' : '✗' ?>
                                </span>
                                <span class="<?= !empty($pFeatures['calculator_mode']) || $isPro ? 'text-slate-700 font-medium' : 'text-slate-400 line-through' ?>">
                                    Calculator & Quote Formulas
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="<?= !empty($pFeatures['white_label']) || $isPro ? 'text-emerald-500 font-bold' : 'text-slate-300' ?>">
                                    <?= !empty($pFeatures['white_label']) || $isPro ? '✓' : '✗' ?>
                                </span>
                                <span class="<?= !empty($pFeatures['white_label']) || $isPro ? 'text-slate-700 font-medium' : 'text-slate-400 line-through' ?>">
                                    100% White-Label (No Branding)
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="<?= !empty($pFeatures['quick_connect']) || $isPro ? 'text-emerald-500 font-bold' : 'text-slate-300' ?>">
                                    <?= !empty($pFeatures['quick_connect']) || $isPro ? '✓' : '✗' ?>
                                </span>
                                <span class="<?= !empty($pFeatures['quick_connect']) || $isPro ? 'text-slate-700 font-medium' : 'text-slate-400 line-through' ?>">
                                    WhatsApp & Call Direct Connect
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="<?= !empty($pFeatures['csv_export']) || $isPro ? 'text-emerald-500 font-bold' : 'text-slate-300' ?>">
                                    <?= !empty($pFeatures['csv_export']) || $isPro ? '✓' : '✗' ?>
                                </span>
                                <span class="<?= !empty($pFeatures['csv_export']) || $isPro ? 'text-slate-700 font-medium' : 'text-slate-400 line-through' ?>">
                                    1-Click CSV Lead Export
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="<?= !empty($pFeatures['webhooks']) || $isPro ? 'text-emerald-500 font-bold' : 'text-slate-300' ?>">
                                    <?= !empty($pFeatures['webhooks']) || $isPro ? '✓' : '✗' ?>
                                </span>
                                <span class="<?= !empty($pFeatures['webhooks']) || $isPro ? 'text-slate-700 font-medium' : 'text-slate-400 line-through' ?>">
                                    Outbound Webhooks (Zapier)
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="<?= !empty($pFeatures['api_access']) || $isPro ? 'text-emerald-500 font-bold' : 'text-slate-300' ?>">
                                    <?= !empty($pFeatures['api_access']) || $isPro ? '✓' : '✗' ?>
                                </span>
                                <span class="<?= !empty($pFeatures['api_access']) || $isPro ? 'text-slate-700 font-medium' : 'text-slate-400 line-through' ?>">
                                    Developer REST API Access
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Upgrade Action Trigger -->
                    <div class="pt-4 border-t border-slate-100">
                        <?php if ($isCurrentPlan): ?>
                            <button type="button" disabled class="w-full py-2.5 rounded-xl bg-slate-100 text-slate-400 font-bold text-xs cursor-not-allowed">
                                Current Plan
                            </button>
                        <?php elseif ($isDowngrade): ?>
                            <button type="button" disabled class="w-full py-2.5 rounded-xl bg-slate-100 text-slate-400 font-semibold text-xs cursor-not-allowed flex items-center justify-center gap-1.5" title="Downgrading is not permitted. You can only upgrade to a higher tier plan.">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Downgrade Unavailable
                            </button>
                        <?php else: ?>
                            <button type="button" 
                                    onclick='openUpgradeModal(<?= json_encode($p) ?>)'
                                    class="w-full py-2.5 rounded-xl text-xs font-bold shadow-sm transition-all text-white <?= $isPro ? 'bg-purple-600 hover:bg-purple-500 shadow-purple-500/20' : 'bg-slate-900 hover:bg-slate-800' ?>">
                                Upgrade to <?= e($p['name']) ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Invoices & Payment History Card -->
    <div class="bg-white border border-[#E4E7EC] rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-[#E2E8F0] flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Invoices & Billing History</h3>
                <p class="text-xs text-slate-500">Record of your workspace subscription renewals and payments.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#E2E8F0] bg-slate-50/50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-5">Invoice #</th>
                        <th class="py-3 px-5">Amount</th>
                        <th class="py-3 px-5">Billing Period</th>
                        <th class="py-3 px-5">Payment Method</th>
                        <th class="py-3 px-5">Status</th>
                        <th class="py-3 px-5 text-right">Date Paid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2E8F0]">
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No billing invoices generated yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($invoices as $inv): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-5 font-mono font-bold text-indigo-600">
                                <?= e($inv['invoice_number']) ?>
                            </td>
                            <td class="py-3 px-5 font-bold text-slate-900">
                                ₹<?= number_format((float)$inv['amount'], 2) ?>
                            </td>
                            <td class="py-3 px-5 text-slate-600 capitalize">
                                <?= e($inv['billing_interval']) ?> 
                                <span class="text-[10px] text-slate-400 block">
                                    <?= date('M d', strtotime($inv['period_start'])) ?> - <?= date('M d, Y', strtotime($inv['period_end'])) ?>
                                </span>
                            </td>
                            <td class="py-3 px-5 capitalize text-slate-600">
                                <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px]">
                                    <?= str_replace('_', ' ', e($inv['payment_method'])) ?>
                                </span>
                            </td>
                            <td class="py-3 px-5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    ✓ Paid
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right font-mono text-slate-500">
                                <?= date('M d, Y', strtotime($inv['paid_at'] ?: $inv['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upgrade Plan Checkout Modal -->
    <div id="upgradeModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-[#E2E8F0]">
            <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Confirm Plan Upgrade
                </h3>
                <button type="button" onclick="closeUpgradeModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="<?= url('/admin/billing/upgrade') ?>" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="plan_id" id="modalTargetPlanId">

                <!-- Plan summary box -->
                <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-indigo-600 tracking-wider">Target Plan</span>
                        <h4 id="modalTargetPlanName" class="text-lg font-extrabold text-indigo-950">Pro Plan</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-bold text-slate-400">Total Price</span>
                        <div id="modalTargetPlanPrice" class="text-lg font-extrabold text-slate-900">₹99.00</div>
                    </div>
                </div>

                <!-- Billing Interval Selection -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Billing Interval</label>
                    <select name="billing_interval" id="modalBillingInterval" onchange="updateModalPrice()" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="monthly">Monthly Cycle</option>
                        <option value="yearly">Yearly Cycle (15% Annual Savings)</option>
                    </select>
                </div>

                <!-- Payment Method Selection -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method</label>
                    <select name="payment_method" id="modalPaymentMethod" onchange="togglePaymentInstructions()" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="direct_activation">Instant Activation / Corporate Direct</option>
                        <option value="upi">UPI / GPay / PhonePe</option>
                        <option value="bank_transfer">Direct Bank Transfer (NEFT / RTGS)</option>
                    </select>
                </div>

                <!-- Offline Payment Instructions Box -->
                <div id="offlineInstructionsBox" class="hidden p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
                    <p class="font-bold text-slate-800">Bank & UPI Payment Details:</p>
                    <p class="text-[11px] text-slate-600">Bank: <strong>HDFC Bank / State Bank of India</strong></p>
                    <p class="text-[11px] text-slate-600">Account: <strong>QuizFunnel Technologies Pvt Ltd</strong></p>
                    <p class="text-[11px] text-slate-600">UPI ID: <strong>billing@qfunnel.upi</strong></p>
                    
                    <div class="pt-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Transaction Ref / UTR Number</label>
                        <input type="text" name="transaction_reference" placeholder="Enter UTR or payment reference" class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white">
                    </div>
                </div>

                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[11px] text-emerald-800">
                    ✓ Your plan limits and newly unlocked features will be updated immediately upon confirmation.
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeUpgradeModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition-colors">
                        Confirm & Upgrade Now
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
let activeModalPlan = null;

function openUpgradeModal(plan) {
    activeModalPlan = plan;
    document.getElementById('modalTargetPlanId').value = plan.id;
    document.getElementById('modalTargetPlanName').textContent = plan.name;
    updateModalPrice();
    document.getElementById('upgradeModal').classList.remove('hidden');
}

function closeUpgradeModal() {
    document.getElementById('upgradeModal').classList.add('hidden');
}

function updateModalPrice() {
    if (!activeModalPlan) return;
    const interval = document.getElementById('modalBillingInterval').value;
    const basePrice = parseFloat(activeModalPlan.price) || 0;
    
    if (interval === 'yearly') {
        const annualPrice = (basePrice * 12 * 0.85).toFixed(2);
        document.getElementById('modalTargetPlanPrice').textContent = '₹' + annualPrice + ' / yr';
    } else {
        document.getElementById('modalTargetPlanPrice').textContent = '₹' + basePrice.toFixed(2) + ' / mo';
    }
}

function togglePaymentInstructions() {
    const method = document.getElementById('modalPaymentMethod').value;
    const box = document.getElementById('offlineInstructionsBox');
    if (method === 'upi' || method === 'bank_transfer') {
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}
</script>
