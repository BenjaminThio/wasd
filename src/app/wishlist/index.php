<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/View.php';
    require_once __DIR__ . '/../../models/Icon.php';

    if (!Auth::getCurrentUser()) {
        echo '<div class="page"><div class="empty-state">'
           . 'Sign in to see your wishlist.<br>'
           . '<a href="' . BASE_URL . '/sign-in">Sign in</a></div></div>';
        return;
    }
?>
<div class="page wishlist-page">
    <header class="page-head reveal">
        <div class="flex-col gap-2">
            <span class="eyebrow">Games you are watching</span>
            <h1 class="page-title">My Wishlist</h1>
        </div>
        <p class="store-count" id="wishlist-count">&nbsp;</p>
    </header>

    <form class="toolbar reveal" onsubmit="return false;">
        <div class="search-box">
            <span class="search-icon"><?= Icon::get('search', 17) ?></span>
            <input type="search" id="wishlist-search" class="field-input"
                   placeholder="Search your wishlist…" autocomplete="off"
                   aria-label="Search your wishlist">
        </div>

        <select id="wishlist-filter" class="field-select" aria-label="Filter">
            <option value="all">Everything</option>
            <option value="on-sale">On sale</option>
            <option value="free">Free</option>
            <option value="paid">Paid</option>
            <option value="in-cart">Already in cart</option>
        </select>

        <select id="wishlist-sort" class="field-select" aria-label="Sort by">
            <option value="added">Recently added</option>
            <option value="title">Title A-Z</option>
            <option value="price-low">Price: low to high</option>
            <option value="price-high">Price: high to low</option>
            <option value="discount">Biggest discount</option>
            <option value="release">Newest release</option>
        </select>
    </form>

    <div class="wishlist-list" id="wishlist-list"></div>

    <div id="wishlist-anchor" class="scroll-anchor"></div>
</div>

<script>
(() => {
    const API = '/src/app/api/wishlist/index.php';
    const ICONS = <?= json_encode(View::platformIcons()) ?>;
    const LIMIT = 12;
    const REVIEW_LABEL = ['Mostly Negative', 'Mixed', 'Overwhelmingly Positive'];
    const REVIEW_CSS = ['review-negative', 'review-mixed', 'review-positive'];

    const list = document.getElementById('wishlist-list');
    const anchor = document.getElementById('wishlist-anchor');
    const counter = document.getElementById('wishlist-count');

    const search = document.getElementById('wishlist-search');
    const filter = document.getElementById('wishlist-filter');
    const sort = document.getElementById('wishlist-sort');

    let offset = 0;
    let total = 0;
    let scroller;

    function cardMarkup(item) {
        const cover = WASD.cover(item, 'list-card-media');

        const price = (item.discount > 0
            ? `<span class="magenta game-tag">-${item.discount}%</span>
               <span class="original">${WASD.money(item.price)}</span>`
            : '') + `<span class="current">${WASD.money(item.final_price)}</span>`;

        const tags = item.categories
            .map(name => `<span class="magenta game-tag">${WASD.escapeHtml(name)}</span>`).join('');

        const icons = item.platforms.map(name => ICONS[name] || '').join('');

        let action;
        if (item.owned) {
            action = `<a class="btn btn-ghost btn-sm" href="${WASD.url('/game?id=' + item.id)}">In your library</a>`;
        } else if (item.in_cart) {
            action = `<button type="button" class="btn btn-ghost btn-sm" onclick="goToCart()">In cart</button>`;
        } else {
            action = `<button type="button" class="btn btn-accent btn-sm" id="cart-btn-${item.id}"
                              onclick="addToCart(${item.id})">Add to cart</button>`;
        }

        return `<div class="list-card" data-id="${item.id}">
            ${cover}
            <div class="list-card-body">
                <a class="list-card-title" href="${WASD.url('/game?id=' + item.id)}">${WASD.escapeHtml(item.title)}</a>
                <div class="chip-list">${tags}</div>
                <div class="list-card-meta">
                    <div><span class="label">Reviews:</span>
                        <span class="value ${REVIEW_CSS[item.review_status]}">${REVIEW_LABEL[item.review_status]}</span>
                    </div>
                    <div><span class="label">Released:</span>
                        <span class="value">${WASD.escapeHtml(item.release_date)}</span>
                    </div>
                </div>
                <div class="list-card-platforms">${icons}</div>
            </div>
            <div class="list-card-price">${price}</div>
            <div class="list-card-actions">
                ${action}
                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(${item.id})">Remove</button>
            </div>
        </div>`;
    }

    function query(startOffset) {
        const params = new URLSearchParams({
            offset: startOffset,
            limit: LIMIT,
            filter: filter.value,
            sort: sort.value,
        });
        if (search.value.trim()) params.set('q', search.value.trim());
        return `${API}?${params.toString()}`;
    }

    function showEmpty() {
        const filtered = search.value.trim() !== '' || filter.value !== 'all';

        list.innerHTML = filtered
            ? `<div class="empty-state">Nothing in your wishlist matches that.</div>`
            : `<div class="empty-state">
                   Your wishlist is empty.<br>
                   <a href="${WASD.url('/store')}">Browse the store</a> and save the games you are watching.
               </div>`;
    }

    async function loadPage() {
        const result = await WASD.api(query(offset));

        if (!result.ok || !result.data) return false;

        total = result.data.total;
        counter.textContent = total.toLocaleString() + ' game' + (total === 1 ? '' : 's');

        if (!result.data.items.length) {
            if (offset === 0) showEmpty();
            return false;
        }

        list.insertAdjacentHTML('beforeend', result.data.items.map(cardMarkup).join(''));
        WASD.lazyImages(list);
        offset += result.data.items.length;

        return offset < total;
    }

    async function reload() {
        scroller?.stop();
        offset = 0;
        list.innerHTML = WASD.skeletonRows(3);

        const more = await loadPage();
        list.querySelectorAll('.skeleton-row').forEach(node => node.remove());

        if (more) scroller = WASD.infiniteScroll(anchor, loadPage);
    }

    async function sendAction(action, gameId) {
        const result = await WASD.api(API, { json: { action, game_id: gameId } });
        return result.ok ? result.data : null;
    }

    window.addToCart = async function (gameId) {
        const data = await sendAction('add-to-cart', gameId);

        if (!data) return WASD.toast('Could not add that to your cart.', 'error');
        if (data.status === 'owned') return WASD.toast('You already own this game.', 'info');

        const button = document.getElementById('cart-btn-' + gameId);
        if (button) {
            button.textContent = 'In cart';
            button.classList.replace('btn-accent', 'btn-ghost');
            button.onclick = window.goToCart;
        }

        window.wasdBumpBadge?.('cart', 1);
        WASD.toast('Added to your cart.', 'success');
    };

    window.removeItem = async function (gameId) {
        const card = list.querySelector(`.list-card[data-id="${gameId}"]`);
        card?.classList.add('is-busy');

        if (!await sendAction('remove', gameId)) {
            card?.classList.remove('is-busy');
            return WASD.toast('Could not remove that.', 'error');
        }

        card?.remove();
        total = Math.max(0, total - 1);
        counter.textContent = total.toLocaleString() + ' game' + (total === 1 ? '' : 's');
        window.wasdBumpBadge?.('wishlist', -1);

        if (!list.querySelector('.list-card')) showEmpty();
    };

    window.goToCart = () => window.wasdNavigate(WASD.url('/cart'));

    search.addEventListener('input', WASD.debounce(reload, 280));
    filter.addEventListener('change', reload);
    sort.addEventListener('change', reload);

    reload();
})();
</script>
