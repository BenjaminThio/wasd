<?php
    require_once __DIR__ . '/../models/Users.php';

    class Auth
    {
        // Start the session securely
        public static function startSession(): void
        {
            if (session_status() === PHP_SESSION_NONE) {
                // Lock down the session cookie before starting it
                session_set_cookie_params([
                    'lifetime' => 86400 * 30, // 30 days
                    'path' => '/',
                    'domain' => '', 
                    'secure' => false, // IMPORTANT: Set to true when deploy to HTTPS!
                    'httponly' => true, // Prevents JavaScript (and XSS attacks) from reading the cookie
                    'samesite' => 'Strict' // Prevents CSRF attacks
                ]);
                session_start();
            }
        }

        public static function login(int $id): void
        {
            self::startSession();
            
            // Regenerate the session ID to completely prevent Session Fixation attacks
            session_regenerate_id(true);
            
            // Store ONLY the user ID in the session, not the whole object or password
            $_SESSION['user_id'] = $id;

            self::forgetCurrentUser();
        }

        /*
        // Create the Session (Call this upon successful Sign In / Sign Up)
        public static function login(User $user): void
        {
            self::startSession();
            
            // Regenerate the session ID to completely prevent Session Fixation attacks
            session_regenerate_id(true);
            
            // Store ONLY the user ID in the session, not the whole object or password
            $_SESSION['user_id'] = $user->getId();

            self::forgetCurrentUser();
        }
        */

        public static function loginDevUser(): User
        {
            $devUser = Users::getDevUser();
            self::login($devUser->getId());
            return $devUser;
        }

        /**
         * Create an account and sign into it in one step - the sign-up
         * counterpart to login(). Callers are expected to have already
         * validated the fields (format, strength, "is it taken"); this just
         * creates the row and starts the session, exactly like login() does
         * for an account that already exists.
         */
        public static function register(string $username, string $email, string $password): User
        {
            $user = Users::create($username, $email, $password);
            self::login($user->getId());
            return $user;
        }

        // Delete the Session (Call this when the user clicks "Log Out")
        public static function logout(): void
        {
            self::startSession();
            
            // Empty the session variables in PHP memory
            $_SESSION = [];
            
            // Force the browser to delete the physical cookie by setting its expiration to the past
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Completely destroy the session file on the server
            session_destroy();

            self::forgetCurrentUser();
        }

        /**
         * The signed-in user, looked up once per request.
         *
         * The header, the footer and the page body all ask for it, and each
         * call used to be a fresh round trip to the database. Nothing can
         * change the answer mid-request except login() and logout(), which
         * both clear the cache below.
         */
        private static ?User $currentUser = null;
        private static bool $currentUserLoaded = false;

        public static function getCurrentUser(): ?User
        {
            if (self::$currentUserLoaded) {
                return self::$currentUser;
            }

            self::startSession();
            self::$currentUserLoaded = true;

            self::$currentUser = isset($_SESSION['user_id'])
                ? Users::getById($_SESSION['user_id'])
                : null;

            return self::$currentUser;
        }

        /** Drops the memoised user, so the next read reflects a new session. */
        private static function forgetCurrentUser(): void
        {
            self::$currentUser = null;
            self::$currentUserLoaded = false;
        }
    }
?>