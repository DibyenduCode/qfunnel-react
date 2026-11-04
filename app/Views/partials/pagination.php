<?php
/**
 * Global Reusable Pagination Partial
 * 
 * Variables:
 * @var array $pagination [ 'page' => int, 'per_page' => int, 'total' => int, 'total_pages' => int ]
 * @var array|null $queryParams Active GET query parameters to preserve
 * @var string|null $pageParam Parameter name (default 'page')
 * @var string|null $baseUrl Target URL (default current script URI without query)
 */

if (!isset($pagination) || !is_array($pagination)) {
    return;
}

$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$total = (int)($pagination['total'] ?? 0);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$paramName = $pageParam ?? 'page';
$params = $queryParams ?? [];
$targetBaseUrl = $baseUrl ?? strtok($_SERVER['REQUEST_URI'] ?? '', '?');

if ($total === 0) {
    return;
}

$from = ($page - 1) * $perPage + 1;
$to = min($total, $page * $perPage);

$buildUrl = function(int $targetPage) use ($params, $paramName, $targetBaseUrl) {
    $merged = array_merge($params, [$paramName => $targetPage]);
    $query = http_build_query($merged);
    return $targetBaseUrl . ($query ? '?' . $query : '');
};

// Calculate visible page numbers
$pagesToShow = [];
if ($totalPages <= 7) {
    for ($i = 1; $i <= $totalPages; $i++) {
        $pagesToShow[] = $i;
    }
} else {
    $pagesToShow[] = 1;
    if ($page > 4) {
        $pagesToShow[] = '...';
    }
    
    $start = max(2, $page - 1);
    $end = min($totalPages - 1, $page + 1);
    
    if ($page <= 3) {
        $start = 2;
        $end = 4;
    }
    if ($page >= $totalPages - 2) {
        $start = $totalPages - 3;
        $end = $totalPages - 1;
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $pagesToShow[] = $i;
    }
    
    if ($page < $totalPages - 3) {
        $pagesToShow[] = '...';
    }
    $pagesToShow[] = $totalPages;
}
?>

<div class="px-4 py-3 border-t border-[#E4E7EC] bg-white flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#687080]">
    <div class="text-[11px] sm:text-xs">
        Showing <span class="font-semibold text-[#171A21]"><?= $from ?></span> to <span class="font-semibold text-[#171A21]"><?= $to ?></span> of <span class="font-semibold text-[#171A21]"><?= number_format($total) ?></span> results
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex items-center gap-1">
        <!-- Previous Page Button -->
        <?php if ($page > 1): ?>
            <a href="<?= $buildUrl($page - 1) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#E4E7EC] hover:bg-[#F7F8FA] text-[#171A21] font-medium transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Prev</span>
            </a>
        <?php else: ?>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#E4E7EC] opacity-40 cursor-not-allowed text-[#687080] font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Prev</span>
            </span>
        <?php endif; ?>

        <!-- Page Numbers -->
        <div class="hidden sm:flex items-center gap-1">
            <?php foreach ($pagesToShow as $p): ?>
                <?php if ($p === '...'): ?>
                    <span class="px-2 py-1 text-slate-400">...</span>
                <?php elseif ($p === $page): ?>
                    <span class="px-3 py-1 rounded-lg bg-[#5B5BD6] text-white font-semibold shadow-sm"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $buildUrl((int)$p) ?>" class="px-3 py-1 rounded-lg border border-[#E4E7EC] hover:bg-[#F7F8FA] text-[#171A21] font-medium transition-colors">
                        <?= $p ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Next Page Button -->
        <?php if ($page < $totalPages): ?>
            <a href="<?= $buildUrl($page + 1) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#E4E7EC] hover:bg-[#F7F8FA] text-[#171A21] font-medium transition-colors">
                <span>Next</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        <?php else: ?>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#E4E7EC] opacity-40 cursor-not-allowed text-[#687080] font-medium">
                <span>Next</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
