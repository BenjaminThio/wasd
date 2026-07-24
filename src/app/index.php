<?php require './models/Icon.php'; ?>
<div class="main-div">
    <div class="intro">
        <div class="neon-text">PLAYER ONE, READY</div>

        <div class="landing-keys-container">
            <div class="landing-glass-key floating" data-key="w">W</div>
            <div class="landing-glass-key floating" data-key="a" style="animation-delay:0.5s;">A</div>
            <div class="landing-glass-key floating" data-key="s" style="animation-delay:1s;">S</div>
            <div class="landing-glass-key floating" data-key="d" style="animation-delay:1.5s;">D</div>
        </div>

        <div class="neon-text">
            "BEYOND THE KEYS: YOUR JOURNEY STARTS HERE"
        </div>

        <div class="caption">
            Your next obsession <span class="start-here">starts here.</span>
        </div>

        <div class="caption2">
            WASD is the game store built for players. Browse the catalog, wishlist what you're watching
            and check out in seconds - all in one dark, neon-soaked storefront.
        </div>

        <div class="button-container">
            <button class="button" onclick="window.location.href='<?= BASE_URL ?>/store';">Browse the store</button>
            <button class="no-highlight-button" onclick="window.location.href='<?= BASE_URL ?>/sign-up';">Create free account</button>
        </div>

        <div class="stats-minimal">
            <div>
                <strong style="font-family:Unbounded;font-weight:600;font-size:22px;">1.2M+</strong>
                <div style="font-size:12px;letter-spacing:0.15rem;">DOWNLOADS</div>
            </div>
            <div>
                <strong style="font-family:Unbounded;font-weight:600;font-size:22px;">25K</strong>
                <div style="font-size:12px;letter-spacing:0.15rem;">Community</div>
            </div>
            <div>
                <strong style="font-family:Unbounded;font-weight:600;font-size:22px;">450+</strong>
                <div style="font-size:12px;letter-spacing:0.15rem;">Active Discussions</div>
            </div>
            <div class="discount">
                <strong style="font-family:Unbounded;font-weight:600;font-size:22px;">New</strong>
                <div style="font-size:12px;letter-spacing:0.15rem;">FLASH SALE</div>
            </div>
        </div>

        <div class="trending"></div>
    </div>

    <div style="width:130%;">
        <div class="promises">Store Promises</div>
         <div class="cards">
            <div class="card-box">
                <div class="big-icon">
                    <?=
                        Icon::get('shield-halved', 24, [
                            "style" => "color: rgb(170, 240, 255);"
                        ]);
                    ?>
                </div>
                <span class="card-title">Secure & Verified</span>
                <p>Your payments are encrypted and every game is officially licensed.</p>
            </div>

            <div class="card-box">
                <div class="big-icon">
                    <?=
                        Icon::get('tag', 24, [
                            "style" => "color: rgb(255, 251, 190);"
                        ]);
                    ?>
                </div>
                <div class="card-title">Transparent Pricing</div>
                <p>The price you see is the price you pay. No hidden service fees.</p>
            </div>

            <div class="card-box">
                <div class="big-icon">
                    <?=
                        Icon::get('bolt', 24, [
                            "style" => "color: rgb(230, 173, 255);"
                        ]);
                    ?>
                </div>
                <div class="card-title">Always Available</div>
                <p>24/7 support and servers optimized for fast, reliable downloads.</p>
            </div>
        </div>
    </div>

    <div style="width:122%;" class="start-library">
        <div class="free">FREE FOREVER</div>
        <div style="margin-bottom:2rem;">
            <div class="press" style="margin-bottom:1rem;text-align:center;">
                Press start on your library
            </div>
            <div style="font-family:Outfit;font-size:17px;color:var(--muted);text-align:center;">
                Join WASD to unlock your wishlist, cart and reviews.
            </div>
        </div>
        <div style="display:flex;gap:1rem;justify-content:center;">
            <button onclick="window.location.href='<?= BASE_URL ?>/sign-up';">Sign Up Free</button>
            <button onclick="window.location.href='<?= BASE_URL ?>/sign-in';">I already have account</button>
        </div>
    </div>
</div>
<script>
(() => {
    // Clean up old global listeners if they exist from a previous SPA visit
    if (window.__wasdCleanup) {
        window.__wasdCleanup();
    }

    const keyMap = {};
    const keyElements = document.querySelectorAll('.landing-glass-key');

    if (!keyElements.length) return;

    // Attach element-level press events
    keyElements.forEach(keyEl => {
        const keyVal = keyEl.getAttribute('data-key')?.toLowerCase();
        if (!keyVal) return;

        keyMap[keyVal] = keyEl;

        const pressKey = () => activateKey(keyEl);
        const releaseKey = () => deactivateKey(keyEl);

        keyEl.addEventListener('mousedown', pressKey);
        keyEl.addEventListener('mouseup', releaseKey);
        keyEl.addEventListener('mouseleave', releaseKey);

        keyEl.addEventListener('touchstart', (e) => { e.preventDefault(); pressKey(); });
        keyEl.addEventListener('touchend', releaseKey);
    });

    function activateKey(element) {
        if (!element) return;
        element.classList.remove('floating');
        element.classList.add('pressed');
    }

    function deactivateKey(element) {
        if (!element) return;
        element.classList.remove('pressed');
        element.classList.add('floating');
    }

    // Define physical keyboard event handlers
    const handleKeyDown = (e) => {
        if (e.repeat) return;
        const pressedKey = e.key.toLowerCase();
        if (keyMap[pressedKey]) activateKey(keyMap[pressedKey]);
    };

    const handleKeyUp = (e) => {
        const releasedKey = e.key.toLowerCase();
        if (keyMap[releasedKey]) deactivateKey(keyMap[releasedKey]);
    };

    // Attach window event listeners
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);

    // Save cleanup function on window to prevent duplicate listeners on future SPA visits
    window.__wasdCleanup = () => {
        window.removeEventListener('keydown', handleKeyDown);
        window.removeEventListener('keyup', handleKeyUp);
        delete window.__wasdCleanup;
    };
})();
</script>