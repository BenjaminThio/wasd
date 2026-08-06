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
        /*
         * Never hand the exception text to the browser. A PDOException carries
         * the failing SQL, table and column names, and sometimes the DSN - a
         * free map of the database for anyone who can make a request fail.
         *
         * The full detail goes to the error log. The developer running the site
         * can opt back in with APP_DEBUG=true in .env.
         */
        error_log('WASD project API: ' . $e::class . ': ' . $e->getMessage()
                  . ' in ' . $e->getFile() . ':' . $e->getLine());

        respond([
            'status' => 'error',
            'error'  => defined('APP_DEBUG') && APP_DEBUG
                ? 'Server error: ' . $e->getMessage()
                : 'Something went wrong saving the project. Please try again.'
        ], 500);
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
        // The limit itself is useful to whoever is uploading; how the server is
        // configured to enforce it is not their business, and naming php.ini
        // directives to every account holder only helps someone mapping the
        // stack. That half goes to the log.
        error_log('WASD: upload rejected, request exceeded post_max_size ('
                  . ini_get('post_max_size') . ') / upload_max_filesize ('
                  . ini_get('upload_max_filesize') . ')');

        respond([
            'status' => 'error',
            'error'  => 'That upload was too large, so nothing was saved. The maximum is '
                      . ini_get('upload_max_filesize') . ' per file.'
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

    // Only one build can be the browser-playable one.
    $playableClaimed = false;

    foreach ($buildManifest as $index => $entry) {
        $displayName = trim((string)($entry['name'] ?? ''));
        $platforms   = trim((string)($entry['platforms'] ?? 'Windows'));
        $isHidden    = !empty($entry['hidden']);
        $wantsPlay   = !empty($entry['playable']) && !$playableClaimed;

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

        // A web build must be a zip - there is nothing to unpack otherwise.
        if ($wantsPlay && strtolower(pathinfo($stored, PATHINFO_EXTENSION)) !== 'zip') {
            $warnings[] = ($displayName ?: 'A build') . ' - only a .zip can be played in the browser.';
            $wantsPlay = false;
        }

        if ($wantsPlay) $playableClaimed = true;

        $buildRows[] = [
            'game_id' => $gameId,
            'display_name' => $displayName !== '' ? $displayName : basename($stored),
            'file_path' => $stored,
            'file_size' => $size,
            'platforms' => $platforms,
            'is_hidden' => $isHidden ? 1 : 0,
            'sort_order' => $index,
            'is_playable' => $wantsPlay ? 1 : 0,
        ];

        $keptFiles[] = $stored;
    }

    // Carry over the counters and the already-unpacked web build, both of which
    // are keyed by the stored file path rather than the row id.
    $previous = [];
    foreach ($database->query(
        'SELECT id, file_path, downloads, is_playable, play_path FROM game_build WHERE game_id = ?',
        [$gameId]
    )->fetchAll() as $row) {
        $previous[Media::store($row['file_path'])] = $row;
    }

    $database->query('DELETE FROM game_build WHERE game_id = ?', [$gameId]);

    $playFoldersInUse = [];

    foreach ($buildRows as $row) {
        $before = $previous[$row['file_path']] ?? null;

        $row['downloads'] = (int)($before['downloads'] ?? 0);
        $row['play_path'] = null;

        $database->insert('game_build', $row);
        $buildId = $database->lastInsertId();

        if (!$row['is_playable']) continue;

        // Re-use the existing unpack when the same archive is still ticked,
        // so saving a project does not re-extract a 300 MB build every time.
        $alreadyPlayable = $before
            && (int)$before['is_playable'] === 1
            && !empty($before['play_path'])
            && is_file(Media::absolute($before['play_path']));

        if ($alreadyPlayable) {
            // The folder is named after the old row id; keep pointing at it.
            $database->update('game_build', ['play_path' => $before['play_path']], ['id' => $buildId]);

            // playRoot, not dirname: an engine that zips its export inside a
            // folder puts the entry several levels below the play folder.
            $existingFolder = Uploads::playRoot($before['play_path']);

            if ($existingFolder !== null) {
                $playFoldersInUse[] = $existingFolder;

                // Refresh its .htaccess even though the files are reused, so a
                // folder unpacked by an older version picks up current headers.
                Uploads::protectPlayDirectory(Media::root() . '/' . $existingFolder);
            }
            continue;
        }

        $result = Uploads::extractWebBuild(
            Media::absolute($row['file_path']),
            Uploads::playDir($gameId, $buildId)
        );

        if (!$result['ok']) {
            $warnings[] = $row['display_name'] . ' - ' . $result['error'];
            $database->update('game_build', ['is_playable' => 0], ['id' => $buildId]);
            continue;
        }

        if ($result['error'] !== '') $warnings[] = $row['display_name'] . ' - ' . $result['error'];

        $database->update('game_build', ['play_path' => $result['entry']], ['id' => $buildId]);
        $playFoldersInUse[] = Uploads::playDir($gameId, $buildId);

        // Operator-facing, deliberately not shown in the UI: a note about the
        // site's own configuration is useless to the developer uploading a
        // game - they cannot act on it - and telling every account holder that
        // builds share our origin only helps anyone looking for a way in.
        if (!Media::playOriginConfigured()) {
            error_log(
                'WASD: game ' . $gameId . ' has a browser-playable build while PLAY_ORIGIN is unset, '
                . 'so it runs on this site\'s own origin. Set PLAY_ORIGIN in .env to serve builds '
                . 'from a separate hostname.'
            );
        }
    }

    // Sweep play folders that no longer belong to a build.
    $gameDirectory = Media::gameDirAbsolute($gameId);
    if (is_dir($gameDirectory)) {
        foreach (array_diff(scandir($gameDirectory) ?: [], ['.', '..']) as $name) {
            if (!str_starts_with($name, 'play_') || !is_dir($gameDirectory . '/' . $name)) continue;

            if (!in_array(Media::gameDir($gameId) . '/' . $name, $playFoldersInUse, true)) {
                Uploads::deleteDirectory($gameDirectory . '/' . $name);
            }
        }
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