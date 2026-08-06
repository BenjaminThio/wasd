<?php
    /**
     * Build download endpoint.
     *
     * GET  ?build=<id>  -> streams the file as an attachment
     *
     * A build may only leave the server when the player is entitled to it:
     * the game is free, or they bought it, or they are the developer who
     * uploaded it. Hidden builds are refused for everyone but the developer.
     *
     * public/uploads/.htaccess blocks direct hits on build files, so this is
     * the only way to get one.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../lib/Media.php';
    require_once __DIR__ . '/../../../models/Games.php';
    require_once __DIR__ . '/../../../models/Library.php';

    Api::begin(false);

    $buildId = Api::int('build', 0, 0);

    if ($buildId <= 0) {
        header('Content-Type: application/json');
        Api::fail('No build was requested.', 400);
    }

    $database = Api::database();
    $user = Api::optionalUser();

    $build = $database->query(
        'SELECT b.*, g.id AS game_id, g.user_id AS owner_id, g.price, g.title
         FROM game_build b
         INNER JOIN game g ON g.id = b.game_id
         WHERE b.id = ?',
        [$buildId]
    )->fetch();

    if (!$build) {
        header('Content-Type: application/json');
        Api::fail('That build does not exist.', 404);
    }

    $gameId     = (int)$build['game_id'];
    $isDeveloper = $user !== null && (int)$build['owner_id'] === $user->getId();

    if ((int)$build['is_hidden'] === 1 && !$isDeveloper) {
        header('Content-Type: application/json');
        Api::fail('That build is not available.', 403);
    }

    if (!Library::owns($user?->getId(), $gameId, $database)) {
        header('Content-Type: application/json');
        Api::fail('You need to buy this game before you can download it.', 403);
    }

    // Never trust the stored path: it has to point inside this game's folder.
    $stored = Media::store($build['file_path']);

    if (!Media::belongsToGame($stored, $gameId)) {
        header('Content-Type: application/json');
        Api::fail('That build is stored in an unexpected place and was blocked.', 403);
    }

    $absolute = Media::absolute($stored);

    if (!is_file($absolute)) {
        header('Content-Type: application/json');
        Api::fail('The file is missing from the server.', 410);
    }

    Games::recordDownload($gameId, $buildId, $database);

    $downloadName = $build['display_name'] !== ''
        ? $build['display_name']
        : basename($absolute);

    // Keep the real extension even when the developer renamed the build.
    $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
    if ($extension !== '' && !str_ends_with(strtolower($downloadName), '.' . $extension)) {
        $downloadName .= '.' . $extension;
    }

    $downloadName = preg_replace('/[^\w\-. ]+/u', '_', $downloadName);

    while (ob_get_level() > 0) ob_end_clean();

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($absolute));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');

    readfile($absolute);
    exit;
?>
