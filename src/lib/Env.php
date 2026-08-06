<?php
    class Env
    {
        private static string $path = __DIR__ . '/../../.env';

        public static function load(string $variableName): string
        {
            if (file_exists(self::$path))
            {
                $lines = file(self::$path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($lines as $line)
                {
                    if (str_starts_with(trim($line), '#'))
                    {
                        continue;
                    }

                    [$key, $value] = explode('=', trim($line), 2);

                    if (trim($key) === $variableName)
                    {
                        return trim($value);
                    }
                }

                self::halt('environment variable "' . $variableName . '" is not set');
            }
            else
            {
                self::halt('.env file is missing');
            }
        }

        /**
         * A variable that may legitimately be absent.
         *
         * load() halts the request when a key is missing, which is right for
         * the database credentials and wrong for optional settings - a missing
         * APP_DEBUG should mean "off", not a blank page.
         */
        public static function get(string $variableName, string $default = ''): string
        {
            if (!file_exists(self::$path)) {
                return $default;
            }

            $lines = file(self::$path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);

                if (trim($key) === $variableName) {
                    return trim($value);
                }
            }

            return $default;
        }

        public static function loadAll(): array
        {
            if (file_exists(self::$path))
            {
                $lines = file(self::$path);
                $_ENV = [];

                foreach ($lines as $line)
                {
                    if (str_starts_with(trim($line), '#'))
                    {
                        continue;
                    }

                    [$key, $value] = explode('=', trim($line), 2);
                    
                    $_ENV[trim($key)] = trim($value);
                }

                return $_ENV;
            }
            else
            {
                self::halt('.env file is missing');
            }
        }

        /**
         * Stop the request without describing the server's setup to a visitor.
         *
         * The old messages printed ".env file not found." straight into the
         * page, which tells anyone reading it how the app is configured and
         * that a config file is missing - an invitation to keep poking. The
         * reason is logged instead.
         */
        private static function halt(string $reason): never
        {
            error_log('WASD configuration error: ' . $reason);

            if (!headers_sent()) {
                http_response_code(500);
            }

            exit(defined('APP_DEBUG') && APP_DEBUG
                ? 'Configuration error: ' . $reason
                : 'The server is not configured correctly. Please try again later.');
        }

        public function setPath($path): void
        {
            $this->path = $path;
        }
    }
?>