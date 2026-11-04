<div class="space-y-4">

    <!-- Header Actions & Filters -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="<?= url('/admin/funnels') ?>" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative w-full md:w-64">
                <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search funnels..."
                       class="w-full pl-9 pr-3.5 py-1.5 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] placeholder-[#687080] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                <svg class="w-4 h-4 text-[#687080] absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                <option value="">All Statuses</option>
                <option value="published" <?= ($status ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= ($status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="archived" <?= ($status ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>

            <?php if (!empty($search) || !empty($status)): ?>
                <a href="<?= url('/admin/funnels') ?>" class="text-xs text-[#687080] hover:text-[#171A21] underline">Clear</a>
            <?php endif; ?>
        </form>

        <a href="<?= url('/admin/funnels/create') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold shadow hover:bg-[#4B4BC0] transition-colors shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Funnel
        </a>
    </div>

    <!-- Funnels Table -->
    <?php 
    $items = isset($funnels['items']) ? $funnels['items'] : $funnels;
    $paginationData = isset($funnels['total_pages']) ? $funnels : null;
    ?>
    <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm overflow-hidden">
        <?php if (empty($items)): ?>
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-xl bg-[#F1F3F6] text-[#687080] flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-[#171A21] mb-1">No funnels found</h3>
                <p class="text-xs text-[#687080] mb-4">Start creating your visual branching customer journey today.</p>
                <a href="<?= url('/admin/funnels/create') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold shadow hover:bg-[#4B4BC0]">
                    Create Funnel
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E4E7EC] bg-[#F7F8FA] text-[11px] font-semibold text-[#687080] uppercase tracking-wider">
                            <th class="py-3 px-4">Funnel Name</th>
                            <th class="py-3 px-4">Public URL</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-center">Starts</th>
                            <th class="py-3 px-4 text-center">Completed</th>
                            <th class="py-3 px-4">Last Updated</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4E7EC] text-xs">
                        <?php foreach ($items as $f): ?>
                            <tr class="hover:bg-[#F7F8FA] transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-[#171A21]">
                                    <a href="<?= url("/admin/funnels/{$f['id']}/builder") ?>" class="hover:text-[#5B5BD6]">
                                        <?= e($f['name']) ?>
                                    </a>
                                    <?php if ($f['description']): ?>
                                        <p class="text-[11px] text-[#687080] font-normal truncate max-w-xs"><?= e($f['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-[#687080]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate max-w-[160px]">/quiz/<?= e($f['slug']) ?></span>
                                        <button onclick="navigator.clipboard.writeText(window.location.origin + '<?= url("/quiz/{$f['slug']}") ?>'); alert('Public link copied to clipboard!');" 
                                                title="Copy public link" class="text-slate-400 hover:text-slate-700">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                        <a href="<?= url("/quiz/{$f['slug']}") ?>" target="_blank" title="Open quiz" class="text-slate-400 hover:text-slate-700">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($f['status'] === 'published' && empty($f['has_unpublished_changes'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Published
                                        </span>
                                    <?php elseif ($f['status'] === 'published' && !empty($f['has_unpublished_changes'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-300" title="Changes saved in draft — Click Publish to update live link">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Not Published
                                        </span>
                                    <?php elseif ($f['status'] === 'draft'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                            Draft
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            Archived
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center font-medium text-[#171A21]"><?= (int)$f['total_starts'] ?></td>
                                <td class="py-3.5 px-4 text-center font-medium text-[#171A21]"><?= (int)$f['total_completions'] ?></td>
                                <td class="py-3.5 px-4 text-[#687080]"><?= date('M d, Y', strtotime($f['updated_at'])) ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= url("/admin/funnels/{$f['id']}/builder") ?>" 
                                           class="px-2.5 py-1 rounded bg-[#5B5BD6] text-white font-medium hover:bg-[#4B4BC0] transition-colors">
                                            Builder
                                        </a>

                                        <a href="<?= url("/admin/funnels/{$f['id']}/settings") ?>" 
                                           class="px-2 py-1 rounded border border-[#E4E7EC] hover:bg-white text-[#171A21]" title="Settings">
                                            Settings
                                        </a>

                                        <form method="POST" action="<?= url("/admin/funnels/{$f['id']}/duplicate") ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="px-2 py-1 rounded border border-[#E4E7EC] hover:bg-white text-[#171A21]" title="Duplicate">
                                                Copy
                                            </button>
                                        </form>

                                        <?php if ($f['status'] === 'published'): ?>
                                            <form method="POST" action="<?= url("/admin/funnels/{$f['id']}/unpublish") ?>" class="inline" onsubmit="return confirm('Unpublish this funnel? New visitors will not be able to start.');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="px-2 py-1 rounded border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100" title="Unpublish">
                                                    Unpublish
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="<?= url("/admin/funnels/{$f['id']}/delete") ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this funnel? This action cannot be undone.');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded" title="Delete Funnel">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($paginationData): ?>
                <?php
                $pagination = $paginationData;
                $queryParams = array_filter(['search' => $search ?? '', 'status' => $status ?? '']);
                require __DIR__ . '/../partials/pagination.php';
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>
