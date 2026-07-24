<?php
    /**
     * Media
     *
     * One rule for the whole app: the DATABASE only ever stores a relative path,
     * e.g. "public/uploads/games/12/build_a91f3c.zip".
     *
     * It never stores http://localhost/wasd/... , because the moment you rename the
     * folder or move the project to a real server every single row breaks.
     *
     * Use Media::url()      when you need something to put in a browser (src / href / background-image).
     * Use Media::absolute() when you need to touch the real file on disk.
     * Use Media::store()    when you have a value coming back from a form / old row.
     */
    class Media
    {
        /** Filesystem path of the project root (the folder that holds /src and /public). */
        public static function root(): string
        {
            return str_replace('\\', '/', dirname(__DIR__, 2));
        }

        /**
         * Turn a stored path into something the browser can load.
         * Accepts already-absolute URLs and legacy rows that included BASE_URL,
         * so old data keeps working.
         */
        public static function url(?string $stored): string
        {
            $stored = trim((string)$stored);
            if ($stored === '') return '';

            // Already a full URL (legacy rows, or an external image)
            if (preg_match('#^https?://#i', $stored)) return $stored;

            $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            // Legacy row that already had BASE_URL baked in
            if ($base !== '' && str_starts_with($stored, $base . '/')) return $stored;

            return $base . '/' . ltrim($stored, '/');
        }

        /**
         * Reverse of url(): take whatever came back from the form / old DB row and
         * reduce it to the clean relative path we want to store.
         */
        public static function store(?string $value): string
        {
            $value = trim((string)$value);
            if ($value === '') return '';

            // Drop scheme + host if a full URL somehow got submitted
            if (preg_match('#^https?://[^/]+(/.*)$#i', $value, $m)) {
                $value = $m[1];
            }

            $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            if ($base !== '' && str_starts_with($value, $base . '/')) {
                $value = substr($value, strlen($base));
            }

            return ltrim(str_replace('\\', '/', $value), '/');
        }

        /** Stored path -> real path on disk. */
        public static function absolute(string $stored): string
        {
            return self::root() . '/' . ltrim(self::store($stored), '/');
        }

        /** Relative folder that holds every asset for one game. */
        public static function gameDir(int $gameId): string
        {
            return 'public/uploads/games/' . $gameId;
        }

        /** Absolute folder for one game, created on demand. */
        public static function gameDirAbsolute(int $gameId, bool $create = false): string
        {
            $dir = self::root() . '/' . self::gameDir($gameId);
            if ($create && !is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            return $dir;
        }

        /**
         * SECURITY: a path submitted by the browser must live inside this game's own
         * upload folder. Blocks "../../src/config.php" and blocks user A from
         * referencing user B's files.
         */
        public static function belongsToGame(string $stored, int $gameId): bool
        {
            $stored = self::store($stored);
            if ($stored === '' || str_contains($stored, '..')) return false;

            $expected = self::gameDir($gameId) . '/';
            if (!str_starts_with($stored, $expected)) return false;

            // No sub-folders, no traversal: exactly one file name after the folder
            return basename($stored) === substr($stored, strlen($expected));
        }
    }
?>