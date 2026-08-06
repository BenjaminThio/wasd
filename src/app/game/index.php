<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/Media.php';
    require_once __DIR__ . '/../../lib/Uploads.php';
    require_once __DIR__ . '/../../lib/View.php';
    require_once __DIR__ . '/../../models/Icon.php';
    require_once __DIR__ . '/../../models/Games.php';
    require_once __DIR__ . '/../../models/Library.php';

    $gameId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($gameId <= 0) {
        echo '<div class="page"><div class="empty-state">No game was requested.<br>'
           . '<a href="' . BASE_URL . '/store">Back to the store</a></div></div>';
        return;
    }

    $game = Games::getById($gameId);

    if (!$game) {
        echo '<div class="page"><div class="empty-state">This game does not exist.<br>'
           . '<a href="' . BASE_URL . '/store">Back to the store</a></div></div>';
        return;
    }

    // The tab should say which game this is, not just "Game".
    $page->setTitle($game->getTitle());

    Auth::startSession();
    $viewer   = Auth::getCurrentUser();
    $viewerId = $viewer?->getId();
    $isDeveloper = $viewerId !== null && $viewerId === $game->getUserId();

    // Draft and restricted projects stay private until they are published.
    if (!$game->isPublic() && !$isDeveloper) {
        echo '<div class="page"><div class="empty-state">'
           . 'This project has not been published yet.<br>'
           . '<a href="' . BASE_URL . '/store">Browse the store</a></div></div>';
        return;
    }

    // One view per visitor per game per session, so a refresh is not a view.
    $seen = $_SESSION['viewed_games'] ?? [];
    if (!$isDeveloper && !in_array($gameId, $seen, true)) {
        Games::recordView($gameId);
        $seen[] = $gameId;
        $_SESSION['viewed_games'] = $seen;
    }

    $database = new Database();

    $owned  = Library::owns($viewerId, $gameId, $database);
    $isFree = $game->isFree();
    $builds = $isDeveloper ? $game->getBuilds() : $game->getVisibleBuilds();
    $screenshots = $game->getScreenshots();

    // The viewer's own review, if they have written one. A player has at most
    // one per game, so the form doubles as the editor for it.
    $myReview = null;
    if ($viewerId !== null) {
        $myReview = $database->query(
            'SELECT id, enjoy, description FROM review WHERE user_id = ? AND game_id = ? LIMIT 1',
            [$viewerId, $gameId]
        )->fetch() ?: null;
    }

    $inCart = $inWishlist = false;
    if ($viewerId !== null) {
        $inCart = (bool)$database->query(
            'SELECT 1 FROM cart WHERE user_id = ? AND game_id = ? LIMIT 1', [$viewerId, $gameId]
        )->fetch();
        $inWishlist = (bool)$database->query(
            'SELECT 1 FROM wishlist WHERE user_id = ? AND game_id = ? LIMIT 1', [$viewerId, $gameId]
        )->fetch();
    }

    // The build the developer marked "playable in the browser", if its unpacked
    // entry document is still on disk.
    $playableBuild = null;
    foreach ($builds as $build) {
        if (empty($build['is_playable']) || empty($build['play_path'])) continue;
        if (!is_file(Media::absolute($build['play_path']))) continue;

        $playableBuild = $build;
        break;
    }

    // Same entitlement as downloading: free, bought, or your own project.
    $canPlay = $playableBuild !== null && $owned;

    if ($playableBuild !== null) {
        // Repair the play folder's headers if it was unpacked before they
        // existed, so an older build starts working without being re-saved.
        $playRoot = Uploads::playRoot($playableBuild['play_path']);

        if ($playRoot !== null) {
            Uploads::ensurePlayDirectoryProtected(Media::root() . '/' . $playRoot);
        }
    }

    if ($canPlay) {
        /*
           Cross-origin isolation.

           Engines that use threads (Godot's web export, Unity with threading)
           need SharedArrayBuffer, and browsers only hand that to a document
           that is cross-origin isolated. An iframe inherits isolation from its
           embedder, so the game page has to declare it too - the unpacked build
           carries the matching headers via its own .htaccess.

           Only sent when a player is actually embedded: COEP requires every
           subresource to opt in, and there is no reason to impose that on a
           page with nothing to isolate.
        */
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Embedder-Policy: require-corp');

        // Headers only arrive with a real document, so this page cannot be
        // reached by a soft swap.
        $page->setRequiresFullLoad(true);
    }

    $coverUrl    = Media::url($game->getImage());
    $reviewCount = $game->getReviewCount();
    $ratingText  = $game->getReviewLabel();
    $reviewStatus = $game->getReviewStatus();

    // The gallery shows the cover first when there is one, then every screenshot.
    $gallery = [];
    if ($coverUrl !== '') $gallery[] = $coverUrl;
    foreach ($screenshots as $shot) {
        $url = Media::url($shot);
        if ($url !== '') $gallery[] = $url;
    }

    $platformNames = array_map(fn(Platform $platform) => $platform->name, $game->getPlatforms());
