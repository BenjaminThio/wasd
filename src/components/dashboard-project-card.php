<?php
    require_once __DIR__ . '/../lib/Media.php';
    $coverUrl = Media::url($game->getImage());
?>
<div class="project-container" id="project-card-<?= (int)$game->getId() ?>">
    <div class="project-image-container">
        <?php if ($coverUrl !== ''): ?>
            <div class="project-image" style="background-image:url('<?= htmlspecialchars($coverUrl, ENT_QUOTES) ?>');background-size:cover;background-position:center;"></div>
        <?php else: ?>
            <div class="project-image <?= htmlspecialchars($game->getFallbackArt()) ?>"></div>
        <?php endif; ?>
    </div>

    <div class="project-info-container">
        <div class="project-title"><?= htmlspecialchars($game->getTitle()) ?></div>
        <div class="project-status-list">
            <div class="project-status-tag <?= $game->getVisibility() === 'Public' ? 'green' : 'orange' ?>">
                <?= strtoupper(htmlspecialchars($game->getVisibility())) ?>
            </div>
        </div>
    </div>

    <div class="project-action-list">
        <button onclick="window.location.href='<?= BASE_URL ?>/game?id=<?= (int)$game->getId() ?>'"
                class="project-action-button" title="View game page">
            <?= Icon::get('eyes') ?>
        </button>

        <button onclick="window.location.href='<?= BASE_URL ?>/project?id=<?= (int)$game->getId() ?>'"
                class="project-action-button" title="Edit project">
            <?= Icon::get('pencil') ?>
        </button>

        <button onclick="deleteProject(<?= (int)$game->getId() ?>)"
                class="project-action-button delete-button" title="Delete project">
            <?= Icon::get('trash') ?>
        </button>
    </div>
</div>