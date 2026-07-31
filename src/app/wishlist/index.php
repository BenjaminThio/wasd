<?php
require_once './models/Icon.php';
$platformIcons = [
    'windows' => Icon::get('windows', 20),
    'linux' => Icon::get('linux', 20),
    'apple' => Icon::get('apple', 20),
    'browser' => Icon::get('browser', 20),
    'android' => Icon::get('android', 20)
];
?>
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
                <button type="button">Filter ▼</button>
            </div>
            <div class="sort-by-dropdown">
                <span>Sort by:</span>
                <button type="button">On Sale ▼</button>
            </div>
        </div>

        <div class="wishlist" id="wishlist-list"></div>
    </div>
</div>

<script>
(() => {
    const API = '<?= BASE_URL ?>/src/app/api/wishlist/index.php';
    const STORE = '<?= BASE_URL ?>/store';
    const CART = '<?= BASE_URL ?>/cart';
    const ICONS = <?= json_encode($platformIcons) ?>;
    const LIMIT = 12;
    const REVIEW_LABEL = ["Overwhelmingly Negative", "Mixed", "Overwhelmingly Positive"];
    const REVIEW_CSS = ["review-negative", "review-mixed", "review-positive"];

    const list = document.getElementById("wishlist-list");
    let offset = 0, loading = false, done = false;

    function money(value) { return "RM" + Number(value).toFixed(2); }

    function showItem(item) {
        let cover = `<div class="fallback-art ${item.fallback_art}"></div>`;
        if (item.cover) cover = `<img src="${item.cover}" alt="${item.title} Cover">`;

        let price = `<span class="current">${money(item.final_price)}</span>`;
        if (item.discount > 0) {
            price = `<span class="discount">-${item.discount}%</span><span class="original">${money(item.price)}</span>` + price;
        }

        let tags = "";
        for (let i = 0; i < item.categories.length; i++) tags += `<span class="magenta game-tag">${item.categories[i]}</span>`;

        let icons = "";
        for (let i = 0; i < item.platforms.length; i++) icons += ICONS[item.platforms[i]];

        let cartButton = `<button class="cart-btn" id="cart-btn-${item.id}" onclick="addToCart(${item.id})">Add to Cart</button>`;
        if (item.in_cart) cartButton = `<button class="cart-btn in-cart" id="cart-btn-${item.id}" onclick="goToCart()">In Cart</button>`;

        const card = `<div class="game-card" data-title="${item.title}">
            <div class="game-pic">${cover}</div>
            <div class="game-info">
                <h3>${item.title}</h3>
                <div class="game-genre">${tags}</div>
                <div class="game-extra">
                    <div class="extra-row">
                        <span class="label">Overall Reviews: </span>
                        <span class="value ${REVIEW_CSS[item.review_status]}">${REVIEW_LABEL[item.review_status]}</span>
                    </div>
                    <div class="extra-row">
                        <span class="label">Release Date: </span>
                        <span class="value">${item.release_date}</span>
                    </div>
                </div>
                <div class="game-platform">${icons}</div>
            </div>
            <div class="game-price">${price}</div>
            <div class="actions">
                ${cartButton}
                <button class="remove-btn" onclick="removeItem(${item.id})">Remove</button>
            </div>
        </div>`;
        list.insertAdjacentHTML("beforeend", card);
    }

    async function loadMore() {
        if (loading || done) return;
        loading = true;
        const response = await fetch(`${API}?offset=${offset}&limit=${LIMIT}`, { cache: 'no-store' });
        if (response.status === 204) { done = true; loading = false; return; }
        const data = await response.json();
        data.items.forEach(showItem);
        offset += data.items.length;
        loading = false;
        if (data.items.length < LIMIT) done = true;
        else if (document.documentElement.scrollHeight <= window.innerHeight) loadMore();
    }

    async function sendAction(action, gameId) {
        await fetch(API, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ action: action, game_id: gameId }) });
    }

    window.addToCart = async function (gameId) {
        await sendAction("add-to-cart", gameId);
        const button = document.getElementById("cart-btn-" + gameId);
        button.textContent = "In Cart";
        button.classList.add("in-cart");
        button.onclick = goToCart;
    };
    window.removeItem = async function (gameId) { await sendAction("remove", gameId); reload(); };
    window.reload = function () {
        list.querySelectorAll(".game-card").forEach(card => card.remove());
        offset = 0; done = false; loadMore();
    };
    window.goToCart = function () { window.location.href = CART; };
    window.goToStore = function () { window.location.href = STORE; };

    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 300) loadMore();
    });
    loadMore();
})();
</script>