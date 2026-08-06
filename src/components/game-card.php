<?php
    /**
     * Store / library grid tile.
     *
     * Expects $game (Game). Optionally $ownedIds (int[]) so the card can show
     * an "owned" flag without asking the database per card.
     *
     * The cover comes from View::cover(), which always lays the generated
     * fallback artwork behind the photo - a game with no cover shows its art,
     * and a cover that fails to load reveals the art rather than a broken icon.
     */
    require_once __DIR__ . '/../lib/Media.php';
    require_once __DIR__ . '/../lib/View.php';

    $cardStatus = $game->getReviewStatus();
    $cardOwned  = isset($ownedIds) && in_array((int)$game->getId(), $ownedIds, true);

    // Everything layered on top of the cover: rating lights, owned flag, platforms.
    ob_start();
?>
    <div class="traffic-light" title="<?= htmlspecialchars($game->getReviewLabel()) ?>">
        <div class="<?= $cardStatus === 0 ? 'red-' : '' ?>light-bulb"></div>
        <div class="<?= $cardStatus === 1 ? 'orange-' : '' ?>light-bulb"></div>
        <div class="<?= $cardStatus === 2 ? 'green-' : '' ?>light-bulb"></div>
    </div>

    <?php if ($cardOwned): ?>
        <span class="game-owned-flag"><?= Icon::get('check', 13) ?> OWNED</span>
    <?php endif; ?>

    <?php if (count($game->getPlatforms()) > 0): ?>
        <div class="platform-strip">
            <?php foreach (Platform::cases() as $case): ?>
                <?php if (in_array($case, $game->getPlatforms(), true)) echo Icon::get($case->name, 16); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php
    $cardOverlay = ob_get_clean();
?>
<a class="game-card" href="<?= BASE_URL ?>/game?id=<?= (int)$game->getId() ?>">
    <?= View::cover($game->getImage(), $game->getFallbackArt(), $game->getTitle(), 'game-img', $cardOverlay) ?>

    <div class="game-info">
        <div class="game-title"><?= htmlspecialchars($game->getTitle()) ?></div>
        <div class="game-desc"><?= htmlspecialchars($game->getDescription()) ?></div>

        <div class="game-tags-container">
            <?php foreach ($game->getCategories() as $category): ?>
                <?php require __DIR__ . '/game-tag.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($game->getDiscount() > 0): ?>
            <div class="game-price">
                <div>
                    <span class="game-old-price-tag"><?= View::money($game->getPrice()) ?></span>
                    <span class="game-price-tag"><?= View::money($game->getDiscountedPrice()) ?></span>
                </div>
                <span class="magenta game-tag">-<?= $game->getDiscount() ?>%</span>
            </div>
        <?php else: ?>
            <div class="game-price">
                <span class="game-price-tag">
                    <?= $game->getPrice() > 0 ? View::money($game->getPrice()) : 'FREE' ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</a>
