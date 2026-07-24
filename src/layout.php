<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title id="page-title"><?= htmlspecialchars($page->getTitle()) ?></title>
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/app/global.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/components/navbar/navbar.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/components/footer/footer.css">
        
        <?php if (!empty($page->getCssUrl())): ?>
            <link id="dynamic-page-style" rel="stylesheet" href="<?= htmlspecialchars($page->getCssUrl()) ?>">
        <?php else: ?>
            <link id="dynamic-page-style" rel="stylesheet" href="">
        <?php endif; ?>
    </head>
    <body style="margin:0;overflow:visible;">
        <?php require __DIR__ . '/components/navbar/navbar.php' ?>

        <!-- The Injection Zone -->
        <main style="display:flex;flex-direction:column;align-items:center;width:100%;min-height:calc(100vh - 180px)" id="app-root">
            <?= $page->getContent()?>
        </main>
        
        <?php require __DIR__ . '/components/footer/footer.php' ?>

        <script>
            window.CURRENT_LAYOUT = "<?= isset($layoutToLoad) ? addslashes($layoutToLoad) : 'unknown' ?>";
        </script>

        <script src="<?= BASE_URL ?>/public/js/spa-router.js"></script>
    </body>
</html>