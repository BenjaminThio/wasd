<?php
    /**
     * One player review. Expects $review (Review).
     *
     * Optionally $reviewViewerId (?int): when it matches the author, the card
     * carries edit and delete controls. The check is repeated on the server for
     * every write, so hiding the buttons is presentation, not security.
     *
     * Rendered both inside the page and by src/app/api/reviews.
     */
    $reviewIsMine = $review->isBy($reviewViewerId ?? null);
?>
<article class="review-card<?= $reviewIsMine ? ' is-mine' : '' ?>"
         data-review-id="<?= (int)$review->getId() ?>">
    <header class="review-card-head">
        <div class="review-card-user">
            <span class="review-avatar">
                <?= strtoupper(htmlspecialchars(substr($review->getUser()->getUsername(), 0, 1))) ?>
            </span>
            <strong><?= htmlspecialchars($review->getUser()->getUsername()) ?></strong>

            <?php if ($review->isEnjoy()): ?>
                <span class="review-verdict is-positive" title="Recommended">
                    <?= Icon::get('thumbs-up', 14) ?>
                </span>
            <?php else: ?>
                <span class="review-verdict is-negative" title="Not recommended">
                    <?= Icon::get('thumbs-down', 14) ?>
                </span>
            <?php endif; ?>

            <?php if ($reviewIsMine): ?>
                <span class="review-mine-flag">Your review</span>
            <?php endif; ?>
        </div>

        <div class="review-card-side">
            <time class="review-card-date"><?= htmlspecialchars($review->getFormattedDate()) ?></time>

            <?php if ($reviewIsMine): ?>
                <div class="review-card-actions">
                    <button type="button" class="review-action" title="Edit your review"
                            onclick="editMyReview(this)">
                        <?= Icon::get('pencil', 14) ?> Edit
                    </button>
                    <button type="button" class="review-action is-danger" title="Delete your review"
                            onclick="deleteMyReview(this)">
                        <?= Icon::get('trash', 14) ?> Delete
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <p class="review-card-body"><?= nl2br(htmlspecialchars($review->getDescription())) ?></p>
</article>
