<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-[#171A21]">Webhooks & Outbound Integrations</h2>
            <p class="text-xs text-[#687080]">Stream captured leads and quiz completions instantly to Zapier, Make, HubSpot, or your custom CRM.</p>
        </div>
        <?php if (!empty($hasWebhooks)): ?>
            <button onclick="document.getElementById('addWebhookModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + Add Webhook Endpoint
            </button>
        <?php else: ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
                🔒 Plan Upgrade Required for Webhooks
            </span>
        <?php endif; ?>
    </div>

    <?php if (empty($hasWebhooks)): ?>
        <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/80 text-xs text-amber-900 flex items-start gap-3">
            <span class="text-base">⚡</span>
            <div>
                <strong class="font-bold block">Webhooks & API Integrations are locked on your current plan</strong>
                <p class="text-[11px] text-amber-800 mt-0.5">Your subscription tier does not include real-time webhook endpoints. Upgrade to Standard or Pro to unlock instant automated leads delivery to Zapier, Make, Slack, or your CRM.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Integrations Guide Banner & Step-by-Step Walkthrough -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-purple-50 via-indigo-50 to-blue-50 border-b border-indigo-100 p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#5B5BD6] text-white flex items-center justify-center shrink-0 font-bold text-sm shadow-sm">
                    ⚡
                </div>
                <div>
                    <h3 class="text-sm font-bold text-[#171A21]">Webhook Integration Guide (Zapier, Make, Google Sheets)</h3>
                    <p class="text-xs text-[#525866] mt-0.5">Stream incoming leads automatically to 5,000+ apps the second they submit a form or finish a quiz.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('webhookGuideDetails').classList.toggle('hidden'); this.querySelector('.arrow').classList.toggle('rotate-180');" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-indigo-200 bg-white text-[#5B5BD6] text-xs font-semibold hover:bg-indigo-50/50 transition-colors shadow-2xs">
                <span>View Setup Instructions</span>
                <svg class="w-3.5 h-3.5 arrow transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>

        <!-- Detailed Guide Accordion Body -->
        <div id="webhookGuideDetails" class="p-5 space-y-5 bg-[#F8FAFC]/50 text-xs text-[#171A21] border-t border-[#E4E7EC] hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Step 1: Zapier / Make -->
                <div class="bg-white p-4 rounded-xl border border-[#E4E7EC] space-y-2">
                    <div class="w-6 h-6 rounded-full bg-[#5B5BD6] text-white font-bold text-xs flex items-center justify-center">1</div>
                    <h4 class="font-bold text-[#171A21]">Connect to Zapier or Make</h4>
                    <ol class="list-decimal list-inside space-y-1 text-[#687080] text-[11px] leading-relaxed">
                        <li>Create a Zap and select <strong>Webhooks by Zapier</strong>.</li>
                        <li>Choose trigger: <strong>Catch Hook</strong>.</li>
                        <li>Copy the generated <strong>Webhook URL</strong>.</li>
                    </ol>
                </div>

                <!-- Step 2: Google Sheets -->
                <div class="bg-white p-4 rounded-xl border border-[#E4E7EC] space-y-2">
                    <div class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">2</div>
                    <h4 class="font-bold text-[#171A21]">Direct to Google Sheets (Free)</h4>
                    <ol class="list-decimal list-inside space-y-1 text-[#687080] text-[11px] leading-relaxed">
                        <li>In your Google Sheet, open <strong>Extensions → Apps Script</strong>.</li>
                        <li>Add a <code>doPost(e)</code> handler to append a row.</li>
                        <li>Click <strong>Deploy → Web app</strong> (Access: Anyone).</li>
                        <li>Copy the deployed Web App URL.</li>
                    </ol>
                </div>

                <!-- Step 3: Add in QFunnel -->
                <div class="bg-white p-4 rounded-xl border border-[#E4E7EC] space-y-2">
                    <div class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">3</div>
                    <h4 class="font-bold text-[#171A21]">Paste & Test in QFunnel</h4>
                    <ol class="list-decimal list-inside space-y-1 text-[#687080] text-[11px] leading-relaxed">
                        <li>Click <strong>+ Add Webhook Endpoint</strong> below.</li>
                        <li>Paste your webhook URL and pick your event.</li>
                        <li>Click <strong>Test Ping</strong> to verify live delivery!</li>
                    </ol>
                </div>

            </div>

            <!-- Example JSON Payload Preview -->
            <div class="p-3.5 rounded-lg bg-slate-900 text-slate-100 space-y-1.5 font-mono text-[11px]">
                <div class="flex items-center justify-between text-slate-400 text-[10px] pb-1 border-b border-slate-800">
                    <span>Sample JSON Payload sent to your Webhook URL:</span>
                    <span class="text-emerald-400">HTTP POST • Content-Type: application/json</span>
                </div>
                <pre class="overflow-x-auto whitespace-pre leading-relaxed">{
  "event": "lead.captured",
  "timestamp": "2026-10-17T01:25:00+00:00",
  "data": {
    "session_id": 42,
    "funnel_name": "Doctor Referral Assessment",
    "lead_data": {
      "full_name": "Dr. Sarah Connor",
      "email": "sarah@example.com",
      "phone": "+1-555-0199"
    }
  }
}</pre>
            </div>
        </div>
    </div>

    <!-- Webhook Endpoints List -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E4E7EC] flex items-center justify-between">
            <h3 class="text-xs font-bold text-[#171A21] uppercase tracking-wide">Configured Webhooks (<?= count($endpoints) ?>)</h3>
        </div>

        <?php if (empty($endpoints)): ?>
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h4 class="text-sm font-semibold text-[#171A21]">No Webhook Endpoints Configured</h4>
                <p class="text-xs text-[#687080] max-w-sm mx-auto mt-1 mb-4">Add your first webhook URL to start sending real-time leads to external CRM systems.</p>
                <button onclick="document.getElementById('addWebhookModal').classList.remove('hidden')" class="px-3.5 py-2 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm">
                    + Add Webhook Endpoint
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#F8FAFC] text-[11px] font-bold uppercase tracking-wider text-[#687080] border-b border-[#E4E7EC]">
                            <th class="py-3 px-4">Webhook Name & Target URL</th>
                            <th class="py-3 px-4">Scope & Event</th>
                            <th class="py-3 px-4">Deliveries</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4E7EC] text-xs">
                        <?php foreach ($endpoints as $hook): ?>
                            <tr class="hover:bg-[#F8FAFC]/60 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-[#171A21]"><?= e($hook['name']) ?></div>
                                    <div class="text-[11px] font-mono text-[#687080] truncate max-w-[320px] mt-0.5" title="<?= e($hook['target_url']) ?>">
                                        <?= e($hook['target_url']) ?>
                                    </div>
                                    <?php if (!empty($hook['secret'])): ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            Secret: <span class="font-mono"><?= substr($hook['secret'], 0, 8) ?>••••••••</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                        <?= e($hook['event_type']) ?>
                                    </span>
                                    <div class="text-[11px] text-[#687080] mt-1">
                                        <?= !empty($hook['funnel_name']) ? ('Funnel: ' . e($hook['funnel_name'])) : 'All Funnels in Workspace' ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-[#171A21]">
                                        <?= (int)$hook['successful_deliveries'] ?> / <?= (int)$hook['total_deliveries'] ?> <span class="text-[10px] text-[#687080] font-normal">ok</span>
                                    </div>
                                    <div class="text-[10px] text-[#687080]">
                                        <?= !empty($hook['last_delivery_at']) ? ('Last: ' . date('M j, H:i', strtotime($hook['last_delivery_at']))) : 'No deliveries yet' ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($hook['is_active'])): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ● Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                            Paused
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="<?= url("/admin/webhooks/{$hook['id']}/test") ?>" method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" title="Send Test Ping" class="px-2.5 py-1 rounded border border-[#E4E7EC] hover:bg-slate-50 text-[11px] font-semibold text-[#171A21] transition-colors">
                                                Test Ping
                                            </button>
                                        </form>

                                        <button onclick='editWebhook(<?= json_encode($hook) ?>)' class="px-2.5 py-1 rounded border border-[#E4E7EC] hover:bg-slate-50 text-[11px] font-semibold text-[#5B5BD6] transition-colors">
                                            Edit
                                        </button>

                                        <form action="<?= url("/admin/webhooks/{$hook['id']}/delete") ?>" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this webhook endpoint?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1 rounded text-rose-500 hover:bg-rose-50 transition-colors" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Deliveries Log -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm p-4 space-y-3">
        <h3 class="text-xs font-bold text-[#171A21] uppercase tracking-wide">Recent Delivery Logs</h3>

        <?php if (empty($recentDeliveries)): ?>
            <p class="text-xs text-[#687080] py-4 text-center">No webhook payloads have been sent yet. Trigger a live funnel completion or click "Test Ping" above.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-[11px] font-semibold text-[#687080] border-b border-[#E4E7EC] pb-2">
                            <th class="py-2 px-3">Status</th>
                            <th class="py-2 px-3">Event</th>
                            <th class="py-2 px-3">Endpoint Target</th>
                            <th class="py-2 px-3">Duration</th>
                            <th class="py-2 px-3">Response</th>
                            <th class="py-2 px-3 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4E7EC]">
                        <?php foreach ($recentDeliveries as $log): ?>
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="py-2.5 px-3">
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                            <?= (int)$log['response_code'] ?> OK
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700">
                                            <?= (int)$log['response_code'] ?: 'ERR' ?> FAIL
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3 font-mono text-[11px] text-[#171A21]"><?= e($log['event_type']) ?></td>
                                <td class="py-2.5 px-3 truncate max-w-[220px]" title="<?= e($log['target_url']) ?>">
                                    <span class="font-semibold"><?= e($log['webhook_name']) ?></span>
                                    <span class="text-[11px] text-[#687080] block truncate font-mono"><?= e($log['target_url']) ?></span>
                                </td>
                                <td class="py-2.5 px-3 text-[#687080] font-mono text-[11px]"><?= (int)$log['duration_ms'] ?>ms</td>
                                <td class="py-2.5 px-3 text-[#687080] truncate max-w-[200px]" title="<?= e($log['response_body'] ?? '') ?>">
                                    <?= e($log['response_body'] ?? 'No body') ?>
                                </td>
                                <td class="py-2.5 px-3 text-[#687080] text-[11px]">
                                    <?= date('M j, H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <form action="<?= url("/admin/webhooks/deliveries/{$log['id']}/retry") ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 text-[10px] font-semibold border border-slate-200 transition-colors" title="Retry delivery now">
                                            ↻ Retry
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal: Add Webhook Endpoint -->
<div id="addWebhookModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-[#E4E7EC] space-y-4">
        <div class="flex items-center justify-between border-b border-[#E4E7EC] pb-3">
            <h3 class="text-sm font-bold text-[#171A21]">Add Webhook Endpoint</h3>
            <button onclick="document.getElementById('addWebhookModal').classList.add('hidden')" class="text-[#687080] hover:text-[#171A21]">✕</button>
        </div>

        <form action="<?= url('/admin/webhooks') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Webhook Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" placeholder="e.g. Zapier New Lead Flow or HubSpot CRM" required
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Target Endpoint URL <span class="text-rose-500">*</span></label>
                <input type="url" name="target_url" placeholder="https://hooks.zapier.com/hooks/catch/..." required
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                <p class="text-[11px] text-[#687080] mt-1">Must accept HTTP POST requests with JSON payload.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Trigger Event</label>
                    <select name="event_type" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white">
                        <option value="lead.captured">lead.captured (Form Submitted)</option>
                        <option value="quiz.completed">quiz.completed (Funnel Finished)</option>
                        <option value="all">All Events</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Target Funnel Scope</label>
                    <select name="funnel_id" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white">
                        <option value="">All Funnels in Workspace</option>
                        <?php foreach ($funnels as $fn): ?>
                            <option value="<?= (int)$fn['id'] ?>"><?= e($fn['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Signing Secret (HMAC-SHA256)</label>
                <input type="text" name="secret" placeholder="Leave blank to auto-generate a secure secret"
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs font-mono text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                <p class="text-[11px] text-[#687080] mt-1">Sent in `X-QFunnel-Signature` header for payload verification.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E4E7EC]">
                <button type="button" onclick="document.getElementById('addWebhookModal').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-[#E4E7EC] text-xs font-semibold text-[#687080] hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm">
                    Create Webhook Endpoint
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Webhook Endpoint -->
<div id="editWebhookModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-[#E4E7EC] space-y-4">
        <div class="flex items-center justify-between border-b border-[#E4E7EC] pb-3">
            <h3 class="text-sm font-bold text-[#171A21]">Edit Webhook Endpoint</h3>
            <button onclick="document.getElementById('editWebhookModal').classList.add('hidden')" class="text-[#687080] hover:text-[#171A21]">✕</button>
        </div>

        <form id="editWebhookForm" action="" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Webhook Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="editName" required
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Target Endpoint URL <span class="text-rose-500">*</span></label>
                <input type="url" name="target_url" id="editTargetUrl" required
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Trigger Event</label>
                    <select name="event_type" id="editEventType" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white">
                        <option value="lead.captured">lead.captured</option>
                        <option value="quiz.completed">quiz.completed</option>
                        <option value="all">All Events</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Target Funnel Scope</label>
                    <select name="funnel_id" id="editFunnelId" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white">
                        <option value="">All Funnels in Workspace</option>
                        <?php foreach ($funnels as $fn): ?>
                            <option value="<?= (int)$fn['id'] ?>"><?= e($fn['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Signing Secret (HMAC-SHA256)</label>
                <input type="text" name="secret" id="editSecret"
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs font-mono text-[#171A21]">
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer pt-1">
                    <input type="checkbox" name="is_active" id="editIsActive" value="1" class="rounded text-[#5B5BD6]">
                    <span class="text-xs font-semibold text-[#171A21]">Enable this webhook (Active)</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#E4E7EC]">
                <button type="button" onclick="document.getElementById('editWebhookModal').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-[#E4E7EC] text-xs font-semibold text-[#687080] hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#171A21] text-white text-xs font-semibold hover:bg-black shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editWebhook(hook) {
    const form = document.getElementById('editWebhookForm');
    form.action = '<?= url('/admin/webhooks') ?>/' + hook.id + '/update';
    document.getElementById('editName').value = hook.name || '';
    document.getElementById('editTargetUrl').value = hook.target_url || '';
    document.getElementById('editEventType').value = hook.event_type || 'lead.captured';
    document.getElementById('editFunnelId').value = hook.funnel_id || '';
    document.getElementById('editSecret').value = hook.secret || '';
    document.getElementById('editIsActive').checked = (parseInt(hook.is_active) === 1);

    document.getElementById('editWebhookModal').classList.remove('hidden');
}
</script>
