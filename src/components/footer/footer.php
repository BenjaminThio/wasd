<footer class="footer">
    <div class="footer-container">
        <div class="footer-keys-container" onclick="window.location.href='<?= BASE_URL ?>';">
            <div class="footer-glass-key" data-footer-key="0">W</div>
            <div class="footer-glass-key" data-footer-key="1">A</div>
            <div class="footer-glass-key" data-footer-key="2">S</div>
            <div class="footer-glass-key" data-footer-key="3">D</div>
        </div>

        <div class="copyright">
            <div>© 2026 WASD Interactive. Games for everyone, everywhere.</div>
        </div>
    </div>
</footer>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Header Wave Logic
    const headerContainer = document.querySelector('.keys-container');
    const headerKeys = document.querySelectorAll('.header .glass-key');
    let headerTimeouts = [];

    if (headerContainer && headerKeys.length) {
        headerContainer.addEventListener('mouseenter', () => {
            clearWave(headerTimeouts, headerKeys, 'nav-hovered');
            headerKeys.forEach((key, index) => {
                const timeout = setTimeout(() => key.classList.add('nav-hovered'), index * 90);
                headerTimeouts.push(timeout);
            });
        });

        headerContainer.addEventListener('mouseleave', () => {
            clearWave(headerTimeouts, headerKeys, 'nav-hovered');
        });
    }

    // Footer Wave Logic
    const footerContainer = document.querySelector('.footer-keys-container');
    const footerKeys = document.querySelectorAll('.footer-glass-key');
    let footerTimeouts = [];

    if (footerContainer && footerKeys.length) {
        footerContainer.addEventListener('mouseenter', () => {
            clearWave(footerTimeouts, footerKeys, 'footer-hovered');
            footerKeys.forEach((key, index) => {
                const timeout = setTimeout(() => key.classList.add('footer-hovered'), index * 90);
                footerTimeouts.push(timeout);
            });
        });

        footerContainer.addEventListener('mouseleave', () => {
            clearWave(footerTimeouts, footerKeys, 'footer-hovered');
        });
    }

    // Helper function to clear timers and active classes
    function clearWave(timeoutArray, elements, className) {
        timeoutArray.forEach(t => clearTimeout(t));
        timeoutArray.length = 0;
        elements.forEach(el => el.classList.remove(className));
    }
});
</script>