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
            <h1 class="welcome">SIGN UP</h1>
        </header>

        <p class="auth-sub">One account to buy, review and publish. It takes about a minute.</p>

        <form class="auth-form" id="signup-form" novalidate>
            <div class="input-group">
                <!--
                    The rules live behind the circled "i" rather than beside the
                    label. As inline text they only appeared on this one field,
                    which read as clutter here and as a missing explanation on
                    every other field.
                -->
                <label class="field-label" for="username">
                    USERNAME:
                    <span class="field-info-wrap">
                        <button type="button" class="field-info"
                                aria-label="Username requirements"
                                aria-describedby="username-tip">i</button>
                        <span class="field-tip" id="username-tip" role="tooltip">
                            3 to 50 characters. Letters, numbers, underscores and
                            hyphens only.
                        </span>
                    </span>
                </label>
                <input type="text" id="username" name="username" class="field-input"
                       placeholder="Type here..." autocomplete="username"
                       minlength="3" maxlength="50" aria-describedby="username-error" required>
                <p class="field-error" id="username-error" hidden></p>
            </div>

            <div class="input-group">
                <label class="field-label" for="email">
                    EMAIL ADDRESS:
                    <span class="field-info-wrap">
                        <button type="button" class="field-info"
                                aria-label="Email requirements"
                                aria-describedby="email-tip">i</button>
                        <span class="field-tip" id="email-tip" role="tooltip">
                            Used to sign in and to reach you about your orders. We
                            do not share it.
                        </span>
                    </span>
                </label>
                <input type="email" id="email" name="email" class="field-input"
                       placeholder="you@example.com" autocomplete="email"
                       maxlength="191" aria-describedby="email-error" required>
                <p class="field-error" id="email-error" hidden></p>
            </div>

            <div class="input-group">
                <label class="field-label" for="password">
                    PASSWORD:
                    <span class="field-info-wrap">
                        <button type="button" class="field-info"
                                aria-label="How your password is stored"
                                aria-describedby="password-tip">i</button>
                        <span class="field-tip" id="password-tip" role="tooltip">
                            Stored as a bcrypt hash. Nobody at WASD can read it, and
                            neither can anyone who gets hold of the database.
                        </span>
                    </span>
                </label>
                <div class="field-wrap">
                    <input type="password" id="password" name="password" class="field-input"
                           placeholder="Type here..." autocomplete="new-password"
                           aria-describedby="password-error password-strength" required>
                    <button type="button" class="password-toggle" data-target="password"
                            aria-label="Show password" aria-pressed="false">
                        <span class="icon-show"><?= Icon::get('eye', 18) ?></span>
                        <span class="icon-hide"><?= Icon::get('eye-off', 18) ?></span>
                    </button>
                </div>

                <!--
                    The bar is always visible. The label and the rule checklist
                    used to be two separate lines - "Password strength" as static
                    text, then a "Requirements" toggle underneath it - which was
                    one line of text more than the page needed to say the same
                    thing twice. The label itself is the toggle now: it reads
                    "Password strength: Weak" and so on as you type, and opens
                    the five rules beneath it when pressed.
                -->
                <div class="strength" id="password-strength" aria-live="polite">
                    <div class="strength-bar"><div class="strength-fill"></div></div>

                    <details class="strength-details">
                        <summary class="strength-summary" id="password-rule-summary">
                            Password strength
                        </summary>
                        <ul class="strength-rules">
                            <li data-rule="length"><?= Icon::get('check', 12) ?> 6+ characters</li>
                            <li data-rule="upper"><?= Icon::get('check', 12) ?> Uppercase letter</li>
                            <li data-rule="lower"><?= Icon::get('check', 12) ?> Lowercase letter</li>
                            <li data-rule="number"><?= Icon::get('check', 12) ?> Number</li>
                            <li data-rule="symbol"><?= Icon::get('check', 12) ?> Symbol</li>
                        </ul>
                    </details>
                </div>

                <p class="field-error" id="password-error" hidden></p>
            </div>

            <div class="input-group">
                <label class="field-label" for="confirm">CONFIRM PASSWORD:</label>
                <div class="field-wrap">
                    <input type="password" id="confirm" name="confirm" class="field-input"
                           placeholder="Type here..." autocomplete="new-password"
                           aria-describedby="confirm-error" required>
                    <button type="button" class="password-toggle" data-target="confirm"
                            aria-label="Show password" aria-pressed="false">
                        <span class="icon-show"><?= Icon::get('eye', 18) ?></span>
                        <span class="icon-hide"><?= Icon::get('eye-off', 18) ?></span>
                    </button>
                </div>
                <p class="field-error" id="confirm-error" hidden></p>
            </div>

            <p class="auth-status" id="signup-status" role="alert" aria-live="polite" hidden></p>

            <button type="submit" class="signup-button" id="signup-button">REGISTER</button>
        </form>

        <div class="auth-divider">OR CONTINUE WITH</div>

        <div class="social-container">
            <button type="button" class="social-btn" data-social="Facebook" aria-label="Sign up with Facebook">
                <?= Icon::get('facebook', 22) ?>
            </button>
            <button type="button" class="social-btn" data-social="Google" aria-label="Sign up with Google">
                <?= Icon::get('google', 22) ?>
            </button>
            <button type="button" class="social-btn" data-social="Discord" aria-label="Sign up with Discord">
                <?= Icon::get('discord', 22) ?>
            </button>
        </div>

        <div class="signup-link">
            Already have an account? <a href="<?= BASE_URL ?>/sign-in">SIGN IN</a>
        </div>
    </div>
