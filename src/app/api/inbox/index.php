<?php
    /**
     * Contact inbox actions.
     *
     * POST { action: 'read' | 'unread' | 'delete', id } -> updates one enquiry
     *
     * Staff only. The page at /inbox checks the same thing before it renders,
     * but that check protects the page, not the data: anybody can post here
     * directly, so the permission has to be enforced again on the way in.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../models/ContactMessages.php';

    Api::begin();

    if (Api::method() !== 'POST') {
        Api::fail('Use POST to update an enquiry.', 405);
    }

    // Checks the session, the CSRF token and the staff flag, in that order.
    Api::requireAdmin();

    $body   = Api::body();
    $action = (string)($body['action'] ?? '');
    $id     = (int)($body['id'] ?? 0);

    $database = Api::database();

    // Handled before the id check, because it is the one action that does not
    // refer to a single enquiry.
    if ($action === 'read-all') {
        $changed = ContactMessages::markAllRead($database);

        Api::json([
            'status'  => 'success',
            'action'  => $action,
            'changed' => $changed,
            'unread'  => ContactMessages::unreadCount($database),
        ]);
    }

    if ($id <= 0) {
        Api::fail('No enquiry was sent.', 422);
    }

    $ok = match ($action) {
        'read'   => ContactMessages::setStatus($id, 'Read', $database),
        'unread' => ContactMessages::setStatus($id, 'New', $database),
        'delete' => ContactMessages::delete($id, $database),
        default  => Api::fail('Unknown inbox action: ' . $action, 422),
    };

    if (!$ok) {
        Api::fail('That enquiry no longer exists.', 404);
    }

    Api::json([
        'status' => 'success',
        'id'     => $id,
        'action' => $action,
        // The badge on the navigation link is recalculated from this rather
        // than being adjusted by one in the browser, so it cannot drift.
        'unread' => ContactMessages::unreadCount($database),
    ]);
?>
