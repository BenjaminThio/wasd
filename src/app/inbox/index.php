<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../models/ContactMessages.php';
    require_once __DIR__ . '/../../models/Icon.php';

    $user = Auth::getCurrentUser();

    /*
       Two different refusals, because they mean different things to whoever is
       reading them. Not signed in is fixable by signing in, so it offers that.
       Signed in without the staff flag is not, so it says so plainly rather
       than bouncing them to a sign-in form they have already used.
    */
    if (!$user) {
        echo '<div class="page"><div class="empty-state">'
           . 'Sign in to read the contact inbox.<br>'
           . '<a href="' . BASE_URL . '/sign-in">Sign in</a></div></div>';
        return;
    }

    if (!$user->isAdmin()) {
        echo '<div class="page"><div class="empty-state">'
           . 'The contact inbox is for staff accounts only.<br>'
           . '<a href="' . BASE_URL . '/">Back to the store</a></div></div>';
        return;
    }

    $messages = ContactMessages::all();
    $unread   = ContactMessages::unreadCount();

    $topicAccent = [
        'General'     => 'cyan',
        'Support'     => 'violet',
        'Partnership' => 'magenta',
        'Press'       => 'amber',
    ];
?>

<div class="page">
    <div class="page-head reveal">
        <div class="flex-col gap-2">
            <span class="eyebrow">Staff</span>
            <h1 class="page-title">Contact Inbox</h1>
            <!-- <p class="page-subtitle">
                Everything sent through the form on the contact page. Messages arrive
                here rather than by email, so nothing depends on a mail server being
                configured.
            </p> -->
        </div>

        <a class="btn btn-ghost" href="<?= BASE_URL ?>/contact">
            <?= Icon::get('press', 16) ?> View the contact page
        </a>
    </div>

    <div class="inbox-summary reveal">
        <div>
            <span class="inbox-count"><?= count($messages) ?></span>
            total,
            <span class="inbox-count is-unread" id="inbox-unread"><?= $unread ?></span>
            unread
        </div>

        <!-- Disabled rather than hidden when everything is already read: a
             control that disappears leaves the reader wondering whether they
             imagined it, and the disabled state explains itself. -->
        <button type="button" class="btn btn-ghost btn-sm" id="inbox-read-all"
                <?= $unread > 0 ? '' : 'disabled' ?>>
            <?= Icon::get('check', 15) ?> Mark all as read
        </button>
    </div>

    <?php if (empty($messages)): ?>
        <div class="empty-state reveal" id="inbox-empty">
            No messages yet.<br>
            Send one through <a href="<?= BASE_URL ?>/contact">the contact form</a> and it
            will appear here.
        </div>
    <?php else: ?>
        <div class="inbox-list stagger" id="inbox-list">
            <?php foreach ($messages as $row): ?>
                <?php
                    $id     = (int)$row['id'];
                    $isNew  = $row['status'] === 'New';
                    $accent = $topicAccent[$row['topic']] ?? 'cyan';
                ?>
                <article class="inbox-card card accent-<?= $accent ?><?= $isNew ? ' is-unread' : '' ?>"
                         id="message-<?= $id ?>" data-id="<?= $id ?>">
                    <header class="inbox-head">
                        <div class="inbox-from">
                            <span class="inbox-name"><?= htmlspecialchars($row['name']) ?></span>
                            <a class="inbox-email" href="mailto:<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($row['email']) ?>
                            </a>
                        </div>

                        <div class="inbox-tags">
                            <span class="inbox-topic"><?= htmlspecialchars($row['topic']) ?></span>
                            <?php if ($row['username'] !== null): ?>
                                <!-- Only when the sender was signed in. A guest enquiry
                                     has no account behind it and shows no badge. -->
                                <span class="inbox-account">
                                    <?= Icon::get('user', 12) ?> <?= htmlspecialchars($row['username']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="inbox-status"><?= $isNew ? 'NEW' : 'READ' ?></span>
                        </div>
                    </header>

                    <p class="inbox-message"><?= nl2br(htmlspecialchars($row['message'])) ?></p>

                    <footer class="inbox-foot">
                        <time class="inbox-date" datetime="<?= htmlspecialchars($row['created_at']) ?>">
                            <?= (new DateTime($row['created_at']))->format('j M Y, g:ia') ?>
                        </time>

                        <?php
                            /*
                               A mailto: link only does something when the machine has a
                               desktop mail client registered to handle the protocol.
                               Plenty do not, and on those nothing happens at all when you
                               click it, which looks like the button is broken. So the
                               reply opens Gmail's compose window in a new tab instead: it
                               is a URL like any other, so it works in any browser without
                               anything being configured first.
                            */
                            $replySubject = 'Re: ' . $row['topic'] . ' enquiry';
                            $replyBody    = "\n\n---\nYou wrote to WASD on "
                                          . (new DateTime($row['created_at']))->format('j M Y')
                                          . ":\n\n" . $row['message'];

                            $gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1'
                                      . '&to=' . rawurlencode($row['email'])
                                      . '&su=' . rawurlencode($replySubject)
                                      . '&body=' . rawurlencode($replyBody);
                        ?>
                        <div class="inbox-actions">
                            <button type="button" class="btn btn-ghost btn-sm inbox-toggle"
                                    data-id="<?= $id ?>">
                                <?= $isNew ? 'Mark as read' : 'Mark as unread' ?>
                            </button>

                            <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener"
                               href="<?= htmlspecialchars($gmailUrl, ENT_QUOTES) ?>">
                                Reply in Gmail
                            </a>

                            <!-- For anyone not using Gmail. The address goes to the
                                 clipboard and they paste it wherever they actually read
                                 mail. -->
                            <button type="button" class="btn btn-ghost btn-sm inbox-copy"
                                    data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>">
                                Copy address
                            </button>

                            <button type="button" class="btn btn-ghost btn-sm inbox-delete"
                                    data-id="<?= $id ?>">
                                Delete
                            </button>
                        </div>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    /*
    * Wrapped like every other page script: the router injects this markup
    * without a document load, so anything at the top level here would collide
    * with itself the second time the page is opened.
    */
    (() => {
        /*
           Sync the header badge to this page's own figure straight away, before
           anything is clicked.

           The header is rendered once per document load and is not re-rendered
           when the router swaps pages, so its count is from whenever the
           document was loaded. Arriving here through a soft navigation after
           reading messages in an earlier visit would otherwise show yesterday's
           number until a full reload.
        */
        window.wasdSetBadge?.('inbox', <?= (int)$unread ?>);

        const list = document.getElementById('inbox-list');
        if (!list || list.dataset.bound) return;
        list.dataset.bound = '1';

        const unreadCounter = document.getElementById('inbox-unread');
        const readAllButton = document.getElementById('inbox-read-all');
        const API = '<?= BASE_URL ?>/src/app/api/inbox/index.php';

        async function send(action, id) {
            const response = await fetch(API, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ action, id }),
            });

            const data = await response.json().catch(() => null);

            if (!response.ok || !data || data.status !== 'success') {
                WASD.toast((data && data.error) || 'That did not work. Try again.', 'error');
                return null;
            }

            // The server sends the recalculated total rather than the page
            // adjusting its own number, so the badge cannot drift out of step
            // with the table.
            if (unreadCounter) unreadCounter.textContent = data.unread;

            // The header is rendered once by PHP and is not re-rendered when the
            // router swaps pages, so its badge has to be told separately. Without
            // this it kept showing the count from whenever the page last loaded,
            // however many messages had been marked read since.
            window.wasdSetBadge?.('inbox', data.unread);

            // Nothing left to mark, so the bulk control has nothing to do.
            if (readAllButton) readAllButton.disabled = data.unread === 0;

            return data;
        }

        /* -------------------------------------------------------- mark all */

        readAllButton?.addEventListener('click', async () => {
            const unreadCards = list.querySelectorAll('.inbox-card.is-unread');
            if (!unreadCards.length) return;

            readAllButton.disabled = true;

            // One request for the whole table rather than one per card. Twenty
            // unread messages would otherwise be twenty round trips, and twenty
            // chances for one of them to fail and leave the page disagreeing
            // with the database.
            const data = await send('read-all', 0);

            if (!data) {
                readAllButton.disabled = false;
                return;
            }

            // Repaint every card the one statement just changed.
            unreadCards.forEach(card => {
                card.classList.remove('is-unread');
                card.querySelector('.inbox-status').textContent = 'READ';
                card.querySelector('.inbox-toggle').textContent = 'Mark as unread';
            });

            WASD.toast(
                data.changed === 1
                    ? 'Marked 1 message as read.'
                    : 'Marked ' + data.changed + ' messages as read.',
                'success'
            );
        });

        // One listener on the list rather than several per card: the cards are
        // rendered by PHP, and this keeps working if any are added later.
        list.addEventListener('click', async (event) => {
            const copy = event.target.closest('.inbox-copy');

            if (copy) {
                const address = copy.dataset.email;

                try {
                    await navigator.clipboard.writeText(address);
                    WASD.toast('Copied ' + address, 'success');
                } catch (err) {
                    // Refused when the page is not a secure context, or when the
                    // browser withholds clipboard permission. Showing the address
                    // is better than a failure the user cannot act on.
                    WASD.toast('Could not copy. The address is ' + address, 'error');
                }
                return;
            }

            const toggle = event.target.closest('.inbox-toggle');
            const remove = event.target.closest('.inbox-delete');
            if (!toggle && !remove) return;

            const button = toggle || remove;
            const id = Number(button.dataset.id);
            const card = document.getElementById('message-' + id);

            button.disabled = true;

            if (toggle) {
                const nowUnread = card.classList.contains('is-unread');
                const data = await send(nowUnread ? 'read' : 'unread', id);

                if (data) {
                    card.classList.toggle('is-unread', !nowUnread);
                    button.textContent = nowUnread ? 'Mark as unread' : 'Mark as read';
                    card.querySelector('.inbox-status').textContent = nowUnread ? 'READ' : 'NEW';
                }

                button.disabled = false;
                return;
            }

            if (!confirm('Delete this message? This cannot be undone.')) {
                button.disabled = false;
                return;
            }

            const data = await send('delete', id);

            if (!data) {
                button.disabled = false;
                return;
            }

            card.remove();

            if (!list.children.length) {
                list.innerHTML = '<div class="empty-state">No messages left.</div>';
            }
        });
    })();
</script>
