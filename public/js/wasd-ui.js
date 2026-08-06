/* ==========================================================================
   WASD - SHARED FRONT-END RUNTIME
   One copy of the helpers that every page used to redefine for itself:
   money formatting, escaping, debouncing, lazy images, skeletons, toasts,
   infinite scroll and a small JSON fetch wrapper.

   Loaded once in the layout, so it survives SPA navigation.
   ========================================================================== */

(function (window, document) {
    'use strict';

    const BASE = window.BASE_URL || '';

    /* ---------------------------------------------------------------- utils */

    function money(value) {
        return 'RM' + Number(value || 0).toFixed(2);
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value).replace(
            /[&<>"']/g,
            c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])
        );
    }

    /** Delays a call until the user stops firing it - used by every search box. */
    function debounce(fn, wait) {
        let timer = null;
        const debounced = function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), wait === undefined ? 300 : wait);
        };
        debounced.cancel = () => clearTimeout(timer);
        return debounced;
    }

    function url(path) {
        return BASE + path;
    }

    /* ----------------------------------------------------------------- fetch */

    /** The per-session CSRF token the server put in the page head. */
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /** fetch() that always resolves to { ok, status, data } and never throws. */
    async function api(path, options) {
        const settings = Object.assign({ cache: 'no-store' }, options || {});

        if (settings.json !== undefined) {
            settings.method = settings.method || 'POST';
            settings.headers = Object.assign(
                { 'Content-Type': 'application/json' },
                settings.headers || {}
            );
            settings.body = JSON.stringify(settings.json);
            delete settings.json;
        }

        // Every write carries the token. Reads never need it, and adding it
        // to them would only widen where the value can leak.
        const method = (settings.method || 'GET').toUpperCase();
        if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
            settings.headers = Object.assign({ 'X-CSRF-Token': csrfToken() }, settings.headers || {});
        }

        try {
            const response = await fetch(path.startsWith('http') ? path : url(path), settings);

            if (response.status === 204) {
                return { ok: true, status: 204, data: null };
            }

            const text = await response.text();
            let data = null;

            if (text) {
                try {
                    data = JSON.parse(text);
                } catch {
                    data = text;
                }
            }

            return { ok: response.ok, status: response.status, data };
        } catch (error) {
            console.error('Request failed:', path, error);
            return { ok: false, status: 0, data: null, error };
        }
    }

    /* --------------------------------------------------------- lazy images */

    /**
     * Fades an image in once it has actually decoded.
     *
     * The class is added on the second animation frame rather than
     * immediately. A cached image reports complete === true the moment it is
     * bound, so setting the class straight away meant the browser painted the
     * final state on the very first frame and there was no transition to run -
     * the picture snapped in. Whether that happened depended on whether the
     * file was in the cache, which is why the effect looked random: warm cache
     * snapped, cold cache faded. Waiting for the "blurred, transparent" state
     * to be painted once makes the fade play the same way every time.
     */
    function markLoaded(img) {
        if (img.dataset.fadePending === '1' || img.classList.contains('is-loaded')) return;
        img.dataset.fadePending = '1';

        const reveal = () => {
            if (img.dataset.fadePending !== '1') return;
            delete img.dataset.fadePending;
            img.classList.add('is-loaded');
            const holder = img.closest('.media');
            if (holder) holder.classList.add('is-ready');
        };

        requestAnimationFrame(() => requestAnimationFrame(reveal));

        // Animation frames are paused while a tab is in the background, so a
        // timer guarantees the picture is never left invisible waiting for one.
        setTimeout(reveal, 200);
    }

    function markFailed(img) {
        // Nothing to fade in: stop the shimmer and let whatever sits behind the
        // image (the fallback artwork) show through instead of a broken icon.
        img.classList.remove('is-loaded');
        img.classList.add('is-broken');
        const holder = img.closest('.media');
        if (holder) holder.classList.add('is-ready', 'has-failed');
    }

    /**
     * Binds the fade-in handlers exactly once per image.
     *
     * The listeners are deliberately NOT { once: true }: an <img> can be
     * pointed at a new file later (the game-page gallery does exactly that),
     * and a one-shot listener left those swapped images stuck at opacity 0.
     */
    function lazyImages(root) {
        const scope = root || document;
        scope.querySelectorAll('img.img-lazy:not([data-lazy-bound])').forEach(img => {
            img.dataset.lazyBound = '1';
            if (!img.getAttribute('loading')) img.setAttribute('loading', 'lazy');
            img.setAttribute('decoding', 'async');

            img.addEventListener('load', () => markLoaded(img));
            img.addEventListener('error', () => markFailed(img));

            if (img.complete && img.naturalWidth > 0) markLoaded(img);
        });
    }

    /**
     * The one place cover markup is generated on the client.
     *
     * Cart, wishlist and checkout each used to build this string themselves,
     * and each of them dropped the fallback artwork behind an opaque
     * placeholder colour when a game had no cover image.
     *
     * @param {{cover?: string, fallback_art?: string, title?: string}} item
     */
    function cover(item, classes) {
        const art = escapeHtml(item.fallback_art || 'art-1');
        const label = escapeHtml(item.title || '');
        const shell = 'media ' + (classes || '');
        const art_layer = `<span class="media-art ${art}" aria-hidden="true"></span>`;

        if (!item.cover) {
            return `<div class="${shell} is-ready" role="img" aria-label="${label}">${art_layer}</div>`;
        }

        return `<div class="${shell}">${art_layer}` +
               `<img class="img-lazy" loading="lazy" decoding="async" ` +
               `src="${escapeHtml(item.cover)}" alt="${label} cover"></div>`;
    }

    /* ------------------------------------------------------------ skeletons */

    function skeletonCards(count) {
        let html = '';
        for (let i = 0; i < (count || 8); i++) {
            html +=
                '<div class="skeleton-card">' +
                    '<div class="skeleton skeleton-cover"></div>' +
                    '<div class="skeleton-lines">' +
                        '<div class="skeleton skeleton-text w-60"></div>' +
                        '<div class="skeleton skeleton-text w-full"></div>' +
                        '<div class="skeleton skeleton-text w-80"></div>' +
                        '<div class="skeleton skeleton-text w-40"></div>' +
                    '</div>' +
                '</div>';
        }
        return html;
    }

    function skeletonRows(count) {
        let html = '';
        for (let i = 0; i < (count || 3); i++) {
            html +=
                '<div class="skeleton-row">' +
                    '<div class="skeleton skeleton-thumb"></div>' +
                    '<div class="skeleton-lines">' +
                        '<div class="skeleton skeleton-text w-40"></div>' +
                        '<div class="skeleton skeleton-text w-80"></div>' +
                        '<div class="skeleton skeleton-text w-60"></div>' +
                    '</div>' +
                '</div>';
        }
        return html;
    }

    /* ---------------------------------------------------------------- toast */

    function toast(message, kind) {
        let host = document.getElementById('wasd-toasts');

        if (!host) {
            host = document.createElement('div');
            host.id = 'wasd-toasts';
            host.className = 'toast-host';
            document.body.appendChild(host);
        }

        const item = document.createElement('div');
        item.className = 'toast toast-' + (kind || 'info');
        item.setAttribute('role', 'status');
        item.textContent = message;
        host.appendChild(item);

        setTimeout(() => {
            item.classList.add('is-leaving');
            setTimeout(() => item.remove(), 300);
        }, 3200);
    }

    /* ------------------------------------------------------- infinite scroll */

    /**
     * Replaces the four hand-rolled IntersectionObserver blocks that used to
     * live in store, dashboard, cart and the game page.
     *
     * load() should return false when there is nothing left to fetch.
     */
    function infiniteScroll(anchor, load) {
        if (!anchor) return { stop() {} };

        let busy = false;
        let finished = false;

        async function run() {
            if (busy || finished) return;
            busy = true;

            try {
                const more = await load();
                if (more === false) {
                    finished = true;
                    observer.disconnect();
                }
            } finally {
                busy = false;
            }

            // The first batch may not fill the viewport - keep going if so.
            if (!finished && anchor.isConnected &&
                anchor.getBoundingClientRect().top < window.innerHeight) {
                run();
            }
        }

        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) run();
        }, { rootMargin: '300px' });

        observer.observe(anchor);

        return {
            stop() { finished = true; observer.disconnect(); },
            reset() { finished = false; observer.observe(anchor); run(); },
            trigger: run
        };
    }

    /* ------------------------------------------------------------ page hooks */

    /**
     * Page scripts are re-executed by the router on every swap, so they only
     * need "run once the markup around me exists". Nothing is queued here,
     * which keeps stale callbacks from piling up across navigations.
     */
    function onPageReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    // The persistent runtime re-scans for new images after every swap.
    document.addEventListener('wasd:page', () => lazyImages(document));

    window.WASD = {
        money,
        escapeHtml,
        debounce,
        url,
        api,
        csrfToken,
        lazyImages,
        cover,
        skeletonCards,
        skeletonRows,
        toast,
        infiniteScroll,
        onPageReady
    };
})(window, document);
