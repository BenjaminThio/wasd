<?php
    require_once __DIR__ . '/Auth.php';

    /**
     * Csrf
     *
     * Cross-site request forgery protection for every state-changing endpoint.
     *
     * The session cookie is already SameSite=Strict, which stops a browser
     * attaching it to a request started by another site - that alone blocks the
     * classic attack. This is the second lock: a secret the attacker's page
     * cannot read (it lives in our HTML, protected by the same-origin policy)
     * and cannot guess.
     *
     * It matters because SameSite is a browser-side promise. An old browser
     * that ignores the attribute, or any future relaxation of the cookie
     * policy, would leave writes exposed with nothing else in the way.
     */
    class Csrf
    {
        private const SESSION_KEY = 'csrf_token';
        public const HEADER = 'X-CSRF-Token';

        /** The token for this session, minted on first use. */
        public static function token(): string
        {
            Auth::startSession();

            if (empty($_SESSION[self::SESSION_KEY])) {
                $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
            }

            return $_SESSION[self::SESSION_KEY];
        }

        /**
         * True when the request carried the right token.
         *
         * Compared with hash_equals so the check cannot be narrowed down by
         * timing one guess against another.
         */
        public static function check(?string $candidate): bool
        {
            Auth::startSession();

            $expected = $_SESSION[self::SESSION_KEY] ?? '';

            if ($expected === '' || $candidate === null || $candidate === '') {
                return false;
            }

            return hash_equals($expected, $candidate);
        }

        /** The token as the browser sent it: header first, then form field. */
        public static function fromRequest(array $body = []): ?string
        {
            $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (is_string($header) && $header !== '') return $header;

            foreach ([$_POST['csrf_token'] ?? null, $body['csrf_token'] ?? null] as $candidate) {
                if (is_string($candidate) && $candidate !== '') return $candidate;
            }

            return null;
        }

        /** Meta tag for the layout, so the front end can read the token. */
        public static function metaTag(): string
        {
            return '<meta name="csrf-token" content="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
        }
    }
?>
