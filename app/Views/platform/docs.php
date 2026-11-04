<!-- API, Webhooks & Embedding Graphical Documentation Hub -->
<div class="space-y-6">

    <!-- Top Banner with Quick Highlights -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden border border-indigo-900/50">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 top-0 w-48 h-48 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 text-xs font-bold tracking-wide uppercase">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Interactive Developer & Integration Hub
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                API, Webhook & Embedding Documentation
            </h1>
            <p class="text-sm sm:text-base text-slate-300 leading-relaxed font-normal">
                Easy, step-by-step guides with live visual diagrams and copy-paste code snippets. Connect your quizzes to your CRM, trigger automations, or embed them on any website in seconds.
            </p>

            <!-- Quick Info Badges -->
            <div class="pt-2 flex flex-wrap items-center gap-3 text-xs">
                <div class="bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/80 flex items-center gap-2">
                    <span class="text-slate-400">Base URL:</span>
                    <span class="font-mono text-indigo-300 font-bold"><?= e($baseUrl) ?></span>
                </div>
                <div class="bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/80 flex items-center gap-2">
                    <span class="text-slate-400">REST API:</span>
                    <span class="text-emerald-400 font-bold">v1 Active</span>
                </div>
                <div class="bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/80 flex items-center gap-2">
                    <span class="text-slate-400">Embed Modes:</span>
                    <span class="text-purple-300 font-bold">Inline • Fullpage • Popup</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-xl border border-slate-200 p-1.5 shadow-xs flex flex-wrap gap-1 sm:gap-2">
        <button type="button" onclick="switchDocTab('api')" id="tabBtn-api" class="doc-tab-btn flex-1 min-w-[130px] sm:flex-none px-5 py-2.5 rounded-lg font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 bg-indigo-600 text-white shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            <span>1. REST API</span>
        </button>
        <button type="button" onclick="switchDocTab('webhooks')" id="tabBtn-webhooks" class="doc-tab-btn flex-1 min-w-[130px] sm:flex-none px-5 py-2.5 rounded-lg font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>2. Webhooks</span>
        </button>
        <button type="button" onclick="switchDocTab('embed')" id="tabBtn-embed" class="doc-tab-btn flex-1 min-w-[130px] sm:flex-none px-5 py-2.5 rounded-lg font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
            <span>3. Website Embedding</span>
        </button>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 1: REST API                                          -->
    <!-- ======================================================== -->
    <div id="docTab-api" class="space-y-6">

        <!-- Simple Explanation Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 text-lg font-black">
                    💡
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">What is the REST API? (Easy Explanation)</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                        Think of an API like a <strong>waiter at a restaurant</strong>. Your CRM or custom software is the customer sitting at the table. Your software says to the waiter: <em>"Please bring me all new leads from Quiz #<?= e($sampleFunnelId) ?>"</em>. The API waiter goes to QFunnel, gets the fresh leads, and brings them back to your software in neat, organized data format (JSON).
                    </p>
                </div>
            </div>

            <!-- Graphical Diagram -->
            <div class="p-4 bg-slate-900 rounded-xl text-slate-200 overflow-x-auto">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 font-mono">Visual Data Flow:</p>
                <div class="font-mono text-xs leading-relaxed flex items-center gap-2 sm:gap-4 flex-wrap sm:flex-nowrap justify-between">
                    <div class="bg-indigo-950 border border-indigo-500/40 p-3 rounded-lg text-center flex-1 min-w-[140px]">
                        <span class="text-indigo-400 font-bold block">1. Your App / CRM</span>
                        <span class="text-[10px] text-slate-400">HubSpot, Zapier, Custom Code</span>
                    </div>
                    <div class="text-indigo-400 font-black text-base">──► <span class="text-[10px] text-slate-400 font-normal">GET + API Key</span> ──►</div>
                    <div class="bg-slate-800 border border-slate-700 p-3 rounded-lg text-center flex-1 min-w-[140px]">
                        <span class="text-emerald-400 font-bold block">2. QFunnel API</span>
                        <span class="text-[10px] text-slate-400">Verifies Key & Fetches Data</span>
                    </div>
                    <div class="text-emerald-400 font-black text-base">──► <span class="text-[10px] text-slate-400 font-normal">Clean JSON</span> ──►</div>
                    <div class="bg-emerald-950 border border-emerald-500/40 p-3 rounded-lg text-center flex-1 min-w-[140px]">
                        <span class="text-emerald-300 font-bold block">3. Leads Saved</span>
                        <span class="text-[10px] text-slate-400">Ready for Sales Team!</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Authentication Guide -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-indigo-600 text-white flex items-center justify-center text-xs">🔑</span>
                How to Authenticate (API Key)
            </h3>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                Every request needs your workspace <strong>API Key</strong>. You can send it in either of two standard ways in your request headers:
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <span class="text-xs font-bold text-slate-800">Method A: Bearer Header (Standard)</span>
                    <pre class="bg-slate-900 text-indigo-300 p-2.5 rounded font-mono text-xs overflow-x-auto">Authorization: Bearer <?= e($sampleApiKey) ?></pre>
                </div>
                <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <span class="text-xs font-bold text-slate-800">Method B: X-API-Key Header</span>
                    <pre class="bg-slate-900 text-indigo-300 p-2.5 rounded font-mono text-xs overflow-x-auto">X-API-Key: <?= e($sampleApiKey) ?></pre>
                </div>
            </div>
        </div>

        <!-- Endpoints List -->
        <div class="space-y-4">
            <h3 class="text-base font-bold text-slate-900">API Endpoints Reference</h3>

            <!-- Endpoint 1: GET /api/v1/submissions -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2 bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2.5 py-1 rounded bg-emerald-600 text-white font-mono text-xs font-extrabold uppercase">GET</span>
                        <code class="font-mono text-xs sm:text-sm font-bold text-slate-900">/api/v1/submissions</code>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">Export captured quiz leads & answers</span>
                </div>
                
                <div class="p-5 sm:p-6 space-y-4">
                    <p class="text-xs sm:text-sm text-slate-600">
                        Retrieves the latest leads collected by your quizzes, including visitor contact details (name, email, phone) and scores.
                    </p>

                    <!-- Parameters Table -->
                    <div>
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-2">Query Parameters (Optional):</span>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border border-slate-200 rounded-lg">
                                <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                    <tr>
                                        <th class="p-2.5">Parameter</th>
                                        <th class="p-2.5">Type</th>
                                        <th class="p-2.5">Default</th>
                                        <th class="p-2.5">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr>
                                        <td class="p-2.5 font-mono text-indigo-600 font-semibold">funnel_id</td>
                                        <td class="p-2.5 text-slate-500">integer</td>
                                        <td class="p-2.5 text-slate-400">—</td>
                                        <td class="p-2.5 text-slate-700">Filter submissions by a specific quiz ID (e.g. <code>?funnel_id=<?= e($sampleFunnelId) ?></code>)</td>
                                    </tr>
                                    <tr>
                                        <td class="p-2.5 font-mono text-indigo-600 font-semibold">limit</td>
                                        <td class="p-2.5 text-slate-500">integer</td>
                                        <td class="p-2.5 text-slate-600">25</td>
                                        <td class="p-2.5 text-slate-700">Number of leads to return (1 to 100 max)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Example cURL Request -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Example cURL Request:</span>
                            <button type="button" onclick="copyCode('curlSubmissions')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">Copy cURL</button>
                        </div>
                        <pre id="curlSubmissions" class="bg-slate-900 text-slate-200 p-3 rounded-lg font-mono text-xs overflow-x-auto leading-relaxed">curl -X GET "<?= e($baseUrl) ?>/api/v1/submissions?limit=10" \
  -H "Authorization: Bearer <?= e($sampleApiKey) ?>" \
  -H "Accept: application/json"</pre>
                    </div>

                    <!-- Example JSON Response -->
                    <div>
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1.5">Example JSON Response:</span>
                        <pre class="bg-slate-950 text-emerald-400 p-3.5 rounded-lg font-mono text-xs overflow-x-auto leading-relaxed">{
  "status": "success",
  "count": 1,
  "data": [
    {
      "session_id": 142,
      "funnel_id": <?= e($sampleFunnelId) ?>,
      "funnel_name": "<?= e($sampleFunnelName) ?>",
      "status": "completed",
      "submitted_at": "2026-10-17 14:32:00",
      "form_fields": [
        { "field": "full_name", "label": "Full Name", "value": "Alex Morgan" },
        { "field": "email", "label": "Business Email", "value": "alex@company.com" },
        { "field": "phone", "label": "Phone Number", "value": "+1-555-0199" }
      ]
    }
  ]
}</pre>
                    </div>
                </div>
            </div>

            <!-- Endpoint 2: GET /api/v1/funnels -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2 bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2.5 py-1 rounded bg-emerald-600 text-white font-mono text-xs font-extrabold uppercase">GET</span>
                        <code class="font-mono text-xs sm:text-sm font-bold text-slate-900">/api/v1/funnels</code>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">List all your quizzes & slugs</span>
                </div>
                
                <div class="p-5 sm:p-6 space-y-4">
                    <p class="text-xs sm:text-sm text-slate-600">
                        Returns a list of all active quiz funnels in your workspace with their IDs and URL slugs.
                    </p>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Example cURL Request:</span>
                            <button type="button" onclick="copyCode('curlFunnels')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">Copy cURL</button>
                        </div>
                        <pre id="curlFunnels" class="bg-slate-900 text-slate-200 p-3 rounded-lg font-mono text-xs overflow-x-auto leading-relaxed">curl -X GET "<?= e($baseUrl) ?>/api/v1/funnels" \
  -H "X-API-Key: <?= e($sampleApiKey) ?>"</pre>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1.5">Example JSON Response:</span>
                        <pre class="bg-slate-950 text-emerald-400 p-3.5 rounded-lg font-mono text-xs overflow-x-auto leading-relaxed">{
  "status": "success",
  "count": 1,
  "data": [
    {
      "id": <?= e($sampleFunnelId) ?>,
      "name": "<?= e($sampleFunnelName) ?>",
      "slug": "<?= e($sampleFunnelSlug) ?>",
      "status": "published",
      "created_at": "2026-10-10 11:20:00"
    }
  ]
}</pre>
                    </div>
                </div>
            </div>

            <!-- Endpoint 3: GET /api/v1/me -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2 bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2.5 py-1 rounded bg-emerald-600 text-white font-mono text-xs font-extrabold uppercase">GET</span>
                        <code class="font-mono text-xs sm:text-sm font-bold text-slate-900">/api/v1/me</code>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">Check API key status & quota limits</span>
                </div>
                
                <div class="p-5 sm:p-6 space-y-4">
                    <p class="text-xs sm:text-sm text-slate-600">
                        Validates your API Key and reports your current subscription tier, active feature permissions, and usage limits.
                    </p>

                    <div>
                        <pre class="bg-slate-900 text-slate-200 p-3 rounded-lg font-mono text-xs overflow-x-auto leading-relaxed">curl -X GET "<?= e($baseUrl) ?>/api/v1/me" -H "Authorization: Bearer <?= e($sampleApiKey) ?>"</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Codes Reference Table -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-3">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">HTTP Status Codes Cheat Sheet</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border border-slate-200 rounded-lg">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-2.5">Code</th>
                            <th class="p-2.5">Status</th>
                            <th class="p-2.5">What it means in simple English</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="p-2.5 font-bold text-emerald-600">200</td>
                            <td class="p-2.5 font-semibold">OK</td>
                            <td class="p-2.5 text-slate-600">Everything worked! Your requested data is in the <code>data</code> field.</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-bold text-amber-600">401</td>
                            <td class="p-2.5 font-semibold">Unauthorized</td>
                            <td class="p-2.5 text-slate-600">Missing or invalid API Key. Please verify your token in Workspace Settings.</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-bold text-rose-600">403</td>
                            <td class="p-2.5 font-semibold">Forbidden</td>
                            <td class="p-2.5 text-slate-600">Your current plan does not have API access enabled. Upgrade to a Pro or Agency tier.</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-bold text-slate-600">404</td>
                            <td class="p-2.5 font-semibold">Not Found</td>
                            <td class="p-2.5 text-slate-600">The requested quiz ID or endpoint does not exist.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- TAB 2: WEBHOOKS ("Web huk")                              -->
    <!-- ======================================================== -->
    <div id="docTab-webhooks" class="hidden space-y-6">

        <!-- Simple Explanation Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center shrink-0 text-lg font-black">
                    ⚡
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">What is a Webhook? (Easy Explanation)</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                        Think of a Webhook like an <strong>instant phone notification</strong>. Instead of having your software repeatedly ask every few minutes: <em>"Did someone fill out my quiz yet?"</em>, QFunnel automatically calls your server the exact second a visitor finishes the quiz and hands you the lead details!
                    </p>
                </div>
            </div>

            <!-- Graphical Diagram -->
            <div class="p-4 bg-slate-900 rounded-xl text-slate-200 overflow-x-auto">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 font-mono">Real-Time Webhook Lifecycle:</p>
                <div class="font-mono text-xs leading-relaxed flex items-center gap-2 sm:gap-4 flex-wrap sm:flex-nowrap justify-between">
                    <div class="bg-purple-950 border border-purple-500/40 p-3 rounded-lg text-center flex-1 min-w-[140px]">
                        <span class="text-purple-300 font-bold block">1. Visitor Finishes Quiz</span>
                        <span class="text-[10px] text-slate-400">Clicks "Get My Result"</span>
                    </div>
                    <div class="text-purple-400 font-black text-base">──► <span class="text-[10px] text-slate-400 font-normal">HTTP POST</span> ──►</div>
                    <div class="bg-slate-800 border border-slate-700 p-3 rounded-lg text-center flex-1 min-w-[140px]">
                        <span class="text-indigo-400 font-bold block">2. Your Webhook URL</span>
                        <span class="text-[10px] text-slate-400">Zapier, CRM, or PHP script</span>
                    </div>
                    <div class="text-emerald-400 font-black text-base">──► <span class="text-[10px] text-slate-400 font-normal">200 OK</span> ──►</div>
                    <div class="bg-emerald-950 border border-emerald-500/40 p-3 rounded-lg text-center flex-1 min-w-[140px]">
                        <span class="text-emerald-300 font-bold block">3. Immediate Action</span>
                        <span class="text-[10px] text-slate-400">Send SMS, Add to CRM, Email Rep</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhook Payload Format -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-purple-600 text-white flex items-center justify-center text-xs">📦</span>
                Standard Webhook Payload (JSON Sent to You)
            </h3>
            <p class="text-xs sm:text-sm text-slate-600">
                QFunnel delivers an HTTP <code class="bg-slate-100 px-1 py-0.5 rounded font-mono text-purple-700">POST</code> request with <code class="bg-slate-100 px-1 py-0.5 rounded font-mono">Content-Type: application/json</code> containing:
            </p>

            <pre class="bg-slate-950 text-emerald-400 p-4 rounded-xl font-mono text-xs overflow-x-auto leading-relaxed">{
  "event": "quiz.completed",
  "event_id": "evt_98fbc12093ea",
  "timestamp": "2026-10-17T14:35:12Z",
  "funnel": {
    "id": <?= e($sampleFunnelId) ?>,
    "name": "<?= e($sampleFunnelName) ?>",
    "slug": "<?= e($sampleFunnelSlug) ?>"
  },
  "session": {
    "id": 142,
    "score": 85,
    "started_at": "2026-10-17 14:33:02",
    "completed_at": "2026-10-17 14:35:12",
    "utm_source": "google_ads",
    "utm_campaign": "fall_lead_gen"
  },
  "lead": {
    "full_name": "Sarah Connor",
    "email": "sarah@cyberdyne.com",
    "phone": "+1-555-8392",
    "company": "Cyberdyne Systems"
  },
  "answers": [
    { "question": "What is your monthly lead volume?", "answer": "100 - 1,000 leads / month", "points": 25 },
    { "question": "What CRM do you use?", "answer": "HubSpot", "points": 20 }
  ]
}</pre>
        </div>

        <!-- Security: HMAC Verification -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-amber-600 text-white flex items-center justify-center text-xs">🛡️</span>
                Security: Signature Verification (HMAC SHA-256)
            </h3>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                To make sure the request <strong>truly came from QFunnel</strong> and not an impostor, every webhook includes a special header:
                <code class="bg-slate-100 px-1 py-0.5 rounded font-mono text-indigo-700">X-QFunnel-Signature</code>.
            </p>

            <div class="space-y-2">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Verification Code (PHP):</span>
                <pre class="bg-slate-900 text-indigo-300 p-3.5 rounded-lg font-mono text-xs overflow-x-auto leading-relaxed">$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_QFUNNEL_SIGNATURE'] ?? '';
