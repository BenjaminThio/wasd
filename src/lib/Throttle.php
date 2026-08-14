<?php
    require_once __DIR__ . '/Auth.php';

    /**
     * Throttle
     *
     * Limits how many times an action can be attempted in a window of time.
     *
     * The sign-in endpoint is the reason this exists. Without it, the form is
     * an open invitation to work through a password list: every guess costs an
     * attacker one HTTP request and tells them whether it was right. Bcrypt
     * makes each guess slow to verify, which helps, but nothing stops them
     * trying all night.
     *
     * Attempts are counted in the session, which is the honest limitation
     * here: clearing cookies resets the count, so this raises the cost of an
     * online guessing attack rather than making it impossible. Counting by IP
     * address in a shared table would survive that, and is the natural next
     * step for a deployment that needs it.
     */
    class Throttle
    {
        private const KEY = 'throttle';

        /**
         * True when the caller has already used up its allowance.
         *
         * Records nothing: a blocked attempt should not extend its own
         * lockout, or a script hammering the endpoint would keep the door shut
         * on the real user indefinitely.
         */
        public static function isBlocked(string $action, int $limit, int $seconds): bool
        {
            return self::countRecent($action, $seconds) >= $limit;
        }

        /** Records one failed attempt. Successes should not call this. */
        public static function record(string $action): void
        {
            Auth::startSession();

            $_SESSION[self::KEY][$action][] = time();
        }

        /** Wipes the history for an action, called after a success. */
        public static function clear(string $action): void
        {
            Auth::startSession();

            unset($_SESSION[self::KEY][$action]);
        }

        /** Seconds until the oldest attempt in the window ages out. */
        public static function retryAfter(string $action, int $seconds): int
        {
            Auth::startSession();

            $attempts = $_SESSION[self::KEY][$action] ?? [];
            if (!$attempts) {
                return 0;
            }

            return max(1, min($attempts) + $seconds - time());
        }

        /**
         * Attempts inside the window, dropping the ones that have expired.
         *
         * Pruning on read keeps the session from growing without limit for
         * somebody who mistypes their password once a week for a year.
         */
        private static function countRecent(string $action, int $seconds): int
        {
            Auth::startSession();

            $cutoff = time() - $seconds;

            $attempts = array_values(array_filter(
                $_SESSION[self::KEY][$action] ?? [],
                static fn (int $at): bool => $at > $cutoff
            ));

            $_SESSION[self::KEY][$action] = $attempts;

            return count($attempts);
        }
    }
?>
