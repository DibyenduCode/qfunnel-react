<div class="space-y-6 max-w-3xl">

    <!-- Header -->
    <div class="border-b border-[#E2E8F0] pb-4">
        <h2 class="text-xl font-bold text-slate-900">Super Admin Profile Settings</h2>
        <p class="text-xs text-slate-500 mt-0.5">Manage your platform administrator credentials, email address, and security credentials.</p>
    </div>

    <!-- Card 1: Profile Information (Name & Email) -->
    <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-5">
        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
            <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-extrabold text-xl flex items-center justify-center shadow-md">
                <?= strtoupper(substr($adminUser['name'] ?? 'S', 0, 1)) ?>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900"><?= e($adminUser['name']) ?></h3>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        Platform Master Admin
                    </span>
                    <span class="text-xs text-slate-400 font-mono"><?= e($adminUser['email']) ?></span>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= url('/admin/platform/profile') ?>" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="name" value="<?= e($adminUser['name']) ?>" required 
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address *</label>
                    <input type="email" name="email" value="<?= e($adminUser['email']) ?>" required 
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium">
                    <span class="text-[11px] text-slate-400 mt-1 block">Used to sign into the Super Admin panel.</span>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end">
                <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Update Profile Details
                </button>
            </div>
        </form>
    </div>

    <!-- Card 2: Change Password -->
    <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
        <div class="pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Change Admin Password
            </h3>
            <p class="text-xs text-slate-500">Ensure your administrative password is strong and unique.</p>
        </div>

        <form method="POST" action="<?= url('/admin/platform/profile/password') ?>" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Current Password *</label>
                <input type="password" name="current_password" required placeholder="Enter your current password" 
                       class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">New Password (Min 8 chars) *</label>
                    <input type="password" name="new_password" required placeholder="New password" 
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password *</label>
                    <input type="password" name="confirm_password" required placeholder="Repeat new password" 
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end">
                <button type="submit" class="px-5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>
