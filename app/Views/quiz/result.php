<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Your Quiz Summary') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', system-ui, sans-serif; background-color: #F7F8FA; color: #171A21; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-[#F7F8FA]">
    <div class="max-w-md w-full bg-white border border-[#E4E7EC] rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
        <div class="text-center">
            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xl flex items-center justify-center mx-auto mb-3">✓</div>
            <h1 class="text-xl font-bold text-[#171A21]">Your Verified Quiz Summary</h1>
            <p class="text-xs text-[#687080] mt-1"><?= e($result['funnel_name']) ?> • Completed <?= date('M d, Y H:i', strtotime($result['completed_at'])) ?></p>
        </div>

        <div class="p-4 bg-[#F7F8FA] border border-[#E4E7EC] rounded-xl space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-[#E4E7EC]">
                <span class="text-xs font-semibold text-[#171A21]">Assigned Result:</span>
                <span class="text-xs font-bold text-[#5B5BD6]"><?= e($result['summary']['destination_name'] ?? 'Result') ?></span>
            </div>

            <div class="space-y-2 text-xs">
                <?php foreach ($result['summary']['answers'] ?? [] as $ans): ?>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-[#687080] truncate max-w-[200px]"><?= e($ans['question'] ?? $ans['question_text_snapshot'] ?? '') ?></span>
                        <strong class="text-[#171A21]"><?= e($ans['answer'] ?? $ans['answer_value_text'] ?? '') ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($result['summary']['destination_url'])): ?>
            <a href="<?= e($result['summary']['destination_url']) ?>" target="_blank" class="inline-flex items-center justify-center w-full py-3 rounded-xl bg-[#5B5BD6] text-white text-xs font-semibold shadow hover:bg-[#4B4BC0]">
                Visit Destination Resource →
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
