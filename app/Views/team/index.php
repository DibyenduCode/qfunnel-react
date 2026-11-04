<?php
$memberItems = $members['items'] ?? $members;
$totalMembers = $members['total'] ?? count($memberItems);
$teamLimit = $usage['team_members']['limit'] ?? 5;
$teamUsed = $usage['team_members']['used'] ?? $totalMembers;
$isUnlimited = !empty($usage['team_members']['is_unlimited']);
?>
<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#171A21]">Team Management</h2>
            <p class="text-xs text-[#687080] mt-0.5">Manage user access, role permissions, and override member credentials (email & password) anytime.</p>
        </div>

        <div class="flex items-center gap-2">
            <?php if ($isOwner): ?>
                <button type="button" onclick="openAddMemberModal()" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Team Member
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Usage & Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div class="p-4 bg-white border border-[#E4E7EC] rounded-xl shadow-sm space-y-1">
            <span class="text-[#687080] font-medium block">Total Team Seats</span>
            <div class="flex items-baseline gap-2">
                <span class="text-xl font-bold text-[#171A21]"><?= $teamUsed ?></span>
                <span class="text-xs text-[#687080]">/ <?= $isUnlimited ? 'Unlimited' : $teamLimit ?> seats</span>
            </div>
            <p class="text-[11px] text-[#687080]"><?= e($usage['plan_name'] ?? 'Pro') ?> Plan</p>
        </div>

        <div class="p-4 bg-white border border-[#E4E7EC] rounded-xl shadow-sm space-y-1">
            <span class="text-[#687080] font-medium block">Workspace Control</span>
            <span class="text-xl font-bold text-[#171A21]"><?= $isOwner ? 'Full Authority' : 'Member View' ?></span>
            <p class="text-[11px] text-emerald-600 font-medium">Owner credential override active</p>
        </div>

        <div class="p-4 bg-white border border-[#E4E7EC] rounded-xl shadow-sm space-y-1">
            <span class="text-[#687080] font-medium block">Permission Levels</span>
            <span class="text-xl font-bold text-[#171A21]">4 Roles</span>
            <p class="text-[11px] text-[#687080]">Owner, Admin, Editor, Analyst</p>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="<?= url('/admin/team') ?>" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative w-full md:w-64">
                <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search by name or email..."
                       class="w-full pl-9 pr-3.5 py-1.5 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] placeholder-[#687080] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                <svg class="w-4 h-4 text-[#687080] absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="role" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                <option value="">All Roles</option>
                <option value="owner" <?= ($roleFilter ?? '') === 'owner' ? 'selected' : '' ?>>Workspace Owner</option>
                <option value="admin" <?= ($roleFilter ?? '') === 'admin' ? 'selected' : '' ?>>Workspace Admin</option>
                <option value="editor" <?= ($roleFilter ?? '') === 'editor' ? 'selected' : '' ?>>Funnel Editor</option>
                <option value="analyst" <?= ($roleFilter ?? '') === 'analyst' ? 'selected' : '' ?>>Viewer / Analyst</option>
            </select>

            <?php if (!empty($search) || !empty($roleFilter)): ?>
                <a href="<?= url('/admin/team') ?>" class="text-xs text-[#687080] hover:text-[#171A21] underline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Team Members Table -->
    <div class="bg-white border border-[#E4E7EC] rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#E4E7EC] bg-[#F7F8FA] text-[11px] font-semibold text-[#687080] uppercase tracking-wider">
                        <th class="py-3 px-4">Member</th>
                        <th class="py-3 px-4">Email Address</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Joined Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4E7EC]">
                    <?php if (empty($memberItems)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No team members found matching criteria.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($memberItems as $m): ?>
                        <tr class="hover:bg-[#F7F8FA] transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-[#171A21]">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-[#EEF0FD] text-[#5B5BD6] font-bold text-xs flex items-center justify-center shrink-0 border border-[#DCDDF9]">
                                        <?= strtoupper(substr($m['name'] ?: 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <span><?= e($m['name']) ?></span>
                                        <?php if ((int)$m['user_id'] === $currentUserId): ?>
                                            <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">You</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-[#687080]">
                                <?= e($m['email']) ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ($m['role'] === 'owner'): ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        Owner
                                    </span>
                                <?php elseif ($m['role'] === 'admin'): ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Admin
                                    </span>
                                <?php elseif ($m['role'] === 'editor'): ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Editor
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        Viewer
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if (($m['user_status'] ?? 'active') === 'active'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Suspended
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-[#687080]">
                                <?= date('M d, Y', strtotime($m['joined_at'])) ?>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <?php if ($isOwner): ?>
                                        <button type="button" 
                                                onclick='openEditMemberModal(<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                class="px-2.5 py-1 rounded-lg border border-[#E4E7EC] hover:bg-[#F7F8FA] text-xs font-semibold text-[#171A21] flex items-center gap-1 transition-colors"
                                                title="Change Email, Password or Role">
                                            <svg class="w-3.5 h-3.5 text-[#687080]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($isOwner && $m['role'] !== 'owner'): ?>
                                        <form method="POST" action="<?= url("/admin/team/{$m['membership_id']}/remove") ?>" class="inline" onsubmit="return confirm('Remove team member <?= e($m['name']) ?> from this workspace?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded transition-colors" title="Remove Member">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($members['total_pages'])): ?>
            <?php
            $pagination = $members;
            $queryParams = array_filter(['search' => $search ?? '', 'role' => $roleFilter ?? '']);
            require __DIR__ . '/../partials/pagination.php';
            ?>
        <?php endif; ?>
    </div>

</div>

<!-- Modal: Add Team Member -->
<div id="addMemberModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-[#E4E7EC]">
        <div class="px-6 py-4 border-b border-[#E4E7EC] flex items-center justify-between bg-[#F7F8FA]">
            <div>
                <h3 class="text-sm font-bold text-[#171A21]">Add Team Member</h3>
                <p class="text-[11px] text-[#687080]">Create an account and set initial access credentials.</p>
            </div>
            <button type="button" onclick="closeAddMemberModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="<?= url('/admin/team') ?>" class="p-6 space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="e.g. Sarah Jenkins"
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Email Address <span class="text-rose-500">*</span></label>
                <input type="email" name="email" required placeholder="sarah@company.com"
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-[#171A21] uppercase">Initial Password</label>
                    <button type="button" onclick="generateRandomPassword('addPassword')" class="text-[11px] text-[#5B5BD6] font-semibold hover:underline">
                        Generate Random
                    </button>
                </div>
                <div class="relative">
                    <input type="text" name="password" id="addPassword" value="Password123!" required
                           class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs font-mono text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                </div>
                <p class="text-[11px] text-[#687080] mt-1">Minimum 8 characters. Share this password with the member to log in.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Workspace Role</label>
                <select name="role" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white focus:ring-2 focus:ring-[#5B5BD6]">
                    <option value="editor">Editor (Can build, edit, and organize funnels)</option>
                    <option value="admin">Admin (Can edit, publish, and manage submissions)</option>
                    <option value="analyst">Analyst / Viewer (Read-only access to responses)</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E4E7EC]">
                <button type="button" onclick="closeAddMemberModal()" class="px-3.5 py-2 rounded-lg border border-[#E4E7EC] text-xs font-semibold text-[#687080] hover:bg-[#F7F8FA]">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold hover:bg-[#4B4BC0] shadow-sm">
                    Add Member
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Team Member (Owner Credential & Access Override) -->
<div id="editMemberModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-[#E4E7EC]">
        <div class="px-6 py-4 border-b border-[#E4E7EC] flex items-center justify-between bg-[#F7F8FA]">
            <div>
                <h3 class="text-sm font-bold text-[#171A21]">Edit Team Member</h3>
                <p class="text-[11px] text-[#687080]">Owner priority: update member email, password, or permissions anytime.</p>
            </div>
            <button type="button" onclick="closeEditMemberModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="editMemberForm" method="POST" action="" class="p-6 space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="editName" required
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Email Address <span class="text-rose-500">*</span></label>
                <input type="email" name="email" id="editEmail" required
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] focus:ring-2 focus:ring-[#5B5BD6]">
                <p class="text-[11px] text-[#687080] mt-1">Changing the email updates their login account address.</p>
            </div>

            <div class="p-3 bg-[#F7F8FA] border border-[#E4E7EC] rounded-lg space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-[#171A21] uppercase">Reset / Change Password</label>
                    <button type="button" onclick="generateRandomPassword('editPassword')" class="text-[11px] text-[#5B5BD6] font-semibold hover:underline">
                        Generate
                    </button>
                </div>
                <input type="text" name="new_password" id="editPassword" placeholder="Enter new password (optional)"
                       class="w-full px-3 py-1.5 rounded-lg border border-[#E4E7EC] text-xs font-mono text-[#171A21] bg-white focus:ring-2 focus:ring-[#5B5BD6]">
                <p class="text-[11px] text-[#687080]">Leave blank to keep existing password. Min 8 characters.</p>
            </div>

            <div id="roleSelectContainer">
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Workspace Role</label>
                <select name="role" id="editRole" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white focus:ring-2 focus:ring-[#5B5BD6]">
                    <option value="editor">Editor (Can build & edit funnels)</option>
                    <option value="admin">Admin (Full funnel control & publishing)</option>
                    <option value="analyst">Analyst / Viewer (Read-only access)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Access Status</label>
                <select name="status" id="editStatus" class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs text-[#171A21] bg-white focus:ring-2 focus:ring-[#5B5BD6]">
                    <option value="active">Active (Access allowed)</option>
                    <option value="suspended">Suspended (Access blocked)</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E4E7EC]">
                <button type="button" onclick="closeEditMemberModal()" class="px-3.5 py-2 rounded-lg border border-[#E4E7EC] text-xs font-semibold text-[#687080] hover:bg-[#F7F8FA]">
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
function openAddMemberModal() {
    document.getElementById('addMemberModal').classList.remove('hidden');
}

function closeAddMemberModal() {
    document.getElementById('addMemberModal').classList.add('hidden');
}

function openEditMemberModal(member) {
    const modal = document.getElementById('editMemberModal');
    const form = document.getElementById('editMemberForm');
    const nameInput = document.getElementById('editName');
    const emailInput = document.getElementById('editEmail');
    const passwordInput = document.getElementById('editPassword');
    const roleSelect = document.getElementById('editRole');
    const statusSelect = document.getElementById('editStatus');
    const roleContainer = document.getElementById('roleSelectContainer');

    form.action = '<?= url("/admin/team") ?>/' + member.membership_id + '/update';
    nameInput.value = member.name || '';
    emailInput.value = member.email || '';
    passwordInput.value = '';
    
    if (member.role === 'owner') {
        roleContainer.classList.add('opacity-50', 'pointer-events-none');
    } else {
        roleContainer.classList.remove('opacity-50', 'pointer-events-none');
        if (roleSelect) roleSelect.value = member.role;
    }

    if (statusSelect) {
        statusSelect.value = member.user_status || 'active';
    }

    modal.classList.remove('hidden');
}

function closeEditMemberModal() {
    document.getElementById('editMemberModal').classList.add('hidden');
}

function generateRandomPassword(elementId) {
    const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const input = document.getElementById(elementId);
    if (input) {
        input.value = password;
    }
}
</script>
