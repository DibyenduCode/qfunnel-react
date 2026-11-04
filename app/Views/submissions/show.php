<div class="space-y-6">

    <!-- Top Bar Navigation & Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="<?= url('/admin/submissions') ?>" class="text-xs text-[#687080] hover:text-[#171A21]">← Submissions</a>
                <span class="text-xs text-[#687080]">/</span>
                <span class="text-xs font-semibold text-[#171A21]">Session #<?= (int)$detail['session']['id'] ?></span>
            </div>
            <h2 class="text-lg font-bold text-[#171A21]">Visitor Journey Inspector</h2>
        </div>
        <div class="flex items-center gap-2">
            <?php
                $visitorPhone = null;
                $visitorName = null;
                if (!empty($detail['form_values'])) {
                    foreach ($detail['form_values'] as $fv) {
                        $fKey = strtolower($fv['field_key_snapshot'] ?? '');
                        $fType = strtolower($fv['field_type_snapshot'] ?? '');
                        if (in_array($fKey, ['phone', 'mobile', 'whatsapp', 'tel']) || $fType === 'phone') {
                            if (!empty($fv['value_text'])) {
                                $visitorPhone = $fv['value_text'];
                            }
                        }
                        if (in_array($fKey, ['name', 'full_name', 'first_name']) && empty($visitorName)) {
                            if (!empty($fv['value_text'])) {
                                $visitorName = $fv['value_text'];
                            }
                        }
                    }
                }
                $quickConnect = $detail['result']['quick_connect'] ?? null;
            ?>
            <?php if ($visitorPhone): 
                $cleanLeadPhone = preg_replace('/[^0-9]/', '', $visitorPhone);
                $greetingMsg = rawurlencode("Hi " . ($visitorName ?: 'there') . ", thank you for your submission on " . $detail['session']['funnel_name'] . "!");
            ?>
                <a href="https://wa.me/<?= $cleanLeadPhone ?>?text=<?= $greetingMsg ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-5.805 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    <span>WhatsApp Lead</span>
                </a>
                <a href="tel:<?= htmlspecialchars($cleanLeadPhone) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-[#5B5BD6] hover:bg-[#4848B8] text-white shadow-xs transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span>Call Lead</span>
                </a>
            <?php endif; ?>
            <?php if ($detail['session']['status'] === 'completed'): ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ✓ Completed Journey
                </span>
            <?php elseif ($detail['session']['status'] === 'form_pending_video_gate'): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    <span>🎬</span>
                    <span>Form Pending (Video Gate)</span>
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <?= ucfirst(str_replace('_', ' ', $detail['session']['status'])) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overview Meta Banner -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm grid grid-cols-2 md:grid-cols-<?= (!empty($detail['session']['formatted_quote']) || $detail['session']['calculated_value'] !== null) ? '5' : '4' ?> gap-4 text-xs">
        <div>
            <span class="text-[#687080] block text-[11px] mb-0.5">Funnel</span>
            <span class="font-bold text-[#171A21]"><?= e($detail['session']['funnel_name']) ?></span>
            <span class="text-[10px] text-[#687080] block">Version <?= (int)$detail['session']['version_number'] ?></span>
        </div>
        <div>
            <span class="text-[#687080] block text-[11px] mb-0.5">Session Initialized</span>
            <span class="font-bold text-[#171A21]"><?= date('M d, Y H:i:s', strtotime($detail['session']['created_at'])) ?></span>
        </div>
        <div>
            <span class="text-[#687080] block text-[11px] mb-0.5">Journey Completed</span>
            <span class="font-bold text-[#171A21]">
                <?= $detail['session']['completed_at'] ? date('M d, Y H:i:s', strtotime($detail['session']['completed_at'])) : 'In Progress' ?>
            </span>
        </div>
        <div>
            <span class="text-[#687080] block text-[11px] mb-0.5">Final Destination</span>
            <span class="font-bold text-[#5B5BD6] truncate block">
                <?= e($detail['session']['destination_name'] ?: 'Pending completion') ?>
            </span>
            <?php if ($detail['session']['destination_url']): ?>
                <a href="<?= e($detail['session']['destination_url']) ?>" target="_blank" class="text-[10px] text-slate-400 hover:underline truncate block">
                    <?= e($detail['session']['destination_url']) ?>
                </a>
            <?php endif; ?>
        </div>
        <?php if (!empty($detail['session']['formatted_quote']) || $detail['session']['calculated_value'] !== null): ?>
            <div>
                <span class="text-purple-800 font-bold block text-[11px] mb-0.5">🧮 Instant Quote</span>
                <span class="font-extrabold font-mono text-purple-700 text-sm block">
                    <?= e($detail['session']['formatted_quote'] ?: (string)$detail['session']['calculated_value']) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content: Two Columns -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Column 1: Captured Lead Profile & Answer Trail (7 cols) -->
        <div class="lg:col-span-7 space-y-6">

            <?php 
                $hasQuote = !empty($detail['session']['formatted_quote']) || $detail['session']['calculated_value'] !== null || !empty($detail['result']['calculator']);
                $calcData = $detail['result']['calculator'] ?? null;
            ?>
            <?php if ($hasQuote): ?>
                <!-- Calculator & Quote Result Card -->
                <div class="bg-gradient-to-br from-purple-50 via-indigo-50/40 to-white border border-purple-200 rounded-xl p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-purple-200/80">
                        <h3 class="text-sm font-bold text-purple-950 flex items-center gap-2">
                            <span>🧮</span>
                            <span><?= e($calcData['result_label'] ?? 'Calculator & Dynamic Quote Result') ?></span>
                        </h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-600 text-white tracking-wide uppercase">
                            CALCULATED
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between">
                        <div>
                            <span class="text-[11px] font-semibold text-purple-800 uppercase tracking-wider block">Generated Quote</span>
                            <span class="text-2xl sm:text-3xl font-black font-mono text-purple-700 tracking-tight">
                                <?= e($detail['session']['formatted_quote'] ?: ($detail['session']['calculated_value'] !== null ? number_format((float)$detail['session']['calculated_value']) : '—')) ?>
                            </span>
                        </div>
                        <?php if ($detail['session']['calculated_value'] !== null): ?>
                            <div class="text-right">
                                <span class="text-[11px] font-semibold text-slate-500 block">Raw Calculated Value</span>
                                <span class="font-mono font-bold text-slate-700 text-sm"><?= (float)$detail['session']['calculated_value'] ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($calcData['formula'])): ?>
                        <div class="p-2.5 rounded-lg bg-white/80 border border-purple-200 text-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-900 block mb-1">Evaluated Formula</span>
                            <code class="font-mono font-bold text-purple-900 text-xs bg-purple-50 px-2 py-1 rounded border border-purple-200 block truncate">
                                <?= e($calcData['formula']) ?>
                            </code>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($calcData['variables'])): ?>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-900 block mb-1.5">Captured Question Variables</span>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($calcData['variables'] as $vKey => $vVal): ?>
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-indigo-200 shadow-2xs text-xs font-mono">
                                        <span class="text-[#5B5BD6] font-extrabold">@<?= e($vKey) ?></span>
                                        <span class="text-slate-400">=</span>
                                        <span class="font-bold text-[#171A21]"><?= e((string)$vVal) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($quickConnect) && !empty($quickConnect['enabled'])): ?>
                <!-- Quick Connect Terminal Action Details -->
                <div class="bg-gradient-to-r from-emerald-50/70 via-teal-50/40 to-white border border-emerald-200 rounded-xl p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between pb-3 border-b border-emerald-200/80">
                        <h3 class="text-sm font-bold text-emerald-950 flex items-center gap-2">
                            <span>📞</span>
                            <span>Direct 1-Click Connect Channel</span>
                        </h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white tracking-wide uppercase">
                            <?= strtoupper(e($quickConnect['action_type'] ?? 'both')) ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <?php if (!empty($quickConnect['call_url'])): ?>
                            <div class="p-3 bg-white rounded-lg border border-emerald-200 shadow-2xs">
                                <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Target Phone Number</span>
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-[#171A21]"><?= e($quickConnect['phone_number'] ?? '—') ?></span>
                                    <a href="<?= e($quickConnect['call_url']) ?>" class="text-[11px] font-bold text-[#5B5BD6] hover:underline">Test Call ↗</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($quickConnect['whatsapp_url'])): ?>
                            <div class="p-3 bg-white rounded-lg border border-emerald-200 shadow-2xs">
                                <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Target WhatsApp</span>
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-[#171A21]"><?= e($quickConnect['whatsapp_number'] ?? '—') ?></span>
                                    <a href="<?= e($quickConnect['whatsapp_url']) ?>" target="_blank" class="text-[11px] font-bold text-emerald-700 hover:underline">Test Chat ↗</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($quickConnect['message'])): ?>
                        <div class="p-2.5 rounded-lg bg-white border border-emerald-200 text-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Pre-filled WhatsApp Message Template</span>
                            <p class="font-sans text-slate-700 text-xs bg-slate-50 p-2 rounded border border-slate-200">
                                <?= nl2br(e($quickConnect['message'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Lead Form Card -->
            <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#E4E7EC]">
                    <h3 class="text-sm font-semibold text-[#171A21] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#5B5BD6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Pre-Quiz Lead Profile
                    </h3>
                    <span class="text-[11px] text-[#687080]"><?= count($detail['form_values']) ?> fields captured</span>
                </div>

                <?php if (empty($detail['form_values'])): ?>
                    <p class="text-xs text-[#687080] italic">No pre-quiz form data for this submission.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <?php foreach ($detail['form_values'] as $fv): ?>
                            <div class="p-3 bg-[#F7F8FA] rounded-lg border border-[#E4E7EC]">
                                <span class="text-[11px] font-semibold text-[#687080] block mb-0.5">
                                    <?= e($fv['field_label_snapshot']) ?>
                                </span>
                                <span class="font-semibold text-[#171A21] break-words">
                                    <?php if ($fv['field_type_snapshot'] === 'consent'): ?>
                                        <?= $fv['value_text'] === '1' ? '✓ Consent Acknowledged' : 'Not Granted' ?>
                                    <?php else: ?>
                                        <?= e($fv['value_text'] ?: '—') ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Visited Questions & Selected Answers Trail -->
            <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#E4E7EC]">
                    <h3 class="text-sm font-semibold text-[#171A21] flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Sequential Question Answers Path
                    </h3>
                    <span class="text-[11px] text-[#687080]"><?= count($detail['answers']) ?> steps completed</span>
                </div>

                <?php if (empty($detail['answers'])): ?>
                    <p class="text-xs text-[#687080] italic">No question answers recorded.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($detail['answers'] as $idx => $ans): ?>
                            <div class="p-4 rounded-xl border border-[#E4E7EC] bg-white hover:border-[#5B5BD6] transition-colors text-xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-indigo-50 text-[#5B5BD6] font-bold text-[10px] flex items-center justify-center">
                                            <?= $idx + 1 ?>
                                        </span>
                                        <h4 class="font-bold text-[#171A21]"><?= e($ans['question_text_snapshot']) ?></h4>
                                    </div>
                                    <span class="text-[10px] text-[#687080]"><?= date('H:i:s', strtotime($ans['answered_at'])) ?></span>
                                </div>
                                <div class="pl-7">
                                    <?php if ($ans['answer_type'] === 'slider'): ?>
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-[#5B5BD6] font-semibold border border-indigo-200">
                                            <span>🎚️ Slider Value:</span>
                                            <span class="font-bold font-mono"><?= e($ans['answer_value_text']) ?></span>
                                        </div>
                                    <?php elseif ($ans['answer_type'] === 'video_gate_question'): ?>
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 text-rose-800 font-semibold border border-rose-200">
                                            <span>🎬 Video Gate Option:</span>
                                            <span class="font-bold"><?= e($ans['answer_value_text']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                                            <span>Choice:</span>
                                            <span><?= e($ans['answer_value_text']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Column 2: Chronological Journey Event Timeline (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-[#171A21] mb-4 pb-3 border-b border-[#E4E7EC] flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Journey Event Timeline
                </h3>

                <div class="relative pl-6 space-y-6 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#E4E7EC]">
                    <?php 
                        $unlockedVideoNodes = [];
                        foreach ($detail['events'] as $eCheck) {
                            if ($eCheck['event_type'] === 'video_gate_completed') {
                                $unlockedVideoNodes[$eCheck['node_id'] ?? 0] = true;
                            }
                        }
                    ?>
                    <?php foreach ($detail['events'] as $evt): 
                        $eventType = $evt['event_type'];
                        $evtData = !empty($evt['event_data_json']) ? (is_string($evt['event_data_json']) ? json_decode($evt['event_data_json'], true) : $evt['event_data_json']) : [];
                        $isVid = str_starts_with($eventType, 'video_');
                        $bulletBg = 'bg-[#5B5BD6]';
                        if ($eventType === 'video_started') $bulletBg = 'bg-rose-500';
                        elseif ($eventType === 'video_gate_reached') $bulletBg = 'bg-amber-500';
                        elseif ($eventType === 'video_gate_completed') $bulletBg = 'bg-emerald-500';
                        elseif ($eventType === 'video_completed') $bulletBg = 'bg-purple-600';
                    ?>
                        <div class="relative text-xs">
                            <!-- Bullet node -->
                            <div class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full <?= $bulletBg ?> ring-4 ring-white shadow-2xs"></div>
                            <div>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <?php if ($eventType === 'video_started'): ?>
                                        <span class="font-bold text-rose-600">▶ Video Playback Started</span>
                                    <?php elseif ($eventType === 'video_gate_reached'): ?>
                                        <span class="font-bold text-amber-600">🔒 Video Gate Reached (Paused)</span>
                                    <?php elseif ($eventType === 'video_gate_completed'): ?>
                                        <span class="font-bold text-emerald-600">🔓 Video Gate Unlocked</span>
                                    <?php elseif ($eventType === 'video_completed'): ?>
                                        <span class="font-bold text-purple-600">🏁 Video Completed</span>
                                    <?php else: ?>
                                        <span class="font-bold text-[#171A21] capitalize">
                                            <?= str_replace('_', ' ', $eventType) ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="text-[10px] text-[#687080]">
                                        <?= date('H:i:s', strtotime($evt['occurred_at'])) ?>
                                    </span>
                                </div>

                                <?php if ($evt['node_title']): ?>
                                    <p class="text-[11px] text-[#687080] mt-0.5">
                                        Node: <strong class="text-[#171A21]"><?= e($evt['node_title']) ?></strong>
                                    </p>
                                <?php endif; ?>

                                <?php if ($isVid): ?>
                                    <div class="mt-1 space-y-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <?php if (!empty($evtData['provider'])): ?>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold <?= strtolower($evtData['provider']) === 'youtube' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-sky-50 text-sky-600 border border-sky-200' ?>">
                                                    <?= ucfirst(e($evtData['provider'])) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (isset($evtData['gate_timestamp'])): ?>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                    ⏱️ Gate at <?= sprintf('%02d:%02d', floor($evtData['gate_timestamp'] / 60), $evtData['gate_timestamp'] % 60) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($eventType === 'video_gate_reached'): ?>
                                                <?php if (!empty($unlockedVideoNodes[$evt['node_id'] ?? 0])): ?>
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        ✓ Unlocked
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                        ⚠️ Abandoned / Dropped off
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($eventType === 'video_gate_completed' && !empty($evtData['lead_fields'])): ?>
                                            <div class="p-2 rounded-lg bg-emerald-50/70 border border-emerald-200 text-[11px] space-y-0.5 mt-1">
                                                <span class="text-[10px] uppercase font-bold text-emerald-800 block mb-0.5">Captured In-Video Lead:</span>
                                                <?php foreach ($evtData['lead_fields'] as $k => $v): ?>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-slate-500 capitalize"><?= e(str_replace('_', ' ', $k)) ?>:</span>
                                                        <span class="font-bold text-slate-800"><?= e((string)$v) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($eventType === 'video_gate_completed' && !empty($evtData['option_label'])): ?>
                                            <div class="text-[11px] text-emerald-800 font-semibold mt-0.5">
                                                Selected Option: <strong><?= e($evtData['option_label']) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$isVid && $evt['option_label']): ?>
                                    <p class="text-[11px] text-emerald-700 mt-0.5">
                                        Selected: <strong><?= e($evt['option_label']) ?></strong>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

</div>
