<?php
    require_once __DIR__ . '/../../../models/Icon.php';

    $page->setTitle('Partners');

    /**
     * Partners.
     *
     * The same eight partners as before. They were eight near-identical blocks
     * of markup with hand-numbered wrapper classes (cont1 … cont8); they are a
     * list and one loop now, which also makes the grid responsive for free.
     */
    $assets = BASE_URL . '/public/assets/partnership/';
    $assetRoot = __DIR__ . '/../../../../public/assets/partnership/';

    $partners = [
        ['nvdia.png', 'NVIDIA', 'Hardware',
         'NVIDIA provides WASD with cutting-edge GPU technologies and AI-powered rendering '
         . 'solutions that significantly enhance the visual quality and performance of our games.'],
        ['mic.png', 'Intellimize', 'Cloud',
         'Microsoft provides WASD with cloud computing infrastructure and gaming ecosystem '
         . 'support, enabling secure online multiplayer services.'],
        ['sony.png', 'Sony Interactive', 'Platform',
         'Sony Interactive Entertainment supports WASD by providing PlayStation platform '
         . 'integration, technical development resources, and performance optimization.'],
        ['epic.png', 'Epic Games', 'Engine',
         'Epic Games empowers WASD through Unreal Engine, providing industry-leading game '
         . 'development technology that enables realistic environments.'],
        ['razerr.png', 'Razer', 'Peripherals',
         'Razer enhances the WASD gaming ecosystem through hardware compatibility, peripheral '
         . 'optimization, and esports collaboration.'],
        ['dis.png', 'Discord', 'Community',
         "Discord strengthens WASD's player community by providing integrated communication "
         . 'platforms that support real-time interaction.'],
        ['and.png', 'Android', 'Mobile',
         'Android supports WASD by providing platform integration, mobile development tools and '
         . 'performance optimization, so games run smoothly across Android devices.'],
        ['aws.jpg', 'AWS', 'Infrastructure',
         'Amazon supports WASD with cloud infrastructure, scalable hosting and content delivery, '
         . 'keeping servers reliable and player data secure worldwide.'],
    ];
?>

<div class="page partners-page">
    <header class="partners-hero reveal">
        <span class="eyebrow">Better together</span>
        <h1 class="contact-title">Our <span class="gradient-text">partners</span></h1>
        <p class="page-subtitle">
            Powered by great partnerships, built for amazing players. These are the companies
            behind the hardware, engines, platforms and infrastructure that WASD runs on.
        </p>
    </header>

    <div class="partners-grid stagger">
        <?php foreach ($partners as [$image, $name, $kind, $copy]): ?>
            <article class="partner-card card card--interactive">
                <div class="partner-logo media">
                    <?php if (is_file($assetRoot . $image)): ?>
                        <img class="img-lazy" loading="lazy" decoding="async"
                             src="<?= $assets . $image ?>" alt="<?= htmlspecialchars($name) ?> logo">
                    <?php endif; ?>
                </div>

                <div class="partner-body">
                    <div class="partner-head">
                        <h2 class="partner-name"><?= htmlspecialchars($name) ?></h2>
                        <span class="badge"><?= htmlspecialchars($kind) ?></span>
                    </div>
                    <p class="partner-copy"><?= htmlspecialchars($copy) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="partners-cta card reveal">
        <div class="flex-col gap-2">
            <h2 class="section-title">Want to work with WASD?</h2>
            <p class="text-muted text-body">
                Tell us what you are building and how you would like to collaborate.
            </p>
        </div>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Talk to the team</a>
    </section>
</div>
