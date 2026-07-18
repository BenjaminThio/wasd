<?php
    require_once __DIR__ . '/../lib/Database.php';
    require_once __DIR__ . '/User.php';
    require_once __DIR__ . '/Games.php';

    // Core User CRUD
    class Users
    {
        public static function save(User $user): void
        {
            $database = new Database();

            // SECURITY: Always hash the password before saving
            $hashedPassword = password_hash($user->getPassword(), PASSWORD_DEFAULT);

            $data = [
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'password' => $hashedPassword,
                'avatar_path' => $user->getAvatarPath()
            ];

            if ($user->getId() === null || $user->getId() === 0) {
                $database->insert('user', $data);
            } else {
                $database->update('user', $data, ['id' => $user->getId()]);
            }
        }

        public static function getByEmail(string $email): ?User
        {
            $database = new Database();
            $stmt = $database->query('SELECT * FROM user WHERE email = ? LIMIT 1', [$email]);
            $row = $stmt->fetch();

            if (!$row) return null;
            return self::hydrateUser($row, $database);
        }

        public static function getById(int $id): ?User
        {
            $database = new Database();
            $stmt = $database->query('SELECT * FROM user WHERE id = ? LIMIT 1', [$id]);
            $row = $stmt->fetch();

            if (!$row) return null;
            return self::hydrateUser($row, $database);
        }

        // E-commerce high-level functions
        public static function addToCart(int $userId, int $gameId): void
        {
            $database = new Database();
            // Use IGNORE so it won't crash if they click "Add to Cart" twice
            $sql = "INSERT IGNORE INTO cart (user_id, game_id) VALUES (?, ?)";
            $database->query($sql, [$userId, $gameId]);
        }

        public static function removeFromCart(int $userId, int $gameId): void
        {
            $database = new Database();
            $sql = "DELETE FROM cart WHERE user_id = ? AND game_id = ?";
            $database->query($sql, [$userId, $gameId]);
        }

        public static function addToWishlist(int $userId, int $gameId): void
        {
            $database = new Database();
            $sql = "INSERT IGNORE INTO wishlist (user_id, game_id) VALUES (?, ?)";
            $database->query($sql, [$userId, $gameId]);
        }

        public static function removeFromWishlist(int $userId, int $gameId): void
        {
            $database = new Database();
            $sql = "DELETE FROM wishlist WHERE user_id = ? AND game_id = ?";
            $database->query($sql, [$userId, $gameId]);
        }

        // The hydrator
        private static function hydrateUser(array $row, Database $database): User
        {
            $userId = $row['id'];

            // Fetch cart items
            $cartSql = "SELECT game_id FROM cart WHERE user_id = ? ORDER BY added_at DESC";
            $cartRows = $database->query($cartSql, [$userId])->fetchAll();

            $cart = [];
            foreach ($cartRows as $cRow) {
                // Get the FULL game object
                $game = Games::getById($cRow['game_id']);
                if ($game) {
                    $cart[] = $game;
                }
            }

            // Fetch wishlist items
            $wishlistSql = "SELECT game_id FROM wishlist WHERE user_id = ? ORDER BY added_at DESC";
            $wishlistRows = $database->query($wishlistSql, [$userId])->fetchAll();
            
            $wishlist = [];
            foreach ($wishlistRows as $wRow) {
                $game = Games::getById($wRow['game_id']);
                if ($game) {
                    $wishlist[] = $game;
                }
            }

            // Assemble and return the fully-loaded User
            return User::fromDatabaseRow($row, $cart, $wishlist);
        }
    }
?>