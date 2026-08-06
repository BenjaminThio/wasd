<?php
    require_once __DIR__ . '/../../models/Icon.php';

    /**
     * Contact hub.
     *
     * Same three destinations as before (support, partners, press) - rebuilt on
     * the store's visual language instead of the old neon Metro tiles.
     */
    $channels = [
        [
            'icon' => 'sup',
            'accent' => 'cyan',
            'title' => 'Support',
            'copy' => 'Trouble with a download, a purchase or your account? Our team answers '
                    . 'every ticket, and the FAQ covers most questions instantly.',
            'link' => BASE_URL . '/contact/support',
            'label' => 'Visit the help centre',
        ],
        [
            'icon' => 'sport',
            'accent' => 'violet',
            'title' => 'Partners',
            'copy' => 'Hardware, engines, cloud and community - the companies that WASD is '
                    . 'built on, and how to join them.',
            'link' => BASE_URL . '/contact/partneshipX',
            'label' => 'See our partners',
        ],
        [
            'icon' => 'press',
            'accent' => 'magenta',
            'title' => 'Press',
            'copy' => 'Release announcements, review copies and everything our community is '
                    . 'saying about the games on WASD.',
            'link' => BASE_URL . '/contact/press',
            'label' => 'Read the newsroom',
        ],
    ];
?>

<div class="page contact-page">
    <header class="contact-hero reveal">
        <span class="eyebrow">We are listening</span>
        <h1 class="contact-title">Contact <span class="gradient-text">WASD</span></h1>
        <p class="page-subtitle">
            Questions about a game, a partnership or a story you are writing - pick the
            desk that fits and we will get back to you.
        </p>
    </header>

    <div class="contact-grid stagger">
        <?php foreach ($channels as $channel): ?>
            <a class="contact-card card card--interactive accent-<?= $channel['accent'] ?>"
               href="<?= $channel['link'] ?>">
                <span class="contact-icon"><?= Icon::get($channel['icon'], 34) ?></span>
                <h2 class="contact-card-title"><?= $channel['title'] ?></h2>
                <p class="contact-card-copy"><?= $channel['copy'] ?></p>
                <span class="contact-card-link">
                    <?= $channel['label'] ?> <?= Icon::get('chevron-right', 15) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <section class="contact-direct card reveal">
        <div class="contact-direct-text">
            <h2 class="section-title">Prefer to write to us?</h2>
            <p class="text-muted text-body">
                Send anything that does not fit the desks above and a human will read it.
                We usually reply within two working days.
            </p>
        </div>

        <div class="contact-details">
            <div class="contact-detail">
                <span class="mono-label">General</span>
                <span>hello@wasd.example</span>
            </div>
            <div class="contact-detail">
                <span class="mono-label">Support</span>
                <span>support@wasd.example</span>
            </div>
            <div class="contact-detail">
                <span class="mono-label">Press</span>
                <span>press@wasd.example</span>
            </div>
        </div>
    </section>
</div>
