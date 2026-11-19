<?php
/** @var \Phantom\View\Template $this */
$heading = $activeCategory?->name ?? ($filters['q'] !== '' ? 'Search' : 'All loot');
$this->layout('layouts.main', ['title' => $heading]);
?>
<div class="page-head">
    <h1 class="page-title"><?= ransom($heading) ?></h1>
    <p class="page-sub">
        <?= (int) $page['total'] ?> item<?= $page['total'] === 1 ? '' : 's' ?>
        <?= $filters['q'] !== '' ? ' matching “' . e($filters['q']) . '”' : '' ?>
        · prices in <?= e(current_currency()) ?>
    </p>
</div>

<div class="shop">
    <aside class="filters">
        <form method="get" action="<?= route('shop') ?>" class="filters__form">
            <label class="field">
                <span class="field__label">Search</span>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="mask, coffee, coat…">
            </label>

            <div class="field">
                <span class="field__label">Category</span>
                <div class="chips">
                    <label class="chip"><input type="radio" name="category" value="" <?= $filters['category'] === '' ? 'checked' : '' ?>><span>All</span></label>
                    <?php foreach ($categories as $category): ?>
                        <label class="chip"><input type="radio" name="category" value="<?= e($category->slug) ?>" <?= $filters['category'] === $category->slug ? 'checked' : '' ?>><span><?= e($category->name) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="field field--row">
                <label><span class="field__label">Min ₱</span><input type="number" name="min_price" min="0" step="50" value="<?= e((string) $filters['min_price']) ?>"></label>
                <label><span class="field__label">Max ₱</span><input type="number" name="max_price" min="0" step="50" value="<?= e((string) $filters['max_price']) ?>"></label>
            </div>

            <label class="field">
                <span class="field__label">Sort</span>
                <select name="sort">
                    <?php foreach (['featured' => 'Featured', 'newest' => 'Newest', 'price_asc' => 'Price: low → high', 'price_desc' => 'Price: high → low', 'name' => 'Name'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $filters['sort'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="check"><input type="checkbox" name="in_stock" value="1" <?= $filters['in_stock'] ? 'checked' : '' ?>> <span>In stock only</span></label>

            <div class="filters__actions">
                <button class="btn btn--red" type="submit">Apply</button>
                <a class="link-btn" href="<?= route('shop') ?>">Reset</a>
            </div>
        </form>

        <div class="filters__rook">
            <p>Can't find it? Describe it.</p>
            <button class="btn btn--white btn--sm" type="button" data-rook-open>Ask Rook</button>
        </div>
    </aside>

    <section class="shop__results">
        <?php if ($page['data'] === []): ?>
            <div class="empty">
                <p class="empty__big">Nothing here.</p>
                <p>The vault came up empty for those filters. Try fewer words, or let Rook dig for you.</p>
                <button class="btn btn--red" type="button" data-rook-open data-rook-prompt="<?= e('I was looking for: ' . ($filters['q'] ?: 'something special')) ?>">Ask Rook</button>
            </div>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach ($page['data'] as $product): ?>
                    <?= $this->insert('partials.product-card', ['product' => $product]) ?>
                <?php endforeach; ?>
            </div>
            <?= $this->insert('partials.pagination', ['page' => $page, 'params' => [
                'q' => $filters['q'], 'category' => $filters['category'], 'min_price' => $filters['min_price'],
                'max_price' => $filters['max_price'], 'sort' => $filters['sort'], 'in_stock' => $filters['in_stock'] ? '1' : '',
            ]]) ?>
        <?php endif; ?>
    </section>
</div>
