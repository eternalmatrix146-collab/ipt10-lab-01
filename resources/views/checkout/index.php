<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.main', ['title' => 'Checkout']);
$authUser = $authUser ?? null;
$prefill = static fn (string $field, string $fallback = '') => old($field, $lastOrder?->{$field} ?? $fallback);
?>
<div class="page-head">
    <h1 class="page-title"><?= ransom('Final plan') ?></h1>
    <p class="page-sub">Confirm the drop-off point and how you'll pay.</p>
</div>

<form class="checkout" method="post" action="<?= route('checkout.store') ?>">
    <?= csrf_field() ?>
    <section class="panel checkout__form">
        <h2>Delivery</h2>
        <div class="form-grid">
            <label class="field">
                <span class="field__label">Full name</span>
                <input name="shipping_name" required maxlength="120" value="<?= e($prefill('shipping_name', (string) $authUser?->name)) ?>">
                <?php if (error('shipping_name')): ?><span class="field__error"><?= e(error('shipping_name')) ?></span><?php endif; ?>
            </label>
            <label class="field">
                <span class="field__label">Phone</span>
                <input name="shipping_phone" required maxlength="20" inputmode="tel" placeholder="0917 123 4567" value="<?= e($prefill('shipping_phone')) ?>">
                <?php if (error('shipping_phone')): ?><span class="field__error"><?= e(error('shipping_phone')) ?></span><?php endif; ?>
            </label>
            <label class="field field--wide">
                <span class="field__label">Address</span>
                <input name="shipping_address" required maxlength="255" placeholder="House no., street, barangay" value="<?= e($prefill('shipping_address')) ?>">
                <?php if (error('shipping_address')): ?><span class="field__error"><?= e(error('shipping_address')) ?></span><?php endif; ?>
            </label>
            <label class="field">
                <span class="field__label">City</span>
                <input name="shipping_city" required maxlength="100" value="<?= e($prefill('shipping_city')) ?>">
                <?php if (error('shipping_city')): ?><span class="field__error"><?= e(error('shipping_city')) ?></span><?php endif; ?>
            </label>
            <label class="field field--wide">
                <span class="field__label">Notes for the courier (optional)</span>
                <textarea name="notes" rows="2" maxlength="500"><?= e(old('notes')) ?></textarea>
            </label>
        </div>

        <h2>Payment</h2>
        <div class="pay-options">
            <?php foreach ($paymentMethods as $value => $label): ?>
                <label class="pay">
                    <input type="radio" name="payment_method" value="<?= e($value) ?>" <?= old('payment_method', 'cod') === $value ? 'checked' : '' ?>>
                    <span><?= e($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php if (error('payment_method')): ?><p class="field__error"><?= e(error('payment_method')) ?></p><?php endif; ?>
        <p class="small muted">Demo store: GCash and card payments are simulated — no real charge is made.</p>
    </section>

    <aside class="panel summary">
        <h2>Your haul</h2>
        <ul class="mini-lines">
            <?php foreach ($cart['items'] as $item): ?>
                <li><span><?= e($item['product']->name) ?> <b>×<?= $item['quantity'] ?></b></span><span><?= e(money($item['line_total'])) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <dl>
            <div><dt>Subtotal</dt><dd><?= e(money($cart['subtotal'])) ?></dd></div>
            <div><dt>Shipping</dt><dd><?= $cart['shipping'] > 0 ? e(money($cart['shipping'])) : '<b class="free">FREE</b>' ?></dd></div>
            <div class="summary__total"><dt>Total</dt><dd><?= e(money($cart['total'])) ?></dd></div>
        </dl>
        <?php if (current_currency() !== 'PHP'): ?>
            <p class="small muted">Charged in PHP: <b><?= e(peso($cart['total'])) ?></b></p>
        <?php endif; ?>
        <button class="btn btn--red btn--lg btn--block" type="submit">Place order</button>
        <a class="link-btn" href="<?= route('cart') ?>">← Back to bag</a>
    </aside>
</form>
