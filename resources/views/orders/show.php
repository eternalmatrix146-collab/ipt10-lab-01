<?php
/** @var \Phantom\View\Template $this */
/** @var \App\Models\Order $order */
$this->layout('layouts.main', ['title' => 'Order ' . $order->reference]);
$steps = ['pending' => 'Received', 'processing' => 'Packing', 'shipped' => 'On the move', 'delivered' => 'Delivered'];
$current = $order->statusStep();
?>
<?php if ($callingCard): ?>
    <div class="calling-card" role="status">
        <div class="calling-card__inner">
            <span class="calling-card__top">Calling card</span>
            <p class="calling-card__text"><?= ransom('Order secured') ?></p>
            <p class="calling-card__body">Your haul <b><?= e($order->reference) ?></b> is locked in. We'll slip it to your door soon.</p>
            <span class="calling-card__sign">— Midnight Market</span>
        </div>
    </div>
<?php endif; ?>

<div class="page-head">
    <h1 class="page-title"><?= ransom('Order') ?> <span class="ref"><?= e($order->reference) ?></span></h1>
    <p class="page-sub">Placed <?= e(date('F j, Y \a\t g:i A', strtotime($order->created_at))) ?> · <?= status_badge($order->status) ?></p>
</div>

<?php if ($current >= 0): ?>
<ol class="timeline" aria-label="Order progress">
    <?php $i = 0; foreach ($steps as $key => $label): ?>
        <li class="timeline__step<?= $i <= $current ? ' is-done' : '' ?><?= $i === $current ? ' is-current' : '' ?>">
            <span class="timeline__dot"><?= $i + 1 ?></span><span><?= e($label) ?></span>
        </li>
    <?php $i++; endforeach; ?>
</ol>
<?php else: ?>
    <p class="notice notice--error">This order was cancelled. Any items were returned to stock.</p>
<?php endif; ?>

<div class="order">
    <section class="panel">
        <h2>Items</h2>
        <ul class="mini-lines">
            <?php foreach ($order->items() as $item): ?>
                <li><span><?= e($item->product_name) ?> <b>×<?= (int) $item->quantity ?></b> <small class="muted">@ <?= e(peso((float) $item->unit_price)) ?></small></span><span><?= e(peso((float) $item->line_total)) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <dl>
            <div><dt>Subtotal</dt><dd><?= e(peso((float) $order->subtotal)) ?></dd></div>
            <div><dt>Shipping</dt><dd><?= (float) $order->shipping_fee > 0 ? e(peso((float) $order->shipping_fee)) : '<b class="free">FREE</b>' ?></dd></div>
            <div class="summary__total"><dt>Total</dt><dd><?= e(peso((float) $order->total)) ?></dd></div>
        </dl>
        <?php if ($order->display_currency !== 'PHP'): ?>
            <p class="small muted">At checkout you were browsing in <?= e($order->display_currency) ?>: ≈ <?= e(app(\App\Services\CurrencyService::class)->symbol($order->display_currency) . number_format((float) $order->total * (float) $order->display_rate, 2)) ?> at the rate locked in then.</p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Drop-off</h2>
        <p><b><?= e($order->shipping_name) ?></b><br><?= e($order->shipping_address) ?><br><?= e($order->shipping_city) ?><br><?= e($order->shipping_phone) ?></p>
        <h2>Payment</h2>
        <p><?= e($order->paymentLabel()) ?></p>
        <?php if ($order->notes): ?><h2>Notes</h2><p><?= e($order->notes) ?></p><?php endif; ?>

        <?php if ($order->status === 'pending'): ?>
            <form method="post" action="<?= route('orders.cancel', ['reference' => $order->reference]) ?>" data-confirm="Cancel order <?= e($order->reference) ?>?">
                <?= csrf_field() ?>
                <button class="btn btn--ghost" type="submit">Cancel order</button>
            </form>
        <?php endif; ?>
        <button class="ask-rook" type="button" data-rook-open data-rook-prompt="<?= e('What is the status of my order ' . $order->reference . '?') ?>"><span>R</span> Ask Rook about this order</button>
    </section>
</div>
<p><a class="link-arrow" href="<?= route('orders') ?>">All my orders</a></p>
