<?php 
require_once __DIR__ . '/../../models/Icon.php'; 
require_once __DIR__ . '/../../models/Games.php'; 

$gameId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$gameId) {
    echo "<div class='main-container'><h1>Game ID not provided.</h1></div>";
    exit;
}

$game = Games::getById($gameId);

if (!$game) {
    echo "<div class='main-container'><h1>This game does not exist in the database.</h1></div>";
    exit;
}

$reviews = $game->getReviews();
$reviewCount = count($reviews);
$reviewStatus = $game->getReviewStatus();

$ratingText = match($reviewStatus) {
    2 => 'Overwhelmingly Positive',
    0 => 'Mostly Negative',
    default => 'Mixed'
};
if ($reviewCount === 0) $ratingText = 'No ratings yet';

$randomArt = rand(1, 8);
?>

<div class="main-container">
    <h1 class="game-name"><?= htmlspecialchars($game->getTitle()) ?></h1>

    <div class="basic-info-table">
        <div class="basic-info-column">
            <div class="basic-info-title-cell">DEVELOPER</div>
            <div class="basic-info-cell"><?= htmlspecialchars($game->getDeveloper()) ?></div>
        </div>
        <div class="basic-info-column">
            <div class="basic-info-title-cell">RELEASED</div>
            <div class="basic-info-cell"><?= htmlspecialchars($game->getFormattedReleaseDate()) ?></div>
        </div>
        <div class="basic-info-column">
            <div class="basic-info-title-cell">PLAYER RATING</div>
            <div class="basic-info-cell">
                <?= htmlspecialchars($ratingText) ?> 
                <?php if ($reviewCount > 0): ?>
                    (<?= $reviewCount ?> <?= $reviewCount === 1 ? 'review' : 'reviews' ?>)
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="game-info-container">
        <div class="game-info-left">
            <?php if (!empty($game->getImage())): ?>
                <div class="game-image" style="background-image: url('<?= htmlspecialchars($game->getImage()) ?>'); background-size: cover; background-position: center;"></div>
            <?php else: ?>
                <div class="game-image art-<?= $randomArt ?>"></div>
            <?php endif; ?>

            <div class="game-about-container">
                <h2 class="game-about-title">About this game</h2>
                <div class="game-about-description">
                    <?= nl2br(htmlspecialchars($game->getDescription())) ?>
                </div>
            </div>
            <div class="more-info-container">MORE INFORMATION</div>
        </div>

        <div class="get-the-game-container">
            <div class="get-the-game-container-title">GET THE GAME</div>
            <?php if ($game->getDiscount() > 0): ?>
                <div style="display: flex; align-items: baseline; gap: 0.8rem;">
                    <h1 class="get-the-game-container-price">RM <?= number_format($game->getDiscountedPrice(), 2) ?></h1>
                    <span style="text-decoration: line-through; color: var(--dim); font-size: 14px;">RM <?= number_format($game->getPrice(), 2) ?></span>
                    <span class="magenta game-tag">-<?= $game->getDiscount() ?>%</span>
                </div>
            <?php else: ?>
                <h1 class="get-the-game-container-price">
                    <?= $game->getPrice() > 0 ? 'RM ' . number_format($game->getPrice(), 2) : 'FREE' ?>
                </h1>
            <?php endif; ?>

            <button onclick="addToAccount('cart')" class="get-the-game-container-button" style="cursor: pointer; background: var(--violet); color: white; border: none;">
                Add to cart
            </button>
            <button onclick="addToAccount('wishlist')" class="get-the-game-container-button" style="cursor: pointer; background: transparent; color: white; border: 1px solid var(--stroke);">
                Add to wishlist
            </button>
        </div>
    </div>

    <!-- Review Section -->
    <div class="review-section-container">
        <h2 class="review-section-header">Review Section</h2>
        
        <div class="review-container">
            <div class="review-title">Write a review</div>
            <div class="review-rating-container">
                <div class="review-rating-title">YOUR RATING</div>
                <div class="review-rating">
                    <div id="btn-up" class="thumbs-up-button" style="cursor: pointer;" onclick="setRating(true)">
                        <?= Icon::get('thumbs-up', 21) ?>
                    </div>
                    <div id="btn-down" class="thumbs-down-button" style="cursor: pointer;" onclick="setRating(false)">
                        <?= Icon::get('thumbs-down', 21) ?>
                    </div>
                </div>
            </div>
            <div class="review-memo-field-container">
                <div class="review-memo-field-container-title">YOUR REVIEW</div>
                <textarea id="review-text" placeholder="Please type something about how you feel about the game." class="review-memo-field"></textarea>
            </div>
            <button onclick="publishReview()" style="width:100%;font-family:'Outfit';font-weight:600;font-size:15px;padding:0.7rem;border-radius:0.5rem;cursor:pointer;background:cyan;border:none;">
                Publish Review
            </button>
        </div>

        <!-- The Infinite Review List -->
        <div id="review-list" style="display:flex;flex-direction:column;gap:1rem;margin-top:2rem;">
            <!-- Chunks are injected here by JS -->
        </div>

        <div id="review-scroll-anchor" style="padding: 2rem; text-align: center; font-family: Outfit; color: var(--dim);">
            Loading reviews...
        </div>
    </div>
