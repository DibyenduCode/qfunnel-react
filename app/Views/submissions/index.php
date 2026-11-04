<?php
$currentTab = $filters['tab'] ?? 'qualified';
$counts = $submissions['counts'] ?? ['all' => 0, 'qualified' => 0, 'abandoned' => 0];
$leadStages = $leadStages ?? \App\Services\LeadStageService::getStagesForTenant(\App\Services\TenantContextService::getActiveTenantId());
$stageMap = array_column($leadStages, null, 'slug');
?>

<div class="space-y-4">

    <!-- Top Agency Navigation Tabs & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E4E7EC] pb-3">
        <div class="flex items-center gap-2">
            <a href="<?= url('/admin/submissions?tab=qualified' . (!empty($filters['funnel_id']) ? '&funnel_id=' . $filters['funnel_id'] : '') . (!empty($filters['date_range']) ? '&date_range=' . $filters['date_range'] : '')) ?>" 
               class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $currentTab === 'qualified' ? 'bg-[#5B5BD6] text-white shadow-sm' : 'bg-white border border-[#E4E7EC] text-slate-700 hover:bg-[#F7F8FA]' ?>">
                <svg class="w-4 h-4 <?= $currentTab === 'qualified' ? 'text-white' : 'text-emerald-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Qualified Leads</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $currentTab === 'qualified' ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                    <?= number_format($counts['qualified']) ?>
                </span>
            </a>

            <a href="<?= url('/admin/submissions?tab=all' . (!empty($filters['funnel_id']) ? '&funnel_id=' . $filters['funnel_id'] : '') . (!empty($filters['date_range']) ? '&date_range=' . $filters['date_range'] : '')) ?>" 
               class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $currentTab === 'all' ? 'bg-[#5B5BD6] text-white shadow-sm' : 'bg-white border border-[#E4E7EC] text-slate-700 hover:bg-[#F7F8FA]' ?>">
                <svg class="w-4 h-4 <?= $currentTab === 'all' ? 'text-white' : 'text-slate-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>All Submissions</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $currentTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>">
                    <?= number_format($counts['all']) ?>
                </span>
            </a>

            <a href="<?= url('/admin/submissions?tab=abandoned' . (!empty($filters['funnel_id']) ? '&funnel_id=' . $filters['funnel_id'] : '') . (!empty($filters['date_range']) ? '&date_range=' . $filters['date_range'] : '')) ?>" 
               class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $currentTab === 'abandoned' ? 'bg-[#5B5BD6] text-white shadow-sm' : 'bg-white border border-[#E4E7EC] text-slate-700 hover:bg-[#F7F8FA]' ?>" title="Visitors who started but dropped before submitting lead contact info">
                <svg class="w-4 h-4 <?= $currentTab === 'abandoned' ? 'text-white' : 'text-amber-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Drop-offs</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $currentTab === 'abandoned' ? 'bg-white/20 text-white' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                    <?= number_format($counts['abandoned']) ?>
                </span>
            </a>
        </div>

        <!-- Right Side Utility Controls -->
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <!-- Manage Custom Lead Stages Button -->
            <button type="button" onclick="openStagesModal()" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-[#E4E7EC] bg-white text-xs font-bold text-slate-700 hover:bg-[#F7F8FA] hover:text-indigo-600 shadow-xs transition-colors">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                <span>Customize Lead Stages</span>
            </button>

            <!-- Export CSV Button -->
            <a href="<?= url('/admin/submissions/export?tab=' . $currentTab . (!empty($filters['funnel_id']) ? '&funnel_id=' . $filters['funnel_id'] : '') . (!empty($filters['date_range']) ? '&date_range=' . $filters['date_range'] : '')) ?>" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-[#E4E7EC] bg-white text-xs font-bold text-slate-700 hover:bg-[#F7F8FA] shadow-xs transition-colors">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <!-- Agency Filter Bar -->
    <div class="bg-white border border-[#E4E7EC] rounded-2xl p-4 shadow-xs">
        <form method="GET" action="<?= url('/admin/submissions') ?>" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="tab" value="<?= e($currentTab) ?>">

            <!-- Search box -->
            <div class="relative flex-1 min-w-[200px] max-w-sm">
                <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search by name, email, phone, or ID..."
                       class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-[#E4E7EC] text-xs text-[#171A21] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Funnel Filter -->
            <select name="funnel_id" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs font-semibold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                <option value="">All Funnels</option>
                <?php foreach ($funnels as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= ($filters['funnel_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Dynamic CRM Lead Stages Filter with Colorful Spheres -->
            <select name="lead_status" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs font-semibold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                <option value="">All Lead Stages</option>
                <?php foreach ($leadStages as $st): ?>
                    <?php $emoji = \App\Services\LeadStageService::getStageEmoji($st['slug'] ?? '', $st['color'] ?? ''); ?>
                    <option value="<?= e($st['slug']) ?>" <?= ($filters['lead_status'] ?? '') === $st['slug'] ? 'selected' : '' ?>>
                        <?= $emoji ?> <?= e($st['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Date Range Filter -->
            <select name="date_range" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-[#E4E7EC] text-xs font-semibold text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                <option value="">All Time</option>
                <option value="today" <?= ($filters['date_range'] ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="yesterday" <?= ($filters['date_range'] ?? '') === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                <option value="7days" <?= ($filters['date_range'] ?? '') === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="30days" <?= ($filters['date_range'] ?? '') === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition-colors">
                Filter
            </button>

            <?php if (!empty($filters['search']) || !empty($filters['funnel_id']) || !empty($filters['lead_status']) || !empty($filters['date_range'])): ?>
                <a href="<?= url('/admin/submissions?tab=' . $currentTab) ?>" class="text-xs font-semibold text-rose-600 hover:underline">
                    Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bulk Action Bar (Hidden until rows are selected) -->
    <div id="bulkActionBar" class="hidden bg-slate-900 text-white rounded-2xl p-3 px-5 shadow-lg flex flex-wrap items-center justify-between gap-3 animate-fade-in">
        <div class="flex items-center gap-3 text-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="font-bold"><span id="selectedCountText">0</span> leads selected</span>
        </div>

        <form id="bulkActionForm" method="POST" action="<?= url('/admin/submissions/bulk-action') ?>" class="flex flex-wrap items-center gap-2">
            <?= csrf_field() ?>
            <div id="bulkHiddenIdsContainer"></div>

            <select name="action" id="bulkActionSelect" class="px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 text-xs font-semibold text-white focus:outline-none">
                <option value="">Bulk Actions...</option>
                <?php foreach ($leadStages as $st): ?>
                    <?php $emoji = \App\Services\LeadStageService::getStageEmoji($st['slug'] ?? '', $st['color'] ?? ''); ?>
                    <option value="mark_<?= e($st['slug']) ?>">Move to: <?= $emoji ?> <?= e($st['name']) ?></option>
                <?php endforeach; ?>
                <option value="delete">Delete Selected</option>
            </select>

            <button type="submit" onclick="return confirmBulkAction();" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-bold text-xs text-white transition-colors">
                Apply Action
            </button>
            <button type="button" onclick="deselectAllRows();" class="px-3 py-1.5 text-xs text-slate-400 hover:text-white">
                Cancel
            </button>
        </form>
    </div>

    <!-- Submissions Table Card -->
    <div class="bg-white border border-[#E4E7EC] rounded-2xl shadow-xs overflow-hidden">
        <?php if (empty($submissions['items'])): ?>
            <div class="p-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-[#5B5BD6] flex items-center justify-center mx-auto mb-3 shadow-inner">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">No Leads Found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    <?= !empty($filters['search']) ? 'No leads matched your search query. Try broadening your filters.' : 'New submissions will appear here in real-time as prospects complete your funnels.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E4E7EC] bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-10 text-center">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                            </th>
                            <th class="py-3 px-4 min-w-[200px]">Lead & Contact</th>
                            <th class="py-3 px-4 min-w-[170px]">Funnel & Key Answers</th>
                            <th class="py-3 px-4 min-w-[110px]">Instant Quote</th>
                            <th class="py-3 px-4 min-w-[150px]">Lead Stage (CRM)</th>
                            <th class="py-3 px-4 min-w-[130px]">Destination</th>
                            <th class="py-3 px-4 min-w-[120px]">Time</th>
                            <th class="py-3 px-4 text-right min-w-[130px]">Quick Outreach</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4E7EC] text-xs">
                        <?php foreach ($submissions['items'] as $sub): 
                            $leadName = $sub['lead_name'] ?: (!empty($sub['lead_email']) ? explode('@', $sub['lead_email'])[0] : 'Visitor #' . $sub['id']);
                            $avatarLetter = strtoupper(substr($leadName, 0, 1));
                            $phone = $sub['lead_phone'] ?? '';
                            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                            $leadStatus = $sub['lead_status'] ?? 'new';
                            $answers = $sub['answers'] ?? [];

                            $stObj = $stageMap[$leadStatus] ?? [
                                'name' => ucfirst(str_replace('_', ' ', $leadStatus)),
                                'color' => '#6366F1'
                            ];
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <!-- Checkbox -->
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox" value="<?= $sub['id'] ?>" onchange="handleRowCheckboxChange()" class="row-checkbox w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                </td>

                                <!-- Lead Name & Contact Details -->
                                <td class="py-3 px-4">
                                    <div class="flex items-start gap-2.5">
                                        <!-- Avatar -->
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                            <?= $avatarLetter ?>
                                        </div>

                                        <div class="min-w-0 space-y-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <a href="<?= url("/admin/submissions/{$sub['id']}") ?>" class="font-bold text-slate-900 hover:text-indigo-600 truncate transition-colors">
                                                    <?= e($leadName) ?>
                                                </a>
                                                <span class="text-[10px] text-slate-400 font-mono">#<?= $sub['id'] ?></span>
                                            </div>

                                            <!-- Email -->
                                            <?php if (!empty($sub['lead_email'])): ?>
                                                <div class="flex items-center gap-1 text-[11px] text-slate-500 truncate">
                                                    <span class="truncate"><?= e($sub['lead_email']) ?></span>
                                                    <button type="button" onclick="copyText('<?= e($sub['lead_email']) ?>', this)" title="Copy email" class="text-slate-400 hover:text-slate-700 p-0.5">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-[10px] text-slate-400 italic block">No email provided</span>
                                            <?php endif; ?>

                                            <!-- Phone -->
                                            <?php if (!empty($phone)): ?>
                                                <div class="flex items-center gap-1 font-mono text-[11px] text-slate-700">
                                                    <span class="font-semibold"><?= e($phone) ?></span>
                                                    <button type="button" onclick="copyText('<?= e($cleanPhone) ?>', this)" title="Copy phone" class="text-slate-400 hover:text-slate-700 p-0.5">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Funnel & Key Qualification Answers -->
                                <td class="py-3 px-4">
                                    <div class="space-y-1.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            <?= e($sub['funnel_name']) ?>
                                        </span>

                                        <!-- Key answers previews -->
                                        <?php if (!empty($answers)): ?>
                                            <div class="flex flex-wrap gap-1 max-w-[220px]">
                                                <?php foreach (array_slice($answers, 0, 2) as $ans): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 truncate max-w-[200px]" 
                                                          title="<?= e($ans['question']) ?>: <?= e($ans['answer']) ?>">
                                                        <span class="text-indigo-400 font-bold">✓</span>
                                                        <span class="truncate"><?= e($ans['answer']) ?></span>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if (count($answers) > 2): ?>
                                                    <span class="text-[9px] text-slate-400 font-semibold self-center">+<?= count($answers) - 2 ?> more</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-[10px] text-slate-400 italic block">No answers yet</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Instant Quote / Calculator Value -->
                                <td class="py-3 px-4">
                                    <?php if (!empty($sub['formatted_quote'])): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-mono font-black bg-purple-50 text-purple-700 border border-purple-200 shadow-2xs">
                                            <?= e($sub['formatted_quote']) ?>
                                        </span>
                                    <?php elseif ($sub['calculated_value'] !== null): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-mono font-black bg-purple-50 text-purple-700 border border-purple-200 shadow-2xs">
                                            <?= number_format((float)$sub['calculated_value'], 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-300 font-mono">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Lead Stage (CRM Pipeline) -->
                                <td class="py-3 px-4">
                                    <div class="inline-flex items-center p-1 px-2 rounded-xl bg-slate-50 border border-slate-200 shadow-2xs">
                                        <select onchange="updateLeadStatusAjax(<?= $sub['id'] ?>, this.value, this)" 
                                                class="text-[11px] font-bold bg-transparent text-slate-800 cursor-pointer focus:outline-none">
                                            <?php foreach ($leadStages as $st): ?>
                                                <?php $emoji = \App\Services\LeadStageService::getStageEmoji($st['slug'] ?? '', $st['color'] ?? ''); ?>
                                                <option value="<?= e($st['slug']) ?>" <?= $leadStatus === $st['slug'] ? 'selected' : '' ?> data-color="<?= e($st['color']) ?>">
                                                    <?= $emoji ?> <?= e($st['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </td>

                                <!-- Destination Reached -->
                                <td class="py-3 px-4 text-slate-600 truncate max-w-[130px]">
                                    <span class="font-medium text-slate-800 block truncate" title="<?= e($sub['destination_name'] ?: 'None') ?>">
                                        <?= e($sub['destination_name'] ?: '—') ?>
                                    </span>
                                    <?php if ($sub['status'] === 'form_pending_video_gate'): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                            <span>🎬</span> Video Gate Pending
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-400 capitalize block">
                                            <?= e(str_replace('_', ' ', $sub['status'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Date & Time -->
                                <td class="py-3 px-4 text-slate-600">
                                    <span class="font-medium block text-slate-800">
                                        <?= date('M d, H:i', strtotime($sub['created_at'])) ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 block">
                                        <?= date('Y', strtotime($sub['created_at'])) ?>
                                    </span>
                                </td>

                                <!-- 1-Click Outreach Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <?php if (!empty($cleanPhone)): 
                                            $waMsg = rawurlencode("Hi " . $leadName . ", reaching out regarding your submission on " . $sub['funnel_name'] . "!");
                                        ?>
                                            <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waMsg ?>" 
                                               target="_blank" 
                                               title="Chat on WhatsApp" 
                                               class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 transition-colors shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-5.805 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                            </a>

                                            <a href="tel:<?= $cleanPhone ?>" 
                                               title="Call lead" 
                                               class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 transition-colors shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= url("/admin/submissions/{$sub['id']}") ?>" 
                                           title="Inspect Full Journey"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] hover:bg-slate-100 text-[11px] font-bold text-slate-700 transition-colors">
                                            <span>Inspect</span>
                                            <span>→</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php
            $pagination = $submissions;
            $queryParams = array_filter([
                'tab' => $currentTab,
                'funnel_id' => $filters['funnel_id'] ?? '',
                'lead_status' => $filters['lead_status'] ?? '',
                'date_range' => $filters['date_range'] ?? '',
                'search' => $filters['search'] ?? '',
            ]);
            require __DIR__ . '/../partials/pagination.php';
            ?>
        <?php endif; ?>
    </div>

</div>

<!-- Manage Custom Lead Stages Modal -->
<div id="stagesModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-[#E2E8F0] animate-scale-in">
        <div class="px-6 py-4 border-b border-[#E2E8F0] flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    Customize Lead Stages & Colors
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Define your agency pipeline stages with custom colors.</p>
            </div>
            <button type="button" onclick="closeStagesModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
            <!-- Existing Stages List -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">Active Lead Stages</label>
                <div class="space-y-2">
                    <?php foreach ($leadStages as $st): ?>
                        <div class="p-2.5 px-3 rounded-xl border border-slate-200 bg-white flex items-center justify-between shadow-2xs hover:border-slate-300 transition-colors">
                            <div class="flex items-center gap-2.5">
                                <?php $emoji = \App\Services\LeadStageService::getStageEmoji($st['slug'] ?? '', $st['color'] ?? ''); ?>
                                <span class="w-3.5 h-3.5 rounded-full shrink-0 shadow-2xs" style="background-color: <?= e($st['color']) ?>;"></span>
                                <span class="text-sm"><?= $emoji ?></span>
                                <div>
                                    <span class="text-xs font-bold text-slate-900"><?= e($st['name']) ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono ml-1.5">(<?= e($st['slug']) ?>)</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <?php if (!empty($st['is_system'])): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 uppercase tracking-wider">
                                        Default System
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 uppercase tracking-wider">
                                        Custom
                                    </span>
                                    <form method="POST" action="<?= url('/admin/submissions/stages/' . $st['id'] . '/delete') ?>" onsubmit="return confirm('Delete this custom stage? Any leads currently on this stage will be reset to New.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition-colors" title="Delete custom stage">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Create New Stage Form -->
            <div class="pt-4 border-t border-slate-200">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 mb-2">Create New Custom Stage</label>
                <form method="POST" action="<?= url('/admin/submissions/stages/create') ?>" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Stage Name</label>
                        <input type="text" name="name" required placeholder="e.g. Appointment Fixed, Proposal Sent, Onboarding..."
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Color Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Stage Color</label>
                        <div class="flex items-center gap-2 mb-2">
                            <input type="color" name="color" id="stageColorInput" value="#6366F1" onchange="syncColorPreview(this.value)" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-300 p-0.5 bg-white">
                            <input type="text" id="stageColorText" value="#6366F1" onchange="syncColorInput(this.value)" class="w-24 px-2 py-1 text-xs font-mono rounded-lg border border-slate-300">
                            
                            <div class="ml-auto inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold text-white shadow-2xs" id="stageBadgePreview" style="background-color: #6366F1;">
                                <span class="w-2 h-2 rounded-full bg-white/70"></span>
                                <span id="stageBadgeNamePreview">Live Preview</span>
                            </div>
                        </div>

                        <!-- Quick Color Swatches -->
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <?php 
                            $swatches = ['#6366F1', '#3B82F6', '#06B6D4', '#10B981', '#14B8A6', '#F59E0B', '#F97316', '#EF4444', '#EC4899', '#8B5CF6'];
                            foreach ($swatches as $c): ?>
                                <button type="button" onclick="selectSwatch('<?= $c ?>')" class="w-6 h-6 rounded-lg transition-transform hover:scale-110 shadow-2xs" style="background-color: <?= $c ?>;"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-sm transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Custom Stage
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Copy toast notification -->
<div id="copyToast" class="fixed bottom-6 right-6 hidden z-50 px-4 py-2 rounded-xl bg-slate-900 text-white font-bold text-xs shadow-xl flex items-center gap-2">
    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    <span id="copyToastText">Copied to clipboard!</span>
</div>

<script>
// Stages Modal Helpers
function openStagesModal() {
    document.getElementById('stagesModal').classList.remove('hidden');
}

function closeStagesModal() {
    document.getElementById('stagesModal').classList.add('hidden');
}

function selectSwatch(color) {
    document.getElementById('stageColorInput').value = color;
    document.getElementById('stageColorText').value = color;
    document.getElementById('stageBadgePreview').style.backgroundColor = color;
}

function syncColorPreview(color) {
    document.getElementById('stageColorText').value = color;
    document.getElementById('stageBadgePreview').style.backgroundColor = color;
}

function syncColorInput(color) {
    if (/^#([a-f0-9]{6}|[a-f0-9]{3})$/i.test(color)) {
        document.getElementById('stageColorInput').value = color;
        document.getElementById('stageBadgePreview').style.backgroundColor = color;
    }
}

// Bulk selection state
function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    handleRowCheckboxChange();
}

function handleRowCheckboxChange() {
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    const bar = document.getElementById('bulkActionBar');
    const countText = document.getElementById('selectedCountText');
    const container = document.getElementById('bulkHiddenIdsContainer');

    if (selected.length > 0) {
        bar.classList.remove('hidden');
        countText.textContent = selected.length;
        container.innerHTML = selected.map(id => `<input type="hidden" name="session_ids[]" value="${id}">`).join('');
    } else {
        bar.classList.add('hidden');
        document.getElementById('selectAllCheckbox').checked = false;
        container.innerHTML = '';
    }
}

function deselectAllRows() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
    handleRowCheckboxChange();
}

function confirmBulkAction() {
    const select = document.getElementById('bulkActionSelect');
    if (!select.value) {
        alert('Please choose a bulk action to apply.');
        return false;
    }
    if (select.value === 'delete') {
        return confirm('Are you sure you want to permanently delete these selected lead submissions? This action cannot be undone.');
    }
    return true;
}

// 1-Click Copy helper
function copyText(text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById('copyToast');
        document.getElementById('copyToastText').textContent = 'Copied: ' + text;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 2200);
    });
}

// Instant AJAX Lead Status Update
function updateLeadStatusAjax(sessionId, newStatus, selectElem) {
    const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value || '';
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const newColor = selectedOption.getAttribute('data-color') || '#6366F1';

    // Update color dot immediately
    const dot = document.getElementById('stageDot_' + sessionId);
    if (dot) {
        dot.style.backgroundColor = newColor;
    }

    fetch('<?= url('/admin/submissions/') ?>' + sessionId + '/status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            '_csrf_token': csrfToken,
            'lead_status': newStatus
        })
    }).then(res => res.json()).then(data => {
        if (data.success) {
            // Toast feedback
            const toast = document.getElementById('copyToast');
            document.getElementById('copyToastText').textContent = 'Lead #' + sessionId + ' moved to ' + selectedOption.textContent.trim();
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2000);
        }
    }).catch(err => {
        console.error('Failed to update lead status:', err);
    });
}
</script>
