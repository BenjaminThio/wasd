<?php
    require_once __DIR__ . '/../../models/Icon.php';
    require_once __DIR__ . '/../../models/ContactMessages.php';
    require_once __DIR__ . '/../../lib/Auth.php';

    // Prefilled for anyone already signed in - they have told us who they are
    // once already, and retyping it is the sort of small friction that stops
    // people writing in at all. Guests get an empty form and are equally
    // welcome to use it.
    $currentUser = Auth::getCurrentUser();

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

    <section class="contact-reach reveal" id="write-to-us">
        <!-- ------------------------------------------------------- the form -->
        <form class="contact-form card" id="contact-form" novalidate>
            <h2 class="section-title">Send us a message</h2>
            <p class="text-muted text-body">
                Anything that does not fit the desks above. A human reads every one of
                these, and we usually reply within two working days.
            </p>

            <div class="contact-form-row">
                <div class="input-group">
                    <label class="field-label" for="contact-name">Your name</label>
                    <input type="text" id="contact-name" name="name" class="field-input"
                           maxlength="<?= ContactMessages::NAME_MAX ?>"
                           autocomplete="name" placeholder="Who are we replying to?"
                           value="<?= htmlspecialchars($currentUser?->getUsername() ?? '', ENT_QUOTES) ?>">
                </div>

                <div class="input-group">
                    <label class="field-label" for="contact-email">Email address</label>
                    <input type="email" id="contact-email" name="email" class="field-input"
                           maxlength="191" autocomplete="email" placeholder="you@example.com"
                           value="<?= htmlspecialchars($currentUser?->getEmail() ?? '', ENT_QUOTES) ?>">
                </div>
            </div>

            <div class="input-group">
                <label class="field-label" for="contact-topic">What is it about?</label>
                <select id="contact-topic" name="topic" class="field-select">
                    <?php foreach (ContactMessages::TOPICS as $topic): ?>
                        <option value="<?= $topic ?>"><?= $topic ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <label class="field-label" for="contact-message">Message</label>
                <textarea id="contact-message" name="message" class="field-textarea" rows="6"
                          maxlength="<?= ContactMessages::MESSAGE_MAX ?>"
                          placeholder="Tell us what is going on..."></textarea>
                <span class="contact-counter" id="contact-counter">
                    0 / <?= ContactMessages::MESSAGE_MAX ?>
                </span>
            </div>

            <p class="contact-status" id="contact-status" role="status" aria-live="polite" hidden></p>

            <button type="submit" class="btn btn-primary" id="contact-submit">
                <?= Icon::get('press', 18) ?> Send message
            </button>
        </form>

        <!-- ---------------------------------------------------- the details -->
        <aside class="contact-direct card">
            <h2 class="section-title">Reach us directly</h2>

            <div class="contact-details">
                <div class="contact-detail">
                    <span class="mono-label">General</span>
                    <a href="mailto:hello@wasd.example">hello@wasd.example</a>
                </div>
                <div class="contact-detail">
                    <span class="mono-label">Support</span>
                    <a href="mailto:support@wasd.example">support@wasd.example</a>
                </div>
                <div class="contact-detail">
                    <span class="mono-label">Press</span>
                    <a href="mailto:press@wasd.example">press@wasd.example</a>
                </div>
                <div class="contact-detail">
                    <span class="mono-label">Phone</span>
                    <a href="tel:+60351234567">+60 3-5123 4567</a>
                    <span class="contact-note">Mon-Fri, 9am-6pm (GMT+8)</span>
                </div>
                <div class="contact-detail">
                    <span class="mono-label">Office</span>
                    <address class="contact-address">
                        WASD Interactive Sdn. Bhd.<br>
                        Level 8, Menara Kembar<br>
                        Jalan Genting Kelang, Setapak<br>
                        53300 Kuala Lumpur, Malaysia
                    </address>
                </div>
            </div>
        </aside>
    </section>
</div>

<script>
    /*
    * Wrapped in an IIFE like every other page script - the SPA router injects
    * this markup without a full page load, so anything declared at the top
    * level here would collide with itself the second time the page is visited.
    */
    (() => {
        const form = document.getElementById('contact-form');
        if (!form) return;

        const status = document.getElementById('contact-status');
        const button = document.getElementById('contact-submit');
        const messageField = document.getElementById('contact-message');
        const counter = document.getElementById('contact-counter');
        const limit = <?= ContactMessages::MESSAGE_MAX ?>;
        const minimum = <?= ContactMessages::MESSAGE_MIN ?>;

        const show = (text, ok) => {
            status.textContent = text;
            status.hidden = !text;
            status.classList.toggle('is-success', !!ok);
        };

        const mark = (field, invalid) => field.classList.toggle('is-invalid', invalid);

        // Live count, so nobody writes 2000 characters and only then finds out.
        const updateCounter = () => {
            const used = messageField.value.length;
            counter.textContent = used + ' / ' + limit;
            counter.classList.toggle('is-near-limit', used > limit * 0.9);
        };

        messageField.addEventListener('input', updateCounter);
        updateCounter();

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const name = document.getElementById('contact-name');
            const email = document.getElementById('contact-email');
            const topic = document.getElementById('contact-topic');

            [name, email, messageField].forEach(field => mark(field, false));
            show('');

            // The same rules the server enforces, checked here first purely so
            // the answer is instant. The server still decides.
            if (name.value.trim().length < <?= ContactMessages::NAME_MIN ?>) {
                mark(name, true);
                name.focus();
                return show('Tell us your name so we know who we are replying to.');
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                mark(email, true);
                email.focus();
                return show('Enter a valid email address so we can reply to you.');
            }

            if (messageField.value.trim().length < minimum) {
                mark(messageField, true);
                messageField.focus();
                return show('Tell us a little more - at least ' + minimum + ' characters.');
            }

            button.disabled = true;
            const original = button.innerHTML;
            button.textContent = 'SENDING…';

            try {
                const response = await fetch('<?= BASE_URL ?>/src/app/api/contact/index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        name: name.value.trim(),
                        email: email.value.trim(),
                        topic: topic.value,
                        message: messageField.value.trim(),
                    }),
                });

                const data = await response.json().catch(() => null);

                if (!response.ok || !data || data.status !== 'success') {
                    return show((data && data.error) || 'Could not send your message. Try again.');
                }

                show(data.message, true);
                messageField.value = '';
                updateCounter();
            } catch (err) {
                show('Could not reach the server. Check your connection and try again.');
            } finally {
                button.disabled = false;
                button.innerHTML = original;
            }
        });
    })();
</script>
