<?php
    require_once __DIR__ . '/Media.php';

    /**
     * Uploads
     *
     * Every upload in the project goes through here so that failures are reported
     * instead of silently swallowed. The old code did:
     *
     *     if ($_FILES[...]['error'][$i] === UPLOAD_ERR_OK) { ... }
     *
     * ...with no else branch. A 300 MB build hits PHP's 2 MB default limit, the
     * error branch is skipped, the API still answers 200, the page still says
     * "saved successfully", and the build is simply gone. That is the single
     * biggest reason uploads look like they vanish.
     */
    class Uploads
    {
        /** Things a game build is allowed to be. */
        public const BUILD_EXTENSIONS = [
            'zip', 'rar', '7z', 'tar', 'gz', 'tgz',
            'exe', 'msi', 'apk', 'aab', 'dmg', 'pkg',
            'jar', 'love', 'pck', 'x86_64', 'appimage', 'bin'
        ];

        /** Things a screenshot / cover image is allowed to be. */
        public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

        /**
         * True when the whole request was thrown away because it was bigger than
         * post_max_size. PHP gives you an empty $_POST and an empty $_FILES with
         * no warning whatsoever, which looks exactly like "the user submitted
         * nothing" unless you check for it.
         */
        public static function postSizeExceeded(): bool
        {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return false;
            if (!empty($_POST) || !empty($_FILES)) return false;

            // Only form posts populate $_POST. A JSON body legitimately leaves it
            // empty, so without this check every JSON request looked oversized.
            if (!str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data')) {
                return false;
            }

            return (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
        }

        /**
         * What to tell the person uploading.
         *
         * Deliberately says nothing about how the server is configured. The
         * uploader can act on "too big" or "the connection dropped"; naming
         * php.ini directives, temp folders or extensions only tells whoever is
         * probing the site how it is put together. Those details go to the
         * error log, where the person who can act on them will see them.
         */
        public static function errorMessage(int $code): string
        {
            $serverSide = 'The server could not accept that file. Try again in a moment.';

            if (!in_array($code, [UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE,
                                  UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE], true)) {
                error_log('WASD upload failure, PHP code ' . $code
                          . ' (upload_tmp_dir=' . (ini_get('upload_tmp_dir') ?: 'default') . ')');
            }

            return match ($code) {
                UPLOAD_ERR_OK         => '',
                // The size limit is worth stating: it is the one thing the
                // uploader can actually work around.
                UPLOAD_ERR_INI_SIZE   => 'That file is larger than the maximum upload size ('
                                         . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_FORM_SIZE  => 'That file is larger than this form accepts.',
                UPLOAD_ERR_PARTIAL    => 'Only part of the file arrived. The connection dropped mid-upload.',
                UPLOAD_ERR_NO_FILE    => 'No file was sent.',
                default               => $serverSide,
            };
        }

        /** "128.4 MB" instead of 134634701. */
        public static function humanSize(int $bytes): string
        {
            if ($bytes <= 0) return '0 KB';
            if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
            if ($bytes < 1024 * 1024 * 1024) return number_format($bytes / (1024 * 1024), 1) . ' MB';
            return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }

        /**
         * Move one uploaded file into a game's folder.
         *
         * @return array{ok: bool, stored: string, size: string, error: string}
         */
        public static function store(?array $file, int $gameId, string $prefix, array $allowedExtensions): array
        {
            return self::storeIn($file, Media::gameDir($gameId), $prefix, $allowedExtensions);
        }

        /**
         * Move one uploaded file into any folder under the project root, given
         * as a relative path such as "public/uploads/avatars".
         *
         * store() is the game-specific wrapper around this; profile avatars use
         * it directly rather than pretending to belong to a game.
         *
         * @return array{ok: bool, stored: string, size: string, error: string}
         */
        public static function storeIn(?array $file, string $relativeDirectory, string $prefix, array $allowedExtensions): array
        {
            $fail = fn(string $why) => ['ok' => false, 'stored' => '', 'size' => '', 'error' => $why];

            if (!is_array($file) || !isset($file['error'])) {
                return $fail('The file never reached the server.');
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                return $fail(self::errorMessage((int)$file['error']));
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                return $fail('Rejected: not a genuine upload.');
            }

            $originalName = (string)($file['name'] ?? '');
            $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
                return $fail('.' . $extension . ' files are not accepted here. Allowed: '
                             . implode(', ', $allowedExtensions) . '.');
            }

            $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
            $directory = Media::root() . '/' . $relativeDirectory;

            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            if (!is_dir($directory) || !is_writable($directory)) {
                error_log('WASD: upload folder is not writable: ' . $relativeDirectory);
                return $fail('The server could not save that file. Try again in a moment.');
            }

            // Random suffix, not time(): two files uploaded in the same second used
            // to overwrite each other.
            $safeName = $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

            if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $safeName)) {
                return $fail('The server could not save the file to disk.');
            }

            return [
                'ok'     => true,
                'stored' => $relativeDirectory . '/' . $safeName,
                'size'   => self::humanSize((int)($file['size'] ?? 0)),
                'error'  => ''
            ];
        }

        /** Archives a browser-playable build may arrive in. */
        public const WEB_BUILD_EXTENSIONS = ['zip'];

        /**
         * Server-side code must never be written into a play folder, whatever
         * the archive claims to contain.
         */
        private const BLOCKED_IN_WEB_BUILD = [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
            'pl', 'py', 'cgi', 'asp', 'aspx', 'jsp', 'sh', 'bat', 'cmd', 'exe',
            'htaccess'
        ];

        /** A web build bigger than this is refused rather than unpacked. */
        private const WEB_BUILD_MAX_BYTES = 600 * 1024 * 1024;
        private const WEB_BUILD_MAX_FILES = 4000;

        /**
         * Unpack a browser-playable build and report its entry document.
         *
         * The archive comes from a user, so every entry is treated as hostile:
         *
         *  - absolute paths and any ".." segment are refused outright, which is
         *    what stops a crafted zip writing outside the play folder
         *    ("zip slip");
         *  - server-executable extensions are skipped;
         *  - the uncompressed size and file count are capped, so a small
         *    archive cannot expand into a full disk.
         *
         * @return array{ok: bool, entry: string, files: int, error: string}
         */
        public static function extractWebBuild(string $archiveAbsolute, string $targetRelative): array
        {
            $fail = fn(string $why) => ['ok' => false, 'entry' => '', 'files' => 0, 'error' => $why];

            if (!class_exists('ZipArchive')) {
                return $fail('This server has no zip support, so browser builds cannot be unpacked.');
            }

            if (!is_file($archiveAbsolute)) {
                return $fail('The uploaded archive is missing from disk.');
            }

            $zip = new ZipArchive();
            if ($zip->open($archiveAbsolute) !== true) {
                return $fail('That file is not a readable .zip archive.');
            }

            if ($zip->numFiles > self::WEB_BUILD_MAX_FILES) {
                $zip->close();
                return $fail('That archive holds more than ' . self::WEB_BUILD_MAX_FILES . ' files.');
            }

            $targetRelative = trim(str_replace('\\', '/', $targetRelative), '/');
            $targetAbsolute = Media::root() . '/' . $targetRelative;

            self::deleteDirectory($targetAbsolute);
            if (!mkdir($targetAbsolute, 0775, true) && !is_dir($targetAbsolute)) {
                $zip->close();
                return $fail('Could not create the play folder on disk.');
            }

            $written = 0;
            $bytes = 0;
            $candidates = [];
            $skipped = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if ($stat === false) continue;

                $name = str_replace('\\', '/', $stat['name']);

                // Directory entries are created implicitly by the files in them.
                if (str_ends_with($name, '/')) continue;

                // Zip slip: anything that tries to escape the play folder.
                if (str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name)) {
                    $skipped++;
                    continue;
                }

                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($extension, self::BLOCKED_IN_WEB_BUILD, true) || basename($name) === '.htaccess') {
                    $skipped++;
                    continue;
                }

                $bytes += (int)$stat['size'];
                if ($bytes > self::WEB_BUILD_MAX_BYTES) {
                    $zip->close();
                    self::deleteDirectory($targetAbsolute);
                    return $fail('That build unpacks to more than '
                                 . self::humanSize(self::WEB_BUILD_MAX_BYTES) . '.');
                }

                $destination = $targetAbsolute . '/' . $name;
                $directory = dirname($destination);

                if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                    $skipped++;
                    continue;
                }

                // Belt and braces: the resolved parent must still be inside the
                // play folder after the filesystem has had its say.
                $realDirectory = realpath($directory);
                if ($realDirectory === false ||
                    !str_starts_with(str_replace('\\', '/', $realDirectory), realpath($targetAbsolute) ? str_replace('\\', '/', realpath($targetAbsolute)) : $targetAbsolute)) {
                    $skipped++;
                    continue;
                }

                $stream = $zip->getStream($stat['name']);
                if (!$stream) {
                    $skipped++;
                    continue;
                }

                $out = fopen($destination, 'wb');
                if ($out) {
                    stream_copy_to_stream($stream, $out);
                    fclose($out);
                    $written++;

                    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'html' ||
                        strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'htm') {
                        $candidates[] = $name;
                    }
                }
                fclose($stream);
            }

            $zip->close();

            if ($written === 0) {
                self::deleteDirectory($targetAbsolute);
                return $fail('Nothing usable was found inside that archive.');
            }

            $entry = self::pickEntryDocument($candidates);

            if ($entry === '') {
                self::deleteDirectory($targetAbsolute);
                return $fail('No index.html was found in that archive. A browser build needs one.');
            }

            self::protectPlayDirectory($targetAbsolute);

            return [
                'ok' => true,
                'entry' => $targetRelative . '/' . $entry,
                'files' => $written,
                'error' => $skipped > 0 ? $skipped . ' unsafe entries were skipped.' : ''
            ];
        }

        /**
         * index.html at the top wins; otherwise the shallowest index.html;
         * otherwise the shallowest .html of any name.
         */
        private static function pickEntryDocument(array $candidates): string
        {
            if (empty($candidates)) return '';

            $depth = fn(string $path) => substr_count($path, '/');

            $indexes = array_values(array_filter(
                $candidates,
                fn(string $path) => strtolower(basename($path)) === 'index.html'
                                 || strtolower(basename($path)) === 'index.htm'
            ));

            $pool = !empty($indexes) ? $indexes : $candidates;
            usort($pool, fn($a, $b) => $depth($a) <=> $depth($b) ?: strcmp($a, $b));

            return $pool[0];
        }

        /**
         * The uploads root refuses a list of extensions so nobody can grab a
         * paid build by URL. A web build legitimately ships some of them
         * (.pck for Godot, .bin and .data for others), so the play folder
         * grants them back - while keeping script execution off.
         */
        /**
         * Bumped whenever the generated .htaccess changes, so folders unpacked
         * by an older version can be spotted and repaired.
         */
        private const PLAY_HEADERS_VERSION = 'v2';

        /**
         * Rewrite a play folder's .htaccess if it is missing or was written by
         * an earlier version.
         *
         * Without this, a build unpacked before the cross-origin headers
         * existed would keep serving the old configuration until its developer
         * happened to save the project again - which looks exactly like the
         * feature being broken.
         */
        public static function ensurePlayDirectoryProtected(string $directoryAbsolute): void
        {
            $htaccess = $directoryAbsolute . '/.htaccess';

            if (is_file($htaccess)) {
                $current = @file_get_contents($htaccess) ?: '';
                if (str_contains($current, 'wasd-play-headers: ' . self::PLAY_HEADERS_VERSION)) {
                    return;
                }
            }

            if (is_dir($directoryAbsolute)) {
                self::protectPlayDirectory($directoryAbsolute);
            }
        }

        public static function protectPlayDirectory(string $directoryAbsolute): void
        {
            $version = self::PLAY_HEADERS_VERSION;

            file_put_contents($directoryAbsolute . '/.htaccess', <<<CONF
            # wasd-play-headers: {$version}
            # Unpacked browser build: served as static assets, never executed.
            php_flag engine off
            AddType text/plain .php .php3 .php4 .php5 .phtml .pl .py .cgi .asp .sh

            # Undo the blanket deny from public/uploads/.htaccess: these files
            # are engine data (Godot .pck, Unity .data/.wasm) and must load.
            <FilesMatch ".*">
                Require all granted
            </FilesMatch>

            <IfModule mod_mime.c>
                AddType application/wasm .wasm
                AddType application/octet-stream .pck .data .unityweb .mem
            </IfModule>

            <IfModule mod_headers.c>
                Header set X-Content-Type-Options "nosniff"

                # Cross-origin isolation.
                #
                # Godot (and Unity with threads) needs SharedArrayBuffer, which
                # browsers only expose to a cross-origin-isolated document: COOP
                # and COEP here, matching headers on the embedding page, and
                # CORP on every subresource the build loads.
                Header set Cross-Origin-Opener-Policy "same-origin"
                Header set Cross-Origin-Embedder-Policy "require-corp"

                # "cross-origin", not "same-origin", and deliberately so.
                #
                # The player iframe is sandboxed without allow-same-origin, so
                # the build runs on an OPAQUE origin - it cannot read cookies or
                # call the API as the signed-in player. But an opaque origin is
                # not the site's origin, so a same-origin CORP would reject the
                # build's own .js, .wasm and .pck files and nothing would load.
                #
                # This relaxation applies only to files inside an unpacked
                # build. The site's own assets keep CORP: same-origin, and paid
                # archives stay denied by public/uploads/.htaccess.
                Header set Cross-Origin-Resource-Policy "cross-origin"
            </IfModule>
            CONF);
        }

        /** Recursively remove a folder. Used when a play build is replaced. */
        public static function deleteDirectory(string $directory): void
        {
            if (!is_dir($directory)) return;

            foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
                $path = $directory . '/' . $entry;
                is_dir($path) ? self::deleteDirectory($path) : @unlink($path);
            }

            @rmdir($directory);
        }

        /** Relative folder that holds one build's unpacked web version. */
        public static function playDir(int $gameId, int $buildId): string
        {
            return Media::gameDir($gameId) . '/play_' . $buildId;
        }

        /**
         * The play folder a stored entry path belongs to.
         *
         * Engines commonly zip their export inside a folder of its own, so the
         * entry can be several levels down - dirname() would land on that inner
         * folder rather than the one whose configuration governs the build.
         *
         *   public/uploads/games/18/play_27/My Game/index.html
         *                          -> public/uploads/games/18/play_27
         */
        public static function playRoot(?string $storedPlayPath): ?string
        {
            $stored = Media::store((string)$storedPlayPath);

            return preg_match('#^(.*/play_\d+)(/|$)#', $stored, $match) ? $match[1] : null;
        }

        /**
         * Drop an .htaccess into public/uploads so that:
         *
         *  1. an uploaded .php file can never be executed by Apache, and
         *  2. build files cannot be fetched by guessing their URL.
         *
         * Point 2 matters now that paid games exist: without it, anyone who
         * knew the path could download a build they never bought. Builds are
         * served only by src/app/api/download, which checks ownership first.
         * Images stay directly accessible because covers and screenshots are
         * loaded straight from <img> tags.
         */
        public static function protectUploadRoot(): void
        {
            $uploadRoot = Media::root() . '/public/uploads';
            if (!is_dir($uploadRoot)) mkdir($uploadRoot, 0775, true);

            $htaccess = $uploadRoot . '/.htaccess';

            $contents = <<<CONF
            # Uploaded files are data, never code.
            php_flag engine off
            AddType text/plain .php .php3 .php4 .php5 .phtml .pl .py .cgi .asp .sh
            <IfModule mod_headers.c>
                Header set X-Content-Type-Options "nosniff"
            </IfModule>

            # Builds are downloaded through src/app/api/download, which verifies
            # that the player owns the game. Direct hits are refused.
            <FilesMatch "\.(zip|rar|7z|tar|gz|tgz|exe|msi|apk|aab|dmg|pkg|jar|love|pck|appimage|bin|x86_64)$">
                Require all denied
            </FilesMatch>
            CONF;

            if (is_file($htaccess) && trim(file_get_contents($htaccess)) === trim($contents)) {
                return;
            }

            file_put_contents($htaccess, $contents);
        }
    }
?>