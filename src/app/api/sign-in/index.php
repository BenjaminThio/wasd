<?php
    /**
     * Sign-in endpoint.
     *
     * POST { email, password } -> starts the session for an existing account.
     *
     * The previous version of this file took a GET request with the password
     * in the query string, validated only that the email ended in
     * "@gmail.com", and never queried the `user` table or called
     * Auth::login(). No sign-in could ever actually succeed - Auth::login()
     * existed and worked, but nothing in this file called it.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../lib/Throttle.php';
    require_once __DIR__ . '/../../../models/Users.php';

    Api::begin();

    // Five wrong passwords in fifteen minutes and the form stops answering.
    // Bcrypt already makes each guess slow to verify; this puts a ceiling on
    // how many an attacker gets to make at all.
    const SIGN_IN_LIMIT  = 5;
    const SIGN_IN_WINDOW = 900;

    if (Api::method() !== 'POST') {
        Api::fail('Use POST to sign in.', 405);
    }

    if (!Csrf::check(Csrf::fromRequest(Api::body()))) {
        Api::fail('Your session token was missing or stale. Reload the page and try again.', 403);
    }

    if (Throttle::isBlocked('sign-in', SIGN_IN_LIMIT, SIGN_IN_WINDOW)) {
        $wait = (int)ceil(Throttle::retryAfter('sign-in', SIGN_IN_WINDOW) / 60);

        Api::fail('Too many failed attempts. Try again in '
                . $wait . ' minute' . ($wait === 1 ? '' : 's') . '.', 429);
    }

    $body = Api::body();

    $email    = trim((string)($body['email'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($email === '') {
        Api::fail('Enter the email address you registered with.', 422, 'email');
    }

    if ($password === '') {
        Api::fail('Enter your password.', 422, 'password');
    }

    $user = Users::getByEmail($email);

    // One message for "no such account" and "wrong password" alike - telling
    // them apart is exactly what lets an attacker enumerate real accounts by
    // trying addresses and watching which error comes back. For the same
    // reason the message is not attached to either field.
    if (!$user || !password_verify($password, $user->getPassword())) {
        Throttle::record('sign-in');

        Api::fail('Incorrect email or password.', 401);
    }

    // A correct password wipes the count, so somebody who mistyped twice and
    // then got it right does not carry those two attempts around with them.
    Throttle::clear('sign-in');

    Auth::login($user);

    Api::json([
        'status' => 'success',
        'user' => [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ],
    ]);
?>
