<div class="game-card">
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
    <!--<div style="font-family:Outfit;position:relative;display:flex;justify-content:center;">
        <div style="border-radius:0.8rem;position:absolute;background-color:var(--card-bg);bottom:-0.7rem;padding-left:0.5rem;padding-right:0.5rem;padding-top:0.1rem;padding-bottom:0.1rem;">★★★★★</div>
    </div>-->
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
            <!-- <span class="green game-tag">Adventure</span>
            <span class="magenta game-tag">Battle</span>
            <span class="magenta game-tag">RPG</span>
            <span class="purple game-tag">Shooter</span>
            <span class="blue game-tag">Simulation</span>
            <span class="yellow game-tag">Royale</span> -->
        </div>
        <!--<div style="font-family:JetBrains Mono;color:cyan;">
            RM 520.00
        </div>-->
        <div class="game-price">
            <div>
                <span class="game-old-price-tag"><?= $game->getPrice() ?></span>
                <span class="game-price-tag"><?= $game->getDiscountedPrice() ?></span>
            </div>
            <span class="magenta game-tag"><?= $game->getDiscount() ?></span>
        </div>
    </div>
</div>