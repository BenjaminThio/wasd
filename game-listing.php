<!DOCTYPE html>
<html style="font-family: monospace">
    <body style="margin: 0; display: flex; justify-content: center; ;">
        <main style="min-height: calc(100vh - 1rem); width: 75%; display: grid; grid-template-columns: auto auto auto auto auto; padding-top: 1rem; gap: 1rem;">
            <?php
                require_once 'models/Game.php';

                $games = [
                    new Game('Game 1', 'This is game 1.', '', [Platform::Windows]),
                    new Game('Game 2', 'This is idk.', '', [Platform::Windows, Platform::Linux]),
                    new Game('Game 3', 'This is a game.', '', [Platform::Android]),
                    new Game('Game 4', 'This is Benjamin.', '', [Platform::Linux, Platform::MacOS, Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser]),
                    new Game('Game 5', 'Hello Mum!', '', [Platform::Browser])
                ];

                foreach ($games as $game)
                {
                    include 'components/game-card.php';
                }
            ?>
        </main>
    </body>
</html>