<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../lib/Auth.php';
require_once __DIR__ . '/../../../models/Games.php';

header('Content-Type: application/json');

$user = Auth::getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please sign in.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $gameId = (int)($data['game_id'] ?? 0);

    if ($gameId && Games::delete($gameId, $user->getId())) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to delete project']);
    }
    exit;
}

if ($method === 'POST') {
    $database = new Database();

    $gameId = isset($_POST['game_id']) && $_POST['game_id'] !== '' ? (int)$_POST['game_id'] : null;
    $title = trim($_POST['title'] ?? 'Untitled Game');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0.0);
    $discount = (int)($_POST['discount'] ?? 0);
    $visibility = $_POST['visibility'] ?? 'Draft';
    $fallbackArt = $_POST['fallback_art'] ?? 'art-1';
    $developer = $user->getUsername();

    $data = [
        'user_id' => $user->getId(),
        'title' => $title,
        'description' => $description,
        'developer' => $developer,
        'price' => $price,
        'discount' => $discount,
        'visibility' => $visibility,
        'fallback_art' => $fallbackArt,
        'release_date' => date('Y-m-d')
    ];

    if ($gameId) {
        $database->update('game', $data, ['id' => $gameId, 'user_id' => $user->getId()]);
    } else {
        $database->insert('game', $data);
        $gameId = (int)$database->query('SELECT LAST_INSERT_ID()')->fetchColumn();
    }

    // Base storage path: public/uploads/games/{gameId}/
    $targetDir = __DIR__ . '/../../../../public/uploads/games/' . $gameId . '/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Cover Image Upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'cover_' . time() . '.' . $ext;
        $dest = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
            $publicUrl = BASE_URL . '/public/uploads/games/' . $gameId . '/' . $fileName;
            $database->update('game', ['image_path' => $publicUrl], ['id' => $gameId]);
        }
    }

    // Game Build Upload (.zip)
    if (isset($_FILES['build_file']) && $_FILES['build_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['build_file']['name'], PATHINFO_EXTENSION);
        $fileName = 'build_' . time() . '.' . $ext;
        $dest = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['build_file']['tmp_name'], $dest)) {
            $publicUrl = BASE_URL . '/public/uploads/games/' . $gameId . '/' . $fileName;
            $database->update('game', ['build_path' => $publicUrl], ['id' => $gameId]);
        }
    }

    // Multiple Screenshots Upload
    if (isset($_FILES['screenshots'])) {
        foreach ($_FILES['screenshots']['tmp_name'] as $idx => $tmpName) {
            if ($_FILES['screenshots']['error'][$idx] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['screenshots']['name'][$idx], PATHINFO_EXTENSION);
                $fileName = 'ss_' . time() . '_' . $idx . '.' . $ext;
                $dest = $targetDir . $fileName;
                if (move_uploaded_file($tmpName, $dest)) {
                    $publicUrl = BASE_URL . '/public/uploads/games/' . $gameId . '/' . $fileName;
                    $database->insert('game_screenshot', [
                        'game_id' => $gameId,
                        'image_path' => $publicUrl
                    ]);
                }
            }
        }
    }

    echo json_encode(['status' => 'success', 'game_id' => $gameId]);
    exit;
}
?>