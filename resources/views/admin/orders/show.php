<?php
/** @var \Phantom\View\Template $this */
/** @var \App\Models\Order $order */
$this->layout('layouts.admin', ['title' => 'Order ' . $order->reference]);
?>
<?php $this->start('actions') ?>
    <?= status_badge($order->status) ?>
<?php $this->stop() ?>

<div class="two-col">
    <section class="panel">
        <h2>Items</h2>
        <ul class="mini-lines">
            <?php foreach ($order->items() as $item): ?>
                <li><span><?= e($item->product_name) ?> <b>×<?= (int) $item->quantity ?></b> <small class="muted">@ <?= e(peso((float) $item->unit_price)) ?></small></span><span><?= e(peso((float) $item->line_total)) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <dl>
            <div><dt>Subtotal</dt><dd><?= e(peso((float) $order->subtotal)) ?></dd></div>
            <div><dt>Shipping</dt><dd><?= e(peso((float) $order->shipping_fee)) ?></dd></div>
            <div class="summary__total"><dt>Total</dt><dd><?= e(peso((float) $order->total)) ?></dd></div>
        </dl>
    </section>

    <section class="panel">
        <h2>Update status</h2>
        <form class="status-form" method="post" action="<?= route('admin.orders.status', ['reference' => $order->reference]) ?>">
            <?= csrf_field() ?><?= method_field('PUT') ?>
            <select name="status" aria-label="Status" <?= $order->status === 'cancelled' ? 'disabled' : '' ?>>
                <?php foreach (\App\Models\Order::STATUSES as $s): ?>
                    <option value="<?= e($s) ?>" <?= $order->status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn--red" type="submit" <?= $order->status === 'cancelled' ? 'disabled' : '' ?>>Save</button>
        </form>
        <p class="small muted">Cancelling returns the items to stock.</p>

        <h2>Customer</h2>
        <p><b><?= e($customer?->name ?? 'Unknown') ?></b><br><?= e($customer?->email ?? '') ?></p>
        <h2>Ship to</h2>
        <p><?= e($order->shipping_name) ?><br><?= e($order->shipping_address) ?>, <?= e($order->shipping_city) ?><br><?= e($order->shipping_phone) ?></p>
        <h2>Payment</h2>
        <p><?= e($order->paymentLabel()) ?> · placed <?= e(date('M j, Y g:i A', strtotime($order->created_at))) ?></p>
        <?php if ($order->notes): ?><h2>Notes</h2><p><?= e($order->notes) ?></p><?php endif; ?>
    </section>
</div>
<p><a class="link-arrow" href="<?= route('admin.orders') ?>">Back to orders</a></p>
