<?php
/** @var \Phantom\View\Template $this */
$authUser = $authUser ?? null;
$currentPath = $currentPath ?? '/';
$aiMode = $aiMode ?? 'offline';
$navActive = static fn (string $prefix): string => ($prefix === '/' ? $currentPath === '/' : str_starts_with($currentPath, $prefix)) ? ' is-active' : '';
?>
<!doctype html>
<html lang="en">
<head>
    <?= $this->insert('partials.head', ['title' => $title ?? null]) ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main">Skip to content</a>
<div class="bg-shards" aria-hidden="true"><span></span><span></span><span></span></div>

<header class="topbar">
    <div class="topbar__inner">
        <a class="brand" href="<?= url('/') ?>" aria-label="Midnight Market home">
            <span class="brand__star">★</span>
            <span class="brand__text"><b>MIDNIGHT</b><i>MARKET</i></span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">
            <span></span><span></span><span></span><span class="sr-only">Menu</span>
        </button>

        <nav class="nav" id="main-nav" aria-label="Main">
            <a class="nav__link<?= $navActive('/') ?>" href="<?= url('/') ?>"><span>Hideout</span></a>
            <a class="nav__link<?= $navActive('/shop') . $navActive('/product') ?>" href="<?= route('shop') ?>"><span>Shop</span></a>
            <?php if ($authUser): ?>
                <a class="nav__link<?= $navActive('/orders') ?>" href="<?= route('orders') ?>"><span>My Heists</span></a>
                <?php if ($authUser->isAdmin()): ?>
                    <a class="nav__link nav__link--admin" href="<?= route('admin.dashboard') ?>"><span>Admin</span></a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>

        <div class="topbar__tools">
            <form class="currency" method="post" action="<?= route('currency.update') ?>">
                <?= csrf_field() ?>
                <label class="sr-only" for="currency-select">Currency</label>
                <select id="currency-select" name="currency" data-autosubmit>
                    <?php foreach ((array) config('app.currency.supported') as $code): ?>
                        <option value="<?= e($code) ?>" <?= current_currency() === $code ? 'selected' : '' ?>><?= e($code) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <a class="cart-link" href="<?= route('cart') ?>" aria-label="Cart">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2.2l2.3 10.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.4-1.1L21 7H6.2" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/><circle cx="9.5" cy="19.5" r="1.6" fill="currentColor"/><circle cx="17" cy="19.5" r="1.6" fill="currentColor"/></svg>
                <span class="cart-badge" data-cart-count><?= cart_count() ?></span>
            </a>

            <?php if ($authUser): ?>
                <form method="post" action="<?= route('logout') ?>" class="userbox">
                    <?= csrf_field() ?>
                    <span class="userbox__name" title="<?= e($authUser->email) ?>"><?= e($authUser->displayName()) ?></span>
                    <button type="submit" class="link-btn">Log out</button>
                </form>
            <?php else: ?>
                <a class="btn btn--sm btn--white" href="<?= route('login') ?>">Log in</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?= $this->insert('partials.flash') ?>

<main id="main" class="page">
    <?= $this->section('content') ?>
</main>

<footer class="footer">
    <div class="footer__inner">
        <div>
            <div class="footer__brand"><?= ransom('Midnight Market') ?></div>
            <p>Gear for night owls, planners and gentle thieves of the spotlight. A school project built on the <b>Phantom</b> PHP framework.</p>
        </div>
        <div class="footer__cols">
            <div>
                <h4>Shop</h4>
                <a href="<?= route('shop') ?>">All products</a>
                <a href="<?= route('shop') ?>?sort=newest">New arrivals</a>
                <a href="<?= route('cart') ?>">Your bag</a>
            </div>
            <div>
                <h4>Powered by</h4>
                <span>NVIDIA NIM — Rook agent</span>
                <span>Frankfurter — live FX rates</span>
                <span>MySQL · PHP <?= e(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?></span>
            </div>
        </div>
    </div>
    <p class="footer__fine">© <?= date('Y') ?> Midnight Market · IPT10 Final Project · Prices in <?= e(current_currency()) ?></p>
</footer>

<?= $this->insert('partials.rook') ?>

<div class="wipe" aria-hidden="true"><i></i><i></i><i></i></div>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
