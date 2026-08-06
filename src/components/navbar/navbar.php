<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/Database.php';
    require_once __DIR__ . '/../../lib/Media.php';
    require_once __DIR__ . '/../../models/Icon.php';

    /**
     * The navigation adapts to who is looking at it:
     *
     *   guest  -> Home, Store, Contact + Sign in / Sign up
     *   member -> Store, Library, Dashboard + wishlist, cart and an account menu
     *
     * Before this, every finished account page (dashboard, projects, wishlist,
     * cart, profile) could only be reached by typing its URL.
     */
    $navUser = Auth::getCurrentUser();
    $navCartCount = 0;
    $navWishlistCount = 0;

    if ($navUser) {
        try {
            $navDatabase = new Database();
            $navCartCount = (int)$navDatabase->query(
                'SELECT COUNT(*) AS total FROM cart WHERE user_id = ?', [$navUser->getId()]
            )->fetch()['total'];
            $navWishlistCount = (int)$navDatabase->query(
                'SELECT COUNT(*) AS total FROM wishlist WHERE user_id = ?', [$navUser->getId()]
            )->fetch()['total'];
        } catch (Throwable) {
            // Counters are decoration - never let them take the header down.
        }
    }

    $navPath = '/' . trim((string)($_GET['page'] ?? ''), '/');
    $isActive = fn(string $route): string => $navPath === $route ? ' is-active' : '';
?>
<header class="header">
    <div class="inner-header">
        <a class="keys-container" href="<?= BASE_URL ?>" aria-label="WASD home">
            <span class="glass-key" aria-hidden="true">W</span>
            <span class="glass-key" aria-hidden="true">A</span>
            <span class="glass-key" aria-hidden="true">S</span>
            <span class="glass-key" aria-hidden="true">D</span>
        </a>

        <button type="button" class="nav-toggle" id="nav-toggle"
                aria-expanded="false" aria-controls="primary-nav" aria-label="Open menu">
            <?= Icon::get('menu', 22) ?>
        </button>

        <nav class="nav" id="primary-nav">
            <?php if ($navUser): ?>
                <a class="nav-link<?= $isActive('/store') ?>" href="<?= BASE_URL ?>/store">Store</a>
                <a class="nav-link<?= $isActive('/library') ?>" href="<?= BASE_URL ?>/library">Library</a>
                <a class="nav-link<?= $isActive('/dashboard') ?>" href="<?= BASE_URL ?>/dashboard">Dashboard</a>
                <a class="nav-link<?= $isActive('/contact') ?>" href="<?= BASE_URL ?>/contact">Contact</a>

                <div class="nav-actions">
                    <a class="nav-icon-link<?= $isActive('/wishlist') ?>" href="<?= BASE_URL ?>/wishlist"
                       aria-label="Wishlist" title="Wishlist">
                        <?= Icon::get('heart', 19) ?>
                        <span class="nav-count" id="nav-wishlist-count"
                              <?= $navWishlistCount === 0 ? 'hidden' : '' ?>><?= $navWishlistCount ?></span>
                    </a>

                    <a class="nav-icon-link<?= $isActive('/cart') ?>" href="<?= BASE_URL ?>/cart"
                       aria-label="Cart" title="Cart">
                        <?= Icon::get('cart', 19) ?>
                        <span class="nav-count" id="nav-cart-count"
                              <?= $navCartCount === 0 ? 'hidden' : '' ?>><?= $navCartCount ?></span>
                    </a>

                    <div class="account-menu">
                        <button type="button" class="account-trigger" id="account-trigger"
                                aria-expanded="false" aria-haspopup="true">
                            <?php $navAvatar = Media::url($navUser->getAvatarPath()); ?>
                            <span class="account-avatar">
                                <?php if ($navAvatar !== ''): ?>
                                    <img src="<?= htmlspecialchars($navAvatar, ENT_QUOTES) ?>" alt="">
                                <?php else: ?>
                                    <?= strtoupper(htmlspecialchars(substr($navUser->getUsername(), 0, 1))) ?>
                                <?php endif; ?>
                            </span>
                            <span class="account-name"><?= htmlspecialchars($navUser->getUsername()) ?></span>
                            <?= Icon::get('chevron-down', 16) ?>
                        </button>

                        <div class="account-dropdown" id="account-dropdown" hidden>
                            <a href="<?= BASE_URL ?>/profile"><?= Icon::get('user', 16) ?> Profile</a>
                            <a href="<?= BASE_URL ?>/library"><?= Icon::get('gamepad', 16) ?> My library</a>
                            <a href="<?= BASE_URL ?>/dashboard"><?= Icon::get('grid', 16) ?> Developer dashboard</a>
                            <a href="<?= BASE_URL ?>/project"><?= Icon::get('plus', 16) ?> New project</a>
                            <a href="<?= BASE_URL ?>/wishlist"><?= Icon::get('heart', 16) ?> Wishlist</a>
                            <a href="<?= BASE_URL ?>/cart"><?= Icon::get('cart', 16) ?> Cart</a>
                            <div class="account-dropdown-line"></div>
                            <a class="account-danger" href="<?= BASE_URL ?>/logout" data-no-spa>
                                <?= Icon::get('logout', 16) ?> Log out
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a class="nav-link<?= $navPath === '/' ? ' is-active' : '' ?>" href="<?= BASE_URL ?>">Home</a>
                <a class="nav-link<?= $isActive('/store') ?>" href="<?= BASE_URL ?>/store">Store</a>
                <a class="nav-link<?= $isActive('/contact') ?>" href="<?= BASE_URL ?>/contact">Contact</a>
                <a class="nav-link<?= $isActive('/contact/support') ?>" href="<?= BASE_URL ?>/contact/support">Support</a>

                <div class="nav-actions">
                    <a class="nav-link" href="<?= BASE_URL ?>/sign-in">Sign in</a>
                    <a class="sign-up-box" href="<?= BASE_URL ?>/sign-up">Sign up free</a>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>

