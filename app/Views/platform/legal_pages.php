<?php
$coreSlugs = ['terms', 'privacy', 'refund-policy', 'cookie-policy'];
$getLegalUrl = function($slug) use ($coreSlugs) {
    return in_array($slug, $coreSlugs) ? url('/' . e($slug)) : url('/legal/' . e($slug));
};
?>
<div class="space-y-6">

    <!-- Top Breadcrumb & Action Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-[#E2E8F0] rounded-2xl p-6 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                <span>Super Admin</span>
                <span>/</span>
                <span class="text-indigo-600">Compliance & Legal CMS</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Legal Pages & Policies CMS</h1>
            <p class="text-xs text-slate-500 mt-1">Manage public legal documents dynamically. Changes reflect instantly on live portals.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openCreateModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Legal Page
            </button>
            <a href="<?= $getLegalUrl($activePage['slug'] ?? 'terms') ?>" target="_blank" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                View Live Public Page
            </a>
        </div>
    </div>

    <!-- Main 2-Column Work Area -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Column 1: Policy Selector List (4 cols) -->
        <div class="lg:col-span-4 space-y-3">
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3 px-1">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">All Legal Documents</span>
                    <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full"><?= count($pages) ?> Pages</span>
                </div>

                <div class="space-y-2">
                    <?php foreach ($pages as $p): ?>
                        <?php 
                            $isSelected = ($activePage && (int)$activePage['id'] === (int)$p['id']);
                            $isCore = in_array($p['slug'], ['privacy', 'terms', 'refund-policy', 'cookie-policy']);
                        ?>
                        <div class="rounded-xl border transition-all p-3.5 <?= $isSelected ? 'border-indigo-500 bg-indigo-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white' ?>">
                            <div class="flex items-start justify-between gap-2">
                                <a href="<?= url('/admin/platform/legal-pages?id=' . $p['id']) ?>" class="min-w-0 flex-1 block">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <h3 class="text-xs font-bold truncate <?= $isSelected ? 'text-indigo-900' : 'text-slate-900' ?>">
                                            <?= e($p['title']) ?>
                                        </h3>
                                        <?php if ($isCore): ?>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 text-slate-500 border border-slate-200 uppercase">Core</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-slate-500 font-mono">
                                        <span>/<?= e($p['slug']) ?></span>
                                    </div>
                                </a>

                                <div class="flex items-center gap-1 shrink-0">
                                    <?php if ($p['is_published']): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Published
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Draft
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                <span>Updated: <?= date('M j, Y', strtotime($p['updated_at'])) ?></span>
                                <div class="flex items-center gap-2">
                                    <a href="<?= $getLegalUrl($p['slug']) ?>" target="_blank" class="hover:text-indigo-600 transition-colors" title="View Public Page">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <?php if (!$isCore): ?>
                                        <form method="POST" action="<?= url('/admin/platform/legal-pages/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Are you sure you want to permanently delete this legal page?');" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-rose-400 hover:text-rose-600 transition-colors" title="Delete Page">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Guidelines Card -->
            <div class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-4 text-xs text-indigo-900 space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-indigo-950">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Legal Content Formatting
                </div>
                <p class="text-[11px] leading-relaxed text-indigo-800">
                    You can write standard HTML elements (<code>&lt;h2&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;table&gt;</code>) or use the toolbar buttons above the editor to format clauses.
                </p>
            </div>
        </div>

        <!-- Column 2: Live Editor Form (8 cols) -->
        <div class="lg:col-span-8">
            <?php if ($activePage): ?>
                <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                    
                    <form method="POST" action="<?= url('/admin/platform/legal-pages/' . $activePage['id'] . '/update') ?>" id="legalPageForm">
                        <?= csrf_field() ?>

                        <!-- Editor Header with Title & Publish Toggle -->
                        <div class="p-6 border-b border-[#E2E8F0] space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Page Title *</label>
                                    <input type="text" name="title" id="pageTitleInput" value="<?= e($activePage['title']) ?>" required
                                           class="w-full text-base font-bold text-slate-900 rounded-lg border border-[#E2E8F0] px-3.5 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div class="sm:w-48">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Publication Status</label>
                                    <select name="is_published" class="w-full text-xs font-semibold rounded-lg border border-[#E2E8F0] px-3 py-2 bg-white focus:ring-2 focus:ring-indigo-500">
                                        <option value="1" <?= $activePage['is_published'] ? 'selected' : '' ?>>🟢 Published (Live)</option>
                                        <option value="0" <?= !$activePage['is_published'] ? 'selected' : '' ?>>⚪ Draft (Hidden)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">URL Slug</label>
                                    <div class="flex rounded-lg shadow-2xs">
                                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-[#E2E8F0] bg-slate-50 text-slate-500 text-xs font-mono">
                                            <?= in_array($activePage['slug'] ?? '', $coreSlugs) ? url('/') . '/' : url('/legal') . '/' ?>
                                        </span>
                                        <input type="text" name="slug" value="<?= e($activePage['slug']) ?>" required
                                               class="flex-1 text-xs font-mono rounded-r-lg border border-[#E2E8F0] px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Meta Description (SEO & Social Previews)</label>
                                    <input type="text" name="meta_description" value="<?= e($activePage['meta_description'] ?? '') ?>" placeholder="Brief 1-2 sentence overview of this legal policy..."
                                           class="w-full text-xs rounded-lg border border-[#E2E8F0] px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <!-- Editor Toolbar & Mode Switcher -->
                        <div class="px-6 py-2.5 bg-slate-50 border-b border-[#E2E8F0] flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-1 flex-wrap">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-2">Insert:</span>
                                <button type="button" onclick="insertSnippet('h2')" class="px-2 py-1 text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded transition-colors" title="Section Heading">H2</button>
                                <button type="button" onclick="insertSnippet('h3')" class="px-2 py-1 text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded transition-colors" title="Sub-section Heading">H3</button>
                                <button type="button" onclick="insertSnippet('bold')" class="px-2 py-1 text-xs font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded transition-colors" title="Bold text">B</button>
                                <button type="button" onclick="insertSnippet('italic')" class="px-2 py-1 text-xs italic bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded transition-colors" title="Italic text">I</button>
                                <button type="button" onclick="insertSnippet('ul')" class="px-2 py-1 text-xs bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded transition-colors" title="Bullet List">• List</button>
                                <button type="button" onclick="insertSnippet('table')" class="px-2 py-1 text-xs bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded transition-colors" title="Table Layout">Table</button>
                                <button type="button" onclick="insertSnippet('callout')" class="px-2 py-1 text-xs bg-white hover:bg-slate-100 text-indigo-700 border border-slate-200 rounded transition-colors" title="Callout Box">Box</button>
                            </div>

                            <div class="flex items-center bg-slate-200/80 p-0.5 rounded-lg text-xs font-semibold">
                                <button type="button" id="tabCodeBtn" onclick="switchEditorTab('code')" class="px-3 py-1 rounded-md bg-white text-slate-900 shadow-2xs">Editor</button>
                                <button type="button" id="tabPreviewBtn" onclick="switchEditorTab('preview')" class="px-3 py-1 rounded-md text-slate-600 hover:text-slate-900">Live Preview</button>
                            </div>
                        </div>

                        <!-- Editor Textarea Area -->
                        <div class="p-6">
                            <div id="editorPane">
                                <textarea name="content" id="policyContentEditor" rows="18" required
                                          class="w-full font-mono text-xs text-slate-800 bg-slate-50/50 border border-[#E2E8F0] rounded-xl p-4 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 leading-relaxed"><?= e($activePage['content']) ?></textarea>
                            </div>

                            <!-- Live Preview Container -->
                            <div id="previewPane" class="hidden min-h-[420px] max-h-[550px] overflow-y-auto border border-slate-200 rounded-xl p-6 bg-white prose prose-slate max-w-none text-xs">
                                <div id="livePreviewContainer"></div>
                            </div>
                        </div>

                        <!-- Save & Cancel Bar -->
                        <div class="p-6 bg-slate-50 border-t border-[#E2E8F0] flex items-center justify-between">
                            <div class="text-xs text-slate-500">
                                Last updated on <strong><?= date('F j, Y, g:i a', strtotime($activePage['updated_at'])) ?></strong>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="<?= url('/' . e($activePage['slug'])) ?>" target="_blank" class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition-colors">
                                    Preview Public Page ↗
                                </a>
                                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all hover:shadow-indigo-500/20">
                                    💾 Save & Publish Policy
                                </button>
                            </div>
                        </div>

                    </form>

                </div>
            <?php else: ?>
                <div class="bg-white border border-[#E2E8F0] rounded-xl p-12 text-center text-slate-500">
                    <p class="text-sm font-semibold text-slate-700">No Legal Page Selected</p>
                    <p class="text-xs mt-1">Select a document from the left list or create a new policy.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- Modal: Create New Legal Page -->
<div id="createPageModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 transform transition-all">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-sm font-bold text-slate-900">Create New Legal Policy Page</h3>
            <button type="button" onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form method="POST" action="<?= url('/admin/platform/legal-pages/create') ?>" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Page Title *</label>
                <input type="text" name="title" placeholder="e.g. Service Level Agreement (SLA)" required
                       class="w-full text-xs font-semibold text-slate-900 rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">URL Slug</label>
                <input type="text" name="slug" placeholder="e.g. service-level-agreement"
                       class="w-full text-xs font-mono text-slate-900 rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                <span class="text-[11px] text-slate-400 mt-1 block">Leave empty to automatically generate from title.</span>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Meta Description</label>
                <input type="text" name="meta_description" placeholder="Brief summary for search engines..."
                       class="w-full text-xs text-slate-900 rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Initial Content</label>
                <textarea name="content" rows="6" placeholder="<h2>1. Overview</h2><p>Policy content starts here...</p>" required
                          class="w-full text-xs font-mono text-slate-900 rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500"><h2>1. Introduction</h2>&#10;<p>This policy outlines our commitments and guidelines...</p></textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_published" id="is_published_chk" value="1" checked class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                <label for="is_published_chk" class="text-xs font-semibold text-slate-700">Publish immediately to public navigation</label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm">Create Legal Page</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createPageModal').classList.remove('hidden');
    }

    function closeCreateModal() {
        document.getElementById('createPageModal').classList.add('hidden');
    }

    function switchEditorTab(mode) {
        const editorPane = document.getElementById('editorPane');
        const previewPane = document.getElementById('previewPane');
        const codeBtn = document.getElementById('tabCodeBtn');
        const previewBtn = document.getElementById('tabPreviewBtn');
        const textarea = document.getElementById('policyContentEditor');
        const previewContainer = document.getElementById('livePreviewContainer');

        if (mode === 'preview') {
            editorPane.classList.add('hidden');
            previewPane.classList.remove('hidden');
            previewContainer.innerHTML = textarea.value;

            codeBtn.className = 'px-3 py-1 rounded-md text-slate-600 hover:text-slate-900';
            previewBtn.className = 'px-3 py-1 rounded-md bg-white text-slate-900 shadow-2xs font-bold';
        } else {
            previewPane.classList.add('hidden');
            editorPane.classList.remove('hidden');

            previewBtn.className = 'px-3 py-1 rounded-md text-slate-600 hover:text-slate-900';
            codeBtn.className = 'px-3 py-1 rounded-md bg-white text-slate-900 shadow-2xs font-bold';
        }
    }

    function insertSnippet(type) {
        const textarea = document.getElementById('policyContentEditor');
        if (!textarea) return;

        let snippet = '';
        switch(type) {
            case 'h2':
                snippet = "\n<h2>Section Title</h2>\n<p>Enter clause explanation here...</p>\n";
                break;
            case 'h3':
                snippet = "\n<h3>Sub-clause Title</h3>\n<p>Details...</p>\n";
                break;
            case 'bold':
                snippet = "<strong>Important notice</strong>";
                break;
            case 'italic':
                snippet = "<em>operative term</em>";
                break;
            case 'ul':
                snippet = "\n<ul>\n    <li>Requirement item one</li>\n    <li>Requirement item two</li>\n</ul>\n";
                break;
            case 'table':
                snippet = '\n<table style="width:100%; border-collapse:collapse; margin:16px 0;">\n    <thead>\n        <tr style="background:#F8FAFC; text-align:left;">\n            <th style="padding:8px 12px; border:1px solid #E2E8F0;">Term</th>\n            <th style="padding:8px 12px; border:1px solid #E2E8F0;">Definition</th>\n        </tr>\n    </thead>\n    <tbody>\n        <tr>\n            <td style="padding:8px 12px; border:1px solid #E2E8F0;">Scope</td>\n            <td style="padding:8px 12px; border:1px solid #E2E8F0;">Worldwide multi-tenant SaaS access.</td>\n        </tr>\n    </tbody>\n</table>\n';
                break;
            case 'callout':
                snippet = '\n<div style="background:#F0FDF4; border-left:4px solid #16A34A; padding:12px 16px; margin:16px 0; border-radius:4px;">\n    <strong>Compliance Note:</strong> This clause satisfies international privacy requirements.\n</div>\n';
                break;
        }

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        textarea.value = text.substring(0, start) + snippet + text.substring(end);
        textarea.focus();
        textarea.selectionStart = start + snippet.length;
        textarea.selectionEnd = start + snippet.length;
    }
</script>
