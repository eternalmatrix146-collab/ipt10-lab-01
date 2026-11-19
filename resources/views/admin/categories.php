<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.admin', ['title' => 'Categories']);
?>
<div class="two-col two-col--wide-left">
    <section class="panel">
        <h2>All categories</h2>
        <?php foreach ($categories as $c): ?>
            <form class="cat-row" method="post" action="<?= route('admin.categories.update', ['id' => $c['id']]) ?>">
                <?= csrf_field() ?><?= method_field('PUT') ?>
                <input name="name" value="<?= e($c['name']) ?>" aria-label="Name" required>
                <input name="description" value="<?= e((string) $c['description']) ?>" aria-label="Description">
                <select name="motif" aria-label="Artwork motif">
                    <?php foreach ($motifs as $m): ?>
                        <option value="<?= e($m) ?>" <?= $c['motif'] === $m ? 'selected' : '' ?>><?= e(ucfirst($m)) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="pill"><?= (int) $c['product_count'] ?> items</span>
                <button class="btn btn--sm" type="submit">Save</button>
                <button class="link-btn link-btn--danger" type="submit"
                        formaction="<?= route('admin.categories.destroy', ['id' => $c['id']]) ?>"
                        name="_method" value="DELETE" data-confirm-click="Delete category <?= e($c['name']) ?>?">Delete</button>
            </form>
        <?php endforeach; ?>
    </section>

    <section class="panel">
        <h2>New category</h2>
        <form method="post" action="<?= route('admin.categories.store') ?>">
            <?= csrf_field() ?>
            <label class="field"><span class="field__label">Name</span><input name="name" required maxlength="100" value="<?= e(old('name')) ?>">
                <?php if (error('name')): ?><span class="field__error"><?= e(error('name')) ?></span><?php endif; ?></label>
            <label class="field"><span class="field__label">Description</span><input name="description" maxlength="255" value="<?= e(old('description')) ?>"></label>
            <label class="field"><span class="field__label">Artwork motif</span>
                <select name="motif"><?php foreach ($motifs as $m): ?><option value="<?= e($m) ?>"><?= e(ucfirst($m)) ?></option><?php endforeach; ?></select>
            </label>
            <button class="btn btn--red" type="submit">Create</button>
        </form>
    </section>
</div>
