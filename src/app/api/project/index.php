<?php
    ob_start();

    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../lib/Auth.php';
    require_once __DIR__ . '/../../../lib/Media.php';
    require_once __DIR__ . '/../../../lib/Uploads.php';
    require_once __DIR__ . '/../../../models/Games.php';

    function respond(array $payload, int $status = 200): never
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($status);
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($payload);
        exit;
    }

    set_exception_handler(function (Throwable $e) {
        respond(['status' => 'error', 'error' => 'Server error: ' . $e->getMessage()], 500);
    });

    $user = Auth::getCurrentUser();
    if (!$user) {
        respond(['status' => 'error', 'error' => 'Your session has expired. Sign in again.'], 401);
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'DELETE') {
        $body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
        $gameId = (int)($body['game_id'] ?? 0);

        if ($gameId <= 0) {
            respond(['status' => 'error', 'error' => 'No project id was sent.'], 400);
        }

        if (!Games::delete($gameId, $user->getId())) {
            respond(['status' => 'error', 'error' => 'That project does not exist, or it is not yours.'], 403);
        }

        respond(['status' => 'success', 'game_id' => $gameId]);
    }

    if ($method !== 'POST') {
        respond(['status' => 'error', 'error' => 'Use POST to save or DELETE to remove.'], 405);
    }

    if (Uploads::postSizeExceeded()) {
        respond([
            'status' => 'error',
            'error'  => 'The upload was larger than the server accepts, so nothing was saved. '
                      . 'Raise post_max_size (currently ' . ini_get('post_max_size') . ') and '
                      . 'upload_max_filesize (currently ' . ini_get('upload_max_filesize') . ') in php.ini, '
                      . 'then restart Apache.'
        ], 413);
    }

    Uploads::protectUploadRoot();

    $database = new Database();
    $warnings = [];

    $gameId = isset($_POST['game_id']) && $_POST['game_id'] !== '' ? (int)$_POST['game_id'] : null;
    $title  = trim((string)($_POST['title'] ?? ''));

    if ($title === '') {
        respond(['status' => 'error', 'error' => 'The project needs a name.'], 422);
    }

    if ($gameId !== null) {
        $existing = Games::getById($gameId);
        if (!$existing || $existing->getUserId() !== $user->getId()) {
            respond(['status' => 'error', 'error' => 'That project does not exist, or it is not yours.'], 403);
        }
    }

    $isFree = ($_POST['is_free'] ?? '1') === '1';
    $price = $isFree ? 0.00 : max(0, (float)($_POST['price'] ?? 0));
    $discount = $isFree ? 0    : min(100, max(0, (int)($_POST['discount'] ?? 0)));
    $visibility = in_array($_POST['visibility'] ?? '', ['Draft', 'Restricted', 'Public'], true)
                    ? $_POST['visibility'] : 'Draft';
    $fallback = preg_match('/^art-[1-8]$/', $_POST['fallback_art'] ?? '') ? $_POST['fallback_art'] : 'art-1';

    $data = [
        'title' => $title,
        'description' => trim((string)($_POST['description'] ?? '')),
        'developer' => $user->getUsername(),
        'price' => $price,
        'discount' => $discount,
        'visibility' => $visibility,
        'fallback_art' => $fallback,
    ];

    if ($gameId) {
        $database->update('game', $data, ['id' => $gameId, 'user_id' => $user->getId()]);
    } else {
        $data['user_id']      = $user->getId();
        $data['release_date'] = date('Y-m-d');
        $database->insert('game', $data);
        $gameId = $database->lastInsertId();
    }

    Media::gameDirAbsolute($gameId, true);

    if (isset($_FILES['cover_image'])) {
        $result = Uploads::store($_FILES['cover_image'], $gameId, 'cover', Uploads::IMAGE_EXTENSIONS);
        if ($result['ok']) {
            $database->update('game', ['image_path' => $result['stored']], ['id' => $gameId]);
        } else {
            $warnings[] = 'Cover image - ' . $result['error'];
        }
    }

    $screenshotManifest = json_decode((string)($_POST['screenshots_manifest'] ?? '[]'), true) ?: [];
    $keptFiles = [];
    $screenshotRows = [];

    foreach ($screenshotManifest as $entry) {
        if (($entry['kind'] ?? '') === 'existing') {
            $stored = Media::store($entry['path'] ?? '');

            if (!Media::belongsToGame($stored, $gameId) || !is_file(Media::absolute($stored))) continue;

            $screenshotRows[] = $stored;
            $keptFiles[] = $stored;
            continue;
        }

        $field = 'screenshot_file_' . ($entry['fileKey'] ?? '');
        if (!isset($_FILES[$field])) {
            $warnings[] = 'A screenshot never reached the server.';
            continue;
        }

        $result = Uploads::store($_FILES[$field], $gameId, 'ss', Uploads::IMAGE_EXTENSIONS);
        if (!$result['ok']) {
            $warnings[] = ($_FILES[$field]['name'] ?? 'A screenshot') . ' - ' . $result['error'];
            continue;
        }

        $screenshotRows[] = $result['stored'];
        $keptFiles[] = $result['stored'];
    }

    $database->query('DELETE FROM game_screenshot WHERE game_id = ?', [$gameId]);
    foreach ($screenshotRows as $path) {
        $database->insert('game_screenshot', ['game_id' => $gameId, 'image_path' => $path]);
    }

    $buildManifest = json_decode((string)($_POST['builds_manifest'] ?? '[]'), true) ?: [];
    $buildRows = [];

    foreach ($buildManifest as $index => $entry) {
        $displayName = trim((string)($entry['name'] ?? ''));
        $platforms   = trim((string)($entry['platforms'] ?? 'Windows'));
        $isHidden    = !empty($entry['hidden']);

        if ($platforms === '') $platforms = 'Windows';

        if (($entry['kind'] ?? '') === 'existing') {
            $stored = Media::store($entry['path'] ?? '');

            if (!Media::belongsToGame($stored, $gameId)) {
                $warnings[] = ($displayName ?: 'A build') . ' - the stored path was rejected.';
                continue;
            }

            $absolute = Media::absolute($stored);
            if (!is_file($absolute)) {
                $warnings[] = ($displayName ?: 'A build') . ' - the file is missing from disk, so it was dropped.';
                continue;
            }

            $size = Uploads::humanSize((int)filesize($absolute));
        } else {
            $field = 'build_file_' . ($entry['fileKey'] ?? '');

            if (!isset($_FILES[$field])) {
                $warnings[] = ($displayName ?: 'A build') . ' - the file never reached the server.';
                continue;
            }

            $result = Uploads::store($_FILES[$field], $gameId, 'build', Uploads::BUILD_EXTENSIONS);
            if (!$result['ok']) {
                $warnings[] = ($displayName ?: $_FILES[$field]['name'] ?? 'A build') . ' - ' . $result['error'];
                continue;
            }

            $stored = $result['stored'];
            $size   = $result['size'];
            if ($displayName === '') $displayName = (string)$_FILES[$field]['name'];
        }

        $buildRows[] = [
            'game_id' => $gameId,
            'display_name' => $displayName !== '' ? $displayName : basename($stored),
            'file_path' => $stored,
            'file_size' => $size,
            'platforms' => $platforms,
            'is_hidden' => $isHidden ? 1 : 0,
            'sort_order' => $index
        ];

        $keptFiles[] = $stored;
    }

    $previousDownloads = [];
    foreach ($database->query('SELECT file_path, downloads FROM game_build WHERE game_id = ?', [$gameId])->fetchAll() as $row) {
        $previousDownloads[Media::store($row['file_path'])] = (int)$row['downloads'];
    }

    $database->query('DELETE FROM game_build WHERE game_id = ?', [$gameId]);
    foreach ($buildRows as $row) {
        $row['downloads'] = $previousDownloads[$row['file_path']] ?? 0;
        $database->insert('game_build', $row);
    }

    $platformNames = [];
    foreach ($buildRows as $row) {
        foreach (explode(',', $row['platforms']) as $name) {
            $name = trim($name);
            if ($name !== '') $platformNames[$name] = true;
        }
    }

    $database->query('DELETE FROM game_platform WHERE game_id = ?', [$gameId]);
    foreach (array_keys($platformNames) as $name) {
        $found = $database->query('SELECT id FROM platform WHERE name = ? LIMIT 1', [$name])->fetch();
        if ($found) {
            $database->query('INSERT IGNORE INTO game_platform (game_id, platform_id) VALUES (?, ?)',
                             [$gameId, $found['id']]);
        }
    }

    $submittedCategories = is_array($_POST['categories'] ?? null) ? $_POST['categories'] : [];

    $database->query('DELETE FROM game_category WHERE game_id = ?', [$gameId]);
    foreach ($submittedCategories as $categoryId) {
        $categoryId = (int)$categoryId;
        if ($categoryId > 0) {
            $database->query('INSERT IGNORE INTO game_category (game_id, category_id) VALUES (?, ?)',
                             [$gameId, $categoryId]);
        }
    }

    $coverRow = $database->query('SELECT image_path FROM game WHERE id = ?', [$gameId])->fetch();
    if (!empty($coverRow['image_path'])) $keptFiles[] = Media::store($coverRow['image_path']);

    $keptNames = array_map('basename', array_filter($keptFiles));
    $directory = Media::gameDirAbsolute($gameId);

    if (is_dir($directory)) {
        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $name) {
            $path = $directory . '/' . $name;
            if (is_file($path) && !in_array($name, $keptNames, true)) {
                @unlink($path);
            }
        }
    }

    respond([
        'status' => 'success',
        'game_id' => $gameId,
        'builds_saved' => count($buildRows),
        'screenshots_saved' => count($screenshotRows),
        'warnings' => $warnings
    ]);
?>