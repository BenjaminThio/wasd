<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/Media.php';
    require_once __DIR__ . '/../../lib/View.php';
    require_once __DIR__ . '/../../models/Icon.php';

    $user = Auth::getCurrentUser();

    if (!$user) {
        echo '<div class="page"><div class="empty-state">'
           . 'Sign in to manage your profile.<br>'
           . '<a href="' . BASE_URL . '/sign-in">Sign in</a></div></div>';
        return;
    }

    $database = new Database();
    $userId = $user->getId();

    $stats = $database->query(
        'SELECT
            (SELECT COUNT(*) FROM game WHERE user_id = ?) AS projects,
            (SELECT COUNT(*) FROM library WHERE user_id = ?) AS owned,
            (SELECT COUNT(*) FROM wishlist WHERE user_id = ?) AS wishlist,
            (SELECT COUNT(*) FROM review WHERE user_id = ?) AS reviews',
        [$userId, $userId, $userId, $userId]
    )->fetch();

    $avatarUrl = Media::url($user->getAvatarPath());
?>

<div class="page profile-page">
    <header class="page-head reveal">
        <div class="flex-col gap-2">
            <span class="eyebrow">Your account</span>
            <h1 class="page-title">Profile</h1>
        </div>
        <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>/dashboard">
            <?= Icon::get('grid', 15) ?> Developer dashboard
        </a>
    </header>

    <div class="profile-layout">

        <!-- Identity ------------------------------------------------------- -->
        <aside class="profile-identity card reveal">
            <label class="avatar-drop" for="avatar-input" title="Change your avatar">
                <span class="avatar-frame media <?= $avatarUrl === '' ? 'is-ready' : '' ?>" id="avatar-frame">
                    <?php if ($avatarUrl !== ''): ?>
                        <img class="img-lazy" id="avatar-image"
                             src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES) ?>" alt="Your avatar">
                    <?php else: ?>
                        <span class="avatar-initial" id="avatar-initial">
                            <?= strtoupper(htmlspecialchars(substr($user->getUsername(), 0, 1))) ?>
                        </span>
                    <?php endif; ?>
                </span>
                <span class="avatar-hint"><?= Icon::get('camera', 15) ?> Change photo</span>
            </label>
            <input type="file" id="avatar-input" accept="image/*" hidden>

            <!-- A picked photo is a preview until Save changes is pressed, so
                 one button still means one save. -->
            <p class="avatar-pending" id="avatar-pending" hidden>
                <span id="avatar-pending-name">New photo selected</span>
                <button type="button" class="avatar-undo" onclick="discardAvatar()">Undo</button>
            </p>

            <div class="profile-identity-text">
                <h2 class="profile-name" id="profile-name"><?= htmlspecialchars($user->getUsername()) ?></h2>
                <p class="profile-email"><?= htmlspecialchars($user->getEmail()) ?></p>
            </div>

            <div class="profile-stats">
                <a class="profile-stat" href="<?= BASE_URL ?>/library">
                    <span class="profile-stat-value"><?= (int)$stats['owned'] ?></span>
                    <span class="profile-stat-label">Games owned</span>
                </a>
                <a class="profile-stat" href="<?= BASE_URL ?>/wishlist">
                    <span class="profile-stat-value"><?= (int)$stats['wishlist'] ?></span>
                    <span class="profile-stat-label">Wishlisted</span>
                </a>
                <a class="profile-stat" href="<?= BASE_URL ?>/dashboard">
                    <span class="profile-stat-value"><?= (int)$stats['projects'] ?></span>
                    <span class="profile-stat-label">Projects</span>
                </a>
                <div class="profile-stat">
                    <span class="profile-stat-value"><?= (int)$stats['reviews'] ?></span>
                    <span class="profile-stat-label">Reviews</span>
                </div>
            </div>
        </aside>

        <!-- Settings ------------------------------------------------------- -->
        <div class="profile-forms">
            <section class="card reveal">
                <div class="profile-section-head">
                    <h2 class="section-title">Account details</h2>
                    <p class="text-muted text-body text-sm">
                        Your display name is what other players and developers see.
                    </p>
                </div>

                <div class="field">
                    <label class="field-label" for="username">Username</label>
                    <input type="text" id="username" class="field-input"
                           value="<?= htmlspecialchars($user->getUsername()) ?>"
                           autocomplete="username" maxlength="50">
                </div>

                <div class="field">
                    <label class="field-label" for="email">Email address</label>
                    <input type="email" id="email" class="field-input"
                           value="<?= htmlspecialchars($user->getEmail()) ?>" disabled
                           autocomplete="email">
                    <p class="field-hint">Contact support if you need your email changed.</p>
                </div>

                <button type="button" class="btn btn-primary" id="save-profile" onclick="saveProfile()">
                    Save changes
                </button>
            </section>

            <section class="card reveal">
                <div class="profile-section-head">
                    <h2 class="section-title">Password</h2>
                    <p class="text-muted text-body text-sm">
                        Leave these blank to keep your current password.
                    </p>
                </div>

                <div class="field">
                    <label class="field-label" for="current-password">Current password</label>
                    <input type="password" id="current-password" class="field-input"
                           placeholder="••••••••" autocomplete="current-password">
                </div>

                <div class="field">
                    <label class="field-label" for="password">New password</label>
                    <input type="password" id="password" class="field-input"
                           placeholder="••••••••" autocomplete="new-password">
                    <p class="field-hint" id="password-hint">
                        At least 6 characters with an uppercase letter, a lowercase letter,
                        a number and a symbol.
                    </p>
                </div>

                <div class="field">
                    <label class="field-label" for="confirm">Confirm new password</label>
                    <input type="password" id="confirm" class="field-input"
                           placeholder="••••••••" autocomplete="new-password">
                    <p class="field-error" id="match-error" hidden>The two passwords do not match.</p>
                </div>

                <button type="button" class="btn btn-primary" onclick="savePassword()">
                    Update password
                </button>
            </section>
        </div>
    </div>
