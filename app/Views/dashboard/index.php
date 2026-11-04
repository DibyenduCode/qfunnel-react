<div class="space-y-6">

    <!-- Top KPI Metric Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Funnels -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
            <span class="text-xs font-medium text-[#687080] block mb-1">Total Funnels</span>
            <span class="text-2xl font-bold text-[#171A21]"><?= (int)$metrics['total_funnels'] ?></span>
            <span class="text-[11px] text-[#687080] block mt-1"><?= (int)$metrics['published_funnels'] ?> published</span>
        </div>

        <!-- Quiz Starts -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
            <span class="text-xs font-medium text-[#687080] block mb-1">Quiz Starts</span>
            <span class="text-2xl font-bold text-[#171A21]"><?= (int)$metrics['total_starts'] ?></span>
            <span class="text-[11px] text-emerald-600 block mt-1 font-medium">Session initialized</span>
        </div>

        <!-- Captured Leads -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
            <span class="text-xs font-medium text-[#687080] block mb-1">Captured Leads</span>
            <span class="text-2xl font-bold text-[#171A21]"><?= (int)$metrics['total_leads'] ?></span>
            <span class="text-[11px] text-[#687080] block mt-1">Pre-quiz submissions</span>
        </div>

        <!-- Completed Quizzes -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
            <span class="text-xs font-medium text-[#687080] block mb-1">Completions</span>
            <span class="text-2xl font-bold text-[#171A21]"><?= (int)$metrics['total_completions'] ?></span>
            <span class="text-[11px] text-[#687080] block mt-1">Reached destination</span>
        </div>

        <!-- Completion Rate -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
            <span class="text-xs font-medium text-[#687080] block mb-1">Completion Rate</span>
            <span class="text-2xl font-bold text-[#5B5BD6]"><?= $metrics['completion_rate'] ?>%</span>
            <span class="text-[11px] text-[#687080] block mt-1">Finished / Starts</span>
        </div>

        <!-- Plan Usage -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm">
            <span class="text-xs font-medium text-[#687080] block mb-1">Plan: <?= e($usage['plan_name']) ?></span>
            <div class="w-full bg-[#E4E7EC] h-2 rounded-full mt-2 overflow-hidden">
                <div class="bg-[#5B5BD6] h-full rounded-full" style="width: <?= $usage['funnels']['percentage'] ?>%"></div>
            </div>
            <span class="text-[11px] text-[#687080] block mt-1.5"><?= $usage['funnels']['used'] ?> / <?= $usage['funnels']['limit'] ?> funnels used</span>
        </div>
    </div>

    <?php
    $trend = $metrics['activity_trend'] ?? [
        'labels' => [],
        'starts' => [],
        'completions' => [],
        'total_week_starts' => 0,
        'total_week_completions' => 0,
    ];
    $stageDist = $metrics['stage_distribution'] ?? [
        'labels' => [],
        'data' => [],
        'colors' => [],
        'details' => [],
        'total' => 0,
    ];
    ?>

    <!-- Visual Analytics Graphs (Simple, Clean & Intuitive) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Chart 1: 7-Day Performance Trend (2 Columns Wide on Desktop) -->
        <div class="lg:col-span-2 bg-white border border-[#E4E7EC] rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E4E7EC] gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#5B5BD6]"></span>
                            <h3 class="text-sm font-bold text-[#171A21]">Activity & Conversion Trend</h3>
                        </div>
                        <p class="text-xs text-[#687080] mt-0.5">Daily visitor starts vs finished submissions over the last 7 days</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100">
                            <span class="w-2 h-2 rounded-full bg-[#5B5BD6]"></span>
                            <span class="text-[11px] font-semibold text-indigo-950">Starts: <strong><?= number_format($trend['total_week_starts']) ?></strong></span>
                        </div>
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-100">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-[11px] font-semibold text-emerald-950">Leads: <strong><?= number_format($trend['total_week_completions']) ?></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Canvas Chart -->
                <div class="pt-4 relative h-64 w-full">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            
            <div class="pt-3 border-t border-[#E4E7EC] flex items-center justify-between text-[11px] text-[#687080]">
                <span>Last 7 Days (Continuous Timeline)</span>
                <span class="font-medium text-emerald-600">
                    7-Day Conversion: <?= $trend['total_week_starts'] > 0 ? round(($trend['total_week_completions'] / $trend['total_week_starts']) * 100, 1) : 0 ?>%
                </span>
            </div>
        </div>

        <!-- Chart 2: Lead Stage Distribution (1 Column Wide) -->
        <div class="bg-white border border-[#E4E7EC] rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-[#E4E7EC]">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h3 class="text-sm font-bold text-[#171A21]">Lead Stages</h3>
                        </div>
                        <p class="text-xs text-[#687080] mt-0.5">Distribution across CRM pipeline</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                        <?= number_format($stageDist['total']) ?> Total
                    </span>
                </div>

                <?php if ($stageDist['total'] === 0): ?>
                    <div class="h-64 flex flex-col items-center justify-center text-center p-4">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700">No leads recorded yet</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Leads will automatically populate here as visitors complete funnels.</p>
                    </div>
                <?php else: ?>
                    <!-- Donut Chart & Legend -->
                    <div class="pt-3 flex flex-col items-center">
                        <div class="relative h-44 w-44">
                            <canvas id="stageChart"></canvas>
                        </div>

                        <!-- Stage Legend List -->
                        <div class="w-full mt-3 space-y-1.5 max-h-36 overflow-y-auto pr-1">
                            <?php foreach ($stageDist['details'] as $item): ?>
                                <?php if ($item['count'] > 0): 
                                    $pct = $stageDist['total'] > 0 ? round(($item['count'] / $stageDist['total']) * 100, 1) : 0;
                                ?>
                                    <div class="flex items-center justify-between text-xs py-1 px-2 rounded-lg hover:bg-slate-50 transition-colors">
                                        <div class="flex items-center gap-1.5 truncate pr-2">
                                            <span><?= $item['emoji'] ?></span>
                                            <span class="font-medium text-slate-700 truncate"><?= e($item['name']) ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="font-bold text-slate-900"><?= $item['count'] ?></span>
                                            <span class="text-[10px] text-slate-400 w-9 text-right font-mono"><?= $pct ?>%</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pt-3 border-t border-[#E4E7EC] flex items-center justify-between text-[11px] text-[#687080]">
                <span>Pipeline Health</span>
                <a href="<?= url('/admin/submissions') ?>" class="text-[#5B5BD6] font-semibold hover:underline">Manage Leads →</a>
            </div>
        </div>

    </div>

    <!-- Middle Grid: Recent Funnels & Recent Submissions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Funnels Table -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-4 border-b border-[#E4E7EC] flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-[#171A21]">Recent Funnels</h3>
                    <a href="<?= url('/admin/funnels') ?>" class="text-xs text-[#5B5BD6] font-medium hover:underline">View all funnels →</a>
                </div>

                <?php if (empty($metrics['recent_funnels'])): ?>
                    <div class="p-8 text-center">
                        <p class="text-sm text-[#687080] mb-4">No funnels created yet in this workspace.</p>
                        <a href="<?= url('/admin/funnels/create') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-medium shadow-sm hover:bg-[#4B4BC0]">
                            Create your first funnel
                        </a>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-[#E4E7EC]">
                        <?php foreach ($metrics['recent_funnels'] as $f): ?>
                            <div class="p-4 flex items-center justify-between hover:bg-[#F7F8FA] transition-colors">
                                <div class="min-w-0 pr-4">
                                    <div class="flex items-center gap-2">
                                        <a href="<?= url("/admin/funnels/{$f['id']}/builder") ?>" class="text-sm font-semibold text-[#171A21] hover:text-[#5B5BD6] truncate">
                                            <?= e($f['name']) ?>
                                        </a>
                                        <?php if ($f['status'] === 'published'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Published</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">Draft</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-[#687080] mt-0.5">
                                        /quiz/<?= e($f['slug']) ?> • <?= (int)$f['starts_count'] ?> starts • <?= (int)$f['completions_count'] ?> completed
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="<?= url("/admin/funnels/{$f['id']}/builder") ?>" class="px-2.5 py-1 text-xs font-medium border border-[#E4E7EC] rounded-lg hover:bg-white text-[#171A21]">
                                        Builder
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Submissions List -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-4 border-b border-[#E4E7EC] flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-[#171A21]">Recent Leads & Submissions</h3>
                    <a href="<?= url('/admin/submissions') ?>" class="text-xs text-[#5B5BD6] font-medium hover:underline">View all submissions →</a>
                </div>

                <?php if (empty($metrics['recent_submissions'])): ?>
                    <div class="p-8 text-center">
                        <p class="text-sm text-[#687080]">No submissions recorded yet. Share your published funnel link to capture leads.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-[#E4E7EC]">
                        <?php foreach ($metrics['recent_submissions'] as $sub): ?>
                            <div class="p-4 flex items-center justify-between hover:bg-[#F7F8FA] transition-colors">
                                <div class="min-w-0 pr-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-[#171A21] truncate">
                                            <?= e($sub['lead_name'] ?: 'Anonymous Visitor') ?>
                                        </span>
                                        <span class="text-xs text-[#687080]">(<?= e($sub['lead_email'] ?: 'No email') ?>)</span>
                                    </div>
                                    <p class="text-xs text-[#687080] mt-0.5">
                                        Funnel: <?= e($sub['funnel_name']) ?> • <?= date('M d, Y H:i', strtotime($sub['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <?php if ($sub['status'] === 'completed'): ?>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200"><?= ucfirst($sub['status']) ?></span>
                                    <?php endif; ?>
                                    <a href="<?= url("/admin/submissions/{$sub['id']}") ?>" class="text-xs text-[#5B5BD6] font-semibold hover:underline">
                                        Inspect →
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<!-- Chart.js CDN for Lightweight & Responsive Visual Analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Performance Trend Chart (Daily Starts vs Completed Leads)
    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        const trendCtx = trendCanvas.getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($trend['labels']) ?>,
                datasets: [
                    {
                        label: 'Quiz Starts',
                        data: <?= json_encode($trend['starts']) ?>,
                        borderColor: '#5B5BD6',
                        backgroundColor: 'rgba(91, 91, 214, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#5B5BD6',
                        pointHoverRadius: 6,
                        pointRadius: 3.5,
                    },
                    {
                        label: 'Completed Leads',
                        data: <?= json_encode($trend['completions']) ?>,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#10B981',
                        pointHoverRadius: 6,
                        pointRadius: 3.5,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1E293B',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 10,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#687080' }
                    },
                    y: {
                        beginAtZero: true,
                        border: { dash: [4, 4] },
                        grid: { color: '#F1F3F6' },
                        ticks: {
                            precision: 0,
                            font: { size: 11 },
                            color: '#687080'
                        }
                    }
                }
            }
        });
    }

    // 2. Lead Stage Distribution Donut Chart
    const stageCanvas = document.getElementById('stageChart');
    if (stageCanvas) {
        const stageCtx = stageCanvas.getContext('2d');
        new Chart(stageCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($stageDist['labels']) ?>,
                datasets: [{
                    data: <?= json_encode($stageDist['data']) ?>,
                    backgroundColor: <?= json_encode($stageDist['colors']) ?>,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1E293B',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const val = ctx.parsed || 0;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

