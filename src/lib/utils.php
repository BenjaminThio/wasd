<?php
    class Console
    {
        public static function log(mixed $data, string $label = 'PHP Debug')
        {
            $json = json_encode($data);

            echo "<script>console.log('{$label}:', {$json});</script>";
        }
    }
?>