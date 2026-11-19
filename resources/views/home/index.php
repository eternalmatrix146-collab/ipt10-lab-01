<?php
/** @var \Phantom\View\Template $this */
$this->layout('layouts.main', ['title' => null, 'bodyClass' => 'is-home']);
?>
<section class="hero">
    <div class="hero__copy">
        <p class="kicker">Open after dark · Ships nationwide</p>
        <h1 class="hero__title"><?= ransom('Steal the') ?><br><?= ransom('Night', 'ransom--xl') ?></h1>
        <p class="hero__lead">Masks, coats, gadgets and late-night fuel — curated for people who do their best work when the city sleeps. Not sure what you want? <b>Rook</b>, our AI concierge, will case the shop for you.</p>
        <div class="hero__cta">
            <a class="btn btn--red btn--lg" href="<?= route('shop') ?>">Enter the shop</a>
            <button class="btn btn--white btn--lg" type="button" data-rook-open data-rook-prompt="Recommend three things for a first-time visitor">Ask Rook</button>
        </div>
    </div>
    <div class="hero__art" aria-hidden="true">
        <div class="hero__burst"></div>
        <div class="hero__card">
            <span class="hero__card-top">Calling card</span>
            <strong>Your<br>style<br>will be<br>taken.</strong>
            <span class="hero__card-sign">— Midnight Market</span>
        </div>
        <div class="hero__mask">
            <svg viewBox="0 0 400 260"><path fill-rule="evenodd" d="M20 110 Q80 0 200 60 Q320 0 380 110 Q360 230 250 210 Q215 205 200 170 Q185 205 150 210 Q40 230 20 110 Z M85 115 Q120 70 170 105 Q135 150 85 115 Z M230 105 Q280 70 315 115 Q265 150 230 105 Z"/></svg>
        </div>
    </div>
</section>

<section class="section">
    <div class="section__head">
        <h2 class="slash-title"><span>Targets</span> by category</h2>
    </div>
    <div class="cats">
        <?php foreach ($categories as $i => $category): ?>
            <a class="cat cat--<?= e($category->motif) ?>" href="<?= route('shop') ?>?category=<?= e($category->slug) ?>" style="--i: <?= $i ?>">
                <span class="cat__num">0<?= $i + 1 ?></span>
                <span class="cat__name"><?= e($category->name) ?></span>
                <span class="cat__desc"><?= e((string) $category->description) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section__head">
        <h2 class="slash-title"><span>Featured</span> loot</h2>
        <a class="link-arrow" href="<?= route('shop') ?>">See everything</a>
    </div>
    <div class="grid">
        <?php foreach ($featured as $product): ?>
            <?= $this->insert('partials.product-card', ['product' => $product]) ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="section rook-promo">
    <div class="rook-promo__inner">
        <div class="rook-promo__badge" aria-hidden="true">R</div>
        <div>
            <h2 class="slash-title slash-title--light"><span>Meet</span> Rook</h2>
            <p class="rook-promo__lead">Rook is an <b>agentic AI</b> built on NVIDIA Nemotron. It doesn't just chat — it uses the shop's tools: searching the catalog, checking stock, converting currencies, filling your bag and tracking orders. You always confirm checkout yourself.</p>
            <ol class="steps">
                <li><b>You ask</b><span>"A warm coat and coffee beans, under ₱6,000 total."</span></li>
                <li><b>Rook plans</b><span>Calls <code>search_products</code> twice and compares prices.</span></li>
                <li><b>Rook acts</b><span>Adds the picks with <code>add_to_cart</code> when you say so.</span></li>
                <li><b>You decide</b><span>Review the bag and check out.</span></li>
            </ol>
            <button class="btn btn--red" type="button" data-rook-open data-rook-prompt="A warm coat and coffee beans, under ₱6,000 total. What do you suggest?">Try that request</button>
        </div>
    </div>
</section>

<section class="section">
    <div class="section__head">
        <h2 class="slash-title"><span>Fresh</span> arrivals</h2>
    </div>
    <div class="grid grid--4">
        <?php foreach ($newest as $product): ?>
            <?= $this->insert('partials.product-card', ['product' => $product]) ?>
        <?php endforeach; ?>
    </div>
</section>
