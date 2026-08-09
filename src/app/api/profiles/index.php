<?php
    /**
     * Profile endpoint.
     *
     * GET                      -> the signed-in user plus their activity counters
     * POST (multipart or JSON) -> update username, avatar and password
     *
     * The previous version of this file took whatever JSON it was given and
     * echoed it straight back - including the plain-text password - without
     * touching the database, so "Save" never actually saved anything.
     *
     * Sign in and sign up are deliberately untouched; this only edits the row
     * of the user who is already authenticated.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../lib/Media.php';
    require_once __DIR__ . '/../../../lib/Uploads.php';
    require_once __DIR__ . '/../../../models/Users.php';

    Api::begin();

    $user = Api::requireUser();
    $userId = $user->getId();
    $database = Api::database();

    /* ----------------------------------------------------------------- read */

    if (Api::method() === 'GET') {
        Api::json([
            'status' => 'ok',
            'user' => [
                'id' => $userId,
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'avatar' => Media::url($user->getAvatarPath()),
            ],
            'stats' => profileStats($database, $userId),
        ]);
    }

    if (Api::method() !== 'POST') {
        Api::fail('Use GET to read your profile or POST to update it.', 405);
    }

    /* ---------------------------------------------------------------- write */

    if (Uploads::postSizeExceeded()) {
        Api::fail('That avatar was larger than the server accepts (post_max_size = '
                . ini_get('post_max_size') . '), so nothing was saved.', 413);
    }

    // The form posts multipart when an avatar is attached and JSON otherwise.
    $input = !empty($_POST) ? $_POST : Api::body();

    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $confirm  = (string)($input['confirm'] ?? '');
    $current  = (string)($input['current_password'] ?? '');

    $changes = [];
    $notes   = [];

    /* Username ------------------------------------------------------------ */

    if ($username !== '' && $username !== $user->getUsername()) {
        if (strlen($username) < 3 || strlen($username) > 50) {
            Api::fail('Usernames are between 3 and 50 characters.', 422);
        }

        $taken = $database->query(
            'SELECT 1 FROM user WHERE username = ? AND id <> ? LIMIT 1',
            [$username, $userId]
        )->fetch();

        if ($taken) {
            Api::fail('That username is already taken.', 409);
        }

        $changes['username'] = $username;
        $notes[] = 'username';
    }

    /* Password ------------------------------------------------------------ */

    if ($password !== '') {
        if ($password !== $confirm) {
            Api::fail('The two passwords do not match.', 422);
        }

        if (!Users::isStrongPassword($password)) {
            Api::fail('Use at least 6 characters with an uppercase letter, a lowercase '
                    . 'letter, a number and a symbol.', 422);
        }

        // Knowing the current password is what stops a borrowed session from
        // taking the account over.
        if ($current === '' || !password_verify($current, $user->getPassword())) {
            Api::fail('Your current password is not correct.', 403);
        }

        $changes['password'] = password_hash($password, PASSWORD_DEFAULT);
        $notes[] = 'password';
    }

    /* Avatar -------------------------------------------------------------- */

    if (isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $result = Uploads::storeIn($_FILES['avatar'], 'public/uploads/avatars', 'avatar', Uploads::IMAGE_EXTENSIONS);

        if (!$result['ok']) {
            Api::fail('Avatar - ' . $result['error'], 422);
        }

        // Replace, never accumulate: drop the previous file once the new one landed.
        $previous = $user->getAvatarPath();
        if ($previous && str_starts_with(Media::store($previous), 'public/uploads/avatars/')) {
            @unlink(Media::absolute($previous));
        }

        $changes['avatar_path'] = $result['stored'];
        $notes[] = 'avatar';
    }

    if (empty($changes)) {
        Api::json(['status' => 'ok', 'changed' => [], 'message' => 'Nothing to update.']);
    }

    $database->update('user', $changes, ['id' => $userId]);

    $fresh = Users::getById($userId);

    Api::json([
        'status' => 'ok',
        'changed' => $notes,
        'user' => [
            'id' => $userId,
            'username' => $fresh?->getUsername() ?? $username,
            'email' => $user->getEmail(),
            'avatar' => Media::url($fresh?->getAvatarPath()),
        ],
    ]);

    /* ------------------------------------------------------------- helpers */

    function profileStats(Database $database, int $userId): array
    {
        $row = $database->query(
            'SELECT
                (SELECT COUNT(*) FROM game WHERE user_id = ?) AS projects,
                (SELECT COUNT(*) FROM library WHERE user_id = ?) AS owned,
                (SELECT COUNT(*) FROM wishlist WHERE user_id = ?) AS wishlist,
                (SELECT COUNT(*) FROM cart WHERE user_id = ?) AS cart,
                (SELECT COUNT(*) FROM review WHERE user_id = ?) AS reviews',
            [$userId, $userId, $userId, $userId, $userId]
        )->fetch();

        return array_map('intval', $row ?: []);
    }
?>
