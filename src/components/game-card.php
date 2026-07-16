<a class="game-card" href="<?= BASE_URL ?>/game">
    <div class="game-img art-<?= $randomArt ?>">
        <div class="traffic-light">
            <div class="<?= $randomStatus === 0 ? 'red-' : '' ?>light-bulb">
            </div>
            <div class="<?= $randomStatus === 1 ? 'orange-' : '' ?>light-bulb">
            </div>
            <div class="<?= $randomStatus === 2 ? 'green-' : '' ?>light-bulb">
            </div>
        </div>
        <?php
        if (count($game->getPlatforms()) > 0)
        {
        ?>
            <div style="position:absolute;bottom:0.7rem;left:0.7rem;border:1px solid white;padding:0.2rem;border-radius:0.5rem;display:flex;gap:0.4rem;">
                <?php
                    foreach (Platform::cases() as $case)
                    {
                        if (in_array($case, $game->getPlatforms(), true))
                        {
                            echo Icon::get($case->name);
                        }
                    }
                ?>
            </div>
        <?php
        }
        ?>
    </div>
    <div class="game-info">
        <div class="game-title">
            <?= htmlspecialchars($game->getTitle()) ?>
        </div>
        <div class="game-desc">
            <?= htmlspecialchars($game->getDescription()) ?>
        </div>
        <div style="margin-bottom:0.3rem;" class="game-tags-container">
            <?php
                foreach ($game->getCategories() as $category)
                {
                    include 'game-tag.php';
                }
            ?>
        </div>
        <?php
            if ($game->getDiscount() > 0)
            {
                echo <<<HTML
                    <div class="game-price">
                        <div>
                            <span class="game-old-price-tag">RM {$game->getPrice()}</span>
                            <span class="game-price-tag">RM {$game->getDiscountedPrice()}</span>
                        </div>
                        <span style="min-width:3ch;text-align:center;" class="magenta game-tag">{$game->getDiscount()}%</span>
                    </div>
                HTML;
            }
            else
            {
                $price = $game->getPrice() > 0 ? 'RM ' . $game->getPrice() : 'FREE';

                echo <<<HTML
                    <div style="font-family:var(--mono-font)" class="game-price-tag">
                        {$price}
                    </div>
                HTML;
            }
        ?>
    </div>
</a>