</div>

<script>
    /*
    * Note the confirm field's id. It was confirmPass in the markup while the
    * script looked up 'confirm', so getElementById returned null and the very
    * first line of the submit handler threw. The button appeared to do nothing
    * at all: no message, no request, no account. The two names now match, and
    * the field ids are the single source both this page and the shared helper
    * read from.
    */
    WASDAuth.init({
        form: 'signup-form',
        button: 'signup-button',
        status: 'signup-status',
        endpoint: '<?= BASE_URL ?>/src/app/api/sign-up/index.php',
        busyLabel: 'CREATING',
        redirect: '<?= BASE_URL ?>/',

        fields: [
            {
                input: 'username',
                emptyMessage: 'Pick a username.',
                validate: value => {
                    const name = value.trim();
                    if (name.length < 3 || name.length > 50) {
                        return 'Usernames are between 3 and 50 characters.';
                    }
                    if (!/^[A-Za-z0-9_-]+$/.test(name)) {
                        return 'Letters, numbers, underscores and hyphens only.';
                    }
                    return null;
                },
            },
            {
                input: 'email',
                emptyMessage: 'Enter an email address.',
                validate: value => WASDAuth.VALID_EMAIL.test(value.trim())
                    ? null
                    : 'That does not look like an email address.',
            },
            {
                input: 'password',
                strength: 'password-strength',
                emptyMessage: 'Choose a password.',
                validate: value => WASDAuth.passwordIsStrong(value)
                    ? null
                    : 'Use 6+ characters with an uppercase letter, a lowercase letter, ' +
                      'a number and a symbol.',
            },
            {
                input: 'confirm',
                emptyMessage: 'Type the password a second time.',
                validate: value => value === document.getElementById('password').value
                    ? null
                    : 'The two passwords do not match.',
            },
        ],

        payload: () => ({
            username: document.getElementById('username').value.trim(),
            email: document.getElementById('email').value.trim(),
            password: document.getElementById('password').value,
            confirm: document.getElementById('confirm').value,
        }),

        onSuccess: () => 'Account created. Taking you in...',
    });

    (() => {
        const status = document.getElementById('signup-status');

        document.querySelectorAll('.social-btn').forEach(button => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';

            button.addEventListener('click', () => {
                WASDAuth.setStatus(status,
                    button.dataset.social + ' sign-up is not connected yet. ' +
                    'Create your account with an email address for now.');
            });
        });
    })();
</script>
