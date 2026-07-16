<?php
    class Console
    {
        public static function log(mixed $data, string $label = 'PHP Debug')
        {
            $json = json_encode($data);

            echo <<<HTML
                <script>console.log("{$label}:", {$json});</script>
            HTML;
        }

        public static function error(mixed $data, string $label = 'PHP Error:')
        {
            $json = json_encode($data);

            echo <<<HTML
                <script>console.error("{$label}", $json);</script>
            HTML;
        }
    }
?>