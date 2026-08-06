<?php
    /**
     * Developer project chunks for the dashboard, rendered as HTML rows.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../lib/View.php';
    require_once __DIR__ . '/../../../models/Games.php';
    require_once __DIR__ . '/../../../models/Icon.php';

    Api::begin(false);

    $user = Api::requireUser();

    $limit  = Api::int('limit', 6, 1, 50);
    $offset = Api::int('offset', 0, 0);

    $userGames = Games::getByUserIdChunk($user->getId(), $limit, $offset);

    if (empty($userGames)) {
        Api::noContent();
    }

    header('Content-Type: text/html; charset=utf-8');

    foreach ($userGames as $game) {
        require __DIR__ . '/../../../components/dashboard-project-card.php';
    }

    exit;
?>
