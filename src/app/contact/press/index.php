<?php
    require_once __DIR__ . '/../../../models/Icon.php';

    $page->setTitle('Newsroom');

    /**
     * Newsroom.
     *
     * The four articles and three community quotes are unchanged - they are
     * data now, and the "slide dots" that used to call a function that was
     * never defined actually work.
     */
    $assets = BASE_URL . '/public/assets/press/';
    $assetRoot = __DIR__ . '/../../../../public/assets/press/';

    $articles = [
        [
            'image' => 'game1.png',
            'platform' => 'XBOX',
            'accent' => 'green',
            'date' => 'MAR 14, 2026',
            'title' => 'Shadow Protocol: Version 2.0',
            'copy' => 'Shadow Protocol: Version 2.0 introduces an enhanced open-world experience '
                    . 'with upgraded graphics, improved AI, and an expanded multiplayer mode.',
            'comments' => '17K',
        ],
        [
            'image' => 'game2.png',
            'platform' => 'PS 5',
            'accent' => 'blue',
            'date' => 'MAR 18, 2026',
            'title' => 'Legends of Aether: Reborn',
            'copy' => 'Legends of Aether: Reborn is the next evolution of the popular fantasy RPG '
                    . 'series. The new version features a redesigned combat system.',
            'comments' => '432',
        ],
        [
            'image' => 'game3.png',
            'platform' => 'PC',
            'accent' => 'purple',
            'date' => 'MAR 20, 2026',
            'title' => 'Cyber Nexus: Evolution',
            'copy' => 'Cyber Nexus: Evolution is an action-packed sci-fi adventure that brings '
                    . 'players into a futuristic cyber world filled with advanced technology.',
            'comments' => '562',
        ],
        [
            'image' => 'game4.png',
            'platform' => 'ANDROID',
            'accent' => 'magenta',
            'date' => 'MAR 23, 2026',
            'title' => 'Last Transmission',
            'copy' => "After Earth's first deep-space mission goes silent, a special operative is "
                    . 'sent to recover the lost crew. Inside the spacecraft, they discover an '
                    . 'experimental AI system.',
            'comments' => '362',
        ],
    ];

    $voices = [
        [
            'image' => 'beast.png',
            'name' => 'Michael',
            'tag' => 'Gamer',
            'quote' => "The attention to detail in WASD's games is exceptional. From immersive "
                     . 'environments to responsive gameplay, every title feels polished and enjoyable.',
        ],
        [
            'image' => 'pew.png',
            'name' => 'Miguel Carpenter',
            'tag' => 'Gamer',
            'quote' => 'WASD consistently delivers high-quality games with outstanding graphics and '
                     . 'smooth performance. Every update introduces exciting new features.',
        ],
        [
            'image' => 'jack.png',
            'name' => 'Jack M.',
            'tag' => 'Gamer',
            'quote' => 'After spending some time with this game, I can confidently say that it has a '
                     . 'lot of potential. The first thing that caught my attention was the incredible '
                     . 'art direction.',
        ],
    ];
?>

<div class="page press-page">
    <header class="press-hero reveal">
        <span class="eyebrow">Newsroom</span>
        <h1 class="contact-title">Our latest <span class="gradient-text">posts</span></h1>
        <p class="page-subtitle">
            The newest games, fresh experiences and upcoming adventures from our creators -
            innovative gameplay, immersive worlds and stories waiting to be discovered.
        </p>
    </header>

    <div class="news-grid stagger">
        <?php foreach ($articles as $article): ?>
            <article class="news-card card--interactive">
                <div class="news-media media">
                    <?php if (is_file($assetRoot . $article['image'])): ?>
                        <img class="img-lazy" loading="lazy" decoding="async"
                             src="<?= $assets . $article['image'] ?>"
                             alt="<?= htmlspecialchars($article['title']) ?>">
                    <?php endif; ?>
                </div>

                <div class="news-body">
                    <div class="news-meta">
                        <span class="game-tag <?= $article['accent'] ?>"><?= $article['platform'] ?></span>
                        <span class="news-date"><?= $article['date'] ?></span>
                    </div>

                    <h2 class="news-title"><?= htmlspecialchars($article['title']) ?></h2>
                    <p class="news-copy"><?= htmlspecialchars($article['copy']) ?></p>

                    <footer class="news-footer">
                        <span><?= Icon::get('user', 14) ?> by Admin</span>
                        <span><?= Icon::get('star', 14) ?> <?= $article['comments'] ?></span>
                    </footer>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="voices reveal">
        <h2 class="section-title text-center">Community feedback</h2>

        <div class="voices-viewport">
            <button type="button" class="voices-arrow" onclick="voicesStep(-1)" aria-label="Previous">
                <?= Icon::get('chevron-right', 20) ?>
            </button>

            <div class="voices-track" id="voices-track">
                <?php foreach ($voices as $voice): ?>
                    <figure class="voice-card card">
                        <div class="voice-avatar media">
                            <?php if (is_file($assetRoot . $voice['image'])): ?>
                                <img class="img-lazy" loading="lazy" decoding="async"
                                     src="<?= $assets . $voice['image'] ?>"
                                     alt="<?= htmlspecialchars($voice['name']) ?>">
                            <?php endif; ?>
                        </div>

                        <figcaption class="voice-name"><?= htmlspecialchars($voice['name']) ?></figcaption>
                        <span class="voice-tag"><?= htmlspecialchars($voice['tag']) ?></span>
                        <blockquote class="voice-quote"><?= htmlspecialchars($voice['quote']) ?></blockquote>
                    </figure>
                <?php endforeach; ?>
            </div>

            <button type="button" class="voices-arrow is-next" onclick="voicesStep(1)" aria-label="Next">
                <?= Icon::get('chevron-right', 20) ?>
            </button>
        </div>

        <div class="voices-dots" id="voices-dots">
            <?php foreach ($voices as $index => $voice): ?>
                <button type="button" class="voice-dot<?= $index === 0 ? ' is-active' : '' ?>"
                        onclick="voicesGo(<?= $index ?>)"
                        aria-label="Go to quote <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
(() => {
    const track = document.getElementById('voices-track');
    const dots = Array.from(document.querySelectorAll('.voice-dot'));
    const cards = Array.from(track.querySelectorAll('.voice-card'));

    function activeIndex() {
        // Whichever card sits closest to the left edge of the viewport wins.
        const left = track.scrollLeft;
        let best = 0;
        let bestDistance = Infinity;

        cards.forEach((card, index) => {
            const distance = Math.abs(card.offsetLeft - track.offsetLeft - left);
            if (distance < bestDistance) {
                bestDistance = distance;
                best = index;
            }
        });

        return best;
    }

    function syncDots() {
        const current = activeIndex();
        dots.forEach((dot, index) => dot.classList.toggle('is-active', index === current));
    }

    window.voicesGo = function (index) {
        const card = cards[Math.max(0, Math.min(cards.length - 1, index))];
        if (card) track.scrollTo({ left: card.offsetLeft - track.offsetLeft, behavior: 'smooth' });
    };

    window.voicesStep = step => window.voicesGo(activeIndex() + step);

    track.addEventListener('scroll', WASD.debounce(syncDots, 90));
    syncDots();
})();
</script>
