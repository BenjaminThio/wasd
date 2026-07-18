<?php
    require __DIR__ . '/../../../models/Games.php';

    $games = new Games();

    Console::log($games->getAll());
?>
<div class="test">
    Testing321
</div>