$secret = 'YOUR_WEBHOOK_SECRET'; // From Funnel Settings

$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    die('Invalid signature!');
}

// Signature is 100% verified! Process lead safely:
$data = json_decode($payload, true);
http_response_code(200);</pre>
            </div>
        </div>

        <!-- Auto-Retry Policy -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-3">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Automatic Retry Policy (Never Lose a Lead)</h3>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                If your receiving server is temporarily down, times out, or returns an error (anything other than HTTP 200 OK), QFunnel <strong>automatically retries 5 times</strong> with smart exponential backoff:
            </p>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2 text-center text-xs">
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="font-bold text-slate-900 block">Try 1</span>
                    <span class="text-indigo-600 font-semibold text-[11px]">Instant</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="font-bold text-slate-900 block">Try 2</span>
                    <span class="text-indigo-600 font-semibold text-[11px]">After 1 min</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="font-bold text-slate-900 block">Try 3</span>
                    <span class="text-indigo-600 font-semibold text-[11px]">After 5 mins</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="font-bold text-slate-900 block">Try 4</span>
                    <span class="text-indigo-600 font-semibold text-[11px]">After 30 mins</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="font-bold text-slate-900 block">Try 5</span>
                    <span class="text-indigo-600 font-semibold text-[11px]">After 2 hours</span>
                </div>
            </div>
        </div>

        <!-- How to Test in 2 Minutes -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 space-y-2 text-xs sm:text-sm text-emerald-950">
            <h4 class="font-bold text-emerald-900 flex items-center gap-2">
                <span>🧪</span>
                How to Test Webhooks in 2 Minutes (Free & Instant):
            </h4>
            <ol class="list-decimal pl-5 space-y-1.5 leading-relaxed">
                <li>Go to free testing website: <a href="https://webhook.site" target="_blank" class="underline font-bold text-emerald-700">webhook.site</a> and copy your unique test URL.</li>
                <li>Go to your Funnel in QFunnel -> click <strong>Settings</strong> -> <strong>Webhooks</strong>.</li>
                <li>Paste your test URL and click <strong>Save & Test</strong>.</li>
                <li>Switch back to webhook.site to see your lead payload appear live on screen!</li>
            </ol>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- TAB 3: EMBEDDING ("Embading")                            -->
    <!-- ======================================================== -->
    <div id="docTab-embed" class="hidden space-y-6">

        <!-- Simple Explanation Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center shrink-0 text-lg font-black">
                    🖼️
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">What is Website Embedding? (Easy Explanation)</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                        Think of embedding like hanging a <strong>picture frame on your living room wall</strong>. Your website (WordPress, Shopify, etc.) is the wall, and the quiz is the picture inside the frame. It lets your visitors take your interactive quiz directly on your own website without sending them away!
                    </p>
                </div>
            </div>

            <!-- Graphical Comparison of 3 Embed Modes -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-900">Mode 1: Inline Widget</span>
                        <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-bold text-[10px]">Popular</span>
                    </div>
                    <div class="h-20 bg-white border border-dashed border-slate-300 rounded flex items-center justify-center text-xs text-slate-400 font-mono">
                        [ Inside Page Content ]
                    </div>
                    <p class="text-[11px] text-slate-500">Blends naturally inside your blog post or landing page section.</p>
                </div>

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-900">Mode 2: Full Screen</span>
                        <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-700 font-bold text-[10px]">100% Height</span>
                    </div>
                    <div class="h-20 bg-white border border-dashed border-purple-300 rounded flex items-center justify-center text-xs text-purple-400 font-mono">
                        [ Full Screen 100vh ]
                    </div>
                    <p class="text-[11px] text-slate-500">Takes over the entire screen for a distraction-free experience.</p>
                </div>

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-900">Mode 3: Floating Popup</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-bold text-[10px]">Bottom-Right</span>
                    </div>
                    <div class="h-20 bg-white border border-dashed border-emerald-300 rounded relative flex items-end justify-end p-2 text-xs font-mono">
                        <span class="bg-indigo-600 text-white px-2 py-1 rounded text-[10px]">💬 Take Quiz</span>
                    </div>
                    <p class="text-[11px] text-slate-500">Floating trigger button at the corner that opens a clean modal popup.</p>
                </div>
            </div>
        </div>

        <!-- Mode 1 Code: Inline Embed -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center text-xs">1</span>
                    Mode 1: Inline Embed Code (Recommended)
                </h3>
                <button type="button" onclick="copyCode('codeEmbedInline')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">Copy Code</button>
            </div>
            <p class="text-xs sm:text-sm text-slate-600">
                Copy and paste this HTML snippet directly into any page. It includes responsive auto-height to prevent double scrollbars:
            </p>
            <pre id="codeEmbedInline" class="bg-slate-900 text-slate-200 p-4 rounded-xl font-mono text-xs overflow-x-auto leading-relaxed">&lt;!-- QFunnel Inline Embed Widget --&gt;
