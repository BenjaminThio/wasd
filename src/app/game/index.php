<?php require_once __DIR__ . '/../../models/Icon.php'; ?>
<div class="main-container">
    <h1 class="game-name">
        Emberfall
    </h1>
    <div class="basic-info-table">
        <div class="basic-info-column">
            <div class="basic-info-title-cell">DEVELOPER</div>
            <div class="basic-info-cell">Benjamin Thio</div>
        </div>
        <div class="basic-info-column">
            <div class="basic-info-title-cell">RELEASED</div>
            <div class="basic-info-cell">7/7/2026</div>
        </div>
        <div class="basic-info-column">
            <div class="basic-info-title-cell">PLAYER RATING</div>
            <div class="basic-info-cell">Overwhemingly Positive (99 reviews)</div>
        </div>
    </div>
    <div class="game-info-container">
        <div class="game-info-left">
            <div class="game-image art-1">
            </div>
            <div class="game-about-container">
                <h2 class="game-about-title">
                    About this game
                </h2>
                <div class="game-about-description">
                    Nita, a rural flower, decides to travel to Phnom Penh for a short period to help sell night-time Num Banh Chok (Khmer rice noodles) on behalf of her aunt, who is preoccupied with other matters. Her goal is simply to earn some extra income to support her livelihood for a while.  
                    However, from the very first day she steps foot in the capital city, a multitude of bizarre and ominous occurrences begin to plague and torment the young woman, throwing "Village One", the very place where she sells her night noodles to feed nocturnal wanderers into utter chaos.  
                    As the old saying goes: “They are in the dark, while we are in the light…”
                </div>
            </div>
            <div class="more-info-container">
                MORE INFORMATION
            </div>
        </div>
        <div class="get-the-game-container">
            <div class="get-the-game-container-title">
                GET THE GAME
            </div>
            <h1 class="get-the-game-container-price">
                RM 100
            </h1>
            <button class="get-the-game-container-button">
                Add to cart
            </button>
            <button class="get-the-game-container-button">
                Add to wishlist
            </button>
        </div>
    </div>
    <div class="review-section-container">
        <h2 class="review-section-header">
            Review Section
        </h2>
        <div class="review-container">
            <div class="review-title">
                Write a review
            </div>
            <div class="review-rating-container">
                <div class="review-rating-title">
                    YOUR RATING
                </div>
                <div class="review-rating">
                    <div class="thumbs-up-button">
                        <?php
                            echo Icon::get('thumbs-up', 21);
                        ?>
                    </div>
                    <div class="thumbs-down-button">
                        <?php
                            echo Icon::get('thumbs-down', 21);
                        ?>
                    </div>
                </div>
            </div>
            <div class="review-memo-field-container">
                <div class="review-memo-field-container-title">
                    YOUR REVIEW
                </div>
                <textarea placeholder="Please type something about how you feel about the game." class="review-memo-field"></textarea>
            </div>
        </div>
    </div>
</div>