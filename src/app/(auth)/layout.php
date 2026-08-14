<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <?php require_once __DIR__ . '/../../lib/Csrf.php'; ?>
        <?= Csrf::metaTag() ?>
        <title id="page-title"><?= htmlspecialchars($page->getTitle()) ?></title>

        <?php
            /*
             * ?v=<modification time>, exactly as the main layout does it.
             *
             * These links used to have no version query while the main layout's
             * did, so a browser that had cached navbar.css or footer.css from an
             * earlier visit kept serving those stale copies here - and only
             * here. That is why the header and footer rendered with the old
             * layout (underlined links, a stray menu button on desktop, footer
             * columns colliding with the copyright line) on the auth pages
             * while looking correct everywhere else.
             */
            // This file lives in src/app/(auth), so the project root is three
            // levels up - anything shallower makes filemtime() miss and the
            // stamp fall back to 0, which would never change again.
            $asset = static function (string $relative): string {
                $stamp = @filemtime(dirname(__DIR__, 3) . $relative) ?: 0;
                return BASE_URL . $relative . '?v=' . $stamp;
            };
        ?>

        <!-- Tokens first, then the components that use them - same order as the
             main layout. ui.css is deliberately NOT loaded: its form styles
             would repaint the sign-in / sign-up panel. -->
        <link rel="stylesheet" href="<?= $asset('/src/app/global.css') ?>">
        <link rel="stylesheet" href="<?= $asset('/src/components/navbar/navbar.css') ?>">
        <link rel="stylesheet" href="<?= $asset('/src/components/footer/footer.css') ?>">

        <!-- Everything sign-in and sign-up share. -->
        <link rel="stylesheet" href="<?= $asset('/src/app/(auth)/auth.css') ?>">

        <!-- The page's own stylesheet stays last, so the panel keeps winning. -->
        <link id="dynamic-page-style" rel="stylesheet"
              href="<?= htmlspecialchars($page->getCssUrl() ?: BASE_URL . '/src/app/blank.css') ?>">

        <!--
            Everything sign-in and sign-up share, loaded once by the shell so
            neither page script can collide with the other's declarations when
            the router swaps between them.

            In the head, and deliberately not deferred. The page's own script is
            inline in the body and calls WASDAuth.init() the moment it is
            parsed, so this has to have run by then. Loaded at the end of the
            body instead, init() threw a ReferenceError, no submit handler was
            ever attached, and the form fell back to a native GET that put the
            typed password in the address bar.
        -->
        <script src="<?= $asset('/public/js/wasd-auth.js') ?>"></script>
    </head>
    <body>
        <?php require __DIR__ . '/../../components/navbar/navbar.php' ?>

        <video autoplay muted loop playsinline class="video-bg">
            <source src="<?= BASE_URL ?>/public/assets/auth/bg.mp4" type="video/mp4">
        </video>

        <!-- THE INJECTION ZONE -->
        <main id="app-root" style="width: 100%;">
            <?= $page->getContent()?>
        </main>

        <?php require __DIR__ . '/../../components/footer/footer.php' ?>

        <script>
            window.BASE_URL = "<?= BASE_URL ?>";
            window.CURRENT_LAYOUT = "<?= isset($layoutToLoad) ? addslashes($layoutToLoad) : 'unknown' ?>";
        </script>

        <script src="<?= $asset('/public/js/spa-router.js') ?>"></script>
    </body>
</html>
