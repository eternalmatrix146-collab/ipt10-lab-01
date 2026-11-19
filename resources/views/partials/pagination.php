<?php
/** @var array{page: int, pages: int} $page */
if ($page['pages'] <= 1) {
    return;
}
$params = $params ?? [];
$params = array_filter($params, static fn ($v) => $v !== '' && $v !== null && $v !== false);
$link = static fn (int $n): string => '?' . http_build_query(array_merge($params, ['page' => $n]));
?>
<nav class="pager" aria-label="Pagination">
    <?php if ($page['page'] > 1): ?>
        <a class="pager__btn" href="<?= e($link($page['page'] - 1)) ?>" rel="prev">◀ Prev</a>
    <?php endif; ?>
    <?php for ($n = 1; $n <= $page['pages']; $n++): ?>
        <a class="pager__num<?= $n === $page['page'] ? ' is-current' : '' ?>" href="<?= e($link($n)) ?>" <?= $n === $page['page'] ? 'aria-current="page"' : '' ?>><?= $n ?></a>
    <?php endfor; ?>
    <?php if ($page['page'] < $page['pages']): ?>
        <a class="pager__btn" href="<?= e($link($page['page'] + 1)) ?>" rel="next">Next ▶</a>
    <?php endif; ?>
</nav>
