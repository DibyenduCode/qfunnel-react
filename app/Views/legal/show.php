<?php
$coreSlugs = ['terms', 'privacy', 'refund-policy', 'cookie-policy'];
$getLegalUrl = function($slug) use ($coreSlugs) {
    return in_array($slug, $coreSlugs) ? url('/' . e($slug)) : url('/legal/' . e($slug));
};
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page['title']) ?> — QFunnel Platform</title>
    <meta name="description" content="<?= e($page['meta_description'] ?? 'Official legal and compliance documentation for QFunnel Platform.') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .legal-content h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0F172A;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #E2E8F0;
        }
        .legal-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1E293B;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .legal-content p {
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 1rem;
        }
        .legal-content ul, .legal-content ol {
            margin-bottom: 1.25rem;
            padding-left: 1.5rem;
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.7;
        }
        .legal-content ul { list-style-type: disc; }
        .legal-content ol { list-style-type: decimal; }
        .legal-content li { margin-bottom: 0.35rem; }
        .legal-content a {
            color: #4F46E5;
            text-decoration: underline;
            text-underline-offset: 3px;
            font-weight: 500;
        }
        .legal-content a:hover { color: #4338CA; }
        .legal-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.25rem 0;
            font-size: 0.875rem;
        }
        .legal-content th, .legal-content td {
            border: 1px solid #E2E8F0;
            padding: 0.75rem 1rem;
            text-align: left;
        }
        .legal-content th {
            background-color: #F8FAFC;
            font-weight: 600;
            color: #0F172A;
        }
        .legal-content code {
            font-family: 'JetBrains Mono', monospace;
            background-color: #F1F5F9;
            color: #0F172A;
            padding: 0.15rem 0.35rem;
            border-radius: 0.25rem;
            font-size: 0.85em;
        }
    </style>
</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="<?= url('/') ?>" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-700 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <span class="text-base font-extrabold text-slate-900 tracking-tight block leading-tight">QFunnel</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 block">Trust & Legal</span>
                    </div>
                </a>
                <span class="hidden md:inline-block h-5 w-px bg-slate-200"></span>
                <span class="hidden md:inline-block text-xs font-medium text-slate-500">Legal Compliance & Security Center</span>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?= url('/') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                    ← Back to Home
                </a>
                <?php if (current_user()): ?>
                    <a href="<?= url('/admin/dashboard') ?>" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                        Go to Workspace
                    </a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                        Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <!-- Sidebar Policy Selector -->
            <aside class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs sticky top-24">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">
                        Legal Documents
                    </div>
                    <nav class="space-y-1">
                        <?php foreach ($allPages as $p): ?>
                            <?php $isActive = ($p['slug'] === $activeSlug); ?>
                            <a href="<?= $getLegalUrl($p['slug']) ?>" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all <?= $isActive ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-200/60 shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                                <span><?= e($p['title']) ?></span>
                                <?php if ($isActive): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="mt-6 pt-5 border-t border-slate-100 px-2 space-y-3">
                        <div class="text-[11px] text-slate-500">
                            <strong>Need custom compliance terms?</strong> Contact our enterprise team.
                        </div>
                        <a href="mailto:support@qfunnel.com" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            support@qfunnel.com
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Main Legal Article Document -->
            <article class="lg:col-span-9 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-8 sm:p-12 shadow-xs">
                    
                    <!-- Document Header -->
                    <div class="border-b border-slate-200 pb-8 mb-8">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Official Binding Platform Policy
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span>Last Updated:</span>
                                <time datetime="<?= date('Y-m-d', strtotime($page['updated_at'])) ?>" class="font-semibold text-slate-700">
                                    <?= date('F j, Y', strtotime($page['updated_at'])) ?>
                                </time>
                            </div>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-3">
                            <?= e($page['title']) ?>
                        </h1>

                        <?php if (!empty($page['meta_description'])): ?>
                            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                                <?= e($page['meta_description']) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Quick Utility Bar -->
                        <div class="flex items-center gap-4 mt-6 pt-4 border-t border-slate-100 text-xs">
                            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-slate-800 font-semibold transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print / Save as PDF
                            </button>
                            <span class="text-slate-300">•</span>
                            <button type="button" onclick="navigator.clipboard.writeText(window.location.href); alert('Page link copied to clipboard!');" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-slate-800 font-semibold transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Copy Document Link
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Body Content -->
                    <div class="legal-content">
                        <?= $page['content'] ?>
                    </div>

                    <!-- Verified Company Footnote Box -->
                    <div class="mt-12 pt-8 border-t border-slate-200 bg-slate-50 rounded-xl p-6 text-xs text-slate-600 space-y-2">
                        <div class="font-bold text-slate-900 text-sm">Official Entity & Contact Registry</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                            <div>
                                <strong class="block text-slate-700">Operating Entity:</strong>
                                <span>QuizFunnel Technologies Pvt Ltd / QFunnel Inc.</span>
                            </div>
                            <div>
                                <strong class="block text-slate-700">Compliance & DPO Desk:</strong>
                                <a href="mailto:support@qfunnel.com" class="text-indigo-600 hover:underline">support@qfunnel.com</a> (Mon–Sat: 10 AM – 7 PM IST)
                            </div>
                            <div>
                                <strong class="block text-slate-700">Official Postal Address:</strong>
                                <span>Helencha Road, Duttapulia, Baikola, West Bengal 743270, India</span>
                            </div>
                            <div>
                                <strong class="block text-slate-700">Helpline Phone:</strong>
                                <span>+91 6294629735</span>
                            </div>
                        </div>
                    </div>

                </div>
            </article>

        </div>
    </main>

    <!-- Simple Public Footer -->
    <footer class="mt-auto bg-white border-t border-slate-200 py-6 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                © <?= date('Y') ?> QFunnel Platform Inc. All rights reserved.
            </div>
            <div class="flex items-center gap-6">
                <?php foreach ($allPages as $p): ?>
                    <a href="<?= $getLegalUrl($p['slug']) ?>" class="hover:text-slate-900 transition-colors <?= ($p['slug'] === $activeSlug) ? 'text-indigo-600 font-semibold' : '' ?>">
                        <?= e($p['title']) ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?= url('/') ?>" class="hover:text-slate-900 transition-colors">Platform Home</a>
            </div>
        </div>
    </footer>

</body>
</html>
