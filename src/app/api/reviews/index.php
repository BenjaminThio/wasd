<?php
    /**
     * Reviews.
     *
     * GET    ?game_id&limit&offset      -> rendered review cards
     * POST   { game_id, enjoy, description } -> create, or update your own
     * DELETE { review_id }              -> remove your own
     *
     * A player has at most one review per game, so POST upserts. Ownership is
     * checked on the server for both writes: the buttons on the card are a
     * convenience, never the gate.
     */
    require_once __DIR__ . '/../../../lib/Api.php';
    require_once __DIR__ . '/../../../models/Games.php';
    require_once __DIR__ . '/../../../models/Icon.php';

    Api::begin(false);

    /* --------------------------------------------------------------- delete */

    if (Api::method() === 'DELETE') {
        header('Content-Type: application/json');

        $user = Api::requireUser();
        $reviewId = (int)(Api::body()['review_id'] ?? 0);

        if ($reviewId <= 0) {
            Api::fail('No review was sent.', 422);
        }

        $database = Api::database();

        $review = $database->query(
            'SELECT user_id, game_id FROM review WHERE id = ?', [$reviewId]
        )->fetch();

        if (!$review) {
            Api::fail('That review no longer exists.', 404);
        }

        if ((int)$review['user_id'] !== $user->getId()) {
            Api::fail('You can only delete your own review.', 403);
        }

        $gameId = (int)$review['game_id'];
        $database->query('DELETE FROM review WHERE id = ? AND user_id = ?', [$reviewId, $user->getId()]);

        Api::json([
            'status' => 'success',
            'review_id' => $reviewId,
            // Lets the page correct its rating and counts on the spot.
            'summary' => Games::reviewSummary($gameId, $database)
        ]);
    }

    /* ----------------------------------------------------------- create/edit */

    if (Api::method() === 'POST') {
        header('Content-Type: application/json');

        $user = Api::requireUser();
        $input = Api::body();

        $gameId = (int)($input['game_id'] ?? 0);
        $description = trim((string)($input['description'] ?? ''));

        if ($gameId <= 0)        Api::fail('No game was sent.', 422);
        if ($description === '') Api::fail('Write a few words about the game.', 422);

        $database = Api::database();

        if (!$database->query('SELECT 1 FROM game WHERE id = ?', [$gameId])->fetch()) {
            Api::fail('That game does not exist.', 404);
        }

        // One review per player per game: a repeat post edits the old one
        // instead of stacking duplicates.
        $existing = $database->query(
            'SELECT id FROM review WHERE user_id = ? AND game_id = ? LIMIT 1',
            [$user->getId(), $gameId]
        )->fetch();

        $data = [
            'enjoy' => !empty($input['enjoy']) ? 1 : 0,
            'description' => $description,
        ];

        if ($existing) {
            $database->update('review', $data, ['id' => (int)$existing['id'], 'user_id' => $user->getId()]);
            $reviewId = (int)$existing['id'];
        } else {
            $database->insert('review', $data + ['user_id' => $user->getId(), 'game_id' => $gameId]);
            $reviewId = $database->lastInsertId();
        }

        Api::json([
            'status' => 'success',
            'updated' => (bool)$existing,
            'review_id' => $reviewId,
            'summary' => Games::reviewSummary($gameId, $database)
        ]);
    }

    /* ------------------------------------------------------------------ read */

    $gameId = Api::int('game_id', 0, 0);
    $limit  = Api::int('limit', 5, 1, 25);
    $offset = Api::int('offset', 0, 0);

    $reviews = Games::getReviewChunk($gameId, $limit, $offset);

    if (empty($reviews)) {
        Api::noContent();
    }

    // Lets each card decide whether to show its owner controls.
    $reviewViewerId = Api::optionalUser()?->getId();

    header('Content-Type: text/html; charset=utf-8');

    foreach ($reviews as $review) {
        require __DIR__ . '/../../../components/review-card.php';
    }

    exit;
?>
