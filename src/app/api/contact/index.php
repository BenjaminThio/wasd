<?php
    /**
     * Contact enquiries.
     *
     * POST { name, email, topic, message } -> stores the enquiry
     *
     * Open to guests as well as signed-in users, so this is the one write in
     * the application that does not go through Api::requireUser(). It still
     * carries the CSRF token, and it is rate limited by email address, because
     * "anybody may post here" is otherwise an open invitation.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../models/ContactMessages.php';

    Api::begin();

    if (Api::method() !== 'POST') {
        Api::fail('Use POST to send an enquiry.', 405);
    }

    if (!Csrf::check(Csrf::fromRequest(Api::body()))) {
        Api::fail('Your session token was missing or stale. Reload the page and try again.', 403);
    }

    $body = Api::body();

    $name    = trim((string)($body['name'] ?? ''));
    $email   = trim((string)($body['email'] ?? ''));
    $topic   = trim((string)($body['topic'] ?? 'General'));
    $message = trim((string)($body['message'] ?? ''));

    if ($error = ContactMessages::validate($name, $email, $topic, $message)) {
        Api::fail($error, 422);
    }

    $database = Api::database();

    if (ContactMessages::recentCountFor($email, $database) >= 5) {
        Api::fail('You have sent us several messages already - give us a chance to reply '
                . 'to those first.', 429);
    }

    // Attributed to the account when there is one. The name and email still
    // come from the form: people legitimately ask us to reply somewhere other
    // than the address they registered with.
    $user = Api::optionalUser();

    $id = ContactMessages::create($name, $email, $topic, $message, $user?->getId(), $database);

    Api::json([
        'status'  => 'success',
        'id'      => $id,
        'message' => 'Thanks - your message is with us. We usually reply within two working days.',
    ]);
?>
