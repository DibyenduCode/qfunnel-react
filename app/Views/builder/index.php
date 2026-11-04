<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Visual Funnel Builder') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            user-select: none;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }
        .canvas-grid {
            background-image: radial-gradient(#CBD5E1 1.25px, transparent 1.25px);
            background-size: 24px 24px;
        }
        .node-card {
            border: 1px solid #E2E8F0;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 2px 4px -1px rgba(15, 23, 42, 0.02);
            transition: box-shadow 0.16s ease, border-color 0.16s ease, transform 0.16s ease;
            cursor: default !important;
        }
        .node-card * {
            cursor: default;
        }
        .node-card .node-header {
            cursor: grab !important;
        }
        .node-card .node-header:active {
            cursor: grabbing !important;
        }
        .node-card .port-handle {
            cursor: crosshair !important;
            width: 14px;
            height: 14px;
            border-radius: 9999px;
            border: 2px solid #ffffff;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.2);
            position: absolute !important;
            z-index: 10;
            transition: transform 0.15s ease;
            box-sizing: border-box;
        }
        .node-card .port-handle:hover {
            transform: scale(1.3) !important;
        }
        .node-card .port-handle[data-port-type="output"] {
            right: -7px !important;
            top: 50% !important;
            margin-top: -7px !important;
        }
        .node-card .port-handle[data-port-type="input"] {
            left: -7px !important;
            top: 14px !important;
            margin-top: 0 !important;
            cursor: pointer !important;
        }
        .node-card button, .node-card a, .node-card input, .node-card select {
            cursor: pointer !important;
        }
        .node-card:hover {
            box-shadow: 0 6px 18px 0 rgba(15, 23, 42, 0.08), 0 2px 4px 0 rgba(15, 23, 42, 0.03);
            border-color: #CBD5E1;
        }
        .node-card.selected {
            border-color: #6366F1 !important;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.22), 0 10px 24px -3px rgba(15, 23, 42, 0.1);
        }
        .wire-path {
            stroke: #94A3B8;
            stroke-width: 2.2;
            fill: none;
            transition: stroke 0.15s ease, stroke-width 0.15s ease;
        }
        .wire-path:hover {
            stroke: #6366F1 !important;
            stroke-width: 3.5 !important;
            cursor: pointer;
            filter: drop-shadow(0 2px 4px rgba(99, 102, 241, 0.35));
        }
        .wire-path.selected {
            stroke: #6366F1 !important;
            stroke-width: 3.5 !important;
            filter: drop-shadow(0 2px 6px rgba(99, 102, 241, 0.45));
        }
        .port-handle {
            transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.15s ease, background-color 0.15s ease;
        }
        .port-handle:hover {
            transform: scale(1.35) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25);
        }
        .wire-delete-btn {
            cursor: pointer;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }
        .wire-delete-btn:hover {
            transform: scale(1.15);
        }
        .palette-card {
            cursor: grab;
            transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .palette-card:active {
            cursor: grabbing;
        }
        .palette-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        }
        .canvas-drag-over {
            background-color: rgba(99, 102, 241, 0.03) !important;
            outline: 2px dashed #6366F1;
            outline-offset: -4px;
        }
        #toastContainer {
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
    </style>
