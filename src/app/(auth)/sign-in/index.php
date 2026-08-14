<?php
    require_once __DIR__ . '/../../../models/Icon.php';
?>

<div class="page-container">
    <div class="box font">
        <header class="auth-head">
            <div class="auth-keys" aria-hidden="true">
                <span class="auth-key">W</span>
                <span class="auth-key">A</span>
                <span class="auth-key">S</span>
                <span class="auth-key">D</span>
            </div>
            <h1 class="welcome">WELCOME</h1>
        </header>

        <p class="form-intro">Your wishlist, cart and reviews are waiting where you left them.</p>

        <!--
            A real <form> rather than a button with an onclick handler. That is
            what makes the Enter key submit, what lets a password manager
            recognise the pair of fields and offer to fill them, and what tells
            a screen reader these controls belong together.
        -->
        <form class="auth-form" id="signin-form" novalidate>
            <div class="input-group">
                <label class="field-label" for="email">
                    EMAIL ADDRESS:
                    <span class="field-info-wrap">
                        <button type="button" class="field-info"
                                aria-label="Which email to use"
                                aria-describedby="email-tip">i</button>
                        <span class="field-tip" id="email-tip" role="tooltip">
                            The address you registered with. Usernames are not
                            accepted here.
                        </span>
                    </span>
                </label>
                <input type="email" id="email" name="email" class="field-input"
                       placeholder="you@example.com" autocomplete="email"
                       aria-describedby="email-error" required>
                <p class="field-error" id="email-error" hidden></p>
            </div>

            <div class="input-group">
                <label class="field-label" for="password">
                    PASSWORD:
                    <span class="field-info-wrap">
                        <button type="button" class="field-info"
                                aria-label="Sign in attempt limit"
                                aria-describedby="password-tip">i</button>
                        <span class="field-tip" id="password-tip" role="tooltip">
                            Five wrong attempts locks sign in for fifteen minutes.
                            Use the eye to check what you typed.
                        </span>
                    </span>
                </label>
                <div class="field-wrap">
                    <input type="password" id="password" name="password" class="field-input"
                           placeholder="Type here..." autocomplete="current-password"
                           aria-describedby="password-error" required>
                    <button type="button" class="password-toggle" data-target="password"
                            aria-label="Show password" aria-pressed="false">
                        <span class="icon-show"><?= Icon::get('eye', 18) ?></span>
                        <span class="icon-hide"><?= Icon::get('eye-off', 18) ?></span>
                    </button>
                </div>
                <p class="field-error" id="password-error" hidden></p>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember-box" name="remember">
                <label for="remember-box">REMEMBER ME</label>
            </div>

            <!-- role="alert" so whatever the server says is announced, not just
                 drawn. Sighted users see it; screen reader users hear it. -->
            <p class="auth-status" id="signin-status" role="alert" aria-live="polite" hidden></p>

            <button type="submit" class="sign-in-button font" id="signin-button">SIGN IN</button>
        </form>

        <div class="auth-divider">OR CONTINUE WITH</div>

        <div class="social-container">
            <button type="button" class="social-btn" data-social="Facebook" aria-label="Sign in with Facebook">
                <?= Icon::get('facebook', 22) ?>
            </button>
            <button type="button" class="social-btn" data-social="Google" aria-label="Sign in with Google">
                <?= Icon::get('google', 22) ?>
            </button>
            <button type="button" class="social-btn" data-social="Discord" aria-label="Sign in with Discord">
                <?= Icon::get('discord', 22) ?>
            </button>
        </div>

        <div class="signup-link">
            New to WASD? <a href="<?= BASE_URL ?>/sign-up">SIGN UP</a>
        </div>
    </div>
</div>

<script>
    /*
    * The shared helper in public/js/wasd-auth.js owns the password toggle, the
    * Caps Lock warning, the per-field messages and the submit handling. This
    * page only describes its own fields and what a valid value looks like.
    */
    WASDAuth.init({
        form: 'signin-form',
        button: 'signin-button',
        status: 'signin-status',
        endpoint: '<?= BASE_URL ?>/src/app/api/sign-in/index.php',
        busyLabel: 'SIGNING IN',
        redirect: '<?= BASE_URL ?>/',

        fields: [
            {
                input: 'email',
                emptyMessage: 'Enter the email address you registered with.',
                validate: value => WASDAuth.VALID_EMAIL.test(value.trim())
                    ? null
                    : 'That does not look like an email address.',
            },
            {
                input: 'password',
                emptyMessage: 'Enter your password.',
                // Deliberately no strength check here. The rules may have been
                // different when this account was made, and telling somebody
                // their existing password is "weak" while they are trying to
                // sign in helps nobody.
            },
        ],

        payload: () => ({
            email: document.getElementById('email').value.trim(),
            password: document.getElementById('password').value,
            remember: document.getElementById('remember-box').checked,
        }),

        onSuccess: () => 'Signed in. Taking you through...',
    });

    /*
    * The three marks below are not wired to any provider. Rather than leaving
    * dead controls on the page, they say so when pressed.
    */
    (() => {
        const status = document.getElementById('signin-status');

        document.querySelectorAll('.social-btn').forEach(button => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';

            button.addEventListener('click', () => {
                WASDAuth.setStatus(status,
                    button.dataset.social + ' sign-in is not connected yet. ' +
                    'Use your email address and password for now.');
            });
        });
    })();
</script>
