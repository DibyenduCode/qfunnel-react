<h2 class="text-xl font-bold text-[#171A21] mb-1">Create your workspace</h2>
<p class="text-sm text-[#687080] mb-6">Start building branching quiz funnels with a 14-day trial</p>

<form action="<?= url('/register') ?>" method="POST" class="space-y-4">
    <?= csrf_field() ?>

    <div>
        <label for="workspace" class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Company / Workspace Name</label>
        <input type="text" id="workspace" name="workspace" value="<?= e(old('workspace')) ?>" placeholder="e.g. Acme Corp" required
               class="w-full px-3.5 py-2.5 rounded-lg border border-[#E4E7EC] text-sm text-[#171A21] placeholder-[#687080] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6] focus:border-transparent">
    </div>

    <div>
        <label for="name" class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Your Full Name</label>
        <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" placeholder="e.g. John Doe" required
               class="w-full px-3.5 py-2.5 rounded-lg border border-[#E4E7EC] text-sm text-[#171A21] placeholder-[#687080] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6] focus:border-transparent">
    </div>

    <div>
        <label for="email" class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Work Email</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="john@acme.com" required
               class="w-full px-3.5 py-2.5 rounded-lg border border-[#E4E7EC] text-sm text-[#171A21] placeholder-[#687080] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6] focus:border-transparent">
    </div>

    <div>
        <label for="password" class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Password (min 8 characters)</label>
        <input type="password" id="password" name="password" minlength="8" required
               class="w-full px-3.5 py-2.5 rounded-lg border border-[#E4E7EC] text-sm text-[#171A21] placeholder-[#687080] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6] focus:border-transparent">
    </div>

    <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-[#5B5BD6] text-white text-sm font-semibold shadow hover:bg-[#4B4BC0] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#5B5BD6]">
        Create Workspace
    </button>
</form>

<div class="mt-6 pt-6 border-t border-[#E4E7EC] text-center">
    <p class="text-xs text-[#687080]">
        Already have an account? 
        <a href="<?= url('/login') ?>" class="text-[#5B5BD6] font-semibold hover:underline">Sign in</a>
    </p>
</div>
