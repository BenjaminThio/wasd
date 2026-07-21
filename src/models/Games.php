<?php
    require_once __DIR__ . '/../lib/Database.php';
    require_once __DIR__ . '/Game.php';
    require_once __DIR__ . '/Category.php';
    require_once __DIR__ . '/Review.php';

    class Games
    {
        public static function save(Game $game): void
        {
            $database = new Database();
            $data = [
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'price' => $game->getPrice(),
                'discount' => $game->getDiscount(),
                'image_path' => $game->getImage()
            ];

            if ($game->getId() === null || $game->getId() === 0) {
                $database->insert('game', $data);
            } else {
                $database->update('game', $data, ['id' => $game->getId()]);
            }
        }

        public static function getAll(): array
        {
            $database = new Database();
            $rows = $database->query('SELECT * FROM game ORDER BY id DESC')->fetchAll();
            $result = [];

            foreach ($rows as $row) {
                $result[] = self::hydrateGame($row, $database);
            }
            return $result;
        }

        public static function getChunk(int $limit, int $offset): array
        {
            $database = new Database();
            $sql = "SELECT * FROM game ORDER BY id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $rows = $database->query($sql)->fetchAll();
            $result = [];

            foreach ($rows as $row) {
                $result[] = self::hydrateGame($row, $database);
            }
            return $result;
        }

        public static function getReviewChunk(int $gameId, int $limit, int $offset): array
        {
            $database = new Database();
            $sql = "
                SELECT r.enjoy, r.description, r.created_at, 
                    u.id AS user_id, u.username, u.email, u.password, u.avatar_path 
                FROM review r 
                INNER JOIN user u ON r.user_id = u.id 
                WHERE r.game_id = ?
                ORDER BY r.created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $reviewRows = $database->query($sql, [$gameId])->fetchAll();
            $reviews = [];

            foreach ($reviewRows as $rRow) {
                $reviewer = new User(
                    $rRow['user_id'], $rRow['username'], $rRow['email'], $rRow['password'], $rRow['avatar_path']
                );
                $reviews[] = new Review($reviewer, (bool)$rRow['enjoy'], $rRow['description'], $rRow['created_at']);
            }
            return $reviews;
        }

        public static function getById(int $id): ?Game
        {
            $database = new Database();
            $stmt = $database->query('SELECT * FROM game WHERE id = ?', [$id]);
            $row = $stmt->fetch();

            if (!$row) return null;
            return self::hydrateGame($row, $database);
        }

        // The hydrator
        private static function hydrateGame(array $row, Database $database): Game
        {
            $gameId = $row['id'];

            // Fetch platforms
            $platformSql = "SELECT p.name FROM platform p INNER JOIN game_platform gp ON p.id = gp.platform_id WHERE gp.game_id = ?";
            $platformRows = $database->query($platformSql, [$gameId])->fetchAll();
            $platforms = [];
            foreach ($platformRows as $pRow) {
                foreach (Platform::cases() as $enumCase) {
                    if (strcasecmp($enumCase->name, $pRow['name']) === 0) {
                        $platforms[] = $enumCase;
                    }
                }
            }

            // Fetch categories
            $categorySql = "SELECT c.name FROM category c INNER JOIN game_category gc ON c.id = gc.category_id WHERE gc.game_id = ?";
            $categoryRows = $database->query($categorySql, [$gameId])->fetchAll();
            $categories = [];
            $availableColors = CategoryColor::cases();
            foreach ($categoryRows as $index => $cRow) {
                $assignedColor = $availableColors[$index % count($availableColors)];
                $categories[] = new Category($cRow['name'], $assignedColor);
            }

            // Fetch Reviews
            $reviewSql = "
                SELECT r.enjoy, r.description, r.created_at, 
                    u.id AS user_id, u.username, u.email, u.password, u.avatar_path 
                FROM review r 
                INNER JOIN user u ON r.user_id = u.id 
                WHERE r.game_id = ?
                ORDER BY r.created_at DESC
            ";

            $reviewRows = $database->query($reviewSql, [$gameId])->fetchAll();
            $reviews = [];

            foreach ($reviewRows as $rRow) {
                $reviewer = new User(
                    $rRow['user_id'], 
                    $rRow['username'], 
                    $rRow['email'], 
                    $rRow['password'], 
                    $rRow['avatar_path']
                );

                $reviews[] = new Review(
                    $reviewer,
                    (bool)$rRow['enjoy'],
                    $rRow['description'],
                    $rRow['created_at']
                );
            }

            // Pass EVERYTHING into the Game Object
            return Game::fromDatabaseRow($row, $platforms, $categories, $reviews);
        }
    }
?>