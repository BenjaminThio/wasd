<?php require './models/Icon.php'; ?>
<div class="page">
    <div class="page-header">
        <div>
            <h1>My Wishlist</h1>
        </div>
    </div>
    <div class="main">
        <div class="controls-bar">
            <div class="search-input">
                <input type="text" id="search-name" class="field-input" placeholder="Search by name">
            </div>
            <div class="filter-dropdown">
                <button>Filter ▼</button>
            </div>
            <div class="sort-by-dropdown">
                <span>Sort by: </span>
                <button>On Sale ▼</button>
            </div>
        </div>
        <div class="wishlist">
            <div class="game-card">
                <div class="game-pic">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ9beYfu27OQsFfv8OEM9zxiBq9vw5Z8wcDM3_hfs8syA&s=10" alt="Game Cover">
                </div>
                <div class="game-info">
                    <h3>Game 1</h3>
                    <div class="game-genre">
                        <span class="magenta game-tag">RPG</span>
                    </div>
                    <div class="game-extra">
                        <div class="extra-row">
                            <span class="label">Overall Reviews: </span><span class="value review-negative">Overwhelmingly Negative</span>
                        </div>
                        <div class="extra-row">
                            <span class="label">Release Date: </span><span class="value">13/08/2025</span>
                        </div>
                    </div>
                    <div class="game-platform">
                        <?= Icon::get('windows', 20) ?>
                        <?= Icon::get('apple', 20) ?>
                    </div>
                </div>
                <div class="game-price">
                    <span class="discount">-50%</span>
                    <span class="original">RM52.00</span>
                    <span class="current">RM26.00</span>
                </div>
                <div class="actions">
                    <button class="cart-btn">Add to Cart</button>
                    <button class="remove-btn">Remove</button>
                </div>
            </div>

            <div class="game-card">
                <div class="game-pic">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ9beYfu27OQsFfv8OEM9zxiBq9vw5Z8wcDM3_hfs8syA&s=10" alt="Game Cover">
                </div>
                <div class="game-info">
                    <h3>Game 2</h3>
                    <div class="game-genre">
                        <span class="magenta game-tag">RPG</span>
                    </div>
                    <div class="game-extra">
                        <div class="extra-row">
                            <span class="label">Overall Reviews: </span><span class="value review-mixed">Mixed</span>
                        </div>
                        <div class="extra-row">
                            <span class="label">Release Date: </span><span class="value">23/05/2026</span>
                        </div>
                    </div>
                    <div class="game-platform">
                        <?= Icon::get('windows', 20) ?>
                        <?= Icon::get('apple', 20) ?>
                    </div>
                </div>
                <div class="game-price">
                    <span class="current">RM152.00</span>
                </div>
                <div class="actions">
                    <button class="cart-btn">Add to Cart</button>
                    <button class="remove-btn">Remove</button>
                </div>
            </div>

            <div class="game-card">
                <div class="game-pic">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ9beYfu27OQsFfv8OEM9zxiBq9vw5Z8wcDM3_hfs8syA&s=10" alt="Game Cover">
                </div>
                <div class="game-info">
                    <h3>Game 3</h3>
                    <div class="game-genre">
                        <span class="magenta game-tag">RPG</span>
                    </div>
                    <div class="game-extra">
                        <div class="extra-row">
                            <span class="label">Overall Reviews: </span><span class="value review-positive">Overwhelmingly Positive</span>
                        </div>
                        <div class="extra-row">
                            <span class="label">Release Date: </span><span class="value">13/03/2026</span>
                        </div>
                    </div>
                    <div class="game-platform">
                        <?= Icon::get('windows', 20) ?>
                        <?= Icon::get('apple', 20) ?>
                    </div>
                </div>
                <div class="game-price">
                    <span class="current">FREE</span>
                </div>
                <div class="actions">
                    <button class="cart-btn">Add to Cart</button>
                    <button class="remove-btn">Remove</button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>