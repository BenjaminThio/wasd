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
            <h1>My Cart</h1>
        </div>
    </div>

    <div class="main">
        <div class="cart" id="cart-list">
            <div class="cart-status" id="cart-status"></div>
        </div>

        <div class="order">
            <h2>Order Summary</h2>
            <div class="order-row">
                <span>Items</span>
                <span id="order-count">0 items</span>
            </div>
            <div class="order-row">
                <span>Price</span>
                <span id="order-price">RM0.00</span>
            </div>
            <div class="order-row">
                <span>Sale Discount</span>
                <span class="order-discount" id="order-discount">-RM0.00</span>
            </div>
            <div class="order-line"></div>
            <div class="order-row order-total">
                <span>Subtotal</span>
                <span id="order-subtotal">RM0.00</span>
            </div>
            <button class="checkout-btn" id="checkout-btn" onclick="checkOut()" disabled>Check Out</button>
        </div>
    </div>
</div>

<script>
(() => {
    const API = '<?= BASE_URL ?>/src/app/api/cart/index.php';
    const STORE = '<?= BASE_URL ?>/store';
    const CHECKOUT = '<?= BASE_URL ?>/checkout';
    const ICONS = <?= json_encode($platformIcons) ?>;
    const LIMIT = 12;

    const list = document.getElementById("cart-list");
    const statusBox = document.getElementById("cart-status");
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
        for (let i = 0; i < item.platforms.length; i++) icons += ICONS[item.platforms[i]] || "";

        const card = `<div class="game-card">
            <div class="game-pic">${cover}</div>
            <div class="game-info">
                <h3>${item.title}</h3>
                <div class="game-genre">${tags}</div>
                <div class="game-platform">${icons}</div>
            </div>
            <div class="game-price">${price}</div>
            <div class="actions">
                <button class="remove-btn" onclick="removeItem(${item.id})">Remove</button>
                <button class="wishlist-btn" onclick="moveToWishlist(${item.id})">Move to wishlist</button>
            </div>
        </div>`;
        list.insertAdjacentHTML("beforeend", card);
    }

    function showTotals(totals) {
        document.getElementById("order-count").textContent = totals.items + " items";
        document.getElementById("order-price").textContent = money(totals.price);
        document.getElementById("order-discount").textContent = "-" + money(totals.discount);
        document.getElementById("order-subtotal").textContent = money(totals.subtotal);
        document.getElementById("checkout-btn").disabled = (totals.items === 0);
    }

    async function loadMore() {
        if (loading || done) return;
        loading = true;
        const response = await fetch(`${API}?offset=${offset}&limit=${LIMIT}`, { cache: 'no-store' });
        if (response.status === 204) {
            done = true;
            if (offset === 0) {
                statusBox.innerHTML = `<div class="cart-empty">Your cart is empty.<br><a href="#" onclick="goToStore(); return false;">Browse the store</a> to find your next game.</div>`;
                showTotals({ items: 0, price: 0, discount: 0, subtotal: 0 });
            }
            loading = false;
            return;
        }
        const data = await response.json();
        data.items.forEach(showItem);
        offset += data.items.length;
        showTotals(data.totals);
        statusBox.textContent = "";
        loading = false;
        if (data.items.length < LIMIT) done = true;
        else if (document.documentElement.scrollHeight <= window.innerHeight) loadMore();
    }

    async function sendAction(action, gameId) {
        await fetch(API, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ action: action, game_id: gameId }) });
        reload();
    }

    window.removeItem = (gameId) => sendAction("remove", gameId);
    window.moveToWishlist = (gameId) => sendAction("move-to-wishlist", gameId);
    window.reload = function () {
        list.querySelectorAll(".game-card").forEach(card => card.remove());
        offset = 0; done = false; loadMore();
    };
    window.checkOut = function () { window.location.href = CHECKOUT; };
    window.goToStore = function () { window.location.href = STORE; };

    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 300) loadMore();
    });
    loadMore();
})();
</script>