<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../models/Games.php';
    require_once __DIR__ . '/../../models/Icon.php';
    require_once __DIR__ . '/../../models/Library.php';

    $user = Auth::getCurrentUser();

    if (!$user) {
        echo '<div class="page"><div class="empty-state">'
           . 'Sign in to see the games you own.<br>'
           . '<a href="' . BASE_URL . '/sign-in">Sign in</a></div></div>';
        return;
    }

    $owned = Games::getOwnedChunk($user->getId(), 60, 0);
?>

<div class="page">
    <div class="page-head reveal">
        <div class="flex-col gap-2">
            <span class="eyebrow">Your collection</span>
            <h1 class="page-title">My Library</h1>
            <p class="page-subtitle">
                Everything you have bought, ready to download. Free games are always
                available straight from their store page.
            </p>
        </div>

        <a class="btn btn-ghost" href="<?= BASE_URL ?>/store">
            <?= Icon::get('search', 16) ?> Find more games
        </a>
    </div>

    <?php if (empty($owned)): ?>
        <div class="empty-state reveal">
            You have not bought anything yet.<br>
            <a href="<?= BASE_URL ?>/store">Browse the store</a> to start your collection.
        </div>
    <?php else: ?>
        <div class="game-grid stagger">
            <?php foreach ($owned as $game): ?>
                <?php require __DIR__ . '/../../components/game-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
