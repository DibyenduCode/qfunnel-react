<div class="space-y-6 max-w-5xl">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Payment Gateway Settings</h2>
            <p class="text-xs text-slate-500 mt-0.5">Configure automated checkout providers and manual bank/offline transfer instructions.</p>
        </div>
    </div>

    <form method="POST" action="<?= url('/admin/platform/payment-gateways') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <!-- SECTION 1: Automated Gateways -->
        <div class="space-y-4">
            <div class="border-b border-[#E2E8F0] pb-2">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Automated Online Gateways
                </h3>
                <p class="text-xs text-slate-500">Self-serve instant credit card & digital wallet processing.</p>
            </div>

            <!-- Stripe Card -->
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-[#635BFF]/10 text-[#635BFF] flex items-center justify-center font-bold text-base">
                            S
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Stripe Payments</h4>
                            <p class="text-xs text-slate-500">Credit / Debit cards, Apple Pay, Google Pay</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="stripe_enabled" value="1" <?= !empty($gateways['stripe']['enabled']) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-2 text-xs font-semibold text-slate-700 peer-checked:text-indigo-600">Enable</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Environment Mode</label>
                        <select name="stripe_mode" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="test" <?= ($gateways['stripe']['mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>Test / Sandbox</option>
                            <option value="live" <?= ($gateways['stripe']['mode'] ?? 'test') === 'live' ? 'selected' : '' ?>>Live / Production</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Publishable Key</label>
                        <input type="text" name="stripe_publishable_key" value="<?= e($gateways['stripe']['publishable_key'] ?? '') ?>" placeholder="pk_test_..." class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Secret Key</label>
                        <input type="password" name="stripe_secret_key" value="<?= e($gateways['stripe']['secret_key'] ?? '') ?>" placeholder="sk_test_..." class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Webhook Secret (Optional)</label>
                        <input type="password" name="stripe_webhook_secret" value="<?= e($gateways['stripe']['webhook_secret'] ?? '') ?>" placeholder="whsec_..." class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- PayPal Card -->
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-[#003087]/10 text-[#003087] flex items-center justify-center font-bold text-base">
                            P
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">PayPal Checkout</h4>
                            <p class="text-xs text-slate-500">PayPal Wallet, Pay in 4, and alternative regional rails</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="paypal_enabled" value="1" <?= !empty($gateways['paypal']['enabled']) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-2 text-xs font-semibold text-slate-700 peer-checked:text-indigo-600">Enable</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Environment Mode</label>
                        <select name="paypal_mode" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="sandbox" <?= ($gateways['paypal']['mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                            <option value="live" <?= ($gateways['paypal']['mode'] ?? 'sandbox') === 'live' ? 'selected' : '' ?>>Live</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Client ID</label>
                        <input type="text" name="paypal_client_id" value="<?= e($gateways['paypal']['client_id'] ?? '') ?>" placeholder="PayPal Client ID" class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Client Secret</label>
                        <input type="password" name="paypal_client_secret" value="<?= e($gateways['paypal']['client_secret'] ?? '') ?>" placeholder="PayPal Secret" class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Razorpay Card -->
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-[#0C2340]/10 text-[#0C2340] flex items-center justify-center font-bold text-base">
                            R
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Razorpay</h4>
                            <p class="text-xs text-slate-500">UPI, Net Banking, and Cards</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="razorpay_enabled" value="1" <?= !empty($gateways['razorpay']['enabled']) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ml-2 text-xs font-semibold text-slate-700 peer-checked:text-indigo-600">Enable</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Key ID</label>
                        <input type="text" name="razorpay_key_id" value="<?= e($gateways['razorpay']['key_id'] ?? '') ?>" placeholder="rzp_test_..." class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Key Secret</label>
                        <input type="password" name="razorpay_key_secret" value="<?= e($gateways['razorpay']['key_secret'] ?? '') ?>" placeholder="Razorpay Secret" class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Manual / Offline Gateways -->
        <div class="space-y-4 pt-4">
            <div class="border-b border-[#E2E8F0] pb-2">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    Manual & Offline Gateways
                </h3>
                <p class="text-xs text-slate-500">Direct wire transfer, cheque, invoice, or offline payments verified by admin.</p>
            </div>

            <!-- Bank Wire Transfer Card -->
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-base">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Direct Bank Wire / ACH Transfer</h4>
                            <p class="text-xs text-slate-500">Provide bank credentials to users for wire or SEPA transfers</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="bank_enabled" value="1" <?= !empty($gateways['bank_transfer']['enabled']) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-semibold text-slate-700 peer-checked:text-emerald-600">Enable</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" value="<?= e($gateways['bank_transfer']['bank_name'] ?? '') ?>" placeholder="e.g. JPMorgan Chase / SVB" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Account Holder Name</label>
                        <input type="text" name="bank_account_holder" value="<?= e($gateways['bank_transfer']['account_holder'] ?? '') ?>" placeholder="e.g. QuizFunnel Inc" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Account / IBAN Number</label>
                        <input type="text" name="bank_account_number" value="<?= e($gateways['bank_transfer']['account_number'] ?? '') ?>" placeholder="e.g. 123456789012" class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Routing / SWIFT / IFSC Code</label>
                        <input type="text" name="bank_routing_code" value="<?= e($gateways['bank_transfer']['routing_code'] ?? '') ?>" placeholder="e.g. CHASUS33XXX" class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Transfer Instructions (Shown to customers)</label>
                    <textarea name="bank_instructions" rows="3" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none"><?= e($gateways['bank_transfer']['instructions'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Custom / Offline / Cheque Card -->
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Custom / Invoice / Offline Option</h4>
                            <p class="text-xs text-slate-500">Corporate purchase order or customized manual billing</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="manual_enabled" value="1" <?= !empty($gateways['manual_custom']['enabled']) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                        <span class="ml-2 text-xs font-semibold text-slate-700 peer-checked:text-amber-600">Enable</span>
                    </label>
                </div>

                <div class="pt-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Custom Method Title</label>
                    <input type="text" name="manual_title" value="<?= e($gateways['manual_custom']['title'] ?? 'Corporate Purchase Order / Offline') ?>" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Instructions for Customers</label>
                    <textarea name="manual_instructions" rows="3" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none"><?= e($gateways['manual_custom']['instructions'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Sticky or prominent Save Action -->
        <div class="pt-4 flex items-center justify-end gap-3">
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md transition-colors flex items-center gap-2">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save Payment Settings
            </button>
        </div>
    </form>

</div>
