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

        public static function getByUsername(string $username): ?User
        {
            $database = new Database();
            $row = $database->query('SELECT * FROM user WHERE username = ? LIMIT 1', [$username])->fetch();

            if (!$row) return null;
            return self::hydrateUser($row, $database);
        }

        public static function usernameTaken(string $username): bool
        {
            $database = new Database();
            return (bool)$database->query(
                'SELECT 1 FROM user WHERE username = ? LIMIT 1', [$username]
            )->fetch();
        }

        public static function emailTaken(string $email): bool
        {
            $database = new Database();
            return (bool)$database->query(
                'SELECT 1 FROM user WHERE email = ? LIMIT 1', [$email]
            )->fetch();
        }

        /**
         * Create the account row and hand back the fully-hydrated User.
         *
         * This is the piece that was entirely missing: nothing in the codebase
         * ever inserted a new row into `user`. save() below could technically
         * do it (it branches on a null id), but nothing called it either - the
         * sign-up endpoint validated a couple of fields and stopped.
         *
         * Auth::register() is the high-level entry point that pairs with
         * Auth::login(); this is its one job, kept separate so the "create a
         * row" step can be tested and reasoned about on its own.
         */
        public static function create(string $username, string $email, string $password): User
        {
            $database = new Database();
            $database->insert('user', [
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'avatar_path' => null,
            ]);

            $user = self::getById($database->lastInsertId());

            if ($user === null) {
                // Unreachable in practice - the insert above just committed
                // this exact row - but create() promises a User, not a User|null.
                throw new RuntimeException('User was created but could not be read back.');
            }

            return $user;
        }

        /**
         * Shared password strength rule.
         *
         * Sign-up and the profile password change both enforce this exact
         * policy, so it lives here once instead of as two copies that could
         * quietly drift apart.
         */
        public static function isStrongPassword(string $password): bool
        {
            return strlen($password) >= 6
                && preg_match('/[A-Z]/', $password)
                && preg_match('/[a-z]/', $password)
                && preg_match('/[0-9]/', $password)
                && preg_match('/[^A-Za-z0-9]/', $password);
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

        /**
         * Turn a user row into a User.
         *
         * This used to eagerly load the whole cart and wishlist as fully
         * hydrated Game objects - every review, screenshot, build and platform
         * of every item - on every single request, because the header calls
         * Auth::getCurrentUser(). Nothing in the application ever read those
         * two arrays, and they cost around sixty queries a page. A static
         * contact page was issuing sixty-four.
         *
         * The cart and wishlist are served by their own endpoints, which page
         * them properly; they are not part of identity.
         */
        private static function hydrateUser(array $row, Database $database): User
        {
            return User::fromDatabaseRow($row);
        }

        public static function getDevUser(): User
        {
            return new User(
                id: 1,
                username: 'GamerGuy99',
                email: 'gamerguy@example.com',
                password: '$2y$10$dummyhash12345678901234567890',
                avatarPath: null,
                cart: [],
                wishlist: []
            );
        }
    }
?>