?>

<div class="page page--wide game-page">

    <!-- ---------------------------------------------------------- header -->
    <header class="game-hero reveal">
        <div class="game-hero-text">
            <a class="game-back" href="<?= BASE_URL ?>/store">
                <?= Icon::get('chevron-right', 14) ?> Store
            </a>
            <h1 class="game-name"><?= htmlspecialchars($game->getTitle()) ?></h1>

            <div class="game-meta">
                <div class="game-meta-item">
                    <span class="mono-label">Developer</span>
                    <span class="game-meta-value"><?= htmlspecialchars($game->getDeveloper()) ?></span>
                </div>
                <div class="game-meta-item">
                    <span class="mono-label">Released</span>
                    <span class="game-meta-value"><?= htmlspecialchars($game->getFormattedReleaseDate()) ?></span>
                </div>
                <div class="game-meta-item">
                    <span class="mono-label">Player rating</span>
                    <span class="game-meta-value rating-<?= $reviewStatus ?>" id="rating-value">
                        <?= htmlspecialchars($ratingText) ?>
                        <?php if ($reviewCount > 0): ?>
                            <span class="text-dim">(<?= $reviewCount ?>)</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($isDeveloper): ?>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/project?id=<?= $gameId ?>">
                <?= Icon::get('pencil', 16) ?> Edit project
            </a>
        <?php endif; ?>
    </header>

    <div class="game-layout">
        <!-- ------------------------------------------------------- left -->
        <div class="game-main">

            <!-- Web player ---------------------------------------------- -->
            <?php if ($playableBuild): ?>
                <section class="web-player reveal" id="web-player">
                    <div class="web-player-frame" id="player-frame">
                        <?php if ($canPlay): ?>
                            <!-- The poster stands in until the player presses
                                 Play, so an embedded engine never downloads
                                 tens of megabytes for someone just browsing. -->
                            <div class="player-poster" id="player-poster">
                                <?= View::cover($game->getImage(), $game->getFallbackArt(), $game->getTitle(), 'player-poster-media') ?>

                                <div class="player-poster-body">
                                    <span class="badge player-badge">
                                        <?= Icon::get('gamepad', 14) ?> Playable in your browser
                                    </span>
                                    <button type="button" class="btn btn-primary player-start"
                                            onclick="startPlaying()">
                                        <?= Icon::get('gamepad', 18) ?> Play now
                                    </button>
                                    <span class="player-poster-note">
                                        <?= htmlspecialchars($playableBuild['display_name']) ?>
                                        &middot; <?= htmlspecialchars($playableBuild['file_size']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="player-poster is-locked">
                                <?= View::cover($game->getImage(), $game->getFallbackArt(), $game->getTitle(), 'player-poster-media') ?>

                                <div class="player-poster-body">
                                    <span class="badge player-badge">
                                        <?= Icon::get('gamepad', 14) ?> Playable in your browser
                                    </span>
                                    <p class="player-poster-note">
                                        Buy this game to play it here, straight in the browser.
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($canPlay): ?>
                        <div class="player-bar" id="player-bar" hidden>
                            <span class="player-title">
                                <?= Icon::get('gamepad', 15) ?>
                                <?= htmlspecialchars($playableBuild['display_name']) ?>
                            </span>

                            <div class="player-controls">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="reloadPlayer()"
                                        title="Restart the game">Restart</button>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="playerFullscreen()"
                                        title="Play fullscreen">Fullscreen</button>
                                <a class="btn btn-ghost btn-sm" id="player-newtab" target="_blank" rel="noopener"
                                   href="<?= htmlspecialchars(Media::playUrl($playableBuild['play_path']), ENT_QUOTES) ?>"
                                   title="Open in a new tab">New tab</a>
                                <button type="button" class="btn btn-danger btn-sm" onclick="stopPlaying()"
                                        title="Close the game">Close</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <!-- Gallery ------------------------------------------------- -->
            <section class="gallery reveal" id="gallery"
                     <?= count($gallery) > 1 ? 'data-count="' . count($gallery) . '"' : '' ?>>
                <?php if (empty($gallery)): ?>
                    <div class="gallery-stage media is-ready">
                        <span class="media-art <?= htmlspecialchars($game->getFallbackArt()) ?>"
                              aria-hidden="true"></span>
                    </div>
                <?php else: ?>
                    <div class="gallery-stage media" id="gallery-stage">
                        <!--
                            Every shot is its own slide on a single track that
                            slides sideways. Swapping one <img>'s src could only
                            ever cross-fade; a track can animate between two
                            pictures and be dragged with a finger.
                        -->
                        <div class="gallery-track media-layer" id="gallery-track">
                            <?php foreach ($gallery as $index => $url): ?>
                                <div class="gallery-slide">
                                    <img class="img-lazy" decoding="async"
                                         loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                                         src="<?= htmlspecialchars($url, ENT_QUOTES) ?>"
                                         alt="<?= htmlspecialchars($game->getTitle()) ?> screenshot <?= $index + 1 ?>"
                                         draggable="false">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($gallery) > 1): ?>
                            <button type="button" class="gallery-arrow gallery-prev"
                                    onclick="galleryStep(-1)" aria-label="Previous screenshot">
                                <?= Icon::get('chevron-right', 20) ?>
                            </button>
                            <button type="button" class="gallery-arrow gallery-next"
                                    onclick="galleryStep(1)" aria-label="Next screenshot">
                                <?= Icon::get('chevron-right', 20) ?>
                            </button>
                            <span class="gallery-counter" id="gallery-counter">1 / <?= count($gallery) ?></span>
                        <?php endif; ?>

                        <button type="button" class="gallery-expand" onclick="openLightbox()"
                                aria-label="View full size">
                            <?= Icon::get('image', 16) ?>
                        </button>
                    </div>

                    <?php if (count($gallery) > 1): ?>
                        <div class="gallery-thumbs" id="gallery-thumbs">
                            <?php foreach ($gallery as $index => $url): ?>
                                <button type="button"
                                        class="gallery-thumb media<?= $index === 0 ? ' is-active' : '' ?>"
                                        onclick="galleryShow(<?= $index ?>)"
                                        aria-label="Screenshot <?= $index + 1 ?>">
                                    <img class="img-lazy" loading="lazy" decoding="async"
                                         src="<?= htmlspecialchars($url, ENT_QUOTES) ?>" alt="">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <!-- About --------------------------------------------------- -->
            <section class="game-about card reveal">
                <h2 class="section-title">About this game</h2>
                <div class="game-about-description" id="game-description">
                    <?= nl2br(htmlspecialchars($game->getDescription())) ?>
                </div>
            </section>

            <!-- More information --------------------------------------- -->
            <section class="more-info reveal">
                <details class="info-block" open>
                    <summary>
                        <span><?= Icon::get('info', 16) ?> More information</span>
                        <?= Icon::get('chevron-down', 18) ?>
                    </summary>

                    <dl class="info-grid">
                        <div class="info-row">
                            <dt>Published</dt>
                            <dd><?= htmlspecialchars($game->getFormattedReleaseDate()) ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Developer</dt>
                            <dd><?= htmlspecialchars($game->getDeveloper()) ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Price</dt>
                            <dd>
                                <?php if ($isFree): ?>
                                    Free
                                <?php elseif ($game->getDiscount() > 0): ?>
                                    <?= View::money($game->getDiscountedPrice()) ?>
                                    <span class="text-dim">(<?= $game->getDiscount() ?>% off
                                    <?= View::money($game->getPrice()) ?>)</span>
                                <?php else: ?>
                                    <?= View::money($game->getPrice()) ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div class="info-row">
                            <dt>Views</dt>
                            <dd><?= number_format($game->getViews()) ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Downloads</dt>
                            <dd><?= number_format($game->getDownloads()) ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Reviews</dt>
                            <dd id="review-count-value">
                                <?= $reviewCount ?>
                                <?php if ($reviewCount > 0): ?>
                                    <span class="text-dim">
                                        (<?= $game->getPositiveReviewCount() ?> positive)
                                    </span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div class="info-row">
                            <dt>Platforms</dt>
                            <dd class="chip-list">
                                <?php if (empty($platformNames)): ?>
                                    <span class="text-dim">Not specified</span>
                                <?php else: ?>
                                    <?php foreach ($game->getPlatforms() as $platform): ?>
                                        <span class="badge">
                                            <?= Icon::get($platform->name, 14) ?>
                                            <?= htmlspecialchars($platform->name) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div class="info-row">
                            <dt>Categories</dt>
                            <dd class="chip-list">
                                <?php if (empty($game->getCategories())): ?>
                                    <span class="text-dim">Uncategorised</span>
                                <?php else: ?>
                                    <?php foreach ($game->getCategories() as $category): ?>
                                        <a class="<?= strtolower($category->getColor()->name) ?> game-tag"
                                           href="<?= BASE_URL ?>/store?q=<?= urlencode($category->getName()) ?>">
                                            <?= htmlspecialchars($category->getName()) ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div class="info-row">
                            <dt>Builds</dt>
                            <dd><?= count($builds) ?> file<?= count($builds) === 1 ? '' : 's' ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Screenshots</dt>
                            <dd><?= count($screenshots) ?></dd>
                        </div>
                        <?php if ($isDeveloper): ?>
                            <div class="info-row">
                                <dt>Visibility</dt>
                                <dd>
                                    <span class="game-tag <?= $game->isPublic() ? 'green' : 'orange' ?>">
                                        <?= strtoupper(htmlspecialchars($game->getVisibility())) ?>
                                    </span>
                                </dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </details>
            </section>

            <!-- Reviews ------------------------------------------------- -->
            <section class="review-section-container reveal">
                <h2 class="section-title">Player reviews</h2>

                <?php if ($viewer): ?>
                    <div class="review-container card<?= $myReview ? ' is-editing' : '' ?>" id="review-form">
                        <div class="review-title" id="review-form-title">
                            <?= $myReview ? 'Edit your review' : 'Write a review' ?>
                        </div>

                        <div class="review-rating-container">
                            <div class="field-label">Your rating</div>
                            <div class="review-rating">
                                <button type="button" id="btn-up"
                                        class="thumbs-button<?= $myReview && $myReview['enjoy'] ? ' is-up' : '' ?>"
                                        onclick="setRating(true)">
                                    <?= Icon::get('thumbs-up', 21) ?>
                                </button>
                                <button type="button" id="btn-down"
                                        class="thumbs-button<?= $myReview && !$myReview['enjoy'] ? ' is-down' : '' ?>"
                                        onclick="setRating(false)">
                                    <?= Icon::get('thumbs-down', 21) ?>
                                </button>
                            </div>
                        </div>

                        <div class="field">
                            <label class="field-label" for="review-text">Your review</label>
                            <textarea id="review-text" class="field-textarea"
                                      placeholder="What did you think of the game?"><?= $myReview ? htmlspecialchars($myReview['description']) : '' ?></textarea>
                        </div>

                        <div class="review-form-actions">
                            <button type="button" class="btn btn-primary btn-block" id="review-submit"
                                    onclick="publishReview()">
                                <?= $myReview ? 'Update review' : 'Publish review' ?>
                            </button>

                            <button type="button" class="btn btn-ghost" id="review-cancel"
                                    onclick="cancelReviewEdit()" <?= $myReview ? '' : 'hidden' ?>>
                                Cancel
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <a href="<?= BASE_URL ?>/sign-in">Sign in</a> to leave a review.
                    </div>
                <?php endif; ?>

                <div id="review-list" class="review-list"></div>

                <div id="review-scroll-anchor" class="review-anchor">Loading reviews…</div>
            </section>
        </div>

        <!-- ------------------------------------------------------ right -->
        <aside class="game-side">
            <div class="get-the-game card reveal" id="get-the-game">
                <?php if ($owned): ?>
                    <div class="eyebrow"><?= $isFree ? 'Free to play' : 'In your library' ?></div>
                    <h2 class="get-price"><?= $isFree ? 'FREE' : 'OWNED' ?></h2>
                    <p class="get-note">
                        <?= $isFree
                            ? 'This game is free - grab any build below.'
                            : 'You already own this game. Download it any time.' ?>
                    </p>

                    <?php if (!empty($builds)): ?>
                        <button type="button" class="btn btn-primary btn-block" onclick="jumpToDownloads()">
                            <?= Icon::get('download', 17) ?> Download
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary btn-block" disabled>
                            No builds uploaded yet
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="eyebrow">Get the game</div>

                    <?php if ($game->getDiscount() > 0): ?>
                        <div class="get-price-row">
                            <h2 class="get-price"><?= View::money($game->getDiscountedPrice()) ?></h2>
                            <span class="get-price-old"><?= View::money($game->getPrice()) ?></span>
                            <span class="magenta game-tag">-<?= $game->getDiscount() ?>%</span>
                        </div>
                    <?php else: ?>
                        <h2 class="get-price"><?= View::money($game->getPrice()) ?></h2>
                    <?php endif; ?>

                    <button type="button" id="cart-button"
                            class="btn btn-block <?= $inCart ? 'btn-ghost' : 'btn-accent' ?>"
                            onclick="<?= $inCart ? 'goToCart()' : "addToAccount('cart')" ?>">
                        <?= Icon::get('cart', 16) ?>
                        <span id="cart-button-text"><?= $inCart ? 'In cart - view cart' : 'Add to cart' ?></span>
                    </button>

                    <button type="button" id="wishlist-button"
                            class="btn btn-ghost btn-block"
                            onclick="<?= $inWishlist ? 'goToWishlist()' : "addToAccount('wishlist')" ?>">
                        <?= Icon::get('heart', 16) ?>
                        <span id="wishlist-button-text">
                            <?= $inWishlist ? 'In wishlist' : 'Add to wishlist' ?>
                        </span>
                    </button>

                    <p class="get-note">
                        <?= $viewer
                            ? 'Buy once, download on every platform the developer supports.'
                            : '<a href="' . BASE_URL . '/sign-in">Sign in</a> to buy or wishlist this game.' ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Downloads ------------------------------------------------ -->
            <div class="downloads card reveal" id="downloads">
                <div class="downloads-head">
                    <h2 class="section-title">Downloads</h2>
                    <span class="badge"><?= count($builds) ?></span>
                </div>

                <?php if (empty($builds)): ?>
                    <p class="text-muted text-body">
                        The developer has not uploaded any builds yet.
                    </p>
                <?php elseif (!$owned): ?>
                    <p class="text-muted text-body">
                        <?= count($builds) ?> build<?= count($builds) === 1 ? '' : 's' ?> available
                        after purchase.
                    </p>

                    <ul class="build-preview">
                        <?php foreach ($builds as $build): ?>
                            <li>
                                <span class="build-preview-icons">
                                    <?php foreach (explode(',', (string)$build['platforms']) as $name): ?>
                                        <?= Icon::get(trim($name), 15) ?>
                                    <?php endforeach; ?>
                                </span>
                                <span class="build-preview-name">
                                    <?= htmlspecialchars($build['display_name']) ?>
                                </span>
                                <span class="badge"><?= htmlspecialchars($build['file_size']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <ul class="build-list">
                        <?php foreach ($builds as $build): ?>
                            <li class="build-item<?= !empty($build['is_hidden']) ? ' is-hidden-build' : '' ?>">
                                <div class="build-item-info">
                                    <div class="build-item-name">
                                        <?= htmlspecialchars($build['display_name']) ?>
                                        <?php if (!empty($build['is_hidden'])): ?>
                                            <span class="game-tag orange">HIDDEN</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="build-item-meta">
                                        <span class="build-platforms">
                                            <?php foreach (explode(',', (string)$build['platforms']) as $name): ?>
                                                <?= Icon::get(trim($name), 15) ?>
                                            <?php endforeach; ?>
                                        </span>
                                        <span><?= htmlspecialchars($build['file_size']) ?></span>
                                        <span>·</span>
                                        <span><?= number_format((int)$build['downloads']) ?> downloads</span>
                                    </div>
                                </div>

                                <a class="btn btn-primary btn-sm"
                                   href="<?= BASE_URL ?>/src/app/api/download/index.php?build=<?= (int)$build['id'] ?>"
                                   download>
                                    <?= Icon::get('download', 15) ?> Download
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<!-- Full-size screenshot viewer -->
<div class="lightbox" id="lightbox" hidden onclick="closeLightbox(event)">
    <button type="button" class="lightbox-close" onclick="closeLightbox()" aria-label="Close">
        <?= Icon::get('close', 22) ?>
    </button>
    <img id="lightbox-image" src="" alt="">
</div>

<script>
(() => {
    const BASE = "<?= BASE_URL ?>";
    const gameId = <?= $gameId ?>;
    const signedIn = <?= $viewer ? 'true' : 'false' ?>;
    const gallery = <?= json_encode($gallery) ?>;

    /* --------------------------------------------------------- web player */

    const PLAY_URL = <?= json_encode($canPlay ? Media::playUrl($playableBuild['play_path']) : null) ?>;

    // Builds served from their own hostname are already isolated by the browser,
    // so the frame does not need an opaque origin on top - and without one the
    // engine can create the workers a threaded export depends on.
    const PLAY_ON_SEPARATE_ORIGIN = <?= Media::playOriginConfigured() ? 'true' : 'false' ?>;

    window.startPlaying = function () {
        const frame = document.getElementById('player-frame');
        const bar = document.getElementById('player-bar');
        if (!frame || !PLAY_URL) return;

        const player = document.createElement('iframe');
        player.id = 'player-iframe';
        player.className = 'player-iframe';
        player.src = PLAY_URL;
        player.title = 'Game player';
        player.setAttribute('allow', 'autoplay; fullscreen; gamepad; xr-spatial-tracking; cross-origin-isolated');
        player.setAttribute('allowfullscreen', '');
        player.setAttribute('referrerpolicy', 'no-referrer');

        /*
           allow-same-origin here means "keep your own origin", not "take
           ours". What that origin actually is decides the security, and that
           is what PLAY_ORIGIN controls:

             PLAY_ORIGIN set  - the build keeps its own hostname. The browser
                                isolates it from us: it cannot read our pages
                                or send our cookies. Use this in production; it
                                is the arrangement itch.io uses.

             PLAY_ORIGIN unset - the build shares our origin and can reach the
                                API as the signed-in visitor.

           It is spelled out rather than sandboxed away because the alternative
           does not work: without allow-same-origin the document gets an opaque
           origin, and an opaque origin cannot construct a Web Worker, which a
           threaded engine build requires. Measured, not assumed - a sandboxed
           frame returned SecurityError for the worker and "Failed to fetch"
           for the .wasm and .pck.

           This is also not a new exposure: the same build is reachable through
           "New tab" and by its own URL. PLAY_ORIGIN is what closes all three.
        */
        player.setAttribute(
            'sandbox',
            'allow-scripts allow-same-origin allow-pointer-lock allow-popups allow-forms allow-modals'
        );

        frame.classList.add('is-playing');
        frame.innerHTML = '';
        frame.appendChild(player);

        if (bar) bar.hidden = false;

        frame.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    window.reloadPlayer = function () {
        const player = document.getElementById('player-iframe');
        if (player) player.src = player.src;
    };

    window.stopPlaying = function () {
        const frame = document.getElementById('player-frame');
        const bar = document.getElementById('player-bar');
        if (!frame) return;

        // Replacing the markup tears the engine down, freeing its memory and
        // stopping its audio - pausing an iframe is not otherwise possible.
        frame.classList.remove('is-playing');
        frame.innerHTML = posterMarkup;
        if (bar) bar.hidden = true;

        WASD.lazyImages(frame);
    };

    window.playerFullscreen = function () {
        const frame = document.getElementById('player-frame');
        if (!frame) return;

        if (document.fullscreenElement) {
            document.exitFullscreen?.();
            return;
        }

        (frame.requestFullscreen || frame.webkitRequestFullscreen)?.call(frame);
    };

    // Kept so Close can put the poster back exactly as it was rendered.
    const posterMarkup = document.getElementById('player-frame')?.innerHTML ?? '';

    /* ------------------------------------------------------------ gallery */

    let galleryIndex = 0;

    const track = document.getElementById('gallery-track');
    const stage = document.getElementById('gallery-stage');
    const slides = track ? [...track.children] : [];

    /** Moves the track. `offset` is a live finger drag in pixels. */
    function placeTrack(offset, animate) {
        if (!track) return;
        track.style.transition = animate ? '' : 'none';
        track.style.transform = `translate3d(calc(${-galleryIndex * 100}% + ${offset || 0}px), 0, 0)`;
    }

    /** The neighbours are what a swipe reveals first, so fetch them early. */
    function preloadNeighbours() {
        [-1, 0, 1].forEach(step => {
            const slide = slides[(galleryIndex + step + slides.length) % slides.length];
            const image = slide?.querySelector('img');
            if (image && image.loading === 'lazy') image.loading = 'eager';
        });
    }

    window.galleryShow = function (index, animate = null) {
        if (!gallery.length) return;

        const next = (index + gallery.length) % gallery.length;

        /*
           Slide between neighbours, jump everything else.

           Animating an arbitrary jump means whooshing through every picture in
           between - wrapping from the last shot back to the first would travel
           1500% of the stage. A step is a swipe; anything longer is a jump.
        */
        const isStep = Math.abs(next - galleryIndex) === 1;
        const shouldAnimate = animate === null ? isStep : animate;

        galleryIndex = next;

        placeTrack(0, shouldAnimate);
        preloadNeighbours();

        const counter = document.getElementById('gallery-counter');
        if (counter) counter.textContent = (galleryIndex + 1) + ' / ' + gallery.length;

        document.querySelectorAll('.gallery-thumb').forEach((thumb, i) =>
            thumb.classList.toggle('is-active', i === galleryIndex));

        WASD.lazyImages(track);
    };

    window.galleryStep = step => window.galleryShow(galleryIndex + step);

    /* --------------------------------------------------------- swipe input */

    if (stage && slides.length > 1) {
        let startX = 0;
        let startY = 0;
        let delta = 0;
        let dragging = false;
        let decided = false;

        const width = () => stage.getBoundingClientRect().width || 1;

        stage.addEventListener('pointerdown', event => {
            // Ignore the arrows, the counter and the expand button.
            if (event.target.closest('button')) return;
            if (event.pointerType === 'mouse' && event.button !== 0) return;

            dragging = true;
            decided = false;
            delta = 0;
            startX = event.clientX;
            startY = event.clientY;
            stage.classList.add('is-dragging');
        });

        stage.addEventListener('pointermove', event => {
            if (!dragging) return;

            const dx = event.clientX - startX;
            const dy = event.clientY - startY;

            // First movement decides whether this is a swipe or a page scroll.
            if (!decided) {
                if (Math.abs(dx) < 6 && Math.abs(dy) < 6) return;
                if (Math.abs(dy) > Math.abs(dx)) {
                    dragging = false;
                    stage.classList.remove('is-dragging');
                    return;
                }
                decided = true;

                // Throws if the pointer is no longer active (it can be
                // released between two move events). Never worth breaking the
                // drag over.
                try {
                    stage.setPointerCapture?.(event.pointerId);
                } catch {
                    /* capture is an optimisation, not a requirement */
                }
            }

            // Resist at the two ends so the track feels bounded.
            const atEdge = (galleryIndex === 0 && dx > 0) ||
                           (galleryIndex === slides.length - 1 && dx < 0);
            delta = atEdge ? dx * 0.28 : dx;

            placeTrack(delta, false);
            event.preventDefault();
        });

        function endDrag() {
            if (!dragging) return;
            dragging = false;
            stage.classList.remove('is-dragging');

            // A short flick counts as much as a long drag.
            const travelled = Math.abs(delta) / width();
            if (travelled > 0.18) {
                window.galleryShow(galleryIndex + (delta < 0 ? 1 : -1));
            } else {
                placeTrack(0, true);
            }

            delta = 0;
        }

        stage.addEventListener('pointerup', endDrag);
        stage.addEventListener('pointercancel', endDrag);
        stage.addEventListener('pointerleave', endDrag);
        stage.addEventListener('dragstart', event => event.preventDefault());
    }

    // Start on the first slide without animating into it.
    window.galleryShow(0, false);

    window.openLightbox = function () {
        if (!gallery.length) return;
        const box = document.getElementById('lightbox');
        document.getElementById('lightbox-image').src = gallery[galleryIndex];
        box.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    window.closeLightbox = function (event) {
        if (event && event.target.closest('#lightbox-image')) return;
        document.getElementById('lightbox').hidden = true;
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', event => {
        if (!document.getElementById('lightbox')) return;
        if (event.key === 'Escape') window.closeLightbox();
        if (event.key === 'ArrowRight') window.galleryStep(1);
        if (event.key === 'ArrowLeft') window.galleryStep(-1);
    });

    window.jumpToDownloads = function () {
        document.getElementById('downloads')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('downloads')?.classList.add('is-highlighted');
        setTimeout(() => document.getElementById('downloads')?.classList.remove('is-highlighted'), 1200);
    };

    /* --------------------------------------------------- cart / wishlist */

    window.goToCart = () => window.wasdNavigate(BASE + '/cart');
    window.goToWishlist = () => window.wasdNavigate(BASE + '/wishlist');

    window.addToAccount = async function (kind) {
        if (!signedIn) {
            WASD.toast('Sign in first to use your cart and wishlist.', 'error');
            return;
        }

        const result = await WASD.api(`/src/app/api/${kind}/index.php`, {
            json: { action: 'add', game_id: gameId }
        });

        if (!result.ok) {
            WASD.toast((result.data && result.data.error) || 'That did not work.', 'error');
            return;
        }

        const button = document.getElementById(kind === 'cart' ? 'cart-button' : 'wishlist-button');
        const label = document.getElementById(kind === 'cart' ? 'cart-button-text' : 'wishlist-button-text');

        label.textContent = kind === 'cart' ? 'In cart - view cart' : 'In wishlist';
        button.classList.remove('btn-accent');
        button.classList.add('btn-ghost');
        button.onclick = kind === 'cart' ? window.goToCart : window.goToWishlist;

        window.wasdBumpBadge?.(kind, 1);
        WASD.toast(kind === 'cart' ? 'Added to your cart.' : 'Added to your wishlist.', 'success');
    };

    /* ------------------------------------------------------------ reviews */

    const REVIEWS_API = '/src/app/api/reviews/index.php';

    // Seeded from the server when the viewer already reviewed this game, so the
    // form opens as an editor rather than pretending nothing is there.
    let selectedRating = <?= $myReview === null ? 'null' : ($myReview['enjoy'] ? 'true' : 'false') ?>;
    let hasReview = <?= $myReview ? 'true' : 'false' ?>;

    const form = document.getElementById('review-form');
    const textField = document.getElementById('review-text');
    const submitButton = document.getElementById('review-submit');
    const cancelButton = document.getElementById('review-cancel');
    const formTitle = document.getElementById('review-form-title');

    window.setRating = function (isPositive) {
        selectedRating = isPositive;
        document.getElementById('btn-up')?.classList.toggle('is-up', isPositive);
        document.getElementById('btn-down')?.classList.toggle('is-down', !isPositive);
    };

    /**
     * Repaints the rating in the header and the counts in More Information
     * from the figures the endpoint returns after a write, so the page never
     * shows a stale verdict while the reviewer is still looking at it.
     */
    function applyReviewSummary(summary) {
        if (!summary) return;

        const rating = document.getElementById('rating-value');
        if (rating) {
            rating.className = 'game-meta-value rating-' + summary.status;
            rating.innerHTML = WASD.escapeHtml(summary.label) +
                (summary.total > 0 ? ` <span class="text-dim">(${summary.total})</span>` : '');
        }

        const counts = document.getElementById('review-count-value');
        if (counts) {
            counts.innerHTML = summary.total +
                (summary.total > 0
                    ? ` <span class="text-dim">(${summary.positive} positive)</span>`
                    : '');
        }
    }

    /** Flips the form between "write" and "edit" wording. */
    function setFormMode(editing) {
        hasReview = editing;
        if (!form) return;

        form.classList.toggle('is-editing', editing);
        formTitle.textContent = editing ? 'Edit your review' : 'Write a review';
        submitButton.textContent = editing ? 'Update review' : 'Publish review';
        cancelButton.hidden = !editing;
    }

    function clearForm() {
        if (!form) return;
        textField.value = '';
        selectedRating = null;
        document.getElementById('btn-up')?.classList.remove('is-up');
        document.getElementById('btn-down')?.classList.remove('is-down');
        setFormMode(false);
    }

    window.cancelReviewEdit = function () {
        clearForm();
        WASD.toast('Edit discarded.', 'info');
    };

    window.publishReview = async function () {
        const text = textField.value.trim();

        if (selectedRating === null) return WASD.toast('Pick a thumbs up or down first.', 'error');
        if (text === '') return WASD.toast('Write a few words about the game.', 'error');

        submitButton.disabled = true;

        // The endpoint upserts, so this one call both publishes and edits.
        const result = await WASD.api(REVIEWS_API, {
            json: { game_id: gameId, enjoy: selectedRating, description: text }
        });

        submitButton.disabled = false;

        if (!result.ok) {
            WASD.toast((result.data && result.data.error) || 'Could not save that review.', 'error');
            return;
        }

        WASD.toast(result.data.updated ? 'Review updated.' : 'Review published.', 'success');
        applyReviewSummary(result.data.summary);
        setFormMode(true);
        resetReviews();
    };

    /* --------------------------------------------------- edit and delete */

    /** Pulls a card's own text and rating back into the form. */
    window.editMyReview = function (button) {
        const card = button.closest('.review-card');
        if (!card || !form) return;

        textField.value = card.querySelector('.review-card-body').innerText.trim();
        window.setRating(card.querySelector('.review-verdict.is-positive') !== null);
        setFormMode(true);

        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        textField.focus({ preventScroll: true });
    };

    window.deleteMyReview = async function (button) {
        const card = button.closest('.review-card');
        const reviewId = Number(card?.dataset.reviewId);

        if (!reviewId || !confirm('Delete your review? This cannot be undone.')) return;

        card.classList.add('is-busy');

        const result = await WASD.api(REVIEWS_API, {
            method: 'DELETE',
            json: { review_id: reviewId }
        });

        if (!result.ok) {
            card.classList.remove('is-busy');
            WASD.toast((result.data && result.data.error) || 'Could not delete that review.', 'error');
            return;
        }

        card.remove();
        clearForm();
        applyReviewSummary(result.data.summary);
        WASD.toast('Review deleted.', 'success');

        if (!list.querySelector('.review-card')) resetReviews();
    };

    /* ------------------------------------------------- review pagination */

    const anchor = document.getElementById('review-scroll-anchor');
    const list = document.getElementById('review-list');
    let reviewOffset = 0;

    async function loadReviews() {
        const result = await WASD.api(
            `/src/app/api/reviews/index.php?game_id=${gameId}&limit=5&offset=${reviewOffset}`
        );

        if (result.status === 204 || !result.data) {
            anchor.textContent = reviewOffset === 0
                ? 'No reviews yet. Be the first to write one.'
                : '';
            anchor.classList.toggle('is-empty', reviewOffset === 0);
            return false;
        }

        list.insertAdjacentHTML('beforeend', result.data);
        reviewOffset += 5;
        anchor.textContent = '';
        return true;
    }

    let scroller = WASD.infiniteScroll(anchor, loadReviews);

    function resetReviews() {
        scroller.stop();
        list.innerHTML = '';
        reviewOffset = 0;
        anchor.textContent = 'Loading reviews…';
        anchor.classList.remove('is-empty');
        scroller = WASD.infiniteScroll(anchor, loadReviews);
    }

    WASD.onPageReady(() => WASD.lazyImages(document));
})();
</script>
