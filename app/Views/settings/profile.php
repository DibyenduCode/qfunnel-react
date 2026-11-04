<div class="max-w-2xl space-y-6">

    <div class="bg-white border border-[#E4E7EC] rounded-xl p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-[#171A21]">Your User Profile</h3>

        <form action="<?= url('/admin/settings/profile') ?>" method="POST" class="space-y-4 text-xs">
            <?= csrf_field() ?>

            <div>
                <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Full Name</label>
                <input type="text" name="name" value="<?= e($user['name']) ?>" required
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs">
            </div>

            <div>
                <label class="block font-semibold text-[#687080] uppercase text-[10px] mb-1">Email Address</label>
                <input type="email" value="<?= e($user['email']) ?>" disabled
                       class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs bg-[#F7F8FA] text-[#687080]">
            </div>

            <div class="pt-4 border-t border-[#E4E7EC] space-y-3">
                <h4 class="font-bold text-[#171A21]">Change Password</h4>
                <p class="text-[11px] text-[#687080]">Leave blank if you do not wish to change your password.</p>

                <div>
                    <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">Current Password</label>
                    <input type="password" name="current_password"
                           class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs">
                </div>

                <div>
                    <label class="block font-semibold text-[#171A21] uppercase text-[10px] mb-1">New Password (min 8 characters)</label>
                    <input type="password" name="new_password" minlength="8"
                           class="w-full px-3 py-2 rounded-lg border border-[#E4E7EC] text-xs">
                </div>
            </div>

            <div class="flex justify-end pt-3">
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#5B5BD6] text-white font-semibold hover:bg-[#4B4BC0]">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>

</div>
