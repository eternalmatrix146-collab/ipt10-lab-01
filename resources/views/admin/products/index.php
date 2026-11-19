<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.admin', ['title' => 'Products']);
?>
<?php $this->start('actions') ?>
    <a class="btn btn--red" href="<?= route('admin.products.create') ?>">+ New product</a>
<?php $this->stop() ?>

<form class="toolbar" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search products…" aria-label="Search products">
    <select name="category" aria-label="Category">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= e($c->slug) ?>" <?= $category === $c->slug ? 'selected' : '' ?>><?= e($c->name) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
</form>

<div class="table-wrap panel">
    <table class="table">
        <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Featured</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($page['data'] as $p): ?>
            <tr>
                <td><img class="thumb" src="<?= e($p->imageUrl()) ?>" alt="" width="44" height="44" loading="lazy"></td>
                <td><b><?= e($p->name) ?></b><br><small class="muted"><?= e($p->slug) ?></small></td>
                <td><?= e($p->category_name) ?></td>
                <td><?= e(peso((float) $p->price)) ?></td>
                <td><span class="stock stock--<?= (int) $p->stock === 0 ? 'out' : ((int) $p->stock <= 5 ? 'low' : 'ok') ?>"><?= (int) $p->stock ?></span></td>
                <td><?= (int) $p->is_featured ? '★' : '—' ?></td>
                <td class="row-actions">
                    <a class="link-btn" href="<?= route('admin.products.edit', ['id' => $p->id]) ?>">Edit</a>
                    <form method="post" action="<?= route('admin.products.destroy', ['id' => $p->id]) ?>" data-confirm="Delete <?= e($p->name) ?>? This cannot be undone.">
                        <?= csrf_field() ?><?= method_field('DELETE') ?>
                        <button class="link-btn link-btn--danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->insert('partials.pagination', ['page' => $page, 'params' => ['q' => $q, 'category' => $category]]) ?>
