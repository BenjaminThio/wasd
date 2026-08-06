<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/View.php';
    require_once __DIR__ . '/../../models/Games.php';
    require_once __DIR__ . '/../../models/Icon.php';
    require_once __DIR__ . '/../../models/Library.php';

    $pageSize = 12;

    // Filters arrive in the URL so a search can be linked to and shared - the
    // category chips on the game page point straight at /store?q=Puzzle.
    $filters = [
        'q'        => trim((string)($_GET['q'] ?? '')),
        'category' => (int)($_GET['category'] ?? 0),
        'platform' => trim((string)($_GET['platform'] ?? '')),
        'price'    => trim((string)($_GET['price'] ?? 'all')),
        'sort'     => trim((string)($_GET['sort'] ?? 'newest')),
        'limit'    => $pageSize,
        'offset'   => 0,
    ];

    $result = Games::search($filters);
    $games  = $result['games'];
    $total  = $result['total'];

    $viewer = Auth::getCurrentUser();
    $ownedIds = Library::ownedIdsIn(
        $viewer?->getId(),
        array_map(fn(Game $game) => (int)$game->getId(), $games)
    );

    $categories = Games::getAllCategories();
    $platforms  = Games::getAllPlatforms();
?>

<div class="page page--wide store-page">

    <header class="page-head reveal">
        <div class="flex-col gap-2">
            <span class="eyebrow">Browse the catalog</span>
            <h1 class="page-title">Store</h1>
        </div>
        <p class="store-count" id="store-count">
            <?= number_format($total) ?> game<?= $total === 1 ? '' : 's' ?>
        </p>
    </header>

    <form class="toolbar reveal" id="store-filters" onsubmit="return false;">
        <div class="search-box">
            <span class="search-icon"><?= Icon::get('search', 17) ?></span>
            <input type="search" id="filter-q" class="field-input" name="q"
                   placeholder="Search games, developers or tags…"
                   value="<?= htmlspecialchars($filters['q']) ?>"
                   autocomplete="off" aria-label="Search the store">
        </div>

        <select id="filter-category" class="field-select" aria-label="Category">
            <option value="0">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int)$category['id'] ?>"
                        <?= $filters['category'] === (int)$category['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="filter-platform" class="field-select" aria-label="Platform">
            <option value="">All platforms</option>
            <?php foreach ($platforms as $platform): ?>
                <option value="<?= htmlspecialchars($platform['name']) ?>"
                        <?= $filters['platform'] === $platform['name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($platform['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="filter-price" class="field-select" aria-label="Price">
            <?php
                $priceOptions = [
                    'all' => 'Any price',
                    'free' => 'Free',
                    'paid' => 'Paid',
                    'discounted' => 'On sale',
                    'under-10' => 'Under RM10',
                ];
            ?>
            <?php foreach ($priceOptions as $value => $label): ?>
                <option value="<?= $value ?>" <?= $filters['price'] === $value ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="filter-sort" class="field-select" aria-label="Sort by">
            <?php
                $sortOptions = [
                    'newest' => 'Newest first',
                    'title' => 'Title A-Z',
                    'price-low' => 'Price: low to high',
                    'price-high' => 'Price: high to low',
                    'discount' => 'Biggest discount',
                    'popular' => 'Most viewed',
                    'downloads' => 'Most downloaded',
                ];
            ?>
            <?php foreach ($sortOptions as $value => $label): ?>
                <option value="<?= $value ?>" <?= $filters['sort'] === $value ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="button" class="btn btn-ghost btn-sm" id="filter-reset">Reset</button>
    </form>

    <div class="game-grid stagger" id="game-grid">
        <?php foreach ($games as $game): ?>
            <?php require __DIR__ . '/../../components/game-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="store-empty empty-state" id="store-empty" <?= empty($games) ? '' : 'hidden' ?>>
        Nothing matches that search.<br>
        Try a different keyword, or <a href="#" id="store-empty-reset">clear the filters</a>.
    </div>

    <div id="scroll-anchor" class="scroll-anchor">
        <span id="loading-spinner" hidden>Loading more games…</span>
    </div>
</div>

<script>
(() => {
    const PAGE_SIZE = <?= $pageSize ?>;
    const grid = document.getElementById('game-grid');
    const emptyState = document.getElementById('store-empty');
    const counter = document.getElementById('store-count');
    const spinner = document.getElementById('loading-spinner');
    const anchor = document.getElementById('scroll-anchor');

    const inputs = {
        q: document.getElementById('filter-q'),
        category: document.getElementById('filter-category'),
        platform: document.getElementById('filter-platform'),
        price: document.getElementById('filter-price'),
        sort: document.getElementById('filter-sort'),
    };

    let offset = <?= count($games) ?>;
    let total = <?= (int)$total ?>;
    let scroller;

    function currentQuery() {
        const params = new URLSearchParams();
        if (inputs.q.value.trim()) params.set('q', inputs.q.value.trim());
        if (inputs.category.value !== '0') params.set('category', inputs.category.value);
        if (inputs.platform.value) params.set('platform', inputs.platform.value);
        if (inputs.price.value !== 'all') params.set('price', inputs.price.value);
        if (inputs.sort.value !== 'newest') params.set('sort', inputs.sort.value);
        return params;
    }

    function updateCount() {
        counter.textContent = total.toLocaleString() + ' game' + (total === 1 ? '' : 's');
    }

    async function fetchPage(startOffset) {
        const params = currentQuery();
        params.set('limit', PAGE_SIZE);
        params.set('offset', startOffset);

        const result = await WASD.api('/src/app/api/games/index.php?' + params.toString());
        return result.ok && result.data ? result.data : null;
    }

    /** Appends the next page. Returns false when there is nothing left. */
    async function loadMore() {
        if (offset >= total) return false;

        spinner.hidden = false;
        const data = await fetchPage(offset);
        spinner.hidden = true;

        if (!data || !data.count) return false;

        grid.insertAdjacentHTML('beforeend', data.html);
        WASD.lazyImages(grid);
        offset += data.count;
        total = data.total;
        updateCount();

        return data.has_more;
    }

    /** Replaces the whole grid after a filter change. */
    async function applyFilters() {
        scroller?.stop();

        // Keep the URL shareable without pushing a history entry per keystroke.
        const params = currentQuery();
        const query = params.toString();
        history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
        window.wasdSyncLocation?.();

        grid.innerHTML = WASD.skeletonCards(PAGE_SIZE);
        emptyState.hidden = true;

        const data = await fetchPage(0);

        if (!data) {
            grid.innerHTML = '';
            emptyState.hidden = false;
            return;
        }

        total = data.total;
        offset = data.count;
        updateCount();

        grid.innerHTML = data.html;
        WASD.lazyImages(grid);
        emptyState.hidden = data.count > 0;

        scroller = WASD.infiniteScroll(anchor, loadMore);
    }

    const debouncedFilter = WASD.debounce(applyFilters, 280);

    inputs.q.addEventListener('input', debouncedFilter);
    ['category', 'platform', 'price', 'sort'].forEach(key =>
        inputs[key].addEventListener('change', applyFilters));

    function resetFilters(event) {
        event?.preventDefault();
        inputs.q.value = '';
        inputs.category.value = '0';
        inputs.platform.value = '';
        inputs.price.value = 'all';
        inputs.sort.value = 'newest';
        applyFilters();
    }

    document.getElementById('filter-reset').addEventListener('click', resetFilters);
    document.getElementById('store-empty-reset').addEventListener('click', resetFilters);

    scroller = WASD.infiniteScroll(anchor, loadMore);
})();
</script>