</div>

<script>
(() => {
    const gameId = <?= $gameId ?>;
    let selectedRating = null;

    // UI Toggle for Thumbs Up / Down
    window.setRating = function(isPositive) {
        selectedRating = isPositive;
        const upBtn = document.getElementById('btn-up');
        const downBtn = document.getElementById('btn-down');

        if (isPositive) {
            upBtn.style.background = 'rgba(52, 211, 153, 0.2)';
            upBtn.style.borderColor = 'var(--green)';
            upBtn.style.color = 'var(--green)';
            
            downBtn.style.background = 'transparent';
            downBtn.style.borderColor = 'var(--stroke)';
            downBtn.style.color = 'var(--violet)';
        } else {
            downBtn.style.background = 'rgba(255, 45, 118, 0.2)';
            downBtn.style.borderColor = 'var(--magenta)';
            downBtn.style.color = 'var(--magenta)';

            upBtn.style.background = 'transparent';
            upBtn.style.borderColor = 'var(--stroke)';
            upBtn.style.color = 'var(--violet)';
        }
    };

    // Publish Review API Call
    window.publishReview = async function() {
        const text = document.getElementById('review-text').value;
        if (selectedRating === null) return alert("Please select a thumbs up or thumbs down!");
        if (text.trim() === '') return alert("Please write a review first!");

        const res = await fetch(`<?= BASE_URL ?>/src/app/api/reviews/index.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ game_id: gameId, enjoy: selectedRating, description: text })
        });

        if (res.ok) {
            alert("Review published!");
            window.location.reload();
        } else {
            alert("Please sign in to publish a review.");
        }
    };

    // Cart & Wishlist API Call
    window.addToAccount = async function(endpoint) {
        const res = await fetch(`<?= BASE_URL ?>/src/app/api/${endpoint}/index.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ game_id: gameId })
        });
        
        if (res.ok) alert(`Successfully added to ${endpoint}!`);
        else alert(`You must be logged in to add to ${endpoint}.`);
    };

    // Infinite Review Scroller Engine
    let reviewOffset = 0;
    let isReviewLoading = false;
    
    async function loadMoreReviews() {
        if (isReviewLoading) return;
        isReviewLoading = true;
        
        const anchor = document.getElementById('review-scroll-anchor');

        try {
            const response = await fetch(`<?= BASE_URL ?>/src/app/api/reviews/index.php?game_id=${gameId}&limit=5&offset=${reviewOffset}`);
            
            if (response.status === 204) {
                reviewObserver.disconnect();
                anchor.innerHTML = reviewOffset === 0 ? "There is no review yet. Be the first one to comment :D" : "No more reviews to load.";
                anchor.style.border = reviewOffset === 0 ? "1px dashed var(--stroke)" : "none";
                return;
            }

            const html = await response.text();
            document.getElementById('review-list').insertAdjacentHTML('beforeend', html);
            reviewOffset += 5;
        } catch (error) {
            console.error(error);
        } finally {
            isReviewLoading = false;
        }
    }

    const reviewObserver = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) loadMoreReviews();
    });

    // Start tracking the bottom of the page
    reviewObserver.observe(document.getElementById('review-scroll-anchor'));
})();
</script>