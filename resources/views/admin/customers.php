<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.admin', ['title' => 'Customers']);
?>
<div class="table-wrap panel">
    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Codename</th><th>Role</th><th>Orders</th><th>Spent</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td><b><?= e($c['name']) ?></b></td>
                <td><?= e($c['email']) ?></td>
                <td><?= e($c['codename'] ?? '—') ?></td>
                <td><span class="tag tag--<?= $c['role'] === 'admin' ? 'admin' : 'shop' ?>"><?= e($c['role']) ?></span></td>
                <td><?= (int) $c['order_count'] ?></td>
                <td><?= e(peso((float) $c['spent'])) ?></td>
                <td><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
