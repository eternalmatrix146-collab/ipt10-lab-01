<?php
/** @var \Phantom\View\Template $this */
$titles = [
    403 => 'Access denied',
    404 => 'Page stolen',
    405 => 'Wrong approach',
    419 => 'Page expired',
    429 => 'Slow down',
    500 => 'Plan failed',
    503 => 'Lying low',
];
$heading = $titles[$status] ?? 'Error';
$this->layout('layouts.main', ['title' => $heading, 'bodyClass' => 'is-error']);
?>
<section class="error-page">
    <div class="error-page__code"><?= (int) $status ?></div>
    <h1 class="page-title"><?= ransom($heading) ?></h1>
    <p class="error-page__msg"><?= e($message) ?></p>
    <div class="hero__cta">
        <a class="btn btn--red" href="<?= url('/') ?>">Back to the hideout</a>
        <a class="btn btn--white" href="<?= route('shop') ?>">Browse the shop</a>
    </div>
</section>
