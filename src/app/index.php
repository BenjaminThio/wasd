<?php
    require_once __DIR__ . '/../lib/Auth.php';
    require_once __DIR__ . '/../lib/View.php';
    require_once __DIR__ . '/../models/Icon.php';
    require_once __DIR__ . '/../models/Games.php';

    $visitor = Auth::getCurrentUser();

    // The counters used to be hard-coded. They now describe the real catalog.
    $database = new Database();
    $catalog = $database->query(
        "SELECT COUNT(*) AS games,
                COALESCE(SUM(downloads), 0) AS downloads,
                COALESCE(SUM(views), 0) AS views,
                SUM(CASE WHEN discount > 0 THEN 1 ELSE 0 END) AS on_sale
         FROM game WHERE visibility = 'Public'"
    )->fetch();

    $trending = Games::search(['sort' => 'popular', 'limit' => 4])['games'];

    $promises = [
        ['shield-halved', 'Secure & verified', 'Every build is scanned and served through an ownership check, so downloads are always the real thing.', 'rgb(170, 240, 255)'],
        ['tag', 'Transparent pricing', 'The price you see is the price you pay. No hidden service fees, ever.', 'rgb(255, 251, 190)'],
        ['bolt', 'Always available', 'Your library follows you: buy once and download on every platform the developer ships.', 'rgb(230, 173, 255)'],
    ];
?>

<div class="landing">

    <!-- ------------------------------------------------------------- hero -->
    <section class="hero">
        <span class="neon-text">PLAYER ONE, READY</span>

        <div class="landing-keys-container">
            <div class="landing-glass-key floating" data-key="w">W</div>
            <div class="landing-glass-key floating" data-key="a" style="animation-delay:.5s">A</div>
            <div class="landing-glass-key floating" data-key="s" style="animation-delay:1s">S</div>
            <div class="landing-glass-key floating" data-key="d" style="animation-delay:1.5s">D</div>
        </div>

        <span class="neon-text">"BEYOND THE KEYS: YOUR JOURNEY STARTS HERE"</span>

        <h1 class="hero-title">
            Your next obsession <span class="gradient-text">starts here.</span>
        </h1>

        <p class="hero-copy">
            WASD is the game store built for players. Browse the catalog, wishlist what you are
            watching and check out in seconds - all in one dark, neon-soaked storefront.
        </p>

        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= BASE_URL ?>/store">Browse the store</a>
            <?php if ($visitor): ?>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>/library">Open my library</a>
            <?php else: ?>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>/sign-up">Create free account</a>
            <?php endif; ?>
        </div>

        <div class="stats-minimal">
            <div>
                <strong><?= View::compactNumber((int)$catalog['downloads']) ?></strong>
                <span>Downloads</span>
            </div>
            <div>
                <strong><?= View::compactNumber((int)$catalog['views']) ?></strong>
                <span>Page views</span>
            </div>
            <div>
                <strong><?= number_format((int)$catalog['games']) ?></strong>
                <span>Games listed</span>
            </div>
            <div class="stat-highlight">
                <strong><?= (int)$catalog['on_sale'] ?></strong>
                <span>On sale now</span>
            </div>
        </div>
    </section>

    <!-- --------------------------------------------------------- trending -->
    <?php if (!empty($trending)): ?>
        <section class="landing-section">
            <header class="landing-section-head">
                <h2 class="landing-heading">Trending right now</h2>
                <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>/store">
                    See the whole store <?= Icon::get('chevron-right', 14) ?>
                </a>
            </header>

            <div class="game-grid stagger">
                <?php foreach ($trending as $game): ?>
                    <?php require __DIR__ . '/../components/game-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- --------------------------------------------------------- promises -->
    <section class="landing-section">
        <h2 class="landing-heading">Store promises</h2>

        <div class="promise-grid stagger">
            <?php foreach ($promises as [$icon, $title, $copy, $color]): ?>
                <article class="card card--interactive promise-card">
                    <span class="big-icon"><?= Icon::get($icon, 24, ['style' => "color: $color;"]) ?></span>
                    <h3 class="promise-title"><?= $title ?></h3>
                    <p class="promise-copy"><?= $copy ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- -------------------------------------------------------------- cta -->
    <section class="start-library">
        <span class="free">FREE FOREVER</span>

        <h2 class="press-heading">
            <?= $visitor ? 'Ready for your next game?' : 'Press start on your library' ?>
        </h2>

        <p class="hero-copy">
            <?= $visitor
                ? 'Your wishlist, cart and library are one click away.'
                : 'Join WASD to unlock your wishlist, cart and reviews.' ?>
        </p>

        <div class="hero-actions">
            <?php if ($visitor): ?>
                <a class="btn btn-primary" href="<?= BASE_URL ?>/store">Browse the store</a>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>/dashboard">Publish your own game</a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= BASE_URL ?>/sign-up">Sign up free</a>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>/sign-in">I already have an account</a>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
(() => {
    // Remove listeners left behind by a previous visit to this page.
    if (window.__wasdCleanup) window.__wasdCleanup();

    const keyElements = document.querySelectorAll('.landing-glass-key');
    if (!keyElements.length) return;

    const keyMap = {};

    const press = element => {
        element.classList.remove('floating');
        element.classList.add('pressed');
    };

    const release = element => {
        element.classList.remove('pressed');
        element.classList.add('floating');
    };

    keyElements.forEach(element => {
        const key = element.dataset.key?.toLowerCase();
        if (!key) return;

        keyMap[key] = element;

        element.addEventListener('mousedown', () => press(element));
        element.addEventListener('mouseup', () => release(element));
        element.addEventListener('mouseleave', () => release(element));
        element.addEventListener('touchstart', event => { event.preventDefault(); press(element); }, { passive: false });
        element.addEventListener('touchend', () => release(element));
    });

    const onKeyDown = event => {
        if (event.repeat) return;
        const element = keyMap[event.key.toLowerCase()];
        if (element) press(element);
    };

    const onKeyUp = event => {
        const element = keyMap[event.key.toLowerCase()];
        if (element) release(element);
    };

    window.addEventListener('keydown', onKeyDown);
    window.addEventListener('keyup', onKeyUp);

    window.__wasdCleanup = () => {
        window.removeEventListener('keydown', onKeyDown);
        window.removeEventListener('keyup', onKeyUp);
        delete window.__wasdCleanup;
    };
})();
</script>
