<?php
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/Auth.php';
    require_once __DIR__ . '/Csrf.php';
    require_once __DIR__ . '/Database.php';

    /**
     * Api
     *
     * Every endpoint under src/app/api used to repeat the same six lines of
     * headers, its own json_decode of the request body and its own ad-hoc way
     * of reporting failure - three of them also read $_SESSION without ever
     * starting the session, which silently made every visitor "user 1".
     *
     * This class is the single place all of that now lives.
     */
    class Api
    {
        private static ?Database $database = null;

        /** Standard headers for a JSON endpoint. Call this first. */
        public static function begin(bool $json = true): void
        {
            if ($json) {
                header('Content-Type: application/json');
            }

            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');

            Auth::startSession();
        }

        /** Shared connection, so one request never opens five of them. */
        public static function database(): Database
        {
            return self::$database ??= new Database();
        }

        public static function method(): string
        {
            return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        }

        /** Decoded JSON request body, always an array. */
        public static function body(): array
        {
            static $cache = null;

            if ($cache === null) {
                $raw = file_get_contents('php://input') ?: '';
                $decoded = json_decode($raw, true);
                $cache = is_array($decoded) ? $decoded : [];
            }

            return $cache;
        }

        /** Query-string integer with clamping, e.g. Api::int('limit', 12, 1, 50). */
        public static function int(string $key, int $default = 0, int $min = 0, int $max = PHP_INT_MAX): int
        {
            $value = isset($_GET[$key]) ? (int)$_GET[$key] : $default;
            return max($min, min($max, $value));
        }

        public static function text(string $key, string $default = ''): string
        {
            return trim((string)($_GET[$key] ?? $default));
        }

        public static function json(array $payload, int $status = 200): never
        {
            http_response_code($status);
            echo json_encode($payload);
            exit;
        }

        public static function fail(string $message, int $status = 400, ?string $field = null): never
        {
            $payload = ['status' => 'error', 'error' => $message];

            // Naming the field lets the browser put the message underneath the
            // input that caused it instead of at the bottom of the form, which
            // is the difference between "something is wrong" and "this is what
            // is wrong, here".
            if ($field !== null) {
                $payload['field'] = $field;
            }

            self::json($payload, $status);
        }

        /** 204 tells the infinite scrollers "stop asking". */
        public static function noContent(): never
        {
            http_response_code(204);
            exit;
        }

        /**
         * The signed-in user, or a 401 - never a silent fallback to user 1.
         *
         * Anything that is not a plain read must also carry the CSRF token.
         * Every write in the application goes through here, so this is the one
         * place the rule has to be enforced.
         */
        public static function requireUser(): User
        {
            $user = Auth::getCurrentUser();

            if (!$user) {
                self::fail('You need to be signed in to do that.', 401);
            }

            if (!in_array(self::method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                if (!Csrf::check(Csrf::fromRequest(self::body()))) {
                    // 403 rather than the 419 some frameworks use: it is the
                    // standard code, and clients and proxies all understand it.
                    self::fail('Your session token was missing or stale. Reload the page and try again.', 403);
                }
            }

            return $user;
        }

        /**
         * A signed-in user who is also staff, or a refusal.
         *
         * Two separate answers on purpose. A visitor who is not signed in gets
         * a 401, because signing in would fix it. A signed-in user who is not
         * staff gets a 403, because it would not.
         */
        public static function requireAdmin(): User
        {
            $user = self::requireUser();

            if (!$user->isAdmin()) {
                self::fail('That area is for staff accounts only.', 403);
            }

            return $user;
        }

        /** The signed-in user or null, for endpoints that also serve guests. */
        public static function optionalUser(): ?User
        {
            return Auth::getCurrentUser();
        }
    }
?>
