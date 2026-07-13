<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($page->getTitle()) ?></title>
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/components/navbar/navbar.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/components/footer/footer.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/src/app/global.css">
        <?php
        if (!empty($page->getCssUrl()))
        {
        ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($page->getCssUrl()) ?>">
        <?php 
        }
        ?>
    </head>
    <body style="margin:0;">
        <?php require './components/navbar/navbar.php' ?>
        <?= $page->getContent()?>
        <?php require './components/footer/footer.php' ?>
    </body>
</html>