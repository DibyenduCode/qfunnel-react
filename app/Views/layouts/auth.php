<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Quiz Funnel SaaS') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background-color: #F7F8FA; color: #171A21; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-[#F7F8FA]">
    <div class="max-w-md w-full">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-[#5B5BD6] text-white font-bold text-xl mb-3 shadow-sm">
                QF
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-[#171A21]">Quiz Funnel SaaS</h1>
            <p class="text-sm text-[#687080] mt-1">Production-ready visual branching quiz builder</p>
        </div>

        <!-- Flash Messages -->
        <?php if ($success = flash('success')): ?>
            <div class="p-3.5 mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span><?= e($success) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error = flash('error')): ?>
            <div class="p-3.5 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Card Container -->
        <div class="bg-white border border-[#E4E7EC] rounded-xl p-8 shadow-sm">
            <?= $content ?>
        </div>
    </div>
</body>
</html>
