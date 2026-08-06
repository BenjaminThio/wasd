<?php
    /**
     * Wishlist endpoint.
     *
     * GET  ?offset&limit&q&sort&filter -> { items, total }
     * POST { action, game_id }         -> add | remove | add-to-cart
     *
     * The search, sort and filter controls on the wishlist page were static
     * markup; they are now real query parameters handled here.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../lib/Media.php';
    require_once __DIR__ . '/../../../models/Library.php';

    Api::begin();

    $user = Api::requireUser();
    $userId = $user->getId();
    $database = Api::database();

    /* --------------------------------------------------------------- write */

    if (Api::method() === 'POST') {
        $input  = Api::body();
        $action = $input['action'] ?? 'add';
        $gameId = (int)($input['game_id'] ?? 0);

        if ($gameId <= 0) Api::fail('No game was sent.', 422);

        switch ($action) {
            case 'add':
                $database->query('INSERT IGNORE INTO wishlist (user_id, game_id) VALUES (?, ?)', [$userId, $gameId]);
                break;

            case 'remove':
                $database->query('DELETE FROM wishlist WHERE user_id = ? AND game_id = ?', [$userId, $gameId]);
                break;

            case 'add-to-cart':
                if (Library::owns($userId, $gameId, $database)) {
                    Api::json(['status' => 'owned', 'message' => 'You already have this game.']);
                }
                $database->query('INSERT IGNORE INTO cart (user_id, game_id) VALUES (?, ?)', [$userId, $gameId]);
                break;

            default:
                Api::fail('Unknown wishlist action: ' . $action, 422);
        }

        Api::json(['status' => 'ok']);
    }

    /* ---------------------------------------------------------------- read */

    $offset = Api::int('offset', 0, 0);
    $limit  = Api::int('limit', 12, 1, 50);
    $term   = Api::text('q');
    $filter = Api::text('filter', 'all');
    $sort   = Api::text('sort', 'added');

    $sorts = [
        'added'      => 'w.added_at DESC',
        'title'      => 'g.title ASC',
        'price-low'  => 'final_price ASC',
        'price-high' => 'final_price DESC',
        'discount'   => 'g.discount DESC',
        'release'    => 'g.release_date DESC',
    ];
    $order = $sorts[$sort] ?? $sorts['added'];

    $where = ['w.user_id = ?'];
    $params = [$userId];

    if ($term !== '') {
        $where[] = '(g.title LIKE ? OR g.developer LIKE ?)';
        $like = '%' . $term . '%';
        array_push($params, $like, $like);
    }

    if ($filter === 'on-sale')  $where[] = 'g.discount > 0';
    if ($filter === 'free')     $where[] = 'g.price * (100 - g.discount) / 100 <= 0';
    if ($filter === 'paid')     $where[] = 'g.price * (100 - g.discount) / 100 > 0';
    if ($filter === 'in-cart')  $where[] = 'EXISTS (SELECT 1 FROM cart c WHERE c.user_id = w.user_id AND c.game_id = g.id)';

    $clause = 'WHERE ' . implode(' AND ', $where);

    $total = (int)$database->query(
        "SELECT COUNT(*) AS total FROM wishlist w INNER JOIN game g ON g.id = w.game_id $clause",
        $params
    )->fetch()['total'];

    $rows = $database->query(
        "SELECT g.id, g.title, g.price, g.discount, g.image_path, g.fallback_art, g.release_date,
                (g.price * (100 - g.discount) / 100) AS final_price,
                EXISTS (SELECT 1 FROM cart c WHERE c.user_id = w.user_id AND c.game_id = g.id) AS in_cart,
                EXISTS (SELECT 1 FROM library l WHERE l.user_id = w.user_id AND l.game_id = g.id) AS owned
         FROM wishlist w
         INNER JOIN game g ON g.id = w.game_id
         $clause
         ORDER BY $order
         LIMIT ? OFFSET ?",
        array_merge($params, [$limit, $offset])
    )->fetchAll();

    if (!$rows) {
        Api::json(['items' => [], 'total' => $total]);
    }

    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

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

    // One grouped query for review sentiment instead of one per row.
    $reviews = [];
    foreach ($database->query(
        "SELECT game_id, COUNT(*) AS total, SUM(enjoy) AS good FROM review
         WHERE game_id IN ($placeholders) GROUP BY game_id", $ids)->fetchAll() as $row) {
        $reviews[(int)$row['game_id']] = ['total' => (int)$row['total'], 'good' => (int)$row['good']];
    }

    $items = [];
    foreach ($rows as $row) {
        $gameId = (int)$row['id'];
        $price = (float)$row['price'];
        $discount = (int)$row['discount'];

        $status = 1;
        if (isset($reviews[$gameId]) && $reviews[$gameId]['total'] > 0) {
            $percentage = $reviews[$gameId]['good'] / $reviews[$gameId]['total'];
            $status = $percentage >= 0.7 ? 2 : ($percentage < 0.4 ? 0 : 1);
        }

        $items[] = [
            'id'            => $gameId,
            'title'         => $row['title'],
            'cover'         => $row['image_path'] ? Media::url($row['image_path']) : '',
            'fallback_art'  => $row['fallback_art'],
            'categories'    => $categories[$gameId] ?? [],
            'platforms'     => $platforms[$gameId] ?? [],
            'price'         => round($price, 2),
            'discount'      => $discount,
            'final_price'   => round($price * (100 - $discount) / 100, 2),
            'review_status' => $status,
            'release_date'  => $row['release_date'] ? date('d M Y', strtotime($row['release_date'])) : 'TBA',
            'in_cart'       => (bool)$row['in_cart'],
            'owned'         => (bool)$row['owned'] || $price * (100 - $discount) / 100 <= 0
        ];
    }

    Api::json(['items' => $items, 'total' => $total]);
?>
