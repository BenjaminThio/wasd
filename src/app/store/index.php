<main style="display:flex;flex-direction:column;gap:2rem;">
    <div style="display:flex;gap:1rem;font-family:Outfit;background-color:rgba(255, 255, 255, 0.05);padding:1rem;border-radius:1rem;border:1px solid var(--stroke)">
        <input type="text" placeholder="test" style="flex:1;background-color:var(--bg);border:none;outline:none;padding:0.5rem;border:1px solid var(--stroke);border-radius:0.5rem;color:white;">
        <select style="font-family:monospace;min-width:9rem;padding-left:0.5rem;padding-right:0.5rem;border-radius:0.5rem;">
            <option>Test</option>
            <option>Test</option>
        </select>
        <select style="font-family:monospace;min-width:9rem;padding-left:0.5rem;padding-right:0.5rem;border-radius:0.5rem;">
            <option>Test</option>
            <option>Test</option>
        </select>
        <button style="min-width:5rem;border-radius:0.5rem;">
            Test
        </button>
    </div>
    <div class="main-container">
        <?php
            use Random\Randomizer;
            require_once './models/Game.php';
            require_once './models/Icon.php';
            require_once './models/Category.php';
            require_once './lib/utils.php';

            $randomizer = new Randomizer();
            $games = [
                new Game('Testing', 'Testing', 0, 0, '', [], [])
            ];

            for ($i = 1; $i <= 11; $i++)
            {
                $randomPrice = $randomizer->getFloat(0.0, 520.0);
                $randomDiscount = rand(1, 100);

                $platforms = Platform::cases();
                shuffle($platforms);
                $randomSupportedPlatformQuantity = rand(0, count($platforms));
                $randomSupportedPlatforms = array_slice($platforms, 0, $randomSupportedPlatformQuantity);

                $games[] = new Game(
                    "Game $i",
                    'This is a game that able to let you feel happy. I have to test the text overflow Ellipsis for awhile.',
                    $randomPrice,
                    $randomDiscount,
                    '',
                    $randomSupportedPlatforms,
                    [
                        new Category('RPG', CategoryColor::Magenta)
                    ]
                );
            }

            foreach ($games as $game)
            {
                $randomArt = rand(1, 8);
                $randomStatus = rand(0, 2);

                include './components/game-card.php';
            }
        ?>
    </div>
</main>