<?php
    require_once __DIR__ . '/../../lib/Auth.php';

    /**
     * Footer.
     *
     * The wave animation on the logo keys is bound here for the footer only -
     * the header binds its own in navbar.php, so the two copies of the same
     * routine that used to live in this file are gone.
     */
    $footerUser = Auth::getCurrentUser();
?>
<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <a class="footer-keys-container" href="<?= BASE_URL ?>" aria-label="WASD home">
                <span class="footer-glass-key">W</span>
                <span class="footer-glass-key">A</span>
                <span class="footer-glass-key">S</span>
                <span class="footer-glass-key">D</span>
            </a>
            <p class="footer-tagline">Games for everyone, everywhere.</p>
        </div>

        <nav class="footer-links" aria-label="Footer">
            <div class="footer-column">
                <span class="footer-heading">Explore</span>
                <a href="<?= BASE_URL ?>/store">Store</a>
                <?php if ($footerUser): ?>
                    <a href="<?= BASE_URL ?>/library">My library</a>
                    <a href="<?= BASE_URL ?>/wishlist">Wishlist</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/sign-up">Create account</a>
                    <a href="<?= BASE_URL ?>/sign-in">Sign in</a>
                <?php endif; ?>
            </div>

            <div class="footer-column">
                <span class="footer-heading">Create</span>
                <a href="<?= BASE_URL ?>/dashboard">Developer dashboard</a>
                <a href="<?= BASE_URL ?>/project">New project</a>
                <a href="<?= BASE_URL ?>/contact/partneshipX">Partners</a>
            </div>

            <div class="footer-column">
                <span class="footer-heading">Company</span>
                <a href="<?= BASE_URL ?>/contact">Contact</a>
                <a href="<?= BASE_URL ?>/contact/support">Support</a>
                <a href="<?= BASE_URL ?>/contact/press">Press</a>
            </div>
        </nav>
    </div>

    <div class="footer-baseline">
        <span>&copy; <?= date('Y') ?> WASD Interactive.</span>
        <span>Built for players and the people who make games.</span>
    </div>
</footer>

<script>
(() => {
    if (window.__wasdFooterReady) return;
    window.__wasdFooterReady = true;

    const start = () => {
        const container = document.querySelector('.footer-keys-container');
        const keys = document.querySelectorAll('.footer-glass-key');
        if (!container || !keys.length) return;

        let timers = [];

        const clearWave = () => {
            timers.forEach(clearTimeout);
            timers = [];
            keys.forEach(key => key.classList.remove('footer-hovered'));
        };

        container.addEventListener('mouseenter', () => {
            clearWave();
            keys.forEach((key, index) => {
                timers.push(setTimeout(() => key.classList.add('footer-hovered'), index * 90));
            });
        });

        container.addEventListener('mouseleave', clearWave);
    };

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', start, { once: true })
        : start();
})();
</script>
