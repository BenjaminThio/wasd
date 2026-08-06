<?php
    /**
     * Ends the session and returns to the landing page.
     *
     * The link that points here carries data-no-spa, so the browser performs a
     * real navigation and the redirect below is followed normally instead of
     * being fetched as JSON by the router.
     */
    require_once __DIR__ . '/../../lib/Auth.php';

    Auth::logout();

    header('Location: ' . BASE_URL . '/');
    exit;
?>
