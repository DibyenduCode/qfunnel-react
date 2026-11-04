<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Super Admin Panel — QuizFunnel') ?></title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <!-- Tailwind CSS (via official script CDN for Core PHP MVC) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        superDark: '#0F172A',
                        superBorder: '#1E293B',
                        superAccent: '#6366F1',
                        superAccentHover: '#4F46E5',
                        appBg: '#F8FAFC',
                        surface: '#FFFFFF',
                        primaryText: '#0F172A',
                        secondaryText: '#64748B',
                        borderBase: '#E2E8F0',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #334155;
            border-radius: 4px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row antialiased bg-[#F8FAFC]">

    <!-- Super Admin Exclusive Sidebar -->
    <aside class="w-full md:w-64 md:fixed md:inset-y-0 md:left-0 md:h-screen bg-[#0F172A] border-r border-[#1E293B] flex flex-col justify-between shrink-0 text-white z-20 md:overflow-y-auto sidebar-scroll">
        <div>
            <!-- Super Admin Header / Brand -->
            <div class="h-20 flex items-center px-6 border-b border-[#1E293B] bg-[#0A0F1D]">
                <a href="<?= url('/admin/platform') ?>" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-base tracking-tight text-white">QuizFunnel</span>
                        </div>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            Super Admin
                        </span>
                    </div>
                </a>
            </div>

            <!-- Super Admin Navigation Links ONLY -->
            <div class="px-4 pt-5 pb-2">
                <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Platform Control</p>
            </div>
            
            <nav class="px-3 space-y-1.5 text-sm font-medium">
                <!-- 1. Overview -->
                <a href="<?= url('/admin/platform') ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= ($_SERVER['REQUEST_URI'] ?? '') === url('/admin/platform') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= ($_SERVER['REQUEST_URI'] ?? '') === url('/admin/platform') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span>Overview</span>
                </a>

                <!-- 2. Customers & Status -->
                <a href="<?= url('/admin/platform/customers') ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/customers') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/customers') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <div class="flex-1 flex items-center justify-between">
                        <span>Customers & Users</span>
                    </div>
                </a>

                <!-- 3. Plans Management -->
                <a href="<?= url('/admin/platform/plans') ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/plans') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/plans') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Plans & Pricing</span>
                </a>

                <!-- 4. Payment Gateway Settings -->
                <a href="<?= url('/admin/platform/payment-gateways') ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/payment-gateways') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/payment-gateways') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span>Payment Gateways</span>
                </a>

                <!-- 5. Legal Pages CMS -->
                <a href="<?= url('/admin/platform/legal-pages') ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/legal-pages') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/legal-pages') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Legal Pages CMS</span>
                </a>

                <!-- 6. Audit Logs -->
                <a href="<?= url('/admin/platform/logs') ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/logs') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/logs') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>System Audit Trail</span>
                </a>

                <!-- 7. API, Webhook & Embedding Documentation -->
                <a href="<?= url('/admin/platform/docs') ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/docs') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/docs') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <div class="flex-1 flex items-center justify-between">
                        <span>API & Embed Docs</span>
                        <span class="px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">Hub</span>
                    </div>
                </a>

                <!-- 8. Profile Option -->
                <a href="<?= url('/admin/platform/profile') ?>" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/profile') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/platform/profile') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Admin Profile</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer with Super Admin Details & Logout -->
        <div class="p-3 border-t border-[#1E293B] bg-[#0A0F1D]">
            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-800/60 transition-colors">
                <a href="<?= url('/admin/platform/profile') ?>" class="flex items-center gap-3 min-w-0" title="Manage Profile">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-sm">
                        <?= strtoupper(substr(current_user()['name'] ?? 'S', 0, 1)) ?>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-white truncate"><?= e(current_user()['name'] ?? 'Super Admin') ?></p>
                        <p class="text-[11px] text-slate-400 truncate"><?= e(current_user()['email'] ?? '') ?></p>
                    </div>
                </a>
                <a href="<?= url('/logout') ?>" title="Sign Out" class="text-slate-400 hover:text-rose-400 p-1.5 rounded transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Super Admin Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen md:ml-64">
        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-[#E2E8F0] px-6 flex items-center justify-between shrink-0 shadow-sm z-10">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-bold text-slate-900"><?= e($title ?? 'Super Admin Control Center') ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                    Master Control Active
                </span>
                <a href="<?= url('/admin/platform/profile') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors border border-slate-200">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile Settings
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-6 pt-4">
            <?php if ($success = flash('success')): ?>
                <div class="p-4 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="font-medium"><?= e($success) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error = flash('error')): ?>
                <div class="p-4 mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="font-medium"><?= e($error) ?></span>
                    </div>
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
