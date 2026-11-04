<div class="max-w-4xl space-y-6">

    <!-- Workspace Details Card -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-[#171A21]">Workspace Details</h3>

        <form action="<?= url('/admin/settings/workspace') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Workspace Name</label>
                    <input type="text" name="name" value="<?= e($tenant['name']) ?>" required
                           class="w-full px-3.5 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#687080] uppercase mb-1">Workspace Slug</label>
                    <input type="text" value="<?= e($tenant['slug']) ?>" disabled
                           class="w-full px-3.5 py-2 rounded-lg border border-[#E4E7EC] text-xs font-mono text-[#687080] bg-[#F7F8FA]">
                    <p class="text-[11px] text-[#687080] mt-1">Unique URL-safe identifier for your workspace (used for tenant routing and custom subdomains).</p>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#171A21] text-white text-xs font-semibold hover:bg-black">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Developer REST API Access Card -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-[#171A21]">Developer REST API Access</h3>
                <p class="text-xs text-[#687080]">Programmatic workspace integration for fetching funnels and captured leads.</p>
            </div>
            <?php if (!empty($hasApiAccess)): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ● API Active
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-700 border border-purple-200">
                    ★ Pro Plan Feature
                </span>
            <?php endif; ?>
        </div>

        <?php if (!empty($hasApiAccess)): ?>
            <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50 space-y-2">
                <label class="block text-xs font-semibold text-slate-700">Workspace API Secret Key</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="workspaceApiKeyInput" readonly value="<?= e($tenant['api_key'] ?? 'No key generated') ?>" class="w-full px-3 py-1.5 text-xs font-mono rounded-lg border border-slate-200 bg-white text-slate-800">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('workspaceApiKeyInput').value); this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy', 2000);" class="shrink-0 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-2xs transition-colors">
                        Copy
                    </button>
                </div>
                <div class="pt-2 text-[11px] text-slate-500 space-y-1">
                    <p>Include this key in your HTTP requests as <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-mono text-indigo-600">Authorization: Bearer <?= e($tenant['api_key'] ?? '') ?></code></p>
                    <p>Endpoints: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-mono text-slate-700">GET /api/v1/funnels</code>, <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-mono text-slate-700">GET /api/v1/submissions</code>, <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-mono text-slate-700">GET /api/v1/me</code></p>
                </div>
            </div>
        <?php else: ?>
            <div class="p-3.5 rounded-lg border border-purple-200 bg-purple-50/60 text-xs text-purple-900 flex items-start gap-3">
                <span class="text-base">🔒</span>
                <div>
                    <strong class="block font-bold">REST API Access is locked on your current plan</strong>
                    <p class="text-[11px] text-purple-800 mt-0.5"><a href="<?= url('/admin/billing') ?>" class="font-bold underline text-purple-900 hover:text-indigo-700">Upgrade your subscription plan</a> to Pro to unlock developer API tokens, bearer authentication, and programmatic lead access.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Plan & Usage Limits Overview -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-[#171A21]">Subscription & Usage Quotas</h3>
                <p class="text-xs text-[#687080]">Current tier limits and resource allocations for this workspace.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-[#5B5BD6] border border-indigo-200">
                    <?= e($usage['plan_name']) ?> (<?= ucfirst($usage['status']) ?>)
                </span>
                <a href="<?= url('/admin/billing') ?>" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold text-white bg-[#5B5BD6] hover:bg-[#4B4BC0] shadow-sm transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Upgrade Plan
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs pt-2">
            <div class="p-4 bg-[#F7F8FA] rounded-xl border border-[#E4E7EC] space-y-2">
                <div class="flex items-center justify-between text-[#687080]">
                    <span>Funnels Created</span>
                    <span class="font-bold text-[#171A21]"><?= $usage['funnels']['used'] ?> / <?= $usage['funnels']['limit'] ?></span>
                </div>
                <div class="w-full bg-[#E4E7EC] h-2 rounded-full overflow-hidden">
                    <div class="bg-[#5B5BD6] h-full" style="width: <?= $usage['funnels']['percentage'] ?>%"></div>
                </div>
            </div>

            <div class="p-4 bg-[#F7F8FA] rounded-xl border border-[#E4E7EC] space-y-2">
                <div class="flex items-center justify-between text-[#687080]">
                    <span>Monthly Submissions</span>
                    <span class="font-bold text-[#171A21]"><?= $usage['submissions']['used'] ?> / <?= $usage['submissions']['limit'] ?></span>
                </div>
                <div class="w-full bg-[#E4E7EC] h-2 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-full" style="width: <?= $usage['submissions']['percentage'] ?>%"></div>
                </div>
            </div>

            <div class="p-4 bg-[#F7F8FA] rounded-xl border border-[#E4E7EC] space-y-2">
                <div class="flex items-center justify-between text-[#687080]">
                    <span>Team Members</span>
                    <span class="font-bold text-[#171A21]"><?= $usage['team_members']['used'] ?> / <?= $usage['team_members']['limit'] ?></span>
                </div>
                <div class="w-full bg-[#E4E7EC] h-2 rounded-full overflow-hidden">
                    <div class="bg-purple-500 h-full" style="width: <?= $usage['team_members']['percentage'] ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members Card -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-[#171A21]">Workspace Team Members</h3>
            <span class="text-xs text-[#687080]"><?= count($members) ?> members</span>
        </div>

        <div class="divide-y divide-[#E4E7EC]">
            <?php foreach ($members as $m): ?>
                <div class="py-3 flex items-center justify-between text-xs">
                    <div>
                        <p class="font-semibold text-[#171A21]"><?= e($m['name']) ?></p>
                        <p class="text-[11px] text-[#687080]"><?= e($m['email']) ?> • Joined <?= date('M d, Y', strtotime($m['joined_at'])) ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="capitalize px-2 py-0.5 rounded text-[11px] font-semibold bg-[#F1F3F6] text-[#171A21] border border-[#E4E7EC]">
                            <?= e($m['role']) ?>
                        </span>
                        <?php if ($m['role'] !== 'owner'): ?>
                            <form method="POST" action="<?= url("/admin/settings/workspace/members/{$m['membership_id']}/remove") ?>" onsubmit="return confirm('Remove team member?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-slate-400 hover:text-rose-600 text-xs">Remove</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Member Form -->
        <div class="pt-4 border-t border-[#E4E7EC]">
            <h4 class="text-xs font-semibold text-[#171A21] mb-3">Add Team Member</h4>
            <form action="<?= url('/admin/settings/workspace/invite') ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <?= csrf_field() ?>
                <div>
                    <input type="text" name="name" placeholder="Full Name" required class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC]">
                </div>
                <div>
                    <input type="email" name="email" placeholder="Email Address" required class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC]">
                </div>
                <div>
                    <select name="role" class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] bg-white">
                        <option value="editor">Editor (Build & edit funnels)</option>
                        <option value="admin">Admin (Manage & publish)</option>
                        <option value="analyst">Analyst (View metrics only)</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full py-1.5 rounded-lg bg-[#5B5BD6] text-white font-semibold hover:bg-[#4B4BC0]">
                        Add Member
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
