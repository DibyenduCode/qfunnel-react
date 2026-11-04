<div class="space-y-6">

    <!-- Top Selector Bar -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-[#171A21]">Funnel Conversion & Drop-off Analytics</h2>
            <p class="text-xs text-[#687080]">Real performance metrics derived directly from visitor journey events</p>
        </div>

        <form method="GET" action="<?= url('/admin/analytics') ?>" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-[#687080]">Funnel:</label>
            <select name="funnel_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white font-medium">
                <?php foreach ($funnels as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= ($selectedFunnelId == $f['id']) ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($analytics)): ?>
        <div class="bg-white border border-[#E4E7EC] rounded-xl p-12 text-center shadow-sm">
            <p class="text-xs text-[#687080]">No funnels available to display analytics.</p>
        </div>
    <?php else: ?>
        <!-- Top Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
                <span class="text-xs font-medium text-[#687080] block mb-1">Total Views</span>
                <span class="text-2xl font-bold text-[#171A21]"><?= (int)$analytics['views'] ?></span>
            </div>

            <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
                <span class="text-xs font-medium text-[#687080] block mb-1">Pre-Form Starts</span>
                <span class="text-2xl font-bold text-[#171A21]"><?= (int)$analytics['form_starts'] ?></span>
                <span class="text-[11px] text-[#687080] block mt-1"><?= $analytics['lead_capture_rate'] ?>% capture</span>
            </div>

            <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
                <span class="text-xs font-medium text-[#687080] block mb-1">Quiz Starts</span>
                <span class="text-2xl font-bold text-[#171A21]"><?= (int)$analytics['quiz_starts'] ?></span>
            </div>

            <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
                <span class="text-xs font-medium text-[#687080] block mb-1">Completions</span>
                <span class="text-2xl font-bold text-[#171A21]"><?= (int)$analytics['completions'] ?></span>
            </div>

            <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
                <span class="text-xs font-medium text-[#687080] block mb-1">Completion Rate</span>
                <span class="text-2xl font-bold text-[#5B5BD6]"><?= $analytics['completion_rate'] ?>%</span>
            </div>

            <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
                <span class="text-xs font-medium text-[#687080] block mb-1">Lead Rate</span>
                <span class="text-2xl font-bold text-emerald-600"><?= $analytics['lead_capture_rate'] ?>%</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Question Step Progression & Drop-off -->
            <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm p-5 space-y-4">
                <h3 class="text-sm font-semibold text-[#171A21]">Question Step Progression</h3>
                <div class="space-y-4">
                    <?php if (empty($analytics['questions'])): ?>
                        <p class="text-xs text-[#687080]">No questions configured in this funnel.</p>
                    <?php else: ?>
                        <?php foreach ($analytics['questions'] as $idx => $q): ?>
                            <?php 
                                $percent = $analytics['quiz_starts'] > 0 ? min(100, round(($q['answers_count'] / $analytics['quiz_starts']) * 100)) : 0;
                            ?>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-[#171A21] truncate max-w-sm">
                                        <?= ($idx + 1) ?>. <?= e($q['question_text']) ?>
                                    </span>
                                    <span class="font-mono text-[#687080]"><?= (int)$q['answers_count'] ?> responses (<?= $percent ?>%)</span>
                                </div>
                                <div class="w-full bg-[#E4E7EC] h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-[#5B5BD6] h-full rounded-full transition-all" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Destination Landing Distribution -->
            <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm p-5 space-y-4">
                <h3 class="text-sm font-semibold text-[#171A21]">Final Destination Distribution</h3>
                <div class="space-y-3">
                    <?php if (empty($analytics['destinations'])): ?>
                        <p class="text-xs text-[#687080]">No destination nodes reached yet.</p>
                    <?php else: ?>
                        <?php foreach ($analytics['destinations'] as $dest): ?>
                            <?php 
                                $destPercent = $analytics['completions'] > 0 ? round(($dest['reach_count'] / $analytics['completions']) * 100) : 0;
                            ?>
                            <div class="p-3 border border-[#E4E7EC] rounded-lg bg-[#F7F8FA] flex items-center justify-between text-xs">
                                <div class="min-w-0 pr-4">
                                    <p class="font-semibold text-[#171A21] truncate"><?= e($dest['name']) ?></p>
                                    <p class="text-[11px] text-[#687080] truncate font-mono"><?= e($dest['url']) ?></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-sm font-bold text-[#5B5BD6]"><?= (int)$dest['reach_count'] ?></span>
                                    <span class="text-[11px] text-[#687080] block"><?= $destPercent ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>
