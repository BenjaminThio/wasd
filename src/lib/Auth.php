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

        // Create the Session (Call this upon successful Sign In / Sign Up)
        public static function login(User $user): void
        {
            self::startSession();
            
            // Regenerate the session ID to completely prevent Session Fixation attacks
            session_regenerate_id(true);
            
            // Store ONLY the user ID in the session, not the whole object or password
            $_SESSION['user_id'] = $user->getId();
        }

        public static function loginDevUser(): User
        {
            $devUser = Users::getDevUser();
            self::login($devUser);
            return $devUser;
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
        }

        // Global helper to grab the logged-in User object anytime you need it
        public static function getCurrentUser(): ?User
        {
            self::startSession();
            
            if (isset($_SESSION['user_id'])) {
                return Users::getById($_SESSION['user_id']);
            }
            
            return null; // No one is logged in
        }
    }
?>