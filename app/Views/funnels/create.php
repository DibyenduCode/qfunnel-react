<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-[#171A21]">Create Funnel</h2>
            <p class="text-xs text-[#687080]">Configure your new branching quiz or interactive calculator funnel</p>
        </div>
        <a href="<?= url('/admin/funnels') ?>" class="text-xs text-[#687080] hover:text-[#171A21]">← Back to Funnels</a>
    </div>

    <div class="bg-white border border-[#E4E7EC] rounded-xl p-6 shadow-sm">
        <form action="<?= url('/admin/funnels/create') ?>" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Funnel Mode Selection Cards -->
            <div>
                <label class="block text-xs font-semibold text-[#171A21] uppercase mb-2">Funnel Engine & Mode *</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Quiz Funnel Card -->
                    <label id="mode-card-quiz" class="relative flex flex-col p-4 rounded-xl border-2 border-[#5B5BD6] bg-indigo-50/30 cursor-pointer transition-all hover:shadow-md select-none">
                        <input type="radio" name="funnel_mode" value="quiz" checked class="sr-only" onchange="updateModeSelection('quiz')">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl">🎯</span>
                            <span id="badge-quiz" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#5B5BD6] text-white">Selected</span>
                        </div>
                        <h3 class="text-xs font-bold text-[#171A21]">Lead & Quiz Funnel</h3>
                        <p class="text-[11px] text-[#687080] mt-1 leading-relaxed">
                            Interactive quiz for prospect qualification, scoring, and branching outcome routing.
                        </p>
                        <div class="mt-3 pt-2 border-t border-slate-200/60 flex items-center gap-1.5 text-[10px] font-medium text-indigo-700">
                            <span>✓ Starter graph seeded</span>
                        </div>
                    </label>

                    <!-- Calculator & Quote Funnel Card -->
                    <label id="mode-card-calculator" class="relative flex flex-col p-4 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all hover:shadow-md select-none hover:border-slate-300">
                        <input type="radio" name="funnel_mode" value="calculator" class="sr-only" onchange="updateModeSelection('calculator')">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl">🧮</span>
                            <span id="badge-calculator" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 hidden">Selected</span>
                        </div>
                        <h3 class="text-xs font-bold text-[#171A21]">Calculator & Quote Funnel</h3>
                        <p class="text-[11px] text-[#687080] mt-1 leading-relaxed">
                            Price estimator, ROI/EMI calculator, and instant quote generator powered by dynamic math formulas.
                        </p>
                        <div class="mt-3 pt-2 border-t border-slate-200/60 flex items-center gap-1.5 text-[10px] font-medium text-emerald-700">
                            <span>✨ Slider + Formula starter template</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label for="name" class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Funnel Title *</label>
                <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" placeholder="e.g. Website Cost Calculator & Instant Quote" required
                       class="w-full px-3.5 py-2 rounded-lg border border-[#E4E7EC] text-sm text-[#171A21] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
            </div>

            <div>
                <label for="slug" class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Public URL Slug</label>
                <div class="flex items-center">
                    <span class="px-3 py-2 bg-[#F1F3F6] border border-r-0 border-[#E4E7EC] rounded-l-lg text-xs text-[#687080]">/quiz/</span>
                    <input type="text" id="slug" name="slug" value="<?= e(old('slug')) ?>" placeholder="cost-calculator (auto-generated if empty)"
                           class="w-full px-3.5 py-2 rounded-r-lg border border-[#E4E7EC] text-sm text-[#171A21] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]">
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-[#171A21] uppercase mb-1">Description / Internal Notes</label>
                <textarea id="description" name="description" rows="3" placeholder="Brief explanation of this funnel's target audience or formula..."
                          class="w-full px-3.5 py-2 rounded-lg border border-[#E4E7EC] text-sm text-[#171A21] focus:outline-none focus:ring-2 focus:ring-[#5B5BD6]"><?= e(old('description')) ?></textarea>
            </div>

            <div class="pt-4 border-t border-[#E4E7EC] flex items-center justify-end gap-3">
                <a href="<?= url('/admin/funnels') ?>" class="px-4 py-2 rounded-lg border border-[#E4E7EC] text-xs font-semibold text-[#171A21] hover:bg-[#F7F8FA]">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#5B5BD6] text-white text-xs font-semibold shadow hover:bg-[#4B4BC0]">
                    Create & Open Builder
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateModeSelection(mode) {
        const quizCard = document.getElementById('mode-card-quiz');
        const calcCard = document.getElementById('mode-card-calculator');
        const quizBadge = document.getElementById('badge-quiz');
        const calcBadge = document.getElementById('badge-calculator');

        if (mode === 'quiz') {
            quizCard.className = 'relative flex flex-col p-4 rounded-xl border-2 border-[#5B5BD6] bg-indigo-50/30 cursor-pointer transition-all hover:shadow-md select-none';
            calcCard.className = 'relative flex flex-col p-4 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all hover:shadow-md select-none hover:border-slate-300';
            quizBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-[#5B5BD6] text-white';
            quizBadge.classList.remove('hidden');
            calcBadge.classList.add('hidden');
        } else {
            calcCard.className = 'relative flex flex-col p-4 rounded-xl border-2 border-emerald-600 bg-emerald-50/30 cursor-pointer transition-all hover:shadow-md select-none';
            quizCard.className = 'relative flex flex-col p-4 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all hover:shadow-md select-none hover:border-slate-300';
            calcBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white';
            calcBadge.classList.remove('hidden');
            quizBadge.classList.add('hidden');
        }
    }
</script>
