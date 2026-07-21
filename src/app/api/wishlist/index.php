<?php
require_once __DIR__ . '/../../../config.php'; 
require_once __DIR__ . '/../../../lib/Auth.php';
require_once __DIR__ . '/../../../models/Users.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

/*
$user = Auth::getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Must be logged in']);
    exit;
}
*/

$data = json_decode(file_get_contents('php://input'), true);
Users::addToWishlist($user->getId(), $data['game_id']);
echo json_encode(['status' => 'success']);
exit;
?>