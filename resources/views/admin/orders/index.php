<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.admin', ['title' => 'Orders']);
?>
<nav class="tabs" aria-label="Filter by status">
    <a class="tabs__tab<?= $status === '' ? ' is-active' : '' ?>" href="<?= route('admin.orders') ?>">All <b><?= array_sum($counts) ?></b></a>
    <?php foreach (\App\Models\Order::STATUSES as $s): ?>
        <a class="tabs__tab<?= $status === $s ? ' is-active' : '' ?>" href="?status=<?= e($s) ?>"><?= e(ucfirst($s)) ?> <b><?= (int) ($counts[$s] ?? 0) ?></b></a>
    <?php endforeach; ?>
</nav>

<form class="toolbar" method="get">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Reference or customer…" aria-label="Search orders">
    <button class="btn btn--sm" type="submit">Search</button>
</form>

<div class="table-wrap panel">
    <table class="table">
        <thead><tr><th>Reference</th><th>Customer</th><th>Placed</th><th>Payment</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($page['data'] as $o): ?>
            <tr>
                <td><a href="<?= route('admin.orders.show', ['reference' => $o['reference']]) ?>"><b><?= e($o['reference']) ?></b></a></td>
                <td><?= e($o['customer']) ?><br><small class="muted"><?= e($o['customer_email']) ?></small></td>
                <td><?= e(date('M j, g:i A', strtotime($o['created_at']))) ?></td>
                <td><?= e(\App\Models\Order::PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method']) ?></td>
                <td><?= e(peso((float) $o['total'])) ?></td>
                <td><?= status_badge($o['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($page['data'] === []): ?>
            <tr><td colspan="6" class="muted">No orders match.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->insert('partials.pagination', ['page' => $page, 'params' => ['status' => $status, 'q' => $q]]) ?>
