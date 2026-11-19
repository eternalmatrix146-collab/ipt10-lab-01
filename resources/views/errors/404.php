<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.main', ['title' => 'Page stolen', 'bodyClass' => 'is-error']);
?>
<section class="error-page">
    <div class="error-page__code">404</div>
    <h1 class="page-title"><?= ransom('This page was stolen') ?></h1>
    <p class="error-page__msg"><?= e($message ?: 'Whatever was here has vanished without a trace.') ?></p>
    <div class="hero__cta">
        <a class="btn btn--red" href="<?= url('/') ?>">Back to the hideout</a>
        <button class="btn btn--white" type="button" data-rook-open data-rook-prompt="I was looking for something but hit a dead end. Can you help me find it?">Ask Rook to track it down</button>
    </div>
</section>
