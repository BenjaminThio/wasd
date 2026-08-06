<?php
    /**
     * Cart endpoint.
     *
     * GET  ?offset&limit          -> { items, totals }
     * POST { action, game_id }    -> add | remove | move-to-wishlist | checkout
     *
     * Previously this file read $_SESSION['user_id'] without ever starting the
     * session and fell back to `?? 1`, so every visitor - signed in or not -
     * shared user 1's cart. It now requires a real session.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../lib/Media.php';
    require_once __DIR__ . '/../../../lib/View.php';
    require_once __DIR__ . '/../../../models/Library.php';

    Api::begin();

    $user = Api::requireUser();
    $userId = $user->getId();
    $database = Api::database();

    function cartTotals(Database $database, int $userId): array
    {
        $row = $database->query(
            'SELECT COUNT(*) AS items,
                    COALESCE(SUM(g.price), 0) AS price,
                    COALESCE(SUM(g.price * g.discount / 100), 0) AS discount
             FROM cart c
             INNER JOIN game g ON g.id = c.game_id
             WHERE c.user_id = ?',
            [$userId]
        )->fetch();

        $price = (float)$row['price'];
        $discount = (float)$row['discount'];

        return [
            'items'    => (int)$row['items'],
            'price'    => round($price, 2),
            'discount' => round($discount, 2),
            'subtotal' => round($price - $discount, 2)
        ];
    }

    /* --------------------------------------------------------------- write */

    if (Api::method() === 'POST') {
        $input  = Api::body();
        $action = $input['action'] ?? 'add';
        $gameId = (int)($input['game_id'] ?? 0);

        switch ($action) {
            case 'add':
                if ($gameId <= 0) Api::fail('No game was sent.', 422);

                // Owning it already (or it being free) makes a cart row pointless.
                if (Library::owns($userId, $gameId, $database)) {
                    Api::json(['status' => 'owned', 'message' => 'You already have this game.']);
                }

                $database->query(
                    'INSERT IGNORE INTO cart (user_id, game_id) VALUES (?, ?)',
                    [$userId, $gameId]
                );
                break;

            case 'remove':
                $database->query('DELETE FROM cart WHERE user_id = ? AND game_id = ?', [$userId, $gameId]);
                break;

            case 'move-to-wishlist':
                $database->query(
                    'INSERT IGNORE INTO wishlist (user_id, game_id) VALUES (?, ?)',
                    [$userId, $gameId]
                );
                $database->query('DELETE FROM cart WHERE user_id = ? AND game_id = ?', [$userId, $gameId]);
                break;

            case 'checkout':
                $paid = cartTotals($database, $userId);

                // The purchase is what was missing: without a library row the
                // game page could never tell a bought game from a new one.
                $granted = Library::grantCart($userId, $database);

                $database->query(
                    'DELETE FROM wishlist WHERE user_id = ?
                     AND game_id IN (SELECT game_id FROM cart WHERE user_id = ?)',
                    [$userId, $userId]
                );
                $database->query('DELETE FROM cart WHERE user_id = ?', [$userId]);

                Api::json([
                    'status'  => 'ok',
                    'paid'    => $paid['subtotal'],
                    'bought'  => $paid['items'],
                    'granted' => $granted
                ]);

            default:
                Api::fail('Unknown cart action: ' . $action, 422);
        }

        Api::json(['status' => 'ok', 'totals' => cartTotals($database, $userId)]);
    }

    /* ---------------------------------------------------------------- read */

    $offset = Api::int('offset', 0, 0);
    $limit  = Api::int('limit', 12, 1, 50);

    $rows = $database->query(
        'SELECT g.id, g.title, g.price, g.discount, g.image_path, g.fallback_art
         FROM cart c
         INNER JOIN game g ON g.id = c.game_id
         WHERE c.user_id = ?
         ORDER BY c.added_at DESC
         LIMIT ? OFFSET ?',
        [$userId, $limit, $offset]
    )->fetchAll();

    if (!$rows) {
        // An empty first page still needs the totals so the summary can reset.
        if ($offset === 0) {
            Api::json(['items' => [], 'totals' => cartTotals($database, $userId)]);
        }
        Api::noContent();
    }

    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Categories and platforms for the whole page in two queries, not 2 per row.
    $categories = [];
    foreach ($database->query(
        "SELECT gc.game_id, c.name FROM game_category gc
         INNER JOIN category c ON c.id = gc.category_id
         WHERE gc.game_id IN ($placeholders) ORDER BY c.name", $ids)->fetchAll() as $row) {
        $categories[(int)$row['game_id']][] = $row['name'];
    }

    $platforms = [];
    foreach ($database->query(
        "SELECT gp.game_id, p.name FROM game_platform gp
         INNER JOIN platform p ON p.id = gp.platform_id
         WHERE gp.game_id IN ($placeholders)", $ids)->fetchAll() as $row) {
        $platforms[(int)$row['game_id']][] = strtolower($row['name']);
    }

    $items = [];
    foreach ($rows as $row) {
        $gameId = (int)$row['id'];
        $price = (float)$row['price'];
        $discount = (int)$row['discount'];

        $items[] = [
            'id'           => $gameId,
            'title'        => $row['title'],
            'cover'        => $row['image_path'] ? Media::url($row['image_path']) : '',
            'fallback_art' => $row['fallback_art'],
            'categories'   => $categories[$gameId] ?? [],
            'platforms'    => $platforms[$gameId] ?? [],
            'price'        => round($price, 2),
            'discount'     => $discount,
            'final_price'  => round($price * (100 - $discount) / 100, 2)
        ];
    }

    Api::json(['items' => $items, 'totals' => cartTotals($database, $userId)]);
?>
