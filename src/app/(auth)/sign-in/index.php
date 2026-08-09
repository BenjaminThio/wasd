<div class="page-container">
    <div class="box font">
        <div class="welcome">WELCOME</div>

        <div class="input-group form-intro">Your wishlist, cart and reviews are waiting where you left them.</div>

        <div class="input-group">
            <label class="field-label" for="email">EMAIL ADDRESS:</label>
            <input type="email" id="email" class="field-input" placeholder="Type here...">
        </div>

        <div class="input-group">
            <label class="field-label" for="password">PASSWORD:</label>
            <input type="password" id="password" class="field-input" placeholder="Type here...">
        </div>

        <div class="remember-me">
            <input type="checkbox" id="remember-box">
            <label for="remember-box">REMEMBER ME</label>
        </div>

        <p id="signin-status" class="auth-status" role="alert" hidden></p>

        <div>
            <button onclick="signIn()" class="sign-in-button font">SIGN IN</button>
        </div>

        <div class="social-container">
            <?php
            require './models/Icon.php';

            echo Icon::get('facebook', 32);
            echo Icon::get('google', 32);
            echo Icon::get('discord', 32);
            ?>
        </div>

        <div class="signup-link">
            New to WASD? <a href="<?= BASE_URL ?>/sign-up">SIGN UP</a>
        </div>
    </div>
</div>

<script>
/*
 * Wrapped in an IIFE, like every other page script in this app. Sign-in and
 * sign-up share one layout, so the SPA router soft-swaps between them rather
 * than reloading; a bare top-level const/function here collided with the
 * same name declared by the other page's script and threw a SyntaxError that
 * killed the injected script before signIn() was even defined.
 */
(() => {
    const authCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showAuthStatus(el, message) {
        el.textContent = message;
        el.hidden = !message;
    }

    function markAuthField(input, invalid) {
        input.classList.toggle('is-invalid', invalid);
    }

    // The button's onclick="signIn()" runs in global scope.
    window.signIn = async function signIn() {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const status = document.getElementById('signin-status');
        const button = document.querySelector('.sign-in-button');

        markAuthField(emailInput, false);
        markAuthField(passwordInput, false);
        showAuthStatus(status, '');

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email || !password) {
            if (!email) markAuthField(emailInput, true);
            if (!password) markAuthField(passwordInput, true);
            return showAuthStatus(status, 'Enter your email and password.');
        }

        button.disabled = true;
        button.textContent = 'SIGNING IN…';

        try {
            const response = await fetch('<?= BASE_URL ?>/src/app/api/sign-in/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': authCsrfToken(),
                },
                body: JSON.stringify({ email, password }),
            });

            const data = await response.json().catch(() => null);

            if (!response.ok || !data || data.status !== 'success') {
                markAuthField(emailInput, true);
                markAuthField(passwordInput, true);
                showAuthStatus(status, (data && data.error) || 'Could not sign in. Try again.');
                return;
            }

            // A full navigation, not the SPA router: the header only decides
            // "signed in" vs "guest" on a real page load, and that is exactly
            // what just changed.
            window.location.href = '<?= BASE_URL ?>/';
        } catch (err) {
            showAuthStatus(status, 'Could not reach the server. Check your connection and try again.');
        } finally {
            button.disabled = false;
            button.textContent = 'SIGN IN';
        }
    };
})();
</script>