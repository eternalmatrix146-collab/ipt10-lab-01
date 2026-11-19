<?php $aiMode = $aiMode ?? 'offline'; ?>
<div class="rook" data-rook
     data-endpoint="<?= route('agent.chat') ?>"
     data-history="<?= route('agent.history') ?>"
     data-reset="<?= route('agent.reset') ?>">
    <button class="rook-fab" type="button" aria-expanded="false" aria-controls="rook-panel" data-rook-toggle>
        <span class="rook-fab__star" aria-hidden="true">★</span>
        <span class="rook-fab__label">Ask<br><b>Rook</b></span>
    </button>

    <section class="rook-panel" id="rook-panel" role="dialog" aria-label="Rook shopping assistant" hidden>
        <header class="rook-panel__head">
            <div class="rook-avatar" aria-hidden="true">R</div>
            <div class="rook-panel__title">
                <strong>ROOK</strong>
                <span>Concierge · <em class="mode mode--<?= e($aiMode) ?>"><?= $aiMode === 'nim' ? 'Nemotron agent' : 'Offline mode' ?></em></span>
            </div>
            <button type="button" class="icon-btn" data-rook-reset title="New conversation" aria-label="New conversation">↺</button>
            <button type="button" class="icon-btn" data-rook-toggle aria-label="Close">×</button>
        </header>

        <div class="chat-log" data-chat-log aria-live="polite">
            <div class="msg msg--assistant msg--intro">
                <div class="bubble">
                    <p>Evening. I'm <b>Rook</b>. I can search the shop, compare items, fill your bag and track your orders. What are we after tonight?</p>
                </div>
            </div>
        </div>

        <div class="chat-suggest" data-chat-suggest>
            <button type="button" data-suggest>Find a gift under ₱1,000</button>
            <button type="button" data-suggest>What's good for late-night coffee?</button>
            <button type="button" data-suggest>What's in my cart?</button>
            <button type="button" data-suggest>Track my latest order</button>
        </div>

        <form class="chat-form" data-chat-form>
            <label class="sr-only" for="rook-input">Message Rook</label>
            <textarea id="rook-input" name="message" rows="1" maxlength="1000" placeholder="Tell Rook what you need…" required></textarea>
            <button type="submit" class="chat-send" aria-label="Send">➤</button>
        </form>
    </section>
</div>
