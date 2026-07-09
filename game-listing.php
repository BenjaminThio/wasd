<link rel="stylesheet" href="./css/game-listing.css">
<main class="main-container">
    <?php
        use Random\Randomizer;
        require_once 'models/Game.php';

        $randomizer = new Randomizer();
        $games = [];

        for ($i = 1; $i <= 11; $i++)
        {
            $games[] = new Game("Game $i", 'This is a game that able to let you feel happy. I have to test the text overflow Ellipsis for awhile.', '', $randomizer->getFloat(0.0, 520.0), []);
        }

        foreach ($games as $game)
        {
            $randomArt = rand(1, 8);
            $randomStatus = rand(0, 2);
            include 'components/game-card.php';
        }
    ?>
</main>