<?php
/** @var \Phantom\View\Template $this */
/** @var \App\Models\Product|null $product */
$editing = $product !== null;
$this->layout('layouts.admin', ['title' => $editing ? 'Edit product' : 'New product']);
$val = static fn (string $field, mixed $default = '') => old($field, $editing ? $product->{$field} : $default);
?>
<form class="panel product-form" method="post" action="<?= $editing ? route('admin.products.update', ['id' => $product->id]) : route('admin.products.store') ?>">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="form-grid">
        <label class="field field--wide">
            <span class="field__label">Name</span>
            <input name="name" required maxlength="150" value="<?= e($val('name')) ?>" data-ai-name>
            <?php if (error('name')): ?><span class="field__error"><?= e(error('name')) ?></span><?php endif; ?>
        </label>
        <label class="field">
            <span class="field__label">Category</span>
            <select name="category_id" required data-ai-category>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c->id ?>" <?= (int) $val('category_id') === (int) $c->id ? 'selected' : '' ?>><?= e($c->name) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (error('category_id')): ?><span class="field__error"><?= e(error('category_id')) ?></span><?php endif; ?>
        </label>
        <label class="field">
            <span class="field__label">Tagline</span>
            <input name="tagline" maxlength="160" value="<?= e($val('tagline')) ?>" data-ai-tagline>
        </label>
        <label class="field">
            <span class="field__label">Price (₱)</span>
            <input type="number" name="price" min="1" step="0.01" required value="<?= e($val('price')) ?>">
            <?php if (error('price')): ?><span class="field__error"><?= e(error('price')) ?></span><?php endif; ?>
        </label>
        <label class="field">
            <span class="field__label">Stock</span>
            <input type="number" name="stock" min="0" step="1" required value="<?= e($val('stock', 0)) ?>">
            <?php if (error('stock')): ?><span class="field__error"><?= e(error('stock')) ?></span><?php endif; ?>
        </label>
        <div class="field field--wide">
            <div class="field__row">
                <label class="field__label" for="description">Description</label>
                <button class="btn btn--sm btn--red" type="button" data-ai-describe="<?= route('admin.products.describe') ?>">✦ Write with AI</button>
            </div>
            <textarea id="description" name="description" rows="6" maxlength="5000" data-ai-target><?= e($val('description')) ?></textarea>
            <span class="field__hint" data-ai-status>Nemotron drafts copy from the name, category and tagline. Review before saving.</span>
        </div>
        <label class="check field--wide"><input type="checkbox" name="is_featured" value="1" <?= $val('is_featured', 0) ? 'checked' : '' ?>> <span>Feature on the home page</span></label>
    </div>

    <div class="form-actions">
        <button class="btn btn--red btn--lg" type="submit"><?= $editing ? 'Save changes' : 'Create product' ?></button>
        <a class="link-btn" href="<?= route('admin.products') ?>">Cancel</a>
        <?php if ($editing): ?><a class="link-btn" href="<?= route('product.show', ['slug' => $product->slug]) ?>" target="_blank" rel="noopener">View in shop ↗</a><?php endif; ?>
    </div>
</form>
