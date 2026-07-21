<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
require_once __DIR__ . '/../../../config.php'; 
require_once __DIR__ . '/../../../lib/Auth.php';
require_once __DIR__ . '/../../../models/Games.php';
require_once __DIR__ . '/../../../models/Icon.php';

// HANDLE POST (Create Review)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $user = Auth::getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Must be logged in']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $db = new Database();
    $db->insert('review', [
        'user_id' => $user->getId(),
        'game_id' => $data['game_id'],
        'enjoy' => $data['enjoy'] ? 1 : 0,
        'description' => htmlspecialchars($data['description'])
    ]);
    echo json_encode(['status' => 'success']);
    exit;
}

// HANDLE GET (Fetch Chunks)
$gameId = $_GET['game_id'] ?? 0;
$limit = $_GET['limit'] ?? 5;
$offset = $_GET['offset'] ?? 0;

$reviews = Games::getReviewChunk((int)$gameId, (int)$limit, (int)$offset);

if (empty($reviews)) {
    http_response_code(204);
    exit;
}

// Render the chunk components
foreach ($reviews as $review) {
    require __DIR__ . '/../../../components/review-card.php';
}
exit;
?>