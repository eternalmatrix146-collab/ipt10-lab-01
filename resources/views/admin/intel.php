<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.admin', ['title' => 'Rook Intel']);
?>
<?php $this->start('actions') ?>
    <span class="pill">Mode: <b class="mode mode--<?= e($mode) ?>"><?= $mode === 'nim' ? e($model) : 'offline (rule-based)' ?></b></span>
<?php $this->stop() ?>

<div class="intel">
    <section class="panel intel__chat"
             data-chat
             data-endpoint="<?= route('admin.intel.chat') ?>"
             data-reset="<?= route('admin.intel.reset') ?>"
             data-autoask="<?= e(mb_substr($ask, 0, 300)) ?>">
        <div class="panel__head">
            <h2>Operations analyst</h2>
            <button class="link-btn" type="button" data-chat-reset>New briefing</button>
        </div>
        <div class="chat-log chat-log--light" data-chat-log aria-live="polite">
            <div class="msg msg--assistant msg--intro"><div class="bubble"><p>Rook Intel on the line. Ask about sales, best-sellers, stock, categories or customers. I can also <b>prepare order-status changes</b> and <b>restocks</b> — you apply them on the order or product page.</p></div></div>
            <?php foreach ($transcript as $entry): ?>
                <div class="msg msg--<?= e($entry['role']) ?>" data-entry="<?= e(json_encode($entry, JSON_UNESCAPED_UNICODE)) ?>"></div>
            <?php endforeach; ?>
        </div>
        <div class="chat-suggest" data-chat-suggest>
            <button type="button" data-suggest>Give me a 30-day sales briefing</button>
            <button type="button" data-suggest>What should I restock first?</button>
            <button type="button" data-suggest>Which category is underperforming?</button>
            <button type="button" data-suggest>Show pending orders</button>
        </div>
        <form class="chat-form" data-chat-form>
            <label class="sr-only" for="intel-input">Message Rook Intel</label>
            <textarea id="intel-input" name="message" rows="1" maxlength="2000" placeholder="Ask Rook Intel…" required></textarea>
            <button class="chat-send" type="submit" aria-label="Send">➤</button>
        </form>
    </section>

    <aside class="intel__side">
        <section class="panel">
            <h2>Agent usage</h2>
            <dl class="kv">
                <div><dt>Conversations</dt><dd><?= (int) $usage['conversations'] ?></dd></div>
                <div><dt>Input tokens</dt><dd><?= number_format((int) $usage['input_tokens']) ?></dd></div>
                <div><dt>Output tokens</dt><dd><?= number_format((int) $usage['output_tokens']) ?></dd></div>
            </dl>
        </section>
        <section class="panel">
            <h2>Agent activity log</h2>
            <p class="small muted">Every tool call made by Rook (shop) and Rook Intel (admin).</p>
            <?php if ($actions === []): ?>
                <p class="muted">No tool calls yet.</p>
            <?php endif; ?>
            <ol class="actlog">
                <?php foreach ($actions as $a): ?>
                    <li class="actlog__item<?= (int) $a['is_error'] ? ' is-error' : '' ?>">
                        <div class="actlog__top">
                            <code><?= e($a['tool_name']) ?></code>
                            <span class="tag tag--<?= e($a['channel']) ?>"><?= e($a['channel']) ?></span>
                        </div>
                        <div class="actlog__meta"><?= e($a['user_name'] ?? 'Guest') ?> · <?= e(date('M j, g:i A', strtotime($a['created_at']))) ?></div>
                        <details>
                            <summary>input / output</summary>
                            <pre><?= e($a['input']) ?></pre>
                            <pre><?= e(str_limit($a['output'], 600)) ?></pre>
                        </details>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    </aside>
</div>
