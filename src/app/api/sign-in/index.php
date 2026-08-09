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
    require_once __DIR__ . '/../../../models/Users.php';

    Api::begin();

    if (Api::method() !== 'POST') {
        Api::fail('Use POST to sign in.', 405);
    }

    if (!Csrf::check(Csrf::fromRequest(Api::body()))) {
        Api::fail('Your session token was missing or stale. Reload the page and try again.', 403);
    }

    $body = Api::body();

    $email    = trim((string)($body['email'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($email === '' || $password === '') {
        Api::fail('Enter your email and password.', 422);
    }

    $user = Users::getByEmail($email);

    // One message for "no such account" and "wrong password" alike - telling
    // them apart is exactly what lets an attacker enumerate real accounts by
    // trying addresses and watching which error comes back.
    if (!$user || !password_verify($password, $user->getPassword())) {
        Api::fail('Incorrect email or password.', 401);
    }

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
