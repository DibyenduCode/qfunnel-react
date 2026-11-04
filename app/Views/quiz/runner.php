<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Quiz Funnel') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php
        $accentColor = !empty($settings['accent_color']) ? $settings['accent_color'] : '#5B5BD6';
        $isDark = ($settings['theme'] ?? 'light') === 'dark';
        $companyName = !empty($settings['company_name']) ? $settings['company_name'] : ($funnel['tenant_name'] ?? $funnel['name']);
        $logoUrl = !empty($settings['logo_url']) ? $settings['logo_url'] : '';
        $isWhiteLabel = !empty($settings['white_label']);
        $trustBadge = !empty($settings['trust_badge_text']) ? $settings['trust_badge_text'] : '🔒 256-Bit SSL Encrypted • 100% Privacy Guaranteed';
        $footerText = !empty($settings['footer_text']) ? $settings['footer_text'] : ('© ' . date('Y') . ' ' . $companyName . '. All rights reserved.');
        $privacyUrl = !empty($settings['privacy_policy_url']) ? $settings['privacy_policy_url'] : '';
        $termsUrl = !empty($settings['terms_url']) ? $settings['terms_url'] : '';
        $showProgressBar = !isset($settings['show_progress_bar']) || !empty($settings['show_progress_bar']);
        $logoPosition = $settings['logo_position'] ?? 'left';
        $logoHeight = max(24, min(90, (int)($settings['logo_height'] ?? 36)));
        $cardRadius = $settings['card_radius'] ?? 'rounded-2xl';
    ?>
    <style>
        :root {
            --brand-primary: <?= e($accentColor) ?>;
            --brand-primary-hover: <?= e($accentColor) ?>ee;
            --brand-primary-light: <?= e($accentColor) ?>18;
            --brand-primary-glow: <?= e($accentColor) ?>33;
        }
        body { 
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif; 
            <?= $isDark ? 'background: #0D0F14; color: #F1F3F9;' : 'background: #F8FAFC; color: #0F172A;' ?>
        }
        .brand-bg { background-color: var(--brand-primary); }
        .brand-text { color: var(--brand-primary); }
        .brand-border { border-color: var(--brand-primary); }
        
        .quiz-card {
            box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .option-card {
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .option-card:hover {
            border-color: var(--brand-primary);
            background-color: var(--brand-primary-light);
            transform: translateY(-1px);
        }
        .option-card.selected {
            border-color: var(--brand-primary);
            background-color: var(--brand-primary-light);
            box-shadow: 0 0 0 1.5px var(--brand-primary), 0 4px 12px var(--brand-primary-glow);
        }
        .option-key-badge {
            transition: all 0.15s ease;
        }
        .option-card.selected .option-key-badge {
            background-color: var(--brand-primary);
            color: #ffffff;
            border-color: var(--brand-primary);
        }
        .custom-focus:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px var(--brand-primary-glow);
        }
        .btn-brand {
            background-color: var(--brand-primary);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-brand:hover {
            opacity: 0.94;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px var(--brand-primary-glow);
        }
        .btn-brand:active {
            transform: translateY(0);
        }
        .progress-bar-fill {
            transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            background: linear-gradient(90deg, var(--brand-primary), var(--brand-primary-hover));
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between p-4 sm:p-6 <?= $isDark ? 'bg-[#0D0F14] text-[#F1F3F9]' : 'bg-[#F8FAFC] text-[#0F172A]' ?>">

    <!-- Top Sticky / Elevated Header: 100% White-Labeled with Dynamic Alignment -->
    <header class="max-w-2xl mx-auto w-full py-4 px-2 sm:px-0">
        <?php if ($logoPosition === 'center'): ?>
            <!-- Center Aligned Header Layout -->
            <div class="flex flex-col items-center justify-center text-center gap-2">
                <div class="flex items-center gap-3">
                    <?php if (!empty($logoUrl)): ?>
                        <img src="<?= e($logoUrl) ?>" alt="<?= e($companyName) ?>" style="height: <?= $logoHeight ?>px;" class="max-w-[220px] object-contain rounded" onerror="this.style.display='none'; document.getElementById('brandFallbackAvatarCenter').style.display='flex';">
                        <span id="brandFallbackAvatarCenter" style="width: <?= $logoHeight ?>px; height: <?= $logoHeight ?>px; display:none;" class="rounded-lg brand-bg text-white font-extrabold text-sm flex items-center justify-center shadow-sm">
                            <?= e(strtoupper(substr($companyName, 0, 2))) ?>
                        </span>
                    <?php else: ?>
                        <span style="width: <?= $logoHeight ?>px; height: <?= $logoHeight ?>px;" class="rounded-lg brand-bg text-white font-extrabold text-sm flex items-center justify-center shadow-sm tracking-wide">
                            <?= e(strtoupper(substr($companyName, 0, 2))) ?>
                        </span>
                    <?php endif; ?>
                    <div class="text-left">
                        <span class="text-[11px] font-bold uppercase tracking-wider <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> block">
                            <?= e($companyName) ?>
                        </span>
                        <h1 class="text-sm font-extrabold <?= $isDark ? 'text-white' : 'text-[#0F172A]' ?>">
                            <?= e($funnel['name']) ?>
                        </h1>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2 pt-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold <?= $isDark ? 'bg-slate-800 text-slate-300 border border-slate-700' : 'bg-white text-slate-600 border border-slate-200' ?> shadow-2xs">
                        <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Verified Secure
                    </span>
                    <span id="stepProgressBadge" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold brand-bg text-white shadow-xs hidden">
                        Step <span id="currentStepNum">1</span>
                    </span>
                </div>
            </div>
        <?php elseif ($logoPosition === 'right'): ?>
            <!-- Right Aligned Logo Header Layout -->
            <div class="flex items-center justify-between gap-4">
                <!-- Progress & Badges on Left -->
                <div class="flex items-center gap-2">
                    <span id="stepProgressBadge" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold brand-bg text-white shadow-xs hidden">
                        Step <span id="currentStepNum">1</span>
                    </span>
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold <?= $isDark ? 'bg-slate-800 text-slate-300 border border-slate-700' : 'bg-white text-slate-600 border border-slate-200' ?> shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Verified Secure
                    </span>
                </div>

                <!-- Brand Logo & Name on Right -->
                <div class="flex items-center gap-3 text-right">
                    <div class="leading-tight">
                        <span class="text-xs font-bold uppercase tracking-wider <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> block">
                            <?= e($companyName) ?>
                        </span>
                        <h1 class="text-sm font-extrabold <?= $isDark ? 'text-white' : 'text-[#0F172A]' ?>">
                            <?= e($funnel['name']) ?>
                        </h1>
                    </div>
                    <?php if (!empty($logoUrl)): ?>
                        <img src="<?= e($logoUrl) ?>" alt="<?= e($companyName) ?>" style="height: <?= $logoHeight ?>px;" class="max-w-[180px] object-contain rounded" onerror="this.style.display='none'; document.getElementById('brandFallbackAvatarRight').style.display='flex';">
                        <span id="brandFallbackAvatarRight" style="width: <?= $logoHeight ?>px; height: <?= $logoHeight ?>px; display:none;" class="rounded-lg brand-bg text-white font-extrabold text-sm flex items-center justify-center shadow-sm">
                            <?= e(strtoupper(substr($companyName, 0, 2))) ?>
                        </span>
                    <?php else: ?>
                        <span style="width: <?= $logoHeight ?>px; height: <?= $logoHeight ?>px;" class="rounded-lg brand-bg text-white font-extrabold text-sm flex items-center justify-center shadow-sm tracking-wide">
                            <?= e(strtoupper(substr($companyName, 0, 2))) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Default Left Aligned Header Layout -->
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <?php if (!empty($logoUrl)): ?>
                        <img src="<?= e($logoUrl) ?>" alt="<?= e($companyName) ?>" style="height: <?= $logoHeight ?>px;" class="max-w-[180px] object-contain rounded" onerror="this.style.display='none'; document.getElementById('brandFallbackAvatarLeft').style.display='flex';">
                        <span id="brandFallbackAvatarLeft" style="width: <?= $logoHeight ?>px; height: <?= $logoHeight ?>px; display:none;" class="rounded-lg brand-bg text-white font-extrabold text-sm flex items-center justify-center shadow-sm">
                            <?= e(strtoupper(substr($companyName, 0, 2))) ?>
                        </span>
                    <?php else: ?>
                        <span style="width: <?= $logoHeight ?>px; height: <?= $logoHeight ?>px;" class="rounded-lg brand-bg text-white font-extrabold text-sm flex items-center justify-center shadow-sm tracking-wide">
                            <?= e(strtoupper(substr($companyName, 0, 2))) ?>
                        </span>
                    <?php endif; ?>

                    <div class="leading-tight">
                        <span class="text-xs font-bold uppercase tracking-wider <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> block">
                            <?= e($companyName) ?>
                        </span>
                        <h1 class="text-sm font-extrabold <?= $isDark ? 'text-white' : 'text-[#0F172A]' ?>">
                            <?= e($funnel['name']) ?>
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold <?= $isDark ? 'bg-slate-800 text-slate-300 border border-slate-700' : 'bg-white text-slate-600 border border-slate-200' ?> shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Verified Secure
                    </span>
                    <span id="stepProgressBadge" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold brand-bg text-white shadow-xs hidden">
                        Step <span id="currentStepNum">1</span>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($showProgressBar): ?>
            <!-- Dynamic Funnel Progress Bar -->
            <div class="mt-3.5 w-full bg-slate-200/60 <?= $isDark ? 'bg-slate-800' : '' ?> h-1.5 rounded-full overflow-hidden">
                <div id="funnelProgressBar" class="progress-bar-fill h-full rounded-full" style="width: 15%;"></div>
            </div>
        <?php endif; ?>
    </header>

    <!-- Main Dynamic Container -->
    <main class="max-w-xl mx-auto w-full my-auto py-4 sm:py-6">
        <div id="quizAppCard" class="quiz-card <?= $isDark ? 'bg-[#151921] border-[#252B36]' : 'bg-white border-[#E2E8F0]' ?> border <?= e($cardRadius) ?> p-6 sm:p-9">
            
            <!-- Loading state initially -->
            <div id="loadingState" class="text-center py-16">
                <div class="w-9 h-9 border-3 border-t-transparent rounded-full animate-spin mx-auto mb-3.5" style="border-color: var(--brand-primary); border-top-color: transparent;"></div>
                <p class="text-xs font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">Preparing your tailored experience...</p>
            </div>

            <!-- Form Container -->
            <div id="formSection" class="hidden space-y-6">
                <div class="text-left">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-bold uppercase tracking-wide brand-bg text-white mb-2">
                        Get Started
                    </span>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight <?= $isDark ? 'text-white' : 'text-[#0F172A]' ?>" id="formHeading">Before we begin</h2>
                    <p class="text-xs sm:text-sm <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> mt-1.5" id="formSubheading">Please provide your details so we can tailor the best recommendations for you.</p>
                </div>

                <form id="preQuizForm" onsubmit="handleFormSubmit(event)" class="space-y-4 pt-1">
                    <div id="formFieldsContainer" class="space-y-4"></div>
                    <div id="formErrorNotice" class="text-xs text-rose-500 hidden font-semibold p-2.5 rounded-lg bg-rose-50 border border-rose-200"></div>
                    
                    <div class="pt-3 flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <button type="button" id="formBackBtn" onclick="handleBackNavigation()" class="px-4 py-3 rounded-xl border <?= $isDark ? 'border-slate-700 text-slate-300 hover:bg-slate-800' : 'border-slate-200 text-slate-700 hover:bg-slate-50' ?> text-xs font-bold hidden transition-colors">
                                ← Back
                            </button>
                            <button type="submit" id="formSubmitBtn" class="w-full py-3.5 rounded-xl btn-brand text-white text-sm font-bold shadow-md">
                                Continue to Questions →
                            </button>
                        </div>

                        <!-- Micro Trust Seal directly under submit -->
                        <div class="flex items-center justify-center gap-2 text-[11px] <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> pt-1">
                            <span><?= e($trustBadge) ?></span>
                        </div>
                    </div>
                </form>
            </div>

                        <!-- Video Gate Step Container (🎬 Interactive Video Gate Node) -->
            <div id="videoGateSection" class="hidden space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-rose-500 flex items-center gap-1.5">
                            <span>🎬</span>
                            <span id="videoGatePlatformLabel">Interactive Video Step</span>
                        </span>
                        <span id="videoGateBadge" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20 shadow-2xs">
                            <span id="videoGateBadgeIcon">🔒</span>
                            <span id="videoGateBadgeText">Gate at 00:45s</span>
                        </span>
                    </div>
                    <h2 id="videoHeadline" class="text-xl sm:text-2xl font-extrabold <?= $isDark ? 'text-white' : 'text-[#0F172A]' ?> tracking-tight leading-snug"></h2>
                </div>

                <!-- Video Viewport Container (16:9 Aspect Ratio) -->
                <div id="videoPlayerWrapper" class="relative w-full aspect-video rounded-2xl overflow-hidden bg-black shadow-lg border <?= $isDark ? 'border-slate-800' : 'border-slate-200' ?> select-none">
                    <!-- Underlying Iframe Container -->
                    <div id="videoIframeTarget" class="absolute inset-0 w-full h-full pointer-events-none"></div>

                    <!-- Transparent Interaction Shield: intercepts direct clicks to prevent opening YT/Vimeo external links -->
                    <div id="videoInteractionShield" onclick="handleVideoShieldClick()" class="absolute inset-0 z-10 cursor-pointer"></div>

                    <!-- Floating Tap to Unmute Badge (shown if smart autoplay starts muted) -->
                    <button type="button" id="tapToUnmuteBtn" onclick="unmuteVideoPlayer(event)" class="hidden absolute top-4 left-4 z-20 px-3 py-1.5 rounded-full bg-black/80 backdrop-blur-md text-white text-xs font-bold border border-white/20 shadow-lg flex items-center gap-1.5 hover:bg-black/90 transition-all">
                        <span>🔊</span>
                        <span>Tap to Unmute</span>
                    </button>

                    <!-- Big Play / Pause Overlay Icon indicator on user tap -->
                    <div id="playStateIndicator" class="pointer-events-none absolute inset-0 z-15 flex items-center justify-center opacity-0 transition-opacity duration-200">
                        <div class="w-16 h-16 rounded-full bg-black/60 backdrop-blur-sm text-white flex items-center justify-center text-2xl shadow-xl">
                            <span id="playStateIcon">▶</span>
                        </div>
                    </div>

                </div>

                <!-- Glassmorphism Mid-Roll Gate Modal Popup (Floating Viewport Modal z-50) -->
                <div id="videoGateOverlay" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-md transition-all duration-300 hidden select-none" style="backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">
                    <div class="w-full max-w-md max-h-[90vh] overflow-y-auto p-6 sm:p-7 rounded-3xl border border-white/10 bg-slate-900/95 text-white shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-200">
                        <div class="text-center space-y-2">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                <span id="overlayPillBadgeText">🔒 Video Gate Required</span>
                            </div>
                            <h3 id="overlayGateHeadline" class="text-lg sm:text-xl font-extrabold tracking-tight text-white leading-snug"></h3>
                            <p id="overlayGateSubheadline" class="text-xs text-slate-400 font-medium leading-relaxed"></p>
                        </div>

                        <!-- Dynamic Gate Content: Form Fields OR Question Options -->
                        <div id="overlayGateContent"></div>

                        <!-- Error Notice inside Overlay -->
                        <div id="overlayErrorNotice" class="hidden text-xs text-rose-400 font-semibold p-3 rounded-xl bg-rose-500/10 border border-rose-500/20"></div>

                        <!-- Skip Gate Link (if allow_skip_gate is true) -->
                        <div id="overlaySkipContainer" class="hidden text-center pt-1">
                            <button type="button" onclick="skipVideoGate()" class="text-xs text-slate-400 hover:text-white underline transition-colors">
                                Skip for now →
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Custom Branded Player Control Bar -->
                <div id="customPlayerControls" class="p-3 rounded-xl border <?= $isDark ? 'border-slate-800 bg-[#141822]' : 'border-slate-200 bg-white' ?> shadow-xs space-y-2">
                    <!-- Custom Progress Bar Track -->
                    <div class="relative w-full pt-1 pb-1">
                        <div id="customProgressTrack" onclick="handleProgressBarSeek(event)" class="relative w-full h-2.5 bg-slate-200 dark:bg-slate-800 rounded-full cursor-not-allowed overflow-visible">
                            <!-- Filled Bar -->
                            <div id="customProgressFill" class="h-full rounded-full brand-bg transition-all duration-75" style="width: 0%;"></div>
                            <!-- Lock Marker -->
                            <div id="gateLockMarker" class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-amber-400 border-2 border-white shadow-md flex items-center justify-center text-[8px] z-20 pointer-events-none" title="Gate Lock Marker">
                                🔒
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Buttons & Time Display -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-3">
                            <!-- Play / Pause Button -->
                            <button type="button" id="controlPlayPauseBtn" onclick="togglePlayPause()" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold transition-colors">
                                <span id="controlPlayIcon">▶</span>
                            </button>
                            <!-- Time Display -->
                            <span class="font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                <span id="currentTimeDisplay">00:00</span> / <span id="durationDisplay">00:00</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Mute / Unmute Button -->
                            <button type="button" id="controlMuteBtn" onclick="toggleMute()" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold transition-colors">
                                <span id="controlMuteIcon">🔊</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- End of Video CTA Button (when on_video_end is 'show_cta_button') -->
                <div id="videoEndCtaContainer" class="hidden pt-2">
                    <button type="button" id="videoEndCtaBtn" onclick="handleVideoStepComplete()" class="w-full py-4 rounded-xl btn-brand text-white text-sm font-extrabold shadow-md flex items-center justify-center gap-2">
                        <span id="videoEndCtaBtnText">Continue to Next Step →</span>
                    </button>
                </div>

                <!-- Navigation Footer (Back Button) -->
                <div class="pt-3 border-t <?= $isDark ? 'border-slate-800' : 'border-slate-100' ?> flex items-center justify-between">
                    <button type="button" id="videoBackBtn" onclick="handleBackNavigation()" class="px-4 py-2.5 rounded-xl border <?= $isDark ? 'border-slate-700 text-slate-300 hover:bg-slate-800' : 'border-slate-200 text-slate-700 hover:bg-slate-50' ?> text-xs font-bold hidden transition-colors">
                        ← Back
                    </button>
                </div>
            </div>

            <!-- Question Step Container -->
            <div id="questionSection" class="hidden space-y-6">
                <div>
                    <div class="flex items-center justify-between">
                        <span id="questionStepBadge" class="text-[11px] font-extrabold uppercase tracking-wider brand-text">Question</span>
                        <span class="text-[11px] font-medium <?= $isDark ? 'text-slate-500' : 'text-slate-400' ?>">Select one option</span>
                    </div>
                    <h2 id="questionText" class="text-xl sm:text-2xl font-extrabold <?= $isDark ? 'text-white' : 'text-[#0F172A]' ?> mt-1 tracking-tight leading-snug"></h2>
                    <p id="questionHelp" class="text-xs <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> mt-1.5 hidden"></p>
                </div>

                <div id="optionsContainer" class="space-y-3"></div>

                <div id="answerErrorNotice" class="text-xs text-rose-500 hidden font-semibold p-2.5 rounded-lg bg-rose-50 border border-rose-200"></div>

                <div class="pt-5 border-t <?= $isDark ? 'border-slate-800' : 'border-slate-100' ?> flex items-center justify-between">
                    <button type="button" id="backBtn" onclick="handleBackNavigation()" class="px-4 py-2.5 rounded-xl border <?= $isDark ? 'border-slate-700 text-slate-300 hover:bg-slate-800' : 'border-slate-200 text-slate-700 hover:bg-slate-50' ?> text-xs font-bold hidden transition-colors">
                        ← Back
                    </button>
                    <button type="button" id="continueBtn" onclick="handleAnswerSubmit()" class="ml-auto px-7 py-3 rounded-xl btn-brand text-white text-xs font-bold shadow-md">
                        Continue →
                    </button>
                </div>
            </div>

            <!-- Result / Destination Container -->
            <div id="resultSection" class="hidden space-y-6 text-center py-2">
                <div id="resultIcon" class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 font-extrabold text-2xl flex items-center justify-center mx-auto border border-emerald-200 shadow-sm animate-bounce">
                    ✓
                </div>
                <div>
                    <h2 id="resultHeading" class="text-2xl font-extrabold tracking-tight <?= $isDark ? 'text-white' : 'text-[#0F172A]' ?>">Quiz Completed!</h2>
                    <p id="resultSubheading" class="text-xs sm:text-sm <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> mt-1.5">Thank you. Based on your personalized answers, here is your recommendation:</p>
                </div>

                <!-- Instant Quote / Calculator Card -->
                <div id="resultQuoteCard" class="hidden p-6 sm:p-7 rounded-2xl border <?= $isDark ? 'border-slate-800 bg-slate-900/90' : 'border-slate-200/80 bg-white' ?> text-center space-y-4 shadow-sm" style="border-color: var(--brand-primary); box-shadow: 0 10px 30px -10px var(--brand-primary-light);">
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase shadow-2xs" style="background-color: var(--brand-primary-light); color: var(--brand-primary);">
                        <span>🧮</span>
                        <span id="quoteCardHeading">Your Estimated Quote</span>
                    </div>

                    <div class="space-y-1.5">
                        <div id="quoteFormattedAmount" class="text-4xl sm:text-5xl font-black font-mono brand-text tracking-tight">
                            ₹ 0
                        </div>
                        <p id="quoteCardSubtext" class="text-xs <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> font-medium">Calculated dynamically based on your specifications</p>
                    </div>
                </div>

                <div id="resultDestCard" class="p-5 <?= $isDark ? 'bg-slate-900 border-slate-800' : 'bg-slate-50 border-slate-200' ?> border rounded-xl text-left space-y-3 shadow-2xs">
                    <div class="flex items-center justify-between pb-2.5 border-b <?= $isDark ? 'border-slate-800' : 'border-slate-200' ?>">
                        <span class="text-xs font-bold uppercase tracking-wider <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">Recommended Outcome:</span>
                        <span id="resultDestName" class="text-xs font-extrabold brand-text"></span>
                    </div>

                    <div id="resultAnswersSummary" class="space-y-2 text-xs"></div>
                </div>

                <!-- 1-Click Call & WhatsApp Quick Connect Actions -->
                <div id="resultQuickConnectCard" class="hidden space-y-2.5">
                    <div id="quickConnectButtonsGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5"></div>
                </div>

                <div id="redirectNotice" class="text-xs font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> hidden">
                    Redirecting you in <strong id="countdownSeconds" class="font-bold brand-text">3</strong> seconds...
                </div>

                <a id="destinationCtaBtn" href="#" class="inline-flex items-center justify-center w-full py-4 rounded-xl btn-brand text-white text-sm font-extrabold shadow-md">
                    Continue to Your Results →
                </a>
            </div>

        </div>
    </main>

    <!-- 100% White-Labeled Marketing Footer -->
    <footer class="max-w-xl mx-auto w-full py-6 text-center text-xs <?= $isDark ? 'text-slate-500' : 'text-slate-400' ?> space-y-2">
        <div class="flex items-center justify-center gap-4 text-[11px] font-medium">
            <?php if (!empty($privacyUrl)): ?>
                <a href="<?= e($privacyUrl) ?>" target="_blank" class="hover:underline">Privacy Policy</a>
                <span>•</span>
            <?php endif; ?>
            <?php if (!empty($termsUrl)): ?>
                <a href="<?= e($termsUrl) ?>" target="_blank" class="hover:underline">Terms of Service</a>
                <span>•</span>
            <?php endif; ?>
            <span><?= e($footerText) ?></span>
        </div>

        <?php if (!$isWhiteLabel): ?>
            <div class="text-[10px] text-slate-400 pt-1">
                Powered by <span class="font-semibold text-slate-600 <?= $isDark ? 'text-slate-400' : '' ?>">QFunnel Platform</span>
            </div>
        <?php endif; ?>
    </footer>

    <!-- Quiz Engine Runner Client Script -->
    <script>
        const SLUG = '<?= e($slug) ?>';
        const API_BASE = '<?= url('') ?>';
        let sessionToken = null;
        let currentStepData = null;
        let selectedOptionId = null;
        let currentStepNumber = 1;
        const totalEstimatedSteps = 4;

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const resp = await fetch(`${API_BASE}/api/quiz/${SLUG}/init`, {
                    method: 'POST'
                });
                const data = await resp.json();
                if (data.success) {
                    sessionToken = data.data.session_token;
                    if (data.data.has_form) {
                        updateProgressBar(1, 4);
                        showForm(data.data.form_fields);
                    } else {
                        loadCurrentStep();
                    }
                } else {
                    showError(data.error || 'Failed to initialize session.');
                }
            } catch (e) {
                showError('Network error starting quiz. Please refresh.');
            }
        });

        function updateProgressBar(current, total) {
            const bar = document.getElementById('funnelProgressBar');
            if (!bar) return;
            const pct = Math.min(100, Math.max(15, Math.round((current / (total || 4)) * 100)));
            bar.style.width = `${pct}%`;
        }

        function showForm(fields, formConfig = null) {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('questionSection').classList.add('hidden');
            document.getElementById('resultSection').classList.add('hidden');
            document.getElementById('formSection').classList.remove('hidden');

            const heading = document.getElementById('formHeading');
            const subheading = document.getElementById('formSubheading');
            const submitBtn = document.getElementById('formSubmitBtn');
            const backBtn = document.getElementById('formBackBtn');

            if (formConfig) {
                heading.textContent = formConfig.heading || formConfig.title || 'Lead Capture Form';
                const sub = (formConfig.subheading !== undefined && formConfig.subheading !== null)
                    ? formConfig.subheading
                    : (formConfig.description || 'Please fill out the form to proceed.');
                subheading.textContent = sub;
                if (!sub || sub.trim() === '') {
                    subheading.classList.add('hidden');
                } else {
                    subheading.classList.remove('hidden');
                }
                const btnText = formConfig.button_text || 'Submit Details';
                submitBtn.textContent = (btnText.endsWith('→') || btnText.endsWith('->')) ? btnText : (btnText + ' →');
                if (formConfig.can_go_back) {
                    backBtn.classList.remove('hidden');
                } else {
                    backBtn.classList.add('hidden');
                }
            } else {
                heading.textContent = 'Before we begin';
                subheading.textContent = 'Please provide your details so we can tailor the best recommendations for you.';
                subheading.classList.remove('hidden');
                submitBtn.textContent = 'Continue to Questions →';
                backBtn.classList.add('hidden');
            }

            const container = document.getElementById('formFieldsContainer');
            container.innerHTML = '';

            fields.forEach(f => {
                const div = document.createElement('div');
                div.className = 'space-y-1.5 text-xs text-left';

                if (f.field_type === 'consent') {
                    div.innerHTML = `
                        <label class="flex items-start gap-3 cursor-pointer pt-2">
                            <input type="checkbox" name="${f.field_key}" value="1" ${f.is_required ? 'required' : ''} class="mt-0.5 rounded text-[var(--brand-primary)]">
                            <span class="text-xs leading-relaxed <?= $isDark ? 'text-slate-400' : 'text-slate-600' ?>">${escapeHtml(f.label)}${f.is_required ? ' *' : ''}</span>
                        </label>
                    `;
                } else if (f.field_type === 'dropdown') {
                    div.innerHTML = `
                        <label class="block font-bold <?= $isDark ? 'text-slate-200' : 'text-slate-800' ?>">${escapeHtml(f.label)}${f.is_required ? ' *' : ''}</label>
                        <select name="${f.field_key}" ${f.is_required ? 'required' : ''} class="w-full px-3.5 py-3 rounded-xl border <?= $isDark ? 'bg-slate-900 border-slate-700 text-white' : 'bg-white border-slate-200 text-slate-800' ?> text-sm custom-focus">
                            <option value="">${escapeHtml(f.placeholder || 'Select an option')}</option>
                            ${(f.options || []).map(opt => `<option value="${escapeHtml(opt.value || opt.label)}">${escapeHtml(opt.label)}</option>`).join('')}
                        </select>
                    `;
                } else {
                    const inputType = f.field_type === 'email' ? 'email' : (f.field_type === 'number' ? 'number' : 'text');
                    div.innerHTML = `
                        <label class="block font-bold <?= $isDark ? 'text-slate-200' : 'text-slate-800' ?>">${escapeHtml(f.label)}${f.is_required ? ' *' : ''}</label>
                        <input type="${inputType}" name="${f.field_key}" placeholder="${escapeHtml(f.placeholder || '')}" ${f.is_required ? 'required' : ''}
                               class="w-full px-3.5 py-3 rounded-xl border <?= $isDark ? 'bg-slate-900 border-slate-700 text-white' : 'bg-white border-slate-200 text-slate-800' ?> text-sm custom-focus">
                    `;
                }

                container.appendChild(div);
            });
        }

        async function handleFormSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('formSubmitBtn');
            const errBox = document.getElementById('formErrorNotice');
            btn.disabled = true;
            btn.textContent = 'Saving...';
            errBox.classList.add('hidden');

            const form = document.getElementById('preQuizForm');
            const formData = new FormData(form);
            const payload = {};
            formData.forEach((value, key) => { payload[key] = value; });

            try {
                let resp;
                if (currentStepData && currentStepData.node_type === 'form') {
                    resp = await fetch(`${API_BASE}/api/quiz/answer`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Quiz-Session': sessionToken
                        },
                        body: JSON.stringify({
                            node_id: currentStepData.node_id,
                            answer: { form_data: payload }
                        })
                    });
                } else {
                    resp = await fetch(`${API_BASE}/api/quiz/form`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Quiz-Session': sessionToken
                        },
                        body: JSON.stringify({ form_data: payload })
                    });
                }

                const data = await resp.json();
                if (data.success) {
                    document.getElementById('formSection').classList.add('hidden');
                    renderStep(data.data);
                } else {
                    errBox.textContent = data.error || 'Please fill in all required fields.';
                    errBox.classList.remove('hidden');
                }
            } catch (err) {
                errBox.textContent = 'Network error saving form.';
                errBox.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                const bText = currentStepData?.button_text || 'Submit Details';
                btn.textContent = (bText.endsWith('→') || bText.endsWith('->')) ? bText : (bText + ' →');
            }
        }

        async function loadCurrentStep() {
            try {
                const resp = await fetch(`${API_BASE}/api/quiz/step`, {
                    headers: { 'X-Quiz-Session': sessionToken }
                });
                const data = await resp.json();
                if (data.success) {
                    renderStep(data.data);
                }
            } catch (e) {
                showError('Failed to load step.');
            }
        }

        function renderStep(stepResponse) {
            document.getElementById('loadingState').classList.add('hidden');

            if (stepResponse.status === 'completed') {
                updateProgressBar(4, 4);
                renderResult(stepResponse.result);
                return;
            }

            if (stepResponse.status === 'video_gate_step') {
                const qSec = document.getElementById('questionSection');
                if (qSec) qSec.classList.add('hidden');
                const fSec = document.getElementById('formSection');
                if (fSec) fSec.classList.add('hidden');
                const rSec = document.getElementById('resultSection');
                if (rSec) rSec.classList.add('hidden');
                const vgSec = document.getElementById('videoGateSection');
                if (vgSec) vgSec.classList.remove('hidden');

                document.getElementById('stepProgressBadge').classList.remove('hidden');
                currentStepNumber = stepResponse.step.step_number || 1;
                document.getElementById('currentStepNum').textContent = currentStepNumber;
                updateProgressBar(currentStepNumber, 4);
                currentStepData = stepResponse.step;
                showVideoGate(stepResponse.step);
                return;
            }

            const vgSec = document.getElementById('videoGateSection');
            if (vgSec) vgSec.classList.add('hidden');
            cleanupVideoPlayer();

            if (stepResponse.status === 'form_step') {
                document.getElementById('questionSection').classList.add('hidden');
                document.getElementById('resultSection').classList.add('hidden');
                document.getElementById('stepProgressBadge').classList.remove('hidden');
                currentStepNumber = stepResponse.step.step_number || 1;
                document.getElementById('currentStepNum').textContent = currentStepNumber;
                updateProgressBar(currentStepNumber, 4);
                currentStepData = stepResponse.step;
                showForm(stepResponse.step.form_fields, stepResponse.step);
                return;
            }

            document.getElementById('formSection').classList.add('hidden');
            document.getElementById('resultSection').classList.add('hidden');

            const step = stepResponse.step;
            currentStepData = step;
            selectedOptionId = null;

            document.getElementById('questionSection').classList.remove('hidden');
            document.getElementById('stepProgressBadge').classList.remove('hidden');
            currentStepNumber = step.step_number || 1;
            document.getElementById('currentStepNum').textContent = currentStepNumber;
            updateProgressBar(currentStepNumber, 4);

            document.getElementById('questionText').textContent = step.question_text;
            const help = document.getElementById('questionHelp');
            if (step.help_text) {
                help.textContent = step.help_text;
                help.classList.remove('hidden');
            } else {
                help.classList.add('hidden');
            }

            // Back button visibility
            const backBtn = document.getElementById('backBtn');
            if (step.can_go_back) {
                backBtn.classList.remove('hidden');
            } else {
                backBtn.classList.add('hidden');
            }

            // Options or Slider Input
            const container = document.getElementById('optionsContainer');
            container.innerHTML = '';

            if (step.input_type === 'slider') {
                const cfg = step.slider_config || {};
                const min = cfg.min_value !== undefined ? parseFloat(cfg.min_value) : 1;
                const max = cfg.max_value !== undefined ? parseFloat(cfg.max_value) : 100;
                const stepVal = cfg.step_value !== undefined ? parseFloat(cfg.step_value) : 1;
                const defVal = (step.current_slider_value !== null && step.current_slider_value !== undefined) 
                    ? parseFloat(step.current_slider_value) 
                    : (cfg.default_value !== undefined ? parseFloat(cfg.default_value) : min);
                const prefix = cfg.unit_prefix || '';
                const unit = cfg.unit_label || '';

                currentSliderValue = defVal;

                container.innerHTML = `
                    <div class="p-6 rounded-2xl border <?= $isDark ? 'border-slate-800 bg-[#141822]' : 'border-slate-200 bg-white' ?> shadow-sm space-y-6">
                        <!-- Live Value Banner Display -->
                        <div class="text-center space-y-1">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Selected Value</span>
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="text-3xl sm:text-4xl font-extrabold brand-text tracking-tight font-mono" id="sliderDisplayValue">
                                    ${escapeHtml(prefix)} ${Number(defVal).toLocaleString()}
                                </span>
                                ${unit ? `<span class="text-sm sm:text-base font-semibold <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">${escapeHtml(unit)}</span>` : ''}
                            </div>
                        </div>

                        <!-- Range Slider Input -->
                        <div class="space-y-2 pt-2 px-2">
                            <input type="range" id="runnerRangeSlider" min="${min}" max="${max}" step="${stepVal}" value="${defVal}"
                                   oninput="updateRunnerSliderValue(this.value, '${escapeHtml(prefix)}', '${escapeHtml(unit)}')"
                                   class="w-full h-2.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[var(--brand-primary)]">
                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 font-mono">
                                <span>${escapeHtml(prefix)} ${Number(min).toLocaleString()} ${escapeHtml(unit)}</span>
                                <span>${escapeHtml(prefix)} ${Number(max).toLocaleString()} ${escapeHtml(unit)}</span>
                            </div>
                        </div>

                        <!-- Stepper Buttons & Exact Number Input -->
                        <div class="flex items-center justify-center gap-3 pt-2">
                            <button type="button" onclick="adjustRunnerSlider(-${stepVal}, '${escapeHtml(prefix)}', '${escapeHtml(unit)}')"
                                    class="w-10 h-10 rounded-xl border <?= $isDark ? 'border-slate-700 bg-slate-800 text-white hover:bg-slate-700' : 'border-slate-200 bg-slate-100 text-slate-800 hover:bg-slate-200' ?> font-extrabold text-lg flex items-center justify-center transition-colors">
                                −
                            </button>
                            <input type="number" id="runnerNumericInput" min="${min}" max="${max}" step="${stepVal}" value="${defVal}"
                                   oninput="updateRunnerSliderValue(this.value, '${escapeHtml(prefix)}', '${escapeHtml(unit)}')"
                                   class="w-24 px-3 py-2 rounded-xl text-center font-bold font-mono text-base border <?= $isDark ? 'border-slate-700 bg-slate-800 text-white' : 'border-slate-200 bg-white text-slate-900' ?> custom-focus">
                            <button type="button" onclick="adjustRunnerSlider(${stepVal}, '${escapeHtml(prefix)}', '${escapeHtml(unit)}')"
                                    class="w-10 h-10 rounded-xl border <?= $isDark ? 'border-slate-700 bg-slate-800 text-white hover:bg-slate-700' : 'border-slate-200 bg-slate-100 text-slate-800 hover:bg-slate-200' ?> font-extrabold text-lg flex items-center justify-center transition-colors">
                                +
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            const keyLetters = ['A', 'B', 'C', 'D', 'E', 'F'];

            (step.options || []).forEach((opt, index) => {
                const isPreselected = step.selected_option_ids && step.selected_option_ids.includes(opt.id);
                if (isPreselected) selectedOptionId = opt.id;

                const letter = keyLetters[index] || (index + 1);
                const card = document.createElement('div');
                card.className = `option-card p-4 rounded-xl border <?= $isDark ? 'border-slate-800 bg-[#1A1F2B]' : 'border-slate-200 bg-white' ?> cursor-pointer flex items-center justify-between text-sm font-semibold <?= $isDark ? 'text-white' : 'text-[#0F172A]' ?> ${isPreselected ? 'selected' : ''}`;
                card.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="option-key-badge w-6 h-6 rounded-md border <?= $isDark ? 'border-slate-700 bg-slate-800 text-slate-300' : 'border-slate-200 bg-slate-50 text-slate-600' ?> text-xs font-bold flex items-center justify-center">
                            ${letter}
                        </span>
                        <span>${escapeHtml(opt.label)}</span>
                    </div>
                    <span class="w-5 h-5 rounded-full border border-slate-300 flex items-center justify-center check-dot">
                        ${isPreselected ? '<span class="w-2.5 h-2.5 rounded-full brand-bg"></span>' : ''}
                    </span>
                `;

                card.addEventListener('click', () => {
                    document.querySelectorAll('.option-card').forEach(c => {
                        c.classList.remove('selected');
                        const dot = c.querySelector('.check-dot');
                        if (dot) dot.innerHTML = '';
                    });
                    card.classList.add('selected');
                    card.querySelector('.check-dot').innerHTML = '<span class="w-2.5 h-2.5 rounded-full brand-bg"></span>';
                    selectedOptionId = opt.id;
                    document.getElementById('answerErrorNotice').classList.add('hidden');
                });

                container.appendChild(card);
            });
        }

        let currentSliderValue = 10;
        function updateRunnerSliderValue(val, prefix = '', unit = '') {
            const num = parseFloat(val) || 0;
            currentSliderValue = num;
            const slider = document.getElementById('runnerRangeSlider');
            const numInput = document.getElementById('runnerNumericInput');
            const display = document.getElementById('sliderDisplayValue');
            if (slider && slider.value != num) slider.value = num;
            if (numInput && numInput.value != num) numInput.value = num;
            if (display) {
                display.textContent = `${prefix} ${Number(num).toLocaleString()}`.trim();
            }
            const err = document.getElementById('answerErrorNotice');
            if (err) err.classList.add('hidden');
        }

        function adjustRunnerSlider(delta, prefix = '', unit = '') {
            const slider = document.getElementById('runnerRangeSlider');
            if (!slider) return;
            const min = parseFloat(slider.min) || 0;
            const max = parseFloat(slider.max) || 100;
            let current = parseFloat(slider.value) || 0;
            let next = Math.max(min, Math.min(max, current + delta));
            slider.value = next;
            updateRunnerSliderValue(next, prefix, unit);
        }

        async function handleAnswerSubmit() {
            let answerPayload = {};
            if (currentStepData && currentStepData.input_type === 'slider') {
                const sliderInput = document.getElementById('runnerRangeSlider');
                const val = sliderInput ? parseFloat(sliderInput.value) : (currentSliderValue ?? 0);
                answerPayload = { slider_value: val };
            } else {
                if (!selectedOptionId) {
                    const errBox = document.getElementById('answerErrorNotice');
                    errBox.textContent = 'Please choose an option to continue.';
                    errBox.classList.remove('hidden');
                    return;
                }
                answerPayload = { option_id: selectedOptionId };
            }

            const btn = document.getElementById('continueBtn');
            btn.disabled = true;
            btn.textContent = 'Processing...';

            try {
                const resp = await fetch(`${API_BASE}/api/quiz/answer`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Quiz-Session': sessionToken
                    },
                    body: JSON.stringify({
                        node_id: currentStepData.node_id,
                        answer: answerPayload
                    })
                });

                const data = await resp.json();
                if (data.success) {
                    renderStep(data.data);
                } else {
                    document.getElementById('answerErrorNotice').textContent = data.error || 'Failed to submit answer.';
                    document.getElementById('answerErrorNotice').classList.remove('hidden');
                }
            } catch (e) {
                document.getElementById('answerErrorNotice').textContent = 'Network error.';
                document.getElementById('answerErrorNotice').classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Continue →';
            }
        }

        async function handleBackNavigation() {
            const btn = document.getElementById('backBtn');
            if (btn) btn.disabled = true;
            const formBtn = document.getElementById('formBackBtn');
            if (formBtn) formBtn.disabled = true;

            try {
                const resp = await fetch(`${API_BASE}/api/quiz/back`, {
                    method: 'POST',
                    headers: { 'X-Quiz-Session': sessionToken }
                });
                const data = await resp.json();
                if (data.success) {
                    renderStep(data.data);
                }
            } catch (e) {
                alert('Back navigation failed.');
            } finally {
                if (btn) btn.disabled = false;
                if (formBtn) formBtn.disabled = false;
            }
        }

        function renderResult(result) {
            cleanupVideoPlayer();
            const vgSec = document.getElementById('videoGateSection');
            if (vgSec) vgSec.classList.add('hidden');
            const questionSection = document.getElementById('questionSection');
            if (questionSection) questionSection.classList.add('hidden');
            const formSection = document.getElementById('formSection');
            if (formSection) formSection.classList.add('hidden');
            const progressBadge = document.getElementById('stepProgressBadge');
            if (progressBadge) progressBadge.classList.add('hidden');
            const loadingState = document.getElementById('loadingState');
            if (loadingState) loadingState.classList.add('hidden');

            const resultSection = document.getElementById('resultSection');
            const resultHeading = document.getElementById('resultHeading');
            const resultSubheading = document.getElementById('resultSubheading');
            const resultDestCard = document.getElementById('resultDestCard');
            const resultQuoteCard = document.getElementById('resultQuoteCard');
            const cta = document.getElementById('destinationCtaBtn');
            const notice = document.getElementById('redirectNotice');
            const resultIcon = document.getElementById('resultIcon');

            if (resultSection) resultSection.classList.remove('hidden');

            // 1. Thank You Screen (Message / No Redirection)
            if (result.completion_type === 'message') {
                if (resultHeading) resultHeading.textContent = result.success_heading || 'Thank You!';
                if (resultSubheading) resultSubheading.textContent = result.success_message || 'Your submission has been received.';
                if (resultDestCard) resultDestCard.classList.add('hidden');
                if (resultQuoteCard) resultQuoteCard.classList.add('hidden');
                if (cta) cta.classList.add('hidden');
                if (notice) notice.classList.add('hidden');
                return;
            }

            const targetUrl = (result.destination_url || '').trim();
            const quoteCard = result.quote_card || (result.summary && result.summary.quote_card);
            const quickConnect = result.quick_connect || (result.summary && result.summary.quick_connect);
            const hasQC = quickConnect && quickConnect.enabled && (quickConnect.call_url || quickConnect.whatsapp_url);

            // Quick Connect Buttons (1-Click Call / WhatsApp / Both)
            const qcCard = document.getElementById('resultQuickConnectCard');
            const qcGrid = document.getElementById('quickConnectButtonsGrid');

            if (hasQC && qcCard && qcGrid) {
                qcCard.classList.remove('hidden');
                qcGrid.innerHTML = '';

                const hasCall = (quickConnect.action_type === 'both' || quickConnect.action_type === 'call') && quickConnect.call_url;
                const hasWa = (quickConnect.action_type === 'both' || quickConnect.action_type === 'whatsapp') && quickConnect.whatsapp_url;

                if (hasCall && hasWa) {
                    qcGrid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-2.5';
                } else {
                    qcGrid.className = 'grid grid-cols-1 gap-2.5';
                }

                const cleanWaText = (quickConnect.whatsapp_button_text || 'Chat on WhatsApp').replace(/^[\s\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}💬📞📱⚡]+/u, '').trim() || 'Chat on WhatsApp';
                const cleanCallText = (quickConnect.call_button_text || 'Call Specialist Now').replace(/^[\s\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}💬📞📱⚡]+/u, '').trim() || 'Call Specialist Now';

                if (hasWa) {
                    qcGrid.innerHTML += `
                        <a href="${escapeHtml(quickConnect.whatsapp_url)}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center justify-center gap-2.5 px-5 py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-extrabold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                            <svg class="w-5 h-5 fill-current shrink-0" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.301-.15-1.781-.878-2.057-.978-.276-.1-.476-.15-.676.15-.2.301-.776.978-.952 1.179-.175.2-.351.226-.652.075-1.523-.761-2.522-1.36-3.535-3.097-.267-.458.267-.425.762-1.415.083-.166.042-.313-.021-.438-.063-.125-.676-1.63-.927-2.235-.244-.589-.493-.509-.676-.519-.175-.009-.376-.01-.577-.01-.2 0-.526.075-.802.376-.276.301-1.053 1.028-1.053 2.507 0 1.479 1.078 2.908 1.229 3.109.15.2 2.12 3.237 5.136 4.54.717.311 1.277.497 1.714.636.721.23 1.377.197 1.895.12.578-.087 1.781-.728 2.032-1.431.251-.703.251-1.305.176-1.431-.076-.126-.276-.201-.577-.351zM12.042 21.75c-1.748 0-3.46-.46-4.966-1.332l-.356-.207-3.693.968.986-3.599-.23-.366c-.958-1.524-1.464-3.292-1.464-5.111 0-5.32 4.338-9.65 9.67-9.65 2.583 0 5.011 1.006 6.837 2.833 1.826 1.826 2.831 4.256 2.831 6.84 0 5.322-4.337 9.626-9.625 9.626zM12.042 0C5.402 0 0 5.402 0 12.042c0 2.119.553 4.185 1.603 6.004L0 24l6.124-1.581c1.747.952 3.731 1.455 5.918 1.455 6.64 0 12.042-5.402 12.042-12.042C24.084 5.402 18.682 0 12.042 0z"/>
                            </svg>
                            <span>${escapeHtml(cleanWaText)}</span>
                        </a>
                    `;
                }

                if (hasCall) {
                    qcGrid.innerHTML += `
                        <a href="${escapeHtml(quickConnect.call_url)}"
                           class="inline-flex items-center justify-center gap-2.5 px-5 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                            <svg class="w-5 h-5 fill-current shrink-0" viewBox="0 0 24 24">
                                <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.45-5.15-3.76-6.59-6.59l1.97-1.57c.28-.28.37-.68.25-1.02A11.36 11.36 0 018.96 4c0-.55-.45-1-1-1H4.16c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.81c0-.55-.45-1-1-1z"/>
                            </svg>
                            <span>${escapeHtml(cleanCallText)}</span>
                        </a>
                    `;
                }
            } else {
                if (qcCard) qcCard.classList.add('hidden');
            }

            // 2. Calculator & Quote Outcome Card
            if (quoteCard && quoteCard.formatted_quote) {
                if (resultHeading) resultHeading.textContent = 'Your Quote is Ready!';
                if (resultSubheading) resultSubheading.textContent = 'Here is your custom calculated quote based on your inputs:';

                if (resultQuoteCard) {
                    resultQuoteCard.classList.remove('hidden');
                    const quoteHeadingEl = document.getElementById('quoteCardHeading');
                    if (quoteHeadingEl) quoteHeadingEl.textContent = quoteCard.result_label || 'Your Estimated Quote';
                    const quoteAmountEl = document.getElementById('quoteFormattedAmount');
                    if (quoteAmountEl) quoteAmountEl.textContent = quoteCard.formatted_quote;


                }

                if (resultDestCard) resultDestCard.classList.add('hidden');

                if (!hasQC && targetUrl && targetUrl !== '#' && !targetUrl.startsWith('javascript:')) {
                    let resolvedUrl = targetUrl;
                    if (!/^https?:\/\//i.test(resolvedUrl) && !resolvedUrl.startsWith('/') && !resolvedUrl.startsWith('.')) {
                        resolvedUrl = 'https://' + resolvedUrl;
                    }

                    if (cta) {
                        cta.classList.remove('hidden');
                        cta.href = resolvedUrl;
                        cta.onclick = null;
                        cta.textContent = 'Lock in this Quote & Continue →';
                    }

                    if (result.auto_redirect) {
                        if (notice) notice.classList.remove('hidden');
                        let sec = result.redirect_delay_seconds || 5;
                        const cd = document.getElementById('countdownSeconds');
                        if (cd) cd.textContent = sec;
                        const timer = setInterval(() => {
                            sec--;
                            if (cd) cd.textContent = sec;
                            if (sec <= 0) {
                                clearInterval(timer);
                                window.location.href = resolvedUrl;
                            }
                        }, 1000);
                    }
                } else if (!hasQC) {
                    if (cta) {
                        cta.classList.remove('hidden');
                        cta.href = 'javascript:void(0)';
                        cta.onclick = () => window.location.reload();
                        cta.textContent = '↻ Calculate Again';
                    }
                    if (notice) notice.classList.add('hidden');
                } else {
                    if (cta) cta.classList.add('hidden');
                    if (notice) notice.classList.add('hidden');
                }
                return;
            }

            // 3. Destination Node without Quote: If redirecting and NO Quick Connect, fast-forward directly
            if (!hasQC && targetUrl && targetUrl !== '#' && !targetUrl.startsWith('javascript:')) {
                let resolvedUrl = targetUrl;
                if (!/^https?:\/\//i.test(resolvedUrl) && !resolvedUrl.startsWith('/') && !resolvedUrl.startsWith('.')) {
                    resolvedUrl = 'https://' + resolvedUrl;
                }

                if (resultDestCard) resultDestCard.classList.add('hidden');
                if (resultQuoteCard) resultQuoteCard.classList.add('hidden');
                if (cta) cta.classList.add('hidden');
                if (notice) notice.classList.add('hidden');

                if (resultIcon) {
                    resultIcon.className = 'w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto border border-indigo-200 shadow-sm';
                    resultIcon.innerHTML = `<svg class="w-6 h-6 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>`;
                }
                if (resultHeading) resultHeading.textContent = 'Redirecting...';
                if (resultSubheading) resultSubheading.textContent = `Taking you to ${result.destination_name || 'your results'}...`;

                window.location.href = resolvedUrl;
                return;
            }

            // 4. Fallback / Quick Connect Outcome: Show recommendation and actions
            if (resultHeading) resultHeading.textContent = hasQC ? 'You are All Set!' : 'Quiz Completed!';
            if (resultSubheading) resultSubheading.textContent = hasQC ? 'Choose how you would like to connect with us below:' : 'Thank you. Based on your answers, here is your recommendation:';
            if (resultDestCard) resultDestCard.classList.remove('hidden');
            if (resultQuoteCard) resultQuoteCard.classList.add('hidden');

            if (!hasQC && targetUrl && targetUrl !== '#' && !targetUrl.startsWith('javascript:')) {
                let resolvedUrl = targetUrl;
                if (!/^https?:\/\//i.test(resolvedUrl) && !resolvedUrl.startsWith('/') && !resolvedUrl.startsWith('.')) {
                    resolvedUrl = 'https://' + resolvedUrl;
                }
                if (cta) {
                    cta.classList.remove('hidden');
                    cta.href = resolvedUrl;
                    cta.onclick = null;
                    cta.textContent = 'Visit Destination Resource →';
                }
            } else {
                if (cta) {
                    if (hasQC) {
                        cta.classList.add('hidden'); // Quick Connect buttons are primary
                    } else {
                        cta.classList.remove('hidden');
                        cta.href = result.destination_url || '#';
                        cta.onclick = null;
                        cta.textContent = 'Continue to Your Results →';
                    }
                }
            }

            const destNameEl = document.getElementById('resultDestName');
            if (destNameEl) destNameEl.textContent = result.destination_name || 'Recommendation';

            const summaryContainer = document.getElementById('resultAnswersSummary');
            if (summaryContainer) {
                summaryContainer.innerHTML = '';
                const answers = ((result.summary && result.summary.answers) || []).filter(a => {
                    if (a.answer_type === 'form') return false;
                    const val = (a.answer || a.answer_value_text || '').trim();
                    if (val.startsWith('{') && val.endsWith('}')) return false;
                    return true;
                });

                answers.forEach(a => {
                    summaryContainer.innerHTML += `
                        <div class="flex items-center justify-between text-[11px] py-1 border-b <?= $isDark ? 'border-slate-800' : 'border-slate-200/60' ?> last:border-0">
                            <span class="<?= $isDark ? 'text-slate-400' : 'text-slate-500' ?> truncate max-w-[280px]">${escapeHtml(a.question || a.question_text_snapshot || '')}</span>
                            <strong class="<?= $isDark ? 'text-slate-200' : 'text-slate-900' ?> font-semibold">${escapeHtml(a.answer || a.answer_value_text || '')}</strong>
                        </div>
                    `;
                });
            }
        }

        function showError(msg) {
            document.getElementById('loadingState').innerHTML = `
                <div class="text-center py-8 text-rose-500 space-y-2">
                    <p class="font-bold text-sm">Unable to load quiz</p>
                    <p class="text-xs <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">${escapeHtml(msg)}</p>
                </div>
            `;
        }

        function escapeHtml(str) {
            return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // =========================================================================
        // 🎬 UNIFIED VIDEO GATE ENGINE (YouTube & Vimeo SDK Wrapper)
        // Mid-Roll Lead Capture & Question Turnstile with Anti-Skip Protection
        // =========================================================================
        let videoPlayerInstance = null;
        let videoProvider = null; // 'youtube' or 'vimeo'
        let videoTimePollInterval = null;
        let maxWatchedTime = 0;
        let isGateUnlocked = false;
        let gateTimestampSeconds = 45;
        let currentVideoDuration = 0;
        let isVideoPlaying = false;
        let isVideoMuted = false;
        let isGateOverlayVisible = false;
        let hasReachedGateMilestone = false;
        let currentVideoStepData = null;

        // Lazy-load official SDKs
        function loadYouTubeApi(callback) {
            if (window.YT && window.YT.Player) {
                callback();
                return;
            }
            if (!document.getElementById('yt-iframe-api-script')) {
                const tag = document.createElement('script');
                tag.id = 'yt-iframe-api-script';
                tag.src = 'https://www.youtube.com/iframe_api';
                const firstScript = document.getElementsByTagName('script')[0];
                firstScript.parentNode.insertBefore(tag, firstScript);
            }
            const prevReady = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function () {
                if (typeof prevReady === 'function') prevReady();
                callback();
            };
        }

        function loadVimeoApi(callback) {
            if (window.Vimeo && window.Vimeo.Player) {
                callback();
                return;
            }
            if (!document.getElementById('vimeo-player-api-script')) {
                const tag = document.createElement('script');
                tag.id = 'vimeo-player-api-script';
                tag.src = 'https://player.vimeo.com/api/player.js';
                tag.onload = callback;
                const firstScript = document.getElementsByTagName('script')[0];
                firstScript.parentNode.insertBefore(tag, firstScript);
            } else {
                const tag = document.getElementById('vimeo-player-api-script');
                const prevOnload = tag.onload;
                tag.onload = function () {
                    if (typeof prevOnload === 'function') prevOnload();
                    callback();
                };
            }
        }

        function cleanupVideoPlayer() {
            if (videoTimePollInterval) {
                clearInterval(videoTimePollInterval);
                videoTimePollInterval = null;
            }
            if (videoPlayerInstance) {
                try {
                    if (videoProvider === 'youtube' && typeof videoPlayerInstance.destroy === 'function') {
                        videoPlayerInstance.destroy();
                    } else if (videoProvider === 'vimeo' && typeof videoPlayerInstance.destroy === 'function') {
                        videoPlayerInstance.destroy();
                    }
                } catch (e) {
                    console.warn('Error destroying player:', e);
                }
                videoPlayerInstance = null;
            }
            const container = document.getElementById('videoIframeTarget');
            if (container) container.innerHTML = '';
            isVideoPlaying = false;
            isGateOverlayVisible = false;
        }

        function formatTimeMmSs(sec) {
            if (isNaN(sec) || sec < 0) sec = 0;
            const m = Math.floor(sec / 60);
            const s = Math.floor(sec % 60);
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        function showVideoGate(step) {
            cleanupVideoPlayer();
            currentVideoStepData = step;
            const vg = step.video_gate || {};

            videoProvider = (vg.video_provider || 'youtube').toLowerCase();
            isGateUnlocked = !!step.is_gate_unlocked || vg.gate_enabled === false;
            gateTimestampSeconds = parseFloat(vg.gate_timestamp_seconds !== undefined ? vg.gate_timestamp_seconds : 45);
            maxWatchedTime = 0;
            hasReachedGateMilestone = false;
            currentVideoDuration = 0;

            // Render Header & Badges
            document.getElementById('videoHeadline').textContent = vg.video_headline || step.title || 'Interactive Video Step';
            document.getElementById('videoGatePlatformLabel').textContent = videoProvider === 'vimeo' ? 'Vimeo Video Step' : 'YouTube Video Step';

            const gateBadge = document.getElementById('videoGateBadge');
            const gateBadgeText = document.getElementById('videoGateBadgeText');
            if (vg.gate_enabled !== false) {
                gateBadge.classList.remove('hidden');
                gateBadgeText.textContent = isGateUnlocked ? '✓ Gate Unlocked' : `🔒 Gate at ${formatTimeMmSs(gateTimestampSeconds)}`;
                if (isGateUnlocked) {
                    gateBadge.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20';
                } else {
                    gateBadge.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20';
                }
            } else {
                gateBadge.classList.add('hidden');
            }

            // Controls reset
            document.getElementById('currentTimeDisplay').textContent = '00:00';
            document.getElementById('durationDisplay').textContent = '00:00';
            document.getElementById('customProgressFill').style.width = '0%';
            document.getElementById('controlPlayIcon').textContent = '▶';
            document.getElementById('videoGateOverlay').classList.add('hidden');
            document.getElementById('tapToUnmuteBtn').classList.add('hidden');
            document.getElementById('videoEndCtaContainer').classList.add('hidden');
            const endAction = vg.end_action || (vg.on_video_end === 'message' || vg.on_video_end === 'thank_you' ? 'thank_you' : 'next_step');
            const ctaBtnText = document.getElementById('videoEndCtaBtnText');
            if (ctaBtnText) {
                ctaBtnText.textContent = vg.cta_button_text || (endAction === 'thank_you' ? '🎉 Complete & View Results ➔' : 'Continue to Next Step ➔');
            }

            // Back button visibility
            const backBtn = document.getElementById('videoBackBtn');
            if (backBtn) {
                if (step.can_go_back) {
                    backBtn.classList.remove('hidden');
                } else {
                    backBtn.classList.add('hidden');
                }
            }

            // Lock progress bar scrubbing if gate not yet unlocked
            updateProgressScrubState();

            // Fire milestone: video_started
            recordVideoEvent('video_started', { provider: videoProvider, video_id: vg.video_id });

            // Initialize respective SDK player
            if (videoProvider === 'vimeo') {
                initVimeoPlayer(vg);
            } else {
                initYouTubePlayer(vg);
            }
        }

        function updateProgressScrubState() {
            const track = document.getElementById('customProgressTrack');
            const marker = document.getElementById('gateLockMarker');
            if (isGateUnlocked) {
                track.className = 'relative w-full h-2.5 bg-slate-200 dark:bg-slate-800 rounded-full cursor-pointer overflow-visible';
                marker.textContent = '🔓';
                marker.className = 'absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-emerald-500 text-white border-2 border-white shadow-md flex items-center justify-center text-[8px] z-20 pointer-events-none';
            } else {
                track.className = 'relative w-full h-2.5 bg-slate-200 dark:bg-slate-800 rounded-full cursor-not-allowed overflow-visible';
                marker.textContent = '🔒';
                marker.className = 'absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-amber-400 border-2 border-white shadow-md flex items-center justify-center text-[8px] z-20 pointer-events-none';
            }
        }

        function initYouTubePlayer(vg) {
            loadYouTubeApi(() => {
                const target = document.getElementById('videoIframeTarget');
                target.innerHTML = '<div id="ytPlayerElement" class="w-full h-full"></div>';

                videoPlayerInstance = new YT.Player('ytPlayerElement', {
                    videoId: vg.video_id,
                    playerVars: {
                        controls: 0,
                        disablekb: 1,
                        rel: 0,
                        modestbranding: 1,
                        playsinline: 1,
                        fs: 0,
                        iv_load_policy: 3,
                        enablejsapi: 1,
                        origin: window.location.origin
                    },
                    events: {
                        onReady: (e) => {
                            try {
                                currentVideoDuration = e.target.getDuration() || 0;
                                document.getElementById('durationDisplay').textContent = formatTimeMmSs(currentVideoDuration);
                                updateLockMarkerPosition(currentVideoDuration);

                                if (vg.autoplay_behavior) {
                                    // Browser autoplay policy requires starting muted
                                    e.target.mute();
                                    isVideoMuted = true;
                                    document.getElementById('controlMuteIcon').textContent = '🔇';
                                    document.getElementById('tapToUnmuteBtn').classList.remove('hidden');
                                    e.target.playVideo();
                                }
                            } catch (err) {
                                console.warn('YT ready handler error:', err);
                            }
                        },
                        onStateChange: (e) => {
                            if (e.data === YT.PlayerState.PLAYING) {
                                isVideoPlaying = true;
                                document.getElementById('controlPlayIcon').textContent = '⏸';
                                startTimePolling();
                            } else if (e.data === YT.PlayerState.PAUSED) {
                                isVideoPlaying = false;
                                document.getElementById('controlPlayIcon').textContent = '▶';
                            } else if (e.data === YT.PlayerState.ENDED) {
                                isVideoPlaying = false;
                                document.getElementById('controlPlayIcon').textContent = '▶';
                                onVideoEnded();
                            }
                        }
                    }
                });
            });
        }

        function initVimeoPlayer(vg) {
            loadVimeoApi(() => {
                const target = document.getElementById('videoIframeTarget');
                target.innerHTML = '';

                videoPlayerInstance = new Vimeo.Player(target, {
                    id: vg.video_id,
                    controls: false,
                    keyboard: false,
                    playsinline: true,
                    title: false,
                    byline: false,
                    portrait: false,
                    dnt: true,
                    responsive: true,
                    autoplay: !!vg.autoplay_behavior
                });

                videoPlayerInstance.getDuration().then(dur => {
                    currentVideoDuration = dur;
                    document.getElementById('durationDisplay').textContent = formatTimeMmSs(dur);
                    updateLockMarkerPosition(dur);
                });

                if (vg.autoplay_behavior) {
                    videoPlayerInstance.setMuted(true).then(() => {
                        isVideoMuted = true;
                        document.getElementById('controlMuteIcon').textContent = '🔇';
                        document.getElementById('tapToUnmuteBtn').classList.remove('hidden');
                    });
                }

                videoPlayerInstance.on('play', () => {
                    isVideoPlaying = true;
                    document.getElementById('controlPlayIcon').textContent = '⏸';
                    startTimePolling();
                });

                videoPlayerInstance.on('pause', () => {
                    isVideoPlaying = false;
                    document.getElementById('controlPlayIcon').textContent = '▶';
                });

                videoPlayerInstance.on('ended', () => {
                    isVideoPlaying = false;
                    document.getElementById('controlPlayIcon').textContent = '▶';
                    onVideoEnded();
                });

                videoPlayerInstance.on('timeupdate', (data) => {
                    handleTimeUpdate(data.seconds);
                });
            });
        }

        function updateLockMarkerPosition(duration) {
            const marker = document.getElementById('gateLockMarker');
            if (duration > 0 && gateTimestampSeconds > 0) {
                const pct = Math.min(100, (gateTimestampSeconds / duration) * 100);
                marker.style.left = `${pct}%`;
                marker.title = `Gate triggers at ${formatTimeMmSs(gateTimestampSeconds)}`;
            } else {
                marker.style.left = '50%';
            }
        }

        function startTimePolling() {
            if (videoTimePollInterval) clearInterval(videoTimePollInterval);
            videoTimePollInterval = setInterval(async () => {
                if (!videoPlayerInstance || !isVideoPlaying) return;
                try {
                    let curTime = 0;
                    if (videoProvider === 'youtube' && typeof videoPlayerInstance.getCurrentTime === 'function') {
                        curTime = videoPlayerInstance.getCurrentTime() || 0;
                        handleTimeUpdate(curTime);
                    }
                } catch (e) {
                    console.warn('Time poll error:', e);
                }
            }, 200);
        }

        // Anti-Skip Guard Engine
        function handleTimeUpdate(currentTime) {
            if (isGateOverlayVisible) return;

            // Track duration if wasn't set yet
            if (currentVideoDuration <= 0 && currentTime > 0) {
                if (videoProvider === 'youtube' && typeof videoPlayerInstance.getDuration === 'function') {
                    currentVideoDuration = videoPlayerInstance.getDuration() || 0;
                }
                if (currentVideoDuration > 0) {
                    document.getElementById('durationDisplay').textContent = formatTimeMmSs(currentVideoDuration);
                    updateLockMarkerPosition(currentVideoDuration);
                }
            }

            // Anti-Skip Guard: visitor attempted to scrub ahead before gate is unlocked
            if (!isGateUnlocked) {
                if (currentTime > maxWatchedTime + 1.5) {
                    forceSeek(maxWatchedTime);
                    showFlashNotice('⚠️ Skipping ahead is locked until you complete this step.');
                    return;
                } else if (currentTime > maxWatchedTime) {
                    maxWatchedTime = currentTime;
                }

                // Hit Mid-Roll Turnstile Gate!
                if (currentTime >= gateTimestampSeconds) {
                    triggerGateTurnstile();
                    return;
                }
            }

            // Update UI Progress Bar & Timer
            document.getElementById('currentTimeDisplay').textContent = formatTimeMmSs(currentTime);
            if (currentVideoDuration > 0) {
                const pct = Math.min(100, (currentTime / currentVideoDuration) * 100);
                document.getElementById('customProgressFill').style.width = `${pct}%`;
            }
        }

        function triggerGateTurnstile() {
            if (isGateUnlocked || isGateOverlayVisible) return;
            isGateOverlayVisible = true;

            // Force pause and lock playback at gate timestamp
            pauseVideo();
            forceSeek(gateTimestampSeconds);
            document.getElementById('currentTimeDisplay').textContent = formatTimeMmSs(gateTimestampSeconds);

            if (!hasReachedGateMilestone) {
                hasReachedGateMilestone = true;
                recordVideoEvent('video_gate_reached', {
                    timestamp: gateTimestampSeconds,
                    provider: videoProvider,
                    video_id: currentVideoStepData?.video_gate?.video_id
                });
            }

            renderGateOverlayContent();
            document.getElementById('videoGateOverlay').classList.remove('hidden');
        }

        function renderGateOverlayContent() {
            const vg = currentVideoStepData?.video_gate || {};
            const gateType = vg.gate_type || 'lead_form';

            document.getElementById('overlayGateHeadline').textContent = vg.gate_headline || '🔒 Complete this quick step to continue watching';
            document.getElementById('overlayGateSubheadline').textContent = vg.gate_subheadline || 'Enter your details below to unlock the rest of this video.';

            const content = document.getElementById('overlayGateContent');
            const errBox = document.getElementById('overlayErrorNotice');
            errBox.classList.add('hidden');
            errBox.textContent = '';

            const skipContainer = document.getElementById('overlaySkipContainer');
            if (vg.allow_skip_gate) {
                skipContainer.classList.remove('hidden');
            } else {
                skipContainer.classList.add('hidden');
            }

            if (gateType === 'question') {
                const q = vg.question || { question_text: 'What best describes your situation?', options: [] };
                const opts = q.options || [];

                content.innerHTML = `
                    <div class="space-y-3.5">
                        ${q.question_text ? `
                            <p class="font-bold text-xs text-slate-300 mb-1">
                                ${escapeHtml(q.question_text)}
                            </p>
                        ` : ''}
                        <div class="space-y-2.5">
                            ${opts.map((opt, idx) => `
                                <button type="button" onclick="submitVideoQuestionOption(${opt.id}, '${escapeHtml(opt.label)}')"
                                        class="w-full p-3.5 rounded-2xl border border-slate-700/80 bg-slate-800/80 hover:border-[#5B5BD6] hover:bg-indigo-950/40 text-left text-xs font-semibold flex items-center justify-between transition-all group shadow-xs hover:scale-[1.01]">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-6 h-6 rounded-full bg-slate-700/70 text-slate-300 text-[10px] font-bold flex items-center justify-center group-hover:bg-[#5B5BD6] group-hover:text-white transition-colors">
                                            ${String.fromCharCode(65 + idx)}
                                        </span>
                                        <span class="text-white group-hover:text-indigo-300 font-medium">${escapeHtml(opt.label)}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400 font-bold group-hover:text-indigo-400 flex items-center gap-1">
                                        <span>Unlock</span>
                                        <span>▶</span>
                                    </span>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                `;
            } else {
                // Lead Form
                const fields = vg.form_fields || [
                    { field_key: 'name', label: 'Full Name', field_type: 'text', placeholder: 'Enter your name', is_required: 1 },
                    { field_key: 'email', label: 'Email Address', field_type: 'email', placeholder: 'Enter your email', is_required: 1 }
                ];

                content.innerHTML = `
                    <form id="videoGateLeadForm" onsubmit="handleVideoLeadSubmit(event)" class="space-y-3.5">
                        ${fields.map(f => {
                            if (f.field_type === 'consent') {
                                return `
                                    <label class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-800/60 border border-slate-700/80 cursor-pointer hover:border-slate-600 transition-colors">
                                        <input type="checkbox" name="${escapeHtml(f.field_key)}" value="1" ${f.is_required ? 'required' : ''} class="mt-0.5 rounded text-[#5B5BD6] bg-slate-900 border-slate-700 focus:ring-offset-slate-900">
                                        <span class="text-xs text-slate-300 leading-snug">${escapeHtml(f.label || 'I agree to the terms and privacy policy')}</span>
                                    </label>
                                `;
                            }

                            let iconSvg = `<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`;
                            if (f.field_type === 'email' || f.field_key === 'email') {
                                iconSvg = `<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>`;
                            } else if (f.field_type === 'phone' || f.field_key === 'phone') {
                                iconSvg = `<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>`;
                            } else if (f.field_key === 'name') {
                                iconSvg = `<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`;
                            } else if (f.field_key === 'company') {
                                iconSvg = `<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>`;
                            }

                            return `
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                        ${escapeHtml(f.label || f.field_key)} ${f.is_required ? '<span class="text-rose-400">*</span>' : ''}
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            ${iconSvg}
                                        </div>
                                        <input type="${f.field_type === 'email' ? 'email' : (f.field_type === 'phone' ? 'tel' : 'text')}"
                                               name="${escapeHtml(f.field_key)}"
                                               placeholder="${escapeHtml(f.placeholder || '')}"
                                               ${f.is_required ? 'required' : ''}
                                               class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-700/80 bg-slate-800/80 text-xs font-medium text-white placeholder-slate-500 hover:border-slate-600 focus:border-[#5B5BD6] focus:ring-2 focus:ring-[#5B5BD6]/30 focus:outline-none transition-all">
                                    </div>
                                </div>
                            `;
                        }).join('')}

                        <button type="submit" id="videoGateSubmitBtn"
                                class="w-full py-3.5 rounded-xl btn-brand text-white text-xs font-bold shadow-lg shadow-indigo-600/30 hover:shadow-indigo-600/50 flex items-center justify-center gap-2 transition-all mt-2">
                            <span>${escapeHtml(vg.gate_button_text || '▶️ Unlock & Continue Video')}</span>
                        </button>
                    </form>
                `;
            }
        }

        function isDarkTheme() {
            return document.body.classList.contains('bg-[#0D0F14]');
        }

        async function handleVideoLeadSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('videoGateSubmitBtn');
            const errBox = document.getElementById('overlayErrorNotice');
            btn.disabled = true;
            btn.textContent = 'Verifying & Unlocking...';
            errBox.classList.add('hidden');

            const form = document.getElementById('videoGateLeadForm');
            const formData = new FormData(form);
            const payload = {};
            formData.forEach((val, key) => { payload[key] = val; });

            try {
                const resp = await fetch(`${API_BASE}/api/quiz/video-gate-submit`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Quiz-Session': sessionToken
                    },
                    body: JSON.stringify({
                        node_id: currentVideoStepData.node_id,
                        form_data: payload
                    })
                });
                const res = await resp.json();
                if (res.success) {
                    unlockGateAndResume();
                } else {
                    errBox.textContent = res.error || 'Please fill in all required fields.';
                    errBox.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = currentVideoStepData?.video_gate?.gate_button_text || '▶️ Unlock & Continue Video';
                }
            } catch (err) {
                errBox.textContent = 'Network error. Please try again.';
                errBox.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = currentVideoStepData?.video_gate?.gate_button_text || '▶️ Unlock & Continue Video';
            }
        }

        async function submitVideoQuestionOption(optionId, optionLabel) {
            const errBox = document.getElementById('overlayErrorNotice');
            errBox.classList.add('hidden');

            try {
                const resp = await fetch(`${API_BASE}/api/quiz/video-gate-submit`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Quiz-Session': sessionToken
                    },
                    body: JSON.stringify({
                        node_id: currentVideoStepData.node_id,
                        option_id: optionId,
                        option_label: optionLabel
                    })
                });
                const res = await resp.json();
                if (res.success) {
                    unlockGateAndResume();
                } else {
                    errBox.textContent = res.error || 'Failed to record choice.';
                    errBox.classList.remove('hidden');
                }
            } catch (err) {
                errBox.textContent = 'Connection error. Please try again.';
                errBox.classList.remove('hidden');
            }
        }

        function skipVideoGate() {
            recordVideoEvent('video_gate_skipped', { timestamp: gateTimestampSeconds });
            unlockGateAndResume();
        }

        function unlockGateAndResume() {
            isGateUnlocked = true;
            isGateOverlayVisible = false;

            // Update lock marker & progress bar
            updateProgressScrubState();

            // Update badge in step header
            const gateBadge = document.getElementById('videoGateBadge');
            const gateBadgeText = document.getElementById('videoGateBadgeText');
            gateBadge.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20';
            gateBadgeText.textContent = '✓ Gate Unlocked';

            // Fade out overlay
            const overlay = document.getElementById('videoGateOverlay');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                overlay.classList.add('hidden');
                overlay.classList.remove('opacity-0');
            }, 250);

            // Resume playback from gate timestamp
            playVideo();
        }

        function handleVideoShieldClick() {
            if (isGateOverlayVisible) return;
            togglePlayPause();
            flashPlayIndicator(isVideoPlaying ? '▶' : '⏸');
        }

        function togglePlayPause() {
            if (isGateOverlayVisible) return;
            if (isVideoPlaying) {
                pauseVideo();
            } else {
                playVideo();
            }
        }

        function playVideo() {
            if (!videoPlayerInstance) return;
            try {
                if (videoProvider === 'youtube' && typeof videoPlayerInstance.playVideo === 'function') {
                    videoPlayerInstance.playVideo();
                } else if (videoProvider === 'vimeo' && typeof videoPlayerInstance.play === 'function') {
                    videoPlayerInstance.play();
                }
            } catch (e) {
                console.warn('Play error:', e);
            }
        }

        function pauseVideo() {
            if (!videoPlayerInstance) return;
            try {
                if (videoProvider === 'youtube' && typeof videoPlayerInstance.pauseVideo === 'function') {
                    videoPlayerInstance.pauseVideo();
                } else if (videoProvider === 'vimeo' && typeof videoPlayerInstance.pause === 'function') {
                    videoPlayerInstance.pause();
                }
            } catch (e) {
                console.warn('Pause error:', e);
            }
        }

        function forceSeek(seconds) {
            if (!videoPlayerInstance) return;
            try {
                if (videoProvider === 'youtube' && typeof videoPlayerInstance.seekTo === 'function') {
                    videoPlayerInstance.seekTo(seconds, true);
                } else if (videoProvider === 'vimeo' && typeof videoPlayerInstance.setCurrentTime === 'function') {
                    videoPlayerInstance.setCurrentTime(seconds);
                }
            } catch (e) {
                console.warn('Seek error:', e);
            }
        }

        function toggleMute() {
            if (!videoPlayerInstance) return;
            try {
                if (videoProvider === 'youtube') {
                    if (videoPlayerInstance.isMuted()) {
                        videoPlayerInstance.unMute();
                        isVideoMuted = false;
                        document.getElementById('controlMuteIcon').textContent = '🔊';
                    } else {
                        videoPlayerInstance.mute();
                        isVideoMuted = true;
                        document.getElementById('controlMuteIcon').textContent = '🔇';
                    }
                } else if (videoProvider === 'vimeo') {
                    videoPlayerInstance.getMuted().then(muted => {
                        const next = !muted;
                        videoPlayerInstance.setMuted(next).then(() => {
                            isVideoMuted = next;
                            document.getElementById('controlMuteIcon').textContent = next ? '🔇' : '🔊';
                        });
                    });
                }
            } catch (e) {
                console.warn('Mute error:', e);
            }
        }

        function unmuteVideoPlayer(e) {
            e.stopPropagation();
            if (!videoPlayerInstance) return;
            try {
                if (videoProvider === 'youtube') {
                    videoPlayerInstance.unMute();
                    videoPlayerInstance.setVolume(100);
                } else if (videoProvider === 'vimeo') {
                    videoPlayerInstance.setMuted(false);
                    videoPlayerInstance.setVolume(1);
                }
                isVideoMuted = false;
                document.getElementById('controlMuteIcon').textContent = '🔊';
                document.getElementById('tapToUnmuteBtn').classList.add('hidden');
            } catch (err) {
                console.warn('Unmute error:', err);
            }
        }

        function handleProgressBarSeek(e) {
            if (!isGateUnlocked) {
                showFlashNotice('🔒 Progress scrubbing is locked until the gate is unlocked.');
                return;
            }
            if (currentVideoDuration <= 0) return;

            const track = document.getElementById('customProgressTrack');
            const rect = track.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const pct = Math.max(0, Math.min(1, clickX / rect.width));
            const targetSec = pct * currentVideoDuration;
            forceSeek(targetSec);
        }

        function flashPlayIndicator(icon) {
            const el = document.getElementById('playStateIndicator');
            const iconEl = document.getElementById('playStateIcon');
            iconEl.textContent = icon;
            el.classList.remove('opacity-0');
            setTimeout(() => {
                el.classList.add('opacity-0');
            }, 250);
        }

        function showFlashNotice(text) {
            const ind = document.getElementById('playStateIndicator');
            const iconEl = document.getElementById('playStateIcon');
            iconEl.textContent = '🔒';
            ind.classList.remove('opacity-0');
            setTimeout(() => {
                ind.classList.add('opacity-0');
            }, 600);
        }

        function onVideoEnded() {
            recordVideoEvent('video_completed', {
                duration: currentVideoDuration,
                provider: videoProvider
            });

            const vg = currentVideoStepData?.video_gate || {};
            const onEnd = vg.on_video_end || 'auto_advance';

            if (onEnd === 'auto_advance') {
                handleVideoStepComplete();
            } else {
                document.getElementById('videoEndCtaContainer').classList.remove('hidden');
            }
        }

        async function handleVideoStepComplete() {
            cleanupVideoPlayer();
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('videoGateSection').classList.add('hidden');

            try {
                const resp = await fetch(`${API_BASE}/api/quiz/video-advance`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Quiz-Session': sessionToken
                    },
                    body: JSON.stringify({
                        node_id: currentVideoStepData.node_id
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    renderStep(data.data);
                } else {
                    showError(data.error || 'Failed to advance to next step.');
                }
            } catch (e) {
                showError('Connection error advancing video step.');
            }
        }

        async function recordVideoEvent(eventType, eventData = {}) {
            if (!sessionToken || !currentVideoStepData) return;
            try {
                await fetch(`${API_BASE}/api/quiz/video-event`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Quiz-Session': sessionToken
                    },
                    body: JSON.stringify({
                        node_id: currentVideoStepData.node_id,
                        event_type: eventType,
                        event_data: eventData
                    })
                });
            } catch (e) {
                console.warn('Failed to record video event:', e);
            }
        }

    </script>
</body>
</html>
