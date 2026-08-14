<?php
    /**
     * Sign-up endpoint.
     *
     * POST { username, email, password, confirm }
     *   -> creates the account and signs the new user straight into it,
     *      the same way sign-in does for an existing one.
     *
     * The previous version of this file took a GET request with the password
     * in the query string (which lands in server access logs, browser
     * history, and any proxy in between), validated nothing correctly (the
     * email check only accepted addresses ending in "@gmail.com"; the
     * password check ran password_verify($password, $confirmPass), which
     * expects its second argument to be a bcrypt hash and so always failed
     * against a plaintext confirm field), and never touched the database. No
     * account could ever actually be created through it.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../models/Users.php';

    Api::begin();

    if (Api::method() !== 'POST') {
        Api::fail('Use POST to create an account.', 405);
    }

    // No session user exists yet, so this can't go through Api::requireUser().
    // Same token check, without the "must already be signed in" half.
    if (!Csrf::check(Csrf::fromRequest(Api::body()))) {
        Api::fail('Your session token was missing or stale. Reload the page and try again.', 403);
    }

    $body = Api::body();

    $username = trim((string)($body['username'] ?? ''));
    $email    = trim((string)($body['email'] ?? ''));
    $password = (string)($body['password'] ?? '');
    $confirm  = (string)($body['confirm'] ?? '');

    if ($username === '' || $email === '' || $password === '') {
        Api::fail('Fill in every field before submitting.', 422);
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        Api::fail('Usernames are between 3 and 50 characters.', 422, 'username');
    }

    if (!preg_match('/^[A-Za-z0-9_-]+$/', $username)) {
        Api::fail('Usernames can only contain letters, numbers, underscores and hyphens.', 422, 'username');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191) {
        Api::fail('Enter a valid email address.', 422, 'email');
    }

    if ($password !== $confirm) {
        Api::fail('The two passwords do not match.', 422, 'confirm');
    }

    if (!Users::isStrongPassword($password)) {
        Api::fail('Use at least 6 characters with an uppercase letter, a lowercase '
                . 'letter, a number and a symbol.', 422, 'password');
    }

    // Friendly pre-checks. The UNIQUE constraints on the table are the real
    // guarantee; these just mean the common case gets a clear message instead
    // of a generic one.
    if (Users::usernameTaken($username)) {
        Api::fail('That username is already taken.', 409, 'username');
    }

    if (Users::emailTaken($email)) {
        Api::fail('An account with that email already exists.', 409, 'email');
    }

    try {
        $user = Auth::register($username, $email, $password);
    } catch (PDOException $e) {
        // The checks above should already have caught this - only a genuine
        // race (two signups for the same name landing at once) reaches here.
        if ($e->getCode() === '23000') {
            Api::fail('That username or email was just taken. Try again.', 409);
        }
        throw $e;
    }

    Api::json([
        'status' => 'success',
        'user' => [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ],
    ]);
?>