</div>

<script>
(() => {
    const API = '/src/app/api/profiles/index.php';

    const usernameInput = document.getElementById('username');
    const currentInput = document.getElementById('current-password');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm');
    const matchError = document.getElementById('match-error');
    const passwordHint = document.getElementById('password-hint');
    const avatarInput = document.getElementById('avatar-input');

    const STRONG = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/;

    // A photo chosen in the picker is held here until the form is saved.
    let pendingAvatar = null;
    let pendingPreviewUrl = null;

    function applyUser(user) {
        if (!user) return;
        document.getElementById('profile-name').textContent = user.username;
        usernameInput.value = user.username;

        // Repoint the picture at the file the server kept. This has to happen
        // before the local preview URL is revoked, or the <img> would be left
        // pointing at a blob that no longer exists.
        const picture = document.getElementById('avatar-image');
        if (picture && user.avatar) {
            picture.src = user.avatar;
            savedFrame = frame.innerHTML;
        }

        // The header is rendered once per full page load and sits outside the
        // injection zone, so it is told about the new picture directly instead
        // of waiting for a refresh.
        window.wasdSetAvatar?.(user.avatar, user.username);
    }

    /* Account details ---------------------------------------------------- */

    window.saveProfile = async function () {
        const username = usernameInput.value.trim();

        if (username.length < 3) {
            usernameInput.classList.add('is-invalid');
            WASD.toast('Usernames need at least 3 characters.', 'error');
            return;
        }

        usernameInput.classList.remove('is-invalid');

        const button = document.getElementById('save-profile');
        button.disabled = true;
        button.textContent = 'Saving…';

        // One request carries whatever changed: the name, the photo, or both.
        // Multipart when a photo is waiting, plain JSON otherwise.
        let result;

        if (pendingAvatar) {
            const form = new FormData();
            form.append('username', username);
            form.append('avatar', pendingAvatar);
            result = await WASD.api(API, { method: 'POST', body: form });
        } else {
            result = await WASD.api(API, { json: { username } });
        }

        button.disabled = false;
        button.textContent = 'Save changes';

        if (!result.ok) {
            WASD.toast((result.data && result.data.error) || 'Could not save that.', 'error');
            return;
        }

        applyUser(result.data.user);
        clearPendingAvatar();

        WASD.toast(
            result.data.changed.length ? 'Profile updated.' : 'Nothing to update.',
            result.data.changed.length ? 'success' : 'info'
        );
    };

    /* Password ------------------------------------------------------------ */

    confirmInput.addEventListener('input', () => {
        const mismatch = confirmInput.value !== '' && confirmInput.value !== passwordInput.value;
        matchError.hidden = !mismatch;
        confirmInput.classList.toggle('is-invalid', mismatch);
    });

    passwordInput.addEventListener('input', () => {
        const weak = passwordInput.value !== '' && !STRONG.test(passwordInput.value);
        passwordInput.classList.toggle('is-invalid', weak);
        passwordHint.classList.toggle('is-warning', weak);
    });

    window.savePassword = async function () {
        if (passwordInput.value === '') {
            WASD.toast('Type a new password first.', 'error');
            return;
        }

        if (!STRONG.test(passwordInput.value)) {
            passwordInput.classList.add('is-invalid');
            WASD.toast('That password is not strong enough.', 'error');
            return;
        }

        if (passwordInput.value !== confirmInput.value) {
            matchError.hidden = false;
            confirmInput.classList.add('is-invalid');
            return;
        }

        const result = await WASD.api(API, {
            json: {
                password: passwordInput.value,
                confirm: confirmInput.value,
                current_password: currentInput.value,
            }
        });

        if (!result.ok) {
            WASD.toast((result.data && result.data.error) || 'Could not change your password.', 'error');
            return;
        }

        [currentInput, passwordInput, confirmInput].forEach(field => {
            field.value = '';
            field.classList.remove('is-invalid');
        });
        matchError.hidden = true;

        WASD.toast('Password updated.', 'success');
    };

    /* Avatar --------------------------------------------------------------- */

    const frame = document.getElementById('avatar-frame');
    const pendingRow = document.getElementById('avatar-pending');

    // What "Undo" goes back to: the picture that is actually stored right now.
    let savedFrame = frame.innerHTML;

    function showPreview(url) {
        frame.innerHTML = `<img class="img-lazy is-loaded" id="avatar-image" src="${url}" alt="Your avatar">`;
        frame.classList.add('is-ready');
    }

    function clearPendingAvatar() {
        if (pendingPreviewUrl) {
            URL.revokeObjectURL(pendingPreviewUrl);
            pendingPreviewUrl = null;
        }
        pendingAvatar = null;
        avatarInput.value = '';
        pendingRow.hidden = true;
        frame.classList.remove('is-pending');
    }

    /** Undo puts the picture that is actually stored back on screen. */
    window.discardAvatar = function (event) {
        event?.preventDefault();
        clearPendingAvatar();
        frame.innerHTML = savedFrame;
        WASD.lazyImages(frame);
    };

    avatarInput.addEventListener('change', () => {
        const file = avatarInput.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            WASD.toast('Pick an image file.', 'error');
            avatarInput.value = '';
            return;
        }

        if (pendingPreviewUrl) URL.revokeObjectURL(pendingPreviewUrl);

        pendingAvatar = file;
        pendingPreviewUrl = URL.createObjectURL(file);

        showPreview(pendingPreviewUrl);
        frame.classList.add('is-pending');

        document.getElementById('avatar-pending-name').textContent = file.name;
        pendingRow.hidden = false;
    });
})();
</script>
