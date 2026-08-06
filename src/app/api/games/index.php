<?php
    /**
     * Store listing / search endpoint.
     *
     * GET ?q&category&platform&price&sort&limit&offset
     *
     * Returns the rendered cards plus the totals the toolbar needs:
     *   { html, count, total, has_more }
     *
     * The cards are produced by the same component the page uses for its first
     * paint, so search results and server-rendered results can never drift.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../lib/View.php';
    require_once __DIR__ . '/../../../models/Games.php';
    require_once __DIR__ . '/../../../models/Icon.php';
    require_once __DIR__ . '/../../../models/Library.php';

    Api::begin();

    $limit  = Api::int('limit', 12, 1, 50);
    $offset = Api::int('offset', 0, 0);

    $result = Games::search([
        'q'        => Api::text('q'),
        'category' => Api::int('category', 0, 0),
        'platform' => Api::text('platform'),
        'price'    => Api::text('price', 'all'),
        'sort'     => Api::text('sort', 'newest'),
        'limit'    => $limit,
        'offset'   => $offset,
    ]);

    $games = $result['games'];

    if (empty($games)) {
        Api::json(['html' => '', 'count' => 0, 'total' => $result['total'], 'has_more' => false]);
    }

    $viewer = Api::optionalUser();
    $ownedIds = Library::ownedIdsIn(
        $viewer?->getId(),
        array_map(fn(Game $game) => (int)$game->getId(), $games),
        Api::database()
    );

    ob_start();
    foreach ($games as $game) {
        require __DIR__ . '/../../../components/game-card.php';
    }
    $html = ob_get_clean();

    Api::json([
        'html'     => $html,
        'count'    => count($games),
        'total'    => $result['total'],
        'has_more' => ($offset + count($games)) < $result['total'],
    ]);
?>