&lt;div style="width: 100%; max-width: 800px; margin: 0 auto;"&gt;
  &lt;iframe 
    src="<?= e($baseUrl) ?>/quiz/<?= e($sampleFunnelSlug) ?>" 
    id="qfunnel-frame"
    style="width: 100%; min-height: 600px; border: none; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);" 
    allow="camera; microphone; autoplay; fullscreen"
    loading="lazy"&gt;
  &lt;/iframe&gt;
&lt;/div&gt;</pre>
        </div>

        <!-- Mode 3 Code: Floating Popup Modal -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-emerald-600 text-white flex items-center justify-center text-xs">2</span>
                    Mode 3: Floating Popup Launcher Button
                </h3>
                <button type="button" onclick="copyCode('codeEmbedPopup')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">Copy Code</button>
            </div>
            <p class="text-xs sm:text-sm text-slate-600">
                Add this code to your website footer to show a floating pill button in the bottom right corner that opens a clean lightbox modal:
            </p>
            <pre id="codeEmbedPopup" class="bg-slate-900 text-slate-200 p-4 rounded-xl font-mono text-xs overflow-x-auto leading-relaxed">&lt;!-- QFunnel Floating Trigger Button --&gt;
&lt;button onclick="document.getElementById('qfunnelModal').style.display='flex'" 
  style="position: fixed; bottom: 24px; right: 24px; z-index: 99999; padding: 14px 22px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; font-weight: bold; border: none; border-radius: 50px; box-shadow: 0 10px 25px rgba(99,102,241,0.5); cursor: pointer; font-family: sans-serif; display: flex; items-center; gap: 8px;"&gt;
  ⚡ Take 60s Quiz
