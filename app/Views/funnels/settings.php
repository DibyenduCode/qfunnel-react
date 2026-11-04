<div class="space-y-6">

    <!-- Top Breadcrumb & Actions Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="<?= url('/admin/funnels') ?>" class="text-xs text-[#687080] hover:text-[#171A21]">Funnels</a>
                <span class="text-xs text-[#687080]">/</span>
                <span class="text-xs font-semibold text-[#171A21]"><?= e($funnel['name']) ?></span>
            </div>
            <h2 class="text-lg font-bold text-[#171A21]">Funnel Settings & Integrations</h2>
            <p class="text-xs text-[#687080]">Configure general parameters, visitor navigation, custom branding, and website embedding.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($funnel['status'] === 'published'): ?>
                <a href="<?= url("/quiz/{$funnel['slug']}") ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-[#171A21] text-xs font-semibold hover:bg-[#F7F8FA] transition-colors">
                    <svg class="w-3.5 h-3.5 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    View Live Quiz
                </a>
            <?php endif; ?>
            <a href="<?= url("/admin/funnels/{$funnel['id']}/builder") ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Open Visual Builder →
            </a>
        </div>
    </div>

    <!-- Category Tabs Navigation (Modern Sleek Pills) -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-1.5 shadow-2xs flex flex-wrap items-center gap-1.5 text-xs font-semibold">
        <button type="button" onclick="switchSettingsSection('general')" id="secTab-general"
                class="settings-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg bg-[#5B5BD6] text-white transition-all shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            General & Info
        </button>

        <button type="button" onclick="switchSettingsSection('branding')" id="secTab-branding"
                class="settings-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[#687080] hover:text-[#171A21] hover:bg-slate-50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            Branding & Logo
        </button>

        <button type="button" onclick="switchSettingsSection('navigation')" id="secTab-navigation"
                class="settings-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[#687080] hover:text-[#171A21] hover:bg-slate-50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            Visitor Experience
        </button>

        <button type="button" onclick="switchSettingsSection('embed')" id="secTab-embed"
                class="settings-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[#687080] hover:text-[#171A21] hover:bg-slate-50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            Embed on Website
        </button>

        <button type="button" onclick="switchSettingsSection('advanced')" id="secTab-advanced"
                class="settings-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[#687080] hover:text-[#171A21] hover:bg-slate-50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Actions & Delete
        </button>
    </div>

    <!-- Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Settings Content (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            <form action="<?= url("/admin/funnels/{$funnel['id']}/settings") ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                <?= csrf_field() ?>

                <!-- SECTION 1: General Information -->
                <div id="section-general" class="settings-section bg-white border border-[#E4E7EC] rounded-xl p-6 shadow-sm space-y-5">
                    <div class="border-b border-[#E4E7EC] pb-3">
                        <h3 class="text-sm font-bold text-[#171A21]">General Configuration</h3>
                        <p class="text-xs text-[#687080]">Basic identifiers, titles, and public descriptions for this quiz funnel.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Funnel Engine & Mode</label>
                        <select name="funnel_mode" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs font-semibold text-[#171A21] bg-white focus:ring-2 focus:ring-[#5B5BD6] focus:border-[#5B5BD6]">
                            <option value="quiz" <?= ($funnel['funnel_mode'] ?? 'quiz') === 'quiz' ? 'selected' : '' ?>>🎯 Lead & Quiz Funnel (Scoring & Branching)</option>
                            <option value="calculator" <?= ($funnel['funnel_mode'] ?? 'quiz') === 'calculator' ? 'selected' : '' ?>>🧮 Calculator & Quote Funnel (Math Formulas & Price Estimates)</option>
                        </select>
                        <p class="text-[11px] text-[#687080] mt-1">Switching modes adapts builder Inspector tools and formula capabilities without losing any existing nodes.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Funnel Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="<?= e($funnel['name']) ?>" required
                               class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6] focus:border-[#5B5BD6]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Description</label>
                        <textarea name="description" rows="3" placeholder="Briefly describe what this quiz is about..."
                                  class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6] focus:border-[#5B5BD6]"><?= e($funnel['description']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Privacy Policy URL</label>
                        <input type="url" name="privacy_policy_url" value="<?= e($settings['privacy_policy_url'] ?? '') ?>" placeholder="https://yourdomain.com/privacy"
                               class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6] focus:border-[#5B5BD6]">
                        <p class="text-[11px] text-[#687080] mt-1">Displayed in the footer of your quiz and consent prompts.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E4E7EC]">
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm transition-colors">
                            Save Changes
                        </button>
                    </div>
                </div>

                <!-- SECTION 2: Branding & White-Labeling -->
                <div id="section-branding" class="settings-section bg-white border border-[#E4E7EC] rounded-xl p-6 shadow-sm space-y-5 hidden">
                    <div class="border-b border-[#E4E7EC] pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-[#171A21]">Branding & White-Labeling</h3>
                            <p class="text-xs text-[#687080]">Customize the logo, colors, and layout of the visitor experience.</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#5B5BD6]/10 text-[#5B5BD6] uppercase tracking-wide">Agency Grade</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Company / Brand Name</label>
                            <input type="text" name="company_name" value="<?= e($settings['company_name'] ?? '') ?>" placeholder="e.g. Apex Health Clinic"
                                   class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                            <p class="text-[11px] text-[#687080] mt-1">Displayed in the header, metadata and trust badges.</p>
                        </div>

                        <!-- Brand Logo Picker -->
                        <div class="md:col-span-2 p-4 rounded-xl border border-[#E4E7EC] bg-[#F8FAFC] space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-[#171A21] uppercase tracking-wide">Brand Logo <span class="text-[#687080] font-normal lowercase">(optional)</span></label>
                                <p class="text-[11px] text-[#687080]">Choose one of two ways to set your logo: upload a file OR paste an existing image link.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-11 gap-3 items-center">
                                <!-- Option A: Upload -->
                                <div class="md:col-span-5 bg-white p-3 rounded-lg border border-[#E4E7EC]">
                                    <label class="block text-[11px] font-semibold text-[#171A21] mb-1.5">Method 1: Upload from Device</label>
                                    <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif"
                                           class="w-full text-xs text-[#171A21] file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#5B5BD6]/10 file:text-[#5B5BD6] hover:file:bg-[#5B5BD6]/20 cursor-pointer">
                                    <span class="text-[10px] text-[#687080] block mt-1">PNG, JPG, SVG, WebP (Max 3MB)</span>
                                </div>

                                <!-- OR Divider -->
                                <div class="md:col-span-1 text-center font-bold text-xs text-[#687080] uppercase">
                                    — OR —
                                </div>

                                <!-- Option B: URL -->
                                <div class="md:col-span-5 bg-white p-3 rounded-lg border border-[#E4E7EC]">
                                    <label class="block text-[11px] font-semibold text-[#171A21] mb-1.5">Method 2: Paste Hosted Image URL</label>
                                    <input type="url" name="logo_url" id="logoUrlInput" value="<?= e($settings['logo_url'] ?? '') ?>" placeholder="https://yourwebsite.com/logo.png"
                                           class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                                    <span class="text-[10px] text-[#687080] block mt-1">Direct link to an image on your website or CDN</span>
                                </div>
                            </div>

                            <?php if (!empty($settings['logo_url'])): ?>
                                <div class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-[#E4E7EC]">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[11px] font-semibold text-[#687080]">Current Active Logo:</span>
                                        <img src="<?= e($settings['logo_url']) ?>" alt="Current Logo" class="h-7 max-w-[140px] object-contain">
                                    </div>
                                    <label class="flex items-center gap-1.5 text-xs text-rose-600 font-semibold cursor-pointer">
                                        <input type="checkbox" name="remove_logo" value="1" class="rounded text-rose-600">
                                        Remove Logo
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Logo Alignment / Position</label>
                            <div class="grid grid-cols-3 gap-2">
                                <?php $logoPos = $settings['logo_position'] ?? 'left'; ?>
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border cursor-pointer text-xs font-semibold <?= $logoPos === 'left' ? 'border-[#5B5BD6] bg-[#5B5BD6]/5 text-[#5B5BD6]' : 'border-[#E4E7EC] text-[#171A21] hover:bg-[#F7F8FA]' ?>">
                                    <input type="radio" name="logo_position" value="left" <?= $logoPos === 'left' ? 'checked' : '' ?> class="sr-only">
                                    <span>⬅ Left</span>
                                </label>
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border cursor-pointer text-xs font-semibold <?= $logoPos === 'center' ? 'border-[#5B5BD6] bg-[#5B5BD6]/5 text-[#5B5BD6]' : 'border-[#E4E7EC] text-[#171A21] hover:bg-[#F7F8FA]' ?>">
                                    <input type="radio" name="logo_position" value="center" <?= $logoPos === 'center' ? 'checked' : '' ?> class="sr-only">
                                    <span>↔ Center</span>
                                </label>
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border cursor-pointer text-xs font-semibold <?= $logoPos === 'right' ? 'border-[#5B5BD6] bg-[#5B5BD6]/5 text-[#5B5BD6]' : 'border-[#E4E7EC] text-[#171A21] hover:bg-[#F7F8FA]' ?>">
                                    <input type="radio" name="logo_position" value="right" <?= $logoPos === 'right' ? 'checked' : '' ?> class="sr-only">
                                    <span>Right ➡</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Logo Height (Pixels)</label>
                            <div class="flex items-center gap-3">
                                <input type="range" name="logo_height" min="24" max="80" step="2" value="<?= (int)($settings['logo_height'] ?? 36) ?>"
                                       oninput="document.getElementById('logoHeightVal').textContent = this.value + 'px'"
                                       class="w-full accent-[#5B5BD6] cursor-pointer">
                                <span id="logoHeightVal" class="text-xs font-mono font-bold text-[#171A21] w-12"><?= (int)($settings['logo_height'] ?? 36) ?>px</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Accent Brand Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="accent_color" id="accentColorPicker" value="<?= e($settings['accent_color'] ?? '#5B5BD6') ?>"
                                       oninput="document.getElementById('accentColorHex').value = this.value"
                                       class="h-9 w-12 rounded border border-[#E4E7EC] p-0.5 cursor-pointer bg-white">
                                <input type="text" id="accentColorHex" value="<?= e($settings['accent_color'] ?? '#5B5BD6') ?>"
                                       oninput="document.getElementById('accentColorPicker').value = this.value"
                                       class="w-28 px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs font-mono text-[#171A21]">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Card Corner Radius</label>
                            <select name="card_radius" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white focus:ring-2 focus:ring-[#5B5BD6]">
                                <?php $cardRad = $settings['card_radius'] ?? 'rounded-2xl'; ?>
                                <option value="rounded-none" <?= $cardRad === 'rounded-none' ? 'selected' : '' ?>>Square (Sharp Corners)</option>
                                <option value="rounded-lg" <?= $cardRad === 'rounded-lg' ? 'selected' : '' ?>>Subtle Rounded (8px)</option>
                                <option value="rounded-xl" <?= $cardRad === 'rounded-xl' ? 'selected' : '' ?>>Medium Rounded (12px)</option>
                                <option value="rounded-2xl" <?= $cardRad === 'rounded-2xl' ? 'selected' : '' ?>>Modern Curvature (16px - Recommended)</option>
                                <option value="rounded-3xl" <?= $cardRad === 'rounded-3xl' ? 'selected' : '' ?>>Pill Soft (24px)</option>
                            </select>
                        </div>
                    </div>

                    <?php $hasWhiteLabel = !empty($planFeatures['white_label']) || !empty($isPlatformAdmin); ?>
                    <div class="pt-3 border-t border-[#E4E7EC] space-y-4">
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-[#E4E7EC] <?= $hasWhiteLabel ? 'hover:bg-[#F8FAFC] cursor-pointer' : 'bg-slate-50 opacity-90' ?> transition-colors">
                            <input type="checkbox" name="white_label" value="1" <?= (!empty($settings['white_label']) && $hasWhiteLabel) ? 'checked' : '' ?> <?= $hasWhiteLabel ? '' : 'disabled' ?> class="mt-0.5 rounded text-[#5B5BD6] focus:ring-[#5B5BD6]">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-[#171A21] block">Enable Complete White-Labeling</span>
                                    <?php if ($hasWhiteLabel): ?>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">Unlocked</span>
                                    <?php else: ?>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200 uppercase">★ Pro Plan</span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-[11px] text-[#687080] block mt-0.5">Removes all "Powered by QFunnel" badges and platform branding from the public quiz runner.</span>
                                <?php if (!$hasWhiteLabel): ?>
                                    <span class="text-[10px] text-purple-700 font-semibold block mt-1">Upgrade your subscription plan to Pro to unlock 100% white-labeling.</span>
                                <?php endif; ?>
                            </div>
                        </label>

                        <div>
                            <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Custom Social Proof / Trust Badge</label>
                            <input type="text" name="trust_badge_text" value="<?= e($settings['trust_badge_text'] ?? '🔒 256-Bit SSL Encrypted • 100% Privacy Guaranteed') ?>"
                                   class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Custom Footer Copyright / Notice</label>
                                <input type="text" name="footer_text" value="<?= e($settings['footer_text'] ?? '') ?>" placeholder="e.g. © 2026 Apex Health Clinic. All rights reserved."
                                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Terms of Service URL</label>
                                <input type="url" name="terms_url" value="<?= e($settings['terms_url'] ?? '') ?>" placeholder="https://example.com/terms"
                                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E4E7EC]">
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm transition-colors">
                            Save Branding Changes
                        </button>
                    </div>
                </div>

                <!-- SECTION 3: Visitor Experience & Navigation -->
                <div id="section-navigation" class="settings-section bg-white border border-[#E4E7EC] rounded-xl p-6 shadow-sm space-y-5 hidden">
                    <div class="border-b border-[#E4E7EC] pb-3">
                        <h3 class="text-sm font-bold text-[#171A21]">Visitor Experience & Navigation</h3>
                        <p class="text-xs text-[#687080]">Control how respondents move through questions and final redirects.</p>
                    </div>

                    <div class="space-y-3">
                        <label class="flex items-start gap-3 cursor-pointer p-3.5 rounded-xl border border-[#E4E7EC] hover:bg-[#F8FAFC] transition-colors">
                            <input type="checkbox" name="enable_back_button" value="1" <?= !empty($settings['enable_back_button']) ? 'checked' : '' ?> class="mt-0.5 rounded text-[#5B5BD6] focus:ring-[#5B5BD6]">
                            <div>
                                <span class="text-xs font-semibold text-[#171A21] block">Enable Back Navigation</span>
                                <span class="text-[11px] text-[#687080]">Allows respondents to go back to previous steps and adjust their answers.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer p-3.5 rounded-xl border border-[#E4E7EC] hover:bg-[#F8FAFC] transition-colors">
                            <input type="checkbox" name="show_progress_bar" value="1" <?= !empty($settings['show_progress_bar']) ? 'checked' : '' ?> class="mt-0.5 rounded text-[#5B5BD6] focus:ring-[#5B5BD6]">
                            <div>
                                <span class="text-xs font-semibold text-[#171A21] block">Show Dynamic Progress Bar</span>
                                <span class="text-[11px] text-[#687080]">Displays completion percentage at the top of the quiz.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer p-3.5 rounded-xl border border-[#E4E7EC] hover:bg-[#F8FAFC] transition-colors">
                            <input type="checkbox" name="auto_redirect" value="1" <?= !empty($settings['auto_redirect']) ? 'checked' : '' ?> class="mt-0.5 rounded text-[#5B5BD6] focus:ring-[#5B5BD6]">
                            <div>
                                <span class="text-xs font-semibold text-[#171A21] block">Auto-Redirect on Outcome Reached</span>
                                <span class="text-[11px] text-[#687080]">Navigates visitor automatically to the outcome link after completion.</span>
                            </div>
                        </label>
                    </div>

                    <div class="pt-2">
                        <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Redirect Delay (Seconds)</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="redirect_delay_seconds" min="0" max="15" value="<?= (int)($settings['redirect_delay_seconds'] ?? 3) ?>"
                                   class="w-32 px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                            <span class="text-xs text-[#687080]">seconds before redirecting</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E4E7EC]">
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm transition-colors">
                            Save Navigation Changes
                        </button>
                    </div>
                </div>

            </form>

            <!-- SECTION 4: Embed on Website -->
            <div id="section-embed" class="settings-section bg-white border border-[#E4E7EC] rounded-xl p-6 shadow-sm space-y-5 hidden">
                <div class="border-b border-[#E4E7EC] pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-[#171A21]">Embed on Any Website</h3>
                        <p class="text-xs text-[#687080]">Seamlessly embed this interactive quiz onto WordPress, Shopify, Webflow, or custom HTML.</p>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Universal Widget</span>
                </div>

                <?php
                $embedHost = rtrim(full_url(''), '/');
                $embedScript = full_url('/assets/js/embed.js');
                ?>

                <!-- Tabs: Inline, Popup, Floating Widget -->
                <div class="space-y-4">
                    <div class="flex items-center border-b border-[#E4E7EC] text-xs font-semibold gap-4">
                        <button type="button" onclick="switchEmbedTab('inline')" id="tabBtnInline" class="pb-2 border-b-2 border-[#5B5BD6] text-[#5B5BD6]">Inline Embed</button>
                        <button type="button" onclick="switchEmbedTab('popup')" id="tabBtnPopup" class="pb-2 border-b-2 border-transparent text-[#687080] hover:text-[#171A21]">Popup Lightbox</button>
                        <button type="button" onclick="switchEmbedTab('float')" id="tabBtnFloat" class="pb-2 border-b-2 border-transparent text-[#687080] hover:text-[#171A21]">Floating Corner Button</button>
                    </div>

                    <!-- Code Snippets -->
                    <div id="embedContentInline" class="space-y-2">
                        <p class="text-[11px] text-[#687080]">Embeds the quiz directly inside your page body:</p>
                        <div class="relative">
                            <pre class="p-3.5 rounded-lg bg-slate-900 text-slate-100 text-[11px] font-mono overflow-x-auto whitespace-pre leading-relaxed">&lt;div data-qfunnel-embed="<?= e($funnel['slug']) ?>" data-qfunnel-host="<?= e($embedHost) ?>" data-qfunnel-mode="inline" data-qfunnel-height="720px"&gt;&lt;/div&gt;
&lt;script src="<?= e($embedScript) ?>" async&gt;&lt;/script&gt;</pre>
                            <button type="button" onclick="copySnippet('snippetInline', this)" id="snippetInline" data-code='<div data-qfunnel-embed="<?= e($funnel['slug']) ?>" data-qfunnel-host="<?= e($embedHost) ?>" data-qfunnel-mode="inline" data-qfunnel-height="720px"></div>&#10;<script src="<?= e($embedScript) ?>" async></script>' class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold border border-slate-700">Copy Code</button>
                        </div>
                    </div>

                    <div id="embedContentPopup" class="space-y-2 hidden">
                        <p class="text-[11px] text-[#687080]">Opens quiz in a lightbox modal when visitors click your custom button:</p>
                        <div class="relative">
                            <pre class="p-3.5 rounded-lg bg-slate-900 text-slate-100 text-[11px] font-mono overflow-x-auto whitespace-pre leading-relaxed">&lt;button data-qfunnel-embed="<?= e($funnel['slug']) ?>" data-qfunnel-host="<?= e($embedHost) ?>" data-qfunnel-mode="popup"&gt;
  Take Our 60-Sec Quiz
&lt;/button&gt;
&lt;script src="<?= e($embedScript) ?>" async&gt;&lt;/script&gt;</pre>
                            <button type="button" onclick="copySnippet('snippetPopup', this)" id="snippetPopup" data-code='<button data-qfunnel-embed="<?= e($funnel['slug']) ?>" data-qfunnel-host="<?= e($embedHost) ?>" data-qfunnel-mode="popup">&#10;  Take Our 60-Sec Quiz&#10;</button>&#10;<script src="<?= e($embedScript) ?>" async></script>' class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold border border-slate-700">Copy Code</button>
                        </div>
                    </div>

                    <div id="embedContentFloat" class="space-y-2 hidden">
                        <p class="text-[11px] text-[#687080]">Displays a floating bubble widget in the bottom corner of your website:</p>
                        <div class="relative">
                            <pre class="p-3.5 rounded-lg bg-slate-900 text-slate-100 text-[11px] font-mono overflow-x-auto whitespace-pre leading-relaxed">&lt;div data-qfunnel-embed="<?= e($funnel['slug']) ?>" data-qfunnel-host="<?= e($embedHost) ?>" data-qfunnel-mode="floating"&gt;&lt;/div&gt;
&lt;script src="<?= e($embedScript) ?>" async&gt;&lt;/script&gt;</pre>
                            <button type="button" onclick="copySnippet('snippetFloat', this)" id="snippetFloat" data-code='<div data-qfunnel-embed="<?= e($funnel['slug']) ?>" data-qfunnel-host="<?= e($embedHost) ?>" data-qfunnel-mode="floating"></div>&#10;<script src="<?= e($embedScript) ?>" async></script>' class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold border border-slate-700">Copy Code</button>
                        </div>
                    </div>
                </div>

                <!-- CMS Guides -->
                <div class="pt-4 border-t border-[#E4E7EC] space-y-3">
                    <h4 class="text-xs font-bold text-[#171A21] uppercase tracking-wide">Step-by-Step Platform Guides</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-[#F8FAFC] border border-[#E4E7EC] space-y-1">
                            <strong class="text-xs font-bold text-[#171A21] block">WordPress</strong>
                            <p class="text-[11px] text-[#687080]">Add a <strong>Custom HTML block</strong> in Gutenberg or Elementor, paste the snippet, and publish.</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F8FAFC] border border-[#E4E7EC] space-y-1">
                            <strong class="text-xs font-bold text-[#171A21] block">Shopify</strong>
                            <p class="text-[11px] text-[#687080]">Add a <strong>Custom Liquid</strong> section in the Theme Editor, paste the code, and click Save.</p>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F8FAFC] border border-[#E4E7EC] space-y-1">
                            <strong class="text-xs font-bold text-[#171A21] block">Webflow</strong>
                            <p class="text-[11px] text-[#687080]">Drag an <strong>Embed (HTML)</strong> element onto the page canvas, paste snippet, and publish.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: Actions & Danger Zone -->
            <div id="section-advanced" class="settings-section bg-white border border-[#E4E7EC] rounded-xl p-6 shadow-sm space-y-5 hidden">
                <div class="border-b border-[#E4E7EC] pb-3">
                    <h3 class="text-sm font-bold text-[#171A21]">Funnel Actions & Management</h3>
                    <p class="text-xs text-[#687080]">Duplicate this entire flow or permanently remove it.</p>
                </div>

                <div class="space-y-4">
                    <div class="p-4 rounded-xl border border-[#E4E7EC] bg-[#F8FAFC] flex items-center justify-between">
                        <div>
                            <strong class="text-xs font-bold text-[#171A21] block">Duplicate Funnel</strong>
                            <p class="text-[11px] text-[#687080]">Clones all nodes, questions, branching edges, and settings into a new draft.</p>
                        </div>
                        <form method="POST" action="<?= url("/admin/funnels/{$funnel['id']}/duplicate") ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="px-4 py-2 rounded-lg border border-[#E4E7EC] bg-white hover:bg-slate-50 text-xs font-semibold text-[#171A21] shadow-2xs">
                                Duplicate Funnel
                            </button>
                        </form>
                    </div>

                    <div class="p-4 rounded-xl border border-rose-200 bg-rose-50/50 flex items-center justify-between">
                        <div>
                            <strong class="text-xs font-bold text-rose-700 block">Delete Funnel</strong>
                            <p class="text-[11px] text-rose-600">Permanently deletes this funnel and all associated question nodes.</p>
                        </div>
                        <form method="POST" action="<?= url("/admin/funnels/{$funnel['id']}/delete") ?>" onsubmit="return confirm('Are you sure you want to delete this funnel? This action cannot be undone.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs">
                                Delete Funnel
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Quick Status & Live Link (4 cols) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Public Access & Live Link Card -->
            <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm space-y-4">
                <div class="border-b border-[#E4E7EC] pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-[#171A21]">Publication Status</h3>
                        <p class="text-xs text-[#687080]">Public access for respondents.</p>
                    </div>
                    <?php if ($funnel['status'] === 'published' && empty($funnel['has_unpublished_changes'])): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ● Published
                        </span>
                    <?php elseif ($funnel['status'] === 'published' && !empty($funnel['has_unpublished_changes'])): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-300" title="Changes saved in draft — Click Publish in Builder to update live quiz">
                            ● Not Published (Draft Changes)
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            ● Draft Mode
                        </span>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#687080] uppercase mb-1">Public Quiz URL</label>
                    <div class="flex items-center gap-1.5">
                        <input type="text" readonly value="<?= url("/quiz/{$funnel['slug']}") ?>" id="publicQuizUrl"
                               class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-[#F7F8FA] text-xs font-mono text-[#171A21]">
                        <button type="button" onclick="copyQuizUrl()" title="Copy Link"
                                class="px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] hover:bg-slate-50 text-[#171A21] text-xs font-medium shrink-0 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Copy
                        </button>
                    </div>
                    <span id="copyToast" class="text-[11px] text-emerald-600 font-medium hidden mt-1 block">Link copied to clipboard!</span>
                </div>

                <?php if ($funnel['status'] === 'published'): ?>
                    <a href="<?= url("/quiz/{$funnel['slug']}") ?>" target="_blank"
                       class="w-full inline-flex items-center justify-center gap-1.5 py-2 rounded-lg border border-[#E4E7EC] text-xs font-semibold text-[#171A21] hover:bg-[#F7F8FA] transition-colors">
                        Launch Live Quiz in New Tab ↗
                    </a>
                <?php else: ?>
                    <p class="text-[11px] text-[#687080]">Publish this funnel inside the Visual Builder to generate version 1 and start collecting submissions.</p>
                <?php endif; ?>
            </div>

            <!-- Quick Jump Help Box -->
            <div class="bg-gradient-to-br from-[#F5F5FE] to-[#EEF0FD] border border-[#DCDDF9] rounded-xl p-5 shadow-sm space-y-3">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-[#5B5BD6]/10 text-[#5B5BD6] border border-[#5B5BD6]/20">
                    💡 Quick Tip
                </span>
                <h4 class="text-xs font-bold text-[#171A21]">Looking for Flow Questions?</h4>
                <p class="text-xs text-[#525866] leading-relaxed">
                    Questions, options, scores, and branching logic are configured visually in the <strong>Flow Builder</strong> canvas.
                </p>
                <div class="pt-1">
                    <a href="<?= url("/admin/funnels/{$funnel['id']}/builder") ?>" class="w-full inline-flex items-center justify-center gap-2 py-2 px-3 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] transition-colors shadow-sm">
                        Open Flow Builder →
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
    function copyQuizUrl() {
        const fullUrl = window.location.origin + '<?= url("/quiz/{$funnel['slug']}") ?>';
        navigator.clipboard.writeText(fullUrl).then(() => {
            const toast = document.getElementById('copyToast');
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2500);
        });
    }

    function switchSettingsSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.settings-section').forEach(sec => sec.classList.add('hidden'));

        // Reset all tabs styling
        document.querySelectorAll('.settings-tab-btn').forEach(btn => {
            btn.className = 'settings-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[#687080] hover:text-[#171A21] hover:bg-slate-50 transition-all';
        });

        // Show target section
        const targetSec = document.getElementById('section-' + sectionId);
        if (targetSec) targetSec.classList.remove('hidden');

        // Activate button
        const activeBtn = document.getElementById('secTab-' + sectionId);
        if (activeBtn) {
            activeBtn.className = 'settings-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg bg-[#5B5BD6] text-white transition-all shadow-xs';
        }

        // Store active section in URL hash for back/refresh persistence
        history.replaceState(null, null, '#' + sectionId);
    }

    function switchEmbedTab(tab) {
        document.getElementById('embedContentInline').classList.add('hidden');
        document.getElementById('embedContentPopup').classList.add('hidden');
        document.getElementById('embedContentFloat').classList.add('hidden');

        document.getElementById('tabBtnInline').className = 'pb-2 border-b-2 border-transparent text-[#687080] hover:text-[#171A21]';
        document.getElementById('tabBtnPopup').className = 'pb-2 border-b-2 border-transparent text-[#687080] hover:text-[#171A21]';
        document.getElementById('tabBtnFloat').className = 'pb-2 border-b-2 border-transparent text-[#687080] hover:text-[#171A21]';

        if (tab === 'inline') {
            document.getElementById('embedContentInline').classList.remove('hidden');
            document.getElementById('tabBtnInline').className = 'pb-2 border-b-2 border-[#5B5BD6] text-[#5B5BD6]';
        } else if (tab === 'popup') {
            document.getElementById('embedContentPopup').classList.remove('hidden');
            document.getElementById('tabBtnPopup').className = 'pb-2 border-b-2 border-[#5B5BD6] text-[#5B5BD6]';
        } else if (tab === 'float') {
            document.getElementById('embedContentFloat').classList.remove('hidden');
            document.getElementById('tabBtnFloat').className = 'pb-2 border-b-2 border-[#5B5BD6] text-[#5B5BD6]';
        }
    }

    function copySnippet(btnId, btnElement) {
        const code = btnElement.getAttribute('data-code');
        navigator.clipboard.writeText(code).then(() => {
            const originalText = btnElement.textContent;
            btnElement.textContent = 'Copied!';
            btnElement.classList.replace('text-slate-300', 'text-emerald-400');
            setTimeout(() => {
                btnElement.textContent = originalText;
                btnElement.classList.replace('text-emerald-400', 'text-slate-300');
            }, 2000);
        });
    }

    // Auto-restore section tab from URL hash on page load
    window.addEventListener('DOMContentLoaded', () => {
        const hash = window.location.hash.replace('#', '');
        if (hash && ['general', 'branding', 'navigation', 'embed', 'advanced'].includes(hash)) {
            switchSettingsSection(hash);
        }
    });
</script>
