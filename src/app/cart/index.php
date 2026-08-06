<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/View.php';
    require_once __DIR__ . '/../../models/Icon.php';

    if (!Auth::getCurrentUser()) {
        echo '<div class="page"><div class="empty-state">'
           . 'Sign in to see your cart.<br>'
           . '<a href="' . BASE_URL . '/sign-in">Sign in</a></div></div>';
        return;
    }
?>
<div class="page cart-page">
    <header class="page-head reveal">
        <div class="flex-col gap-2">
            <span class="eyebrow">Ready when you are</span>
            <h1 class="page-title">My Cart</h1>
        </div>
        <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>/store">
            <?= Icon::get('search', 15) ?> Keep browsing
        </a>
    </header>

    <div class="cart-layout">
        <div class="cart-column">
            <div class="cart-list" id="cart-list"></div>
            <div id="cart-anchor" class="scroll-anchor"></div>
        </div>

        <aside class="order card reveal">
            <h2 class="section-title">Order summary</h2>

            <div class="order-row">
                <span>Items</span>
                <span id="order-count">0 items</span>
            </div>
            <div class="order-row">
                <span>Price</span>
                <span id="order-price">RM0.00</span>
            </div>
            <div class="order-row">
                <span>Sale discount</span>
                <span class="order-discount" id="order-discount">-RM0.00</span>
            </div>

            <div class="divider"></div>

            <div class="order-row order-total">
                <span>Subtotal</span>
                <span id="order-subtotal">RM0.00</span>
            </div>

            <button type="button" class="btn btn-primary btn-block" id="checkout-btn"
                    onclick="checkOut()" disabled>
                Check out
            </button>

            <p class="order-note text-body text-sm text-muted">
                Games are added to your library the moment the order goes through.
            </p>
        </aside>
    </div>
</div>

<script>
(() => {
    const API = '/src/app/api/cart/index.php';
    const ICONS = <?= json_encode(View::platformIcons()) ?>;
    const LIMIT = 12;

    const list = document.getElementById('cart-list');
    let offset = 0;
    let scroller;

    function cardMarkup(item) {
        // WASD.cover() is the shared renderer, so a game with no uploaded image
        // gets its fallback artwork here exactly as it does on a store card.
        const cover = WASD.cover(item, 'list-card-media');

        const price = (item.discount > 0
            ? `<span class="magenta game-tag">-${item.discount}%</span>
               <span class="original">${WASD.money(item.price)}</span>`
            : '') + `<span class="current">${WASD.money(item.final_price)}</span>`;

        const tags = item.categories
            .map(name => `<span class="magenta game-tag">${WASD.escapeHtml(name)}</span>`).join('');

        const icons = item.platforms.map(name => ICONS[name] || '').join('');

        return `<div class="list-card" data-id="${item.id}">
            ${cover}
            <div class="list-card-body">
                <a class="list-card-title" href="${WASD.url('/game?id=' + item.id)}">${WASD.escapeHtml(item.title)}</a>
                <div class="chip-list">${tags}</div>
                <div class="list-card-platforms">${icons}</div>
            </div>
            <div class="list-card-price">${price}</div>
            <div class="list-card-actions">
                <button type="button" class="btn btn-ghost btn-sm" onclick="moveToWishlist(${item.id})">
                    Move to wishlist
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(${item.id})">
                    Remove
                </button>
            </div>
        </div>`;
    }

    function showTotals(totals) {
        document.getElementById('order-count').textContent =
            totals.items + ' item' + (totals.items === 1 ? '' : 's');
        document.getElementById('order-price').textContent = WASD.money(totals.price);
        document.getElementById('order-discount').textContent = '-' + WASD.money(totals.discount);
        document.getElementById('order-subtotal').textContent = WASD.money(totals.subtotal);
        document.getElementById('checkout-btn').disabled = totals.items === 0;
        window.wasdSetBadge?.('cart', totals.items);
    }

    function showEmpty() {
        list.innerHTML = `<div class="empty-state">
            Your cart is empty.<br>
            <a href="${WASD.url('/store')}">Browse the store</a> to find your next game.
        </div>`;
        showTotals({ items: 0, price: 0, discount: 0, subtotal: 0 });
    }

    async function loadPage() {
        const result = await WASD.api(`${API}?offset=${offset}&limit=${LIMIT}`);

        if (result.status === 401) {
            list.innerHTML = `<div class="empty-state">
                Your session expired. <a href="${WASD.url('/sign-in')}">Sign in again</a>.
            </div>`;
            return false;
        }

        if (result.status === 204 || !result.data || !result.data.items.length) {
            if (offset === 0) showEmpty();
            else if (result.data && result.data.totals) showTotals(result.data.totals);
            return false;
        }

        const html = result.data.items.map(cardMarkup).join('');
        list.insertAdjacentHTML('beforeend', html);
        WASD.lazyImages(list);

        offset += result.data.items.length;
        showTotals(result.data.totals);

        return result.data.items.length === LIMIT;
    }

    async function reload() {
        scroller?.stop();
        offset = 0;
        list.innerHTML = WASD.skeletonRows(2);

        const more = await loadPage();
        // The skeletons sit in front of the first real page - drop them.
        list.querySelectorAll('.skeleton-row').forEach(node => node.remove());

        if (more) scroller = WASD.infiniteScroll(document.getElementById('cart-anchor'), loadPage);
    }

    async function sendAction(action, gameId, card) {
        card?.classList.add('is-busy');
        const result = await WASD.api(API, { json: { action, game_id: gameId } });

        if (!result.ok) {
            card?.classList.remove('is-busy');
            WASD.toast('That did not work. Try again.', 'error');
            return;
        }

        card?.remove();
        if (result.data && result.data.totals) showTotals(result.data.totals);
        if (!list.querySelector('.list-card')) showEmpty();
    }

    const cardOf = id => list.querySelector(`.list-card[data-id="${id}"]`);

    window.removeItem = id => sendAction('remove', id, cardOf(id));

    window.moveToWishlist = async id => {
        await sendAction('move-to-wishlist', id, cardOf(id));
        window.wasdBumpBadge?.('wishlist', 1);
        WASD.toast('Moved to your wishlist.', 'success');
    };

    window.checkOut = () => window.wasdNavigate(WASD.url('/checkout'));

    reload();
})();
</script>
