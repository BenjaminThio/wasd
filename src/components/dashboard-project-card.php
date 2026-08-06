<?php
    /**
     * One row in the developer dashboard. Expects $game (Game).
     */
    require_once __DIR__ . '/../lib/Media.php';
    require_once __DIR__ . '/../lib/View.php';

?>
<div class="project-row" id="project-card-<?= (int)$game->getId() ?>">
    <?= View::cover($game->getImage(), $game->getFallbackArt(), $game->getTitle(), 'project-media') ?>

    <div class="project-info">
        <a class="project-title" href="<?= BASE_URL ?>/game?id=<?= (int)$game->getId() ?>">
            <?= htmlspecialchars($game->getTitle()) ?>
        </a>

        <div class="project-meta">
            <span class="game-tag <?= $game->isPublic() ? 'green' : 'orange' ?>">
                <?= strtoupper(htmlspecialchars($game->getVisibility())) ?>
            </span>
            <span><?= Icon::get('chart', 13) ?> <?= View::compactNumber($game->getViews()) ?> views</span>
            <span><?= Icon::get('download', 13) ?> <?= View::compactNumber($game->getDownloads()) ?> downloads</span>
            <span><?= Icon::get('folder', 13) ?> <?= count($game->getBuilds()) ?> builds</span>
        </div>
    </div>

    <div class="project-actions">
        <a class="btn-icon" href="<?= BASE_URL ?>/game?id=<?= (int)$game->getId() ?>" title="View game page">
            <?= Icon::get('eyes', 17) ?>
        </a>
        <a class="btn-icon" href="<?= BASE_URL ?>/project?id=<?= (int)$game->getId() ?>" title="Edit project">
            <?= Icon::get('pencil', 17) ?>
        </a>
        <button type="button" class="btn-icon is-danger" title="Delete project"
                onclick="deleteProject(<?= (int)$game->getId() ?>)">
            <?= Icon::get('trash', 17) ?>
        </button>
    </div>
</div>
