<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/../../../config.php'; 
require_once __DIR__ . '/../../../lib/Env.php'; 
require_once __DIR__ . '/../../../models/Games.php';
require_once __DIR__ . '/../../../models/Icon.php';
require_once __DIR__ . '/../../../models/Category.php';
require_once __DIR__ . '/../../../lib/utils.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
// Read the limit from the URL, default to 12 if missing, cap at 50 for security
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 12;

// Get the next chunk from the database
$games = Games::getChunk($limit, $offset);

// If the array is empty, tell JS to stop asking (HTTP 204)
if (empty($games)) {
    http_response_code(204);
    exit;
}

// Generate the HTML by reusing your exact same component
foreach ($games as $game) {
    require __DIR__ . '/../../../components/game-card.php';
}

exit; 
?>