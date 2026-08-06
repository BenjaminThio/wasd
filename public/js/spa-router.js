/* ==========================================================================
   WASD - SPA ROUTER
   Intercepts internal link clicks, asks the PHP router for the page as JSON
   and swaps only the <main> content, the title and the page stylesheet.

   Anything that is not a normal in-app navigation (downloads, files, new
   tabs, modifier-clicks, anchors) is deliberately left to the browser.
   ========================================================================== */

(function (window, document) {
    'use strict';

    const ROOT_ID = 'app-root';
    let navigating = false;

    // Which document is currently rendered, ignoring the fragment. Used to tell
    // "went to another page" from "jumped to a heading on this one".
    let currentPage = window.location.pathname + window.location.search;

    /*
       Own the scroll position outright.

       Browsers restore the previous offset on reload, and on a page whose
       content arrives after paint (the store keeps appending cards) that
       restore lands somewhere arbitrary - which is why refreshing the store
       nudged the window down a little every time. The router decides where a
       page starts instead: the top, or the element named in the hash.
    */
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    /* --------------------------------------------------- what we may handle */

    function isPlainLeftClick(event) {
        return !event.defaultPrevented &&
               event.button === 0 &&
               !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
    }

    function isRoutableLink(link) {
        if (!link || !link.href) return false;
        if (link.origin !== window.location.origin) return false;

        // Explicit opt-outs
        if (link.hasAttribute('download')) return false;
        if (link.hasAttribute('data-no-spa')) return false;
        if (link.target && link.target !== '_self') return false;
        if ((link.getAttribute('rel') || '').includes('external')) return false;

        const href = link.getAttribute('href') || '';
        if (href.startsWith('#')) return false;
        if (/^(mailto:|tel:|javascript:)/i.test(href)) return false;

        // Same page, different hash - let the browser scroll.
        if (link.pathname === window.location.pathname && link.hash) return false;

        // Physical files (uploads, assets, API endpoints) are not SPA routes.
        if (/\.(php|zip|rar|7z|exe|msi|apk|aab|dmg|pkg|jar|bin|png|jpe?g|gif|webp|avif|svg|mp4|pdf|css|js)$/i
                .test(link.pathname)) {
            return false;
        }

        return true;
    }

    /* ---------------------------------------------------------- the swapper */

    /* ------------------------------------------------------- scroll targets */

    /**
     * Scrolls an in-page target into view, honouring its scroll-margin so the
     * heading does not end up hidden under the sticky header.
     */
    function scrollToHash(hash, smooth) {
        if (!hash || hash === '#') return false;

        let target = null;
        try {
            target = document.querySelector(hash);
        } catch {
            return false;
        }

        if (!target) return false;

        target.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
        return true;
    }

    function scrollToTop() {
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }

    /* ------------------------------------------------------- header state */

    /**
     * The header is rendered once by PHP and lives outside the injection zone,
     * so its "you are here" underline never moved during a soft navigation.
     * It is recalculated here after every swap.
     */
    function syncActiveNav() {
        const tidy = path => path.replace(/\/+$/, '');
        const here = tidy(window.location.pathname);

        // Exact match only, so /contact/support highlights Support and not
        // Contact as well - the same rule the PHP header applies on a full load.
        document.querySelectorAll('.header a.nav-link, .header a.nav-icon-link').forEach(link => {
            link.classList.toggle('is-active', tidy(link.pathname) === here);
        });
    }

    function runInjectedScripts(root) {
        root.querySelectorAll('script').forEach(script => {
            const fresh = document.createElement('script');
            Array.from(script.attributes).forEach(a => fresh.setAttribute(a.name, a.value));
            fresh.textContent = script.textContent;
            script.parentNode.replaceChild(fresh, script);
        });
    }

    async function navigate(href, addToHistory) {
        if (navigating) return;
        navigating = true;
        document.documentElement.classList.add('is-navigating');

        try {
            const response = await fetch(href, { headers: { 'X-SPA-Request': 'true' } });
            if (!response.ok) throw new Error('Router replied ' + response.status);

            const data = await response.json();

            // A different shell (for example the auth layout with its video
            // background) cannot be soft-swapped - reload instead.
            //
            // `reload` covers the other case: a page whose response headers do
            // the work. The game page sends cross-origin isolation headers so
            // an embedded HTML5 build can use SharedArrayBuffer, and headers
            // only arrive with a real document.
            if (data.layout !== window.CURRENT_LAYOUT || data.reload) {
                window.location.href = href;
                return;
            }

            document.getElementById('page-title').innerText = data.title;
            document.getElementById('dynamic-page-style').href =
                data.css || (window.BASE_URL || '') + '/src/app/blank.css';

            const root = document.getElementById(ROOT_ID);
            root.innerHTML = data.html;
            runInjectedScripts(root);

            if (addToHistory !== false) history.pushState({ spa: true }, '', href);

            currentPage = window.location.pathname + window.location.search;
            syncActiveNav();

            // A hash in the URL is only honoured by the browser on a real
            // document load, so the router has to place the view itself.
            const hash = new URL(href, window.location.origin).hash;
            if (!scrollToHash(hash, false)) scrollToTop();

            // Let shared helpers and page scripts re-initialise.
            document.dispatchEvent(new CustomEvent('wasd:page', { detail: { url: href } }));
        } catch (error) {
            console.error('SPA navigation failed, falling back to a full load:', error);
            window.location.href = href;
        } finally {
            navigating = false;
            document.documentElement.classList.remove('is-navigating');
        }
    }

    /* ------------------------------------------------------------- wiring up */

    document.addEventListener('click', event => {
        if (!isPlainLeftClick(event)) return;

        const link = event.target.closest('a');
        if (!link || link.origin !== window.location.origin) return;

        // In-page anchors (the help centre topic chips) are handled here rather
        // than left to the browser: after a soft navigation the document was
        // never re-loaded, so the browser has no fragment to act on.
        const samePage = link.pathname === window.location.pathname &&
                         link.search === window.location.search;

        if (link.hash && samePage && !link.hasAttribute('data-no-spa')) {
            if (scrollToHash(link.hash, true)) {
                event.preventDefault();
                history.pushState({ spa: true }, '', link.href);
            }
            return;
        }

        if (!isRoutableLink(link)) return;

        event.preventDefault();
        navigate(link.href, true);
    });

    window.addEventListener('popstate', () => {
        // Back/forward between two hashes of the same page is a scroll, not a
        // page load - only the fragment changed, so the markup is still valid.
        const target = window.location.pathname + window.location.search;

        if (target === currentPage) {
            if (!scrollToHash(window.location.hash, true)) scrollToTop();
            return;
        }

        navigate(window.location.href, false);
    });

    // Programmatic navigation for buttons and JS handlers.
    window.wasdNavigate = href => navigate(href, true);

    /**
     * Pages that rewrite their own query string with replaceState (the store's
     * filters) tell the router about it, so back/forward keeps working out
     * whether it is looking at a new page or the same one.
     */
    window.wasdSyncLocation = () => {
        currentPage = window.location.pathname + window.location.search;
    };

    // First paint counts as a page load for anything listening.
    document.addEventListener('DOMContentLoaded', () => {
        syncActiveNav();

        // Scroll restoration is manual, so place the view deliberately.
        if (!scrollToHash(window.location.hash, false)) scrollToTop();

        document.dispatchEvent(new CustomEvent('wasd:page', { detail: { initial: true } }));
    });
})(window, document);
