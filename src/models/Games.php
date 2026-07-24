<?php
    require_once __DIR__ . '/../lib/Database.php';
    require_once __DIR__ . '/../lib/Media.php';
    require_once __DIR__ . '/Game.php';
    require_once __DIR__ . '/Category.php';
    require_once __DIR__ . '/Review.php';

    class Games
    {
        public static function save(Game $game): void
        {
            $database = new Database();
            $data = [
                'title'       => $game->getTitle(),
                'description' => $game->getDescription(),
                'price'       => $game->getPrice(),
                'discount'    => $game->getDiscount(),
                'image_path'  => $game->getImage()
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
            $row = $database->query('SELECT * FROM game WHERE id = ?', [$id])->fetch();

            if (!$row) return null;
            return self::hydrateGame($row, $database);
        }

        public static function getChunk(int $limit, int $offset): array
        {
            $database = new Database();
            $sql = "SELECT * FROM game WHERE visibility = 'Public' ORDER BY id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $rows = $database->query($sql)->fetchAll();
            $result = [];

            foreach ($rows as $row) {
                $result[] = self::hydrateGame($row, $database);
            }
            return $result;
        }

        public static function getByUserIdChunk(int $userId, int $limit, int $offset): array
        {
            $database = new Database();
            $sql = "SELECT * FROM game WHERE user_id = ? ORDER BY id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $rows = $database->query($sql, [$userId])->fetchAll();
            $result = [];

            foreach ($rows as $row) {
                $result[] = self::hydrateGame($row, $database);
            }
            return $result;
        }

        public static function getUserStats(int $userId): array
        {
            $database = new Database();
            $sql = "SELECT
                        COUNT(id) AS total_projects,
                        SUM(views) AS total_views,
                        SUM(downloads) AS total_downloads,
                        SUM(CASE WHEN visibility = 'Public' THEN 1 ELSE 0 END) AS total_published
                    FROM game WHERE user_id = ?";

            $stats = $database->query($sql, [$userId])->fetch();

            return [
                'projects'  => (int)($stats['total_projects'] ?? 0),
                'views'     => (int)($stats['total_views'] ?? 0),
                'published' => (int)($stats['total_published'] ?? 0),
                'downloads' => (int)($stats['total_downloads'] ?? 0)
            ];
        }

        public static function getAllCategories(): array
        {
            $database = new Database();
            return $database->query('SELECT id, name FROM category ORDER BY name ASC')->fetchAll();
        }

        public static function getCategoryIds(int $gameId): array
        {
            $database = new Database();
            $rows = $database->query('SELECT category_id FROM game_category WHERE game_id = ?', [$gameId])->fetchAll();

            return array_map('intval', array_column($rows, 'category_id'));
        }

        public static function delete(int $gameId, int $userId): bool
        {
            $database = new Database();
            $game = self::getById($gameId);

            if (!$game || $game->getUserId() !== $userId) {
                return false;
            }

            $uploadDir = Media::gameDirAbsolute($gameId);
            if (is_dir($uploadDir)) {
                self::deleteDirectory($uploadDir);
            }

            $database->delete('game', ['id' => $gameId, 'user_id' => $userId]);
            return true;
        }

        private static function deleteDirectory(string $dir): void
        {
            if (!file_exists($dir)) return;

            foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $file) {
                is_dir("$dir/$file") ? self::deleteDirectory("$dir/$file") : @unlink("$dir/$file");
            }
            @rmdir($dir);
        }

        private static function hydrateGame(array $row, Database $database): Game
        {
            $gameId = $row['id'];

            // Platforms
            $platformSql = "SELECT p.name FROM platform p INNER JOIN game_platform gp ON p.id = gp.platform_id WHERE gp.game_id = ?";
            $platformRows = $database->query($platformSql, [$gameId])->fetchAll();
            $platforms = [];
            foreach ($platformRows as $pRow) {
                foreach (Platform::cases() as $enumCase) {
                    if (strcasecmp($enumCase->name, $pRow['name']) === 0) $platforms[] = $enumCase;
                }
            }

            // Categories
            $categorySql = "SELECT c.name FROM category c INNER JOIN game_category gc ON c.id = gc.category_id WHERE gc.game_id = ?";
            $categoryRows = $database->query($categorySql, [$gameId])->fetchAll();
            $categories = [];
            $availableColors = CategoryColor::cases();
            foreach ($categoryRows as $index => $cRow) {
                $categories[] = new Category($cRow['name'], $availableColors[$index % count($availableColors)]);
            }

            // Reviews
            $reviewSql = "SELECT r.enjoy, r.description, r.created_at, u.id AS user_id, u.username, u.email, u.password, u.avatar_path FROM review r INNER JOIN user u ON r.user_id = u.id WHERE r.game_id = ? ORDER BY r.created_at DESC";
            $reviewRows = $database->query($reviewSql, [$gameId])->fetchAll();
            $reviews = [];
            foreach ($reviewRows as $rRow) {
                $reviewer = new User($rRow['user_id'], $rRow['username'], $rRow['email'], $rRow['password'], $rRow['avatar_path']);
                $reviews[] = new Review($reviewer, (bool)$rRow['enjoy'], $rRow['description'], $rRow['created_at']);
            }

            // Screenshots - stored paths, run them through Media::url() to display.
            $screenshotSql = "SELECT image_path FROM game_screenshot WHERE game_id = ? ORDER BY id ASC";
            $screenshotRows = $database->query($screenshotSql, [$gameId])->fetchAll();
            $screenshots = array_column($screenshotRows, 'image_path');

            // Builds, in the order the developer arranged them.
            $buildSql = "SELECT * FROM game_build WHERE game_id = ? ORDER BY sort_order ASC, id ASC";
            $builds = $database->query($buildSql, [$gameId])->fetchAll();

            return Game::fromDatabaseRow($row, $platforms, $categories, $reviews, $screenshots, $builds);
        }
    }
?>