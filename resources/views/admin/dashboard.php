<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.admin', ['title' => 'Dashboard']);
$max = max(1, max(array_column($series, 'revenue')));
$barW = 100 / count($series);
?>
<div class="stats">
    <div class="stat stat--red"><span class="stat__label">Revenue · 30 days</span><strong class="stat__value"><?= e(peso((float) $stats['revenue30'])) ?></strong></div>
    <div class="stat"><span class="stat__label">Orders · 30 days</span><strong class="stat__value"><?= (int) $stats['orders30'] ?></strong></div>
    <div class="stat"><span class="stat__label">Pending orders</span><strong class="stat__value"><?= (int) $stats['pending'] ?></strong><a href="<?= route('admin.orders') ?>?status=pending">Process ›</a></div>
    <div class="stat"><span class="stat__label">Customers</span><strong class="stat__value"><?= (int) $stats['customers'] ?></strong></div>
    <div class="stat stat--warn"><span class="stat__label">Low stock (≤5)</span><strong class="stat__value"><?= (int) $stats['lowStock'] ?></strong><span>of <?= (int) $stats['products'] ?> products</span></div>
    <div class="stat stat--dark"><span class="stat__label">AI agent</span><strong class="stat__value"><?= (int) $stats['agentActions'] ?></strong><span>tool calls in <?= (int) $stats['agentChats'] ?> chats</span></div>
</div>

<section class="panel">
    <div class="panel__head"><h2>Revenue · last 14 days</h2><span class="muted small">Excludes cancelled orders</span></div>
    <figure class="chart" role="img" aria-label="Bar chart of daily revenue for the last 14 days">
        <svg viewBox="0 0 100 50" preserveAspectRatio="none" class="chart__svg">
            <?php foreach ($series as $i => $point):
                $h = $point['revenue'] / $max * 44; ?>
                <rect x="<?= $i * $barW + $barW * 0.15 ?>" y="<?= 46 - $h ?>" width="<?= $barW * 0.7 ?>" height="<?= max(0.4, $h) ?>" class="chart__bar<?= $i === count($series) - 1 ? ' is-today' : '' ?>">
                    <title><?= e($point['label'] . ': ' . peso($point['revenue']) . ' · ' . $point['orders'] . ' orders') ?></title>
                </rect>
            <?php endforeach; ?>
            <line x1="0" y1="46" x2="100" y2="46" class="chart__axis"/>
        </svg>
        <figcaption class="chart__labels">
            <?php foreach ($series as $i => $point): ?>
                <span><?= $i % 2 === 0 || $i === count($series) - 1 ? e($point['label']) : '' ?></span>
            <?php endforeach; ?>
        </figcaption>
    </figure>
</section>

<div class="two-col">
    <section class="panel">
        <div class="panel__head"><h2>Latest orders</h2><a class="link-arrow" href="<?= route('admin.orders') ?>">All</a></div>
        <table class="table table--compact">
            <thead><tr><th>Ref</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><a href="<?= route('admin.orders.show', ['reference' => $o['reference']]) ?>"><?= e($o['reference']) ?></a></td>
                    <td><?= e($o['customer']) ?></td>
                    <td><?= e(peso((float) $o['total'])) ?></td>
                    <td><?= status_badge($o['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Running low</h2><a class="link-arrow" href="<?= route('admin.products') ?>">Inventory</a></div>
        <?php if ($lowStock === []): ?>
            <p class="muted">All stocked up.</p>
        <?php else: ?>
            <ul class="stock-list">
                <?php foreach ($lowStock as $p): ?>
                    <li>
                        <a href="<?= route('admin.products.edit', ['id' => $p['id']]) ?>"><?= e($p['name']) ?></a>
                        <span class="stock stock--<?= (int) $p['stock'] === 0 ? 'out' : 'low' ?>"><?= (int) $p['stock'] === 0 ? 'Sold out' : (int) $p['stock'] . ' left' ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a class="btn btn--sm btn--red" href="<?= route('admin.intel') ?>?ask=<?= rawurlencode('Which products should I restock first, and why?') ?>">Ask Rook Intel</a>
    </section>
</div>
