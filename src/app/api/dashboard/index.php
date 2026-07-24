<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../lib/Auth.php';
require_once __DIR__ . '/../../../models/Games.php';
require_once __DIR__ . '/../../../models/Icon.php';

$user = Auth::getCurrentUser();
if (!$user) {
    http_response_code(401);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$userGames = Games::getByUserIdChunk($user->getId(), $limit, $offset);

if (empty($userGames)) {
    http_response_code(204);
    exit;
}

foreach ($userGames as $game) {
    require __DIR__ . '/../../../components/dashboard-project-card.php';
}
exit;
?>