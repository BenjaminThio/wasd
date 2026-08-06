<?php
    require_once __DIR__ . '/Media.php';
    require_once __DIR__ . '/../models/Icon.php';

    /**
     * View
     *
     * Small rendering helpers that were previously copy-pasted into cart,
     * wishlist, checkout and the game page (the platform-icon map alone
     * appeared four times).
     */
    class View
    {
        /** Money is displayed the same way everywhere: RM12.34 */
        public static function money(float $amount): string
        {
            return 'RM' . number_format($amount, 2);
        }

        /** Icon markup keyed by lowercase platform name, for JSON payloads. */
        public static function platformIcons(int $size = 20): array
        {
            static $cache = [];

            if (!isset($cache[$size])) {
                $cache[$size] = [
                    'windows' => Icon::get('windows', $size),
                    'linux'   => Icon::get('linux', $size),
                    'apple'   => Icon::get('apple', $size),
                    'browser' => Icon::get('browser', $size),
                    'android' => Icon::get('android', $size),
                ];
            }

            return $cache[$size];
        }

        /**
         * The one place cover markup is generated on the server. Its JavaScript
         * twin is WASD.cover() in public/js/wasd-ui.js.
         *
         * The generated artwork is always rendered, as its own layer beneath
         * the photo. That way a game with no cover shows its artwork, and a
         * cover that fails to load reveals the artwork instead of a broken
         * image icon - no empty rectangles, no layout shift either way.
         *
         * @param string $classes extra classes for the wrapper, e.g. "game-img"
         * @param string $overlay markup layered on top, e.g. the rating lights
         */
        public static function cover(
            ?string $storedPath,
            string $fallbackArt,
            string $alt,
            string $classes = '',
            string $overlay = ''
        ): string {
            $url = Media::url($storedPath);
            $wrapper = trim('media ' . $classes);
            $art = preg_match('/^art-[1-8]$/', $fallbackArt) ? $fallbackArt : 'art-1';

            $layers = '<span class="media-art ' . $art . '" aria-hidden="true"></span>';

            if ($url !== '') {
                $layers .= '<img class="img-lazy" loading="lazy" decoding="async" src="'
                         . htmlspecialchars($url, ENT_QUOTES) . '" alt="'
                         . htmlspecialchars($alt) . ' cover">';
            }

            $attributes = 'class="' . htmlspecialchars($wrapper) . ($url === '' ? ' is-ready' : '') . '"';
            if ($url === '') {
                $attributes .= ' role="img" aria-label="' . htmlspecialchars($alt) . '"';
            }

            return '<div ' . $attributes . '>' . $layers . $overlay . '</div>';
        }

        /** "1.2K", "3.4M" - used by the stats blocks. */
        public static function compactNumber(int $value): string
        {
            if ($value >= 1000000) return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . 'M';
            if ($value >= 1000)    return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'K';
            return (string)$value;
        }

        /** "3 days ago" for review and build timestamps. */
        public static function timeAgo(?string $timestamp): string
        {
            if (!$timestamp) return 'unknown';

            $then = strtotime($timestamp);
            if (!$then) return 'unknown';

            $seconds = time() - $then;

            if ($seconds < 60)     return 'just now';
            if ($seconds < 3600)   return floor($seconds / 60) . ' min ago';
            if ($seconds < 86400)  return floor($seconds / 3600) . ' hr ago';
            if ($seconds < 2592000) return floor($seconds / 86400) . ' days ago';

            return date('d M Y', $then);
        }
    }
?>
