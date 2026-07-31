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
            <h1>Checkout</h1>
        </div>
    </div>

    <div class="main" id="checkout-main">
        <div class="cart" id="checkout-list">
            <div class="cart-status" id="checkout-status"></div>
        </div>

        <div class="order">
            <h2>Payment</h2>
            <div class="order-row"><span>Name on Card</span></div>
            <div class="order-row"><input type="text" id="card-name" class="field-input" placeholder="AHMAD BIN ALI"></div>
            <div class="order-row"><span>Card Number</span></div>
            <div class="order-row"><input type="text" id="card-number" class="field-input" placeholder="4111 1111 1111 1111"></div>
            <div class="order-row"><span>Expiry</span></div>
            <div class="order-row"><input type="text" id="card-expiry" class="field-input" placeholder="MM/YY"></div>
            <div class="order-row"><span>CVV</span></div>
            <div class="order-row"><input type="text" id="card-cvv" class="field-input" placeholder="123"></div>

            <div class="order-line"></div>
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
                <span>Total</span>
                <span id="order-subtotal">RM0.00</span>
            </div>
            <button class="checkout-btn" id="pay-btn" onclick="payNow()" disabled>Place Order</button>
            <button class="back-btn" onclick="goToCart()">Back to Cart</button>
        </div>
    </div>

    <div class="main" id="receipt-main" style="display:none">
        <div class="order">
            <h2>Thank you for your purchase!</h2>
            <div class="order-row">
                <span>Games bought</span>
                <span id="paid-count">0 items</span>
            </div>
            <div class="order-row order-total">
                <span>Paid</span>
                <span id="paid-total">RM0.00</span>
            </div>
            <div class="order-line"></div>
            <div class="order-note">Taking you back to the store...</div>
            <button class="checkout-btn" onclick="goToStore()">Continue Browsing Games</button>
        </div>
    </div>
</div>

<script>
(() => {
    const API = '<?= BASE_URL ?>/src/app/api/cart/index.php';
    const STORE = '<?= BASE_URL ?>/store';
    const CART = '<?= BASE_URL ?>/cart';
    const ICONS = <?= json_encode($platformIcons) ?>;
    const LIMIT = 12;

    const list = document.getElementById("checkout-list");
    const statusBox = document.getElementById("checkout-status");
    let offset = 0, loading = false, done = false;

    function money(value) { return "RM" + Number(value).toFixed(2); }

    window.goToCart = function () { window.location.href = CART; };
    window.goToStore = function () { window.location.href = STORE; };

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
        </div>`;
        list.insertAdjacentHTML("beforeend", card);
    }

    function showTotals(totals) {
        document.getElementById("order-count").textContent = totals.items + " items";
        document.getElementById("order-price").textContent = money(totals.price);
        document.getElementById("order-discount").textContent = "-" + money(totals.discount);
        document.getElementById("order-subtotal").textContent = money(totals.subtotal);
        document.getElementById("pay-btn").disabled = (totals.items === 0);
    }

    async function loadMore() {
        if (loading || done) return;
        loading = true;
        const response = await fetch(`${API}?offset=${offset}&limit=${LIMIT}`, { cache: 'no-store' });
        if (response.status === 204) {
            done = true;
            if (offset === 0) {
                statusBox.innerHTML = `<div class="cart-empty">There is nothing to check out.<br><a href="#" onclick="goToStore(); return false;">Browse the store</a> to find a game first.</div>`;
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

    window.payNow = async function () {
        const name = document.getElementById("card-name").value;
        const number = document.getElementById("card-number").value;
        const expiry = document.getElementById("card-expiry").value;
        const cvv = document.getElementById("card-cvv").value;
        if (name === "" || number === "" || expiry === "" || cvv === "") {
            alert("Please fill in all the payment details.");
            return;
        }
        document.getElementById("pay-btn").disabled = true;
        const response = await fetch(API, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ action: "checkout" }) });
        const data = await response.json();
        document.getElementById("paid-count").textContent = data.bought + " items";
        document.getElementById("paid-total").textContent = money(data.paid);
        document.getElementById("checkout-main").style.display = "none";
        document.getElementById("receipt-main").style.display = "";
        setTimeout(goToStore, 3000);
    };

    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 300) loadMore();
    });
    loadMore();
})();
</script>