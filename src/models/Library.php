<?php
    require_once __DIR__ . '/../lib/Database.php';

    /**
     * Library
     *
     * A row here means "this user owns this game and may download its builds".
     *
     * Free games are deliberately NOT stored: everybody owns them, so asking
     * the table about them would only create rows that never get read.
     * owns() answers that case from the price instead.
     */
    class Library
    {
        /** Records a purchase. Ignores duplicates so re-buying is harmless. */
        public static function grant(int $userId, int $gameId, float $pricePaid = 0.0, ?Database $database = null): void
        {
            $database ??= new Database();

            $database->query(
                'INSERT IGNORE INTO library (user_id, game_id, price_paid) VALUES (?, ?, ?)',
                [$userId, $gameId, $pricePaid]
            );
        }

        /** Moves everything currently in the cart into the library, in one statement. */
        public static function grantCart(int $userId, Database $database): int
        {
            $statement = $database->query(
                'INSERT IGNORE INTO library (user_id, game_id, price_paid)
                 SELECT ?, g.id, g.price * (100 - g.discount) / 100
                 FROM cart c
                 INNER JOIN game g ON g.id = c.game_id
                 WHERE c.user_id = ?',
                [$userId, $userId]
            );

            return $statement->rowCount();
        }

        /**
         * True when the player may download this game: either it is free, or
         * they bought it, or they are the developer who uploaded it.
         */
        public static function owns(?int $userId, int $gameId, ?Database $database = null): bool
        {
            $database ??= new Database();

            $game = $database->query('SELECT user_id, price FROM game WHERE id = ?', [$gameId])->fetch();
            if (!$game) return false;

            if ((float)$game['price'] <= 0) return true;
            if ($userId === null) return false;
            if ((int)$game['user_id'] === $userId) return true;

            $row = $database->query(
                'SELECT 1 FROM library WHERE user_id = ? AND game_id = ? LIMIT 1',
                [$userId, $gameId]
            )->fetch();

            return (bool)$row;
        }

        /**
         * Ids owned out of the given list - one query for a whole page of
         * cards instead of one query per card.
         *
         * @param int[] $gameIds
         * @return int[]
         */
        public static function ownedIdsIn(?int $userId, array $gameIds, ?Database $database = null): array
        {
            if ($userId === null || empty($gameIds)) return [];

            $database ??= new Database();
            $placeholders = implode(',', array_fill(0, count($gameIds), '?'));

            $rows = $database->query(
                "SELECT game_id FROM library WHERE user_id = ? AND game_id IN ($placeholders)",
                array_merge([$userId], array_map('intval', $gameIds))
            )->fetchAll();

            return array_map('intval', array_column($rows, 'game_id'));
        }

        /** Everything the player owns, newest purchase first. */
        public static function gameIdsFor(int $userId, ?Database $database = null): array
        {
            $database ??= new Database();

            $rows = $database->query(
                'SELECT game_id FROM library WHERE user_id = ? ORDER BY acquired_at DESC',
                [$userId]
            )->fetchAll();

            return array_map('intval', array_column($rows, 'game_id'));
        }
    }
?>
