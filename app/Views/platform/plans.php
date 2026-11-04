<?php
$featureCatalog = $featureCatalog ?? \App\Services\UsageLimitService::getFeatureCatalog();
$groupedFeatures = [];
foreach ($featureCatalog as $key => $meta) {
    $cat = $meta['category'] ?? 'General';
    $groupedFeatures[$cat][$key] = $meta;
}
?>

<div class="space-y-6">

    <!-- Page Header & Create Plan Trigger -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Subscription Plans & Feature Management</h2>
            <p class="text-xs text-slate-500 mt-0.5">Define, edit, and configure pricing tiers, resource quotas, and system/premium features for all users.</p>
        </div>
        <button type="button" onclick="openCreatePlanModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-2 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create New Plan
        </button>
    </div>

    <!-- Core Tier Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($plans as $p): 
            $limits = json_decode($p['limits_json'] ?? '{}', true) ?: [];
            $features = json_decode($p['features_json'] ?? '{}', true) ?: [];
            $maxFunnels = $limits['max_funnels'] ?? 1;
            $funnelsLabel = ($maxFunnels <= 0 || $maxFunnels == -1) ? 'Unlimited Funnels' : "{$maxFunnels} " . ($maxFunnels == 1 ? 'Funnel' : 'Funnels');
            $isPro = in_array(strtolower($p['slug']), ['pro', 'enterprise'], true);
            $activeFeatCount = 0;
            $premiumFeatCount = 0;
            foreach ($featureCatalog as $fKey => $fMeta) {
                if (!empty($features[$fKey]) || ($isPro && !isset($features[$fKey]))) {
                    $activeFeatCount++;
                    if ($fMeta['is_premium']) $premiumFeatCount++;
                }
            }
        ?>
            <div class="bg-white border rounded-xl p-5 shadow-sm flex flex-col justify-between transition-all hover:shadow-md <?= $isPro ? 'border-purple-300 ring-2 ring-purple-100' : 'border-[#E2E8F0]' ?>">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-extrabold text-slate-900"><?= e($p['name']) ?></span>
                        <div class="flex items-center gap-1 flex-wrap justify-end">
                            <?php if ($isPro): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700">★ POPULAR</span>
                            <?php endif; ?>
                            <?php if ($p['is_active']): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500">Disabled</span>
                            <?php endif; ?>
                            <?php if (!empty($p['is_visible'])): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200" title="Visible on frontend home page">👁 Frontend</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200" title="Hidden from frontend home page">🚫 Hidden</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-bold text-slate-900">₹<?= number_format((float)$p['price'], 2) ?></span>
                            <span class="text-xs text-slate-400">/<?= e($p['billing_interval'] ?? 'monthly') ?></span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 line-clamp-2"><?= e($p['description'] ?: 'Configured platform subscription tier') ?></p>
                    </div>

                    <!-- Quota Highlights -->
                    <div class="space-y-2 py-3 border-t border-slate-100 text-xs">
                        <div class="flex items-center justify-between text-slate-700">
                            <span class="text-slate-500">Funnel Limit:</span>
                            <strong class="font-bold text-indigo-600"><?= $funnelsLabel ?></strong>
                        </div>
                        <div class="flex items-center justify-between text-slate-700">
                            <span class="text-slate-500">Monthly Leads:</span>
                            <span class="font-semibold"><?= ($limits['max_submissions_per_month'] ?? 100) <= 0 ? 'Unlimited' : number_format($limits['max_submissions_per_month'] ?? 100) ?></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-700">
                            <span class="text-slate-500">Team Seats:</span>
                            <span class="font-semibold"><?= ($limits['max_team_members'] ?? 1) <= 0 ? 'Unlimited' : ($limits['max_team_members'] ?? 1) ?></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-700 pt-1 border-t border-dashed border-slate-200">
                            <span class="text-slate-500">Features Unlocked:</span>
                            <div class="flex items-center gap-1">
                                <span class="font-semibold text-slate-800"><?= $activeFeatCount ?></span>
                                <?php if ($premiumFeatCount > 0): ?>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        <?= $premiumFeatCount ?> Pro
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400 font-medium">
                        <?= (int)$p['active_subscribers_count'] ?> active <?= (int)$p['active_subscribers_count'] === 1 ? 'tenant' : 'tenants' ?>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <form method="POST" action="<?= url("/admin/platform/plans/{$p['id']}/toggle-visibility") ?>" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="px-2 py-1 text-[11px] font-semibold rounded <?= !empty($p['is_visible']) ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' ?>" title="<?= !empty($p['is_visible']) ? 'Click to hide this plan from frontend users' : 'Click to make this plan visible on frontend' ?>">
                                <?= !empty($p['is_visible']) ? 'Hide' : 'Show' ?>
                            </button>
                        </form>
                        <button type="button" 
                                onclick='openEditPlanModal(<?= json_encode($p) ?>)' 
                                class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Detailed Plans Table -->
    <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E2E8F0] flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">All Subscription Tiers</h3>
                <p class="text-xs text-slate-500">Overview of configured limits and system capabilities per tier.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-[11px] text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200 font-semibold">
                    ★ = Premium Feature
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#E2E8F0] bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Plan Name & Slug</th>
                        <th class="py-3 px-4">Price</th>
                        <th class="py-3 px-4">Funnels Limit</th>
                        <th class="py-3 px-4">Submissions Limit</th>
                        <th class="py-3 px-4">Enabled Features & Premium Capabilities</th>
                        <th class="py-3 px-4 text-center">Active Tenants</th>
                        <th class="py-3 px-4">System Status</th>
                        <th class="py-3 px-4 text-center">Frontend Visibility</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2E8F0]">
                    <?php foreach ($plans as $p): 
                        $limits = json_decode($p['limits_json'] ?? '{}', true) ?: [];
                        $features = json_decode($p['features_json'] ?? '{}', true) ?: [];
                        $maxFunnels = $limits['max_funnels'] ?? 1;
                        $funnelsLabel = ($maxFunnels <= 0 || $maxFunnels == -1) ? 'Unlimited' : "{$maxFunnels} " . ($maxFunnels == 1 ? 'Funnel' : 'Funnels');
                        $isPro = in_array(strtolower($p['slug']), ['pro', 'enterprise'], true);
                    ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                <?= e($p['name']) ?>
                                <span class="block text-[10px] text-slate-400 font-mono">slug: <?= e($p['slug']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                ₹<?= number_format((float)$p['price'], 2) ?>
                                <span class="text-[10px] font-normal text-slate-400">/<?= e($p['billing_interval']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-indigo-600">
                                <?= $funnelsLabel ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700">
                                <?= ($limits['max_submissions_per_month'] ?? 100) <= 0 ? 'Unlimited' : number_format($limits['max_submissions_per_month'] ?? 100) . '/mo' ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <div class="flex flex-wrap gap-1 max-w-md">
                                    <?php 
                                    $enabledCount = 0;
                                    foreach ($featureCatalog as $fKey => $fMeta):
                                        $isEnabled = !empty($features[$fKey]) || ($isPro && !isset($features[$fKey]));
                                        if ($isEnabled):
                                            $enabledCount++;
                                            if ($fMeta['is_premium']): ?>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200" title="<?= e($fMeta['description']) ?>">
                                                    ★ <?= e($fMeta['name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600" title="<?= e($fMeta['description']) ?>">
                                                    <?= e($fMeta['name']) ?>
                                                </span>
                                            <?php endif;
                                        endif;
                                    endforeach; 
                                    if ($enabledCount === 0): ?>
                                        <span class="text-[10px] text-slate-400 italic">No features enabled</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-800">
                                <?= (int)$p['active_subscribers_count'] ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ($p['is_active']): ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <?php if (!empty($p['is_visible'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Visible
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                            Hidden
                                        </span>
                                    <?php endif; ?>

                                    <form method="POST" action="<?= url("/admin/platform/plans/{$p['id']}/toggle-visibility") ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-2 py-0.5 text-[10px] font-semibold rounded <?= !empty($p['is_visible']) ? 'bg-amber-100 hover:bg-amber-200 text-amber-800' : 'bg-emerald-100 hover:bg-emerald-200 text-emerald-800' ?>" title="<?= !empty($p['is_visible']) ? 'Hide this plan from public frontend' : 'Show this plan on public frontend' ?>">
                                            <?= !empty($p['is_visible']) ? 'Hide' : 'Show' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" 
                                            onclick='openEditPlanModal(<?= json_encode($p) ?>)' 
                                            class="px-2.5 py-1 text-xs font-semibold rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors">
                                        Edit
                                    </button>
                                    <form method="POST" action="<?= url("/admin/platform/plans/{$p['id']}/delete") ?>" class="inline" onsubmit="return confirm('Are you sure you want to remove or deactivate plan \'<?= e(addslashes($p['name'])) ?>\'?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Create New Plan -->
    <div id="createPlanModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full overflow-hidden border border-[#E2E8F0]">
            <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Create New Subscription Plan</h3>
                    <p class="text-xs text-slate-500">Configure plan parameters, usage quotas, and full system features.</p>
                </div>
                <button type="button" onclick="closeCreatePlanModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="<?= url('/admin/platform/plans/create') ?>" class="p-6 space-y-5 max-h-[82vh] overflow-y-auto">
                <?= csrf_field() ?>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Plan Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Agency Pro" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Slug (Identifier)</label>
                        <input type="text" name="slug" placeholder="e.g. agency-pro (auto-generated if empty)" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Price (₹ INR) *</label>
                        <input type="number" step="0.01" name="price" value="0.00" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Billing Interval</label>
                        <select name="billing_interval" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                <!-- Limits Quota -->
                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-3">
                    <span class="block text-xs font-bold text-slate-800">Resource Quotas & Limits (-1 = Unlimited)</span>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Funnels Limit</label>
                            <input type="number" name="max_funnels" value="5" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Monthly Leads</label>
                            <input type="number" name="max_submissions_per_month" value="1000" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Team Members</label>
                            <input type="number" name="max_team_members" value="3" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 bg-white">
                        </div>
                    </div>
                </div>

                <!-- Features Checkboxes Categorized with Presets -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200">
                        <div>
                            <label class="block text-xs font-bold text-slate-800">System Features & Premium Capabilities</label>
                            <p class="text-[11px] text-slate-500">Enable or disable specific platform modules and premium capabilities for subscribers.</p>
                        </div>
                        <!-- Presets Toolbar -->
                        <div class="flex items-center gap-1.5 text-[11px] flex-wrap">
                            <span class="text-slate-400 font-medium mr-1">Quick Presets:</span>
                            <button type="button" onclick="applyFeaturePreset('create', 'free')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition-colors">Free</button>
                            <button type="button" onclick="applyFeaturePreset('create', 'starter')" class="px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold transition-colors">Starter</button>
                            <button type="button" onclick="applyFeaturePreset('create', 'standard')" class="px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold transition-colors">Standard</button>
                            <button type="button" onclick="applyFeaturePreset('create', 'pro')" class="px-2 py-0.5 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold transition-colors">Pro / All</button>
                            <button type="button" onclick="applyFeaturePreset('create', 'none')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 font-semibold transition-colors">Clear All</button>
                        </div>
                    </div>

                    <?php foreach ($groupedFeatures as $catName => $catFeatures): ?>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <?= e($catName) ?>
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white border border-slate-200 text-slate-500 font-normal"><?= count($catFeatures) ?></span>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                <?php foreach ($catFeatures as $fKey => $fMeta): ?>
                                    <label class="relative flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/20 cursor-pointer transition-colors shadow-2xs">
                                        <input type="checkbox" name="feature_<?= $fKey ?>" id="create_feat_<?= $fKey ?>" value="1" <?= !$fMeta['is_premium'] ? 'checked' : '' ?> class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                                <span class="text-xs font-semibold text-slate-800 leading-tight truncate"><?= e($fMeta['name']) ?></span>
                                                <?php if ($fMeta['is_premium']): ?>
                                                    <span class="shrink-0 px-1.5 py-0.2 text-[9px] font-bold uppercase rounded bg-purple-100 text-purple-700 border border-purple-200">★ PRO</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-[10px] text-slate-500 leading-snug line-clamp-2"><?= e($fMeta['description']) ?></p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Brief overview of plan target audience and positioning..." class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                </div>

                <div class="space-y-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="create_is_active" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="create_is_active" class="text-xs font-medium text-slate-700">Make this plan active for tenant subscriptions</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_visible" id="create_is_visible" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="create_is_visible" class="text-xs font-medium text-slate-700"><strong>Show on Frontend:</strong> Visible to visitors on public home page pricing section</label>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreatePlanModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition-colors">
                        Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Existing Plan -->
    <div id="editPlanModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full overflow-hidden border border-[#E2E8F0]">
            <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Plan: <span id="editPlanModalTitle" class="text-indigo-600"></span></h3>
                    <p class="text-xs text-slate-500">Update pricing, limits, and enabled features for this tier.</p>
                </div>
                <button type="button" onclick="closeEditPlanModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="editPlanForm" method="POST" action="" class="p-6 space-y-5 max-h-[82vh] overflow-y-auto">
                <?= csrf_field() ?>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Plan Name *</label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Slug (Identifier)</label>
                        <input type="text" id="edit_slug" readonly class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-600 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Price (₹ INR) *</label>
                        <input type="number" step="0.01" name="price" id="edit_price" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Billing Interval</label>
                        <select name="billing_interval" id="edit_interval" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                <!-- Limits Quota -->
                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-3">
                    <span class="block text-xs font-bold text-slate-800">Resource Quotas & Limits (-1 = Unlimited)</span>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Funnels Limit</label>
                            <input type="number" name="max_funnels" id="edit_max_funnels" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Monthly Leads</label>
                            <input type="number" name="max_submissions_per_month" id="edit_max_submissions" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Team Members</label>
                            <input type="number" name="max_team_members" id="edit_max_team_members" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 bg-white">
                        </div>
                    </div>
                </div>

                <!-- Features Checkboxes Categorized with Presets -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200">
                        <div>
                            <label class="block text-xs font-bold text-slate-800">System Features & Premium Capabilities</label>
                            <p class="text-[11px] text-slate-500">Toggle enabled features and premium perks for this subscription tier.</p>
                        </div>
                        <!-- Presets Toolbar -->
                        <div class="flex items-center gap-1.5 text-[11px] flex-wrap">
                            <span class="text-slate-400 font-medium mr-1">Quick Presets:</span>
                            <button type="button" onclick="applyFeaturePreset('edit', 'free')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition-colors">Free</button>
                            <button type="button" onclick="applyFeaturePreset('edit', 'starter')" class="px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold transition-colors">Starter</button>
                            <button type="button" onclick="applyFeaturePreset('edit', 'standard')" class="px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold transition-colors">Standard</button>
                            <button type="button" onclick="applyFeaturePreset('edit', 'pro')" class="px-2 py-0.5 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold transition-colors">Pro / All</button>
                            <button type="button" onclick="applyFeaturePreset('edit', 'none')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 font-semibold transition-colors">Clear All</button>
                        </div>
                    </div>

                    <?php foreach ($groupedFeatures as $catName => $catFeatures): ?>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <?= e($catName) ?>
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white border border-slate-200 text-slate-500 font-normal"><?= count($catFeatures) ?></span>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                <?php foreach ($catFeatures as $fKey => $fMeta): ?>
                                    <label class="relative flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/20 cursor-pointer transition-colors shadow-2xs">
                                        <input type="checkbox" name="feature_<?= $fKey ?>" id="edit_feat_<?= $fKey ?>" value="1" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                                <span class="text-xs font-semibold text-slate-800 leading-tight truncate"><?= e($fMeta['name']) ?></span>
                                                <?php if ($fMeta['is_premium']): ?>
                                                    <span class="shrink-0 px-1.5 py-0.2 text-[9px] font-bold uppercase rounded bg-purple-100 text-purple-700 border border-purple-200">★ PRO</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-[10px] text-slate-500 leading-snug line-clamp-2"><?= e($fMeta['description']) ?></p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" id="edit_description" rows="2" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                </div>

                <div class="space-y-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded border-slate-300 text-indigo-600">
                        <label for="edit_is_active" class="text-xs font-medium text-slate-700">Plan is Active for subscriptions</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_visible" id="edit_is_visible" value="1" class="rounded border-slate-300 text-indigo-600">
                        <label for="edit_is_visible" class="text-xs font-medium text-slate-700"><strong>Show on Frontend:</strong> Visible to visitors on public home page pricing section</label>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditPlanModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
const featureCatalog = <?= json_encode($featureCatalog) ?>;

function openCreatePlanModal() {
    document.getElementById('createPlanModal').classList.remove('hidden');
}
function closeCreatePlanModal() {
    document.getElementById('createPlanModal').classList.add('hidden');
}

function openEditPlanModal(plan) {
    const modal = document.getElementById('editPlanModal');
    const form = document.getElementById('editPlanForm');
    form.action = '<?= url("/admin/platform/plans") ?>/' + plan.id + '/update';

    document.getElementById('editPlanModalTitle').textContent = plan.name;
    document.getElementById('edit_name').value = plan.name;
    document.getElementById('edit_slug').value = plan.slug;
    document.getElementById('edit_price').value = plan.price || '0.00';
    document.getElementById('edit_interval').value = plan.billing_interval || 'monthly';
    document.getElementById('edit_description').value = plan.description || '';
    document.getElementById('edit_is_active').checked = plan.is_active == 1;
    document.getElementById('edit_is_visible').checked = plan.is_visible == 1;

    let limits = {};
    try {
        limits = typeof plan.limits_json === 'string' ? JSON.parse(plan.limits_json) : (plan.limits_json || {});
    } catch (e) {}

    document.getElementById('edit_max_funnels').value = limits.max_funnels !== undefined ? limits.max_funnels : 1;
    document.getElementById('edit_max_submissions').value = limits.max_submissions_per_month !== undefined ? limits.max_submissions_per_month : 100;
    document.getElementById('edit_max_team_members').value = limits.max_team_members !== undefined ? limits.max_team_members : 1;

    let features = {};
    try {
        features = typeof plan.features_json === 'string' ? JSON.parse(plan.features_json) : (plan.features_json || {});
    } catch (e) {}

    const isAllInclusive = ['pro', 'enterprise'].includes((plan.slug || '').toLowerCase());

    for (const key in featureCatalog) {
        const el = document.getElementById('edit_feat_' + key);
        if (el) {
            if (features[key] !== undefined) {
                el.checked = !!features[key];
            } else if (isAllInclusive) {
                el.checked = true;
            } else {
                el.checked = !featureCatalog[key].is_premium;
            }
        }
    }

    modal.classList.remove('hidden');
}

function closeEditPlanModal() {
    document.getElementById('editPlanModal').classList.add('hidden');
}

function applyFeaturePreset(formPrefix, preset) {
    const freeKeys = ['visual_builder', 'conditional_logic', 'lead_capture_forms', 'dynamic_scoring', 'back_navigation', 'analytics', 'journey_inspector'];
    const starterKeys = [...freeKeys, 'calculator_mode', 'quick_connect', 'custom_branding', 'custom_footer', 'auto_redirect', 'embed_widgets'];
    const standardKeys = [...starterKeys, 'white_label', 'custom_trust_badges', 'csv_export', 'step_dropoff_analytics', 'webhooks', 'team_collaboration'];

    for (const key in featureCatalog) {
        const el = document.getElementById(formPrefix + '_feat_' + key);
        if (!el) continue;

        if (preset === 'all' || preset === 'pro') {
            el.checked = true;
        } else if (preset === 'none') {
            el.checked = false;
        } else if (preset === 'free') {
            el.checked = freeKeys.includes(key);
        } else if (preset === 'starter') {
            el.checked = starterKeys.includes(key);
        } else if (preset === 'standard') {
            el.checked = standardKeys.includes(key);
        }
    }
}
</script>
