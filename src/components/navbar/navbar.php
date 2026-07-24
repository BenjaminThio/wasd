<header class="header">
    <div class="inner-header">
        <div class="keys-container" onclick="window.location.href='<?= BASE_URL ?>';">
            <div class="glass-key" data-nav-key="0">W</div>
            <div class="glass-key" data-nav-key="1">A</div>
            <div class="glass-key" data-nav-key="2">S</div>
            <div class="glass-key" data-nav-key="3">D</div>
        </div>

        <nav class="nav">
            <a href="<?= BASE_URL ?>">Home</a>
            <a href="<?= BASE_URL ?>/store">Store</a>
            <a href="<?= BASE_URL ?>/contact">Contact</a>
            <a href="<?= BASE_URL ?>/sign-in">Sign In</a>
            <a href="<?= BASE_URL ?>/sign-up" class="sign-up-box">Sign up free</a>
        </nav>
    </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const keysContainer = document.querySelector('.keys-container');
    const headerKeys = document.querySelectorAll('.header .glass-key');
    let waveTimeouts = [];

    if (!keysContainer || !headerKeys.length) return;

    keysContainer.addEventListener('mouseenter', () => {
        // Clear any leftover timeouts from previous rapid hover
        clearWave();

        // Trigger wave sequentially with a 90ms delay between keys
        headerKeys.forEach((key, index) => {
            const timeout = setTimeout(() => {
                key.classList.add('nav-hovered');
            }, index * 90); // Adjust 90 to speed up/slow down the wave
            
            waveTimeouts.push(timeout);
        });
    });

    keysContainer.addEventListener('mouseleave', () => {
        clearWave();
    });

    function clearWave() {
        waveTimeouts.forEach(t => clearTimeout(t));
        waveTimeouts = [];
        headerKeys.forEach(key => key.classList.remove('nav-hovered'));
    }
});
</script>