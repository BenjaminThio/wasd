<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title id="page-title"><?= htmlspecialchars($page->getTitle()) ?></title>
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/components/navbar/navbar.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/components/footer/footer.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/app/global.css">
        
        <?php if (!empty($page->getCssUrl())): ?>
            <link id="dynamic-page-style" rel="stylesheet" href="<?= htmlspecialchars($page->getCssUrl()) ?>">
        <?php else: ?>
            <link id="dynamic-page-style" rel="stylesheet" href="">
        <?php endif; ?>
    </head>
    <body style="margin:0;">
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
            window.CURRENT_LAYOUT = "<?= isset($layoutToLoad) ? addslashes($layoutToLoad) : 'unknown' ?>";
        </script>

        <script src="<?= BASE_URL ?>/public/js/spa-router.js"></script>
    </body>
</html>