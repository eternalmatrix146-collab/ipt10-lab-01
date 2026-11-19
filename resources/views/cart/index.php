<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.main', ['title' => 'Your bag']);
$free = (float) config('app.shipping.free_over');
?>
<div class="page-head">
    <h1 class="page-title"><?= ransom('Your bag') ?></h1>
    <p class="page-sub"><?= $cart['count'] ?> item<?= $cart['count'] === 1 ? '' : 's' ?> secured</p>
</div>

<?php if ($cart['items'] === []): ?>
    <div class="empty">
        <p class="empty__big">Empty-handed?</p>
        <p>Nothing in your bag yet. Browse the shop or let Rook pick something for you.</p>
        <a class="btn btn--red" href="<?= route('shop') ?>">Go shopping</a>
        <button class="btn btn--white" type="button" data-rook-open data-rook-prompt="Pick something for me under ₱1,500">Ask Rook</button>
    </div>
<?php else: ?>
<div class="cart">
    <div class="cart__items">
        <?php foreach ($cart['items'] as $item): $p = $item['product']; ?>
            <article class="line">
                <a class="line__art" href="<?= route('product.show', ['slug' => $p->slug]) ?>"><img src="<?= e($p->imageUrl()) ?>" alt="" width="96" height="96"></a>
                <div class="line__info">
                    <a class="line__name" href="<?= route('product.show', ['slug' => $p->slug]) ?>"><?= e($p->name) ?></a>
                    <span class="line__unit"><?= e(money((float) $p->price)) ?> each · <?= e($p->stockLabel()) ?></span>
                </div>
                <form class="line__qty" method="post" action="<?= route('cart.update') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $p->id ?>">
                    <div class="qty" data-qty data-autosubmit-qty>
                        <button type="button" data-step="-1" aria-label="Decrease">−</button>
                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="0" max="<?= min(10, (int) $p->stock) ?>" aria-label="Quantity for <?= e($p->name) ?>">
                        <button type="button" data-step="1" aria-label="Increase">+</button>
                    </div>
                    <noscript><button class="link-btn" type="submit">Update</button></noscript>
                </form>
                <strong class="line__total"><?= e(money($item['line_total'])) ?></strong>
                <form method="post" action="<?= route('cart.remove') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $p->id ?>">
                    <button class="icon-btn icon-btn--dark" type="submit" aria-label="Remove <?= e($p->name) ?>">×</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>

    <aside class="summary panel">
        <h2>Summary</h2>
        <dl>
            <div><dt>Subtotal</dt><dd><?= e(money($cart['subtotal'])) ?></dd></div>
            <div><dt>Shipping</dt><dd><?= $cart['shipping'] > 0 ? e(money($cart['shipping'])) : '<b class="free">FREE</b>' ?></dd></div>
            <div class="summary__total"><dt>Total</dt><dd><?= e(money($cart['total'])) ?></dd></div>
        </dl>
        <?php if ($cart['shipping'] > 0): ?>
            <p class="summary__hint">Add <?= e(money($free - $cart['subtotal'])) ?> more for free shipping.</p>
            <div class="meter"><span style="width: <?= min(100, round($cart['subtotal'] / $free * 100)) ?>%"></span></div>
        <?php endif; ?>
        <a class="btn btn--red btn--lg btn--block" href="<?= route('checkout') ?>">Checkout</a>
        <?php if (current_currency() !== 'PHP'): ?>
            <p class="small muted">You will be charged in PHP: <?= e(peso($cart['total'])) ?>. Converted with live rates from the Frankfurter API.</p>
        <?php endif; ?>
    </aside>
</div>
<?php endif; ?>
