<?php $status = session()->getFlash('status'); ?>
<div class="toasts" id="toasts" aria-live="polite">
    <?php if (is_array($status)): ?>
        <div class="toast toast--<?= e($status['type'] ?? 'info') ?>" role="status">
            <span class="toast__tag"><?= e(match ($status['type'] ?? 'info') { 'success' => 'Done', 'error' => 'Alert', default => 'Notice' }) ?></span>
            <p><?= e($status['message'] ?? '') ?></p>
            <button type="button" class="toast__close" aria-label="Dismiss">×</button>
        </div>
    <?php endif; ?>
</div>
