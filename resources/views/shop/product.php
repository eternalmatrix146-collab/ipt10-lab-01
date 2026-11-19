<?php
/** @var \Phantom\View\Template $this */
/** @var \App\Models\Product $product */
$this->layout('layouts.main', ['title' => $product->name]);
$authUser = $authUser ?? null;
?>
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="<?= route('shop') ?>">Shop</a>
    <a href="<?= route('shop') ?>?category=<?= e($product->category_slug) ?>"><?= e($product->category_name) ?></a>
    <span><?= e($product->name) ?></span>
</nav>

<section class="product">
    <div class="product__art">
        <img src="<?= e($product->imageUrl()) ?>" alt="<?= e($product->name) ?> artwork" width="400" height="400">
    </div>

    <div class="product__info">
        <span class="product__cat"><?= e($product->category_name) ?></span>
        <h1 class="product__name"><?= e($product->name) ?></h1>
        <p class="product__tagline"><?= e((string) $product->tagline) ?></p>

        <div class="product__meta">
            <span class="price-tag"><?= e(money((float) $product->price)) ?></span>
            <?php if (current_currency() !== 'PHP'): ?>
                <span class="product__base">≈ <?= e(peso((float) $product->price)) ?></span>
            <?php endif; ?>
            <a class="product__rating" href="#reviews"><?= stars($rating['average']) ?> <span><?= $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?></span></a>
        </div>

        <p class="product__desc"><?= nl2br(e((string) $product->description)) ?></p>

        <form class="buybox" method="post" action="<?= route('cart.add') ?>" data-ajax-cart>
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product->id ?>">
            <div class="qty" data-qty>
                <button type="button" data-step="-1" aria-label="Decrease quantity">−</button>
                <input type="number" name="quantity" value="1" min="1" max="<?= max(1, min(10, (int) $product->stock)) ?>" aria-label="Quantity">
                <button type="button" data-step="1" aria-label="Increase quantity">+</button>
            </div>
            <button class="btn btn--red btn--lg" type="submit" <?= $product->inStock() ? '' : 'disabled' ?>>
                <?= $product->inStock() ? 'Add to bag' : 'Sold out' ?>
            </button>
            <span class="stock stock--<?= $product->inStock() ? ((int) $product->stock <= 5 ? 'low' : 'ok') : 'out' ?>"><?= e($product->stockLabel()) ?></span>
        </form>

        <button class="ask-rook" type="button" data-rook-open data-rook-prompt="<?= e('Tell me more about the ' . $product->name . ' and what pairs well with it.') ?>">
            <span>R</span> Ask Rook what pairs with this
        </button>
    </div>
</section>

<section class="section" id="reviews">
    <div class="section__head">
        <h2 class="slash-title"><span>Word</span> on the street</h2>
    </div>
    <div class="reviews">
        <div class="reviews__list">
            <?php if ($reviews === []): ?>
                <p class="muted">No reviews yet — be the first to leave your mark.</p>
            <?php endif; ?>
            <?php foreach ($reviews as $review): ?>
                <article class="review">
                    <header>
                        <strong><?= e($review['user_codename'] ?: explode(' ', $review['user_name'])[0]) ?></strong>
                        <?= stars((float) $review['rating']) ?>
                        <time datetime="<?= e($review['created_at']) ?>"><?= e(date('M j, Y', strtotime($review['created_at']))) ?></time>
                    </header>
                    <p><?= e($review['comment']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="reviews__form panel">
            <?php if (!$authUser): ?>
                <p><a href="<?= route('login') ?>">Log in</a> to leave a review.</p>
            <?php elseif ($hasReviewed): ?>
                <p>You already reviewed this item. Thanks!</p>
            <?php else: ?>
                <h3>Leave a review</h3>
                <?php if (!$hasPurchased): ?><p class="muted small">Tip: reviews from buyers carry the most weight.</p><?php endif; ?>
                <form method="post" action="<?= route('product.review', ['slug' => $product->slug]) ?>">
                    <?= csrf_field() ?>
                    <fieldset class="rate-input">
                        <legend class="field__label">Rating</legend>
                        <?php for ($r = 5; $r >= 1; $r--): ?>
                            <input type="radio" id="rate-<?= $r ?>" name="rating" value="<?= $r ?>" <?= (int) old('rating', 5) === $r ? 'checked' : '' ?>>
                            <label for="rate-<?= $r ?>" title="<?= $r ?> stars">★</label>
                        <?php endfor; ?>
                    </fieldset>
                    <?php if (error('rating')): ?><p class="field__error"><?= e(error('rating')) ?></p><?php endif; ?>
                    <label class="field">
                        <span class="field__label">Comment</span>
                        <textarea name="comment" rows="3" maxlength="1000" required><?= e(old('comment')) ?></textarea>
                        <?php if (error('comment')): ?><span class="field__error"><?= e(error('comment')) ?></span><?php endif; ?>
                    </label>
                    <button class="btn" type="submit">Post review</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($related !== []): ?>
<section class="section">
    <div class="section__head">
        <h2 class="slash-title"><span>Partners</span> in crime</h2>
    </div>
    <div class="grid grid--4">
        <?php foreach ($related as $item): ?>
            <?= $this->insert('partials.product-card', ['product' => $item]) ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
