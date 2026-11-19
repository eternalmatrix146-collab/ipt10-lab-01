<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.main', ['title' => 'Join the crew', 'bodyClass' => 'is-auth']);
?>
<div class="auth">
    <div class="auth__card panel">
        <h1 class="page-title"><?= ransom('Join the crew') ?></h1>
        <p class="muted">Create an account to check out and track orders.</p>
        <form method="post" action="<?= url('/register') ?>" novalidate>
            <?= csrf_field() ?>
            <label class="field">
                <span class="field__label">Full name</span>
                <input name="name" value="<?= e(old('name')) ?>" required maxlength="100" autocomplete="name">
                <?php if (error('name')): ?><span class="field__error"><?= e(error('name')) ?></span><?php endif; ?>
            </label>
            <label class="field">
                <span class="field__label">Codename (optional)</span>
                <input name="codename" value="<?= e(old('codename')) ?>" maxlength="50" placeholder="What should Rook call you?">
                <?php if (error('codename')): ?><span class="field__error"><?= e(error('codename')) ?></span><?php endif; ?>
            </label>
            <label class="field">
                <span class="field__label">Email</span>
                <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
                <?php if (error('email')): ?><span class="field__error"><?= e(error('email')) ?></span><?php endif; ?>
            </label>
            <div class="form-grid">
                <label class="field">
                    <span class="field__label">Password</span>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password">
                    <?php if (error('password')): ?><span class="field__error"><?= e(error('password')) ?></span><?php endif; ?>
                </label>
                <label class="field">
                    <span class="field__label">Confirm</span>
                    <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
                </label>
            </div>
            <button class="btn btn--red btn--lg btn--block" type="submit">Create account</button>
        </form>
        <p class="auth__alt">Already in? <a href="<?= route('login') ?>">Log in</a></p>
    </div>
    <div class="auth__art" aria-hidden="true"><?= ransom('New recruit') ?></div>
</div>
