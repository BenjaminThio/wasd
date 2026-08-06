<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <?php require_once __DIR__ . '/lib/Csrf.php'; ?>
        <?= Csrf::metaTag() ?>
        <title id="page-title"><?= htmlspecialchars($page->getTitle()) ?></title>

        <link rel="preload" as="font" type="font/woff2" crossorigin
              href="<?= BASE_URL ?>/public/fonts/Outfit-Variable.woff2">
        <link rel="preload" as="font" type="font/woff2" crossorigin
              href="<?= BASE_URL ?>/public/fonts/Unbounded-Variable.woff2">

        <?php
            // ?v=<modification time> so an edited stylesheet or script is never
            // served from a stale browser cache.
            $asset = static function (string $relative): string {
                $stamp = @filemtime(dirname(__DIR__) . $relative) ?: 0;
                return BASE_URL . $relative . '?v=' . $stamp;
            };
        ?>

        <link rel="stylesheet" href="<?= $asset('/src/app/global.css') ?>">
        <link rel="stylesheet" href="<?= $asset('/src/app/ui.css') ?>">
        <link rel="stylesheet" href="<?= $asset('/src/components/navbar/navbar.css') ?>">
        <link rel="stylesheet" href="<?= $asset('/src/components/footer/footer.css') ?>">

        <link id="dynamic-page-style" rel="stylesheet"
              href="<?= htmlspecialchars($page->getCssUrl() ?: BASE_URL . '/src/app/blank.css') ?>">

        <script>
            window.BASE_URL = "<?= BASE_URL ?>";
            window.CURRENT_LAYOUT = "<?= isset($layoutToLoad) ? addslashes($layoutToLoad) : 'unknown' ?>";
        </script>

        <!-- Loaded here, not at the end of <body>: page scripts are injected
             inside <main> and run while the document is still parsing, so the
             shared helpers have to exist before them. -->
        <script src="<?= $asset('/public/js/wasd-ui.js') ?>"></script>
    </head>
    <body>
        <?php require __DIR__ . '/components/navbar/navbar.php' ?>

        <!-- The Injection Zone -->
        <main id="app-root" class="app-root">
            <?= $page->getContent() ?>
        </main>

        <?php require __DIR__ . '/components/footer/footer.php' ?>

        <script src="<?= $asset('/public/js/spa-router.js') ?>"></script>
    </body>
</html>
