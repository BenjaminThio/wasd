<?php
    require_once __DIR__ . '/../lib/Media.php';
    $coverUrl = Media::url($game->getImage());
    $status   = $game->getReviewStatus();
?>
<!-- Pass the real database ID into the URL -->
<a class="game-card" href="<?= BASE_URL ?>/game?id=<?= $game->getId() ?>">
    <div class="game-img<?= $coverUrl === '' ? ' ' . htmlspecialchars($game->getFallbackArt()) : '' ?>"
         <?php if ($coverUrl !== ''): ?>
             style="background-image:url('<?= htmlspecialchars($coverUrl, ENT_QUOTES) ?>');background-size:cover;background-position:center;"
         <?php endif; ?>>
        <div class="traffic-light">
            <div class="<?= $status === 0 ? 'red-' : '' ?>light-bulb">
            </div>
            <div class="<?= $status === 1 ? 'orange-' : '' ?>light-bulb">
            </div>
            <div class="<?= $status === 2 ? 'green-' : '' ?>light-bulb">
            </div>
        </div>
        
        <?php if (count($game->getPlatforms()) > 0): ?>
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
        <?php endif; ?>
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
                    require __DIR__ . '/game-tag.php';
                }
            ?>
        </div>
        
        <?php if ($game->getDiscount() > 0): ?>
            <div class="game-price">
                <div>
                    <span class="game-old-price-tag">RM <?= $game->getPrice() ?></span>
                    <span class="game-price-tag">RM <?= $game->getDiscountedPrice() ?></span>
                </div>
                <span style="min-width:3ch;text-align:center;" class="magenta game-tag"><?= $game->getDiscount() ?>%</span>
            </div>
        <?php else: ?>
            <?php $priceText = $game->getPrice() > 0 ? 'RM ' . $game->getPrice() : 'FREE'; ?>
            <div style="font-family:var(--mono-font)" class="game-price-tag">
                <?= $priceText ?>
            </div>
        <?php endif; ?>
    </div>
</a>