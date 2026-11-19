<?php
/** @var \App\Models\Product $product */
$tilt = ((int) $product->id % 5) - 2;
?>
<article class="pcard" style="--tilt: <?= $tilt * 0.6 ?>deg">
    <a class="pcard__link" href="<?= route('product.show', ['slug' => $product->slug]) ?>">
        <div class="pcard__art">
            <img src="<?= e($product->imageUrl()) ?>" alt="" loading="lazy" width="400" height="400">
            <span class="pcard__price"><?= e(money((float) $product->price)) ?></span>
            <?php if ((int) $product->is_featured): ?><span class="pcard__flag">Hot</span><?php endif; ?>
        </div>
        <div class="pcard__body">
            <span class="pcard__cat"><?= e($product->category_name ?? '') ?></span>
            <h3 class="pcard__name"><?= e($product->name) ?></h3>
            <p class="pcard__tag"><?= e(str_limit((string) $product->tagline, 70)) ?></p>
        </div>
    </a>
    <form class="pcard__buy" method="post" action="<?= route('cart.add') ?>" data-ajax-cart>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product->id ?>">
        <span class="stock stock--<?= $product->inStock() ? ((int) $product->stock <= 5 ? 'low' : 'ok') : 'out' ?>"><?= e($product->stockLabel()) ?></span>
        <button class="btn btn--sm" type="submit" <?= $product->inStock() ? '' : 'disabled' ?>>Steal it</button>
    </form>
</article>
