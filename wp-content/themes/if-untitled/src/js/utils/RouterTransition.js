import { prefersReducedMotion } from '@/js/utils/MediaBreakpoints.js';

/**
 * Fade overlay (.loader) between same-origin page navigations.
 * Singleton: `new RouterTransition()` always returns the same instance.
 */
export default class RouterTransition {
    static #instance;

    delay = 500;
    enterClass = 'is-entered';
    leaveClass = 'is-leaving';
    rootSelector = '.loader';

    constructor() {
        if (RouterTransition.#instance) return RouterTransition.#instance;
        RouterTransition.#instance = this;

        this.rootElement = document.querySelector(this.rootSelector);
        if (prefersReducedMotion()) this.delay = 0;
        this.main();
    }

    main() {
        document.addEventListener('click', (event) => this.onLinkClick(event));
        // Restore state when coming back via the bfcache (history back/forward).
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) this.transitionEnter();
        });
        this.transitionEnter();
    }

    onLinkClick(event) {
        const link = event.target.closest('a[href]');
        if (!link || !this.shouldHandle(link, event)) return;

        event.preventDefault();
        this.transitionLeave();
        setTimeout(() => window.location.assign(link.href), this.delay);
    }

    shouldHandle(link, event) {
        if (event.defaultPrevented || event.button !== 0) return false;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
        if (link.target && link.target !== '_self') return false;
        if (link.hasAttribute('download') || link.dataset.noTransition !== undefined) return false;

        const target = new URL(link.href, window.location.href);
        const current = new URL(window.location.href);
        if (target.origin !== current.origin) return false;
        if (/\/wp-(?:admin|login)/.test(target.pathname)) return false;
        // Files (pdf, zip, images…) may download instead of navigating, which would leave the overlay up.
        if (/\.(?!html?$|php$)[a-z0-9]{2,5}$/i.test(target.pathname)) return false;
        // Same page or in-page anchor.
        if (target.pathname === current.pathname && target.search === current.search) return false;

        return true;
    }

    transitionLeave() {
        this.rootElement?.classList.remove(this.enterClass);
        this.rootElement?.classList.add(this.leaveClass);
    }

    transitionEnter() {
        this.rootElement?.classList.remove(this.leaveClass);
        this.rootElement?.classList.add(this.enterClass);
    }
}