&lt;/button&gt;

&lt;!-- QFunnel Modal Lightbox --&gt;
&lt;div id="qfunnelModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 16px;"&gt;
  &lt;div style="position: relative; width: 100%; max-width: 750px; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);"&gt;
    &lt;button onclick="document.getElementById('qfunnelModal').style.display='none'" style="position: absolute; top: 14px; right: 16px; background: #f1f5f9; border: none; font-size: 18px; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; z-index: 10;"&gt;✕&lt;/button&gt;
    &lt;iframe src="<?= e($baseUrl) ?>/quiz/<?= e($sampleFunnelSlug) ?>" style="width: 100%; height: 650px; border: none;" allow="camera; microphone; autoplay; fullscreen"&gt;&lt;/iframe&gt;
  &lt;/div&gt;
&lt;/div&gt;</pre>
        </div>

        <!-- Platform Instructions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- WordPress Guide -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 font-bold flex items-center justify-center text-xs border border-blue-100">W</span>
                    <h4 class="font-bold text-sm text-slate-900">How to Embed in WordPress</h4>
                </div>
                <ol class="list-decimal pl-5 space-y-1.5 text-xs text-slate-600 leading-relaxed">
                    <li>Edit your page or post in WordPress.</li>
                    <li>Click the <strong>+ (Add block)</strong> icon and search for <strong>Custom HTML</strong>.</li>
                    <li>Paste the QFunnel embed snippet inside the block.</li>
                    <li>Click <strong>Update / Publish</strong>. Your quiz is live!</li>
                    <li><em>Using Elementor?</em> Just drag the <strong>HTML Widget</strong> onto the page and paste.</li>
                </ol>
            </div>

            <!-- Shopify Guide -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 font-bold flex items-center justify-center text-xs border border-emerald-100">S</span>
                    <h4 class="font-bold text-sm text-slate-900">How to Embed in Shopify</h4>
                </div>
                <ol class="list-decimal pl-5 space-y-1.5 text-xs text-slate-600 leading-relaxed">
                    <li>In Shopify Admin, go to <strong>Online Store -> Pages</strong>.</li>
                    <li>Click <strong>Add Page</strong> (e.g. "Find Your Size Quiz").</li>
                    <li>In the text editor, click the <strong>Show HTML (&lt;&gt;)</strong> button.</li>
                    <li>Paste the embed code snippet and click <strong>Save</strong>.</li>
                    <li><em>Using Theme Customizer?</em> Click <strong>Add section -> Custom Liquid</strong> and paste.</li>
                </ol>
            </div>

        </div>

    </div>

</div>

<!-- Copy to Clipboard & Tab Switching Script -->
<script>
function switchDocTab(tabName) {
    // Hide all tab contents
    document.getElementById('docTab-api').classList.add('hidden');
    document.getElementById('docTab-webhooks').classList.add('hidden');
    document.getElementById('docTab-embed').classList.add('hidden');

    // Reset button styles
    const tabBtns = document.querySelectorAll('.doc-tab-btn');
    tabBtns.forEach(btn => {
        btn.className = 'doc-tab-btn flex-1 min-w-[130px] sm:flex-none px-5 py-2.5 rounded-lg font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100';
    });

    // Show target tab
    const activeTab = document.getElementById('docTab-' + tabName);
    if (activeTab) activeTab.classList.remove('hidden');

    // Highlight target button
    const activeBtn = document.getElementById('tabBtn-' + tabName);
    if (activeBtn) {
        activeBtn.className = 'doc-tab-btn flex-1 min-w-[130px] sm:flex-none px-5 py-2.5 rounded-lg font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 bg-indigo-600 text-white shadow-sm';
    }
}

function copyCode(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = el.innerText || el.textContent;

    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    }).catch(() => {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Copied to clipboard!');
    });
}
</script>
