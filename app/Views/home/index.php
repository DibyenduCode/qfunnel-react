<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'QFunnel — Interactive Quiz Funnel & Lead Qualification SaaS') ?></title>
    <meta name="description" content="Turn visitors into qualified leads with interactive branching quiz funnels. 1-click universal embeds for WordPress & Shopify, automated Google Sheets sync, and real-time CRM webhooks.">
    <meta name="keywords" content="quiz funnel saas, interactive quiz builder, lead qualification, wordpress quiz embed, shopify quiz, google sheets webhook">
    
    <!-- Open Graph / Social Meta -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="QFunnel — Interactive Quiz Funnel SaaS">
    <meta property="og:description" content="Build visual branching quiz funnels that 3x your lead conversion rate. Embed anywhere with 1 line of code.">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#EEF2FF',
                            100: '#E0E7FF',
                            200: '#C7D2FE',
                            300: '#A5B4FC',
                            400: '#818CF8',
                            500: '#6366F1',
                            600: '#4F46E5',
                            700: '#4338CA',
                            800: '#3730A3',
                            900: '#312E81',
                            950: '#1E1B4B',
                        },
                        dark: {
                            950: '#07090E',
                            900: '#0B0F17',
                            850: '#101623',
                            800: '#161F32',
                            700: '#23304A',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: #07090E;
            color: #F8FAFC;
        }

        /* Ambient Glow & Mesh Backgrounds */
        .ambient-mesh {
            background-image: 
                radial-gradient(at 15% 15%, rgba(99, 102, 241, 0.18) 0px, transparent 50%),
                radial-gradient(at 85% 20%, rgba(168, 85, 247, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 60%, rgba(59, 130, 246, 0.12) 0px, transparent 65%);
        }

        .ambient-card {
            background: rgba(16, 22, 35, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .ambient-card-hover {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .ambient-card-hover:hover {
            transform: translateY(-4px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.25);
        }

        .brand-gradient-text {
            background: linear-gradient(135deg, #FFFFFF 0%, #A5B4FC 50%, #818CF8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-gradient-btn {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 50%, #4338CA 100%);
            box-shadow: 0 8px 24px -6px rgba(99, 102, 241, 0.5);
        }
        .brand-gradient-btn:hover {
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 50%, #3730A3 100%);
            box-shadow: 0 12px 28px -6px rgba(99, 102, 241, 0.7);
        }

        /* Glass pill border */
        .glass-pill {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Visual Canvas Showcase Styles */
        .canvas-grid {
            background-color: #080B11;
            background-image: 
                radial-gradient(rgba(255, 255, 255, 0.08) 1.2px, transparent 1.2px),
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 24px 24px, 120px 120px, 120px 120px;
        }

        @keyframes cableFlow {
            from { stroke-dashoffset: 40; }
            to { stroke-dashoffset: 0; }
        }

        .cable-flow {
            stroke-dasharray: 6 6;
            animation: cableFlow 1.6s linear infinite;
        }

        .builder-node {
            transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.15s ease;
        }
        .builder-node:hover {
            box-shadow: 0 10px 30px -5px rgba(99, 102, 241, 0.3);
        }
        .builder-node.active-step {
            border-color: #6366F1 !important;
            box-shadow: 0 0 30px 2px rgba(99, 102, 241, 0.45) !important;
            transform: scale(1.02);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0B0F17;
        }
        ::-webkit-scrollbar-thumb {
            background: #23304A;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #4F46E5;
        }
    </style>
</head>
<body class="bg-dark-950 text-slate-100 antialiased selection:bg-brand-500 selection:text-white">

    <!-- ================= ANNOUNCEMENT TOP BAR ================= -->
    <div class="relative z-50 bg-gradient-to-r from-brand-950 via-dark-900 to-brand-950 border-b border-brand-500/20 py-2.5 px-4 text-center text-xs font-medium">
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-2 flex-wrap">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-brand-500/20 text-brand-300 border border-brand-500/30 text-[11px] font-bold tracking-wide uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-400 animate-pulse"></span>
                v2.4 Release
            </span>
            <span class="text-slate-200">Universal Embeds for WordPress & Shopify + Auto-Retrying Webhooks & REST API!</span>
            <a href="#builder-showcase" class="inline-flex items-center gap-1 text-brand-400 hover:text-brand-300 font-bold ml-1 transition-colors">
                <span>Explore Visual Canvas</span>
                <span class="text-sm">→</span>
            </a>
        </div>
    </div>

    <!-- ================= NAVBAR ================= -->
    <header class="sticky top-0 z-40 bg-dark-950/80 backdrop-blur-xl border-b border-white/[0.08] transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            
            <!-- Brand Logo -->
            <a href="<?= url('/') ?>" class="flex items-center gap-3 group shrink-0 select-none">
                <div class="w-10 h-10 rounded-xl brand-gradient-btn flex items-center justify-center font-black text-white shadow-lg shadow-brand-500/20 group-hover:scale-105 transition-all duration-300 ring-1 ring-white/10">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <div class="leading-tight shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-extrabold text-white tracking-tight font-display whitespace-nowrap">QFunnel</span>
                        <span class="px-2 py-0.5 rounded-full bg-brand-500/10 text-brand-400 text-[10px] font-bold border border-brand-500/25 tracking-wide whitespace-nowrap">PRO</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium tracking-wider uppercase block whitespace-nowrap">Interactive Quiz SaaS</span>
                </div>
            </a>

            <!-- Navigation Links (Modern Glass Capsule Pill - 4 Core Items) -->
            <nav class="hidden md:flex items-center gap-1 lg:gap-2 px-4 py-1.5 rounded-full bg-white/[0.03] border border-white/[0.08] backdrop-blur-md shadow-inner text-slate-300">
                <a href="#how-it-works" class="px-3.5 py-1.5 rounded-full text-xs lg:text-sm font-medium hover:text-white hover:bg-white/[0.06] transition-all whitespace-nowrap">How It Works</a>
                <a href="#features" class="px-3.5 py-1.5 rounded-full text-xs lg:text-sm font-medium hover:text-white hover:bg-white/[0.06] transition-all whitespace-nowrap">Features</a>
                <a href="#pricing" class="px-3.5 py-1.5 rounded-full text-xs lg:text-sm font-medium hover:text-white hover:bg-white/[0.06] transition-all whitespace-nowrap">Pricing</a>
                <a href="#faq" class="px-3.5 py-1.5 rounded-full text-xs lg:text-sm font-medium hover:text-white hover:bg-white/[0.06] transition-all whitespace-nowrap">FAQ</a>
            </nav>

            <!-- Actions & Language Switcher -->
            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                
                <!-- Language Toggle (ENG / বাংলা) -->
                <div class="hidden sm:flex items-center bg-white/[0.04] p-1 rounded-full border border-white/10 text-xs font-bold shrink-0 shadow-xs">
                    <button type="button" onclick="switchLanguage('en')" id="btnLangEn" class="px-2.5 py-1 rounded-full bg-brand-600 text-white transition-all shadow-xs text-xs">EN</button>
                    <button type="button" onclick="switchLanguage('bn')" id="btnLangBn" class="px-2.5 py-1 rounded-full text-slate-400 hover:text-white transition-all text-xs">বাং</button>
                </div>

                <?php if (!empty($currentUser)): ?>
                    <a href="<?= is_platform_admin() ? url('/admin/platform') : url('/admin/dashboard') ?>" 
                       class="px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl brand-gradient-btn text-white text-xs font-bold transition-all flex items-center gap-2 group whitespace-nowrap shadow-md shadow-brand-500/20">
                        <span>Dashboard</span>
                        <span class="group-hover:translate-x-0.5 transition-transform">→</span>
                    </a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="hidden sm:inline-flex text-xs font-bold text-slate-300 hover:text-white transition-colors px-3 py-2 rounded-lg hover:bg-white/5 whitespace-nowrap">
                        Sign In
                    </a>
                    <a href="<?= url('/register') ?>" class="shrink-0 px-3.5 py-2 sm:px-5 sm:py-2.5 rounded-xl brand-gradient-btn text-white text-xs font-bold transition-all flex items-center gap-1.5 group whitespace-nowrap shadow-md shadow-brand-500/20 hover:shadow-brand-500/35">
                        <span>Get Started Free</span>
                        <span class="group-hover:translate-x-0.5 transition-transform text-xs">→</span>
                    </a>
                <?php endif; ?>

                <!-- Mobile Hamburger Button -->
                <button type="button" onclick="toggleMobileMenu()" id="mobileMenuBtn" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 text-slate-300 hover:text-white transition-all focus:outline-none" aria-label="Toggle navigation menu">
                    <svg id="iconBurger" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="iconClose" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile & Tablet Drawer Menu -->
        <div id="mobileMenuDrawer" class="hidden md:hidden border-t border-white/[0.08] bg-dark-950/95 backdrop-blur-2xl transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 space-y-3">
                <nav class="flex flex-col space-y-1 font-medium text-sm text-slate-300">
                    <a href="#how-it-works" onclick="toggleMobileMenu()" class="px-3.5 py-2.5 rounded-xl hover:bg-white/[0.06] hover:text-white transition-all flex items-center justify-between">
                        <span>How It Works</span>
                        <span class="text-xs text-slate-500">→</span>
                    </a>
                    <a href="#features" onclick="toggleMobileMenu()" class="px-3.5 py-2.5 rounded-xl hover:bg-white/[0.06] hover:text-white transition-all flex items-center justify-between">
                        <span>Features</span>
                        <span class="text-xs text-slate-500">→</span>
                    </a>
                    <a href="#pricing" onclick="toggleMobileMenu()" class="px-3.5 py-2.5 rounded-xl hover:bg-white/[0.06] hover:text-white transition-all flex items-center justify-between">
                        <span>Pricing</span>
                        <span class="text-xs text-slate-500">→</span>
                    </a>
                    <a href="#faq" onclick="toggleMobileMenu()" class="px-3.5 py-2.5 rounded-xl hover:bg-white/[0.06] hover:text-white transition-all flex items-center justify-between">
                        <span>FAQ</span>
                        <span class="text-xs text-slate-500">→</span>
                    </a>
                </nav>
                
                <div class="pt-3 border-t border-white/[0.08] flex items-center justify-between gap-3">
                    <div class="flex items-center bg-white/[0.04] p-1 rounded-full border border-white/10 text-xs font-bold">
                        <button type="button" onclick="switchLanguage('en')" id="btnMobileLangEn" class="px-3 py-1 rounded-full bg-brand-600 text-white transition-all shadow-xs text-xs">EN</button>
                        <button type="button" onclick="switchLanguage('bn')" id="btnMobileLangBn" class="px-3 py-1 rounded-full text-slate-400 hover:text-white transition-all text-xs">বাং</button>
                    </div>
                    
                    <?php if (empty($currentUser)): ?>
                        <a href="<?= url('/login') ?>" class="text-xs font-bold text-slate-300 hover:text-white px-4 py-2 rounded-xl bg-white/[0.04] border border-white/10">
                            Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- ================= HERO SECTION (HIGH IMPACT) ================= -->
    <section class="relative pt-16 pb-24 lg:pt-24 lg:pb-32 overflow-hidden ambient-mesh border-b border-white/[0.08]">
        
        <!-- Ambient Background Glow Balls -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[350px] bg-brand-600/15 blur-[120px] rounded-full pointer-events-none"></div>
        <div class="absolute top-1/3 right-10 w-[400px] h-[300px] bg-purple-600/15 blur-[100px] rounded-full pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                
                <!-- Left: Value Proposition -->
                <div class="lg:col-span-7 space-y-7 text-center lg:text-left">
                    
                    <!-- Eyebrow Pill Badge -->
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full glass-pill border-brand-500/30 text-brand-300 text-xs font-bold tracking-wide">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                        </span>
                        <span id="heroBadgeText">🚀 3x Higher Conversion Than Static Web Forms</span>
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.1] font-display">
                        <span id="heroHead1" class="text-white">Turn cold visitors into</span><br>
                        <span id="heroHead2" class="brand-gradient-text">high-intent qualified leads</span><br>
                        <span id="heroHead3" class="text-slate-300">with interactive quizzes</span>
                    </h1>

                    <!-- Subtitle -->
                    <p id="heroSubText" class="text-base sm:text-lg text-slate-300 max-w-xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        Boring static forms suffer from 95% drop-off. QFunnel builds visual branching quizzes that engage visitors, score intent in real time, and automatically push qualified payloads to Google Sheets & your CRM.
                    </p>

                    <!-- CTAs -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-1">
                        <a href="<?= url('/register') ?>" class="px-8 py-4 rounded-xl brand-gradient-btn text-white text-sm font-bold transition-all flex items-center gap-2 group">
                            <span id="heroCtaText">Build Your First Funnel Free</span>
                            <span class="group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                        <a href="#builder-showcase" class="px-7 py-4 rounded-xl glass-pill hover:bg-white/[0.08] text-white border border-white/[0.15] text-sm font-bold transition-all flex items-center gap-2 group">
                            <span class="text-brand-400">✨</span>
                            <span>Watch Live Visual Builder</span>
                        </a>
                    </div>

                    <!-- Trust Metrics & Guarantees -->
                    <div class="pt-2 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-slate-400">
                        <div class="flex items-center gap-2">
                            <div class="flex text-amber-400 text-sm">★★★★★</div>
                            <span class="font-bold text-white">4.9/5 Rating</span>
                            <span class="text-slate-500">(1,200+ Creators)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>Free plan available</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>No coding required</span>
                        </div>
                    </div>

                </div>

                <!-- Right: Interactive Live Quiz Funnel Node Simulator -->
                <div class="lg:col-span-5 relative">
                    
                    <!-- Decorative Gradient Backdrop Glow -->
                    <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-purple-600 rounded-3xl blur-xl opacity-30 group-hover:opacity-100 transition duration-1000 group-hover:duration-200"></div>

                    <!-- Simulator Card Container -->
                    <div class="relative rounded-3xl ambient-card p-6 sm:p-7 border border-white/[0.12] shadow-2xl space-y-5">
                        
                        <!-- Window Chrome Bar -->
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                                    <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                                    <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                                </div>
                                <span class="text-xs font-bold text-slate-300 font-mono">live-quiz-player.js</span>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Live Node Active
                            </span>
                        </div>

                        <!-- Quiz Meta Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-brand-500 text-white flex items-center justify-center font-bold text-xs shadow-md">
                                    QF
                                </div>
                                <div class="leading-tight">
                                    <h4 class="text-xs font-bold text-white">SaaS Lead Qualification Funnel</h4>
                                    <span class="text-[11px] text-slate-400">Multi-Step Branching Engine</span>
                                </div>
                            </div>
                            <span class="text-[11px] font-mono text-brand-300 font-semibold">Step 2 of 3</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-[11px] text-slate-400 font-medium">
                                <span>Completion Progress</span>
                                <span class="text-brand-300 font-bold" id="demoProgressText">66%</span>
                            </div>
                            <div class="w-full bg-white/[0.06] h-2 rounded-full overflow-hidden p-0.5 border border-white/[0.06]">
                                <div id="demoProgressBar" class="bg-gradient-to-r from-brand-500 to-indigo-400 h-full rounded-full transition-all duration-500" style="width: 66%;"></div>
                            </div>
                        </div>

                        <!-- Question Block -->
                        <div class="pt-1">
                            <span class="text-[10px] font-bold text-brand-300 bg-brand-500/10 px-2.5 py-1 rounded-md border border-brand-500/20 uppercase tracking-wider font-mono">
                                Branching Decision Node
                            </span>
                            <h3 class="text-sm sm:text-base font-bold text-white mt-2.5">
                                What is your team's monthly lead generation volume?
                            </h3>
                        </div>

                        <!-- Clickable Interactive Option Selector -->
                        <div class="space-y-2.5" id="heroDemoOptions">
                            <button type="button" onclick="selectHeroAnswer(this, 15, 'Tier A: Starter Route (1-50 leads)')" class="demo-card w-full p-3.5 rounded-xl border border-white/[0.1] bg-white/[0.03] hover:bg-white/[0.06] hover:border-brand-500/50 text-xs font-semibold text-slate-200 flex items-center justify-between transition-all text-left">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-white/[0.06] flex items-center justify-center font-mono text-[11px] text-slate-400 font-bold">A</span>
                                    <span>Under 100 leads / month</span>
                                </div>
                                <span class="text-[11px] font-mono text-slate-400">+10 pts</span>
                            </button>

                            <button type="button" onclick="selectHeroAnswer(this, 35, 'Tier B: Growth Route (100-1,000 leads)')" class="demo-card active w-full p-3.5 rounded-xl border-2 border-brand-500 bg-brand-500/15 text-xs font-bold text-white flex items-center justify-between transition-all text-left shadow-lg shadow-brand-500/10">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-brand-500 text-white flex items-center justify-center font-mono text-[11px] font-bold">B</span>
                                    <span>100 – 1,000 leads / month</span>
                                </div>
                                <span class="text-[10px] bg-brand-500 text-white px-2 py-0.5 rounded-full font-bold">Selected ✓</span>
                            </button>

                            <button type="button" onclick="selectHeroAnswer(this, 50, 'Tier C: Enterprise VIP Route (1,000+ leads)')" class="demo-card w-full p-3.5 rounded-xl border border-white/[0.1] bg-white/[0.03] hover:bg-white/[0.06] hover:border-brand-500/50 text-xs font-semibold text-slate-200 flex items-center justify-between transition-all text-left">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-white/[0.06] flex items-center justify-center font-mono text-[11px] text-slate-400 font-bold">C</span>
                                    <span>Over 1,000+ leads / month</span>
                                </div>
                                <span class="text-[11px] font-mono text-slate-400">+50 pts</span>
                            </button>
                        </div>

                        <!-- Real-time Computed Logic Branch & Webhook Preview -->
                        <div class="p-3.5 rounded-xl bg-dark-900 border border-white/[0.08] space-y-2 text-xs">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400 flex items-center gap-1.5 font-mono">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                    Computed Route:
                                </span>
                                <span class="text-brand-300 font-bold font-mono" id="heroScoreDisplay">Score: 85/100</span>
                            </div>
                            <div class="font-semibold text-white flex items-center gap-2">
                                <span class="text-brand-400">⚡</span>
                                <span id="heroRouteText">Tier B: Growth Route (100-1,000 leads)</span>
                            </div>
                            <div class="pt-1 border-t border-white/[0.06] flex items-center justify-between text-[11px] text-slate-400">
                                <span>Automation:</span>
                                <span class="text-emerald-400 font-medium">Auto-Sync to Google Sheets & CRM ✓</span>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ================= METRICS & STATS SECTION ================= -->
    <section class="py-12 bg-dark-900/60 border-b border-white/[0.08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="space-y-1">
                    <span class="text-3xl sm:text-4xl font-black text-brand-400 font-display block">310%</span>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Avg. Lead Lift</p>
                </div>
                <div class="space-y-1">
                    <span class="text-3xl sm:text-4xl font-black text-white font-display block">&lt; 45s</span>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Completion Time</p>
                </div>
                <div class="space-y-1">
                    <span class="text-3xl sm:text-4xl font-black text-emerald-400 font-display block">99.9%</span>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Webhook Reliability</p>
                </div>
                <div class="space-y-1">
                    <span class="text-3xl sm:text-4xl font-black text-purple-400 font-display block">1-Click</span>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Universal Embed</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= HOW IT WORKS (3-STEP ARCHITECTURE) ================= -->
    <section id="how-it-works" class="py-20 lg:py-28 bg-dark-950 border-b border-white/[0.08] relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <span class="text-xs font-bold tracking-widest uppercase text-brand-400 font-mono">HOW IT WORKS</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight font-display">
                    From idea to live lead engine in 3 simple steps
                </h2>
                <p class="text-base text-slate-400">
                    Replace complicated form builders with an interactive visual quiz funnel that works seamlessly everywhere.
                </p>
            </div>

            <!-- 3 Step Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                
                <!-- Step 1 -->
                <div class="ambient-card ambient-card-hover p-8 rounded-3xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-white/[0.06] text-white flex items-center justify-center font-black text-base font-mono border border-white/[0.1]">
                            01
                        </div>
                        <span class="text-2xl">🎨</span>
                    </div>
                    <h3 class="text-lg font-bold text-white">Design Visual Logic Flow</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Add branching question nodes, scoring calculations, and custom pre-quiz lead capture fields on a high-speed visual canvas.
                    </p>
                    <div class="pt-2">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-brand-400 font-mono">
                            ✓ Zero coding required
                        </span>
                    </div>
                </div>

                <!-- Step 2 (Highlighted) -->
                <div class="ambient-card ambient-card-hover p-8 rounded-3xl border-2 border-brand-500 space-y-4 relative shadow-xl shadow-brand-500/10">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl brand-gradient-btn text-white flex items-center justify-center font-black text-base font-mono shadow-md">
                            02
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-brand-500/20 text-brand-300 text-[10px] font-bold border border-brand-500/30 font-mono">
                            UNIVERSAL EMBED
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-white">Embed Anywhere in 1 Click</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Copy a lightweight script snippet. Paste it into WordPress, Shopify, Webflow, Squarespace, or any custom HTML landing page.
                    </p>
                    <div class="pt-2">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400 font-mono">
                            ✓ Inline, Popup & Floating Bubble
                        </span>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="ambient-card ambient-card-hover p-8 rounded-3xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-white/[0.06] text-white flex items-center justify-center font-black text-base font-mono border border-white/[0.1]">
                            03
                        </div>
                        <span class="text-2xl">⚡</span>
                    </div>
                    <h3 class="text-lg font-bold text-white">Automate Leads & CRM Webhooks</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Every submission automatically syncs to Google Sheets and dispatches secured HMAC-signed webhooks to your CRM or Zapier.
                    </p>
                    <div class="pt-2">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-purple-400 font-mono">
                            ✓ Auto-retry & delivery audit logs
                        </span>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ================= MOVING VISUAL BUILDER SHOWCASE (LIVE PRODUCT FEATURE) ================= -->
    <section id="builder-showcase" class="py-20 lg:py-28 bg-dark-900 border-b border-white/[0.08] relative overflow-hidden">
        
        <!-- Ambient Glowing Background Lights -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[450px] bg-brand-600/10 blur-[140px] rounded-full pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-12 space-y-3">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-500/15 text-brand-300 border border-brand-500/30 text-xs font-bold font-mono uppercase tracking-wide">
                    <span class="w-2 h-2 rounded-full bg-brand-400 animate-ping"></span>
                    <span>Live Moving Canvas Engine</span>
                </div>
                <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight font-display">
                    Visual branching builder. Moving in real time.
                </h2>
                <p class="text-base text-slate-400">
                    Experience our dynamic graph architecture in action. Watch simulated leads flow through decision branches, or grab and drag any node yourself!
                </p>
            </div>

            <!-- Canvas Window Container -->
            <div class="rounded-3xl border border-white/[0.12] ambient-card overflow-hidden shadow-2xl">
                
                <!-- Mac-Style Window Top Bar -->
                <div class="bg-dark-950/90 border-b border-white/[0.08] px-5 py-3.5 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                        </div>
                        <div class="flex items-center gap-2 pl-3 border-l border-white/[0.1] text-xs font-mono">
                            <span class="font-bold text-white">Visual Graph Builder</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold border border-emerald-500/30">Auto-Router Active</span>
                        </div>
                    </div>

                    <!-- Builder Action Controls -->
                    <div class="flex items-center gap-2.5 text-xs">
                        <div class="hidden sm:flex items-center gap-1.5 glass-pill px-3 py-1 rounded-lg text-slate-300 font-mono text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>6 Nodes Synced</span>
                        </div>
                        <button type="button" onclick="toggleTourMode()" id="btnTourToggle" class="px-3.5 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white font-bold transition-all flex items-center gap-1.5 shadow-sm">
                            <span id="tourIcon">⏸</span>
                            <span id="tourLabel">Pause Tour</span>
                        </button>
                        <button type="button" onclick="resetCanvasLayout()" class="px-3 py-1.5 rounded-lg bg-white/[0.06] hover:bg-white/[0.1] text-slate-300 font-bold transition-all">
                            Reset View ↺
                        </button>
                    </div>
                </div>

                <!-- Canvas Body with Dot Grid & Virtual Cursor -->
                <div id="showcaseCanvasContainer" class="relative canvas-grid w-full h-[540px] overflow-x-auto overflow-y-hidden select-none">
                    
                    <div id="innerCanvasWorld" class="relative w-[1300px] h-[540px]">
                        
                        <!-- Virtual Animated User Cursor -->
                        <div id="virtualCursor" class="absolute z-30 pointer-events-none transition-all duration-700 ease-out flex items-center gap-2" style="top: 220px; left: 160px;">
                            <svg class="w-5 h-5 text-brand-400 drop-shadow-lg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 2l16 12-7 1.5 4 8-3 1.5-4-8-4 5z"/>
                            </svg>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-brand-500 text-white shadow-xl whitespace-nowrap font-mono flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-ping"></span>
                                Alex (Automating Graph...)
                            </span>
                        </div>

                        <!-- SVG Cables Surface -->
                        <svg id="canvasSvgSurface" class="absolute inset-0 w-full h-full pointer-events-none z-10">
                            <defs>
                                <linearGradient id="cableGradPrimary" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#6366F1"/>
                                    <stop offset="100%" stop-color="#818CF8"/>
                                </linearGradient>
                                <linearGradient id="cableGradSuccess" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#6366F1"/>
                                    <stop offset="100%" stop-color="#10B981"/>
                                </linearGradient>
                                <linearGradient id="cableGradPurple" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#6366F1"/>
                                    <stop offset="100%" stop-color="#C084FC"/>
                                </linearGradient>
                                <filter id="glowFilter">
                                    <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                    <feMerge>
                                        <feMergeNode in="coloredBlur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                            </defs>

                            <!-- Base background wires -->
                            <path id="wireBase1" d="" fill="none" stroke="#23304A" stroke-width="2.5" stroke-linecap="round"/>
                            <path id="wireBase2" d="" fill="none" stroke="#23304A" stroke-width="2.5" stroke-linecap="round"/>
                            <path id="wireBase3A" d="" fill="none" stroke="#23304A" stroke-width="2.5" stroke-linecap="round"/>
                            <path id="wireBase3B" d="" fill="none" stroke="#23304A" stroke-width="2.5" stroke-linecap="round"/>
                            <path id="wireBase4" d="" fill="none" stroke="#23304A" stroke-width="2.5" stroke-linecap="round"/>
                            <path id="wireBase5" d="" fill="none" stroke="#23304A" stroke-width="2.5" stroke-linecap="round"/>

                            <!-- Glowing Animated Flow Cables -->
                            <path id="wireFlow1" d="" fill="none" stroke="url(#cableGradPrimary)" stroke-width="2.5" stroke-linecap="round" class="cable-flow"/>
                            <path id="wireFlow2" d="" fill="none" stroke="url(#cableGradPrimary)" stroke-width="2.5" stroke-linecap="round" class="cable-flow"/>
                            <path id="wireFlow3A" d="" fill="none" stroke="url(#cableGradSuccess)" stroke-width="2.5" stroke-linecap="round" class="cable-flow"/>
                            <path id="wireFlow3B" d="" fill="none" stroke="url(#cableGradPurple)" stroke-width="2.5" stroke-linecap="round" class="cable-flow"/>
                            <path id="wireFlow4" d="" fill="none" stroke="url(#cableGradSuccess)" stroke-width="2.5" stroke-linecap="round" class="cable-flow"/>
                            <path id="wireFlow5" d="" fill="none" stroke="url(#cableGradPurple)" stroke-width="2.5" stroke-linecap="round" class="cable-flow"/>

                            <!-- Traveling energy packets along the wires -->
                            <circle r="4" fill="#818CF8" filter="url(#glowFilter)">
                                <animateMotion id="motionPacket1" dur="2.4s" repeatCount="indefinite" path=""/>
                            </circle>
                            <circle r="4" fill="#34D399" filter="url(#glowFilter)">
                                <animateMotion id="motionPacket2" dur="2.8s" repeatCount="indefinite" path=""/>
                            </circle>
                            <circle r="4" fill="#C084FC" filter="url(#glowFilter)">
                                <animateMotion id="motionPacket3" dur="3.2s" repeatCount="indefinite" path=""/>
                            </circle>
                        </svg>

                        <!-- Nodes Layer (z-20) -->
                        <div id="canvasNodesLayer" class="absolute inset-0 z-20">
                            
                            <!-- NODE 1: Start Trigger -->
                            <div id="node1" class="builder-node active-step absolute w-60 rounded-2xl bg-dark-900/95 border border-white/[0.12] p-4 shadow-xl hover:border-brand-500 cursor-move" style="top: 190px; left: 40px;" onmousedown="startNodeDrag(event, 'node1')">
                                <div class="flex items-center justify-between border-b border-white/[0.08] pb-2.5 mb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-brand-500/20 text-brand-400 flex items-center justify-center text-xs">🌐</span>
                                        <span class="text-xs font-bold text-white">Start Trigger</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white/[0.06] text-slate-400 font-mono">Input</span>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <div class="font-semibold text-slate-200">Universal Embed Hook</div>
                                    <p class="text-[11px] text-slate-400">WordPress, Shopify & Webflow traffic</p>
                                </div>
                                <!-- Output port circle -->
                                <div class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-brand-500 border-2 border-dark-950 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </div>
                            </div>

                            <!-- NODE 2: Question 1 -->
                            <div id="node2" class="builder-node absolute w-64 rounded-2xl bg-dark-900/95 border border-white/[0.12] p-4 shadow-xl hover:border-brand-500 cursor-move" style="top: 130px; left: 320px;" onmousedown="startNodeDrag(event, 'node2')">
                                <!-- Input port -->
                                <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-700 border-2 border-dark-950"></div>
                                <div class="flex items-center justify-between border-b border-white/[0.08] pb-2.5 mb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs">❓</span>
                                        <span class="text-xs font-bold text-white">Branching Question</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-300 font-mono">Q1</span>
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div class="font-semibold text-slate-200 leading-snug">What is your primary growth bottleneck?</div>
                                    <div class="space-y-1 text-[11px]">
                                        <div class="p-1.5 rounded bg-white/[0.04] text-slate-300 flex justify-between">
                                            <span>A) Low website traffic</span>
                                            <span class="text-slate-500 font-mono">+10 pts</span>
                                        </div>
                                        <div id="node2OptionB" class="p-1.5 rounded bg-brand-500/20 text-brand-300 border border-brand-500/30 flex justify-between font-semibold transition-all">
                                            <span>B) Unqualified leads</span>
                                            <span class="text-brand-400 font-mono">+35 pts</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Output port -->
                                <div class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-brand-500 border-2 border-dark-950 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </div>
                            </div>

                            <!-- NODE 3: Conditional Branch Evaluation -->
                            <div id="node3" class="builder-node absolute w-64 rounded-2xl bg-dark-900/95 border border-white/[0.12] p-4 shadow-xl hover:border-brand-500 cursor-move" style="top: 150px; left: 620px;" onmousedown="startNodeDrag(event, 'node3')">
                                <!-- Input port -->
                                <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-700 border-2 border-dark-950"></div>
                                <div class="flex items-center justify-between border-b border-white/[0.08] pb-2.5 mb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">🔀</span>
                                        <span class="text-xs font-bold text-white">Dynamic Logic Split</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-brand-500 text-white font-mono">Branch</span>
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div id="branchRowA" class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 flex items-center justify-between transition-all">
                                        <span class="font-bold">Score ≥ 35 (VIP Priority)</span>
                                        <span class="text-[10px] font-mono">Route A ➔</span>
                                    </div>
                                    <div id="branchRowB" class="p-2 rounded-lg bg-white/[0.03] text-slate-400 flex items-center justify-between transition-all">
                                        <span>Score &lt; 35 (Nurture)</span>
                                        <span class="text-[10px] font-mono">Route B ➔</span>
                                    </div>
                                </div>
                                <!-- Output port A (top right) -->
                                <div class="absolute -right-2 top-1/3 -translate-y-1/2 w-4 h-4 rounded-full bg-emerald-500 border-2 border-dark-950 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </div>
                                <!-- Output port B (bottom right) -->
                                <div class="absolute -right-2 top-2/3 -translate-y-1/2 w-4 h-4 rounded-full bg-purple-500 border-2 border-dark-950 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </div>
                            </div>

                            <!-- NODE 4: Form Capture (Route A) -->
                            <div id="node4" class="builder-node absolute w-60 rounded-2xl bg-dark-900/95 border border-white/[0.12] p-4 shadow-xl hover:border-brand-500 cursor-move" style="top: 50px; left: 920px;" onmousedown="startNodeDrag(event, 'node4')">
                                <!-- Input port -->
                                <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-700 border-2 border-dark-950"></div>
                                <div class="flex items-center justify-between border-b border-white/[0.08] pb-2 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">📝</span>
                                        <span class="text-xs font-bold text-white">VIP Lead Intake</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 font-mono">Form</span>
                                </div>
                                <div class="space-y-1 text-[11px] text-slate-300">
                                    <div>• Verified Work Email</div>
                                    <div>• Direct Phone / WhatsApp</div>
                                    <div>• Budget Range Selector</div>
                                </div>
                                <!-- Output port -->
                                <div class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-brand-500 border-2 border-dark-950 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </div>
                            </div>

                            <!-- NODE 5: Outcome Nurture (Route B) -->
                            <div id="node5" class="builder-node absolute w-60 rounded-2xl bg-dark-900/95 border border-white/[0.12] p-4 shadow-xl hover:border-brand-500 cursor-move" style="top: 320px; left: 920px;" onmousedown="startNodeDrag(event, 'node5')">
                                <!-- Input port -->
                                <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-700 border-2 border-dark-950"></div>
                                <div class="flex items-center justify-between border-b border-white/[0.08] pb-2 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs">🎯</span>
                                        <span class="text-xs font-bold text-white">Self-Serve Nurture</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-400 font-mono">Outcome</span>
                                </div>
                                <div class="space-y-1 text-[11px] text-slate-300">
                                    <div>• Personalized Audit Report</div>
                                    <div>• Free Video Guide Link</div>
                                    <div>• Recommended Starter Plan</div>
                                </div>
                                <!-- Output port -->
                                <div class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-brand-500 border-2 border-dark-950 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </div>
                            </div>

                            <!-- NODE 6: Real-Time Webhook & Google Sheets -->
                            <div id="node6" class="builder-node absolute w-64 rounded-2xl bg-dark-900/95 border border-emerald-500/40 p-4 shadow-xl hover:border-emerald-400 cursor-move" style="top: 175px; left: 1200px;" onmousedown="startNodeDrag(event, 'node6')">
                                <!-- Input ports -->
                                <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-700 border-2 border-dark-950"></div>
                                <div class="flex items-center justify-between border-b border-white/[0.08] pb-2 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">⚡</span>
                                        <span class="text-xs font-bold text-white">Automated Webhooks</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 font-mono" id="node6StatusPill">200 OK</span>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <div class="font-semibold text-slate-200">Google Sheets & CRM Sync</div>
                                    <p class="text-[11px] text-emerald-400 font-mono">HMAC-SHA256 • 28ms Latency</p>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Canvas Right Drawer Inspector (Live Node Properties) -->
                    <div id="builderInspectorDrawer" class="absolute top-4 right-4 z-30 w-72 bg-dark-950/95 backdrop-blur-xl border border-white/[0.12] rounded-2xl p-4 shadow-2xl space-y-3 hidden sm:block">
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-2">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-mono">Step Inspector</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase font-mono block">Node Type</span>
                                <strong class="text-white text-xs" id="inspectorNodeTitle">Branching Logic Split</strong>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase font-mono block">Condition Trigger</span>
                                <span class="font-mono text-brand-300 text-[11px]" id="inspectorCondition">if (score >= 35) ➔ VIP Route</span>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase font-mono block">Automation Target</span>
                                <span class="font-mono text-emerald-400 text-[11px]">Google Sheets + HubSpot CRM</span>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-white/[0.08] flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">Graph Sync:</span>
                            <span class="text-emerald-400 font-bold">Auto-Saved ✓</span>
                        </div>
                    </div>

                </div>

                <!-- Canvas Bottom Execution Stream Bar -->
                <div class="bg-dark-950 border-t border-white/[0.08] px-5 py-3 flex flex-wrap items-center justify-between gap-4 text-xs font-mono">
                    <div class="flex items-center gap-3">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-slate-400">Live Execution Event:</span>
                        <span id="canvasEventTicker" class="text-slate-200 font-semibold">[15:37:12] Visitor #1289 evaluated ➔ Selected Option B (+35 pts) ➔ Routed to VIP Funnel ➔ Webhook 200 OK (28ms)</span>
                    </div>

                    <div class="flex items-center gap-4 text-slate-400 text-[11px]">
                        <span>Graph Latency: <strong class="text-emerald-400">28ms</strong></span>
                        <span>Delivery Rate: <strong class="text-white">100%</strong></span>
                    </div>
                </div>

            </div>

            <!-- Bottom CTA Link -->
            <div class="mt-8 text-center">
                <a href="<?= url('/register') ?>" class="inline-flex items-center gap-2 text-sm font-bold text-brand-400 hover:text-brand-300 transition-colors">
                    <span>Try the full drag-and-drop visual builder with your free account</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ================= 3-IN-1 EMBED SHOWCASE SECTION ================= -->
    <section id="embed-showcase" class="py-20 lg:py-28 bg-dark-950 border-b border-white/[0.08] relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <span class="text-xs font-bold tracking-widest uppercase text-brand-400 font-mono">UNIVERSAL COMPATIBILITY</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight font-display">
                    3 flexible ways to embed on any website
                </h2>
                <p class="text-base text-slate-400">
                    Whether you run WordPress, Shopify, Webflow, or a custom landing page, QFunnel fits your site structure perfectly.
                </p>
            </div>

            <!-- Tab Switcher -->
            <div class="flex justify-center mb-10">
                <div class="inline-flex p-1.5 rounded-2xl bg-white/[0.04] border border-white/[0.1] gap-1">
                    <button type="button" onclick="switchEmbedTab('inline')" id="tabBtnInline" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all bg-brand-600 text-white shadow-md">
                        1. Inline Embed
                    </button>
                    <button type="button" onclick="switchEmbedTab('modal')" id="tabBtnModal" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all text-slate-400 hover:text-white">
                        2. Lightbox Modal
                    </button>
                    <button type="button" onclick="switchEmbedTab('bubble')" id="tabBtnBubble" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all text-slate-400 hover:text-white">
                        3. Floating Corner Bubble
                    </button>
                </div>
            </div>

            <!-- Dynamic Tab Content Container -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center ambient-card p-6 sm:p-10 rounded-3xl border border-white/[0.1]">
                
                <!-- Left: Embed Details & Code Snippet -->
                <div class="lg:col-span-6 space-y-6">
                    <div id="embedContentText" class="space-y-3">
                        <span class="px-3 py-1 rounded-full bg-brand-500/20 text-brand-300 text-xs font-bold font-mono border border-brand-500/30">
                            Mode: Inline Embed
                        </span>
                        <h3 class="text-2xl font-bold text-white font-display">
                            Embed directly inside your blog posts or landing page
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Perfect for interactive buyer guides, diagnostic quizzes, and product finders embedded right in the flow of your article or high-converting sales page.
                        </p>
                    </div>

                    <!-- Code Snippet Box with Copy Button -->
                    <div class="rounded-2xl bg-dark-950 border border-white/[0.1] p-4 space-y-3 font-mono text-xs">
                        <div class="flex items-center justify-between text-slate-400 border-b border-white/[0.08] pb-2">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-brand-400"></span>
                                embed-snippet.html
                            </span>
                            <button type="button" onclick="copySnippetCode()" id="btnCopySnippet" class="text-brand-400 hover:text-brand-300 font-bold transition-colors">
                                Copy Snippet 📋
                            </button>
                        </div>
                        <pre class="overflow-x-auto text-emerald-400 text-[11px] leading-relaxed p-1" id="snippetCodeDisplay">&lt;div id="qfunnel-container" data-funnel-id="YOUR_SLUG"&gt;&lt;/div&gt;
&lt;script src="<?= url('/assets/js/embed.js') ?>" async&gt;&lt;/script&gt;</pre>
                    </div>

                    <div class="flex items-center gap-6 text-xs text-slate-400">
                        <div class="flex items-center gap-2">
                            <span>✅ WordPress Compatible</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>✅ Shopify Compatible</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>✅ Webflow & Squarespace</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Visual Mockup Container -->
                <div class="lg:col-span-6">
                    <div class="relative bg-dark-950 rounded-2xl border border-white/[0.1] p-6 shadow-2xl min-h-[320px] flex flex-col justify-center items-center text-center">
                        
                        <div id="embedVisualMockup" class="w-full max-w-sm space-y-4">
                            <!-- Visual representation -->
                            <div class="p-5 rounded-2xl bg-white/[0.04] border border-brand-500/40 space-y-3">
                                <div class="w-8 h-8 rounded-lg brand-gradient-btn mx-auto flex items-center justify-center text-white font-bold text-xs">
                                    Q
                                </div>
                                <h4 class="text-xs font-bold text-white">Find Your Ideal Solution in 60s</h4>
                                <p class="text-[11px] text-slate-400">Answer 3 quick questions for custom pricing</p>
                                <button type="button" class="w-full py-2.5 rounded-lg brand-gradient-btn text-white text-xs font-bold">
                                    Start Interactive Quiz →
                                </button>
                            </div>
                            <span class="text-[11px] text-slate-500 font-mono block">Inline Container (Responsive Auto-Height)</span>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ================= FEATURES GRID SECTION ================= -->
    <section id="features" class="py-20 lg:py-28 bg-dark-900 border-b border-white/[0.08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <span class="text-xs font-bold tracking-widest uppercase text-brand-400 font-mono">POWERFUL TOOLSET</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight font-display">
                    Everything you need to convert & qualify leads
                </h2>
                <p class="text-base text-slate-400">
                    Engineered for growth marketing teams, agencies, and high-velocity SaaS businesses.
                </p>
            </div>

            <!-- 6 Grid Feature Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 mb-16">
                
                <!-- Feature 1: Canvas Builder -->
                <div class="ambient-card ambient-card-hover p-7 rounded-3xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-brand-500/10 text-brand-400 border border-brand-500/20 flex items-center justify-center font-bold text-xl">
                            🎨
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">VISUAL BUILDER</span>
                    </div>
                    <h3 class="text-base font-bold text-white">Drag & Drop Funnel Builder</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Design question cards, conditional branches, score thresholds, and outcome destinations on an intuitive visual canvas.
                    </p>
                    <div class="pt-2">
                        <a href="<?= url('/register') ?>" class="text-xs font-bold text-brand-400 hover:text-brand-300 flex items-center gap-1">
                            Explore Visual Builder <span>→</span>
                        </a>
                    </div>
                </div>

                <!-- Feature 2: Universal Embeds -->
                <div class="ambient-card ambient-card-hover p-7 rounded-3xl border-2 border-brand-500/40 space-y-4 relative shadow-lg shadow-brand-500/5">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center justify-center font-bold text-xl">
                            🌐
                        </div>
                        <span class="text-[10px] font-bold text-brand-300 bg-brand-500/20 px-2 py-0.5 rounded-full uppercase tracking-wider font-mono">
                            3-IN-1 EMBEDS
                        </span>
                    </div>
                    <h3 class="text-base font-bold text-white">Universal Website Embeds</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Inline embedded quiz, popup lightbox modal, or floating corner bubble widget for WordPress, Shopify, and Webflow.
                    </p>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="text-[11px] font-bold text-emerald-400 flex items-center gap-1">• Live Widget Active</span>
                        <a href="<?= url('/embed-demo.html') ?>" class="text-brand-400 font-bold text-xs hover:underline">Demo →</a>
                    </div>
                </div>

                <!-- Feature 3: Webhooks & Google Sheets -->
                <div class="ambient-card ambient-card-hover p-7 rounded-3xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center font-bold text-xl">
                            ⚡
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">AUTOMATION</span>
                    </div>
                    <h3 class="text-base font-bold text-white">Webhooks & Google Sheets</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Automatic real-time payload delivery with auto-retry logic, event delivery timelines, and HMAC signature protection.
                    </p>
                    <div class="pt-2">
                        <a href="<?= url('/register') ?>" class="text-xs font-bold text-brand-400 hover:text-brand-300 flex items-center gap-1">
                            Explore Webhook Engine <span>→</span>
                        </a>
                    </div>
                </div>

                <!-- Feature 4: Lead Capture & Pre-Quiz Forms -->
                <div class="ambient-card ambient-card-hover p-7 rounded-3xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center font-bold text-xl">
                            📝
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">LEAD CAPTURE</span>
                    </div>
                    <h3 class="text-base font-bold text-white">Custom Pre-Quiz Form Fields</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Collect Name, Email, Phone/WhatsApp, and custom intent criteria before questions begin to prevent drop-off loss.
                    </p>
                    <div class="pt-2">
                        <a href="<?= url('/register') ?>" class="text-xs font-bold text-brand-400 hover:text-brand-300 flex items-center gap-1">
                            Explore Forms <span>→</span>
                        </a>
                    </div>
                </div>

                <!-- Feature 5: Agency White-Labeling -->
                <div class="ambient-card ambient-card-hover p-7 rounded-3xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-400 border border-purple-500/20 flex items-center justify-center font-bold text-xl">
                            🏷️
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">AGENCY</span>
                    </div>
                    <h3 class="text-base font-bold text-white">100% White-Label Branding</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Upload custom logos, define brand accent colors, set trust badges, remove SaaS watermarks, and use custom domains.
                    </p>
                    <div class="pt-2">
                        <a href="<?= url('/register') ?>" class="text-xs font-bold text-brand-400 hover:text-brand-300 flex items-center gap-1">
                            Explore White-Label <span>→</span>
                        </a>
                    </div>
                </div>

                <!-- Feature 6: Developer REST API -->
                <div class="ambient-card ambient-card-hover p-7 rounded-3xl border border-white/[0.08] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 flex items-center justify-center font-bold text-xl">
                            &lt;/&gt;
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">V1 REST API</span>
                    </div>
                    <h3 class="text-base font-bold text-white">Developer REST API (v1)</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Bearer-token authenticated API endpoints to programmatically trigger funnels, query submissions, and sync analytics.
                    </p>
                    <div class="pt-2">
                        <a href="<?= url('/register') ?>" class="text-xs font-bold text-brand-400 hover:text-brand-300 flex items-center gap-1">
                            Explore API Docs <span>→</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ================= COMPARISON SECTION (TRADITIONAL FORMS VS QFUNNEL) ================= -->
    <section id="comparison" class="py-20 lg:py-28 bg-dark-950 border-b border-white/[0.08]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <span class="text-xs font-bold tracking-widest uppercase text-brand-400 font-mono">THE HARD TRUTH</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight font-display">
                    Why traditional forms lose 95% of your traffic
                </h2>
                <p class="text-base text-slate-400">
                    See how switching to an interactive quiz funnel dramatically transforms your visitor engagement.
                </p>
            </div>

            <div class="ambient-card rounded-3xl border border-white/[0.1] overflow-hidden shadow-2xl">
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-white/[0.08]">
                    
                    <!-- Left: Boring Static Forms -->
                    <div class="p-8 sm:p-10 space-y-6 bg-red-950/10">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-red-500/20 text-red-400 text-xs font-bold font-mono border border-red-500/30">
                                ❌ Traditional Static Forms
                            </span>
                            <span class="text-red-400 text-sm font-bold">2.3% Conversion</span>
                        </div>

                        <ul class="space-y-4 text-xs sm:text-sm text-slate-300">
                            <li class="flex items-start gap-3">
                                <span class="text-red-400 font-bold text-base">✕</span>
                                <span>Demands sensitive contact info immediately upfront</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-red-400 font-bold text-base">✕</span>
                                <span>Zero personalization — every visitor sees identical boring fields</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-red-400 font-bold text-base">✕</span>
                                <span>High bounce rate — visitors feel like they are doing unpaid work</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-red-400 font-bold text-base">✕</span>
                                <span>Manual CSV exports or delayed email notifications</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-red-400 font-bold text-base">✕</span>
                                <span>Unqualified leads waste valuable sales team hours</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Right: QFunnel Interactive Funnels -->
                    <div class="p-8 sm:p-10 space-y-6 bg-brand-950/20">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold font-mono border border-emerald-500/30">
                                🚀 QFunnel Interactive Quizzes
                            </span>
                            <span class="text-emerald-400 text-sm font-bold">18% – 35% Conversion</span>
                        </div>

                        <ul class="space-y-4 text-xs sm:text-sm text-slate-200">
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-bold text-base">✓</span>
                                <span>Interactive micro-commitments deliver instant curiosity & value</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-bold text-base">✓</span>
                                <span>Dynamic conditional branching based on specific visitor pain points</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-bold text-base">✓</span>
                                <span>Scores intent automatically so you prioritize high-budget buyers</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-bold text-base">✓</span>
                                <span>Instant automated sync to Google Sheets & CRM via webhooks</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-400 font-bold text-base">✓</span>
                                <span>1-click embed anywhere on WordPress, Shopify, and custom pages</span>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <!-- ================= PRICING SECTION ================= -->
    <section id="pricing" class="py-20 lg:py-28 bg-dark-900 border-b border-white/[0.08] relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-14 space-y-3">
                <span class="text-xs font-bold tracking-widest uppercase text-brand-400 font-mono">TRANSPARENT TIERS</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight font-display">
                    Pick the plan that fits your growth
                </h2>
                <p class="text-base text-slate-400">
                    Simple, transparent pricing for solo creators, growing marketing teams, and high-volume agencies.
                </p>

                <!-- Billing Frequency Switcher -->
                <div class="pt-4 flex items-center justify-center gap-3">
                    <span class="text-xs font-semibold text-slate-300" id="billingMonthlyLabel">Monthly</span>
                    <button type="button" onclick="togglePricingInterval()" id="pricingIntervalToggle" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-brand-600 transition-colors duration-200 ease-in-out">
                        <span id="pricingToggleThumb" class="translate-x-5 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                    <span class="text-xs font-semibold text-slate-300 flex items-center gap-1.5" id="billingAnnualLabel">
                        <span>Yearly</span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold border border-emerald-500/30">SAVE 20%</span>
                    </span>
                </div>
            </div>

            <!-- Dynamic Pricing Columns Grid -->
            <?php 
            $planCount = count($plans ?? []);
            $gridColsClass = 'grid-cols-1';
            if ($planCount === 2) {
                $gridColsClass = 'grid-cols-1 md:grid-cols-2 max-w-4xl mx-auto';
            } elseif ($planCount === 3) {
                $gridColsClass = 'grid-cols-1 md:grid-cols-3 max-w-6xl mx-auto';
            } elseif ($planCount >= 4) {
                $gridColsClass = 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4';
            } else {
                $gridColsClass = 'grid-cols-1 max-w-md mx-auto';
            }
            ?>
            <div class="grid <?= $gridColsClass ?> gap-6 lg:gap-8 items-stretch mb-12">
                <?php if (empty($plans)): ?>
                    <div class="col-span-full py-12 text-center text-slate-400">
                        <p class="text-sm">No subscription plans are currently available for public registration. Please contact our support team.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($plans as $p): 
                        $limits = json_decode($p['limits_json'] ?? '{}', true) ?: [];
                        $features = json_decode($p['features_json'] ?? '{}', true) ?: [];

                        $maxFunnels = (int)($limits['max_funnels'] ?? 1);
                        $maxSubmissions = (int)($limits['max_submissions_per_month'] ?? 100);
                        $maxTeam = (int)($limits['max_team_members'] ?? 1);

                        $funnelsLabel = ($maxFunnels <= 0 || $maxFunnels == -1) ? 'Unlimited Active Funnels' : ($maxFunnels == 1 ? '1 Active Funnel' : "{$maxFunnels} Active Funnels");
                        $submissionsLabel = ($maxSubmissions <= 0 || $maxSubmissions == -1) ? 'Unlimited Lead Submissions' : number_format($maxSubmissions) . ' Submissions / month';
                        $teamLabel = ($maxTeam <= 0 || $maxTeam == -1) ? 'Unlimited Workspace Seats' : ($maxTeam == 1 ? '1 Team Member' : "{$maxTeam} Team Members Workspace");

                        $priceVal = (float)$p['price'];
                        $isFree = ($priceVal <= 0);
                        $isPro = in_array(strtolower($p['slug']), ['pro', 'popular'], true) || ($planCount > 1 && strtolower($p['slug']) === 'pro');
                        $isAgency = in_array(strtolower($p['slug']), ['agency', 'enterprise'], true);

                        if ($isFree) {
                            $monthlyPriceDisplay = '₹0';
                            $yearlyPriceDisplay = '₹0';
                            $monthlyPeriod = ' / forever';
                            $yearlyPeriod = ' / forever';
                        } else {
                            if (($p['billing_interval'] ?? 'monthly') === 'yearly') {
                                $yearlyMonthlyEq = round($priceVal / 12);
                                $monthlyReg = round($yearlyMonthlyEq * 1.25);
                            } else {
                                $monthlyReg = round($priceVal);
                                $yearlyMonthlyEq = round($priceVal * 0.8);
                            }
                            $monthlyPriceDisplay = '₹' . number_format($monthlyReg);
                            $yearlyPriceDisplay = '₹' . number_format($yearlyMonthlyEq);
                            $monthlyPeriod = ' / month';
                            $yearlyPeriod = ' / mo (billed yearly)';
                        }
                    ?>
                        <div class="ambient-card ambient-card-hover flex flex-col justify-between p-7 rounded-3xl <?= $isPro ? 'border-2 border-brand-500 relative shadow-2xl shadow-brand-500/20 bg-gradient-to-b from-brand-950/40 via-dark-900 to-dark-950' : 'border border-white/[0.08]' ?>">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-bold text-white font-display"><?= e($p['name']) ?></h3>
                                    <?php if ($isPro): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-brand-500 text-white font-mono shadow-sm">MOST POPULAR</span>
                                    <?php elseif ($isFree): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/[0.08] text-slate-300 border border-white/[0.1] font-mono">FREE FOREVER</span>
                                    <?php elseif ($isAgency): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-400 border border-purple-500/30 font-mono">SCALE</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30 font-mono">GROWTH</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="text-3xl font-black text-white font-display dynamic-plan-price" data-monthly="<?= $monthlyPriceDisplay ?>" data-yearly="<?= $yearlyPriceDisplay ?>">
                                        <?= $yearlyPriceDisplay ?>
                                    </span>
                                    <span class="text-xs text-slate-400 font-mono dynamic-plan-period" data-monthly="<?= $monthlyPeriod ?>" data-yearly="<?= $yearlyPeriod ?>">
                                        <?= $yearlyPeriod ?>
                                    </span>
                                </div>
                                <p class="text-xs <?= $isPro ? 'text-slate-300' : 'text-slate-400' ?> leading-relaxed">
                                    <?= e($p['description'] ?: ($isFree ? 'Essential single-funnel plan for testing interactive quizzes and lead capture.' : 'Comprehensive plan designed for scaling interactive lead funnels.')) ?>
                                </p>
                                
                                <div class="pt-4 border-t <?= $isPro ? 'border-brand-500/30 text-slate-200' : 'border-white/[0.08] text-slate-300' ?> space-y-2.5 text-xs">
                                    <span class="text-[10px] font-bold <?= $isPro ? 'text-brand-400' : 'text-slate-400' ?> uppercase tracking-wider block mb-2 font-mono">
                                        <?= $isPro ? 'ALL PERKS INCLUDED:' : 'INCLUDED PERKS:' ?>
                                    </span>
                                    <div class="flex items-start gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <strong class="text-white"><?= e($funnelsLabel) ?></strong>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span><?= e($submissionsLabel) ?></span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span><?= e($teamLabel) ?></span>
                                    </div>
                                    <?php if (!empty($features['visual_builder']) || $isPro || $isFree): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>Visual Canvas Builder</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($features['calculator_mode']) || $isPro): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>Interactive Calculator & Formulas</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($features['webhooks']) || $isPro): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>Webhooks & Google Sheets Sync</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($features['api_access']) || $isPro): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>REST API (v1) with Bearer Token</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($features['white_label']) || $isPro): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>100% White-Label (No SaaS branding)</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($features['custom_branding']) || $isPro): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>Custom Brand Logo & Accent Colors</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($features['export_leads']) || $isPro): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>CSV Lead Data Export</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($features['audit_logs']) || $isAgency): ?>
                                        <div class="flex items-start gap-2">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>Full Audit Logs & Priority SLA</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-start gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Universal Embeds (WP & Shopify)</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Bilingual Support (English & Bengali)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6">
                                <a href="<?= url('/register?plan=' . urlencode($p['slug'])) ?>" class="w-full py-3 px-4 rounded-xl <?= $isPro ? 'brand-gradient-btn text-white text-xs font-bold transition-all text-center block shadow-lg' : 'bg-white/[0.08] hover:bg-white/[0.14] text-white text-xs font-bold transition-all text-center block border border-white/[0.1]' ?>">
                                    <?= $isFree ? 'Try for Free →' : ($isPro ? 'Get ' . e($p['name']) . ' Access →' : 'Choose ' . e($p['name']) . ' →') ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Lifetime Deal Highlight Card -->
            <div class="ambient-card p-8 rounded-3xl border border-brand-500/40 relative shadow-2xl max-w-3xl mx-auto bg-gradient-to-r from-brand-950/60 via-dark-900 to-brand-950/60">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-brand-500 text-white font-mono uppercase tracking-wider">
                                LIMITED LIFETIME DEAL
                            </span>
                            <span class="text-xs text-emerald-400 font-bold font-mono">One-Time Payment</span>
                        </div>
                        <h3 class="text-xl font-bold text-white font-display">Pay Once. Use QFunnel Forever.</h3>
                        <p class="text-xs text-slate-300 max-w-md">
                            Get all Pro Tier perks, unlimited funnels, webhooks, REST API access, and lifetime software updates without recurring monthly bills.
                        </p>
                    </div>

                    <div class="text-left sm:text-right space-y-2 flex-shrink-0">
                        <div class="text-3xl font-black text-white font-display">₹2,999</div>
                        <span class="text-xs text-slate-400 font-mono block">Zero Recurring Fees</span>
                        <a href="<?= url('/register') ?>" class="inline-block py-2.5 px-6 rounded-xl brand-gradient-btn text-white text-xs font-bold shadow-md">
                            Claim Lifetime Deal →
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ================= FAQ SECTION (ACCORDION) ================= -->
    <section id="faq" class="py-20 lg:py-28 bg-dark-950 border-b border-white/[0.08]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="text-center mb-16 space-y-3">
                <span class="text-xs font-bold tracking-widest uppercase text-brand-400 font-mono">FAQ</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight font-display">
                    Frequently asked questions
                </h2>
                <p class="text-base text-slate-400">
                    Everything you need to know about building, embedding, and automating quiz funnels.
                </p>
            </div>

            <!-- Accordion List -->
            <div class="space-y-3">
                
                <!-- FAQ Item 1 (Open by default) -->
                <div class="rounded-2xl border border-white/[0.08] ambient-card overflow-hidden">
                    <button type="button" onclick="toggleFaq(this)" class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-brand-300 transition-colors">
                        <span>Why are interactive quiz funnels better than traditional forms?</span>
                        <span class="faq-icon text-base text-brand-400 transition-transform">▲</span>
                    </button>
                    <div class="faq-body px-5 pb-5 text-xs text-slate-300 leading-relaxed border-t border-white/[0.06] pt-3">
                        Traditional static forms ask for contact info upfront with no immediate value, causing high drop-off rates (averaging under 3% conversion). In contrast, QFunnel interactive quizzes provide instant diagnosis, personalized recommendations, and engaging micro-commitments, typically increasing lead conversions to 18% - 35%.
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="rounded-2xl border border-white/[0.08] ambient-card overflow-hidden">
                    <button type="button" onclick="toggleFaq(this)" class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-brand-300 transition-colors">
                        <span>How does embedding work on WordPress, Shopify, and Webflow?</span>
                        <span class="faq-icon text-base text-brand-400 transition-transform">▼</span>
                    </button>
                    <div class="faq-body hidden px-5 pb-5 text-xs text-slate-300 leading-relaxed border-t border-white/[0.06] pt-3">
                        QFunnel generates clean, 1-line embed snippets. You simply paste the code into a Custom HTML block in WordPress (Elementor/Gutenberg), a Custom Liquid section in Shopify, or an HTML embed element in Webflow. You can choose between an <strong>Inline Embed</strong>, a <strong>Popup Modal Button</strong>, or a <strong>Floating Corner Widget</strong>.
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="rounded-2xl border border-white/[0.08] ambient-card overflow-hidden">
                    <button type="button" onclick="toggleFaq(this)" class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-brand-300 transition-colors">
                        <span>Can I sync leads directly into Google Sheets and external Webhooks?</span>
                        <span class="faq-icon text-base text-brand-400 transition-transform">▼</span>
                    </button>
                    <div class="faq-body hidden px-5 pb-5 text-xs text-slate-300 leading-relaxed border-t border-white/[0.06] pt-3">
                        Yes! Whenever a respondent completes your quiz, QFunnel automatically fires an instant JSON payload to your configured Webhook URL (e.g. Google Apps Script, Zapier, Make, or custom CRM). Every webhook payload includes a cryptographic HMAC-SHA256 signature for complete security and automatic retry logs.
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="rounded-2xl border border-white/[0.08] ambient-card overflow-hidden">
                    <button type="button" onclick="toggleFaq(this)" class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-brand-300 transition-colors">
                        <span>Can I customize the branding and remove the QFunnel badge?</span>
                        <span class="faq-icon text-base text-brand-400 transition-transform">▼</span>
                    </button>
                    <div class="faq-body hidden px-5 pb-5 text-xs text-slate-300 leading-relaxed border-t border-white/[0.06] pt-3">
                        Yes! With White-Label enabled on Pro and Agency plans, you can upload your custom company logo, adjust logo position/height, configure brand accent colors, define custom trust badge text, and completely hide SaaS watermarks.
                    </div>
                </div>

                <!-- FAQ Item 5 -->
                <div class="rounded-2xl border border-white/[0.08] ambient-card overflow-hidden">
                    <button type="button" onclick="toggleFaq(this)" class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-brand-300 transition-colors">
                        <span>How does branching logic work if a user changes an answer?</span>
                        <span class="faq-icon text-base text-brand-400 transition-transform">▼</span>
                    </button>
                    <div class="faq-body hidden px-5 pb-5 text-xs text-slate-300 leading-relaxed border-t border-white/[0.06] pt-3">
                        QFunnel includes an atomic backtracking engine. If a respondent clicks the "Back" button and chooses a different answer, all downstream answers that depended on the old answer are automatically invalidated and the route is smoothly recalculated.
                    </div>
                </div>

                <!-- FAQ Item 6 -->
                <div class="rounded-2xl border border-white/[0.08] ambient-card overflow-hidden">
                    <button type="button" onclick="toggleFaq(this)" class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-brand-300 transition-colors">
                        <span>Do you offer a REST API for developers?</span>
                        <span class="faq-icon text-base text-brand-400 transition-transform">▼</span>
                    </button>
                    <div class="faq-body hidden px-5 pb-5 text-xs text-slate-300 leading-relaxed border-t border-white/[0.06] pt-3">
                        Yes! QFunnel provides a documented v1 REST API authenticated with Bearer tokens. You can query published funnels, fetch submission events, initialize remote quiz sessions, and sync customer analytics into custom applications.
                    </div>
                </div>

                <!-- FAQ Item 7 -->
                <div class="rounded-2xl border border-white/[0.08] ambient-card overflow-hidden">
                    <button type="button" onclick="toggleFaq(this)" class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm text-white hover:text-brand-300 transition-colors">
                        <span>Are lead submissions and customer data secure?</span>
                        <span class="faq-icon text-base text-brand-400 transition-transform">▼</span>
                    </button>
                    <div class="faq-body hidden px-5 pb-5 text-xs text-slate-300 leading-relaxed border-t border-white/[0.06] pt-3">
                        Absolutely. Every workspace has isolated tenant boundaries to prevent cross-account data leakage (strict IDOR protection). All form transmissions use 256-bit SSL encryption, CSRF protection, and sanitized database queries.
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ================= PRE-FOOTER CTA SECTION ================= -->
    <section class="py-24 bg-dark-900 text-center border-b border-white/[0.08] relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-brand-950/30 to-dark-950 pointer-events-none"></div>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 relative z-10 space-y-4">
            <span class="px-3 py-1 rounded-full bg-brand-500/20 text-brand-300 text-xs font-bold font-mono border border-brand-500/30">
                START TODAY IN 2 MINUTES
            </span>
            <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight font-display">
                Ready to 3x your lead conversion?
            </h2>
            <p class="text-sm sm:text-base text-slate-300 max-w-xl mx-auto">
                Build your first interactive quiz funnel in less than 2 minutes. Free forever, no credit card required.
            </p>
            <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                <a href="<?= url('/register') ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl brand-gradient-btn text-white text-sm font-bold shadow-xl transition-all group">
                    <span>Start Building Free</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </a>
                <a href="<?= url('/embed-demo.html') ?>" class="inline-flex items-center gap-2 px-7 py-4 rounded-xl glass-pill hover:bg-white/[0.08] text-white border border-white/[0.15] text-sm font-bold transition-all">
                    <span>✨ Test Live Embed</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ================= DARK FOOTER ================= -->
    <footer class="bg-dark-950 text-slate-400 py-16 text-xs border-t border-white/[0.08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-white/[0.08]">
                
                <!-- Col 1: Brand & Status -->
                <div class="space-y-4 md:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl brand-gradient-btn text-white flex items-center justify-center font-bold text-base shadow-md">
                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                <polyline points="2 17 12 22 22 17"></polyline>
                                <polyline points="2 12 12 17 22 12"></polyline>
                            </svg>
                        </div>
                        <span class="text-lg font-black text-white tracking-tight font-display">QFunnel</span>
                    </div>
                    
                    <p class="text-xs text-slate-400 leading-relaxed">
                        The interactive quiz funnel & lead qualification platform. Build visual branching logic, embed anywhere, and automate workflows effortlessly.
                    </p>

                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/[0.04] border border-white/[0.08] text-[11px] font-semibold text-emerald-400 font-mono">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        All Systems Operational • 99.9% Uptime
                    </div>
                </div>

                <!-- Col 2: Product Links -->
                <div class="space-y-2.5">
                    <h5 class="text-xs font-bold text-white uppercase tracking-wider mb-3 font-mono">PRODUCT</h5>
                    <div><a href="#how-it-works" class="hover:text-white transition-colors">How It Works</a></div>
                    <div><a href="#builder-showcase" class="hover:text-white transition-colors">Visual Canvas Builder</a></div>
                    <div><a href="#embed-showcase" class="hover:text-white transition-colors">Universal Embeds (3-in-1)</a></div>
                    <div><a href="<?= url('/embed-demo.html') ?>" class="hover:text-white transition-colors">Live Interactive Demo</a></div>
                    <div><a href="<?= url('/docs') ?>" class="text-emerald-400 hover:text-emerald-300 font-semibold transition-colors flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Documentation</a></div>
                    <div><a href="#pricing" class="hover:text-white transition-colors">Pricing & Plans</a></div>
                    <div><a href="#faq" class="hover:text-white transition-colors">FAQ & Knowledgebase</a></div>
                    <div><a href="<?= url('/register') ?>" class="text-brand-400 font-semibold hover:underline">Create Free Workspace →</a></div>
                </div>

                <!-- Col 3: Integrations & Trust -->
                <div class="space-y-2.5">
                    <h5 class="text-xs font-bold text-white uppercase tracking-wider mb-3 font-mono">LEGAL & POLICIES</h5>
                    <div><a href="<?= url('/privacy') ?>" class="hover:text-white transition-colors">Privacy Policy & GDPR</a></div>
                    <div><a href="<?= url('/terms') ?>" class="hover:text-white transition-colors">Terms of Service</a></div>
                    <div><a href="<?= url('/refund-policy') ?>" class="hover:text-white transition-colors">Refund & Cancellation</a></div>
                    <div><a href="<?= url('/cookie-policy') ?>" class="hover:text-white transition-colors">Cookie Policy</a></div>
                    <div><a href="<?= url('/docs') ?>" class="hover:text-white transition-colors">API & Webhook Docs</a></div>
                    <div><a href="#features" class="hover:text-white transition-colors">Google Sheets Webhook Sync</a></div>
                    <div><a href="#features" class="hover:text-white transition-colors">WordPress & Shopify Guides</a></div>
                </div>

                <!-- Col 4: Official Contact -->
                <div class="space-y-2.5">
                    <h5 class="text-xs font-bold text-white uppercase tracking-wider mb-3 font-mono">OFFICIAL CONTACT</h5>
                    <div class="flex items-start gap-2">
                        <span>👤</span>
                        <div>
                            <strong class="text-white block font-semibold">Dibyendu Kumar Khaskel</strong>
                            <span class="text-[10px] text-slate-400">Platform Founder & Lead Engineer</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>✉️</span>
                        <a href="mailto:support@qfunnel.com" class="hover:text-white transition-colors font-mono">support@qfunnel.com</a>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>📞</span>
                        <span class="font-mono text-slate-300">+91 6294629735</span>
                    </div>
                    <div class="text-[11px] text-slate-400 pl-6">
                        Mon–Sat: 10 AM – 7 PM IST
                    </div>
                    <div class="flex items-start gap-2 pt-1">
                        <span>📍</span>
                        <span class="text-[11px] text-slate-400">Helencha Road, Duttapulia, Baikola, West Bengal 743270, India</span>
                    </div>
                </div>

            </div>

            <!-- Footer Bottom Bar -->
            <div class="pt-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs text-slate-400">
                <div>
                    © <?= date('Y') ?> QFunnel Platform Inc. All rights reserved.
                </div>
                <div>
                    Engineered with precision for marketing teams & high-converting businesses.
                </div>
                <div class="flex items-center gap-2">
                    <span>Language:</span>
                    <div class="inline-flex glass-pill rounded-full p-0.5 text-[11px] font-bold">
                        <button type="button" onclick="switchLanguage('en')" class="px-2 py-0.5 rounded-full bg-brand-600 text-white">ENG</button>
                        <button type="button" onclick="switchLanguage('bn')" class="px-2 py-0.5 rounded-full text-slate-400 hover:text-white">বাংলা</button>
                    </div>
                </div>
            </div>

        </div>
    </footer>

    <!-- ================= JAVASCRIPT INTERACTIONS & SIMULATION ================= -->
    <script>
        // Hero Quiz Node Simulator Answer Click
        function selectHeroAnswer(btn, score, route) {
            const container = document.getElementById('heroDemoOptions');
            container.querySelectorAll('button').forEach(b => {
                b.className = 'demo-card w-full p-3.5 rounded-xl border border-white/[0.1] bg-white/[0.03] hover:bg-white/[0.06] hover:border-brand-500/50 text-xs font-semibold text-slate-200 flex items-center justify-between transition-all text-left';
                const pill = b.querySelector('span:last-child');
                if (pill && pill.textContent.includes('Selected')) {
                    pill.outerHTML = '<span class="text-[11px] font-mono text-slate-400">+15 pts</span>';
                }
            });

            btn.className = 'demo-card active w-full p-3.5 rounded-xl border-2 border-brand-500 bg-brand-500/15 text-xs font-bold text-white flex items-center justify-between transition-all text-left shadow-lg shadow-brand-500/10';
            const rightBadge = btn.querySelector('span:last-child');
            if (rightBadge) {
                rightBadge.outerHTML = '<span class="text-[10px] bg-brand-500 text-white px-2 py-0.5 rounded-full font-bold">Selected ✓</span>';
            }

            document.getElementById('heroScoreDisplay').textContent = 'Score: ' + (50 + score) + '/100';
            document.getElementById('heroRouteText').textContent = route;

            const progressBar = document.getElementById('demoProgressBar');
            const progressText = document.getElementById('demoProgressText');
            if (score === 15) {
                progressBar.style.width = '33%';
                progressText.textContent = '33%';
            } else if (score === 35) {
                progressBar.style.width = '66%';
                progressText.textContent = '66%';
            } else {
                progressBar.style.width = '100%';
                progressText.textContent = '100%';
            }
        }

        // ================= MOVING VISUAL BUILDER CANVAS LOGIC =================
        function getPortCoordinates(element, isOutput, portIndex = 0, totalPorts = 1) {
            const rect = element.getBoundingClientRect();
            const parent = document.getElementById('innerCanvasWorld').getBoundingClientRect();
            
            const x = isOutput ? (rect.right - parent.left) : (rect.left - parent.left);
            let y = (rect.top - parent.top) + rect.height / 2;
            
            if (totalPorts > 1) {
                const fraction = (portIndex + 1) / (totalPorts + 1);
                y = (rect.top - parent.top) + (rect.height * fraction);
            }
            return { x, y };
        }

        function buildCubicBezier(p1, p2) {
            const dx = Math.abs(p2.x - p1.x);
            const cx1 = p1.x + Math.max(dx * 0.45, 40);
            const cy1 = p1.y;
            const cx2 = p2.x - Math.max(dx * 0.45, 40);
            const cy2 = p2.y;
            return `M ${p1.x} ${p1.y} C ${cx1} ${cy1}, ${cx2} ${cy2}, ${p2.x} ${p2.y}`;
        }

        function updateCanvasWires() {
            const n1 = document.getElementById('node1');
            const n2 = document.getElementById('node2');
            const n3 = document.getElementById('node3');
            const n4 = document.getElementById('node4');
            const n5 = document.getElementById('node5');
            const n6 = document.getElementById('node6');

            if (!n1 || !n2 || !n3 || !n4 || !n5 || !n6) return;

            // Coordinates
            const p1 = getPortCoordinates(n1, true);
            const p2In = getPortCoordinates(n2, false);
            const p2Out = getPortCoordinates(n2, true);
            const p3In = getPortCoordinates(n3, false);
            const p3OutA = getPortCoordinates(n3, true, 0, 2);
            const p3OutB = getPortCoordinates(n3, true, 1, 2);
            const p4In = getPortCoordinates(n4, false);
            const p4Out = getPortCoordinates(n4, true);
            const p5In = getPortCoordinates(n5, false);
            const p5Out = getPortCoordinates(n5, true);
            const p6InA = getPortCoordinates(n6, false, 0, 2);
            const p6InB = getPortCoordinates(n6, false, 1, 2);

            // Paths
            const d1 = buildCubicBezier(p1, p2In);
            const d2 = buildCubicBezier(p2Out, p3In);
            const d3A = buildCubicBezier(p3OutA, p4In);
            const d3B = buildCubicBezier(p3OutB, p5In);
            const d4 = buildCubicBezier(p4Out, p6InA);
            const d5 = buildCubicBezier(p5Out, p6InB);

            // Set wire paths
            document.getElementById('wireBase1').setAttribute('d', d1);
            document.getElementById('wireFlow1').setAttribute('d', d1);
            
            document.getElementById('wireBase2').setAttribute('d', d2);
            document.getElementById('wireFlow2').setAttribute('d', d2);

            document.getElementById('wireBase3A').setAttribute('d', d3A);
            document.getElementById('wireFlow3A').setAttribute('d', d3A);

            document.getElementById('wireBase3B').setAttribute('d', d3B);
            document.getElementById('wireFlow3B').setAttribute('d', d3B);

            document.getElementById('wireBase4').setAttribute('d', d4);
            document.getElementById('wireFlow4').setAttribute('d', d4);

            document.getElementById('wireBase5').setAttribute('d', d5);
            document.getElementById('wireFlow5').setAttribute('d', d5);

            // Animate Motion Packets
            document.getElementById('motionPacket1').setAttribute('path', d1);
            document.getElementById('motionPacket2').setAttribute('path', d3A);
            document.getElementById('motionPacket3').setAttribute('path', d4);
        }

        // Auto Tour Simulation Loop
        let tourActive = true;
        let tourStep = 0;
        let tourInterval = null;

        const tourStepsData = [
            {
                cursor: { x: 160, y: 220 },
                activeNode: 'node1',
                title: 'Start Trigger: Universal Embed Hook',
                cond: 'Receives traffic from WordPress & Shopify',
                ticker: '[15:37:00] New visitor arrival triggered from WordPress / Shopify landing page'
            },
            {
                cursor: { x: 440, y: 210 },
                activeNode: 'node2',
                title: 'Branching Question 1',
                cond: 'Selected Option B: Unqualified leads (+35 pts)',
                ticker: '[15:37:03] Visitor selected Option B: "Unqualified leads" (+35 pts computed)'
            },
            {
                cursor: { x: 740, y: 220 },
                activeNode: 'node3',
                title: 'Dynamic Logic Split Condition',
                cond: 'if (total_score >= 35) ➔ VIP Route (Evaluated TRUE)',
                ticker: '[15:37:06] Decision Gate: Score 85 >= 35 ➔ Unlocks VIP Priority Intake Branch'
            },
            {
                cursor: { x: 1040, y: 120 },
                activeNode: 'node4',
                title: 'VIP Lead Intake Form',
                cond: 'Collects Work Email, Phone & Company Size',
                ticker: '[15:37:09] Lead captured: alex@growthagency.io (Phone verified +1-415-***)'
            },
            {
                cursor: { x: 1320, y: 240 },
                activeNode: 'node6',
                title: 'Automated Webhooks & Google Sheets',
                cond: 'Dispatches HMAC-SHA256 Payload to CRM',
                ticker: '[15:37:12] Webhook 200 OK ➔ Synced to Google Sheets & CRM in 28ms'
            }
        ];

        function runTourTick() {
            if (!tourActive) return;
            const current = tourStepsData[tourStep];
            
            // Move Virtual Cursor
            const cursor = document.getElementById('virtualCursor');
            if (cursor) {
                cursor.style.left = current.cursor.x + 'px';
                cursor.style.top = current.cursor.y + 'px';
            }

            // Highlight Active Node
            document.querySelectorAll('.builder-node').forEach(n => n.classList.remove('active-step'));
            const activeEl = document.getElementById(current.activeNode);
            if (activeEl) {
                activeEl.classList.add('active-step');
            }

            // Update Inspector Drawer
            document.getElementById('inspectorNodeTitle').textContent = current.title;
            document.getElementById('inspectorCondition').textContent = current.cond;
            document.getElementById('canvasEventTicker').textContent = current.ticker;

            // Flash Webhook Pill on final step
            if (current.activeNode === 'node6') {
                const pill = document.getElementById('node6StatusPill');
                pill.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-400 text-dark-950 font-mono transition-colors';
                setTimeout(() => {
                    pill.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 font-mono transition-colors';
                }, 1200);
            }

            tourStep = (tourStep + 1) % tourStepsData.length;
        }

        function toggleTourMode() {
            tourActive = !tourActive;
            const btn = document.getElementById('btnTourToggle');
            const icon = document.getElementById('tourIcon');
            const label = document.getElementById('tourLabel');

            if (tourActive) {
                icon.textContent = '⏸';
                label.textContent = 'Pause Tour';
                btn.className = 'px-3.5 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white font-bold transition-all flex items-center gap-1.5 shadow-sm';
            } else {
                icon.textContent = '▶';
                label.textContent = 'Resume Tour';
                btn.className = 'px-3.5 py-1.5 rounded-lg bg-white/[0.1] hover:bg-white/[0.2] text-white font-bold transition-all flex items-center gap-1.5 shadow-sm';
            }
        }

        function resetCanvasLayout() {
            document.getElementById('node1').style.top = '190px';
            document.getElementById('node1').style.left = '40px';

            document.getElementById('node2').style.top = '130px';
            document.getElementById('node2').style.left = '320px';

            document.getElementById('node3').style.top = '150px';
            document.getElementById('node3').style.left = '620px';

            document.getElementById('node4').style.top = '50px';
            document.getElementById('node4').style.left = '920px';

            document.getElementById('node5').style.top = '320px';
            document.getElementById('node5').style.left = '920px';

            document.getElementById('node6').style.top = '175px';
            document.getElementById('node6').style.left = '1200px';

            updateCanvasWires();
        }

        // Draggable Nodes Implementation
        let draggedNode = null;
        let dragOffset = { x: 0, y: 0 };

        function startNodeDrag(e, nodeId) {
            draggedNode = document.getElementById(nodeId);
            if (!draggedNode) return;

            // Pause tour temporarily when user drags
            tourActive = false;
            document.getElementById('tourIcon').textContent = '▶';
            document.getElementById('tourLabel').textContent = 'Resume Tour';

            const rect = draggedNode.getBoundingClientRect();
            dragOffset.x = e.clientX - rect.left;
            dragOffset.y = e.clientY - rect.top;

            // Select node in inspector
            document.querySelectorAll('.builder-node').forEach(n => n.classList.remove('active-step'));
            draggedNode.classList.add('active-step');

            window.addEventListener('mousemove', onNodeDragMove);
            window.addEventListener('mouseup', onNodeDragEnd);
        }

        function onNodeDragMove(e) {
            if (!draggedNode) return;
            const parent = document.getElementById('innerCanvasWorld').getBoundingClientRect();
            let newX = e.clientX - parent.left - dragOffset.x;
            let newY = e.clientY - parent.top - dragOffset.y;

            // Boundaries
            newX = Math.max(10, Math.min(newX, 1050));
            newY = Math.max(10, Math.min(newY, 400));

            draggedNode.style.left = newX + 'px';
            draggedNode.style.top = newY + 'px';

            updateCanvasWires();
        }

        function onNodeDragEnd() {
            draggedNode = null;
            window.removeEventListener('mousemove', onNodeDragMove);
            window.removeEventListener('mouseup', onNodeDragEnd);
        }

        // Initial setup on window load
        window.addEventListener('load', () => {
            updateCanvasWires();
            window.addEventListener('resize', updateCanvasWires);
            tourInterval = setInterval(runTourTick, 2800);
            runTourTick();
        });

        // ================= 3-IN-1 EMBED SHOWCASE TABS =================
        const embedTabContents = {
            inline: {
                title: 'Embed directly inside your blog posts or landing page',
                desc: 'Perfect for interactive buyer guides, diagnostic quizzes, and product finders embedded right in the flow of your article or high-converting sales page.',
                code: `<div id="qfunnel-container" data-funnel-id="YOUR_SLUG"></div>\n<script src="<?= url('/assets/js/embed.js') ?>" async><\/script>`,
                visualHtml: `
                    <div class="p-5 rounded-2xl bg-white/[0.04] border border-brand-500/40 space-y-3">
                        <div class="w-8 h-8 rounded-lg brand-gradient-btn mx-auto flex items-center justify-center text-white font-bold text-xs">Q</div>
                        <h4 class="text-xs font-bold text-white">Find Your Ideal Solution in 60s</h4>
                        <p class="text-[11px] text-slate-400">Answer 3 quick questions for custom pricing</p>
                        <button type="button" class="w-full py-2.5 rounded-lg brand-gradient-btn text-white text-xs font-bold">Start Interactive Quiz →</button>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono block">Inline Container (Responsive Auto-Height)</span>
                `
            },
            modal: {
                title: 'Popup Lightbox Modal triggered by any button or exit-intent',
                desc: 'High-converting trigger that displays over your page with zero distraction. Great for call-to-action buttons in your navbar, header, or exit-intent popups.',
                code: `<button onclick="QFunnel.openModal('YOUR_SLUG')" class="btn-primary">Take Our Free Quiz</button>\n<script src="<?= url('/assets/js/embed.js') ?>" async><\/script>`,
                visualHtml: `
                    <div class="relative w-full max-w-xs p-5 rounded-2xl bg-dark-900 border-2 border-brand-500 shadow-2xl space-y-3">
                        <div class="flex justify-between items-center text-xs text-slate-400">
                            <span class="font-bold text-white">Modal Lightbox Active</span>
                            <span class="text-slate-400 font-bold">✕</span>
                        </div>
                        <div class="w-full bg-white/[0.06] h-1.5 rounded-full overflow-hidden">
                            <div class="bg-brand-500 h-full w-1/2"></div>
                        </div>
                        <h4 class="text-xs font-bold text-white">What is your monthly ad spend?</h4>
                        <div class="space-y-1.5 text-left text-xs">
                            <div class="p-2 rounded bg-white/[0.06] text-slate-300">A) Under $1,000</div>
                            <div class="p-2 rounded bg-brand-500/20 text-brand-300 border border-brand-500/40">B) $1,000 - $10,000 ✓</div>
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono block">Backdrop Dimming Overlay Enabled</span>
                `
            },
            bubble: {
                title: 'Floating Corner Bubble Widget that stays accessible',
                desc: 'Sticky, friendly chat-style bubble anchored to the bottom-right of every page. Engages visitors passively without obstructing main page content.',
                code: `<script src="<?= url('/assets/js/embed.js') ?>" data-qfunnel-bubble="YOUR_SLUG" data-position="bottom-right" async><\/script>`,
                visualHtml: `
                    <div class="relative w-full h-44 flex flex-col justify-end items-end p-2">
                        <div class="mb-2 p-3 rounded-2xl bg-white/[0.06] border border-white/[0.1] text-xs text-slate-200 text-right shadow-lg">
                            👋 Need help choosing the right plan?
                        </div>
                        <div class="w-14 h-14 rounded-full brand-gradient-btn flex items-center justify-center text-white text-xl shadow-2xl animate-bounce">
                            💬
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono block">Bottom-Right Floating Bubble Assistant</span>
                `
            }
        };

        function switchEmbedTab(mode) {
            ['inline', 'modal', 'bubble'].forEach(m => {
                const btn = document.getElementById('tabBtn' + m.charAt(0).toUpperCase() + m.slice(1));
                if (btn) {
                    if (m === mode) {
                        btn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold transition-all bg-brand-600 text-white shadow-md';
                    } else {
                        btn.className = 'px-5 py-2.5 rounded-xl text-xs font-bold transition-all text-slate-400 hover:text-white';
                    }
                }
            });

            const data = embedTabContents[mode];
            if (data) {
                document.getElementById('embedContentText').innerHTML = `
                    <span class="px-3 py-1 rounded-full bg-brand-500/20 text-brand-300 text-xs font-bold font-mono border border-brand-500/30">
                        Mode: ${mode.toUpperCase()} EMBED
                    </span>
                    <h3 class="text-2xl font-bold text-white font-display">${data.title}</h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">${data.desc}</p>
                `;
                document.getElementById('snippetCodeDisplay').textContent = data.code;
                document.getElementById('embedVisualMockup').innerHTML = data.visualHtml;
            }
        }

        function copySnippetCode() {
            const code = document.getElementById('snippetCodeDisplay').textContent;
            navigator.clipboard.writeText(code).then(() => {
                const btn = document.getElementById('btnCopySnippet');
                btn.textContent = 'Copied! ✓';
                btn.classList.add('text-emerald-400');
                setTimeout(() => {
                    btn.textContent = 'Copy Snippet 📋';
                    btn.classList.remove('text-emerald-400');
                }, 2000);
            });
        }

        // Pricing Interval Toggle (Monthly / Yearly)
        let isAnnual = true;
        function togglePricingInterval() {
            isAnnual = !isAnnual;
            const toggle = document.getElementById('pricingIntervalToggle');
            const thumb = document.getElementById('pricingToggleThumb');
            
            if (thumb) {
                if (isAnnual) {
                    thumb.className = 'translate-x-5 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out';
                } else {
                    thumb.className = 'translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out';
                }
            }

            document.querySelectorAll('.dynamic-plan-price').forEach(function(el) {
                const val = isAnnual ? el.getAttribute('data-yearly') : el.getAttribute('data-monthly');
                if (val) el.textContent = val;
            });
            document.querySelectorAll('.dynamic-plan-period').forEach(function(el) {
                const val = isAnnual ? el.getAttribute('data-yearly') : el.getAttribute('data-monthly');
                if (val) el.textContent = val;
            });
        }

        // FAQ Accordion Toggler
        function toggleFaq(btn) {
            const body = btn.nextElementSibling;
            const icon = btn.querySelector('.faq-icon');
            const isOpen = !body.classList.contains('hidden');
            
            // Close all
            document.querySelectorAll('.faq-body').forEach(b => b.classList.add('hidden'));
            document.querySelectorAll('.faq-icon').forEach(i => i.textContent = '▼');

            if (!isOpen) {
                body.classList.remove('hidden');
                icon.textContent = '▲';
            }
        }

        // Bilingual Switcher (English & Bengali)
        const translations = {
            en: {
                heroBadge: '🚀 3x Higher Conversion Than Static Web Forms',
                head1: 'Turn cold visitors into',
                head2: 'high-intent qualified leads',
                head3: 'with interactive quizzes',
                sub: 'Boring static forms suffer from 95% drop-off. QFunnel builds visual branching quizzes that engage visitors, score intent in real time, and automatically push qualified payloads to Google Sheets & your CRM.',
                cta: 'Build Your First Funnel Free'
            },
            bn: {
                heroBadge: '🚀 সাধারণ ওয়েব ফর্মের চেয়ে ৩ গুণ বেশি লিড কনভার্সন',
                head1: 'ওয়েবসাইটের ভিজিটরদের রূপান্তর করুন',
                head2: 'উচ্চমানের যোগ্য লিডে',
                head3: 'ইন্টারেক্টিভ কুইজের মাধ্যমে',
                sub: 'স্ট্যাটিক ফর্মে ৯৫% ভিজিটর কোনো তথ্য না দিয়ে চলে যায়। QFunnel-এর স্মার্ট ব্রাঞ্চিং কুইজ গ্রাহকদের সম্পৃক্ত করে, সঠিক রুট নির্ধারণ করে এবং সংগৃহীত লিড স্বয়ংক্রিয়ভাবে গুগল শিট ও সিআরএমে পাঠায়।',
                cta: 'বিনামূল্যে প্রথম ফানেল তৈরি করুন'
            }
        };

        function switchLanguage(lang) {
            const t = translations[lang] || translations.en;
            const heroBadge = document.getElementById('heroBadgeText');
            const heroHead1 = document.getElementById('heroHead1');
            const heroHead2 = document.getElementById('heroHead2');
            const heroHead3 = document.getElementById('heroHead3');
            const heroSub = document.getElementById('heroSubText');
            const heroCta = document.getElementById('heroCtaText');

            if (heroBadge) heroBadge.textContent = t.heroBadge;
            if (heroHead1) heroHead1.textContent = t.head1;
            if (heroHead2) heroHead2.textContent = t.head2;
            if (heroHead3) heroHead3.textContent = t.head3;
            if (heroSub) heroSub.textContent = t.sub;
            if (heroCta) heroCta.textContent = t.cta;

            const activeBtn = 'px-2.5 py-1 rounded-full bg-brand-600 text-white transition-all shadow-xs text-xs';
            const inactiveBtn = 'px-2.5 py-1 rounded-full text-slate-400 hover:text-white transition-all text-xs';

            ['btnLangEn', 'btnMobileLangEn'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.className = (lang === 'en') ? activeBtn : inactiveBtn;
            });
            ['btnLangBn', 'btnMobileLangBn'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.className = (lang === 'bn') ? activeBtn : inactiveBtn;
            });
        }

        function toggleMobileMenu() {
            const drawer = document.getElementById('mobileMenuDrawer');
            const burger = document.getElementById('iconBurger');
            const close = document.getElementById('iconClose');
            if (!drawer) return;

            const isClosed = drawer.classList.contains('hidden');
            if (isClosed) {
                drawer.classList.remove('hidden');
                burger?.classList.add('hidden');
                close?.classList.remove('hidden');
            } else {
                drawer.classList.add('hidden');
                burger?.classList.remove('hidden');
                close?.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
