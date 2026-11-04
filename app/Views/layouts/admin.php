<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Quiz Funnel SaaS') ?></title>
    <!-- Tailwind CSS (via official script CDN for Core PHP MVC) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        appBg: '#F7F8FA',
                        surface: '#FFFFFF',
                        primaryText: '#171A21',
                        secondaryText: '#687080',
                        borderBase: '#E4E7EC',
                        mutedSurface: '#F1F3F6',
                        accent: '#5B5BD6',
                        accentHover: '#4B4BC0',
                        successText: '#16835D',
                        warningText: '#A86613',
                        dangerText: '#C23B42',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F7F8FA;
            color: #171A21;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #E4E7EC;
            border-radius: 4px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row antialiased">

    <!-- Sidebar -->
    <aside class="w-full md:w-60 md:fixed md:inset-y-0 md:left-0 md:h-screen bg-white border-r border-[#E4E7EC] flex flex-col justify-between shrink-0 md:overflow-y-auto sidebar-scroll z-30">
        <div>
            <!-- Workspace / Brand Header -->
            <div class="h-16 flex items-center px-6 border-b border-[#E4E7EC]">
                <a href="<?= url('/admin/dashboard') ?>" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-[#5B5BD6] flex items-center justify-center text-white font-bold text-sm shadow-sm">
                        QF
                    </div>
                    <div>
                        <span class="font-bold text-sm tracking-tight text-[#171A21]">QuizFunnel</span>
                        <span class="block text-[11px] font-medium text-[#687080] truncate max-w-[130px]">
                            <?= e(current_tenant()['name'] ?? 'Workspace') ?>
                        </span>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1 text-sm font-medium">
                <a href="<?= url('/admin/dashboard') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#171A21] hover:bg-[#F1F3F6] transition-colors <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/dashboard') ? 'bg-[#F1F3F6] font-semibold text-[#5B5BD6]' : '' ?>">
                    <svg class="w-4 h-4 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Overview
                </a>
                <a href="<?= url('/admin/funnels') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#171A21] hover:bg-[#F1F3F6] transition-colors <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/funnels') ? 'bg-[#F1F3F6] font-semibold text-[#5B5BD6]' : '' ?>">
                    <svg class="w-4 h-4 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Funnels
                </a>
                <a href="<?= url('/admin/submissions') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#171A21] hover:bg-[#F1F3F6] transition-colors <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/submissions') ? 'bg-[#F1F3F6] font-semibold text-[#5B5BD6]' : '' ?>">
                    <svg class="w-4 h-4 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Submissions
                </a>
                <a href="<?= url('/admin/analytics') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#171A21] hover:bg-[#F1F3F6] transition-colors <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/analytics') ? 'bg-[#F1F3F6] font-semibold text-[#5B5BD6]' : '' ?>">
                    <svg class="w-4 h-4 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Analytics
                </a>
                <a href="<?= url('/admin/team') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#171A21] hover:bg-[#F1F3F6] transition-colors <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/team') ? 'bg-[#F1F3F6] font-semibold text-[#5B5BD6]' : '' ?>">
                    <svg class="w-4 h-4 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Team
                </a>
                <a href="<?= url('/admin/webhooks') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#171A21] hover:bg-[#F1F3F6] transition-colors <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/webhooks') ? 'bg-[#F1F3F6] font-semibold text-[#5B5BD6]' : '' ?>">
                    <svg class="w-4 h-4 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Webhooks
                </a>
                <a href="<?= url('/admin/billing') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#171A21] hover:bg-[#F1F3F6] transition-colors <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/billing') ? 'bg-[#F1F3F6] font-semibold text-[#5B5BD6]' : '' ?>">
                    <svg class="w-4 h-4 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Billing & Plans
                </a>
                <a href="<?= url('/admin/settings/workspace') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[#171A21] hover:bg-[#F1F3F6] transition-colors <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/settings') ? 'bg-[#F1F3F6] font-semibold text-[#5B5BD6]' : '' ?>">
                    <svg class="w-4 h-4 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Settings
                </a>

                <?php if (is_platform_admin()): ?>
                <div class="pt-4 mt-4 border-t border-[#E4E7EC]">
                    <span class="px-3 text-[11px] font-semibold text-[#687080] uppercase tracking-wider">Super Admin</span>
                    <a href="<?= url('/admin/platform') ?>" class="mt-1 flex items-center gap-3 px-3 py-2 rounded-lg text-purple-700 bg-purple-50 hover:bg-purple-100 transition-colors">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Platform Panel
                    </a>
                </div>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-3 border-t border-[#E4E7EC] bg-white">
            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-[#F1F3F6] transition-colors">
                <a href="<?= url('/admin/settings/profile') ?>" class="flex items-center gap-2.5 min-w-0">
                    <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 font-semibold text-xs flex items-center justify-center shrink-0">
                        <?= strtoupper(substr(current_user()['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-[#171A21] truncate"><?= e(current_user()['name'] ?? 'User') ?></p>
                        <p class="text-[11px] text-[#687080] truncate"><?= e(current_user()['email'] ?? '') ?></p>
                    </div>
                </a>
                <a href="<?= url('/logout') ?>" title="Sign Out" class="text-[#687080] hover:text-[#C23B42] p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 md:ml-60">
        <?php if (is_platform_admin()): ?>
        <div class="bg-slate-900 text-white px-6 py-2 flex items-center justify-between text-xs border-b border-slate-800">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">Super Admin</span>
                <span class="text-slate-300">Viewing workspace: <strong class="text-white"><?= e(current_tenant()['name'] ?? 'Workspace') ?></strong></span>
            </div>
            <a href="<?= url('/admin/platform') ?>" class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 font-semibold">
                <span>← Back to Super Admin Panel</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-[#E4E7EC] px-6 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <h1 class="text-base font-semibold text-[#171A21]"><?= e($title ?? 'Dashboard') ?></h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-[#F1F3F6] text-[#687080] border border-[#E4E7EC]">
                    Role: <strong class="capitalize text-[#171A21]"><?= e(\App\Services\TenantContextService::getCurrentRole() ?? 'Member') ?></strong>
                </span>
                <a href="<?= url('/admin/funnels/create') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold shadow-sm hover:bg-[#4B4BC0] transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Funnel
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-6 pt-4">
            <?php if ($success = flash('success')): ?>
                <div class="p-3.5 mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span><?= e($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error = flash('error')): ?>
                <div class="p-3.5 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dynamic Body Content -->
        <main class="flex-1 p-6 overflow-y-auto">
            <?= $content ?>
        </main>
    </div>

</body>
</html>
