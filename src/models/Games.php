<?php
    require_once __DIR__ . '/../lib/Database.php';
    require_once __DIR__ . '/../lib/Media.php';
    require_once __DIR__ . '/Game.php';
    require_once __DIR__ . '/Category.php';
    require_once __DIR__ . '/Review.php';

    /**
     * Games
     *
     * Repository for everything the store reads.
     *
     * The important change here is hydrateMany(): building a page of 12 cards
     * used to fire 5 queries per game (platforms, categories, every review body,
     * screenshots, builds) - 60+ round trips for one store page. Those five
     * queries are now issued once for the whole batch and grouped in PHP, so a
     * page costs 6 queries no matter how many games it shows. Review bodies are
     * only loaded when a single game is opened; lists get counts instead.
     */
    class Games
    {
        private const SORTS = [
            'newest'    => 'g.id DESC',
            'oldest'    => 'g.id ASC',
            'title'     => 'g.title ASC',
            'price-low' => 'final_price ASC, g.id DESC',
            'price-high'=> 'final_price DESC, g.id DESC',
            'discount'  => 'g.discount DESC, g.id DESC',
            'popular'   => 'g.views DESC, g.id DESC',
            'downloads' => 'g.downloads DESC, g.id DESC',
        ];

        /* ------------------------------------------------------------ reads */

        public static function getAll(): array
        {
            $database = new Database();
            return self::hydrateMany($database->query('SELECT * FROM game ORDER BY id DESC')->fetchAll(), $database);
        }

        public static function getById(int $id): ?Game
        {
            $database = new Database();
            $row = $database->query('SELECT * FROM game WHERE id = ?', [$id])->fetch();

            if (!$row) return null;

            $games = self::hydrateMany([$row], $database, withReviews: true);
            return $games[0] ?? null;
        }

        public static function getChunk(int $limit, int $offset): array
        {
            $database = new Database();
            $rows = $database->query(
                'SELECT * FROM game WHERE visibility = ? ORDER BY id DESC LIMIT ? OFFSET ?',
                ['Public', $limit, $offset]
            )->fetchAll();

            return self::hydrateMany($rows, $database);
        }

        public static function getByUserIdChunk(int $userId, int $limit, int $offset): array
        {
            $database = new Database();
            $rows = $database->query(
                'SELECT * FROM game WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?',
                [$userId, $limit, $offset]
            )->fetchAll();

            return self::hydrateMany($rows, $database);
        }

        /** Games the player owns, newest purchase first. */
        public static function getOwnedChunk(int $userId, int $limit, int $offset): array
        {
            $database = new Database();
            $rows = $database->query(
                'SELECT g.* FROM library l
                 INNER JOIN game g ON g.id = l.game_id
                 WHERE l.user_id = ?
                 ORDER BY l.acquired_at DESC
                 LIMIT ? OFFSET ?',
                [$userId, $limit, $offset]
            )->fetchAll();

            return self::hydrateMany($rows, $database);
        }

        /* ----------------------------------------------------------- search */

        /**
         * Store search and filtering.
         *
         * @param array{
         *   q?:string, category?:int, platform?:string, price?:string,
         *   sort?:string, limit?:int, offset?:int, developer?:string
         * } $filters
         *
         * @return array{games: Game[], total: int}
         */
        public static function search(array $filters = []): array
        {
            $database = new Database();

            $term      = trim((string)($filters['q'] ?? ''));
            $category  = (int)($filters['category'] ?? 0);
            $platform  = trim((string)($filters['platform'] ?? ''));
            $price     = (string)($filters['price'] ?? 'all');
            $sort      = self::SORTS[$filters['sort'] ?? 'newest'] ?? self::SORTS['newest'];
            $limit     = max(1, min(50, (int)($filters['limit'] ?? 12)));
            $offset    = max(0, (int)($filters['offset'] ?? 0));

            $where  = ['g.visibility = ?'];
            $params = ['Public'];

            if ($term !== '') {
                // Title, developer and category name all count as a match, so
                // "valve", "portal" and "puzzle" each find Portal 2.
                $where[] = '(g.title LIKE ? OR g.developer LIKE ? OR g.description LIKE ?
                             OR EXISTS (
                                 SELECT 1 FROM game_category gc
                                 INNER JOIN category c ON c.id = gc.category_id
                                 WHERE gc.game_id = g.id AND c.name LIKE ?
                             ))';
                $like = '%' . $term . '%';
                array_push($params, $like, $like, $like, $like);
            }

            if ($category > 0) {
                $where[] = 'EXISTS (SELECT 1 FROM game_category gc WHERE gc.game_id = g.id AND gc.category_id = ?)';
                $params[] = $category;
            }

            if ($platform !== '') {
                $where[] = 'EXISTS (
                                SELECT 1 FROM game_platform gp
                                INNER JOIN platform p ON p.id = gp.platform_id
                                WHERE gp.game_id = g.id AND p.name = ?
                            )';
                $params[] = $platform;
            }

            if ($price === 'free')      $where[] = 'g.price * (100 - g.discount) / 100 <= 0';
            if ($price === 'paid')      $where[] = 'g.price * (100 - g.discount) / 100 > 0';
            if ($price === 'discounted')$where[] = 'g.discount > 0';
            if ($price === 'under-10')  $where[] = 'g.price * (100 - g.discount) / 100 BETWEEN 0.01 AND 10';

            $clause = 'WHERE ' . implode(' AND ', $where);

            $total = (int)$database->query("SELECT COUNT(*) AS total FROM game g $clause", $params)
                                   ->fetch()['total'];

            $rows = $database->query(
                "SELECT g.*, (g.price * (100 - g.discount) / 100) AS final_price
                 FROM game g $clause
                 ORDER BY $sort
                 LIMIT ? OFFSET ?",
                array_merge($params, [$limit, $offset])
            )->fetchAll();

            return ['games' => self::hydrateMany($rows, $database), 'total' => $total];
        }

        /* ------------------------------------------------------- one game up */

        /** Reviews for the detail page, paged. */
        public static function getReviewChunk(int $gameId, int $limit, int $offset): array
        {
            $database = new Database();

            $rows = $database->query(
                'SELECT r.id, r.enjoy, r.description, r.created_at,
                        u.id AS user_id, u.username, u.email, u.password, u.avatar_path
                 FROM review r
                 INNER JOIN user u ON r.user_id = u.id
                 WHERE r.game_id = ?
                 ORDER BY r.created_at DESC
                 LIMIT ? OFFSET ?',
                [$gameId, $limit, $offset]
            )->fetchAll();

            return array_map(fn(array $row) => new Review(
                new User($row['user_id'], $row['username'], $row['email'], $row['password'], $row['avatar_path']),
                (bool)$row['enjoy'],
                $row['description'],
                $row['created_at'],
                (int)$row['id']
            ), $rows);
        }

        /**
         * Current review tally for one game, ready for the page to display.
         *
         * The reviews endpoint returns this after every write so the rating in
         * the header and the counts in More Information can update without a
         * reload.
         *
         * @return array{total:int, positive:int, status:int, label:string}
         */
        public static function reviewSummary(int $gameId, ?Database $database = null): array
        {
            $database ??= new Database();

            $row = $database->query(
                'SELECT COUNT(*) AS total, COALESCE(SUM(enjoy), 0) AS positive
                 FROM review WHERE game_id = ?',
                [$gameId]
            )->fetch();

            $total = (int)($row['total'] ?? 0);
            $positive = (int)($row['positive'] ?? 0);

            return [
                'total' => $total,
                'positive' => $positive,
                'status' => Review::statusFor($total, $positive),
                'label' => Review::labelFor($total, $positive),
            ];
        }

        /** One extra page view. Fire-and-forget, never blocks a render. */
        public static function recordView(int $gameId): void
        {
            try {
                (new Database())->query('UPDATE game SET views = views + 1 WHERE id = ?', [$gameId]);
            } catch (Throwable) {
                // A missed view counter is never worth breaking the page for.
            }
        }

        /** One build download: counted on the build and on the game. */
        public static function recordDownload(int $gameId, int $buildId, ?Database $database = null): void
        {
            $database ??= new Database();
            $database->query('UPDATE game_build SET downloads = downloads + 1 WHERE id = ?', [$buildId]);
            $database->query('UPDATE game SET downloads = downloads + 1 WHERE id = ?', [$gameId]);
        }

        /* ------------------------------------------------------- developer */

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

        public static function getUserStats(int $userId): array
        {
            $database = new Database();

            $stats = $database->query(
                "SELECT COUNT(id) AS total_projects,
                        COALESCE(SUM(views), 0) AS total_views,
                        COALESCE(SUM(downloads), 0) AS total_downloads,
                        SUM(CASE WHEN visibility = 'Public' THEN 1 ELSE 0 END) AS total_published
                 FROM game WHERE user_id = ?",
                [$userId]
            )->fetch();

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

        public static function getAllPlatforms(): array
        {
            $database = new Database();
            return $database->query('SELECT id, name FROM platform ORDER BY id ASC')->fetchAll();
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

            $uploadDirectory = Media::gameDirAbsolute($gameId);
            if (is_dir($uploadDirectory)) {
                self::deleteDirectory($uploadDirectory);
            }

            $database->delete('game', ['id' => $gameId, 'user_id' => $userId]);
            return true;
        }

        private static function deleteDirectory(string $directory): void
        {
            if (!file_exists($directory)) return;

            foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
                is_dir("$directory/$entry")
                    ? self::deleteDirectory("$directory/$entry")
                    : @unlink("$directory/$entry");
            }
            @rmdir($directory);
        }

        /* ---------------------------------------------------------- hydration */

        /**
         * Turns raw game rows into Game objects using a fixed number of
         * queries, whatever the size of the batch.
         *
         * @param array<int, array> $rows
         * @return Game[]
         */
        public static function hydrateMany(array $rows, ?Database $database = null, bool $withReviews = false): array
        {
            if (empty($rows)) return [];

            $database ??= new Database();

            $ids = array_map(static fn(array $row) => (int)$row['id'], $rows);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // 1. Platforms
            $platformsByGame = [];
            $platformRows = $database->query(
                "SELECT gp.game_id, p.name
                 FROM game_platform gp
                 INNER JOIN platform p ON p.id = gp.platform_id
                 WHERE gp.game_id IN ($placeholders)",
                $ids
            )->fetchAll();

            foreach ($platformRows as $row) {
                foreach (Platform::cases() as $case) {
                    if (strcasecmp($case->name, $row['name']) === 0) {
                        $platformsByGame[(int)$row['game_id']][] = $case;
                    }
                }
            }

            // 2. Categories
            $categoriesByGame = [];
            $colors = CategoryColor::cases();
            $categoryRows = $database->query(
                "SELECT gc.game_id, c.name
                 FROM game_category gc
                 INNER JOIN category c ON c.id = gc.category_id
                 WHERE gc.game_id IN ($placeholders)
                 ORDER BY c.name ASC",
                $ids
            )->fetchAll();

            foreach ($categoryRows as $row) {
                $gameId = (int)$row['game_id'];
                $index = count($categoriesByGame[$gameId] ?? []);
                $categoriesByGame[$gameId][] = new Category($row['name'], $colors[$index % count($colors)]);
            }

            // 3. Review aggregates - enough for the traffic light and the label.
            $reviewStats = [];
            $statRows = $database->query(
                "SELECT game_id, COUNT(*) AS total, SUM(enjoy) AS positive
                 FROM review WHERE game_id IN ($placeholders) GROUP BY game_id",
                $ids
            )->fetchAll();

            foreach ($statRows as $row) {
                $reviewStats[(int)$row['game_id']] = [
                    'total' => (int)$row['total'],
                    'positive' => (int)$row['positive']
                ];
            }

            // 4. Screenshots
            $screenshotsByGame = [];
            $screenshotRows = $database->query(
                "SELECT game_id, image_path FROM game_screenshot
                 WHERE game_id IN ($placeholders) ORDER BY id ASC",
                $ids
            )->fetchAll();

            foreach ($screenshotRows as $row) {
                $screenshotsByGame[(int)$row['game_id']][] = $row['image_path'];
            }

            // 5. Builds
            $buildsByGame = [];
            $buildRows = $database->query(
                "SELECT * FROM game_build WHERE game_id IN ($placeholders)
                 ORDER BY sort_order ASC, id ASC",
                $ids
            )->fetchAll();

            foreach ($buildRows as $row) {
                $buildsByGame[(int)$row['game_id']][] = $row;
            }

            // 6. Full review bodies, only when a single game is being opened.
            $reviewsByGame = [];
            if ($withReviews) {
                $rows2 = $database->query(
                    "SELECT r.id, r.game_id, r.enjoy, r.description, r.created_at,
                            u.id AS user_id, u.username, u.email, u.password, u.avatar_path
                     FROM review r
                     INNER JOIN user u ON r.user_id = u.id
                     WHERE r.game_id IN ($placeholders)
                     ORDER BY r.created_at DESC",
                    $ids
                )->fetchAll();

                foreach ($rows2 as $row) {
                    $reviewsByGame[(int)$row['game_id']][] = new Review(
                        new User($row['user_id'], $row['username'], $row['email'], $row['password'], $row['avatar_path']),
                        (bool)$row['enjoy'],
                        $row['description'],
                        $row['created_at'],
                        (int)$row['id']
                    );
                }
            }

            $games = [];
            foreach ($rows as $row) {
                $id = (int)$row['id'];

                $games[] = Game::fromDatabaseRow(
                    $row,
                    $platformsByGame[$id] ?? [],
                    $categoriesByGame[$id] ?? [],
                    $reviewsByGame[$id] ?? [],
                    $screenshotsByGame[$id] ?? [],
                    $buildsByGame[$id] ?? [],
                    $reviewStats[$id]['total'] ?? 0,
                    $reviewStats[$id]['positive'] ?? 0
                );
            }

            return $games;
        }
    }
?>
