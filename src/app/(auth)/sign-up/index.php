<div class="page-container">
    <div class="box font">
        <div class="welcome">SIGN UP</div>

        <label for="avatar" class="avatar-label">
            <span class="avatar-plus">+</span>
        </label>
        <input type="file" id="avatar" style="display:none">

        <div class="input-group">
            <label class="field-label" for="username">USERNAME:</label>
            <input type="text" id="username" class="field-input" placeholder="Type here..." autocomplete="username">
        </div>

        <div class="input-group">
            <label class="field-label" for="email">EMAIL ADDRESS:</label>
            <input type="email" id="email" class="field-input" placeholder="Type here..." autocomplete="email">
        </div>

        <div class="input-group">
            <label class="field-label" for="password">PASSWORD:</label>
            <input type="password" id="password" class="field-input" placeholder="Type here..." autocomplete="new-password">
        </div>

        <div class="input-group">
            <label class="field-label" for="confirm">CONFIRM PASSWORD:</label>
            <input type="password" id="confirm" class="field-input" placeholder="Type here..." autocomplete="new-password">
        </div>

        <p id="signup-status" class="auth-status" role="alert" hidden></p>

        <button class="signup-button" onclick="signUp()">REGISTER</button>

        <div class="social-container">
            <?php
            require './models/Icon.php';
            echo Icon::get('facebook', 32);
            echo Icon::get('google', 32);
            echo Icon::get('discord', 32);
            ?>
        </div>

        <div class="signup-link">
            Already have an account? <a href="<?= BASE_URL ?>/sign-in">SIGN IN</a>
        </div>
    </div>
</div>

<script>
/*
 * Wrapped in an IIFE, like every other page script in this app - and unlike
 * how this file used to be written. The sign-in and sign-up pages share one
 * layout, so the SPA router soft-swaps between them instead of doing a full
 * reload; a bare top-level `const` here collided with the same name declared
 * by the other page's script the moment someone followed the "Sign in" /
 * "Sign up" link, throwing a SyntaxError that killed the whole injected
 * script before signUp() even got defined. Scoping everything to this
 * closure - and only this closure - makes each script re-runnable no matter
 * how many times the router injects it.
 */
(() => {
    const authCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showAuthStatus(el, message, ok) {
        el.textContent = message;
        el.hidden = !message;
        el.classList.toggle('is-success', !!ok);
    }

    function markAuthField(input, invalid) {
        input.classList.toggle('is-invalid', invalid);
    }

    const STRONG_PASSWORD = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/;
    const VALID_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // The button's onclick="signUp()" runs in global scope, so the function
    // it calls has to be attached to window explicitly.
    window.signUp = async function signUp() {
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm');
        const status = document.getElementById('signup-status');
        const button = document.querySelector('.signup-button');

        [usernameInput, emailInput, passwordInput, confirmInput].forEach(field => markAuthField(field, false));
        showAuthStatus(status, '');

        const username = usernameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (username.length < 3 || username.length > 50) {
            markAuthField(usernameInput, true);
            return showAuthStatus(status, 'Usernames are between 3 and 50 characters.');
        }

        if (!/^[A-Za-z0-9_-]+$/.test(username)) {
            markAuthField(usernameInput, true);
            return showAuthStatus(status, 'Usernames can only contain letters, numbers, underscores and hyphens.');
        }

        if (!VALID_EMAIL.test(email)) {
            markAuthField(emailInput, true);
            return showAuthStatus(status, 'Enter a valid email address.');
        }

        if (password !== confirm) {
            markAuthField(confirmInput, true);
            return showAuthStatus(status, 'The two passwords do not match.');
        }

        if (!STRONG_PASSWORD.test(password)) {
            markAuthField(passwordInput, true);
            return showAuthStatus(status,
                'Use at least 6 characters with an uppercase letter, a lowercase letter, a number and a symbol.');
        }

        button.disabled = true;
        button.textContent = 'CREATING…';

        try {
            const response = await fetch('<?= BASE_URL ?>/src/app/api/sign-up/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': authCsrfToken(),
                },
                body: JSON.stringify({ username, email, password, confirm }),
            });

            const data = await response.json().catch(() => null);

            if (!response.ok || !data || data.status !== 'success') {
                markAuthField(usernameInput, false);
                showAuthStatus(status, (data && data.error) || 'Could not create your account. Try again.');
                return;
            }

            showAuthStatus(status, 'Account created! Taking you in…', true);

            // A full navigation, not the SPA router: the header only decides
            // "signed in" vs "guest" on a real page load, and that is exactly
            // what just changed.
            window.location.href = '<?= BASE_URL ?>/';
        } catch (err) {
            showAuthStatus(status, 'Could not reach the server. Check your connection and try again.');
        } finally {
            button.disabled = false;
            button.textContent = 'REGISTER';
        }
    };
})();
</script>