<script>
/* The header lives outside the SPA injection zone, so this runs once per full
   page load and keeps working across soft navigations. */
(() => {
    if (window.__wasdNavReady) return;
    window.__wasdNavReady = true;

    const onReady = fn =>
        document.readyState === 'loading'
            ? document.addEventListener('DOMContentLoaded', fn, { once: true })
            : fn();

    onReady(() => {
        const nav = document.getElementById('primary-nav');
        const toggle = document.getElementById('nav-toggle');
        const trigger = document.getElementById('account-trigger');
        const dropdown = document.getElementById('account-dropdown');

        function closeMenus() {
            nav?.classList.remove('is-open');
            toggle?.setAttribute('aria-expanded', 'false');
            if (dropdown) dropdown.hidden = true;
            trigger?.setAttribute('aria-expanded', 'false');
        }

        toggle?.addEventListener('click', () => {
            const open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(open));
        });

        trigger?.addEventListener('click', event => {
            event.stopPropagation();
            const open = dropdown.hidden;
            dropdown.hidden = !open;
            trigger.setAttribute('aria-expanded', String(open));
        });

        document.addEventListener('click', event => {
            if (!event.target.closest('.account-menu')) {
                if (dropdown) dropdown.hidden = true;
                trigger?.setAttribute('aria-expanded', 'false');
            }
            if (event.target.closest('a')) closeMenus();
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeMenus();
        });

        // The header is not re-rendered during SPA navigation, so pages update
        // these counters directly after a cart / wishlist change.
        window.wasdSetBadge = (name, value) => {
            const badge = document.getElementById('nav-' + name + '-count');
            if (!badge) return;
            badge.textContent = value;
            badge.hidden = Number(value) <= 0;
        };

        window.wasdBumpBadge = (name, delta) => {
            const badge = document.getElementById('nav-' + name + '-count');
            if (!badge) return;
            window.wasdSetBadge(name, Math.max(0, (parseInt(badge.textContent, 10) || 0) + delta));
        };

        /**
         * Keeps the header in step with a profile save. Without this the new
         * picture only appeared after a full page load, because the header is
         * rendered by PHP once and never re-rendered during soft navigation.
         */
        window.wasdSetAvatar = (url, username) => {
            const holder = document.querySelector('.account-avatar');
            if (!holder) return;

            if (url) {
                const cacheBuster = url.includes('?') ? '' : '?t=' + Date.now();
                holder.innerHTML = '<img src="' + url + cacheBuster + '" alt="">';
            } else if (username) {
                holder.textContent = username.charAt(0).toUpperCase();
            }

            const name = document.querySelector('.account-name');
            if (name && username) name.textContent = username;
        };

        // Keyboard-wave flourish on the logo.
        const keys = document.querySelectorAll('.header .glass-key');
        const container = document.querySelector('.keys-container');
        let timers = [];

        const clearWave = () => {
            timers.forEach(clearTimeout);
            timers = [];
            keys.forEach(key => key.classList.remove('nav-hovered'));
        };

        container?.addEventListener('mouseenter', () => {
            clearWave();
            keys.forEach((key, index) => {
                timers.push(setTimeout(() => key.classList.add('nav-hovered'), index * 90));
            });
        });

        container?.addEventListener('mouseleave', clearWave);
    });
})();
</script>
