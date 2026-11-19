<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.main', ['title' => 'Log in', 'bodyClass' => 'is-auth']);
?>
<div class="auth">
    <div class="auth__card panel">
        <h1 class="page-title"><?= ransom('Log in') ?></h1>
        <p class="muted">Welcome back to the hideout.</p>
        <form method="post" action="<?= url('/login') ?>" novalidate>
            <?= csrf_field() ?>
            <label class="field">
                <span class="field__label">Email</span>
                <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email" autofocus>
                <?php if (error('email')): ?><span class="field__error"><?= e(error('email')) ?></span><?php endif; ?>
            </label>
            <label class="field">
                <span class="field__label">Password</span>
                <input type="password" name="password" required autocomplete="current-password">
                <?php if (error('password')): ?><span class="field__error"><?= e(error('password')) ?></span><?php endif; ?>
            </label>
            <button class="btn btn--red btn--lg btn--block" type="submit">Enter</button>
        </form>
        <p class="auth__alt">New here? <a href="<?= route('register') ?>">Join the crew</a></p>
        <p class="small muted">Demo accounts are listed in the project README.</p>
    </div>
    <div class="auth__art" aria-hidden="true"><?= ransom('Welcome back') ?></div>
</div>
