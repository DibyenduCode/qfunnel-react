<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'API, Webhooks & Embedding Documentation — QFunnel') ?></title>
    <meta name="description" content="Easy graphical documentation for QFunnel REST API, real-time webhooks, and 1-click embedding for WordPress & Shopify.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .brand-gradient-btn {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 50%, #4338CA 100%);
        }
    </style>
</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="<?= url('/') ?>" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl brand-gradient-btn flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                    <div>
                        <span class="text-base font-extrabold text-slate-900 tracking-tight block leading-tight font-display">QFunnel</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 block">Developer & Embed Hub</span>
                    </div>
                </a>
                <span class="hidden md:inline-block h-5 w-px bg-slate-200"></span>
                <span class="hidden md:inline-block text-xs font-medium text-slate-500">API, Webhooks & Universal Embedding Guide</span>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?= url('/') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                    ← Back to Home
                </a>
                <?php if (!empty($currentUser)): ?>
                    <a href="<?= is_platform_admin() ? url('/admin/platform') : url('/admin/dashboard') ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                        Go to Workspace →
                    </a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="hidden sm:inline-flex text-xs font-semibold text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                        Sign In
                    </a>
                    <a href="<?= url('/register') ?>" class="px-4 py-2 brand-gradient-btn hover:opacity-95 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                        Get Started Free →
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <?php include __DIR__ . '/../platform/docs.php'; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-8 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                © <?= date('Y') ?> QFunnel Platform Inc. Developer & Embedding Documentation.
            </div>
            <div class="flex items-center gap-6">
                <a href="<?= url('/') ?>" class="hover:text-slate-900 transition-colors">Home</a>
                <a href="<?= url('/privacy') ?>" class="hover:text-slate-900 transition-colors">Privacy Policy</a>
                <a href="<?= url('/terms') ?>" class="hover:text-slate-900 transition-colors">Terms of Service</a>
                <a href="<?= url('/login') ?>" class="hover:text-slate-900 transition-colors">Sign In</a>
            </div>
        </div>
    </footer>

</body>
</html>
