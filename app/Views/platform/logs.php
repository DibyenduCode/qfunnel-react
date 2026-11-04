<?php
$logItems = $logs['items'] ?? $logs;
$totalLogsCount = $logs['total'] ?? count($logItems);
?>
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Platform System Audit Trail</h2>
            <p class="text-xs text-slate-500 mt-0.5">Chronological record of administrative events, plan changes, and security actions.</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200">
            <?= number_format($totalLogsCount) ?> Recorded Events
        </span>
    </div>

    <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#E2E8F0] bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Workspace / Scope</th>
                        <th class="py-3 px-4">Operator</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Target Resource</th>
                        <th class="py-3 px-4">Metadata Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2E8F0]">
                    <?php if (empty($logItems)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No audit records logged yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($logItems as $l): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap font-mono text-[11px]">
                                <?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-800">
                                <?= e($l['tenant_name'] ?: 'Platform Scope') ?>
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                <span class="font-medium"><?= e($l['user_name'] ?: 'System') ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded text-[11px] border border-indigo-200">
                                    <?= e($l['action']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <?= e($l['resource_type']) ?> #<?= (int)$l['resource_id'] ?>
                            </td>
                            <td class="py-3 px-4 font-mono text-[10px] text-slate-500 max-w-sm truncate" title="<?= e($l['metadata_json'] ?? '') ?>">
                                <?= e($l['metadata_json'] ?: '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($logs['total_pages'])): ?>
            <?php
            $pagination = $logs;
            require __DIR__ . '/../partials/pagination.php';
            ?>
        <?php endif; ?>
    </div>

</div>
