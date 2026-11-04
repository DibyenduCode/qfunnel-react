<h2 class="text-xl font-bold text-[#171A21] mb-1">Sign in to your account</h2>
<p class="text-sm text-[#687080] mb-6">Enter your details to access your workspace</p>

<form action="<?= url('/login') ?>" method="POST" class="space-y-4">
    <?= csrf_field() ?>

    <div>
        <label for="email" class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Email address</label>
        <input type="email" id="email" name="email" value="<?= e(old('email', 'owner@qfunnel.local')) ?>" required
               class="w-full px-3.5 py-2.5 rounded-lg border border-[#E4E7EC] text-sm text-[#171A21] placeholder-[#687080] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6] focus:border-transparent">
    </div>

    <div>
        <div class="flex items-center justify-between mb-1">
            <label for="password" class="block text-xs font-semibold text-[#171A21] uppercase">Password</label>
        </div>
        <input type="password" id="password" name="password" value="Password123!" required
               class="w-full px-3.5 py-2.5 rounded-lg border border-[#E4E7EC] text-sm text-[#171A21] placeholder-[#687080] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6] focus:border-transparent">
    </div>

    <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-[#5B5BD6] text-white text-sm font-semibold shadow hover:bg-[#4B4BC0] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#5B5BD6]">
        Sign In
    </button>
</form>

<div class="mt-6 pt-6 border-t border-[#E4E7EC] text-center">
    <p class="text-xs text-[#687080]">
        Don't have an account yet? 
        <a href="<?= url('/register') ?>" class="text-[#5B5BD6] font-semibold hover:underline">Create a workspace</a>
    </p>
</div>

<div class="mt-6 p-3 bg-[#F1F3F6] rounded-lg text-xs text-[#687080] space-y-1">
    <p class="font-semibold text-[#171A21]">Pre-configured Test Accounts:</p>
    <p>• <strong>Owner:</strong> owner@qfunnel.local / Password123!</p>
    <p>• <strong>Super Admin:</strong> superadmin@qfunnel.local / Password123!</p>
</div>