</head>
<body class="h-screen w-screen flex flex-col bg-[#F7F8FA]">

    <!-- Top Workspace Header -->
    <header class="h-14 bg-white border-b border-[#E4E7EC] px-3 sm:px-4 flex items-center justify-between z-20 shrink-0 gap-3 overflow-x-auto overflow-y-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden min-w-0">
        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0 min-w-0">
            <a href="<?= url('/admin/funnels') ?>" class="p-1.5 rounded-lg text-[#687080] hover:text-[#171A21] hover:bg-[#F1F3F6] shrink-0" title="Back to Funnels">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div class="h-4 w-px bg-[#E4E7EC] shrink-0"></div>
            <div class="min-w-0 shrink">
                <h1 class="text-xs font-bold text-[#171A21] truncate max-w-[140px] sm:max-w-[200px]"><?= e($funnel['name']) ?></h1>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="text-[10px] text-[#687080] truncate max-w-[100px] sm:max-w-none">/quiz/<?= e($funnel['slug']) ?></span>
                    <button type="button" onclick="copyFunnelUrl()" class="text-[#687080] hover:text-[#5B5BD6] transition-colors p-0.5 rounded hover:bg-slate-100 shrink-0" title="Copy Public Funnel URL">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <span id="funnelPublishStatusBadge" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold shrink-0 whitespace-nowrap <?= ($funnel['status'] === 'published' && empty($funnel['has_unpublished_changes'])) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-300' ?>" title="<?= ($funnel['status'] === 'published' && empty($funnel['has_unpublished_changes'])) ? 'Published & Live' : 'Changes saved in draft — Click Publish Version to update the live public URL' ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= ($funnel['status'] === 'published' && empty($funnel['has_unpublished_changes'])) ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse' ?>"></span>
                        <span id="funnelPublishStatusText"><?= ($funnel['status'] === 'published' && empty($funnel['has_unpublished_changes'])) ? 'Published' : 'Not Published' ?></span>
                    </span>
                </div>
            </div>

            <div class="h-4 w-px bg-[#E4E7EC] shrink-0"></div>

            <!-- Mode Switcher Dropdown -->
            <div class="relative inline-flex items-center rounded-lg bg-slate-100/90 hover:bg-slate-200/80 border border-slate-200/90 text-xs shadow-2xs shrink-0 whitespace-nowrap transition-colors">
                <span id="funnelModeIcon" class="pl-2.5 pointer-events-none flex items-center justify-center shrink-0">
                    <?php if (($funnel['funnel_mode'] ?? 'quiz') === 'calculator'): ?>
                        <svg class="w-3.5 h-3.5 text-purple-600" viewBox="0 0 16 16" fill="none"><rect x="2.5" y="2" width="11" height="12" rx="2" stroke="currentColor" stroke-width="1.3"/><rect x="4.5" y="4" width="7" height="2.5" rx="0.5" stroke="currentColor" stroke-width="1"/><path d="M5 9h1.5M8 9h1.5M5 11.5h1.5M8 11.5h1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                    <?php else: ?>
                        <svg class="w-3.5 h-3.5 text-indigo-600" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="4.5" rx="1.5" stroke="currentColor" stroke-width="1.3"/><rect x="2" y="9" width="12" height="4.5" rx="1.5" stroke="currentColor" stroke-width="1.3"/><circle cx="4.5" cy="4.75" r="1" fill="currentColor"/><circle cx="4.5" cy="11.25" r="1" fill="currentColor"/></svg>
                    <?php endif; ?>
                </span>
                <select id="funnelModeSelector" onchange="switchFunnelMode(this.value)" class="appearance-none bg-transparent pl-1.5 pr-7 py-1.5 text-[11px] font-semibold text-slate-800 focus:outline-none cursor-pointer">
                    <option value="quiz" <?= ($funnel['funnel_mode'] ?? 'quiz') === 'quiz' ? 'selected' : '' ?>>Quiz Mode</option>
                    <option value="calculator" <?= ($funnel['funnel_mode'] ?? 'quiz') === 'calculator' ? 'selected' : '' ?>>Calculator Mode</option>
                </select>
                <span class="absolute right-2 pointer-events-none text-slate-400 flex items-center">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </div>
        </div>

        <!-- Center: Save Status & Actions -->
        <div class="flex items-center gap-3 sm:gap-4 shrink-0 whitespace-nowrap">
            <div id="saveStatusIndicator" class="flex items-center gap-1.5 text-xs text-slate-500 font-medium shrink-0 whitespace-nowrap min-w-[70px]">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span class="whitespace-nowrap">Saved</span>
            </div>

            <div class="flex items-center bg-[#F1F3F6] rounded-lg p-0.5 border border-slate-200 shrink-0 whitespace-nowrap">
                <button type="button" onclick="zoomCanvas(0.85)" class="px-1.5 py-1 text-xs text-slate-600 hover:text-slate-900 rounded shrink-0" title="Zoom Out (−)">−</button>
                <button type="button" onclick="resetZoom()" class="px-1.5 py-1 text-xs text-slate-600 hover:text-slate-900 rounded font-mono shrink-0" id="zoomLevelText">100%</button>
                <button type="button" onclick="zoomCanvas(1.15)" class="px-1.5 py-1 text-xs text-slate-600 hover:text-slate-900 rounded shrink-0" title="Zoom In (+)">+</button>
                <button type="button" onclick="centerCanvas()" class="px-2 py-1 text-xs text-slate-600 hover:text-slate-900 rounded ml-0.5 shrink-0" title="Center Canvas (Ctrl+0)">Fit</button>
                <button type="button" onclick="autoArrangeNodes()" class="px-2 py-1 text-xs text-[#5B5BD6] font-semibold hover:bg-indigo-50 rounded ml-0.5 flex items-center gap-1 transition-colors shrink-0 whitespace-nowrap" title="Auto-arrange canvas tree layout (L)">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"/></svg>
                    <span class="whitespace-nowrap">Auto Layout</span>
                </button>
            </div>
        </div>

        <!-- Right: Flow Validation, Preview, Publish -->
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 whitespace-nowrap pr-2">
            <button type="button" onclick="openValidationDrawer()" id="validateBtn" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold text-[#171A21] hover:bg-[#F7F8FA] transition-colors shrink-0 whitespace-nowrap" title="Validate Flow">
                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span class="whitespace-nowrap">Validate</span>
            </button>

            <button type="button" onclick="openPreviewModal()" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold text-[#171A21] hover:bg-[#F7F8FA] transition-colors shrink-0 whitespace-nowrap" title="Preview Quiz (P)">
                <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="whitespace-nowrap">Preview</span>
            </button>

            <button type="button" onclick="saveGraphNow()" id="saveBtn" class="px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold text-[#171A21] hover:bg-[#F7F8FA] shrink-0 whitespace-nowrap" title="Save Flow (Ctrl+S)">
                Save
            </button>

            <?php $isLive = ($funnel['status'] === 'published' && empty($funnel['has_unpublished_changes'])); ?>
            <button type="button" onclick="publishFunnel()" id="publishBtn" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition-colors shrink-0 whitespace-nowrap <?= $isLive ? 'bg-[#5B5BD6] text-white hover:bg-[#4B4BC0] font-semibold' : 'bg-amber-600 text-white hover:bg-amber-700 font-bold shadow-sm' ?>">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span id="publishBtnText"><?= $isLive ? 'Published' : 'Publish Changes' ?></span>
            </button>
        </div>
    </header>

    <!-- 3-Column Main Workspace Layout -->
    <div class="flex-1 flex overflow-hidden relative">

        <!-- Left Node Palette (Zone 1) -->
        <aside id="leftSidebar" class="w-60 bg-white border-r border-[#E4E7EC] flex flex-col z-10 shrink-0 transition-all duration-200">
            <div class="p-3 border-b border-[#E4E7EC] flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#171A21]">Node Palette</h2>
                    <p class="text-[11px] text-[#687080] mt-0.5">Drag to canvas or click to add</p>
                </div>
                <button type="button" onclick="toggleLeftSidebar()" class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Collapse Palette ([)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
            </div>

            <div class="p-3 space-y-2.5 overflow-y-auto flex-1">

                <!-- Question Node -->
                <div draggable="true" ondragstart="handlePaletteDragStart(event, 'question')" onclick="addNodeToCanvas('question')" class="palette-card w-full p-2.5 rounded-xl border border-slate-200/80 bg-white hover:border-indigo-400 hover:bg-indigo-50/20 text-left transition-all flex items-start gap-2.5 shadow-2xs select-none group">
                    <div class="w-7 h-7 rounded-lg bg-slate-50 text-indigo-600 border border-slate-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-indigo-50/50 transition-all">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="4.5" rx="1.5" stroke="currentColor" stroke-width="1.3"/><rect x="2" y="9" width="12" height="4.5" rx="1.5" stroke="currentColor" stroke-width="1.3"/><circle cx="4.5" cy="4.75" r="1" fill="currentColor"/><circle cx="4.5" cy="11.25" r="1" fill="currentColor"/><line x1="7.5" y1="4.75" x2="11.5" y2="4.75" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><line x1="7.5" y1="11.25" x2="11.5" y2="11.25" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="block text-xs font-semibold text-slate-800">Choice Question</span>
                            <kbd class="text-[9px] font-mono font-semibold bg-slate-100 text-slate-600 px-1 rounded border border-slate-200">Q</kbd>
                        </div>
                        <span class="block text-[10px] text-slate-500 leading-tight mt-0.5">Single / multiple choices</span>
                    </div>
                </div>

                <!-- Number / Slider Node (Calculator Input) -->
                <div draggable="true" ondragstart="handlePaletteDragStart(event, 'slider')" onclick="addNodeToCanvas('slider')" class="palette-card w-full p-2.5 rounded-xl border border-slate-200/80 bg-white hover:border-emerald-400 hover:bg-emerald-50/20 text-left transition-all flex items-start gap-2.5 shadow-2xs select-none group">
                    <div class="w-7 h-7 rounded-lg bg-slate-50 text-emerald-600 border border-slate-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-emerald-50/50 transition-all">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><line x1="2" y1="8" x2="14" y2="8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="6" cy="8" r="2.5" fill="white" stroke="currentColor" stroke-width="1.4"/><path d="M6 3.5V5M6 11v1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="block text-xs font-semibold text-slate-800">Number / Slider</span>
                            <kbd class="text-[9px] font-mono font-semibold bg-slate-100 text-slate-600 px-1 rounded border border-slate-200">S</kbd>
                        </div>
                        <span id="sliderPaletteSubtitle" class="block text-[10px] text-slate-500 leading-tight mt-0.5"><?= ($funnel['funnel_mode'] ?? 'quiz') === 'calculator' ? 'Numeric quantity & variable' : 'Numeric quantity & range' ?></span>
                    </div>
                </div>

                <!-- Form Node (Lead Capture) -->
                <div draggable="true" ondragstart="handlePaletteDragStart(event, 'form')" onclick="addNodeToCanvas('form')" class="palette-card w-full p-2.5 rounded-xl border border-slate-200/80 bg-white hover:border-sky-400 hover:bg-sky-50/20 text-left transition-all flex items-start gap-2.5 shadow-2xs select-none group">
                    <div class="w-7 h-7 rounded-lg bg-slate-50 text-sky-600 border border-slate-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-sky-50/50 transition-all">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><line x1="5" y1="5.5" x2="11" y2="5.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><line x1="5" y1="8.5" x2="9" y2="8.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="10" cy="10.5" r="1" fill="currentColor"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="block text-xs font-semibold text-slate-800">Form Node</span>
                            <kbd class="text-[9px] font-mono font-semibold bg-slate-100 text-slate-600 px-1 rounded border border-slate-200">F</kbd>
                        </div>
                        <span class="block text-[10px] text-slate-500 leading-tight mt-0.5">Lead capture & custom data</span>
                    </div>
                </div>

                <!-- Destination Node -->
                <div draggable="true" ondragstart="handlePaletteDragStart(event, 'destination')" onclick="addNodeToCanvas('destination')" class="palette-card w-full p-2.5 rounded-xl border border-slate-200/80 bg-white hover:border-purple-400 hover:bg-purple-50/20 text-left transition-all flex items-start gap-2.5 shadow-2xs select-none group">
                    <div class="w-7 h-7 rounded-lg bg-slate-50 text-purple-600 border border-slate-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-purple-50/50 transition-all">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M5.5 10.5L10.5 5.5M10.5 5.5H7.5M10.5 5.5V8.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="block text-xs font-semibold text-slate-800">Destination Node</span>
                            <kbd class="text-[9px] font-mono font-semibold bg-slate-100 text-slate-600 px-1 rounded border border-slate-200">D</kbd>
                        </div>
                        <span class="block text-[10px] text-slate-500 leading-tight mt-0.5">Terminal landing page URL</span>
                    </div>
                </div>

                <!-- 1-Click Call & WhatsApp Connect Node -->
                <div draggable="true" ondragstart="handlePaletteDragStart(event, 'connect')" onclick="addNodeToCanvas('connect')" class="palette-card w-full p-2.5 rounded-xl border border-slate-200/80 bg-white hover:border-emerald-400 hover:bg-emerald-50/20 text-left transition-all flex items-start gap-2.5 shadow-2xs select-none group">
                    <div class="w-7 h-7 rounded-lg bg-slate-50 text-emerald-600 border border-slate-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-emerald-50/50 transition-all">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><path d="M2.5 4.5A2 2 0 014.5 2.5h1.2a1 1 0 011 .76l.6 2.37a1 1 0 01-.28.98l-.8.8a8.3 8.3 0 003.87 3.87l.8-.8a1 1 0 01.98-.28l2.37.6a1 1 0 01.76 1v1.2a2 2 0 01-2 2c-6.07 0-11-4.93-11-11v-1.2z" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="block text-xs font-semibold text-slate-800">Call & WhatsApp</span>
                            <kbd class="text-[9px] font-mono font-semibold bg-slate-100 text-slate-600 px-1 rounded border border-slate-200">C</kbd>
                        </div>
                        <span class="block text-[10px] text-slate-500 leading-tight mt-0.5">1-click direct call / chat</span>
                    </div>
                </div>

                <!-- Video Gate Node (YouTube & Vimeo Turnstile) -->
                <div draggable="true" ondragstart="handlePaletteDragStart(event, 'video_gate')" onclick="addNodeToCanvas('video_gate')" class="palette-card w-full p-2.5 rounded-xl border border-slate-200/80 bg-white hover:border-rose-400 hover:bg-rose-50/20 text-left transition-all flex items-start gap-2.5 shadow-2xs select-none group">
                    <div class="w-7 h-7 rounded-lg bg-slate-50 text-rose-600 border border-slate-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-rose-50/50 transition-all">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><polygon points="6,5.5 11,8 6,10.5" fill="currentColor"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="block text-xs font-semibold text-slate-800">Video Gate</span>
                            <kbd class="text-[9px] font-mono font-semibold bg-slate-100 text-slate-600 px-1 rounded border border-slate-200">V</kbd>
                        </div>
                        <span class="block text-[10px] text-slate-500 leading-tight mt-0.5">YouTube/Vimeo mid-roll turnstile</span>
                    </div>
                </div>

                <!-- Quick Help / Workflow Tip -->
                <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-200/60 text-[11px] text-slate-500 space-y-1">
                    <div class="flex items-center gap-1.5 font-semibold text-slate-700 text-[10px] uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Flow Strategy</span>
                    </div>
                    <p class="leading-relaxed text-[10px] text-slate-500">
                        Connect output ports on the right to input ports on the left to create dynamic branching paths.
                    </p>
                </div>
            </div>

            <!-- Collapsible Shortcuts / Hotkeys Accordion (Collapsed by default) -->
            <div class="border-t border-[#E4E7EC] bg-[#F7F8FA]">
                <button type="button" onclick="toggleHotkeysAccordion()" class="w-full px-3 py-2 flex items-center justify-between text-[11px] font-semibold text-[#171A21] hover:bg-slate-100 transition-colors">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#5B5BD6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        <span>Shortcuts Guide</span>
                    </div>
                    <svg id="hotkeysChevron" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div id="hotkeysContent" class="hidden px-3 pb-3 space-y-1 text-[10px] text-[#485060]">
                    <div class="flex items-center justify-between"><span class="font-medium">Save</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">Ctrl+S</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Auto Layout</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">L</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Delete Wire/Node</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">Delete</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Move Node / Pan</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">↑ ↓ ← →</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Toggle Sidebars</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">[ / ]</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Deselect / Cancel</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">Esc</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Zoom In / Out</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">+ / -</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Add Question</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">Q</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Add Number Slider</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">S</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Add Form Node</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">F</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Add Destination</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">D</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Add Call/WhatsApp</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">C</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Add Video Gate</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">V</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Live Preview</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">P</kbd></div>
                    <div class="flex items-center justify-between"><span class="font-medium">Validate Flow</span><kbd class="px-1.5 py-0.5 bg-white border border-[#D0D5DD] rounded text-[9px] font-mono shadow-xs">V</kbd></div>
                </div>
            </div>
        </aside>

        <!-- Central Infinite Pan/Zoom Canvas (Zone 2) -->
        <main id="canvasViewport" ondragover="handleCanvasDragOver(event)" ondragleave="handleCanvasDragLeave(event)" ondrop="handleCanvasDrop(event)" class="flex-1 relative overflow-hidden canvas-grid cursor-grab active:cursor-grabbing">
            <!-- Floating Expand Buttons when sidebars are collapsed -->
            <button id="leftSidebarExpandBtn" type="button" onclick="toggleLeftSidebar()" class="hidden absolute left-3 top-3 z-30 px-2.5 py-1.5 rounded-lg bg-white border border-[#E4E7EC] shadow-md hover:bg-[#F7F8FA] text-xs font-semibold text-[#171A21] flex items-center gap-1.5 transition-all cursor-pointer" title="Expand Node Palette ([)">
                <svg class="w-3.5 h-3.5 text-[#5B5BD6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span>Palette</span>
            </button>

            <button id="inspectorExpandBtn" type="button" onclick="toggleInspector()" class="hidden absolute right-3 top-3 z-30 px-2.5 py-1.5 rounded-lg bg-white border border-[#E4E7EC] shadow-md hover:bg-[#F7F8FA] text-xs font-semibold text-[#171A21] flex items-center gap-1.5 transition-all cursor-pointer" title="Expand Inspector (])">
                <span>Inspector</span>
                <svg class="w-3.5 h-3.5 text-[#5B5BD6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>

            <!-- Floating Top Notification Toast -->
            <div id="toastContainer" class="pointer-events-none absolute top-4 left-1/2 transform -translate-x-1/2 z-40 opacity-0 -translate-y-2 transition-all duration-200">
                <div id="toastMessage" class="px-4 py-2 rounded-xl bg-slate-900/90 backdrop-blur-xs text-white text-xs font-medium shadow-lg flex items-center gap-2 border border-slate-700">
                    <span id="toastIcon">✨</span>
                    <span id="toastText">Action completed</span>
                </div>
            </div>

            <!-- Transform Container -->
            <div id="canvasSurface" class="absolute inset-0 origin-top-left" style="transform: translate(0px, 0px) scale(1);">
                <!-- SVG Wire Layer -->
                <svg id="svgLayer" class="absolute top-0 left-0 w-[5000px] h-[5000px] pointer-events-none" style="z-index: 1;">
                    <defs>
                        <marker id="arrow" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                            <path d="M 0 1 L 10 5 L 0 9 z" fill="#8E95A5" />
                        </marker>
                        <marker id="arrow-selected" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                            <path d="M 0 1 L 10 5 L 0 9 z" fill="#5B5BD6" />
                        </marker>
                    </defs>
                    <g id="wiresGroup"></g>
                    <!-- Temporary wire while dragging -->
                    <path id="tempWire" class="wire-path stroke-indigo-400 stroke-dashed hidden" stroke-dasharray="4" d="" />
                </svg>

                <!-- HTML Nodes Layer -->
                <div id="nodesContainer" class="absolute top-0 left-0 w-[5000px] h-[5000px] pointer-events-auto" style="z-index: 2;">
                    <!-- Rendered dynamically via JS -->
                </div>
            </div>

            <!-- Floating Interactive Mini-Map in bottom-right -->
            <div id="minimapContainer" class="absolute right-4 bottom-4 z-20 bg-white/95 backdrop-blur-sm border border-[#E4E7EC] rounded-xl shadow-lg overflow-hidden flex flex-col transition-all duration-200">
                <div class="px-2.5 py-1.5 border-b border-[#E4E7EC] bg-slate-50/80 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#5B5BD6]"></span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#687080]">Minimap</span>
                    </div>
                    <button type="button" onclick="toggleMinimap()" class="text-[10px] text-slate-400 hover:text-slate-700 p-0.5 leading-none" title="Hide Minimap">✕</button>
                </div>
                <div id="minimapViewport" class="w-44 h-28 relative bg-[#F7F8FA] overflow-hidden cursor-crosshair">
                    <canvas id="minimapCanvas" width="176" height="112" class="w-full h-full block"></canvas>
                    <div id="minimapViewBox" class="absolute border-2 border-[#5B5BD6] bg-indigo-500/10 pointer-events-none rounded-xs"></div>
                </div>
            </div>

            <!-- Mini-map Re-open button if minimized -->
            <button id="minimapOpenBtn" type="button" onclick="toggleMinimap()" class="hidden absolute right-4 bottom-4 z-20 px-2.5 py-1.5 rounded-lg bg-white border border-[#E4E7EC] shadow-md hover:bg-[#F7F8FA] text-[11px] font-semibold text-[#171A21] flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-[#5B5BD6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <span>Minimap</span>
            </button>
        </main>

        <!-- Right Inspector Panel (Zone 3) -->
        <aside id="inspectorPanel" class="w-80 bg-white border-l border-[#E4E7EC] flex flex-col z-10 shrink-0 shadow-sm transition-all duration-200">
            <div class="p-3.5 border-b border-[#E4E7EC] flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#171A21]">Node Inspector</h2>
                    <span id="inspectorNodeTypeBadge" class="text-[10px] font-mono px-2 py-0.5 rounded bg-indigo-50 text-[#5B5BD6] font-semibold uppercase">Overview</span>
                </div>
                <button type="button" onclick="toggleInspector()" class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Collapse Inspector (Right Sidebar)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <div id="inspectorContent" class="p-4 space-y-4 overflow-y-auto flex-1 text-xs">
                <!-- Dynamically populated via JS: Funnel Overview when unselected, or Node/Wire properties when selected -->
            </div>
        </aside>

    </div>

    <!-- Validation Drawer -->
    <div id="validationDrawer" class="fixed inset-y-0 right-0 w-96 bg-white border-l border-[#E4E7EC] shadow-2xl z-50 transform translate-x-full transition-transform duration-200 flex flex-col">
        <div class="p-4 border-b border-[#E4E7EC] flex items-center justify-between bg-[#F7F8FA]">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="text-sm font-bold text-[#171A21]">Flow Validation</h3>
            </div>
            <button onclick="closeValidationDrawer()" class="text-slate-400 hover:text-slate-700 p-1">✕</button>
        </div>
        <div id="validationResults" class="p-4 overflow-y-auto flex-1 space-y-3 text-xs">
            <p class="text-[#687080]">Running validation checks...</p>
        </div>
    </div>

    <!-- Interactive Live Preview Modal -->
    <div id="previewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl border border-[#E4E7EC] max-w-lg w-full shadow-2xl flex flex-col overflow-hidden max-h-[90vh]">
            <div class="px-5 py-4 border-b border-[#E4E7EC] flex items-center justify-between bg-[#F7F8FA]">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-bold text-[#171A21]">Quiz Runner Live Preview</span>
                </div>
                <button onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-700 font-bold p-1">✕</button>
            </div>
            <div id="previewModalBody" class="p-6 overflow-y-auto flex-1">
                <!-- Interactive quiz rendered here -->
            </div>
        </div>
    </div>

    <!-- Beautified Custom Confirmation & Alert Modal -->
    <div id="customDialogModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
        <div id="customDialogCard" class="bg-white rounded-2xl border border-slate-200 max-w-sm w-full shadow-2xl overflow-hidden p-6 space-y-4 transform scale-95 transition-all duration-150">
            <div class="flex items-start gap-3.5">
                <div id="customDialogIconContainer" class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <svg id="customDialogDangerIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <svg id="customDialogWarningIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <svg id="customDialogSuccessIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg id="customDialogInfoIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1">
                    <h4 id="customDialogTitle" class="text-sm font-bold text-[#171A21]">Confirmation</h4>
                    <p id="customDialogMessage" class="text-xs text-[#687080] mt-1 leading-relaxed">Are you sure?</p>
                </div>
            </div>
            <div id="customDialogActions" class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" id="customDialogCancelBtn" class="px-3.5 py-2 rounded-xl border border-[#D0D5DD] bg-white text-xs font-semibold text-[#344054] hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="button" id="customDialogConfirmBtn" class="px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700 shadow-xs shadow-rose-200 transition-colors">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Global Application Config for JS -->
    <script>
        window.FUNNEL_ID = <?= (int)$funnel['id'] ?>;
        window.FUNNEL_MODE = '<?= e($funnel['funnel_mode'] ?? 'quiz') ?>';
        window.CSRF_TOKEN = '<?= csrf_token() ?>';
        window.API_BASE = '<?= url('') ?>';
    </script>

    <!-- Visual Builder Engine -->
    <script src="<?= asset('/js/builder.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
