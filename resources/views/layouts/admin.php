<?php
/** @var \Phantom\View\Template $this */
$currentPath = $currentPath ?? '/admin';
$authUser = $authUser ?? null;
$aiMode = $aiMode ?? 'offline';
$is = static fn (string $path, bool $exact = false): string => ($exact ? $currentPath === $path : str_starts_with($currentPath, $path)) ? ' is-active' : '';
?>
<!doctype html>
<html lang="en">
<head>
    <?= $this->insert('partials.head', ['title' => isset($title) ? $title . ' · Admin' : 'Admin']) ?>
</head>
<body class="admin">
<a class="skip-link" href="#main">Skip to content</a>
<aside class="side">
    <a class="brand brand--side" href="<?= route('admin.dashboard') ?>">
        <span class="brand__star">★</span>
        <span class="brand__text"><b>MIDNIGHT</b><i>COMMAND</i></span>
    </a>
    <nav class="side__nav" aria-label="Admin">
        <a class="side__link<?= $is('/admin', true) ?>" href="<?= route('admin.dashboard') ?>">Dashboard</a>
        <a class="side__link<?= $is('/admin/intel') ?>" href="<?= route('admin.intel') ?>">Rook Intel <em class="mode mode--<?= e($aiMode) ?>">AI</em></a>
        <a class="side__link<?= $is('/admin/orders') ?>" href="<?= route('admin.orders') ?>">Orders</a>
        <a class="side__link<?= $is('/admin/products') ?>" href="<?= route('admin.products') ?>">Products</a>
        <a class="side__link<?= $is('/admin/categories') ?>" href="<?= route('admin.categories') ?>">Categories</a>
        <a class="side__link<?= $is('/admin/customers') ?>" href="<?= route('admin.customers') ?>">Customers</a>
    </nav>
    <div class="side__foot">
        <a class="link-btn" href="<?= url('/') ?>">← View shop</a>
        <form method="post" action="<?= route('logout') ?>">
            <?= csrf_field() ?>
            <button class="link-btn" type="submit">Log out <?= e($authUser?->displayName() ?? '') ?></button>
        </form>
    </div>
</aside>

<?= $this->insert('partials.flash') ?>

<main id="main" class="admin-main">
    <header class="admin-head">
        <h1 class="admin-title"><?= ransom($title ?? 'Admin') ?></h1>
        <?= $this->section('actions') ?>
    </header>
    <?= $this->section('content') ?>
</main>

<div class="wipe" aria-hidden="true"><i></i><i></i><i></i></div>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
