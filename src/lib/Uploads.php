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

            return (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
        }

        /** Plain-English version of PHP's numeric upload error codes. */
        public static function errorMessage(int $code): string
        {
            return match ($code) {
                UPLOAD_ERR_OK         => '',
                UPLOAD_ERR_INI_SIZE   => 'Bigger than the server limit (upload_max_filesize = '
                                         . ini_get('upload_max_filesize') . '). Raise it in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'Bigger than the form limit.',
                UPLOAD_ERR_PARTIAL    => 'Only part of the file arrived. The connection dropped mid-upload.',
                UPLOAD_ERR_NO_FILE    => 'No file was sent.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server has no temp folder configured (upload_tmp_dir).',
                UPLOAD_ERR_CANT_WRITE => 'The server could not write the file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
                default               => 'Unknown upload error (code ' . $code . ').',
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

            $directory = Media::gameDirAbsolute($gameId, true);
            if (!is_dir($directory) || !is_writable($directory)) {
                return $fail('The uploads folder is not writable: ' . Media::gameDir($gameId));
            }

            // Random suffix, not time(): two files uploaded in the same second used
            // to overwrite each other.
            $safeName = $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

            if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $safeName)) {
                return $fail('The server could not save the file to disk.');
            }

            return [
                'ok'     => true,
                'stored' => Media::gameDir($gameId) . '/' . $safeName,
                'size'   => self::humanSize((int)($file['size'] ?? 0)),
                'error'  => ''
            ];
        }

        /**
         * Drop an .htaccess into public/uploads so that an uploaded .php file can
         * never be executed by Apache. Cheap, and it closes a real hole.
         */
        public static function protectUploadRoot(): void
        {
            $uploadRoot = Media::root() . '/public/uploads';
            if (!is_dir($uploadRoot)) mkdir($uploadRoot, 0775, true);

            $htaccess = $uploadRoot . '/.htaccess';
            if (is_file($htaccess)) return;

            file_put_contents($htaccess, <<<CONF
            # Uploaded files are data, never code.
            php_flag engine off
            AddType text/plain .php .php3 .php4 .php5 .phtml .pl .py .cgi .asp .sh
            <IfModule mod_headers.c>
                Header set X-Content-Type-Options "nosniff"
            </IfModule>
            CONF);
        }
    }
?>