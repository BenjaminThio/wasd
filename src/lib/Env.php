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

                die('Enviroment variable not found.');
            }
            else
            {
                die('.env file not found.');
            }
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
                die('.env file not found.');
            }
        }

        public function setPath($path): void
        {
            $this->path = $path;
        }
    }
?>