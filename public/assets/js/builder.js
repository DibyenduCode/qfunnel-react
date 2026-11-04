/**
 * Quiz Funnel SaaS — Visual Branching Logic Graph Builder
 * Vanilla JavaScript & SVG Interactive Engine
 */

(function () {
    'use strict';

    // State
    let graphData = {
        nodes: [],
        edges: []
    };
    let selectedNodeId = null;
    let selectedEdgeId = null;

    // Viewport transform
    let scale = 1.0;
    let panX = 40;
    let panY = 40;
    let isPanning = false;
    let startPanX = 0;
    let startPanY = 0;

    // Node dragging
    let draggedNode = null;
    let dragStartX = 0;
    let dragStartY = 0;
    let nodeStartX = 0;
    let nodeStartY = 0;

    // Wire connection dragging
    let connectingSource = null; // { nodeId, optionId, x, y }

    let isDirty = false;
    let autoSaveTimer = null;

    // DOM Elements
    const viewport = document.getElementById('canvasViewport');
    const surface = document.getElementById('canvasSurface');
    const nodesContainer = document.getElementById('nodesContainer');
    const svgLayer = document.getElementById('svgLayer');
    const wiresGroup = document.getElementById('wiresGroup');
    const tempWire = document.getElementById('tempWire');
    const inspectorContent = document.getElementById('inspectorContent');
    const inspectorBadge = document.getElementById('inspectorNodeTypeBadge');
    const zoomText = document.getElementById('zoomLevelText');
    const saveIndicator = document.getElementById('saveStatusIndicator');

    // Safe node lookup helper by ID or key (handles string vs number casting)
    function findNode(idOrKey) {
        if (idOrKey === null || idOrKey === undefined || idOrKey === '') return null;
        if (!graphData || !graphData.nodes) return null;
        return graphData.nodes.find(n => n.id == idOrKey || n.node_key === String(idOrKey)) || null;
    }
    function parseVideoUrl(url) {
        if (!url || typeof url !== 'string') return { provider: null, id: null, hash: null };
        url = url.trim();

        // YouTube
        let m = url.match(/(?:youtube\.com\/(?:watch\?.*v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        if (m) {
            return { provider: 'youtube', id: m[1], hash: null };
        }

        // Vimeo with unlisted hash e.g. vimeo.com/123456789/abcdef123 or player.vimeo.com/video/123456789?h=abcdef123
        m = url.match(/vimeo\.com\/(?:video\/)?(\d+)(?:\/([a-zA-Z0-9]+))?/);
        if (m) {
            let hash = m[2] || null;
            if (!hash) {
                let hm = url.match(/[?&]h=([a-zA-Z0-9]+)/);
                if (hm) hash = hm[1];
            }
            return { provider: 'vimeo', id: m[1], hash: hash };
        }

        return { provider: null, id: null, hash: null };
    }

    // Init
    window.addEventListener('DOMContentLoaded', init);

    async function init() {
        setupCanvasEvents();
        setupKeyboardEvents();
        setupMinimapEvents();
        await loadGraph();
        centerCanvas();
        deselectAll();
    }

    async function loadGraph() {
        setSaveStatus('loading', 'Loading graph...');
        try {
            const resp = await fetch(`${window.API_BASE}/api/funnels/${window.FUNNEL_ID}/graph`);
            const json = await resp.json();
            if (json.success) {
                graphData = json.data;

                // Automatic migration for legacy 'start' nodes if any exist
                const legacyStart = (graphData.nodes || []).find(n => n.node_type === 'start');
                if (legacyStart) {
                    const outEdge = (graphData.edges || []).find(e => e.source_node_id == legacyStart.id);
                    if (outEdge) {
                        const tgtNode = (graphData.nodes || []).find(n => n.id == outEdge.target_node_id);
                        if (tgtNode) tgtNode.is_start = true;
                    }
                    graphData.nodes = graphData.nodes.filter(n => n.id != legacyStart.id);
                    graphData.edges = (graphData.edges || []).filter(e => e.source_node_id != legacyStart.id && e.target_node_id != legacyStart.id);
                    markDirty();
                }

                // Ensure at least one starting step
                if (!graphData.nodes.some(n => n.is_start)) {
                    const firstNonDest = graphData.nodes.find(n => n.node_type !== 'destination');
                    if (firstNonDest) firstNonDest.is_start = true;
                }

                renderCanvas();
                deselectAll();
                setSaveStatus('saved', 'Saved');
                updateMinimap();
            } else {
                setSaveStatus('error', 'Failed to load');
            }
        } catch (e) {
            console.error('Failed to load graph:', e);
            setSaveStatus('error', 'Connection error');
        }
    }

    function setupCanvasEvents() {
        // Pan with mouse drag on background
        viewport.addEventListener('mousedown', (e) => {
            if (e.target === viewport || e.target === surface || e.target.id === 'nodesContainer' || e.target.id === 'svgLayer') {
                isPanning = true;
                startPanX = e.clientX - panX;
                startPanY = e.clientY - panY;
                deselectAll();
            }
        });

        window.addEventListener('mousemove', (e) => {
            if (isPanning) {
                panX = e.clientX - startPanX;
                panY = e.clientY - startPanY;
                applyTransform();
            } else if (draggedNode) {
                const dx = (e.clientX - dragStartX) / scale;
                const dy = (e.clientY - dragStartY) / scale;
                draggedNode.position_x = Math.max(20, nodeStartX + dx);
                draggedNode.position_y = Math.max(20, nodeStartY + dy);
                
                const nodeEl = document.getElementById(`node-${draggedNode.id}`);
                if (nodeEl) {
                    nodeEl.style.left = `${draggedNode.position_x}px`;
                    nodeEl.style.top = `${draggedNode.position_y}px`;
                }
                renderWires();
                updateMinimap();
                markDirty();
            } else if (connectingSource) {
                const rect = surface.getBoundingClientRect();
                const mouseX = (e.clientX - rect.left) / scale;
                const mouseY = (e.clientY - rect.top) / scale;
                drawBezier(tempWire, connectingSource.x, connectingSource.y, mouseX, mouseY);
            }
        });

        window.addEventListener('mouseup', () => {
            if (isPanning) isPanning = false;
            if (draggedNode) draggedNode = null;
            if (connectingSource) {
                connectingSource = null;
                tempWire.classList.add('hidden');
                document.querySelectorAll('.node-card').forEach(c => c.classList.remove('ring-2', 'ring-[#5B5BD6]'));
            }
        });

        // Zoom with wheel
        viewport.addEventListener('wheel', (e) => {
            e.preventDefault();
            const zoomFactor = e.deltaY < 0 ? 1.08 : 0.92;
            const newScale = Math.min(Math.max(scale * zoomFactor, 0.4), 2.2);

            const rect = viewport.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            panX = mouseX - (mouseX - panX) * (newScale / scale);
            panY = mouseY - (mouseY - panY) * (newScale / scale);
            scale = newScale;

            applyTransform();
        }, { passive: false });
    }

    function setupKeyboardEvents() {
        window.addEventListener('keydown', (e) => {
            const isInputFocused = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) || document.activeElement?.isContentEditable;

            // 1. Ctrl+S or Cmd+S -> Save funnel immediately anywhere
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                saveGraphNow();
                return;
            }

            // 2. Escape -> Deselect, Cancel Drag, Close Modals/Drawers
            if (e.key === 'Escape') {
                e.preventDefault();
                if (isInputFocused) {
                    document.activeElement.blur();
                }
                const previewModal = document.getElementById('previewModal');
                if (previewModal && !previewModal.classList.contains('hidden')) {
                    closePreviewModal();
                    return;
                }
                const valDrawer = document.getElementById('validationDrawer');
                if (valDrawer && !valDrawer.classList.contains('translate-x-full')) {
                    closeValidationDrawer();
                    return;
                }
                if (connectingSource) {
                    connectingSource = null;
                    tempWire.classList.add('hidden');
                    document.querySelectorAll('.node-card').forEach(c => c.classList.remove('ring-2', 'ring-[#5B5BD6]'));
                    return;
                }
                deselectAll();
                return;
            }

            // Do not trigger canvas action shortcuts if user is typing in text fields
            if (isInputFocused) return;

            // 3. Delete key only (NOT Backspace) -> Delete selected wire or selected node
            if (e.key === 'Delete') {
                if (selectedEdgeId) {
                    e.preventDefault();
                    deleteSelectedEdge();
                } else if (selectedNodeId) {
                    e.preventDefault();
                    deleteNode(selectedNodeId);
                }
                return;
            }

            // 4. Arrow Keys -> Nudge Selected Node OR Pan Canvas
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                e.preventDefault();
                const step = e.shiftKey ? 50 : 10;
                if (selectedNodeId) {
                    const node = graphData.nodes.find(n => n.id == selectedNodeId);
                    if (node) {
                        if (e.key === 'ArrowUp') node.position_y = Math.max(20, node.position_y - step);
                        if (e.key === 'ArrowDown') node.position_y += step;
                        if (e.key === 'ArrowLeft') node.position_x = Math.max(20, node.position_x - step);
                        if (e.key === 'ArrowRight') node.position_x += step;

                        const nodeEl = document.getElementById(`node-${node.id}`);
                        if (nodeEl) {
                            nodeEl.style.left = `${node.position_x}px`;
                            nodeEl.style.top = `${node.position_y}px`;
                        }
                        renderWires();
                        markDirty();
                    }
                } else {
                    // Pan canvas with keyboard
                    if (e.key === 'ArrowUp') panY += 40;
                    if (e.key === 'ArrowDown') panY -= 40;
                    if (e.key === 'ArrowLeft') panX += 40;
                    if (e.key === 'ArrowRight') panX -= 40;
                    applyTransform();
                }
                return;
            }

            // 5. Zoom (+ / - / Ctrl+0)
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                zoomCanvas(1.15);
                return;
            }
            if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                zoomCanvas(0.85);
                return;
            }
            if (e.key === '0' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                resetZoom();
                centerCanvas();
                return;
            }

            // 6. Quick Add Node: 'Q' for Question, 'F' for Form, 'D' for Destination
            if (e.key.toLowerCase() === 'q' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                addNodeToCanvas('question');
                return;
            }
            if (e.key.toLowerCase() === 'f' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                addNodeToCanvas('form');
                return;
            }
            if (e.key.toLowerCase() === 'd' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                addNodeToCanvas('destination');
                return;
            }
            if (e.key.toLowerCase() === 's' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                addNodeToCanvas('slider');
                return;
            }
            if (e.key.toLowerCase() === 'c' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                addNodeToCanvas('connect');
                return;
            }
            if (e.key.toLowerCase() === 'v' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                addNodeToCanvas('video_gate');
                return;
            }
            if (e.key.toLowerCase() === 'l' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                autoArrangeNodes();
                return;
            }

            // 7. Toggle Sidebars: '[' for Left Sidebar, ']' for Right Inspector
            if (e.key === '[' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                toggleLeftSidebar();
                return;
            }
            if (e.key === ']' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                toggleInspector();
                return;
            }

            // 8. Modals: 'V' for Validate Flow, 'P' for Live Preview
            if (e.key.toLowerCase() === 'v' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                const drawer = document.getElementById('validationDrawer');
                if (drawer && !drawer.classList.contains('translate-x-full')) {
                    closeValidationDrawer();
                } else {
                    openValidationDrawer();
                }
                return;
            }
            if (e.key.toLowerCase() === 'p' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                const modal = document.getElementById('previewModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closePreviewModal();
                } else {
                    openPreviewModal();
                }
                return;
            }
        });
    }

    function applyTransform() {
        surface.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
        zoomText.textContent = `${Math.round(scale * 100)}%`;
        updateMinimap();
    }

    window.zoomCanvas = function (deltaFactor) {
        scale = Math.min(Math.max(scale * deltaFactor, 0.4), 2.2);
        applyTransform();
    };

    window.resetZoom = function () {
        scale = 1.0;
        applyTransform();
    };

    window.centerCanvas = function () {
        if (!graphData.nodes || graphData.nodes.length === 0) {
            panX = 60;
            panY = 60;
            scale = 1.0;
            applyTransform();
            return;
        }

        let minX = Infinity, minY = Infinity;
        graphData.nodes.forEach(n => {
            if (n.position_x < minX) minX = n.position_x;
            if (n.position_y < minY) minY = n.position_y;
        });

        panX = Math.max(40, 100 - minX * scale);
        panY = Math.max(40, 100 - minY * scale);
        applyTransform();
    };

    // Rendering Canvas Elements
    function renderCanvas() {
        nodesContainer.innerHTML = '';
        if (!graphData.nodes || graphData.nodes.length === 0) {
            nodesContainer.innerHTML = `
                <div class="absolute left-64 top-40 text-center pointer-events-none select-none max-w-sm p-6 rounded-2xl border-2 border-dashed border-slate-300 bg-white/70 backdrop-blur-xs shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-[#5B5BD6] flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-[#171A21]">Blank Canvas</h3>
                    <p class="text-xs text-[#687080] mt-1 leading-relaxed">Click or drag any node from the <strong>Node Palette</strong> on the left to start building your workflow.</p>
                </div>
            `;
        } else {
            graphData.nodes.forEach(renderNodeElement);
        }
        renderWires();
        updateMinimap();
    }

    function renderNodeElement(node) {
        const div = document.createElement('div');
        div.id = `node-${node.id}`;

        const isCalc = node.node_type === 'destination' && node.calculator && node.calculator.enabled;
        const hasQC = node.node_type === 'destination' && node.quick_connect && node.quick_connect.enabled;
        const isRichDest = isCalc || hasQC;
        const cardWidthClass = 'w-72';

        let topBorderColor = 'border-t-indigo-500';
        let badgeText = 'Choice Question';
        // Choice Options widget
        let iconSvg = `<svg class="w-3.5 h-3.5 text-indigo-600" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="4.5" rx="1.5" stroke="currentColor" stroke-width="1.3"/><rect x="2" y="9" width="12" height="4.5" rx="1.5" stroke="currentColor" stroke-width="1.3"/><circle cx="4.5" cy="4.75" r="1" fill="currentColor"/><circle cx="4.5" cy="11.25" r="1" fill="currentColor"/><line x1="7.5" y1="4.75" x2="11.5" y2="4.75" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><line x1="7.5" y1="11.25" x2="11.5" y2="11.25" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>`;
        let iconBg = 'bg-slate-50 border-slate-200/80';

        if (node.node_type === 'start') {
            topBorderColor = 'border-t-emerald-500';
            badgeText = 'Start';
            // Flow Trigger Bullseye
            iconSvg = `<svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.3"/><circle cx="8" cy="8" r="2" fill="currentColor"/></svg>`;
            iconBg = 'bg-slate-50 border-slate-200/80';
        } else if (node.node_type === 'question') {
            const q = node.question || { question_text: 'Untitled Question', options: [] };
            const inputType = node.input_type || q.question_type || 'single_choice';
            if (inputType === 'slider') {
                topBorderColor = 'border-t-emerald-500';
                badgeText = 'Range Slider';
                // Precision Slider Track & Thumb
                iconSvg = `<svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 16 16" fill="none"><line x1="2" y1="8" x2="14" y2="8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="6" cy="8" r="2.5" fill="white" stroke="currentColor" stroke-width="1.4"/><path d="M6 3.5V5M6 11v1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>`;
                iconBg = 'bg-slate-50 border-slate-200/80';
            } else {
                topBorderColor = 'border-t-indigo-500';
                badgeText = inputType === 'multiple_choice' ? 'Multiple Choice' : 'Choice Question';
            }
        } else if (node.node_type === 'destination') {
            topBorderColor = 'border-t-purple-500';
            if (hasQC && !isCalc) {
                badgeText = 'Direct Connect';
                // Direct Contact Receiver
                iconSvg = `<svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 16 16" fill="none"><path d="M2.5 4.5A2 2 0 014.5 2.5h1.2a1 1 0 011 .76l.6 2.37a1 1 0 01-.28.98l-.8.8a8.3 8.3 0 003.87 3.87l.8-.8a1 1 0 01.98-.28l2.37.6a1 1 0 01.76 1v1.2a2 2 0 01-2 2c-6.07 0-11-4.93-11-11v-1.2z" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                iconBg = 'bg-slate-50 border-slate-200/80';
            } else if (hasQC && isCalc) {
                badgeText = 'Quote & Connect';
                // Fast Calculation Bolt
                iconSvg = `<svg class="w-3.5 h-3.5 text-purple-600" viewBox="0 0 16 16" fill="none"><path d="M8.5 2L3 9.5H7.5L6.5 14L13 6.5H8.5L9.5 2H8.5Z" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                iconBg = 'bg-slate-50 border-slate-200/80';
            } else if (isCalc) {
                badgeText = 'Calculated Quote';
                // Calculator Display & Keys
                iconSvg = `<svg class="w-3.5 h-3.5 text-purple-600" viewBox="0 0 16 16" fill="none"><rect x="2.5" y="2" width="11" height="12" rx="2" stroke="currentColor" stroke-width="1.3"/><rect x="4.5" y="4" width="7" height="2.5" rx="0.5" stroke="currentColor" stroke-width="1"/><path d="M5 9h1.5M8 9h1.5M5 11.5h1.5M8 11.5h1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>`;
                iconBg = 'bg-slate-50 border-slate-200/80';
            } else {
                badgeText = 'Destination';
                // Terminal Portal Window
                iconSvg = `<svg class="w-3.5 h-3.5 text-purple-600" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><path d="M5.5 10.5L10.5 5.5M10.5 5.5H7.5M10.5 5.5V8.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                iconBg = 'bg-slate-50 border-slate-200/80';
            }
        } else if (node.node_type === 'form') {
            topBorderColor = 'border-t-sky-500';
            badgeText = 'Lead Form';
            // Input Fields Card
            iconSvg = `<svg class="w-3.5 h-3.5 text-sky-600" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><line x1="5" y1="5.5" x2="11" y2="5.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><line x1="5" y1="8.5" x2="9" y2="8.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="10" cy="10.5" r="1" fill="currentColor"/></svg>`;
            iconBg = 'bg-slate-50 border-slate-200/80';
        } else if (node.node_type === 'video_gate') {
            topBorderColor = 'border-t-rose-500';
            badgeText = 'Video Gate';
            // Playback Video Camera Card
            iconSvg = `<svg class="w-3.5 h-3.5 text-rose-600" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3"/><polygon points="6,5.5 11,8 6,10.5" fill="currentColor"/></svg>`;
            iconBg = 'bg-slate-50 border-slate-200/80';
        }

        div.className = `absolute node-card ${cardWidthClass} rounded-xl bg-white border border-slate-200/90 border-t-2 ${topBorderColor} text-xs select-none cursor-default shadow-xs hover:shadow-md transition-all ${selectedNodeId === node.id ? 'selected' : ''}`;
        div.style.left = `${node.position_x}px`;
        div.style.top = `${node.position_y}px`;

        let bodyHtml = '';
        let statusBadgeHtml = '';

        if (node.node_type === 'start') {
            const hasStartEdge = (graphData.edges || []).some(e => e.source_node_id == node.id);
            statusBadgeHtml = hasStartEdge
                ? `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/60"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Linked</span></span>`
                : `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/70"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span>Unlinked</span></span>`;

            bodyHtml = `
                <div class="p-3 text-[11px] text-slate-500 leading-relaxed">
                    Public Entry Point. Connects visitors to the first step in the funnel.
                </div>
                <div class="px-3 py-2 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between text-[11px] rounded-b-xl relative">
                    <span class="font-medium text-slate-700 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Proceed to flow</span>
                    </span>
                    <div class="port-handle ${hasStartEdge ? 'bg-emerald-500' : 'bg-amber-400'}"
                         data-port-type="output" data-node-id="${node.id}" data-option-id="null" title="${hasStartEdge ? 'Connected to first step' : 'Drag to connect first step'}"></div>
                </div>
            `;
        } else if (node.node_type === 'question') {
            const q = node.question || { question_text: 'Untitled Question', options: [] };
            const inputType = node.input_type || q.question_type || 'single_choice';
            const isSlider = inputType === 'slider';

            if (isSlider) {
                const sliderCfg = node.slider_config || { min_value: 1, max_value: 100, step_value: 1, default_value: 10, unit_label: 'Units', unit_prefix: '' };
                const outgoingEdge = (graphData.edges || []).find(e => e.source_node_id == node.id && (e.source_option_id === null || e.source_option_id === undefined));
                const targetNode = outgoingEdge ? graphData.nodes.find(n => n.id == outgoingEdge.target_node_id) : null;
                const targetLabel = targetNode ? `→ ${targetNode.title || targetNode.node_type}` : null;

                statusBadgeHtml = outgoingEdge 
                    ? `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/60"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Mapped</span></span>`
                    : `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/70"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span>Unlinked</span></span>`;

                bodyHtml = `
                    <div class="p-3">
                        <p class="font-semibold text-xs text-slate-800 line-clamp-2 leading-snug">${escapeHtml(q.question_text || 'Untitled Question')}</p>
                        <div class="mt-2.5 p-2 rounded-lg bg-slate-50 border border-slate-200/70">
                            <div class="flex items-center justify-between text-[10px] text-slate-600 font-medium mb-1.5">
                                <span>Default Preview</span>
                                <span class="px-1.5 py-0.5 rounded bg-emerald-600 text-white font-mono text-[9px] font-semibold">${escapeHtml(sliderCfg.unit_prefix || '')}${sliderCfg.default_value ?? 10} ${escapeHtml(sliderCfg.unit_label || '')}</span>
                            </div>
                            <div class="w-full bg-slate-200/80 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full w-1/2 rounded-full"></div>
                            </div>
                            <div class="flex justify-between text-[9px] text-slate-400 font-mono mt-1.5">
                                <span>Min: ${sliderCfg.min_value ?? 1}</span>
                                <span>Max: ${sliderCfg.max_value ?? 100}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-3 py-2 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between text-[11px] rounded-b-xl relative">
                        <span class="font-medium text-slate-700 flex items-center gap-1.5 truncate max-w-[190px]">
                            <span class="w-1.5 h-1.5 rounded-full ${outgoingEdge ? 'bg-emerald-500' : 'bg-amber-400'} shrink-0"></span>
                            <span class="text-[10px] text-slate-700 truncate">${targetLabel || 'Route on slide'}</span>
                        </span>
                        <div class="port-handle ${outgoingEdge ? 'bg-emerald-500' : 'bg-amber-400'}"
                             data-port-type="output" data-node-id="${node.id}" data-option-id="null" title="${outgoingEdge ? `Connected: ${escapeHtml(targetLabel)}` : 'Drag to connect route'}"></div>
                    </div>
                `;
            } else {
                const options = q.options || [];
                const unmappedCount = options.filter(opt => !(graphData.edges || []).some(e => e.source_node_id == node.id && e.source_option_id == opt.id)).length;
                if (unmappedCount > 0) {
                    statusBadgeHtml = `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/70" title="${unmappedCount} option(s) missing route"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span>${unmappedCount} unlinked</span></span>`;
                } else if (options.length > 0) {
                    statusBadgeHtml = `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/60"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Routed</span></span>`;
                }

                bodyHtml = `
                    <div class="p-3">
                        <p class="font-semibold text-xs text-slate-800 line-clamp-2 leading-snug">${escapeHtml(q.question_text || 'Untitled Question')}</p>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">${inputType === 'multiple_choice' ? 'Multiple Choice' : 'Single Choice'} • ${options.length} options</span>
                    </div>
                    <div class="p-2 border-t border-slate-100 bg-slate-50/50 rounded-b-xl space-y-1.5">
                `;

                if (options.length === 0) {
                    bodyHtml += `<div class="p-2 text-center text-[10px] text-rose-500 font-medium bg-rose-50/60 border border-rose-100 rounded-lg">No options configured yet</div>`;
                } else {
                    options.forEach((opt, idx) => {
                        const edge = (graphData.edges || []).find(e => e.source_node_id == node.id && e.source_option_id == opt.id);
                        const targetNode = edge ? graphData.nodes.find(n => n.id == edge.target_node_id) : null;
                        const targetLabel = targetNode ? `→ ${targetNode.title || targetNode.node_type}` : null;
                        const isCalcMode = window.FUNNEL_MODE === 'calculator';
                        const valDisplay = (isCalcMode && opt.value !== undefined && opt.value !== null && opt.value !== '' && opt.value !== opt.label) 
                            ? `<span class="text-[9px] font-mono text-emerald-700 bg-emerald-50 px-1 py-0.2 rounded border border-emerald-200/60 ml-1">v: ${escapeHtml(opt.value)}</span>` 
                            : '';
                        const letter = String.fromCharCode(65 + (idx % 26));

                        bodyHtml += `
                            <div class="px-2.5 py-1.5 rounded-lg border ${edge ? 'border-slate-200/80 bg-white hover:border-slate-300 shadow-2xs' : 'border-amber-200/70 bg-amber-50/30'} flex items-center justify-between text-[11px] relative group transition-all">
                                <div class="flex items-center truncate max-w-[210px] pr-2">
                                    <span class="w-4 h-4 rounded bg-slate-100 border border-slate-200/90 text-[10px] font-bold text-slate-500 flex items-center justify-center shrink-0 mr-1.5 select-none">${letter}</span>
                                    <div class="truncate">
                                        <div class="flex items-center truncate">
                                            <span class="truncate font-medium text-slate-800 leading-tight">${escapeHtml(opt.label)}</span>
                                            ${valDisplay}
                                        </div>
                                        ${targetLabel ? `<span class="block text-[10px] font-medium text-indigo-600 truncate mt-0.5">${escapeHtml(targetLabel)}</span>` : `<span class="block text-[10px] text-amber-600 font-medium mt-0.5">Route needed</span>`}
                                    </div>
                                </div>
                                <div class="port-handle ${edge ? 'bg-[#5B5BD6]' : 'bg-amber-400'}"
                                     data-port-type="output" data-node-id="${node.id}" data-option-id="${opt.id}" title="${edge ? `Connected: ${escapeHtml(targetLabel)}` : 'Missing route! Drag to target step'}"></div>
                            </div>
                        `;
                    });
                }
                bodyHtml += `</div>`;
            }
        } else if (node.node_type === 'destination') {
            const dest = node.destination || { name: 'Landing Page', url: 'https://example.com' };
            const isCalc = node.calculator && node.calculator.enabled;
            const hasQC = node.quick_connect && node.quick_connect.enabled;
            const destMode = node.destination_mode || (hasQC ? 'direct_connect' : 'redirect');
            const isDirectConnect = destMode === 'direct_connect';
            const incomingCount = (graphData.edges || []).filter(e => e.target_node_id == node.id).length;
            statusBadgeHtml = incomingCount > 0
                ? `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200/60"><span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span><span>${incomingCount} route${incomingCount === 1 ? '' : 's'}</span></span>`
                : `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/70"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span>Unreached</span></span>`;

            const hasValidUrl = dest.url && dest.url.trim() !== '' && dest.url.trim() !== '#' && dest.url.trim() !== 'https://example.com';

            bodyHtml = `
                <div class="p-3 space-y-2.5">
                    <div>
                        <p class="font-semibold text-xs text-slate-800 truncate leading-snug">${escapeHtml(dest.name || node.title || (isDirectConnect ? 'Direct Connect' : 'Landing Destination'))}</p>
                        ${!isDirectConnect ? (hasValidUrl ? `
                            <p class="text-[10px] text-slate-500 truncate mt-0.5 font-mono flex items-center gap-1">
                                <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                <span class="truncate">${escapeHtml(dest.url)}</span>
                            </p>
                        ` : `
                            <p class="text-[10px] text-emerald-700 font-medium mt-0.5 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <span>Direct On-Page Outcome</span>
                            </p>
                        `) : ''}
                    </div>

                    ${isCalc ? `
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="font-semibold text-slate-700 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span>Formula</span>
                                </span>
                                <span class="text-[9px] font-mono font-semibold text-purple-700 bg-purple-50 px-1.5 py-0.2 rounded border border-purple-200/60">
                                    ${escapeHtml(node.calculator.result_prefix || '₹')} Quote
                                </span>
                            </div>
                            <div class="font-mono text-[11px] font-semibold text-slate-800 bg-white px-2 py-1 rounded border border-slate-200/80 truncate">
                                ${escapeHtml(node.calculator.formula_expression || '@Q1 * 100')}
                            </div>
                        </div>
                    ` : ''}

                    ${isDirectConnect ? `
                        <div class="p-2 rounded-lg bg-emerald-50/50 border border-emerald-200/70 space-y-1.5">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="font-semibold text-emerald-900 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>${node.quick_connect.action_type === 'call' ? 'Instant Call' : (node.quick_connect.action_type === 'whatsapp' ? 'WhatsApp Chat' : 'Call & WhatsApp')}</span>
                                </span>
                                <span class="inline-flex items-center gap-1 text-[9px] font-medium text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>1-Click</span>
                                </span>
                            </div>
                            <div class="grid grid-cols-${(node.quick_connect.phone_number && node.quick_connect.whatsapp_number && (node.quick_connect.action_type === 'both' || !node.quick_connect.action_type)) ? '2' : '1'} gap-1.5 text-[10px] font-mono">
                                ${((node.quick_connect.action_type === 'both' || !node.quick_connect.action_type || node.quick_connect.action_type === 'call') && node.quick_connect.phone_number) ? `
                                    <div class="px-2 py-1 rounded bg-white border border-emerald-200/60 text-slate-700 truncate flex items-center gap-1 shadow-2xs">
                                        <svg class="w-2.5 h-2.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span class="truncate font-medium">${escapeHtml(node.quick_connect.phone_number)}</span>
                                    </div>
                                ` : ''}
                                ${((node.quick_connect.action_type === 'both' || !node.quick_connect.action_type || node.quick_connect.action_type === 'whatsapp') && node.quick_connect.whatsapp_number) ? `
                                    <div class="px-2 py-1 rounded bg-white border border-emerald-200/60 text-emerald-800 truncate flex items-center gap-1 shadow-2xs">
                                        <svg class="w-2.5 h-2.5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        <span class="truncate font-medium">${escapeHtml(node.quick_connect.whatsapp_number)}</span>
                                    </div>
                                ` : ''}
                                ${(!node.quick_connect.phone_number && !node.quick_connect.whatsapp_number) ? `
                                    <div class="px-2 py-1 rounded bg-amber-50 border border-amber-200/80 text-amber-700 text-[10px] text-center font-sans font-medium">
                                        Click to configure contact numbers
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    ` : ''}
                </div>
                <div class="px-3 py-2 bg-slate-50/70 border-t border-slate-100 text-[10px] text-slate-500 rounded-b-xl flex items-center justify-between">
                    <span class="flex items-center gap-1 font-medium text-slate-600">Terminal Outcome</span>
                    <span class="font-medium text-slate-700">
                        ${isDirectConnect ? (isCalc ? 'Quote + Direct Connect' : (node.quick_connect.action_type === 'call' ? 'Instant Call' : (node.quick_connect.action_type === 'whatsapp' ? 'WhatsApp Chat' : 'Call & WhatsApp'))) : (isCalc ? 'Quote + Redirect' : 'Redirect Link')}
                    </span>
                </div>
            `;
        } else if (node.node_type === 'form') {
            const form = node.form || {
                fields: [],
                submit_action: 'redirect',
                button_text: 'Submit Details',
                success_heading: 'Thank You!',
                success_message: 'Your submission has been received.'
            };
            const fields = form.fields || [];
            const submitAction = form.submit_action || 'redirect';
            const edge = (graphData.edges || []).find(e => e.source_node_id == node.id);
            const targetNode = edge ? graphData.nodes.find(n => n.id == edge.target_node_id) : null;
            const targetLabel = targetNode ? `→ ${targetNode.title || targetNode.node_type}` : null;

            if (submitAction === 'message') {
                statusBadgeHtml = `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/60"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Displays Message</span></span>`;
            } else if (edge) {
                statusBadgeHtml = `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded border border-sky-200/60"><span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span><span>Linked</span></span>`;
            } else {
                statusBadgeHtml = `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/70"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span>Unlinked</span></span>`;
            }

            bodyHtml = `
                <div class="p-3">
                    <p class="font-semibold text-xs text-slate-800 line-clamp-1">${escapeHtml(node.title || 'Lead Capture Form')}</p>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">${fields.length} field${fields.length === 1 ? '' : 's'} • ${submitAction === 'message' ? 'On-screen thank you' : 'Forward to next step'}</span>
                    
                    <div class="mt-2 space-y-1">
                        ${fields.slice(0, 5).map(f => `
                            <div class="px-2 py-1 rounded-md bg-slate-50 border border-slate-200/60 text-[10px] text-slate-600 flex items-center justify-between">
                                <span class="truncate max-w-[150px] font-medium">${escapeHtml(f.label || f.field_key)}</span>
                                <span class="text-[9px] text-slate-400 font-mono">${escapeHtml(f.field_type || 'text')}${f.is_required ? '*' : ''}</span>
                            </div>
                        `).join('')}
                        ${fields.length > 5 ? `<div class="text-[9px] text-slate-400 text-center font-medium pt-0.5">+${fields.length - 5} more field${fields.length - 5 === 1 ? '' : 's'}</div>` : ''}
                        ${fields.length === 0 ? `<div class="text-[10px] text-amber-600 bg-amber-50/70 border border-amber-200/60 p-1.5 rounded-md text-center">No fields configured yet</div>` : ''}
                    </div>
                </div>
                <div class="px-3 py-2 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between text-[11px] rounded-b-xl relative">
                    <span class="font-medium text-slate-700 flex items-center gap-1.5 truncate max-w-[190px]">
                        ${submitAction === 'message' 
                            ? `<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span><span class="text-[10px] text-emerald-700 font-medium truncate">Displays Thank You</span>`
                            : `<span class="w-1.5 h-1.5 rounded-full ${edge ? 'bg-sky-500' : 'bg-amber-400'} shrink-0"></span><span class="text-[10px] text-slate-700 truncate">${targetLabel || 'Route on submit'}</span>`
                        }
                    </span>
                    ${submitAction === 'redirect' ? `
                        <div class="port-handle ${edge ? 'bg-sky-500' : 'bg-amber-400'}"
                             data-port-type="output" data-node-id="${node.id}" data-option-id="null" title="${edge ? `Connected: ${escapeHtml(targetLabel)}` : 'Drag to connect route'}"></div>
                    ` : ''}
                </div>
            `;
        } else if (node.node_type === 'video_gate') {
            const vg = node.video_gate || {};
            const parsed = parseVideoUrl(vg.video_url || '');
            const prov = parsed.provider || vg.video_provider || 'youtube';
            const gateEnabled = vg.gate_enabled !== false;
            const gateSeconds = parseFloat(vg.gate_timestamp_seconds !== undefined ? vg.gate_timestamp_seconds : 45);
            const gateMin = Math.floor(gateSeconds / 60);
            const gateSec = Math.floor(gateSeconds % 60);
            const gateFormatted = `${String(gateMin).padStart(2, '0')}:${String(gateSec).padStart(2, '0')}s`;
            const gateType = vg.gate_type || 'lead_form';
            const onEnd = vg.on_video_end || 'auto_advance';
            const endAction = vg.end_action || (vg.on_video_end === 'message' || vg.on_video_end === 'thank_you' ? 'thank_you' : 'next_step');

            const edge = (graphData.edges || []).find(e => e.source_node_id == node.id && (e.source_option_id === null || e.source_option_id === undefined));
            const targetNode = edge ? graphData.nodes.find(n => n.id == edge.target_node_id) : null;
            const targetLabel = targetNode ? `→ ${targetNode.title || targetNode.node_type}` : null;

            if (endAction === 'thank_you') {
                statusBadgeHtml = `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/60"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Thank You Screen</span></span>`;
            } else if (gateEnabled) {
                statusBadgeHtml = `<span class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/70"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span>🔒 ${gateFormatted}</span></span>`;
            } else {
                statusBadgeHtml = `<span class="inline-flex items-center gap-1 text-[9px] font-medium text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200"><span>Gate Off</span></span>`;
            }

            let optionsHtml = '';
            if (gateType === 'question') {
                const q = node.question || vg.question || { options: [] };
                const opts = q.options || [];
                optionsHtml = `
                    <div class="mt-2.5 space-y-1.5 border-t border-slate-100 pt-2">
                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-600">
                            <span>Turnstile Choices (${opts.length})</span>
                            <span class="text-[9px] text-slate-400">Routes per option</span>
                        </div>
                        ${opts.map(opt => {
                            const optEdge = (graphData.edges || []).find(e => e.source_node_id == node.id && (e.source_option_id == opt.id || e.source_option_key == opt.option_key));
                            const optTarget = optEdge ? graphData.nodes.find(n => n.id == optEdge.target_node_id) : null;
                            return `
                                <div class="px-2 py-1.5 rounded-md bg-slate-50 border border-slate-200/70 text-[10px] flex items-center justify-between relative group/opt">
                                    <span class="truncate max-w-[130px] font-medium text-slate-700">${escapeHtml(opt.label || 'Choice')}</span>
                                    <span class="text-[9px] ${optTarget ? 'text-indigo-600 font-semibold' : 'text-slate-400'} truncate max-w-[85px]">${optTarget ? escapeHtml(optTarget.title) : 'Default route'}</span>
                                    <div class="port-handle ${optTarget ? 'bg-indigo-500' : 'bg-slate-300'}"
                                         data-port-type="output" data-node-id="${node.id}" data-option-id="${opt.id}" title="${optTarget ? `Connected to: ${escapeHtml(optTarget.title)}` : 'Drag to connect downstream option branch'}"></div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            } else {
                const fields = vg.form_fields || [];
                optionsHtml = `
                    <div class="mt-2 space-y-1">
                        <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium">
                            <span>In-Video Lead Capture Form:</span>
                            <span class="font-bold text-rose-600">${fields.length} field${fields.length === 1 ? '' : 's'}</span>
                        </div>
                        ${fields.slice(0, 3).map(f => `
                            <div class="px-2 py-0.5 rounded bg-slate-50 border border-slate-200/60 text-[9px] text-slate-600 flex items-center justify-between">
                                <span class="truncate max-w-[150px] font-medium">${escapeHtml(f.label || f.field_key)}</span>
                                <span class="text-slate-400 font-mono">${escapeHtml(f.field_type || 'text')}${f.is_required ? '*' : ''}</span>
                            </div>
                        `).join('')}
                        ${fields.length > 3 ? `<div class="text-[9px] text-slate-400 text-center font-medium">+${fields.length - 3} more field${fields.length - 3 === 1 ? '' : 's'}</div>` : ''}
                    </div>
                `;
            }

            bodyHtml = `
                <div class="p-3">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold ${prov === 'vimeo' ? 'bg-sky-100 text-sky-800 border border-sky-300' : 'bg-red-100 text-red-800 border border-red-300'}">
                            <span>${prov === 'vimeo' ? 'Vimeo' : 'YouTube'}</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono truncate max-w-[110px]">${escapeHtml(parsed.id || vg.video_id || 'ID Pending')}</span>
                    </div>
                    <p class="font-semibold text-xs text-slate-800 line-clamp-1">${escapeHtml(node.title || 'Interactive Video Gate')}</p>
                    ${vg.video_headline ? `<p class="text-[10px] text-slate-500 line-clamp-1 mt-0.5 italic">"${escapeHtml(vg.video_headline)}"</p>` : ''}
                    ${optionsHtml}
                </div>
                <div class="px-3 py-2 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between text-[11px] rounded-b-xl relative">
                    <span class="font-medium text-slate-700 flex items-center gap-1.5 truncate max-w-[190px]">
                        <span class="w-1.5 h-1.5 rounded-full ${endAction === 'thank_you' ? 'bg-emerald-500' : (edge ? 'bg-rose-500' : 'bg-amber-400')} shrink-0"></span>
                        <span class="text-[10px] text-slate-700 truncate">${endAction === 'thank_you' ? '🎉 Terminal Thank You Screen' : (targetLabel || (onEnd === 'auto_advance' ? 'Auto-advance on finish' : 'Route after finish CTA'))}</span>
                    </span>
                    ${endAction !== 'thank_you' ? `
                        <div class="port-handle ${edge ? 'bg-rose-500' : 'bg-amber-400'}"
                             data-port-type="output" data-node-id="${node.id}" data-option-id="null" title="${edge ? `Connected: ${escapeHtml(targetLabel)}` : 'Drag to connect downstream flow'}"></div>
                    ` : ''}
                </div>
            `;
        }

        // Left input port handle (for question, form, and destination nodes)
        let inputPortHtml = '';
        if (node.node_type !== 'start') {
            inputPortHtml = `
                <div class="port-handle bg-slate-300 hover:bg-[#5B5BD6]"
                     data-port-type="input" data-node-id="${node.id}" title="Input connection port"></div>
            `;
        }

        // Header right badges (Question variable, Start badge, Route count)
        let rightBadgesHtml = '';
        if (node.is_start && node.node_type !== 'start') {
            rightBadgesHtml += `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-900 text-white tracking-wide">START</span>`;
        }
        if (node.node_type === 'question' && window.FUNNEL_MODE === 'calculator') {
            rightBadgesHtml += `<span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/80" title="Formula Variable Reference">@${escapeHtml(node.variable_key || 'Q1')}</span>`;
        }
        if (statusBadgeHtml) {
            rightBadgesHtml += statusBadgeHtml;
        }

        div.innerHTML = `
            ${inputPortHtml}
            <div class="node-header px-3 py-2 bg-white border-b border-slate-100 rounded-t-xl flex items-center justify-between cursor-move">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-5 h-5 rounded-md ${iconBg} border flex items-center justify-center shrink-0">${iconSvg}</span>
                    <span class="font-semibold text-xs truncate text-slate-800">${escapeHtml(badgeText)}</span>
                </div>
                <div class="flex items-center gap-1.5 shrink-0 ml-2">
                    ${rightBadgesHtml}
                </div>
            </div>
            ${bodyHtml}
        `;

        // Node click & drag event handlers
        div.addEventListener('click', (e) => {
            e.stopPropagation();
            selectNode(node.id);
        });

        const header = div.querySelector('.node-header');
        header.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            draggedNode = node;
            dragStartX = e.clientX;
            dragStartY = e.clientY;
            nodeStartX = node.position_x;
            nodeStartY = node.position_y;
            selectNode(node.id);
        });

        // Enable dropping wire anywhere on valid target node card (not start node)
        if (node.node_type !== 'start') {
            div.addEventListener('mouseup', (e) => {
                if (connectingSource && connectingSource.nodeId !== node.id) {
                    e.stopPropagation();
                    createEdge(connectingSource.nodeId, connectingSource.optionId, node.id);
                    connectingSource = null;
                    tempWire.classList.add('hidden');
                    document.querySelectorAll('.node-card').forEach(c => c.classList.remove('ring-2', 'ring-[#5B5BD6]'));
                }
            });

            div.addEventListener('mouseenter', () => {
                if (connectingSource && connectingSource.nodeId !== node.id) {
                    div.classList.add('ring-2', 'ring-[#5B5BD6]');
                }
            });

            div.addEventListener('mouseleave', () => {
                div.classList.remove('ring-2', 'ring-[#5B5BD6]');
            });
        }

        // Port handle events for wire dragging
        const ports = div.querySelectorAll('.port-handle');
        ports.forEach(p => {
            if (p.dataset.portType === 'output') {
                p.addEventListener('mousedown', (e) => {
                    e.stopPropagation();
                    const portRect = p.getBoundingClientRect();
                    const surfaceRect = surface.getBoundingClientRect();
                    const x = (portRect.left + portRect.width / 2 - surfaceRect.left) / scale;
                    const y = (portRect.top + portRect.height / 2 - surfaceRect.top) / scale;

                    connectingSource = {
                        nodeId: parseInt(p.dataset.nodeId),
                        optionId: p.dataset.optionId === 'null' ? null : parseInt(p.dataset.optionId),
                        x: x,
                        y: y
                    };

                    tempWire.classList.remove('hidden');
                    drawBezier(tempWire, x, y, x, y);
                });
            } else if (p.dataset.portType === 'input') {
                p.addEventListener('mouseup', (e) => {
                    e.stopPropagation();
                    if (connectingSource && connectingSource.nodeId !== node.id) {
                        createEdge(connectingSource.nodeId, connectingSource.optionId, node.id);
                        connectingSource = null;
                        tempWire.classList.add('hidden');
                        document.querySelectorAll('.node-card').forEach(c => c.classList.remove('ring-2', 'ring-[#5B5BD6]'));
                    }
                });
            }
        });

        nodesContainer.appendChild(div);
    }

    // Wires & Bezier Curve Calculations
    function renderWires() {
        wiresGroup.innerHTML = '';
        const surfaceRect = surface.getBoundingClientRect();

        (graphData.edges || []).forEach(edge => {
            const srcNode = graphData.nodes.find(n => n.id == edge.source_node_id);
            const tgtNode = graphData.nodes.find(n => n.id == edge.target_node_id);
            if (!srcNode || !tgtNode) return;

            // Default fallback port handle coordinates
            let srcX = srcNode.position_x + 256;
            let srcY = srcNode.position_y + 40;
            if (srcNode.node_type === 'start') {
                srcY = srcNode.position_y + 55;
            } else if (srcNode.node_type === 'form') {
                srcY = srcNode.position_y + 115;
            } else if (srcNode.node_type === 'question') {
                const q = srcNode.question || {};
                const options = q.options || [];
                const optIndex = options.findIndex(o => o.id == edge.source_option_id);
                if (optIndex >= 0) {
                    srcY = srcNode.position_y + 70 + (optIndex * 34) + 14;
                }
            }

            let tgtX = tgtNode.position_x;
            let tgtY = tgtNode.position_y + 20;

            // Compute exact pixel coordinates directly from DOM port handles
            const srcEl = document.getElementById(`node-${srcNode.id}`);
            if (srcEl && surfaceRect && surfaceRect.width > 0) {
                let portEl = null;
                if (edge.source_option_id) {
                    portEl = srcEl.querySelector(`.port-handle[data-port-type="output"][data-option-id="${edge.source_option_id}"]`);
                } else {
                    portEl = srcEl.querySelector('.port-handle[data-port-type="output"]');
                }
                if (portEl) {
                    const pRect = portEl.getBoundingClientRect();
                    srcX = (pRect.left + pRect.width / 2 - surfaceRect.left) / scale;
                    srcY = (pRect.top + pRect.height / 2 - surfaceRect.top) / scale;
                }
            }

            const tgtEl = document.getElementById(`node-${tgtNode.id}`);
            if (tgtEl && surfaceRect && surfaceRect.width > 0) {
                const inPort = tgtEl.querySelector('.port-handle[data-port-type="input"]');
                if (inPort) {
                    const inRect = inPort.getBoundingClientRect();
                    tgtX = (inRect.left + inRect.width / 2 - surfaceRect.left) / scale;
                    tgtY = (inRect.top + inRect.height / 2 - surfaceRect.top) / scale;
                }
            }

            const isStart = srcNode.node_type === 'start';
            const isForm = srcNode.node_type === 'form';
            const wireColor = selectedEdgeId === edge.id ? '#5B5BD6' : (isStart ? '#10B981' : (isForm ? '#0EA5E9' : '#6366F1'));

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.id = `wire-${edge.id || `${edge.source_node_id}-${edge.target_node_id}`}`;
            path.setAttribute('class', `wire-path pointer-events-auto ${selectedEdgeId === edge.id ? 'selected' : ''}`);
            path.style.stroke = wireColor;
            path.setAttribute('marker-end', selectedEdgeId === edge.id ? 'url(#arrow-selected)' : 'url(#arrow)');
            drawBezier(path, srcX, srcY, tgtX, tgtY);

            path.addEventListener('click', (e) => {
                e.stopPropagation();
                selectEdge(edge);
            });

            // Highlight connected node cards on wire hover
            path.addEventListener('mouseenter', () => {
                const sEl = document.getElementById(`node-${srcNode.id}`);
                const tEl = document.getElementById(`node-${tgtNode.id}`);
                if (sEl) sEl.classList.add('ring-2', 'ring-indigo-300');
                if (tEl) tEl.classList.add('ring-2', 'ring-indigo-300');
            });
            path.addEventListener('mouseleave', () => {
                const sEl = document.getElementById(`node-${srcNode.id}`);
                const tEl = document.getElementById(`node-${tgtNode.id}`);
                if (sEl && selectedNodeId !== srcNode.id) sEl.classList.remove('ring-2', 'ring-indigo-300');
                if (tEl && selectedNodeId !== tgtNode.id) tEl.classList.remove('ring-2', 'ring-indigo-300');
            });

            wiresGroup.appendChild(path);

            if (selectedEdgeId === edge.id) {
                const midX = (srcX + tgtX) / 2;
                const midY = (srcY + tgtY) / 2;
                const delGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                delGroup.setAttribute('class', 'wire-delete-btn pointer-events-auto');
                delGroup.setAttribute('transform', `translate(${midX}, ${midY})`);
                delGroup.style.cursor = 'pointer';
                delGroup.innerHTML = `
                    <circle r="9" fill="#EF4444" stroke="#FFFFFF" stroke-width="2"/>
                    <path d="M -3 -3 L 3 3 M 3 -3 L -3 3" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>
                `;
                delGroup.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteSelectedEdge();
                });
                wiresGroup.appendChild(delGroup);
            }
        });

        updateMinimap();
    }

    function drawBezier(pathEl, x1, y1, x2, y2) {
        const dx = Math.max(Math.abs(x2 - x1) * 0.45, 40);
        const d = `M ${x1} ${y1} C ${x1 + dx} ${y1}, ${x2 - dx} ${y2}, ${x2} ${y2}`;
        pathEl.setAttribute('d', d);
    }

    // Collapsible Sidebars
    window.toggleLeftSidebar = function (forceOpen) {
        const sidebar = document.getElementById('leftSidebar');
        const expandBtn = document.getElementById('leftSidebarExpandBtn');
        if (!sidebar) return;

        const shouldOpen = forceOpen !== undefined ? forceOpen : sidebar.classList.contains('hidden');

        if (shouldOpen) {
            sidebar.classList.remove('hidden');
            if (expandBtn) expandBtn.classList.add('hidden');
        } else {
            sidebar.classList.add('hidden');
            if (expandBtn) expandBtn.classList.remove('hidden');
        }
        setTimeout(renderWires, 60);
    };

    window.toggleInspector = function (forceOpen) {
        const panel = document.getElementById('inspectorPanel');
        const expandBtn = document.getElementById('inspectorExpandBtn');
        if (!panel) return;

        const shouldOpen = forceOpen !== undefined ? forceOpen : panel.classList.contains('hidden');

        if (shouldOpen) {
            panel.classList.remove('hidden');
            if (expandBtn) expandBtn.classList.add('hidden');
        } else {
            panel.classList.add('hidden');
            if (expandBtn) expandBtn.classList.remove('hidden');
        }
        setTimeout(renderWires, 60);
    };

    window.openInspector = function () {
        toggleInspector(true);
    };

    // Node & Edge Selection
    function selectNode(nodeId) {
        const found = findNode(nodeId);
        selectedNodeId = found ? found.id : nodeId;
        selectedEdgeId = null;

        document.querySelectorAll('.node-card').forEach(el => el.classList.remove('selected'));
        const el = document.getElementById(`node-${selectedNodeId}`);
        if (el) el.classList.add('selected');

        openInspector();
        renderWires();
        renderInspector();
    }

    function selectEdge(edge) {
        selectedEdgeId = edge.id;
        selectedNodeId = null;
        document.querySelectorAll('.node-card').forEach(el => el.classList.remove('selected'));
        openInspector();
        renderWires();

        inspectorBadge.textContent = 'Active Wire';
        inspectorBadge.className = 'text-[10px] font-mono px-2 py-0.5 rounded bg-purple-50 text-purple-700 font-semibold uppercase';
        
        const srcNode = graphData.nodes.find(n => n.id == edge.source_node_id);
        const tgtNode = graphData.nodes.find(n => n.id == edge.target_node_id);
        
        let optLabel = '';
        if (edge.source_option_id && srcNode && srcNode.question && srcNode.question.options) {
            const opt = srcNode.question.options.find(o => o.id == edge.source_option_id);
            if (opt) optLabel = opt.label;
        }

        inspectorContent.innerHTML = `
            <div class="space-y-4">
                <div class="p-3 bg-indigo-50/70 border border-indigo-100 rounded-xl space-y-2">
                    <div class="flex items-center gap-1.5 font-bold text-xs text-[#171A21]">
                        <span class="w-2 h-2 rounded-full bg-[#5B5BD6]"></span>
                        <span>Connection Wire Route</span>
                    </div>
                    <div class="space-y-1.5 text-[11px] pt-1">
                        <div class="p-2 bg-white rounded-lg border border-slate-200">
                            <span class="text-[10px] text-[#687080] uppercase tracking-wider block font-bold">Source Step</span>
                            <span class="font-semibold text-[#171A21]">${escapeHtml(srcNode?.title || 'Start')}</span>
                            ${optLabel ? `<span class="block text-[10px] text-indigo-600 mt-0.5 font-medium">Option: "${escapeHtml(optLabel)}"</span>` : ''}
                        </div>
                        <div class="text-center text-slate-400 font-bold text-xs">↓</div>
                        <div class="p-2 bg-white rounded-lg border border-slate-200">
                            <span class="text-[10px] text-[#687080] uppercase tracking-wider block font-bold">Destination Step</span>
                            <span class="font-semibold text-[#171A21]">${escapeHtml(tgtNode?.title || 'Next Node')}</span>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="deleteSelectedEdge()" class="w-full py-2.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 font-semibold text-xs hover:bg-rose-100 flex items-center justify-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Delete Connection (Delete)</span>
                </button>
            </div>
        `;
    }

    function deselectAll() {
        selectedNodeId = null;
        selectedEdgeId = null;
        document.querySelectorAll('.node-card').forEach(el => el.classList.remove('selected'));
        renderWires();
        renderFunnelOverviewInspector();
    }

    function renderFunnelOverviewInspector() {
        inspectorBadge.textContent = 'Overview';
        inspectorBadge.className = 'text-[10px] font-mono px-2 py-0.5 rounded bg-indigo-50 text-[#5B5BD6] font-semibold uppercase';

        const funnel = graphData.funnel || { name: 'My Funnel', slug: 'funnel', status: 'draft' };
        const totalNodes = graphData.nodes ? graphData.nodes.length : 0;
        const questions = (graphData.nodes || []).filter(n => n.node_type === 'question');
        const forms = (graphData.nodes || []).filter(n => n.node_type === 'form');
        const destinations = (graphData.nodes || []).filter(n => n.node_type === 'destination');
        const edges = graphData.edges || [];

        // Check unmapped options & unlinked redirect forms
        const unmappedList = [];
        questions.forEach(qNode => {
            const q = qNode.question || {};
            const options = q.options || [];
            options.forEach(opt => {
                const isMapped = edges.some(e => e.source_node_id == qNode.id && e.source_option_id == opt.id);
                if (!isMapped) {
                    unmappedList.push({
                        nodeId: qNode.id,
                        nodeTitle: qNode.title || 'Question',
                        optionLabel: opt.label
                    });
                }
            });
        });

        forms.forEach(fNode => {
            const form = fNode.form || {};
            if ((form.submit_action || 'redirect') === 'redirect') {
                const isMapped = edges.some(e => e.source_node_id == fNode.id);
                if (!isMapped) {
                    unmappedList.push({
                        nodeId: fNode.id,
                        nodeTitle: fNode.title || 'Form',
                        optionLabel: 'Submit Route'
                    });
                }
            }
        });

        const videoGates = (graphData.nodes || []).filter(n => n.node_type === 'video_gate');
        videoGates.forEach(vgNode => {
            const vg = vgNode.video_gate || {};
            const endAction = vg.end_action || (vg.on_video_end === 'message' || vg.on_video_end === 'thank_you' ? 'thank_you' : 'next_step');
            if (endAction === 'next_step') {
                const isMapped = edges.some(e => e.source_node_id == vgNode.id && (e.source_option_id === null || e.source_option_id === undefined));
                if (!isMapped) {
                    unmappedList.push({
                        nodeId: vgNode.id,
                        nodeTitle: vgNode.title || 'Video Gate',
                        optionLabel: 'Next Step Route'
                    });
                }
            }
        });

        const startNode = (graphData.nodes || []).find(n => n.is_start) || (graphData.nodes || []).find(n => n.node_type !== 'destination');
        const hasStart = !!startNode;

        let healthHtml = '';
        if (unmappedList.length === 0 && hasStart) {
            healthHtml = `
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-xs">
                        <span>✓</span>
                        <span>Flow is 100% Connected</span>
                    </div>
                    <p class="text-[11px] text-emerald-700 leading-relaxed">
                        Starting step is configured and all branching routes lead to valid destinations.
                    </p>
                </div>
            `;
        } else {
            let issuesText = [];
            if (!hasStart) issuesText.push('Please designate a Starting Step for your funnel.');
            if (unmappedList.length > 0) issuesText.push(`${unmappedList.length} step route(s) still unlinked.`);

            healthHtml = `
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs flex items-center gap-1">⚠️ <span>Unfinished Routes (${unmappedList.length + (!hasStart ? 1 : 0)})</span></span>
                        <button type="button" onclick="openValidationDrawer()" class="text-[10px] text-amber-700 underline font-semibold">Inspect</button>
                    </div>
                    <p class="text-[11px] text-amber-800 leading-snug">
                        ${issuesText.join(' ')}
                    </p>
                    ${unmappedList.slice(0, 3).map(item => `
                        <div class="flex items-center justify-between p-1.5 bg-white/70 rounded border border-amber-200/60 text-[10px]">
                            <span class="truncate max-w-[170px]"><strong>${escapeHtml(item.nodeTitle)}</strong>: "${escapeHtml(item.optionLabel)}"</span>
                            <button type="button" onclick="focusNode(${item.nodeId})" class="text-[#5B5BD6] font-semibold hover:underline">Fix →</button>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        inspectorContent.innerHTML = `
            <div class="space-y-4">
                <!-- Funnel Title & Public Link Card -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-[10px] font-bold text-[#687080] uppercase tracking-wider">Active Funnel</span>
                            <h3 class="font-bold text-sm text-[#171A21] truncate max-w-[200px]">${escapeHtml(funnel.name)}</h3>
                        </div>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded flex items-center gap-1 ${(funnel.status === 'published' && !funnel.has_unpublished_changes) ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-300'}">
                            <span class="w-1.5 h-1.5 rounded-full ${(funnel.status === 'published' && !funnel.has_unpublished_changes) ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'}"></span>
                            <span>${(funnel.status === 'published' && !funnel.has_unpublished_changes) ? 'published' : 'not published'}</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-1.5 bg-white rounded-lg border border-slate-200 text-[11px]">
                        <span class="font-mono text-[#687080] truncate text-[10px]">/quiz/${escapeHtml(funnel.slug)}</span>
                        <button type="button" onclick="copyFunnelUrl()" class="px-2 py-0.5 bg-indigo-50 text-[#5B5BD6] rounded font-semibold text-[10px] hover:bg-indigo-100 transition-colors shrink-0">
                            Copy Link
                        </button>
                    </div>

                    ${!(funnel.status === 'published' && !funnel.has_unpublished_changes) ? `
                        <div class="p-2.5 bg-amber-50/90 border border-amber-200 rounded-lg text-amber-900 space-y-1.5 shadow-2xs mt-1">
                            <div class="flex items-center gap-1.5 font-bold text-[11px] text-amber-800">
                                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Changes Saved (Not Published)</span>
                            </div>
                            <p class="text-[10px] text-amber-800/90 leading-tight">
                                Edits are saved in your workspace draft, but will not show in your public link until published.
                            </p>
                            <button type="button" onclick="publishFunnel()" class="w-full py-1 px-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-[11px] font-semibold shadow-xs transition-colors flex items-center justify-center gap-1 mt-1 cursor-pointer">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Publish Version Now</span>
                            </button>
                        </div>
                    ` : ''}
                </div>

                <!-- 4-Stat Metrics Grid -->
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-[#687080] mb-2">Structure Metrics</span>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-2.5 rounded-xl border border-slate-200 bg-white shadow-2xs">
                            <span class="block text-[10px] text-[#687080]">Total Steps</span>
                            <span class="block text-base font-bold text-[#171A21] mt-0.5">${totalNodes}</span>
                        </div>
                        <div class="p-2.5 rounded-xl border border-slate-200 bg-white shadow-2xs">
                            <span class="block text-[10px] text-[#687080]">Questions</span>
                            <span class="block text-base font-bold text-indigo-600 mt-0.5">${questions.length}</span>
                        </div>
                        <div class="p-2.5 rounded-xl border border-slate-200 bg-white shadow-2xs">
                            <span class="block text-[10px] text-[#687080]">Forms</span>
                            <span class="block text-base font-bold text-sky-600 mt-0.5">${forms.length}</span>
                        </div>
                        <div class="p-2.5 rounded-xl border border-slate-200 bg-white shadow-2xs">
                            <span class="block text-[10px] text-[#687080]">Destinations</span>
                            <span class="block text-base font-bold text-purple-600 mt-0.5">${destinations.length}</span>
                        </div>
                    </div>
                </div>

                <!-- Health Diagnostic -->
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-[#687080] mb-2">Flow Health</span>
                    ${healthHtml}
                </div>

                <!-- Quick Canvas Actions -->
                <div class="space-y-2 pt-2 border-t border-slate-200">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-[#687080] mb-1">Canvas Actions</span>
                    
                    <button type="button" onclick="autoArrangeNodes()" class="w-full py-2 px-3 rounded-xl border border-indigo-200 bg-indigo-50/60 text-[#5B5BD6] font-semibold text-xs hover:bg-indigo-100 flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-1.5">
                            <span>✨</span>
                            <span>Auto-Arrange Flow (Tree Layout)</span>
                        </span>
                        <kbd class="font-mono text-[9px] bg-white px-1.5 py-0.5 rounded border border-indigo-200">L</kbd>
                    </button>

                    <button type="button" onclick="centerCanvas()" class="w-full py-2 px-3 rounded-xl border border-slate-200 bg-white text-[#171A21] font-semibold text-xs hover:bg-slate-50 flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-1.5">
                            <span>🎯</span>
                            <span>Center & Fit View</span>
                        </span>
                        <kbd class="font-mono text-[9px] bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">Fit</kbd>
                    </button>

                    <button type="button" onclick="openPreviewModal()" class="w-full py-2 px-3 rounded-xl border border-slate-200 bg-white text-[#171A21] font-semibold text-xs hover:bg-slate-50 flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-1.5">
                            <span>👁️</span>
                            <span>Test Run Live Quiz</span>
                        </span>
                        <kbd class="font-mono text-[9px] bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">P</kbd>
                    </button>
                </div>

                <!-- Pro-Tip Banner -->
                <div class="p-2.5 rounded-xl bg-slate-100/70 border border-slate-200/80 text-[11px] text-[#687080]">
                    <span class="font-semibold text-[#171A21] block mb-0.5">💡 Canvas Tip</span>
                    Click any node to edit questions & answer routes, or drag handles to connect steps.
                </div>
            </div>
        `;
    }

    function getStartStepWidgetHtml(node) {
        if (node.node_type === 'destination') return '';
        return `
            <div class="p-2.5 rounded-xl border ${node.is_start ? 'border-emerald-300 bg-emerald-50/70 ring-1 ring-emerald-200' : 'border-slate-200 bg-slate-50/70'} flex items-center justify-between transition-all">
                <div>
                    <div class="text-xs font-bold ${node.is_start ? 'text-emerald-900' : 'text-slate-800'} flex items-center gap-1.5">
                        ${node.is_start ? '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>' : ''}
                        <span>Funnel Starting Step</span>
                    </div>
                    <div class="text-[10px] ${node.is_start ? 'text-emerald-700' : 'text-slate-500'}">
                        ${node.is_start ? 'Visitors begin their funnel journey here' : 'Not the current starting step'}
                    </div>
                </div>
                ${node.is_start 
                    ? '<span class="px-2 py-0.5 rounded-lg bg-emerald-600 text-white font-bold text-[10px] shadow-xs">★ START</span>'
                    : `<button type="button" onclick="setAsStartNode(${node.id})" class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 hover:border-emerald-500 hover:text-emerald-700 text-slate-700 font-semibold text-[10px] shadow-xs transition-colors">Set as Start</button>`
                }
            </div>
        `;
    }

    window.setAsStartNode = function (nodeId) {
        graphData.nodes.forEach(n => {
            n.is_start = (n.id == nodeId);
        });
        renderCanvas();
        renderInspector();
        markDirty();
        showToast('Starting step updated', 'success');
    };

    // Inspector Panel Rendering
    function renderInspector(preserveScroll = false) {
        window.renderInspector = renderInspector;
        const scrollPos = (preserveScroll && inspectorContent) ? inspectorContent.scrollTop : 0;
        const activeEl = document.activeElement;
        let focusedInfo = null;
        if (inspectorContent && activeEl && inspectorContent.contains(activeEl)) {
            focusedInfo = {
                id: activeEl.id || null,
                oninput: activeEl.getAttribute('oninput'),
                onchange: activeEl.getAttribute('onchange'),
                name: activeEl.name || null,
                selectionStart: activeEl.selectionStart,
                selectionEnd: activeEl.selectionEnd
            };
        }

        const node = findNode(selectedNodeId);
        if (!node) return deselectAll();

        inspectorBadge.textContent = node.node_type;

        if (node.node_type === 'start') {
            const outgoingEdge = (graphData.edges || []).find(e => e.source_node_id == node.id);
            inspectorContent.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Node Title</label>
                        <input type="text" value="${escapeHtml(node.title || '')}" oninput="updateNodeTitle(${node.id}, this.value)"
                               class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs">
                    </div>

                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-[11px]">
                        <strong>Funnel Start:</strong> Visitors enter here after submitting the pre-quiz form. Connect the output handle to your first branching question.
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Connected Next Node</label>
                        <select onchange="updateEdgeTarget(${node.id}, null, this.value)" class="w-full px-2.5 py-1.5 rounded border border-[#E4E7EC] bg-white text-xs">
                            <option value="">-- Disconnected --</option>
                            ${graphData.nodes.filter(n => n.id !== node.id).map(n => `
                                <option value="${n.id}" ${outgoingEdge && outgoingEdge.target_node_id == n.id ? 'selected' : ''}>${escapeHtml(n.title)} (${n.node_type})</option>
                            `).join('')}
                        </select>
                    </div>
                </div>
            `;
        } else if (node.node_type === 'question') {
            const q = node.question || { question_text: '', help_text: '', question_type: 'single_choice', is_required: 1, options: [] };
            const inputType = node.input_type || q.question_type || 'single_choice';
            const isSlider = inputType === 'slider';
            const isCalcMode = window.FUNNEL_MODE === 'calculator';
            const sliderCfg = node.slider_config || { min_value: 1, max_value: 100, step_value: 1, default_value: 10, unit_label: 'Units', unit_prefix: '' };
            const varKey = node.variable_key || 'Q1';

            let optionsHtml = '';
            const options = q.options || [];
            options.forEach((opt, idx) => {
                const currentEdge = (graphData.edges || []).find(e => e.source_node_id == node.id && e.source_option_id == opt.id);
                optionsHtml += `
                    <div class="p-2.5 border border-[#E4E7EC] rounded-lg bg-[#F7F8FA] space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-[10px] text-[#687080]">Option ${idx + 1}</span>
                            <button type="button" onclick="deleteOption(${node.id}, ${opt.id})" class="text-rose-500 hover:text-rose-700 font-bold text-xs" title="Remove Option">✕</button>
                        </div>
                        <div class="grid grid-cols-12 gap-2">
                            <div class="${isCalcMode ? 'col-span-8' : 'col-span-12'}">
                                <label class="block text-[9px] font-semibold text-[#687080] mb-0.5">Label</label>
                                <input type="text" value="${escapeHtml(opt.label)}" placeholder="Option text" oninput="updateOptionLabel(${node.id}, ${opt.id}, this.value)"
                                       class="w-full px-2 py-1 rounded border border-[#E4E7EC] bg-white text-xs font-medium">
                            </div>
                            ${isCalcMode ? `
                            <div class="col-span-4">
                                <label class="block text-[9px] font-semibold text-emerald-800 mb-0.5">Price / Value</label>
                                <input type="text" value="${escapeHtml(opt.value !== undefined ? opt.value : '0')}" placeholder="0" oninput="updateOptionValue(${node.id}, ${opt.id}, this.value)"
                                       class="w-full px-2 py-1 rounded border border-emerald-300 bg-emerald-50/50 text-xs font-mono font-bold text-emerald-800 text-right">
                            </div>
                            ` : ''}
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-[#687080] mb-0.5">Routes to next node:</label>
                            <select onchange="updateEdgeTarget(${node.id}, ${opt.id}, this.value)" class="w-full px-2 py-1 rounded border border-[#E4E7EC] bg-white text-xs font-semibold ${currentEdge ? 'text-[#5B5BD6]' : 'text-amber-600'}">
                                <option value="">⚠️ No route (Pick target)</option>
                                ${graphData.nodes.filter(n => n.id !== node.id).map(n => `
                                    <option value="${n.id}" ${currentEdge && currentEdge.target_node_id == n.id ? 'selected' : ''}>→ ${escapeHtml(n.title)} (${n.node_type})</option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                `;
            });

            // If slider, resolve single outgoing route
            const sliderOutgoingEdge = (graphData.edges || []).find(e => e.source_node_id == node.id && (e.source_option_id === null || e.source_option_id === undefined));

            inspectorContent.innerHTML = `
                <div class="space-y-4">
                    ${getStartStepWidgetHtml(node)}

                    <!-- Formula Variable Reference Card (Calculator Mode Only) -->
                    ${isCalcMode ? `
                    <div class="p-2.5 rounded-xl bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 flex items-center justify-between">
                        <div>
                            <span class="block text-[10px] uppercase font-bold text-indigo-900 tracking-wider">Formula Variable Key</span>
                            <span class="text-[10px] text-indigo-700">Reference in formulas using <strong>@${escapeHtml(varKey)}</strong></span>
                        </div>
                        <div class="flex items-center gap-1 bg-white px-2 py-1 rounded-lg border border-indigo-300 shadow-2xs">
                            <span class="text-xs font-extrabold text-[#5B5BD6]">@</span>
                            <input type="text" value="${escapeHtml(varKey)}" onchange="updateQuestionVariableKey(${node.id}, this.value)"
                                   class="w-14 text-xs font-mono font-extrabold text-indigo-950 uppercase outline-none" title="Edit variable key">
                        </div>
                    </div>
                    ` : ''}

                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Step Title / Identifier</label>
                        <input type="text" value="${escapeHtml(node.title || '')}" oninput="updateNodeTitle(${node.id}, this.value)"
                               class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs">
                    </div>

                    <!-- Question Input Type Selector -->
                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Question Input Type</label>
                        <select onchange="updateQuestionInputType(${node.id}, this.value)" class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold text-[#171A21]">
                            <option value="single_choice" ${inputType === 'single_choice' ? 'selected' : ''}>🔘 Single Choice (Select One Option)</option>
                            <option value="multiple_choice" ${inputType === 'multiple_choice' ? 'selected' : ''}>☑️ Multiple Choice (Select Many Options)</option>
                            <option value="slider" ${inputType === 'slider' ? 'selected' : ''}>🎚️ Number / Range Slider (Visitor Inputs Number)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Question Text *</label>
                        <textarea rows="2" oninput="updateQuestionProperty(${node.id}, 'question_text', this.value)"
                                  class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs">${escapeHtml(q.question_text || '')}</textarea>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Help / Subtitle Text</label>
                        <input type="text" value="${escapeHtml(q.help_text || '')}" oninput="updateQuestionProperty(${node.id}, 'help_text', this.value)"
                               class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs" placeholder="Optional helper note">
                    </div>

                    ${isSlider ? `
                        <!-- Number / Slider Configuration Panel -->
                        <div class="pt-3 border-t border-[#E4E7EC] space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-[#171A21] text-xs flex items-center gap-1.5">
                                    <span>🎚️</span>
                                    <span>Slider Configuration</span>
                                </span>
                                <span class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    ${isCalcMode ? `Value → @${escapeHtml(varKey)}` : 'Numeric Range'}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <label class="block text-[10px] font-semibold text-[#687080] mb-1">Min Value</label>
                                    <input type="number" value="${sliderCfg.min_value ?? 1}" oninput="updateSliderConfig(${node.id}, 'min_value', this.value)"
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] text-xs font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-[#687080] mb-1">Max Value</label>
                                    <input type="number" value="${sliderCfg.max_value ?? 100}" oninput="updateSliderConfig(${node.id}, 'max_value', this.value)"
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] text-xs font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-[#687080] mb-1">Step Increment</label>
                                    <input type="number" value="${sliderCfg.step_value ?? 1}" oninput="updateSliderConfig(${node.id}, 'step_value', this.value)"
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] text-xs font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-[#687080] mb-1">Default Value</label>
                                    <input type="number" value="${sliderCfg.default_value ?? 10}" oninput="updateSliderConfig(${node.id}, 'default_value', this.value)"
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] text-xs font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-[#687080] mb-1">Unit Label (Suffix)</label>
                                    <input type="text" value="${escapeHtml(sliderCfg.unit_label || '')}" placeholder="Pages, Users, Kg" oninput="updateSliderConfig(${node.id}, 'unit_label', this.value)"
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-[#687080] mb-1">Prefix Symbol</label>
                                    <input type="text" value="${escapeHtml(sliderCfg.unit_prefix || '')}" placeholder="₹, $, #" oninput="updateSliderConfig(${node.id}, 'unit_prefix', this.value)"
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] text-xs">
                                </div>
                            </div>

                            <!-- Next Route for Slider -->
                            <div class="pt-2">
                                <label class="block text-[10px] font-semibold text-[#171A21] uppercase mb-1">Connected Next Node</label>
                                <select onchange="updateEdgeTarget(${node.id}, null, this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold ${sliderOutgoingEdge ? 'text-[#5B5BD6]' : 'text-amber-600'}">
                                    <option value="">⚠️ Disconnected (Pick next step)</option>
                                    ${graphData.nodes.filter(n => n.id !== node.id).map(n => `
                                        <option value="${n.id}" ${sliderOutgoingEdge && sliderOutgoingEdge.target_node_id == n.id ? 'selected' : ''}>→ ${escapeHtml(n.title)} (${n.node_type})</option>
                                    `).join('')}
                                </select>
                            </div>
                        </div>
                    ` : `
                        <!-- Choice Branching Options Panel -->
                        <div class="pt-2 border-t border-[#E4E7EC]">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-[#171A21] text-xs">Branching Options (${options.length})</span>
                                <button type="button" onclick="addOption(${node.id})" class="text-xs text-[#5B5BD6] font-semibold hover:underline">
                                    + Add Option
                                </button>
                            </div>
                            ${options.length > 1 ? `
                                <div class="mb-3 p-2 bg-indigo-50/60 rounded-lg border border-indigo-100 flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-semibold text-indigo-900 shrink-0">Set all options to:</span>
                                    <select onchange="if(this.value) setAllOptionsRoute(${node.id}, this.value)" class="text-[11px] px-2 py-1 rounded border border-indigo-200 bg-white font-medium text-[#171A21] w-full">
                                        <option value="">-- Choose common target --</option>
                                        ${graphData.nodes.filter(n => n.id !== node.id).map(n => `
                                            <option value="${n.id}">→ ${escapeHtml(n.title)} (${n.node_type})</option>
                                        `).join('')}
                                    </select>
                                </div>
                            ` : ''}
                            <div class="space-y-2">
                                ${optionsHtml}
                            </div>
                        </div>
                    `}

                    <div class="pt-4 border-t border-[#E4E7EC]">
                        <button type="button" onclick="deleteNode('${node.id}')" class="w-full py-2 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 font-semibold hover:bg-rose-100">
                            Delete Question Node
                        </button>
                    </div>
                </div>
            `;
        } else if (node.node_type === 'destination') {
            const dest = node.destination || { name: 'Landing Page', url: 'https://example.com' };
            if (!node.calculator) {
                node.calculator = {
                    enabled: window.FUNNEL_MODE === 'calculator',
                    formula_expression: '@Q1 * 1000',
                    result_label: 'Your Estimated Project Cost',
                    result_prefix: '₹',
                    result_suffix: '',
                    decimal_places: 0
                };
            }
            const calc = node.calculator;
            const isCalcMode = window.FUNNEL_MODE === 'calculator';

            if (!node.quick_connect) {
                node.quick_connect = {
                    enabled: false,
                    action_type: 'both',
                    phone_number: '',
                    call_button_text: 'Call Specialist Now',
                    whatsapp_number: '',
                    whatsapp_button_text: 'Chat on WhatsApp',
                    whatsapp_message: 'Hi! I just completed the {{funnel_name}} and received my quote: {{formatted_quote}}.'
                };
            }
            if (!node.quick_connect.action_type) {
                node.quick_connect.action_type = 'both';
            }
            if (!node.destination_mode) {
                node.destination_mode = (node.quick_connect && node.quick_connect.enabled) ? 'direct_connect' : 'redirect';
            }
            const isDirectConnect = node.destination_mode === 'direct_connect';
            const qc = node.quick_connect;
            const cleanCallBtnLabel = (qc.call_button_text || 'Call Specialist Now').replace(/^[\s\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}💬📞📱⚡]+/u, '').trim() || 'Call Specialist Now';
            const cleanWaBtnLabel = (qc.whatsapp_button_text || 'Chat on WhatsApp').replace(/^[\s\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}💬📞📱⚡]+/u, '').trim() || 'Chat on WhatsApp';

            // Collect all Question nodes on canvas for clickable variable pills
            const questionNodes = graphData.nodes.filter(n => n.node_type === 'question');

            inspectorContent.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Destination Name</label>
                        <input type="text" value="${escapeHtml(dest.name || '')}" oninput="updateDestinationProperty('${node.id}', 'name', this.value)"
                               class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs">
                    </div>

                    <!-- Mutually Exclusive Option Choice: 1) Redirect Link vs 2) WhatsApp / Call -->
                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1.5">Destination Action Type *</label>
                        <div class="grid grid-cols-2 p-1 rounded-xl bg-slate-100 border border-slate-200/80 text-xs gap-1">
                            <button type="button" onclick="setDestinationMode('${node.id}', 'redirect')"
                                    class="py-2 px-2.5 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer ${!isDirectConnect ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 bg-white/50'}">
                                <svg class="w-3.5 h-3.5 ${!isDirectConnect ? 'text-white' : 'text-slate-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                <span>1) Redirect Link</span>
                            </button>
                            <button type="button" onclick="setDestinationMode('${node.id}', 'direct_connect')"
                                    class="py-2 px-2.5 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer ${isDirectConnect ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 bg-white/50'}">
                                <svg class="w-3.5 h-3.5 ${isDirectConnect ? 'text-white' : 'text-emerald-600'}" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                                <span>2) WhatsApp / Call</span>
                            </button>
                        </div>
                    </div>

                    ${!isDirectConnect ? `
                        <!-- Option 1: External Landing Page Redirect Link -->
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                            <div>
                                <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Landing Page URL *</label>
                                <input type="url" value="${escapeHtml(dest.url || '')}" oninput="updateDestinationProperty('${node.id}', 'url', this.value)"
                                       placeholder="https://yourdomain.com/quote-result" class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-mono">
                                <span class="text-[10px] text-[#687080] block mt-1">Supports dynamic tags: <code class="text-[#5B5BD6] font-mono font-bold">{{calculated_value}}</code> and <code class="text-[#5B5BD6] font-mono font-bold">{{formatted_quote}}</code></span>
                            </div>

                            ${dest.url ? `
                                <div>
                                    <a href="${dest.url}" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-[#5B5BD6] hover:underline font-semibold">
                                        Preview URL in New Tab ↗
                                    </a>
                                </div>
                            ` : ''}
                        </div>
                    ` : `
                        <!-- Option 2: Direct 1-Click WhatsApp & Call Connect Card -->
                        <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/20 shadow-2xs space-y-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-slate-800">Direct Connect</span>
                                    <span class="block text-[10px] text-slate-500">1-click WhatsApp & Phone action</span>
                                </div>
                            </div>

                            <div class="space-y-3 pt-2 border-t border-slate-100">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Action Mode</label>
                                    <div class="grid grid-cols-3 p-1 rounded-xl bg-slate-100 border border-slate-200/80 text-xs">
                                        <!-- Both -->
                                        <button type="button" id="qc_mode_both_${node.id}" onclick="updateDestinationQuickConnect('${node.id}', 'action_type', 'both')"
                                                class="py-1.5 px-2 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer ${qc.action_type === 'both' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'}">
                                            <svg class="w-3.5 h-3.5 pointer-events-none ${qc.action_type === 'both' ? 'text-white' : 'text-amber-500'}" viewBox="0 0 24 24" fill="currentColor"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span class="pointer-events-none">Both</span>
                                        </button>

                                        <!-- WhatsApp -->
                                        <button type="button" id="qc_mode_wa_${node.id}" onclick="updateDestinationQuickConnect('${node.id}', 'action_type', 'whatsapp')"
                                                class="py-1.5 px-2 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer ${qc.action_type === 'whatsapp' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'}">
                                            <svg class="w-3.5 h-3.5 pointer-events-none ${qc.action_type === 'whatsapp' ? 'text-white' : 'text-[#25D366]'}" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                                            <span class="pointer-events-none">WhatsApp</span>
                                        </button>

                                        <!-- Call Only -->
                                        <button type="button" id="qc_mode_call_${node.id}" onclick="updateDestinationQuickConnect('${node.id}', 'action_type', 'call')"
                                                class="py-1.5 px-2 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all cursor-pointer ${qc.action_type === 'call' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'}">
                                            <svg class="w-3.5 h-3.5 pointer-events-none ${qc.action_type === 'call' ? 'text-white' : 'text-emerald-600'}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            <span class="pointer-events-none">Call Only</span>
                                        </button>
                                    </div>
                                </div>

                                ${(qc.action_type === 'both' || qc.action_type === 'call') ? `
                                    <div class="space-y-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                        <div class="flex items-center gap-1.5 text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            <span class="text-xs font-semibold">Phone Call Configuration</span>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-500 mb-1">Phone Number (with Country Code)</label>
                                            <input type="tel" value="${escapeHtml(qc.phone_number || '')}" placeholder="+1 (555) 123-4567 or +91 9876543210"
                                                   oninput="updateDestinationQuickConnect('${node.id}', 'phone_number', this.value)"
                                                   class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-500 mb-1">Call Button Text</label>
                                            <input type="text" value="${escapeHtml(cleanCallBtnLabel)}"
                                                   oninput="updateDestinationQuickConnect('${node.id}', 'call_button_text', this.value)"
                                                   class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors">
                                        </div>
                                    </div>
                                ` : ''}

                                ${(qc.action_type === 'both' || qc.action_type === 'whatsapp') ? `
                                    <div class="space-y-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                        <div class="flex items-center gap-1.5 text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                                            <span class="text-xs font-semibold">WhatsApp Configuration</span>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-500 mb-1">WhatsApp Number (with Country Code)</label>
                                            <input type="tel" value="${escapeHtml(qc.whatsapp_number || '')}" placeholder="+15551234567 or +919876543210"
                                                   oninput="updateDestinationQuickConnect('${node.id}', 'whatsapp_number', this.value)"
                                                   class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-500 mb-1">WhatsApp Button Text</label>
                                            <input type="text" value="${escapeHtml(cleanWaBtnLabel)}"
                                                   oninput="updateDestinationQuickConnect('${node.id}', 'whatsapp_button_text', this.value)"
                                                   class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-500 mb-1">Pre-filled Chat Message</label>
                                            <textarea id="waMessageInput_${node.id}" rows="2"
                                                      oninput="updateDestinationQuickConnect('${node.id}', 'whatsapp_message', this.value)"
                                                      placeholder="Hi! I just got my quote: {{formatted_quote}} on {{funnel_name}}."
                                                      class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors">${escapeHtml(qc.whatsapp_message || '')}</textarea>
                                            <div class="flex flex-wrap gap-1 mt-1.5">
                                                <button type="button" onclick="insertQuickConnectMessageTag('${node.id}', '{{formatted_quote}}')"
                                                        class="px-2 py-0.5 rounded-md bg-slate-200/80 hover:bg-slate-300 text-slate-700 text-[10px] font-mono font-medium transition-colors">
                                                    + {{formatted_quote}}
                                                </button>
                                                <button type="button" onclick="insertQuickConnectMessageTag('${node.id}', '{{funnel_name}}')"
                                                        class="px-2 py-0.5 rounded-md bg-slate-200/80 hover:bg-slate-300 text-slate-700 text-[10px] font-mono font-medium transition-colors">
                                                    + {{funnel_name}}
                                                </button>
                                                <button type="button" onclick="insertQuickConnectMessageTag('${node.id}', '{{destination_name}}')"
                                                        class="px-2 py-0.5 rounded-md bg-slate-200/80 hover:bg-slate-300 text-slate-700 text-[10px] font-mono font-medium transition-colors">
                                                    + {{destination_name}}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}

                                <!-- Live Visitor Preview Chips -->
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                                    <span class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Visitor Preview:</span>
                                    <div class="space-y-1.5">
                                        ${(qc.action_type === 'both' || qc.action_type === 'whatsapp') ? `
                                            <div class="w-full py-2 px-3 rounded-lg bg-[#25D366] hover:bg-[#20bd5a] text-white font-semibold text-xs flex items-center justify-center gap-2 shadow-xs transition-colors">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                                                <span>${escapeHtml(cleanWaBtnLabel)}</span>
                                            </div>
                                        ` : ''}
                                        ${(qc.action_type === 'both' || qc.action_type === 'call') ? `
                                            <div class="w-full py-2 px-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center justify-center gap-2 shadow-xs transition-colors">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                <span>${escapeHtml(cleanCallBtnLabel)}</span>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `}

                    ${(isCalcMode || calc.enabled) ? `
                    <!-- Calculator & Quote Formula Engine Card -->
                    <div class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/40 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" ${calc.enabled ? 'checked' : ''} onchange="toggleDestinationCalculator('${node.id}', this.checked)"
                                       class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500">
                                <span class="font-bold text-xs text-purple-950">🧮 Enable Calculation / Price Quote</span>
                            </label>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded ${calc.enabled ? 'bg-purple-600 text-white' : 'bg-slate-200 text-slate-600'}">
                                ${calc.enabled ? 'ACTIVE' : 'OFF'}
                            </span>
                        </div>

                        ${calc.enabled ? `
                            <div class="space-y-3 pt-2 border-t border-purple-200/80">
                                <div>
                                    <label class="block font-bold text-[10px] uppercase text-purple-900 mb-1">
                                        Formula Expression
                                    </label>
                                    <textarea id="formulaInput_${node.id}" rows="2" oninput="updateDestinationCalculator('${node.id}', 'formula_expression', this.value)"
                                              placeholder="e.g. (@Q1 * 1200) + @Q2 - 500"
                                              class="w-full px-3 py-2 rounded-lg border border-purple-300 bg-white font-mono text-xs font-bold text-purple-900 focus:ring-2 focus:ring-purple-500">${escapeHtml(calc.formula_expression || '')}</textarea>
                                </div>

                                <!-- Clickable Variable Pills -->
                                <div>
                                    <span class="block text-[10px] font-semibold text-purple-900 mb-1.5">Click to insert question variable:</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        ${questionNodes.length === 0 ? `
                                            <span class="text-[10px] text-slate-400 italic">No question nodes on canvas</span>
                                        ` : questionNodes.map(qn => `
                                            <button type="button" onclick="insertFormulaToken('${node.id}', '@${escapeHtml(qn.variable_key || 'Q1')}')"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white border border-indigo-200 text-[#5B5BD6] hover:bg-indigo-50 font-mono text-[11px] font-bold shadow-2xs transition-all hover:scale-105"
                                                    title="${escapeHtml(qn.title || 'Question')}">
                                                <span>@${escapeHtml(qn.variable_key || 'Q1')}</span>
                                                <span class="text-[9px] text-[#687080] font-normal truncate max-w-[80px]">(${escapeHtml(qn.title || 'Q')})</span>
                                            </button>
                                        `).join('')}
                                    </div>
                                    <!-- Math Operator Quick Chips -->
                                    <div class="flex flex-wrap gap-1 mt-1.5 pt-1.5 border-t border-purple-200/60">
                                        ${['+', '-', '*', '/', '(', ')'].map(op => `
                                            <button type="button" onclick="insertFormulaToken('${node.id}', '${op}')"
                                                    class="w-6 h-6 rounded bg-purple-100 hover:bg-purple-200 text-purple-900 font-mono text-xs font-bold flex items-center justify-center transition-colors">
                                                ${op}
                                            </button>
                                        `).join('')}
                                    </div>
                                </div>

                                <!-- Formatting Settings -->
                                <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-[#687080] mb-1">Prefix (Currency)</label>
                                        <input type="text" value="${escapeHtml(calc.result_prefix || '₹')}" oninput="updateDestinationCalculator('${node.id}', 'result_prefix', this.value)"
                                                class="w-full px-2.5 py-1 rounded border border-[#E4E7EC] text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-[#687080] mb-1">Suffix (Unit/Period)</label>
                                        <input type="text" value="${escapeHtml(calc.result_suffix || '')}" placeholder="/mo, Total" oninput="updateDestinationCalculator('${node.id}', 'result_suffix', this.value)"
                                                class="w-full px-2.5 py-1 rounded border border-[#E4E7EC] text-xs">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-semibold text-[#687080] mb-1">Result Label / Heading</label>
                                        <input type="text" value="${escapeHtml(calc.result_label || 'Your Estimated Project Cost')}" oninput="updateDestinationCalculator('${node.id}', 'result_label', this.value)"
                                                class="w-full px-2.5 py-1 rounded border border-[#E4E7EC] text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-[#687080] mb-1">Decimals</label>
                                        <select onchange="updateDestinationCalculator('${node.id}', 'decimal_places', this.value)" class="w-full px-2.5 py-1 rounded border border-[#E4E7EC] text-xs bg-white">
                                            <option value="0" ${(calc.decimal_places ?? 0) == 0 ? 'selected' : ''}>0 (e.g. ₹ 23,500)</option>
                                            <option value="2" ${(calc.decimal_places ?? 0) == 2 ? 'selected' : ''}>2 (e.g. $ 23,500.50)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Live Formula Validator & Mini Test Calculation Preview -->
                                <div id="formulaValidatorBox_${node.id}" class="p-2.5 rounded-lg bg-white border border-purple-200 space-y-1.5 shadow-2xs">
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="font-bold text-slate-500 uppercase tracking-wider">Formula Live Test</span>
                                        <span id="formulaSyntaxStatus_${node.id}" class="font-bold text-emerald-600">✓ Valid Syntax</span>
                                    </div>
                                    <div class="p-2 rounded bg-slate-50 border border-slate-200 flex items-center justify-between">
                                        <span class="text-[11px] text-[#687080]">Sample Output:</span>
                                        <span id="formulaSampleResult_${node.id}" class="text-sm font-extrabold font-mono text-[#5B5BD6]">
                                            Calculating...
                                        </span>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    ` : ''}

                    <div class="pt-4 border-t border-[#E4E7EC]">
                        <button type="button" onclick="deleteNode('${node.id}')" class="w-full py-2 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 font-semibold hover:bg-rose-100">
                            Delete Destination Node
                        </button>
                    </div>
                </div>
            `;

            // Trigger initial live formula validation for destination preview
            if (calc && calc.enabled) {
                setTimeout(() => updateFormulaLiveValidator(node.id, calc.formula_expression), 30);
            }
        } else if (node.node_type === 'form') {
            if (!node.form) {
                node.form = {
                    heading: node.title || 'Lead Capture Form',
                    subheading: node.description !== undefined ? node.description : 'Please fill out the form to proceed.',
                    fields: [],
                    submit_action: 'redirect',
                    button_text: 'Submit Details',
                    success_heading: 'Thank You!',
                    success_message: 'Your submission has been received.'
                };
            }
            if (node.form.heading === undefined) node.form.heading = node.title || 'Lead Capture Form';
            if (node.form.subheading === undefined) node.form.subheading = node.description !== undefined ? node.description : 'Please fill out the form to proceed.';
            if (node.form.button_text === undefined) node.form.button_text = 'Submit Details';
            if (!node.form.fields) node.form.fields = [];

            const form = node.form;
            const fields = form.fields;
            // Ensure every field has a reliable ID
            fields.forEach((f, idx) => {
                if (!f.id) f.id = Date.now() + idx + 1;
            });

            const submitAction = form.submit_action || 'redirect';
            const outgoingEdge = (graphData.edges || []).find(e => e.source_node_id == node.id);

            let fieldsHtml = '';
            fields.forEach((f, idx) => {
                fieldsHtml += `
                    <div class="p-2.5 border border-[#E4E7EC] rounded-xl bg-[#F7F8FA] space-y-2 relative group">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center justify-center">${idx + 1}</span>
                                <span id="field-header-label-${f.id}" class="font-bold text-[10px] text-[#171A21] truncate max-w-[140px]">${escapeHtml(f.label || 'Untitled Field')}</span>
                                ${f.is_required ? '<span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1 rounded">Required</span>' : ''}
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="moveFormField('${node.id}', '${f.id}', 'up')" ${idx === 0 ? 'disabled' : ''} class="p-1 text-slate-400 hover:text-slate-700 disabled:opacity-30 disabled:hover:text-slate-400" title="Move Up">↑</button>
                                <button type="button" onclick="moveFormField('${node.id}', '${f.id}', 'down')" ${idx === fields.length - 1 ? 'disabled' : ''} class="p-1 text-slate-400 hover:text-slate-700 disabled:opacity-30 disabled:hover:text-slate-400" title="Move Down">↓</button>
                                <button type="button" onclick="deleteFormField('${node.id}', '${f.id}')" class="p-1 text-rose-500 hover:text-rose-700 font-bold ml-1" title="Delete Field">✕</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[9px] font-semibold text-[#687080] mb-0.5">Label</label>
                                <input type="text" id="field-label-input-${f.id}" value="${escapeHtml(f.label || '')}" placeholder="Field Label"
                                       oninput="updateFormField('${node.id}', '${f.id}', 'label', this.value)"
                                       class="w-full px-2 py-1 rounded border border-[#E4E7EC] bg-white text-xs">
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-[#687080] mb-0.5">Field Type</label>
                                <select id="field-type-select-${f.id}" onchange="updateFormField('${node.id}', '${f.id}', 'field_type', this.value)"
                                        class="w-full px-2 py-1 rounded border border-[#E4E7EC] bg-white text-xs">
                                    <option value="text" ${f.field_type === 'text' ? 'selected' : ''}>Text Input</option>
                                    <option value="email" ${f.field_type === 'email' ? 'selected' : ''}>Email Address</option>
                                    <option value="phone" ${f.field_type === 'phone' ? 'selected' : ''}>Phone Number</option>
                                    <option value="number" ${f.field_type === 'number' ? 'selected' : ''}>Number</option>
                                    <option value="dropdown" ${f.field_type === 'dropdown' ? 'selected' : ''}>Dropdown Select</option>
                                    <option value="consent" ${f.field_type === 'consent' ? 'selected' : ''}>Consent Checkbox</option>
                                </select>
                            </div>
                        </div>

                        ${f.field_type !== 'consent' ? `
                            <div>
                                <label class="block text-[9px] font-semibold text-[#687080] mb-0.5">Placeholder</label>
                                <input type="text" id="field-placeholder-input-${f.id}" value="${escapeHtml(f.placeholder || '')}" placeholder="Placeholder text"
                                       oninput="updateFormField('${node.id}', '${f.id}', 'placeholder', this.value)"
                                       class="w-full px-2 py-1 rounded border border-[#E4E7EC] bg-white text-xs">
                            </div>
                        ` : ''}

                        <div class="pt-1 flex items-center justify-between text-[10px]">
                            <label class="flex items-center gap-1.5 cursor-pointer text-[#344054]">
                                <input type="checkbox" id="field-required-input-${f.id}" ${f.is_required ? 'checked' : ''}
                                       onchange="updateFormField('${node.id}', '${f.id}', 'is_required', this.checked ? 1 : 0)"
                                       class="rounded text-[#5B5BD6]">
                                <span>Required field</span>
                            </label>
                            <span id="field-key-badge-${f.id}" class="text-slate-400 font-mono text-[9px]">key: ${escapeHtml(f.field_key || '')}</span>
                        </div>
                    </div>
                `;
            });

            inspectorContent.innerHTML = `
                <div class="space-y-4">
                    ${getStartStepWidgetHtml(node)}

                    <!-- Form Header & Copy Settings -->
                    <div class="space-y-3 p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-700">Form Header & Content</span>
                            <span class="text-[9px] font-medium text-slate-400">Visitor Card Display</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Form Heading *</label>
                            <input type="text" value="${escapeHtml(form.heading || node.title || 'Lead Capture Form')}"
                                   oninput="updateFormHeading('${node.id}', this.value)"
                                   placeholder="Lead Capture Form"
                                   class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-[#5B5BD6]">
                        </div>
                        <div>
                            <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Subheading / Description</label>
                            <textarea rows="2"
                                      oninput="updateFormProperty('${node.id}', 'subheading', this.value)"
                                      placeholder="Please fill out the form to proceed."
                                      class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-[#5B5BD6]">${escapeHtml(form.subheading !== undefined ? form.subheading : (form.description !== undefined ? form.description : (node.description || 'Please fill out the form to proceed.')))}</textarea>
                            <span class="text-[10px] text-slate-400 block mt-0.5">Shown right below the heading on the visitor lead capture card.</span>
                        </div>
                    </div>

                    <!-- Post-Submission Decision: Redirect vs No Redirection -->
                    <div class="p-3 bg-sky-50 border border-sky-200/80 rounded-xl space-y-2">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-sky-600"></span>
                            <label class="block font-bold text-sky-950 uppercase text-[10px] tracking-wider">After Form Submission *</label>
                        </div>
                        <p class="text-[11px] text-sky-800 leading-snug">Choose what happens when visitors submit this form:</p>

                        <div class="space-y-1.5 pt-1">
                            <label class="flex items-start gap-2.5 p-2 rounded-xl bg-white border ${submitAction === 'redirect' ? 'border-sky-500 ring-1 ring-sky-300' : 'border-slate-200'} cursor-pointer hover:border-sky-400 transition-all">
                                <input type="radio" name="form_submit_action_${node.id}" value="redirect" ${submitAction === 'redirect' ? 'checked' : ''}
                                       onchange="updateFormProperty('${node.id}', 'submit_action', 'redirect')" class="mt-0.5 text-sky-600">
                                <div>
                                    <span class="block font-bold text-xs text-[#171A21]">Route / Redirect to Next Step</span>
                                    <span class="block text-[10px] text-[#687080] mt-0.5 leading-tight">Proceeds to connected question or destination landing page.</span>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 p-2 rounded-xl bg-white border ${submitAction === 'message' ? 'border-emerald-500 ring-1 ring-emerald-300' : 'border-slate-200'} cursor-pointer hover:border-emerald-400 transition-all">
                                <input type="radio" name="form_submit_action_${node.id}" value="message" ${submitAction === 'message' ? 'checked' : ''}
                                       onchange="updateFormProperty('${node.id}', 'submit_action', 'message')" class="mt-0.5 text-emerald-600">
                                <div>
                                    <span class="block font-bold text-xs text-[#171A21]">Show Thank You Message (No Redirection)</span>
                                    <span class="block text-[10px] text-[#687080] mt-0.5 leading-tight">Acts as final step; displays success confirmation without leaving.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    ${submitAction === 'redirect' ? `
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <label class="block font-semibold text-[#171A21] uppercase text-[10px]">Routes to Next Step</label>
                            <select onchange="updateEdgeTarget('${node.id}', null, this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold ${outgoingEdge ? 'text-sky-600' : 'text-amber-600'}">
                                <option value="">⚠️ No route (Drag wire or pick target)</option>
                                ${graphData.nodes.filter(n => n.id != node.id).map(n => `
                                    <option value="${n.id}" ${outgoingEdge && outgoingEdge.target_node_id == n.id ? 'selected' : ''}>→ ${escapeHtml(n.title)} (${n.node_type})</option>
                                `).join('')}
                            </select>
                        </div>
                    ` : `
                        <div class="p-3 bg-emerald-50/60 border border-emerald-200 rounded-xl space-y-2.5 text-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 block">Thank You Screen Settings</span>
                            <div>
                                <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Confirmation Heading</label>
                                <input type="text" value="${escapeHtml(form.success_heading || 'Thank You!')}"
                                       oninput="updateFormProperty('${node.id}', 'success_heading', this.value)"
                                       placeholder="Thank You!" class="w-full px-2.5 py-1 rounded border border-[#E4E7EC] bg-white text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Confirmation Message</label>
                                <textarea rows="2" oninput="updateFormProperty('${node.id}', 'success_message', this.value)"
                                          placeholder="Your submission has been received."
                                          class="w-full px-2.5 py-1 rounded border border-[#E4E7EC] bg-white text-xs">${escapeHtml(form.success_message || 'Your submission has been received.')}</textarea>
                            </div>
                        </div>
                    `}

                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Submit Button Text</label>
                        <input type="text" value="${escapeHtml(form.button_text || 'Submit Details')}"
                               oninput="updateFormProperty('${node.id}', 'button_text', this.value)"
                               placeholder="Submit Details" class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs">
                    </div>

                    <!-- Fields Section -->
                    <div class="pt-2 border-t border-[#E4E7EC] space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-[#171A21] text-xs">Form Fields (${fields.length})</span>
                            <button type="button" onclick="addFormField('${node.id}', 'text')" class="text-xs text-[#5B5BD6] font-semibold hover:underline">
                                + Custom Field
                            </button>
                        </div>

                        <!-- Quick Presets -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[10px] text-[#687080] font-semibold">Presets:</span>
                            <button type="button" onclick="addFormField('${node.id}', 'name')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-medium text-[#171A21]">+ Name</button>
                            <button type="button" onclick="addFormField('${node.id}', 'email')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-medium text-[#171A21]">+ Email</button>
                            <button type="button" onclick="addFormField('${node.id}', 'phone')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-medium text-[#171A21]">+ Phone</button>
                            <button type="button" onclick="addFormField('${node.id}', 'company')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-medium text-[#171A21]">+ Company</button>
                            <button type="button" onclick="addFormField('${node.id}', 'consent')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-medium text-[#171A21]">+ Consent</button>
                        </div>

                        <div class="space-y-2 pt-1">
                            ${fields.length === 0 ? `
                                <div class="p-3 text-center text-[11px] text-amber-700 bg-amber-50 rounded-xl border border-amber-200">
                                    No fields added yet. Click one of the presets above to add fields to this form.
                                </div>
                            ` : fieldsHtml}
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#E4E7EC]">
                        <button type="button" onclick="deleteNode('${node.id}')" class="w-full py-2 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 font-semibold hover:bg-rose-100 transition-colors">
                            Delete Form Node
                        </button>
                    </div>
                </div>
            `;
        } else if (node.node_type === 'video_gate') {
            if (!node.video_gate) {
                node.video_gate = {
                    video_provider: 'youtube',
                    video_url: '',
                    video_id: '',
                    gate_enabled: true,
                    gate_timestamp_seconds: 45,
                    gate_type: 'lead_form',
                    gate_headline: '🔒 Complete this quick step to continue watching',
                    gate_subheadline: 'Enter your details below to unlock the rest of this video.',
                    gate_button_text: '▶️ Unlock & Continue Video',
                    allow_skip_gate: false,
                    on_video_end: 'auto_advance',
                    form_fields: [],
                    question: { question_text: '', options: [] }
                };
            }
            const vg = node.video_gate;
            const parsed = parseVideoUrl(vg.video_url || '');
            const prov = parsed.provider || vg.video_provider || 'youtube';
            const vidId = parsed.id || vg.video_id || '';
            const gateType = vg.gate_type || 'lead_form';
            const onEnd = vg.on_video_end || 'auto_advance';

            const defaultEdge = (graphData.edges || []).find(e => e.source_node_id == node.id && (e.source_option_id === null || e.source_option_id === undefined));

            inspectorContent.innerHTML = `
                <div class="space-y-4">
                    ${getStartStepWidgetHtml(node)}

                    <div>
                        <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Step Title</label>
                        <input type="text" value="${escapeHtml(node.title || '')}" oninput="updateNodeTitle(${node.id}, this.value)"
                               class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs">
                    </div>

                    <!-- Video Source Settings -->
                    <div class="space-y-3 p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 flex items-center gap-1">
                                <span>🎬</span>
                                <span>Video Source (YouTube / Vimeo Only)</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold ${prov === 'vimeo' ? 'bg-sky-100 text-sky-800' : 'bg-red-100 text-red-800'}">
                                ${prov === 'vimeo' ? 'Vimeo' : 'YouTube'}
                            </span>
                        </div>

                        <div>
                            <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Video URL (YouTube or Vimeo) *</label>
                            <input type="text" value="${escapeHtml(vg.video_url || '')}"
                                   placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..."
                                   oninput="updateVideoGateUrl(${node.id}, this.value)"
                                   class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-mono">
                            <div class="flex items-center justify-between mt-1 text-[10px]">
                                <span class="text-slate-400">Zero file upload • IFrame streaming only</span>
                                <span class="font-mono text-slate-600">${vidId ? `ID: ${escapeHtml(vidId)}` : 'Paste URL to detect'}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Optional Headline Above Player</label>
                            <input type="text" value="${escapeHtml(vg.video_headline || '')}"
                                   placeholder="e.g. Watch this quick 2-minute overview"
                                   oninput="updateVideoGateProperty(${node.id}, 'video_headline', this.value)"
                                   class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs">
                        </div>

                        <div class="pt-1 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-semibold text-slate-800 block text-[11px]">Smart Autoplay</span>
                                <span class="text-[10px] text-slate-400 block leading-tight">Plays automatically (falls back to muted if browser requires)</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" ${vg.autoplay_behavior !== false ? 'checked' : ''}
                                       onchange="updateVideoGateProperty(${node.id}, 'autoplay_behavior', this.checked)"
                                       class="sr-only peer">
                                <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-rose-500"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Mid-Roll Turnstile Gate Configuration -->
                    <div class="space-y-3 p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 flex items-center gap-1">
                                <span>🔒</span>
                                <span>Mid-Roll Gate (Turnstile)</span>
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" ${vg.gate_enabled !== false ? 'checked' : ''}
                                       onchange="updateVideoGateProperty(${node.id}, 'gate_enabled', this.checked)"
                                       class="sr-only peer">
                                <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-amber-500"></div>
                            </label>
                        </div>

                        ${vg.gate_enabled !== false ? `
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Gate Timestamp (Seconds) *</label>
                                    <input type="number" min="0" step="1" value="${vg.gate_timestamp_seconds !== undefined ? vg.gate_timestamp_seconds : 45}"
                                           oninput="updateVideoGateProperty(${node.id}, 'gate_timestamp_seconds', parseFloat(this.value) || 0)"
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-mono font-bold text-amber-700">
                                </div>
                                <div>
                                    <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Turnstile Mode</label>
                                    <select onchange="updateVideoGateProperty(${node.id}, 'gate_type', this.value)"
                                            class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold text-slate-800">
                                        <option value="lead_form" ${gateType === 'lead_form' ? 'selected' : ''}>📋 Lead Capture Form</option>
                                        <option value="question" ${gateType === 'question' ? 'selected' : ''}>❓ Turnstile Question</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Gate Overlay Headline</label>
                                <input type="text" value="${escapeHtml(vg.gate_headline || '🔒 Complete this quick step to continue watching')}"
                                       oninput="updateVideoGateProperty(${node.id}, 'gate_headline', this.value)"
                                       class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Gate Overlay Subheadline</label>
                                <input type="text" value="${escapeHtml(vg.gate_subheadline || 'Enter your details below to unlock the rest of this video.')}"
                                       oninput="updateVideoGateProperty(${node.id}, 'gate_subheadline', this.value)"
                                       class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs">
                            </div>

                            <div>
                                <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Unlock Button Text</label>
                                <input type="text" value="${escapeHtml(vg.gate_button_text || '▶️ Unlock & Continue Video')}"
                                       oninput="updateVideoGateProperty(${node.id}, 'gate_button_text', this.value)"
                                       class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs">
                            </div>

                            <div class="pt-1 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-semibold text-slate-800 block text-[11px]">Allow Skip Gate</span>
                                    <span class="text-[10px] text-slate-400 block leading-tight">Shows a subtle "Skip for now" link</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" ${vg.allow_skip_gate ? 'checked' : ''}
                                           onchange="updateVideoGateProperty(${node.id}, 'allow_skip_gate', this.checked)"
                                           class="sr-only peer">
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-slate-700"></div>
                                </label>
                            </div>
                        ` : `
                            <p class="text-[11px] text-slate-500 italic">Mid-roll gate disabled. Video will play from start to finish without pausing for capture.</p>
                        `}
                    </div>

                    <!-- Turnstile Content (Lead Form Fields OR Question Options) -->
                    ${vg.gate_enabled !== false ? (
                        gateType === 'lead_form' ? `
                            <div class="space-y-3 p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-700">In-Video Form Fields (${(vg.form_fields || []).length})</span>
                                    <button type="button" onclick="addVideoGateField(${node.id}, 'custom')" class="text-xs text-[#5B5BD6] font-semibold hover:underline">+ Custom Field</button>
                                </div>

                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[10px] text-[#687080] font-semibold">Presets:</span>
                                    <button type="button" onclick="addVideoGateField(${node.id}, 'name')" class="px-2 py-0.5 rounded bg-white hover:bg-slate-100 text-[10px] font-medium border border-slate-200 text-[#171A21]">+ Name</button>
                                    <button type="button" onclick="addVideoGateField(${node.id}, 'email')" class="px-2 py-0.5 rounded bg-white hover:bg-slate-100 text-[10px] font-medium border border-slate-200 text-[#171A21]">+ Email</button>
                                    <button type="button" onclick="addVideoGateField(${node.id}, 'phone')" class="px-2 py-0.5 rounded bg-white hover:bg-slate-100 text-[10px] font-medium border border-slate-200 text-[#171A21]">+ Phone</button>
                                    <button type="button" onclick="addVideoGateField(${node.id}, 'company')" class="px-2 py-0.5 rounded bg-white hover:bg-slate-100 text-[10px] font-medium border border-slate-200 text-[#171A21]">+ Company</button>
                                    <button type="button" onclick="addVideoGateField(${node.id}, 'consent')" class="px-2 py-0.5 rounded bg-white hover:bg-slate-100 text-[10px] font-medium border border-slate-200 text-[#171A21]">+ Consent</button>
                                </div>

                                <div class="space-y-2 pt-1">
                                    ${(vg.form_fields || []).map((f, idx) => `
                                        <div class="p-2.5 border border-[#E4E7EC] rounded-xl bg-white space-y-2">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-4 h-4 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold flex items-center justify-center">${idx + 1}</span>
                                                    <span class="font-bold text-[10px] text-[#171A21] truncate max-w-[140px]">${escapeHtml(f.label || 'Untitled Field')}</span>
                                                    ${f.is_required ? '<span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1 rounded">Required</span>' : ''}
                                                </div>
                                                <button type="button" onclick="removeVideoGateField(${node.id}, ${f.id})" class="p-1 text-rose-500 hover:text-rose-700 font-bold ml-1" title="Delete Field">✕</button>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-[9px] font-semibold text-[#687080] mb-0.5">Label</label>
                                                    <input type="text" value="${escapeHtml(f.label || '')}"
                                                           oninput="updateVideoGateField(${node.id}, ${f.id}, 'label', this.value)"
                                                           class="w-full px-2 py-1 rounded border border-[#E4E7EC] bg-white text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-semibold text-[#687080] mb-0.5">Type</label>
                                                    <select onchange="updateVideoGateField(${node.id}, ${f.id}, 'field_type', this.value)"
                                                            class="w-full px-2 py-1 rounded border border-[#E4E7EC] bg-white text-xs">
                                                        <option value="text" ${f.field_type === 'text' ? 'selected' : ''}>Text</option>
                                                        <option value="email" ${f.field_type === 'email' ? 'selected' : ''}>Email</option>
                                                        <option value="phone" ${f.field_type === 'phone' ? 'selected' : ''}>Phone</option>
                                                        <option value="consent" ${f.field_type === 'consent' ? 'selected' : ''}>Consent</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : `
                            <div class="space-y-3 p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-700">Turnstile Choices</span>
                                    <button type="button" onclick="addVideoGateOption(${node.id})" class="text-xs text-[#5B5BD6] font-semibold hover:underline">+ Add Option</button>
                                </div>

                                <div class="space-y-2 pt-1">
                                    ${((node.question || vg.question || {}).options || []).map((opt, idx) => `
                                        <div class="p-2.5 border border-[#E4E7EC] rounded-xl bg-white space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-bold text-slate-700">Choice ${idx + 1}</span>
                                                <button type="button" onclick="removeVideoGateOption(${node.id}, ${opt.id})" class="text-rose-500 font-bold hover:text-rose-700 text-xs">✕</button>
                                            </div>
                                            <input type="text" value="${escapeHtml(opt.label || '')}"
                                                   placeholder="Option text"
                                                   oninput="updateVideoGateOption(${node.id}, ${opt.id}, 'label', this.value)"
                                                   class="w-full px-2.5 py-1 rounded border border-[#E4E7EC] bg-white text-xs">
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `
                    ) : ''}

                    <!-- After Video Action: Next Step OR Thank You -->
                    <div class="space-y-3 p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-700 block">After Video Finishes Action</span>
                        <div class="space-y-1.5 pt-1">
                            <label class="flex items-start gap-2.5 p-2 rounded-xl bg-white border ${endAction === 'next_step' ? 'border-rose-500 ring-1 ring-rose-300' : 'border-slate-200'} cursor-pointer hover:border-rose-400 transition-all">
                                <input type="radio" name="video_end_action_${node.id}" value="next_step" ${endAction === 'next_step' ? 'checked' : ''}
                                       onchange="updateVideoGateProperty(${node.id}, 'end_action', 'next_step')" class="mt-0.5 text-rose-600">
                                <div>
                                    <span class="block font-bold text-xs text-[#171A21]">Route / Redirect to Next Step</span>
                                    <span class="block text-[10px] text-[#687080] mt-0.5 leading-tight">Proceeds to connected question, form, or destination landing page.</span>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 p-2 rounded-xl bg-white border ${endAction === 'thank_you' ? 'border-emerald-500 ring-1 ring-emerald-300' : 'border-slate-200'} cursor-pointer hover:border-emerald-400 transition-all">
                                <input type="radio" name="video_end_action_${node.id}" value="thank_you" ${endAction === 'thank_you' ? 'checked' : ''}
                                       onchange="updateVideoGateProperty(${node.id}, 'end_action', 'thank_you')" class="mt-0.5 text-emerald-600">
                                <div>
                                    <span class="block font-bold text-xs text-[#171A21]">Show Thank You Message (No Redirection)</span>
                                    <span class="block text-[10px] text-[#687080] mt-0.5 leading-tight">Acts as final step; displays success confirmation screen without leaving.</span>
                                </div>
                            </label>
                        </div>

                        ${endAction === 'thank_you' ? `
                            <div class="space-y-2 pt-2 border-t border-slate-200/60">
                                <div>
                                    <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Success Heading</label>
                                    <input type="text" value="${escapeHtml(vg.success_heading || '🎉 Thank You for Watching!')}"
                                           oninput="updateVideoGateProperty(${node.id}, 'success_heading', this.value)"
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs">
                                </div>
                                <div>
                                    <label class="block font-semibold text-[#171A21] text-[10px] mb-1">Success Message</label>
                                    <textarea rows="2" oninput="updateVideoGateProperty(${node.id}, 'success_message', this.value)"
                                              class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs">${escapeHtml(vg.success_message || 'Your submission has been received. We will be in touch shortly.')}</textarea>
                                </div>
                            </div>
                        ` : `
                            <div class="space-y-2 pt-2 border-t border-slate-200/60">
                                <div>
                                    <label class="block font-semibold text-[#171A21] text-[10px] mb-1">When Video Ends</label>
                                    <select onchange="updateVideoGateProperty(${node.id}, 'on_video_end', this.value)"
                                            class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs font-semibold text-slate-800">
                                        <option value="auto_advance" ${onEnd === 'auto_advance' ? 'selected' : ''}>⚡ Auto-advance immediately on finish</option>
                                        <option value="show_cta_button" ${onEnd === 'show_cta_button' ? 'selected' : ''}>🔘 Show CTA Button below video</option>
                                    </select>
                                </div>
                                ${onEnd === 'show_cta_button' ? `
                                    <div>
                                        <label class="block font-semibold text-[#171A21] text-[10px] mb-1">CTA Button Text</label>
                                        <input type="text" value="${escapeHtml(vg.cta_button_text || 'Continue to Next Step ➔')}"
                                               oninput="updateVideoGateProperty(${node.id}, 'cta_button_text', this.value)"
                                               class="w-full px-2.5 py-1.5 rounded-lg border border-[#E4E7EC] bg-white text-xs">
                                    </div>
                                ` : ''}
                            </div>
                        `}
                    </div>

                    ${endAction === 'next_step' && gateType !== 'question' ? `
                        <div>
                            <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Connected Next Node</label>
                            <select onchange="updateEdgeTarget(${node.id}, null, this.value)" class="w-full px-2.5 py-1.5 rounded border border-[#E4E7EC] bg-white text-xs">
                                <option value="">-- Disconnected --</option>
                                ${graphData.nodes.filter(n => n.id !== node.id).map(n => `
                                    <option value="${n.id}" ${defaultEdge && defaultEdge.target_node_id == n.id ? 'selected' : ''}>${escapeHtml(n.title)} (${n.node_type})</option>
                                `).join('')}
                            </select>
                        </div>
                    ` : ''}

                    <div class="pt-4 border-t border-[#E4E7EC]">
                        <button type="button" onclick="deleteNode('${node.id}')" class="w-full py-2 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 font-semibold hover:bg-rose-100 transition-colors">
                            Delete Video Gate Node
                        </button>
                    </div>
                </div>
            `;
        }

        if (preserveScroll && inspectorContent) {
            inspectorContent.scrollTop = scrollPos;
        }

        // Restore active focus and text selection range if preserving view
        if (preserveScroll && focusedInfo) {
            let targetEl = null;
            if (focusedInfo.id) {
                targetEl = inspectorContent.querySelector(`#${CSS.escape(focusedInfo.id)}`);
            }
            if (!targetEl && focusedInfo.name) {
                targetEl = inspectorContent.querySelector(`[name="${CSS.escape(focusedInfo.name)}"]`);
            }
            if (!targetEl && focusedInfo.oninput) {
                targetEl = inspectorContent.querySelector(`[oninput="${CSS.escape(focusedInfo.oninput)}"]`);
            }
            if (!targetEl && focusedInfo.onchange) {
                targetEl = inspectorContent.querySelector(`[onchange="${CSS.escape(focusedInfo.onchange)}"]`);
            }
            if (targetEl) {
                try {
                    targetEl.focus();
                    if (focusedInfo.selectionStart !== null && typeof targetEl.setSelectionRange === 'function') {
                        targetEl.setSelectionRange(focusedInfo.selectionStart, focusedInfo.selectionEnd);
                    }
                } catch (e) {
                    console.warn('Failed to restore focus/selection:', e);
                }
            }
        }
    }
    window.renderInspector = renderInspector;

    // Graph Mutations
    window.addNodeToCanvas = function (type, customX = null, customY = null) {
        // Place in center of current view if not provided
        const canvasX = customX !== null ? customX : Math.max(40, (-panX + 220) / scale);
        const canvasY = customY !== null ? customY : Math.max(40, (-panY + 160) / scale);

        const newNodeId = Date.now(); // temporary unique id for draft
        const nodeKey = `node_${Math.random().toString(36).substring(2, 8)}`;

        let node = {
            id: newNodeId,
            node_key: nodeKey,
            node_type: type,
            title: type === 'start' ? 'Start' : (type === 'question' ? 'New Question' : (type === 'form' ? 'Lead Capture Form' : 'Landing Destination')),
            position_x: Math.round(canvasX),
            position_y: Math.round(canvasY),
        };

        if (type === 'slider') {
            type = 'question';
            const qCount = graphData.nodes.filter(n => n.node_type === 'question').length + 1;
            node.node_type = 'question';
            node.input_type = 'slider';
            node.variable_key = `Q${qCount}`;
            node.title = `Question ${qCount} (Slider)`;
            node.question = {
                question_text: 'Select your desired quantity or range:',
                question_type: 'slider',
                is_required: 1,
                options: []
            };
            node.slider_config = {
                min_value: 1,
                max_value: 100,
                step_value: 1,
                default_value: 10,
                unit_label: 'Units',
                unit_prefix: ''
            };
        } else if (type === 'question') {
            const qCount = graphData.nodes.filter(n => n.node_type === 'question').length + 1;
            node.input_type = 'single_choice';
            node.variable_key = `Q${qCount}`;
            node.question = {
                question_text: 'What is your primary interest?',
                question_type: 'single_choice',
                is_required: 1,
                options: [
                    { id: Date.now() + 1, option_key: `opt_${Math.random().toString(36).substring(2, 8)}`, label: 'Option A', value: '100', display_order: 1 },
                    { id: Date.now() + 2, option_key: `opt_${Math.random().toString(36).substring(2, 8)}`, label: 'Option B', value: '200', display_order: 2 }
                ]
            };
        } else if (type === 'connect' || type === 'quick_connect') {
            type = 'destination';
            node.node_type = 'destination';
            node.title = '📞 1-Click Call & WhatsApp';
            node.destination = {
                name: 'Direct Call & WhatsApp Connect',
                url: '#'
            };
            node.quick_connect = {
                enabled: true,
                action_type: 'both',
                phone_number: '+1 (555) 019-2834',
                call_button_text: 'Call Specialist Now',
                whatsapp_number: '+15550192834',
                whatsapp_button_text: 'Chat on WhatsApp',
                whatsapp_message: 'Hi! I just completed the {{funnel_name}} and received my quote: {{formatted_quote}}.'
            };
            if (window.FUNNEL_MODE === 'calculator') {
                node.calculator = {
                    enabled: true,
                    formula_expression: '@Q1 * 100',
                    result_label: 'Your Estimated Quote',
                    result_prefix: '₹',
                    result_suffix: '',
                    decimal_places: 0
                };
            }
        } else if (type === 'destination') {
            node.destination = {
                name: 'Custom Result Page',
                url: 'https://example.com/result'
            };
            node.quick_connect = {
                enabled: false,
                action_type: 'both',
                phone_number: '',
                call_button_text: 'Call Specialist Now',
                whatsapp_number: '',
                whatsapp_button_text: 'Chat on WhatsApp',
                whatsapp_message: 'Hi! I just completed the {{funnel_name}} and received my quote: {{formatted_quote}}.'
            };
            if (window.FUNNEL_MODE === 'calculator') {
                node.calculator = {
                    enabled: true,
                    formula_expression: '@Q1 * 100',
                    result_label: 'Your Estimated Quote',
                    result_prefix: '₹',
                    result_suffix: '',
                    decimal_places: 0
                };
            }
        } else if (type === 'form') {
            node.form = {
                heading: 'Lead Capture Form',
                subheading: 'Please fill out the form to proceed.',
                fields: [
                    { id: Date.now() + 1, field_key: 'name', label: 'Full Name', field_type: 'text', placeholder: 'Enter your name', is_required: 1 },
                    { id: Date.now() + 2, field_key: 'email', label: 'Email Address', field_type: 'email', placeholder: 'Enter your email', is_required: 1 }
                ],
                submit_action: 'redirect',
                button_text: 'Submit Details',
                success_heading: 'Thank You!',
                success_message: 'Your submission has been received.'
            };
        } else if (type === 'video_gate') {
            node.title = '🎬 Interactive Video Gate';
            node.video_gate = {
                video_provider: 'youtube',
                video_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                video_id: 'dQw4w9WgXcQ',
                video_headline: 'Watch this quick video',
                autoplay_behavior: true,
                gate_enabled: true,
                gate_timestamp_seconds: 45,
                gate_type: 'lead_form',
                gate_headline: '🔒 Complete this quick step to continue watching',
                gate_subheadline: 'Enter your details below to unlock the rest of this video.',
                gate_button_text: '▶️ Unlock & Continue Video',
                allow_skip_gate: false,
                end_action: 'next_step',
                on_video_end: 'auto_advance',
                success_heading: '🎉 Thank You for Watching!',
                success_message: 'Your submission has been received. We will be in touch shortly.',
                cta_button_text: 'Continue to Next Step ➔',
                form_fields: [
                    { id: Date.now() + 1, field_key: 'name', label: 'Full Name', field_type: 'text', is_required: 1 },
                    { id: Date.now() + 2, field_key: 'email', label: 'Work Email', field_type: 'email', is_required: 1 }
                ],
                question: {
                    question_text: 'What is your primary interest?',
                    options: [
                        { id: Date.now() + 1, option_key: `opt_${Math.random().toString(36).substring(2, 8)}`, label: 'Option A', value: 'option_a', display_order: 1 },
                        { id: Date.now() + 2, option_key: `opt_${Math.random().toString(36).substring(2, 8)}`, label: 'Option B', value: 'option_b', display_order: 2 }
                    ]
                }
            };
            node.question = node.video_gate.question;
        }

        // If no node is currently designated as start, make this node the start node
        const hasStart = graphData.nodes.some(n => n.is_start);
        if (!hasStart && type !== 'destination') {
            node.is_start = true;
        }

        graphData.nodes.push(node);
        renderCanvas();
        selectNode(node.id);
        markDirty();
    };

    window.updateNodeTitle = function (nodeId, title) {
        const node = findNode(nodeId);
        if (node) {
            node.title = title;
            if (node.form) {
                node.form.heading = title;
            }
            const el = document.getElementById(`node-${nodeId}`);
            if (el) {
                const titleSpan = el.querySelector('.node-header span.truncate');
                if (titleSpan) titleSpan.textContent = title || (node.node_type === 'form' ? 'Lead Capture Form' : 'Node');
                const formCardTitle = el.querySelector('.p-3 p.font-semibold');
                if (formCardTitle && node.node_type === 'form') formCardTitle.textContent = title || 'Lead Capture Form';
            }
            markDirty();
        }
    };

    window.updateFormHeading = function (nodeId, heading) {
        const node = findNode(nodeId);
        if (node) {
            node.title = heading;
            if (!node.form) node.form = {};
            node.form.heading = heading;
            const el = document.getElementById(`node-${nodeId}`);
            if (el) {
                const titleSpan = el.querySelector('.node-header span.truncate');
                if (titleSpan) titleSpan.textContent = heading || 'Lead Capture Form';
                const formCardTitle = el.querySelector('.p-3 p.font-semibold');
                if (formCardTitle) formCardTitle.textContent = heading || 'Lead Capture Form';
            }
            markDirty();
        }
    };

    window.updateQuestionProperty = function (nodeId, prop, value) {
        const node = findNode(nodeId);
        if (node && node.question) {
            node.question[prop] = value;
            const el = document.getElementById(`node-${nodeId}`);
            if (el && prop === 'question_text') {
                const p = el.querySelector('.line-clamp-2');
                if (p) p.textContent = value;
            }
            markDirty();
        }
    };

    window.updateDestinationProperty = function (nodeId, prop, value) {
        const node = findNode(nodeId);
        if (node && node.destination) {
            node.destination[prop] = value;
            const el = document.getElementById(`node-${nodeId}`);
            if (el) {
                const nameEl = el.querySelector('.p-3 p.font-semibold');
                const urlEl = el.querySelector('.p-3 p.text-\\[10px\\]');
                if (nameEl && prop === 'name') nameEl.textContent = value;
                if (urlEl && prop === 'url') urlEl.textContent = value;
            }
            markDirty();
        }
    };

    window.updateFormProperty = function (nodeId, prop, value) {
        const node = findNode(nodeId);
        if (node) {
            if (!node.form) node.form = {};
            node.form[prop] = value;
            if (prop === 'heading') {
                node.title = value;
                const el = document.getElementById(`node-${nodeId}`);
                if (el) {
                    const titleSpan = el.querySelector('.node-header span.truncate');
                    if (titleSpan) titleSpan.textContent = value || 'Lead Capture Form';
                    const formCardTitle = el.querySelector('.p-3 p.font-semibold');
                    if (formCardTitle) formCardTitle.textContent = value || 'Lead Capture Form';
                }
            } else if (prop === 'subheading') {
                node.description = value;
                node.form.description = value;
            } else if (prop === 'button_text') {
                node.button_text = value;
            } else if (prop === 'submit_action') {
                renderCanvas();
                renderInspector(true);
            }
            markDirty();
        }
    };

    window.addFormField = function (nodeId, presetType = 'text') {
        const node = findNode(nodeId);
        if (!node) {
            console.error('addFormField: node not found for nodeId:', nodeId);
            return;
        }
        if (!node.form) node.form = { fields: [] };
        if (!node.form.fields) node.form.fields = [];

        let label = 'Custom Field';
        let placeholder = '';
        let key = `field_${Date.now().toString(36)}`;
        let type = 'text';

        if (presetType === 'name') {
            label = 'Full Name';
            key = 'name';
            placeholder = 'Enter your name';
            type = 'text';
        } else if (presetType === 'email') {
            label = 'Email Address';
            key = 'email';
            placeholder = 'Enter your email';
            type = 'email';
        } else if (presetType === 'phone') {
            label = 'Phone Number';
            key = 'phone';
            placeholder = '+1 (555) 000-0000';
            type = 'phone';
        } else if (presetType === 'company') {
            label = 'Company';
            key = 'company';
            placeholder = 'Your company name';
            type = 'text';
        } else if (presetType === 'consent') {
            label = 'I agree to the privacy policy & terms';
            key = 'consent';
            type = 'consent';
        }

        // Ensure unique key
        let testKey = key;
        let counter = 1;
        while (node.form.fields.some(f => f.field_key === testKey)) {
            testKey = `${key}_${counter++}`;
        }
        key = testKey;

        const newFieldId = Date.now() + Math.floor(Math.random() * 1000);

        node.form.fields.push({
            id: newFieldId,
            field_key: key,
            label: label,
            field_type: type,
            placeholder: placeholder,
            is_required: presetType === 'consent' ? 0 : 1
        });

        renderCanvas();
        renderInspector(true);
        markDirty();

        showToast(`Added ${label} field`, 'success');

        // Smoothly scroll down so user immediately sees the newly added field
        setTimeout(() => {
            if (inspectorContent) {
                inspectorContent.scrollTo({
                    top: inspectorContent.scrollHeight,
                    behavior: 'smooth'
                });
            }
        }, 50);
    };

    window.updateFormField = function (nodeId, fieldId, prop, value) {
        const node = findNode(nodeId);
        if (node && node.form && node.form.fields) {
            const field = node.form.fields.find(f => f.id == fieldId || f.field_key == fieldId);
            if (field) {
                field[prop] = value;

                if (prop === 'label') {
                    // Update header title in card directly in DOM
                    const headerLabelEl = document.getElementById(`field-header-label-${field.id}`);
                    if (headerLabelEl) {
                        headerLabelEl.textContent = value.trim() ? value : 'Untitled Field';
                    }

                    // Auto-generate key ONLY if existing key is auto-generated or generic AND sanitized value is not empty
                    const sanitized = value.toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/^_+|_+$/g, '').substring(0, 30);
                    if (sanitized && (!field.field_key || field.field_key.startsWith('field_') || field.field_key === 'custom_field')) {
                        field.field_key = sanitized;
                        const keyBadgeEl = document.getElementById(`field-key-badge-${field.id}`);
                        if (keyBadgeEl) {
                            keyBadgeEl.textContent = `key: ${field.field_key}`;
                        }
                    }
                }

                if (prop === 'field_type') {
                    renderInspector(true);
                }

                renderCanvas();
                markDirty();
            }
        }
    };

    window.deleteFormField = function (nodeId, fieldId) {
        const node = findNode(nodeId);
        if (node && node.form && node.form.fields) {
            node.form.fields = node.form.fields.filter(f => f.id != fieldId && f.field_key != fieldId);
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.moveFormField = function (nodeId, fieldId, direction) {
        const node = findNode(nodeId);
        if (!node || !node.form || !node.form.fields) return;
        const index = node.form.fields.findIndex(f => f.id == fieldId || f.field_key == fieldId);
        if (index < 0) return;

        const targetIndex = direction === 'up' ? index - 1 : index + 1;
        if (targetIndex < 0 || targetIndex >= node.form.fields.length) return;

        const [item] = node.form.fields.splice(index, 1);
        node.form.fields.splice(targetIndex, 0, item);

        renderCanvas();
        renderInspector(true);
        markDirty();
    };

    window.addOption = function (nodeId) {
        const node = findNode(nodeId);
        if (node && node.question) {
            const optId = Date.now();
            node.question.options.push({
                id: optId,
                option_key: `opt_${Math.random().toString(36).substring(2, 6)}`,
                label: `Option ${(node.question.options.length + 1)}`,
                value: `opt_val_${(node.question.options.length + 1)}`,
                display_order: node.question.options.length + 1
            });
            renderCanvas();
            renderInspector();
            markDirty();
        }
    };

    window.updateOptionLabel = function (nodeId, optionId, label) {
        const node = graphData.nodes.find(n => n.id === nodeId);
        if (node && node.question) {
            const opt = node.question.options.find(o => o.id === optionId);
            if (opt) {
                opt.label = label;
                if (opt.value === undefined || opt.value === null || opt.value === '') {
                    opt.value = label;
                }
                renderCanvas();
                markDirty();
            }
        }
    };

    window.updateOptionValue = function (nodeId, optionId, val) {
        const node = graphData.nodes.find(n => n.id === nodeId);
        if (node && node.question) {
            const opt = node.question.options.find(o => o.id === optionId);
            if (opt) {
                opt.value = val;
                markDirty();
            }
        }
    };

    window.updateQuestionVariableKey = function (nodeId, key) {
        const node = graphData.nodes.find(n => n.id === nodeId);
        if (node) {
            const cleanKey = (key || '').replace(/^@+/, '').replace(/[^A-Za-z0-9_]/g, '').toUpperCase();
            node.variable_key = cleanKey || 'Q1';
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.updateQuestionInputType = function (nodeId, inputType) {
        const node = graphData.nodes.find(n => n.id === nodeId);
        if (node) {
            node.input_type = inputType;
            if (node.question) {
                node.question.question_type = inputType;
            }
            if (inputType === 'slider') {
                if (!node.slider_config) {
                    node.slider_config = {
                        min_value: 1,
                        max_value: 100,
                        step_value: 1,
                        default_value: 10,
                        unit_label: 'Units',
                        unit_prefix: ''
                    };
                }
            } else {
                if (!node.question) node.question = { question_text: 'Choose an option', question_type: inputType, options: [] };
                if (!node.question.options || node.question.options.length === 0) {
                    node.question.options = [
                        { id: Date.now() + 1, option_key: `opt_${Math.random().toString(36).substring(2, 6)}`, label: 'Option 1', value: '100', display_order: 1 },
                        { id: Date.now() + 2, option_key: `opt_${Math.random().toString(36).substring(2, 6)}`, label: 'Option 2', value: '200', display_order: 2 }
                    ];
                }
            }
            renderCanvas();
            renderInspector();
            markDirty();
        }
    };

    window.updateSliderConfig = function (nodeId, field, val) {
        const node = graphData.nodes.find(n => n.id === nodeId);
        if (node) {
            if (!node.slider_config) node.slider_config = {};
            if (['min_value', 'max_value', 'step_value', 'default_value'].includes(field)) {
                node.slider_config[field] = parseFloat(val) || 0;
            } else {
                node.slider_config[field] = val;
            }
            renderCanvas();
            markDirty();
        }
    };

    window.setDestinationMode = function (nodeId, mode) {
        const node = findNode(nodeId);
        if (node) {
            node.destination_mode = mode;
            if (!node.quick_connect) {
                node.quick_connect = {
                    enabled: false,
                    action_type: 'both',
                    phone_number: '',
                    call_button_text: 'Call Specialist Now',
                    whatsapp_number: '',
                    whatsapp_button_text: 'Chat on WhatsApp',
                    whatsapp_message: 'Hi! I just completed the {{funnel_name}} and received my quote: {{formatted_quote}}.'
                };
            }
            if (mode === 'direct_connect') {
                node.quick_connect.enabled = true;
            } else {
                node.quick_connect.enabled = false;
            }
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.toggleDestinationQuickConnect = function (nodeId, enabled) {
        const node = findNode(nodeId);
        if (node) {
            if (!node.quick_connect) {
                node.quick_connect = {
                    enabled: false,
                    action_type: 'both',
                    phone_number: '',
                    call_button_text: 'Call Specialist Now',
                    whatsapp_number: '',
                    whatsapp_button_text: 'Chat on WhatsApp',
                    whatsapp_message: 'Hi! I just completed the {{funnel_name}} and received my quote: {{formatted_quote}}.'
                };
            }
            node.quick_connect.enabled = !!enabled;
            node.destination_mode = enabled ? 'direct_connect' : 'redirect';
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.updateDestinationQuickConnect = function (nodeId, field, val) {
        const node = findNode(nodeId);
        if (node) {
            if (!node.quick_connect) node.quick_connect = {};
            node.quick_connect[field] = val;
            renderCanvas();
            if (field === 'action_type') {
                renderInspector(true);
            }
            markDirty();
        }
    };

    window.insertQuickConnectMessageTag = function (nodeId, tag) {
        const input = document.getElementById(`waMessageInput_${nodeId}`);
        const node = findNode(nodeId);
        if (!node) return;
        if (!node.quick_connect) node.quick_connect = {};

        if (input) {
            const start = input.selectionStart || 0;
            const end = input.selectionEnd || 0;
            const text = input.value || '';
            const toInsert = ` ${tag} `;
            const newText = text.substring(0, start) + toInsert + text.substring(end);
            input.value = newText.replace(/\s+/g, ' ').trim();
            node.quick_connect.whatsapp_message = input.value;
            input.focus();
            const newPos = start + toInsert.length;
            input.setSelectionRange(newPos, newPos);
        } else {
            const cur = node.quick_connect.whatsapp_message || '';
            node.quick_connect.whatsapp_message = (cur + ' ' + tag).trim();
        }
        markDirty();
    };

    window.toggleDestinationCalculator = function (nodeId, enabled) {
        const node = findNode(nodeId);
        if (node) {
            if (!node.calculator) {
                node.calculator = {
                    enabled: false,
                    formula_expression: '@Q1 * 100',
                    result_label: 'Your Estimated Quote',
                    result_prefix: '₹',
                    result_suffix: '',
                    decimal_places: 0
                };
            }
            node.calculator.enabled = !!enabled;
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.updateDestinationCalculator = function (nodeId, field, val) {
        const node = findNode(nodeId);
        if (node) {
            if (!node.calculator) node.calculator = {};
            if (field === 'decimal_places') {
                node.calculator[field] = parseInt(val, 10) || 0;
            } else {
                node.calculator[field] = val;
            }
            if (field === 'formula_expression') {
                updateFormulaLiveValidator(nodeId, val);
            }
            renderCanvas();
            markDirty();
        }
    };

    window.insertFormulaToken = function (nodeId, token) {
        const input = document.getElementById(`formulaInput_${nodeId}`);
        const node = findNode(nodeId);
        if (!node) return;
        if (!node.calculator) node.calculator = {};

        if (input) {
            const start = input.selectionStart || 0;
            const end = input.selectionEnd || 0;
            const text = input.value || '';
            const toInsert = (token.startsWith('@') || ['+', '-', '*', '/', '(', ')'].includes(token)) ? ` ${token} ` : token;
            const newText = text.substring(0, start) + toInsert + text.substring(end);
            input.value = newText.replace(/\s+/g, ' ').trim();
            node.calculator.formula_expression = input.value;
            input.focus();
            const newPos = start + toInsert.length;
            input.setSelectionRange(newPos, newPos);
        } else {
            const cur = node.calculator.formula_expression || '';
            node.calculator.formula_expression = (cur + ' ' + token).trim();
        }
        updateFormulaLiveValidator(nodeId, node.calculator.formula_expression);
        renderCanvas();
        markDirty();
    };

    window.switchFunnelMode = async function (newMode) {
        if (window.FUNNEL_MODE === newMode) return;
        const selector = document.getElementById('funnelModeSelector');
        const iconEl = document.getElementById('funnelModeIcon');
        const prevMode = window.FUNNEL_MODE;

        try {
            const res = await fetch(`${window.API_BASE}/api/funnels/${window.FUNNEL_ID}/mode`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.CSRF_TOKEN
                },
                body: JSON.stringify({ funnel_mode: newMode })
            });
            const data = await res.json();
            if (data.success || data.status === 'success') {
                window.FUNNEL_MODE = newMode;
                if (graphData.funnel) graphData.funnel.funnel_mode = newMode;
                if (selector) selector.value = newMode;
                
                const btnQuiz = document.getElementById('modeBtnQuiz');
                const btnCalc = document.getElementById('modeBtnCalc');
                if (btnQuiz && btnCalc) {
                    if (newMode === 'calculator') {
                        btnCalc.className = 'px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1.5 shrink-0 whitespace-nowrap bg-white text-slate-800 shadow-2xs';
                        btnQuiz.className = 'px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1.5 shrink-0 whitespace-nowrap text-slate-500 hover:text-slate-800';
                    } else {
                        btnQuiz.className = 'px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1.5 shrink-0 whitespace-nowrap bg-white text-slate-800 shadow-2xs';
                        btnCalc.className = 'px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all flex items-center gap-1.5 shrink-0 whitespace-nowrap text-slate-500 hover:text-slate-800';
                    }
                }

                if (iconEl) {
                    iconEl.innerHTML = newMode === 'calculator' 
                        ? `<svg class="w-3.5 h-3.5 text-purple-600" viewBox="0 0 16 16" fill="none"><rect x="2.5" y="2" width="11" height="12" rx="2" stroke="currentColor" stroke-width="1.3"/><rect x="4.5" y="4" width="7" height="2.5" rx="0.5" stroke="currentColor" stroke-width="1"/><path d="M5 9h1.5M8 9h1.5M5 11.5h1.5M8 11.5h1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>`
                        : `<svg class="w-3.5 h-3.5 text-indigo-600" viewBox="0 0 16 16" fill="none"><rect x="2" y="2.5" width="12" height="4.5" rx="1.5" stroke="currentColor" stroke-width="1.3"/><rect x="2" y="9" width="12" height="4.5" rx="1.5" stroke="currentColor" stroke-width="1.3"/><circle cx="4.5" cy="4.75" r="1" fill="currentColor"/><circle cx="4.5" cy="11.25" r="1" fill="currentColor"/></svg>`;
                }
                const sliderSub = document.getElementById('sliderPaletteSubtitle');
                if (sliderSub) sliderSub.textContent = newMode === 'calculator' ? 'Numeric quantity & variable' : 'Numeric quantity & range';
                showToast(`Switched to ${newMode === 'calculator' ? 'Calculator & Quote' : 'Lead & Quiz'} Funnel`);
                renderCanvas();
                if (selectedNodeId) {
                    renderInspector(true);
                } else {
                    renderFunnelOverviewInspector();
                }
            } else {
                if (selector) selector.value = prevMode;
                showToast(data.error || data.message || 'Failed to update funnel mode', '⚠️');
            }
        } catch (e) {
            console.error('Mode switch error:', e);
            if (selector) selector.value = prevMode;
            showToast('Network error while switching funnel mode', '⚠️');
        }
    };

    // Safe Sandboxed Mathematical Evaluator (No eval())
    window.safeEvaluateMath = function (expr, variables = {}) {
        if (!expr || typeof expr !== 'string') return 0;
        
        // Clean and replace variables with numeric values
        let parsed = expr.replace(/@([A-Za-z0-9_]+)/g, (match, vKey) => {
            const val = variables[vKey] !== undefined ? variables[vKey] : 0;
            const num = parseFloat(val);
            return isNaN(num) ? '0' : String(num);
        });

        // Strip spaces
        parsed = parsed.replace(/\s+/g, '');
        if (!parsed) return 0;

        // Ensure safe mathematical characters only
        if (!/^[0-9.+\-*/%^()]+$/.test(parsed)) {
            throw new Error('Disallowed characters in formula expression');
        }

        // Tokenize
        const tokens = [];
        let i = 0;
        while (i < parsed.length) {
            const ch = parsed[i];
            if ('+-*/%^()'.includes(ch)) {
                // Handle unary minus
                if (ch === '-' && (tokens.length === 0 || '+-*/%^('.includes(tokens[tokens.length - 1]))) {
                    let numStr = '-';
                    i++;
                    while (i < parsed.length && /[0-9.]/.test(parsed[i])) {
                        numStr += parsed[i];
                        i++;
                    }
                    if (numStr === '-') throw new Error('Incomplete unary minus');
                    tokens.push(parseFloat(numStr));
                    continue;
                } else {
                    tokens.push(ch);
                    i++;
                }
            } else if (/[0-9.]/.test(ch)) {
                let numStr = '';
                while (i < parsed.length && /[0-9.]/.test(parsed[i])) {
                    numStr += parsed[i];
                    i++;
                }
                tokens.push(parseFloat(numStr));
            } else {
                throw new Error(`Unexpected character: ${ch}`);
            }
        }

        // Shunting-Yard Infix to RPN
        const precedence = { '+': 1, '-': 1, '*': 2, '/': 2, '%': 2, '^': 3 };
        const associativity = { '+': 'L', '-': 'L', '*': 'L', '/': 'L', '%': 'L', '^': 'R' };
        const outputQueue = [];
        const opStack = [];

        for (const token of tokens) {
            if (typeof token === 'number') {
                outputQueue.push(token);
            } else if (token in precedence) {
                while (
                    opStack.length > 0 &&
                    opStack[opStack.length - 1] in precedence &&
                    (
                        (associativity[token] === 'L' && precedence[token] <= precedence[opStack[opStack.length - 1]]) ||
                        (associativity[token] === 'R' && precedence[token] < precedence[opStack[opStack.length - 1]])
                    )
                ) {
                    outputQueue.push(opStack.pop());
                }
                opStack.push(token);
            } else if (token === '(') {
                opStack.push(token);
            } else if (token === ')') {
                while (opStack.length > 0 && opStack[opStack.length - 1] !== '(') {
                    outputQueue.push(opStack.pop());
                }
                if (opStack.length === 0) throw new Error('Unbalanced parentheses');
                opStack.pop();
            }
        }

        while (opStack.length > 0) {
            const top = opStack.pop();
            if (top === '(' || top === ')') throw new Error('Unbalanced parentheses');
            outputQueue.push(top);
        }

        // RPN evaluation
        const evalStack = [];
        for (const token of outputQueue) {
            if (typeof token === 'number') {
                evalStack.push(token);
            } else {
                if (evalStack.length < 2) throw new Error('Invalid math expression syntax');
                const b = evalStack.pop();
                const a = evalStack.pop();
                let res = 0;
                switch (token) {
                    case '+': res = a + b; break;
                    case '-': res = a - b; break;
                    case '*': res = a * b; break;
                    case '/':
                        if (b === 0) throw new Error('Division by zero');
                        res = a / b;
                        break;
                    case '%': res = a % b; break;
                    case '^': res = Math.pow(a, b); break;
                }
                evalStack.push(res);
            }
        }

        if (evalStack.length !== 1) throw new Error('Syntax error in formula');
        const finalVal = evalStack[0];
        return (isNaN(finalVal) || !isFinite(finalVal)) ? 0 : finalVal;
    };

    window.updateFormulaLiveValidator = function (nodeId, formula) {
        const node = findNode(nodeId);
        const statusEl = document.getElementById(`formulaSyntaxStatus_${nodeId}`);
        const resultEl = document.getElementById(`formulaSampleResult_${nodeId}`);
        if (!node || !statusEl || !resultEl) return;

        if (!formula || !formula.trim()) {
            statusEl.textContent = '⚠️ Formula expression is empty';
            statusEl.className = 'font-bold text-amber-600';
            resultEl.textContent = '--';
            return;
        }

        // Gather mock variable values from question nodes
        const mockVars = {};
        (graphData.nodes || []).filter(n => n.node_type === 'question').forEach(qn => {
            const vKey = qn.variable_key || 'Q1';
            if (qn.input_type === 'slider') {
                mockVars[vKey] = qn.slider_config?.default_value ?? 10;
            } else {
                const firstOpt = qn.question?.options?.[0];
                mockVars[vKey] = firstOpt ? (parseFloat(firstOpt.value) || 100) : 50;
            }
        });

        try {
            const val = window.safeEvaluateMath(formula, mockVars);
            const calc = node.calculator || {};
            const decimals = calc.decimal_places ?? 0;
            const prefix = calc.result_prefix ?? '';
            const suffix = calc.result_suffix ?? '';
            const formatted = `${prefix} ${Number(val).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals })} ${suffix}`.trim();

            statusEl.textContent = '✓ Valid Formula';
            statusEl.className = 'font-bold text-emerald-600';
            resultEl.textContent = formatted;
        } catch (e) {
            statusEl.textContent = `⚠️ ${e.message}`;
            statusEl.className = 'font-bold text-rose-600';
            resultEl.textContent = 'Syntax Error';
        }
    };

    window.deleteOption = function (nodeId, optionId) {
        const node = findNode(nodeId);
        if (node && node.question) {
            node.question.options = (node.question.options || []).filter(o => o.id != optionId);
            // Remove connected edges
            graphData.edges = (graphData.edges || []).filter(e => !(e.source_node_id == nodeId && e.source_option_id == optionId));
            renderCanvas();
            renderInspector();
            markDirty();
        }
    };

    window.setAllOptionsRoute = function (nodeId, targetNodeId) {
        const node = graphData.nodes.find(n => n.id == nodeId);
        if (!node || !node.question || !node.question.options) return;

        const tgtN = graphData.nodes.find(n => n.id == targetNodeId);

        node.question.options.forEach(opt => {
            // Remove existing edge for this option
            graphData.edges = (graphData.edges || []).filter(e => !(e.source_node_id == nodeId && e.source_option_id == opt.id));

            if (targetNodeId && targetNodeId !== '') {
                graphData.edges.push({
                    id: Date.now() + Math.floor(Math.random() * 10000),
                    source_node_id: parseInt(nodeId),
                    source_node_key: node.node_key,
                    source_option_id: parseInt(opt.id),
                    source_option_key: opt.option_key,
                    target_node_id: parseInt(targetNodeId),
                    target_node_key: tgtN ? tgtN.node_key : null
                });
            }
        });

        renderCanvas();
        renderInspector();
        markDirty();
    };

    window.updateEdgeTarget = function (sourceNodeId, sourceOptionId, targetNodeId) {
        // Remove existing edge from this source & option
        graphData.edges = (graphData.edges || []).filter(e => {
            if (sourceOptionId) {
                return !(e.source_node_id == sourceNodeId && e.source_option_id == sourceOptionId);
            } else {
                return !(e.source_node_id == sourceNodeId && !e.source_option_id);
            }
        });

        if (targetNodeId && targetNodeId !== '') {
            const srcN = graphData.nodes.find(n => n.id == sourceNodeId);
            const tgtN = graphData.nodes.find(n => n.id == targetNodeId);
            let optKey = null;
            if (srcN && srcN.question && srcN.question.options) {
                const optObj = srcN.question.options.find(o => o.id == sourceOptionId);
                if (optObj) optKey = optObj.option_key;
            }

            graphData.edges.push({
                id: Date.now(),
                source_node_id: parseInt(sourceNodeId),
                source_node_key: srcN ? srcN.node_key : null,
                source_option_id: sourceOptionId ? parseInt(sourceOptionId) : null,
                source_option_key: optKey,
                target_node_id: parseInt(targetNodeId),
                target_node_key: tgtN ? tgtN.node_key : null
            });
        }

        renderCanvas();
        renderInspector();
        markDirty();
    };

    function createEdge(sourceNodeId, sourceOptionId, targetNodeId) {
        updateEdgeTarget(sourceNodeId, sourceOptionId, targetNodeId);
    }

    window.deleteSelectedEdge = function () {
        if (selectedEdgeId) {
            graphData.edges = (graphData.edges || []).filter(e => e.id !== selectedEdgeId);
            selectedEdgeId = null;
            renderCanvas();
            deselectAll();
            markDirty();
        }
    };

    // Custom Beautified Confirm & Alert Dialogs
    window.showConfirmDialog = function ({ title = 'Confirm Action', message = 'Are you sure?', confirmText = 'Confirm', cancelText = 'Cancel', type = 'danger' }) {
        return new Promise((resolve) => {
            const modal = document.getElementById('customDialogModal');
            const card = document.getElementById('customDialogCard');
            const titleEl = document.getElementById('customDialogTitle');
            const msgEl = document.getElementById('customDialogMessage');
            const confirmBtn = document.getElementById('customDialogConfirmBtn');
            const cancelBtn = document.getElementById('customDialogCancelBtn');
            const iconContainer = document.getElementById('customDialogIconContainer');
            const dangerIcon = document.getElementById('customDialogDangerIcon');
            const warningIcon = document.getElementById('customDialogWarningIcon');
            const successIcon = document.getElementById('customDialogSuccessIcon');
            const infoIcon = document.getElementById('customDialogInfoIcon');

            if (!modal) {
                // Fallback if modal HTML not present
                resolve(confirm(message));
                return;
            }

            titleEl.textContent = title;
            msgEl.textContent = message;
            confirmBtn.textContent = confirmText;
            cancelBtn.textContent = cancelText;
            cancelBtn.classList.remove('hidden');

            dangerIcon.classList.add('hidden');
            warningIcon.classList.add('hidden');
            successIcon.classList.add('hidden');
            infoIcon.classList.add('hidden');

            if (type === 'danger') {
                iconContainer.className = 'w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700 shadow-xs shadow-rose-200 transition-colors';
                dangerIcon.classList.remove('hidden');
            } else if (type === 'warning') {
                iconContainer.className = 'w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 shadow-xs shadow-amber-200 transition-colors';
                warningIcon.classList.remove('hidden');
            } else {
                iconContainer.className = 'w-10 h-10 rounded-xl bg-indigo-100 text-[#5B5BD6] flex items-center justify-center shrink-0';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-xs transition-colors';
                infoIcon.classList.remove('hidden');
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }, 10);

            function cleanup(confirmed) {
                card.classList.remove('scale-100');
                card.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 100);
                confirmBtn.onclick = null;
                cancelBtn.onclick = null;
                modal.onclick = null;
                resolve(confirmed);
            }

            confirmBtn.onclick = () => cleanup(true);
            cancelBtn.onclick = () => cleanup(false);
            modal.onclick = (e) => {
                if (e.target === modal) cleanup(false);
            };
        });
    };

    window.showAlertDialog = function ({ title = 'Notice', message = '', btnText = 'Got it', type = 'info' }) {
        return new Promise((resolve) => {
            const modal = document.getElementById('customDialogModal');
            const card = document.getElementById('customDialogCard');
            const titleEl = document.getElementById('customDialogTitle');
            const msgEl = document.getElementById('customDialogMessage');
            const confirmBtn = document.getElementById('customDialogConfirmBtn');
            const cancelBtn = document.getElementById('customDialogCancelBtn');
            const iconContainer = document.getElementById('customDialogIconContainer');
            const dangerIcon = document.getElementById('customDialogDangerIcon');
            const warningIcon = document.getElementById('customDialogWarningIcon');
            const successIcon = document.getElementById('customDialogSuccessIcon');
            const infoIcon = document.getElementById('customDialogInfoIcon');

            if (!modal) {
                alert(message);
                resolve();
                return;
            }

            titleEl.textContent = title;
            msgEl.textContent = message;
            confirmBtn.textContent = btnText;
            cancelBtn.classList.add('hidden'); // No cancel button for alert notice

            dangerIcon.classList.add('hidden');
            warningIcon.classList.add('hidden');
            successIcon.classList.add('hidden');
            infoIcon.classList.add('hidden');

            if (type === 'danger' || type === 'error') {
                iconContainer.className = 'w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700 shadow-xs transition-colors';
                dangerIcon.classList.remove('hidden');
            } else if (type === 'warning') {
                iconContainer.className = 'w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 shadow-xs transition-colors';
                warningIcon.classList.remove('hidden');
            } else if (type === 'success') {
                iconContainer.className = 'w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 shadow-xs transition-colors';
                successIcon.classList.remove('hidden');
            } else {
                iconContainer.className = 'w-10 h-10 rounded-xl bg-indigo-100 text-[#5B5BD6] flex items-center justify-center shrink-0';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-xs transition-colors';
                infoIcon.classList.remove('hidden');
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }, 10);

            function cleanup() {
                card.classList.remove('scale-100');
                card.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 100);
                confirmBtn.onclick = null;
                modal.onclick = null;
                resolve();
            }

            confirmBtn.onclick = cleanup;
            modal.onclick = (e) => {
                if (e.target === modal) cleanup();
            };
        });
    };

    window.deleteNode = async function (nodeId) {
        const node = findNode(nodeId);
        const nodeName = node ? `"${node.title || 'Selected Node'}"` : 'this node';

        const confirmed = await showConfirmDialog({
            title: 'Delete Node?',
            message: `Are you sure you want to delete ${nodeName} and all its connected wires? This step will be removed from your funnel flow.`,
            confirmText: 'Delete Node',
            cancelText: 'Cancel',
            type: 'danger'
        });

        if (confirmed) {
            const targetId = node ? node.id : nodeId;
            const targetKey = node ? node.node_key : null;
            const wasStart = node && node.is_start;

            graphData.nodes = (graphData.nodes || []).filter(n => {
                if (targetId !== null && targetId !== undefined && n.id == targetId) return false;
                if (targetKey && n.node_key === targetKey) return false;
                if (nodeId !== null && nodeId !== undefined && (n.id == nodeId || n.node_key == nodeId)) return false;
                return true;
            });

            graphData.edges = (graphData.edges || []).filter(e => {
                if (targetId !== null && targetId !== undefined && (e.source_node_id == targetId || e.target_node_id == targetId)) return false;
                if (targetKey && (e.source_node_key === targetKey || e.target_node_key === targetKey)) return false;
                if (nodeId !== null && nodeId !== undefined && (e.source_node_id == nodeId || e.target_node_id == nodeId || e.source_node_key == nodeId || e.target_node_key == nodeId)) return false;
                return true;
            });

            if (wasStart) {
                const nextStart = (graphData.nodes || []).find(n => n.node_type !== 'destination');
                if (nextStart) {
                    nextStart.is_start = true;
                }
            }

            deselectAll();
            renderCanvas();
            markDirty();
        }
    };

    // Persistence & Status
    window.updatePublishStatusBadge = function (status, hasUnpublished) {
        const badge = document.getElementById('funnelPublishStatusBadge');
        const publishBtn = document.getElementById('publishBtn');
        const publishBtnText = document.getElementById('publishBtnText');
        const isLive = (status === 'published' && !hasUnpublished);

        if (badge) {
            if (isLive) {
                badge.className = 'inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold shrink-0 whitespace-nowrap bg-emerald-50 text-emerald-700 border border-emerald-200';
                badge.setAttribute('title', 'Published & Live');
                badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span id="funnelPublishStatusText">Published</span>';
            } else {
                badge.className = 'inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold shrink-0 whitespace-nowrap bg-amber-50 text-amber-800 border border-amber-300';
                badge.setAttribute('title', 'Changes saved in draft — Click Publish to update the live public URL');
                badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span><span id="funnelPublishStatusText">Not Published</span>';
            }
        }

        if (publishBtn) {
            if (isLive) {
                publishBtn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition-colors shrink-0 whitespace-nowrap bg-[#5B5BD6] text-white hover:bg-[#4B4BC0] font-semibold';
            } else {
                publishBtn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition-colors shrink-0 whitespace-nowrap bg-amber-600 text-white hover:bg-amber-700 font-bold shadow-sm';
            }
        }
        if (publishBtnText) {
            publishBtnText.textContent = isLive ? 'Published' : 'Publish Changes';
        }

        if (graphData && graphData.funnel) {
            graphData.funnel.has_unpublished_changes = hasUnpublished ? 1 : 0;
        }
    };

    function markDirty() {
        isDirty = true;
        setSaveStatus('unsaved', 'Unsaved changes');
        updatePublishStatusBadge(graphData && graphData.funnel ? graphData.funnel.status : 'published', true);

        if (autoSaveTimer) clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            // If user is actively typing or focused inside an inspector input/textarea, defer autosave
            if (inspectorContent && document.activeElement && inspectorContent.contains(document.activeElement)) {
                const tag = (document.activeElement.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'textarea') {
                    markDirty();
                    return;
                }
            }
            saveGraphNow(true);
        }, 3000);
    }

    window.saveGraphNow = async function (isAuto = false) {
        if (isAuto && inspectorContent && document.activeElement && inspectorContent.contains(document.activeElement)) {
            const tag = (document.activeElement.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea') {
                if (autoSaveTimer) clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => saveGraphNow(true), 2500);
                return;
            }
        }

        setSaveStatus('saving', 'Saving...');
        try {
            // Track the currently selected node by key so we don't lose selection or desync IDs
            const prevSelectedNode = selectedNodeId ? findNode(selectedNodeId) : null;
            const prevSelectedKey = prevSelectedNode ? prevSelectedNode.node_key : null;

            const resp = await fetch(`${window.API_BASE}/api/funnels/${window.FUNNEL_ID}/graph`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN
                },
                body: JSON.stringify(graphData)
            });
            const json = await resp.json();
            if (json.success) {
                graphData = json.data;
                isDirty = false;
                setSaveStatus('saved', 'Saved');
                updatePublishStatusBadge(graphData.funnel ? graphData.funnel.status : 'published', true);

                // Restore selectedNodeId using its stable node_key
                if (prevSelectedKey) {
                    const matched = graphData.nodes.find(n => n.node_key === prevSelectedKey);
                    if (matched) {
                        selectedNodeId = matched.id;
                    }
                }

                renderCanvas();

                // If inspector is currently viewing a node, re-render with fresh IDs while keeping scroll position
                // However, do NOT re-render during auto-save if user is actively editing inside the inspector
                const isEditingInspector = inspectorContent && document.activeElement && inspectorContent.contains(document.activeElement);
                if (selectedNodeId && (!isAuto || !isEditingInspector)) {
                    renderInspector(true);
                } else if (!selectedNodeId) {
                    renderFunnelOverviewInspector();
                }
                updateMinimap();
            } else {
                setSaveStatus('error', json.error || 'Save failed');
            }
        } catch (e) {
            console.error('Save error:', e);
            setSaveStatus('error', 'Network error');
        }
    };

    function setSaveStatus(type, text) {
        let dotColor = 'bg-emerald-500';
        if (type === 'unsaved') dotColor = 'bg-amber-500';
        if (type === 'saving' || type === 'loading') dotColor = 'bg-indigo-500 animate-pulse';
        if (type === 'error') dotColor = 'bg-rose-500';

        saveIndicator.innerHTML = `
            <span class="w-2 h-2 rounded-full ${dotColor} shrink-0"></span>
            <span class="whitespace-nowrap">${text}</span>
        `;
    }

    // Validation Drawer
    window.openValidationDrawer = async function () {
        const drawer = document.getElementById('validationDrawer');
        const container = document.getElementById('validationResults');
        drawer.classList.remove('translate-x-full');
        container.innerHTML = '<p class="text-[#687080]">Checking flow connectivity...</p>';

        try {
            const resp = await fetch(`${window.API_BASE}/api/funnels/${window.FUNNEL_ID}/validate`);
            const json = await resp.json();
            if (json.success) {
                const val = json.data;
                if (val.is_valid && val.issues.length === 0) {
                    container.innerHTML = `
                        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 space-y-1">
                            <p class="font-bold flex items-center gap-1.5">✓ Flow is 100% Valid</p>
                            <p class="text-[11px]">All answer options connect to reachable destinations. The funnel is ready to publish.</p>
                        </div>
                    `;
                } else {
                    let html = `<div class="mb-3 font-semibold text-[#171A21]">${val.errors_count} error(s), ${val.warnings_count} warning(s) found:</div>`;
                    val.issues.forEach(issue => {
                        const isErr = issue.severity === 'error';
                        html += `
                            <div class="p-3 rounded-lg border ${isErr ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-amber-50 border-amber-200 text-amber-800'} space-y-1 cursor-pointer hover:opacity-90"
                                 onclick="focusNode(${issue.node_id})">
                                <p class="font-bold text-xs">${isErr ? '⛔ Error' : '⚠️ Warning'}</p>
                                <p class="text-[11px]">${escapeHtml(issue.message)}</p>
                                ${issue.node_id ? '<span class="text-[10px] underline font-semibold">Click to focus node →</span>' : ''}
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                }
            }
        } catch (e) {
            container.innerHTML = '<p class="text-rose-600">Failed to validate flow.</p>';
        }
    };

    window.closeValidationDrawer = function () {
        document.getElementById('validationDrawer').classList.add('translate-x-full');
    };

    window.focusNode = function (nodeId) {
        if (!nodeId) return;
        selectNode(nodeId);
        const node = graphData.nodes.find(n => n.id === nodeId);
        if (node) {
            panX = -node.position_x * scale + viewport.clientWidth / 2 - 120;
            panY = -node.position_y * scale + viewport.clientHeight / 2 - 80;
            applyTransform();
        }
        closeValidationDrawer();
    };

    // Version Publishing
    window.publishFunnel = async function () {
        // First save current graph
        await saveGraphNow();

        const btn = document.getElementById('publishBtn');
        const publishBtnText = document.getElementById('publishBtnText');
        if (btn) btn.disabled = true;
        if (publishBtnText) {
            publishBtnText.textContent = 'Publishing...';
        } else if (btn) {
            btn.textContent = 'Publishing...';
        }

        try {
            const resp = await fetch(`${window.API_BASE}/admin/funnels/${window.FUNNEL_ID}/publish`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.CSRF_TOKEN
                }
            });

            let json = null;
            try {
                json = await resp.json();
            } catch (parseErr) {
                const rawText = await resp.text().catch(() => '');
                throw new Error(rawText ? `Server returned error (${resp.status}): ${rawText.slice(0, 150)}` : `Server error (${resp.status})`);
            }

            if (json && json.success) {
                await showAlertDialog({
                    title: 'Funnel Published!',
                    message: 'Funnel published successfully! Public URL is active.',
                    type: 'success',
                    btnText: 'Awesome!'
                });
                window.location.reload();
            } else {
                await showAlertDialog({
                    title: 'Cannot Publish Funnel',
                    message: (json && json.error) ? json.error : 'Please fix flow errors before publishing.',
                    type: 'danger',
                    btnText: 'Inspect Errors'
                });
                openValidationDrawer();
            }
        } catch (e) {
            console.error('Publish error:', e);
            showAlertDialog({
                title: 'Cannot Publish',
                message: e.message || 'Publish request failed. Please check your network or server connection.',
                type: 'danger',
                btnText: 'Close'
            });
        } finally {
            if (btn) btn.disabled = false;
            const isLive = graphData && graphData.funnel ? (graphData.funnel.status === 'published' && !graphData.funnel.has_unpublished_changes) : false;
            if (publishBtnText) {
                publishBtnText.textContent = isLive ? 'Published' : 'Publish Changes';
            } else if (btn) {
                btn.textContent = isLive ? 'Published' : 'Publish Changes';
            }
        }
    };

    // Live Quiz Simulation Preview Modal
    let previewStepNodeId = null;

    window.openPreviewModal = function () {
        const modal = document.getElementById('previewModal');
        const body = document.getElementById('previewModalBody');
        modal.classList.remove('hidden');

        // Find Starting Node
        let startNode = graphData.nodes.find(n => n.is_start);
        if (!startNode) {
            startNode = graphData.nodes.find(n => n.node_type !== 'destination');
        }
        if (!startNode) {
            body.innerHTML = `<p class="text-rose-600 text-xs text-center py-6 font-semibold">Cannot preview: No starting step found.</p>`;
            return;
        }

        renderPreviewStep(startNode.id);
    };

    window.closePreviewModal = function () {
        document.getElementById('previewModal').classList.add('hidden');
    };

    function renderPreviewStep(nodeId) {
        previewStepNodeId = nodeId;
        const body = document.getElementById('previewModalBody');
        const node = graphData.nodes.find(n => n.id == nodeId);

        if (!node) {
            body.innerHTML = `<p class="text-rose-600 text-xs text-center py-4">Error: Step node ${nodeId} not found in graph.</p>`;
            return;
        }

        if (node.node_type === 'destination') {
            const dest = node.destination || { name: 'Completion', url: 'https://example.com' };
            body.innerHTML = `
                <div class="text-center py-6 space-y-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xl flex items-center justify-center mx-auto">✓</div>
                    <h3 class="text-base font-bold text-[#171A21]">Quiz Finished!</h3>
                    <p class="text-xs text-[#687080]">You reached terminal destination:</p>
                    <div class="p-3 bg-[#F1F3F6] rounded-xl text-xs font-semibold text-[#171A21]">${escapeHtml(dest.name)}</div>
                    <a href="${dest.url}" target="_blank" class="inline-flex px-4 py-2 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0]">
                        Open Landing Page (${escapeHtml(dest.url)}) →
                    </a>
                </div>
            `;
            return;
        }

        if (node.node_type === 'video_gate') {
            const vg = node.video_gate || {};
            const parsed = parseVideoUrl(vg.video_url || '');
            const prov = parsed.provider || vg.video_provider || 'YouTube';
            body.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider flex items-center gap-1">
                            <span>🎬</span>
                            <span>${prov === 'vimeo' ? 'Vimeo' : 'YouTube'} Interactive Video</span>
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            🔒 Gate at ${vg.gate_timestamp_seconds || 45}s
                        </span>
                    </div>
                    <h3 class="text-sm font-bold text-[#171A21]">${escapeHtml(node.title || 'Video Gate Step')}</h3>
                    ${vg.video_headline ? `<p class="text-xs text-slate-500">${escapeHtml(vg.video_headline)}</p>` : ''}
                    <div class="aspect-video bg-black rounded-xl flex items-center justify-center text-white text-xs font-mono shadow-inner border border-slate-800">
                        ▶ Interactive ${prov === 'vimeo' ? 'Vimeo' : 'YouTube'} Player (${escapeHtml(parsed.id || vg.video_id || 'ID')})
                    </div>
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800">
                        <strong>Turnstile Gate:</strong> Pauses at ${vg.gate_timestamp_seconds || 45}s to present ${vg.gate_type === 'question' ? 'a multiple choice turnstile' : 'a lead capture form'}.
                    </div>
                    <button type="button" onclick="submitPreviewVideoGate(${node.id})" class="w-full py-2.5 rounded-xl bg-rose-600 text-white text-xs font-semibold shadow hover:bg-rose-700 transition-colors">
                        Simulate Watch & Finish ➔
                    </button>
                </div>
            `;
            return;
        }

        if (node.node_type === 'form') {
            const form = node.form || {
                fields: [],
                submit_action: 'redirect',
                button_text: 'Submit Details',
                success_heading: 'Thank You!',
                success_message: 'Your submission has been received.'
            };
            const fields = form.fields || [];
            const submitAction = form.submit_action || 'redirect';

            let fieldsHtml = '';
            if (fields.length === 0) {
                fieldsHtml = `<div class="p-3 text-center text-xs text-amber-700 bg-amber-50 rounded-xl">No form fields configured for this step.</div>`;
            } else {
                fields.forEach(f => {
                    if (f.field_type === 'consent') {
                        fieldsHtml += `
                            <label class="flex items-start gap-2.5 cursor-pointer pt-1">
                                <input type="checkbox" ${f.is_required ? 'required' : ''} class="mt-0.5 rounded text-[#5B5BD6]">
                                <span class="text-xs text-[#687080] leading-snug">${escapeHtml(f.label)}${f.is_required ? ' *' : ''}</span>
                            </label>
                        `;
                    } else if (f.field_type === 'dropdown') {
                        fieldsHtml += `
                            <div class="space-y-1">
                                <label class="block font-semibold text-[#171A21] text-xs">${escapeHtml(f.label)}${f.is_required ? ' *' : ''}</label>
                                <select class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs">
                                    <option value="">${escapeHtml(f.placeholder || 'Select an option')}</option>
                                    <option value="1">Sample Option 1</option>
                                    <option value="2">Sample Option 2</option>
                                </select>
                            </div>
                        `;
                    } else {
                        const inputType = f.field_type === 'email' ? 'email' : (f.field_type === 'number' ? 'number' : 'text');
                        fieldsHtml += `
                            <div class="space-y-1">
                                <label class="block font-semibold text-[#171A21] text-xs">${escapeHtml(f.label)}${f.is_required ? ' *' : ''}</label>
                                <input type="${inputType}" placeholder="${escapeHtml(f.placeholder || '')}" ${f.is_required ? 'required' : ''}
                                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs">
                            </div>
                        `;
                    }
                });
            }

            body.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <span class="text-[10px] font-bold text-sky-600 uppercase tracking-wider">Form Step</span>
                        <h3 class="text-sm font-bold text-[#171A21] mt-0.5">${escapeHtml(form.heading || node.title || 'Lead Capture Form')}</h3>
                        ${(form.subheading || form.description) ? `<p class="text-xs text-slate-500 mt-1">${escapeHtml(form.subheading || form.description)}</p>` : ''}
                    </div>

                    <form onsubmit="event.preventDefault(); submitPreviewForm(${node.id});" class="space-y-3 pt-1">
                        ${fieldsHtml}
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-[#5B5BD6] text-white text-xs font-semibold shadow hover:bg-[#4B4BC0] transition-colors mt-2">
                            ${escapeHtml(form.button_text || 'Submit Details')} →
                        </button>
                    </form>
                </div>
            `;
            return;
        }

        const q = node.question || { question_text: 'Question', options: [] };
        const options = q.options || [];

        let html = `
            <div class="space-y-4">
                <div>
                    <span class="text-[10px] font-bold text-[#5B5BD6] uppercase tracking-wider">Preview Step</span>
                    <h3 class="text-sm font-bold text-[#171A21] mt-0.5">${escapeHtml(q.question_text)}</h3>
                    ${q.help_text ? `<p class="text-xs text-[#687080] mt-1">${escapeHtml(q.help_text)}</p>` : ''}
                </div>

                <div class="space-y-2 pt-2">
        `;

        options.forEach(opt => {
            const edge = (graphData.edges || []).find(e => e.source_node_id == node.id && e.source_option_id == opt.id);
            const targetId = edge ? edge.target_node_id : null;

            html += `
                <button type="button" onclick="choosePreviewOption(${targetId})" 
                        class="w-full p-3 rounded-xl border border-[#E4E7EC] hover:border-[#5B5BD6] hover:bg-indigo-50/30 text-left text-xs font-medium text-[#171A21] flex items-center justify-between transition-all">
                    <span>${escapeHtml(opt.label)}</span>
                    <span class="text-[10px] text-slate-400">→</span>
                </button>
            `;
        });

        html += `</div></div>`;
        body.innerHTML = html;
    }

    window.submitPreviewVideoGate = function (nodeId) {
        const node = graphData.nodes.find(n => n.id == nodeId);
        if (!node) return;
        const vg = node.video_gate || {};
        const endAction = vg.end_action || (vg.on_video_end === 'message' || vg.on_video_end === 'thank_you' ? 'thank_you' : 'next_step');

        if (endAction === 'thank_you') {
            const body = document.getElementById('previewModalBody');
            body.innerHTML = `
                <div class="text-center py-6 space-y-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xl flex items-center justify-center mx-auto">🎉</div>
                    <h3 class="text-base font-bold text-[#171A21]">${escapeHtml(vg.success_heading || '🎉 Thank You for Watching!')}</h3>
                    <p class="text-xs text-[#687080] leading-relaxed max-w-sm mx-auto">${escapeHtml(vg.success_message || 'Your submission has been received. We will be in touch shortly.')}</p>
                    <div class="pt-2">
                        <button type="button" onclick="openPreviewModal()" class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-[#171A21] hover:bg-slate-50">
                            Restart Preview
                        </button>
                    </div>
                </div>
            `;
        } else {
            const edge = (graphData.edges || []).find(e => e.source_node_id == nodeId && (e.source_option_id === null || e.source_option_id === undefined));
            if (!edge || !edge.target_node_id) {
                showAlertDialog({
                    title: 'No Connected Route',
                    message: 'This video step is configured to route to the next step, but no next node is connected yet on the canvas.',
                    type: 'warning',
                    btnText: 'Got It'
                });
                return;
            }
            renderPreviewStep(edge.target_node_id);
        }
    };

    window.submitPreviewForm = function (nodeId) {
        const node = graphData.nodes.find(n => n.id == nodeId);
        if (!node) return;
        const form = node.form || {};
        const submitAction = form.submit_action || 'redirect';

        if (submitAction === 'message') {
            const body = document.getElementById('previewModalBody');
            body.innerHTML = `
                <div class="text-center py-6 space-y-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xl flex items-center justify-center mx-auto">✓</div>
                    <h3 class="text-base font-bold text-[#171A21]">${escapeHtml(form.success_heading || 'Thank You!')}</h3>
                    <p class="text-xs text-[#687080] leading-relaxed max-w-sm mx-auto">${escapeHtml(form.success_message || 'Your submission has been received.')}</p>
                    <div class="pt-2">
                        <button type="button" onclick="openPreviewModal()" class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-[#171A21] hover:bg-slate-50">
                            Restart Preview
                        </button>
                    </div>
                </div>
            `;
        } else {
            const edge = (graphData.edges || []).find(e => e.source_node_id == nodeId);
            if (!edge || !edge.target_node_id) {
                showAlertDialog({
                    title: 'No Connected Route',
                    message: 'This form is set to redirect to the next step, but no outgoing wire is connected to a next question or destination.',
                    type: 'warning',
                    btnText: 'Got It'
                });
                return;
            }
            renderPreviewStep(edge.target_node_id);
        }
    };

    window.choosePreviewOption = function (targetNodeId) {
        if (!targetNodeId) {
            showAlertDialog({
                title: 'No Connected Route',
                message: 'This option does not lead anywhere yet. Connect its handle to a next question or destination on the canvas!',
                type: 'warning',
                btnText: 'Got It'
            });
            return;
        }
        renderPreviewStep(targetNodeId);
    };

    function escapeHtml(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Toast Notification Utility
    
    window.updateVideoGateProperty = function (nodeId, prop, value) {
        const node = findNode(nodeId);
        if (node) {
            if (!node.video_gate) node.video_gate = {};
            node.video_gate[prop] = value;
            if (prop === 'gate_type' || prop === 'gate_enabled' || prop === 'on_video_end' || prop === 'end_action') {
                renderCanvas();
                renderInspector(true);
            }
            markDirty();
        }
    };

    window.updateVideoGateUrl = function (nodeId, url) {
        const node = findNode(nodeId);
        if (node) {
            if (!node.video_gate) node.video_gate = {};
            node.video_gate.video_url = url;
            const parsed = parseVideoUrl(url);
            if (parsed.provider) {
                node.video_gate.video_provider = parsed.provider;
                node.video_gate.video_id = parsed.id;
                node.video_gate.video_hash = parsed.hash;
            }
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.addVideoGateField = function (nodeId, presetType) {
        const node = findNode(nodeId);
        if (node && node.video_gate) {
            if (!node.video_gate.form_fields) node.video_gate.form_fields = [];
            let newField = { id: Date.now(), field_key: 'custom', label: 'Custom Field', field_type: 'text', is_required: 0 };
            if (presetType === 'name') newField = { id: Date.now(), field_key: 'name', label: 'Full Name', field_type: 'text', is_required: 1 };
            if (presetType === 'email') newField = { id: Date.now(), field_key: 'email', label: 'Email Address', field_type: 'email', is_required: 1 };
            if (presetType === 'phone') newField = { id: Date.now(), field_key: 'phone', label: 'Phone Number', field_type: 'phone', is_required: 1 };
            if (presetType === 'company') newField = { id: Date.now(), field_key: 'company', label: 'Company Name', field_type: 'text', is_required: 0 };
            if (presetType === 'consent') newField = { id: Date.now(), field_key: 'consent', label: 'I agree to the terms and privacy policy', field_type: 'consent', is_required: 1 };
            node.video_gate.form_fields.push(newField);
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.removeVideoGateField = function (nodeId, fieldId) {
        const node = findNode(nodeId);
        if (node && node.video_gate && node.video_gate.form_fields) {
            node.video_gate.form_fields = node.video_gate.form_fields.filter(f => f.id != fieldId);
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.updateVideoGateField = function (nodeId, fieldId, key, val) {
        const node = findNode(nodeId);
        if (node && node.video_gate && node.video_gate.form_fields) {
            const f = node.video_gate.form_fields.find(fld => fld.id == fieldId);
            if (f) {
                f[key] = val;
                renderCanvas();
                renderInspector(true);
                markDirty();
            }
        }
    };

    window.addVideoGateOption = function (nodeId) {
        const node = findNode(nodeId);
        if (node && node.video_gate) {
            if (!node.video_gate.question) node.video_gate.question = { question_text: 'Question', options: [] };
            if (!node.video_gate.question.options) node.video_gate.question.options = [];
            const count = node.video_gate.question.options.length + 1;
            const newOpt = {
                id: Date.now(),
                option_key: `opt_${Math.random().toString(36).substring(2, 8)}`,
                label: `Option ${count}`,
                value: `choice_${count}`,
                display_order: count
            };
            node.video_gate.question.options.push(newOpt);
            node.question = node.video_gate.question;
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.removeVideoGateOption = function (nodeId, optionId) {
        const node = findNode(nodeId);
        if (node && node.video_gate && node.video_gate.question && node.video_gate.question.options) {
            node.video_gate.question.options = node.video_gate.question.options.filter(o => o.id != optionId);
            node.question = node.video_gate.question;
            renderCanvas();
            renderInspector(true);
            markDirty();
        }
    };

    window.updateVideoGateOption = function (nodeId, optionId, key, val) {
        const node = findNode(nodeId);
        if (node && node.video_gate && node.video_gate.question && node.video_gate.question.options) {
            const opt = node.video_gate.question.options.find(o => o.id == optionId);
            if (opt) {
                opt[key] = val;
                node.question = node.video_gate.question;
                renderCanvas();
                renderInspector(true);
                markDirty();
            }
        }
    };

    window.showToast = function (text, iconType = 'success') {
        const container = document.getElementById('toastContainer');
        const textEl = document.getElementById('toastText');
        const iconEl = document.getElementById('toastIcon');
        if (!container || !textEl) return;

        textEl.textContent = text;
        if (iconEl) {
            if (iconType === 'success' || iconType === '✨' || iconType === '📋') {
                iconEl.innerHTML = `<svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`;
            } else if (iconType === 'warning' || iconType === '⚠️') {
                iconEl.innerHTML = `<svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;
            } else {
                iconEl.innerHTML = `<svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
            }
        }

        container.classList.remove('opacity-0', '-translate-y-2');
        container.classList.add('opacity-100', 'translate-y-0');

        if (window._toastTimer) clearTimeout(window._toastTimer);
        window._toastTimer = setTimeout(() => {
            container.classList.remove('opacity-100', 'translate-y-0');
            container.classList.add('opacity-0', '-translate-y-2');
        }, 2600);
    };

    // Copy Public Funnel Link
    window.copyFunnelUrl = function () {
        const slug = graphData.funnel ? graphData.funnel.slug : 'quiz';
        const url = `${window.location.origin}/quiz/${slug}`;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                showToast('Public funnel URL copied to clipboard');
            }).catch(() => {
                showToast(`Funnel URL: /quiz/${slug}`);
            });
        } else {
            showToast(`Funnel URL: /quiz/${slug}`);
        }
    };

    // Collapsible Hotkeys Accordion
    window.toggleHotkeysAccordion = function () {
        const content = document.getElementById('hotkeysContent');
        const chevron = document.getElementById('hotkeysChevron') || document.getElementById('hotkeysArrow');
        if (!content) return;
        const isHidden = content.classList.contains('hidden');
        if (isHidden) {
            content.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-90');
        } else {
            content.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-90');
        }
    };

    // Palette Drag & Canvas Drop
    window.handlePaletteDragStart = function (e, type) {
        e.dataTransfer.setData('text/plain', type);
        e.dataTransfer.effectAllowed = 'copy';
    };

    window.handleCanvasDragOver = function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        viewport.classList.add('canvas-drag-over');
    };

    window.handleCanvasDragLeave = function (e) {
        viewport.classList.remove('canvas-drag-over');
    };

    window.handleCanvasDrop = function (e) {
        e.preventDefault();
        viewport.classList.remove('canvas-drag-over');
        const type = e.dataTransfer.getData('text/plain');
        if (!['start', 'question', 'destination', 'form'].includes(type)) return;

        const rect = surface.getBoundingClientRect();
        const dropX = Math.max(20, Math.round((e.clientX - rect.left) / scale));
        const dropY = Math.max(20, Math.round((e.clientY - rect.top) / scale));

        addNodeToCanvas(type, dropX, dropY);
        showToast(`Added ${type} node to canvas`, 'info');
    };

    // Mini-Map Component
    window.toggleMinimap = function () {
        const container = document.getElementById('minimapContainer');
        const openBtn = document.getElementById('minimapOpenBtn');
        if (!container) return;
        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            if (openBtn) openBtn.classList.add('hidden');
            updateMinimap();
        } else {
            container.classList.add('hidden');
            if (openBtn) openBtn.classList.remove('hidden');
        }
    };

    function setupMinimapEvents() {
        const minimapVp = document.getElementById('minimapViewport');
        if (!minimapVp) return;

        minimapVp.addEventListener('click', (e) => {
            const canvas = document.getElementById('minimapCanvas');
            if (!canvas || !graphData.nodes || graphData.nodes.length === 0) return;
            const rect = canvas.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;

            let minX = 0, minY = 0, maxX = 1200, maxY = 800;
            graphData.nodes.forEach(n => {
                if (n.position_x < minX) minX = n.position_x;
                if (n.position_y < minY) minY = n.position_y;
                if (n.position_x + 280 > maxX) maxX = n.position_x + 280;
                if (n.position_y + 240 > maxY) maxY = n.position_y + 240;
            });
            minX -= 100; minY -= 100; maxX += 100; maxY += 100;
            const worldW = Math.max(maxX - minX, 600);
            const worldH = Math.max(maxY - minY, 400);
            const mapScale = Math.min(canvas.width / worldW, canvas.height / worldH);

            const worldTargetX = (clickX / mapScale) + minX;
            const worldTargetY = (clickY / mapScale) + minY;

            panX = -(worldTargetX * scale) + viewport.clientWidth / 2;
            panY = -(worldTargetY * scale) + viewport.clientHeight / 2;
            applyTransform();
        });
    }

    function updateMinimap() {
        const canvas = document.getElementById('minimapCanvas');
        const viewBox = document.getElementById('minimapViewBox');
        const container = document.getElementById('minimapContainer');
        if (!canvas || !viewBox || !container || container.classList.contains('hidden')) return;

        const ctx = canvas.getContext('2d');
        const cw = canvas.width;
        const ch = canvas.height;
        ctx.clearRect(0, 0, cw, ch);

        if (!graphData.nodes || graphData.nodes.length === 0) return;

        let minX = 0, minY = 0, maxX = 1200, maxY = 800;
        graphData.nodes.forEach(n => {
            if (n.position_x < minX) minX = n.position_x;
            if (n.position_y < minY) minY = n.position_y;
            if (n.position_x + 280 > maxX) maxX = n.position_x + 280;
            if (n.position_y + 240 > maxY) maxY = n.position_y + 240;
        });

        minX -= 100; minY -= 100; maxX += 100; maxY += 100;
        const worldW = Math.max(maxX - minX, 600);
        const worldH = Math.max(maxY - minY, 400);
        const mapScale = Math.min(cw / worldW, ch / worldH);

        // Draw connections on minimap
        ctx.strokeStyle = '#CBD5E1';
        ctx.lineWidth = 1;
        (graphData.edges || []).forEach(e => {
            const s = graphData.nodes.find(n => n.id == e.source_node_id);
            const t = graphData.nodes.find(n => n.id == e.target_node_id);
            if (!s || !t) return;
            const sx = (s.position_x + 250 - minX) * mapScale;
            const sy = (s.position_y + 40 - minY) * mapScale;
            const tx = (t.position_x - minX) * mapScale;
            const ty = (t.position_y + 30 - minY) * mapScale;
            ctx.beginPath();
            ctx.moveTo(sx, sy);
            ctx.lineTo(tx, ty);
            ctx.stroke();
        });

        // Draw node rectangles
        graphData.nodes.forEach(n => {
            const x = (n.position_x - minX) * mapScale;
            const y = (n.position_y - minY) * mapScale;
            const w = Math.max(256 * mapScale, 8);
            const h = Math.max(120 * mapScale, 6);

            if (n.node_type === 'start') {
                ctx.fillStyle = '#10B981';
            } else if (n.node_type === 'question') {
                ctx.fillStyle = '#6366F1';
            } else if (n.node_type === 'form') {
                ctx.fillStyle = '#0EA5E9';
            } else if (n.node_type === 'video_gate') {
                ctx.fillStyle = '#F43F5E';
            } else {
                ctx.fillStyle = '#A855F7';
            }

            if (selectedNodeId === n.id) {
                ctx.fillStyle = '#312E81';
            }

            ctx.beginPath();
            ctx.roundRect ? ctx.roundRect(x, y, w, h, 2) : ctx.rect(x, y, w, h);
            ctx.fill();
        });

        // Viewport rect calculation
        const vpRect = viewport.getBoundingClientRect();
        const curLeft = (-panX / scale);
        const curTop = (-panY / scale);
        const curWidth = vpRect.width / scale;
        const curHeight = vpRect.height / scale;

        const boxX = Math.max(0, Math.min(cw, (curLeft - minX) * mapScale));
        const boxY = Math.max(0, Math.min(ch, (curTop - minY) * mapScale));
        const boxW = Math.max(10, Math.min(cw, curWidth * mapScale));
        const boxH = Math.max(10, Math.min(ch, curHeight * mapScale));

        viewBox.style.left = `${boxX}px`;
        viewBox.style.top = `${boxY}px`;
        viewBox.style.width = `${boxW}px`;
        viewBox.style.height = `${boxH}px`;
    }

    // Auto-Arrange Flow (Hierarchical Tree Layout)
    window.autoArrangeNodes = function () {
        if (!graphData.nodes || graphData.nodes.length === 0) return;

        const nodeMap = new Map();
        graphData.nodes.forEach(n => nodeMap.set(n.id, n));

        const adj = new Map();
        const inDegree = new Map();
        graphData.nodes.forEach(n => {
            adj.set(n.id, []);
            inDegree.set(n.id, 0);
        });

        (graphData.edges || []).forEach(e => {
            if (adj.has(e.source_node_id) && nodeMap.has(e.target_node_id)) {
                adj.get(e.source_node_id).push(e.target_node_id);
                inDegree.set(e.target_node_id, (inDegree.get(e.target_node_id) || 0) + 1);
            }
        });

        let startNode = graphData.nodes.find(n => n.is_start) || graphData.nodes.find(n => n.node_type === 'start') || graphData.nodes.find(n => n.node_type !== 'destination');
        const queue = [];
        const layers = new Map();
        const visited = new Set();

        if (startNode) {
            queue.push({ id: startNode.id, layer: 0 });
            visited.add(startNode.id);
            layers.set(startNode.id, 0);
        } else {
            graphData.nodes.forEach(n => {
                if (inDegree.get(n.id) === 0 && !visited.has(n.id)) {
                    queue.push({ id: n.id, layer: 0 });
                    visited.add(n.id);
                    layers.set(n.id, 0);
                }
            });
        }

        while (queue.length > 0) {
            const { id, layer } = queue.shift();
            const neighbors = adj.get(id) || [];
            neighbors.forEach(nextId => {
                const currentLayer = layers.get(nextId) || 0;
                const newLayer = Math.max(currentLayer, layer + 1);
                layers.set(nextId, newLayer);
                if (!visited.has(nextId)) {
                    visited.add(nextId);
                    queue.push({ id: nextId, layer: newLayer });
                }
            });
        }

        let maxLayer = 0;
        layers.forEach(l => { if (l > maxLayer) maxLayer = l; });
        graphData.nodes.forEach(n => {
            if (!visited.has(n.id)) {
                maxLayer++;
                layers.set(n.id, maxLayer);
                visited.add(n.id);
            }
        });

        const layerBuckets = new Map();
        graphData.nodes.forEach(n => {
            const l = layers.get(n.id) || 0;
            if (!layerBuckets.has(l)) layerBuckets.set(l, []);
            layerBuckets.get(l).push(n);
        });

        const startX = 60;
        const colSpacing = 340;
        const rowSpacing = 220;

        const sortedLayers = Array.from(layerBuckets.keys()).sort((a, b) => a - b);
        let maxBucketCount = 1;
        sortedLayers.forEach(l => {
            const count = layerBuckets.get(l).length;
            if (count > maxBucketCount) maxBucketCount = count;
        });

        const centerTargetY = Math.max(160, (maxBucketCount * rowSpacing) / 2);

        sortedLayers.forEach(layerIdx => {
            const nodesInLayer = layerBuckets.get(layerIdx);
            const totalHeight = (nodesInLayer.length - 1) * rowSpacing;
            const layerStartY = Math.max(60, centerTargetY - (totalHeight / 2));

            nodesInLayer.forEach((node, idx) => {
                node.position_x = startX + (layerIdx * colSpacing);
                node.position_y = layerStartY + (idx * rowSpacing);

                const el = document.getElementById(`node-${node.id}`);
                if (el) {
                    el.style.left = `${node.position_x}px`;
                    el.style.top = `${node.position_y}px`;
                }
            });
        });

        renderWires();
        updateMinimap();
        centerCanvas();
        markDirty();
        showToast('Auto-arranged flow into tree layout', 'success');
    };

})();
