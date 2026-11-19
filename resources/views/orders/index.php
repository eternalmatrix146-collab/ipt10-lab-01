<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.main', ['title' => 'My heists']);
?>
<div class="page-head">
    <h1 class="page-title"><?= ransom('My heists') ?></h1>
    <p class="page-sub">Every order you've pulled off.</p>
</div>

<?php if ($orders === []): ?>
    <div class="empty">
        <p class="empty__big">No heists yet.</p>
        <p>Your order history will show up here.</p>
        <a class="btn btn--red" href="<?= route('shop') ?>">Start shopping</a>
    </div>
<?php else: ?>
    <div class="table-wrap panel">
        <table class="table">
            <thead><tr><th>Reference</th><th>Placed</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><b><?= e($order->reference) ?></b></td>
                    <td><?= e(date('M j, Y · g:i A', strtotime($order->created_at))) ?></td>
                    <td><?= $order->itemCount() ?></td>
                    <td><?= e(peso((float) $order->total)) ?></td>
                    <td><?= status_badge($order->status) ?></td>
                    <td><a class="link-arrow" href="<?= route('orders.show', ['reference' => $order->reference]) ?>">Details</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
