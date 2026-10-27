/**
 * Infinite scroll as progressive enhancement over native /page/N/ links. When the pagination enters the viewport,
 * fetches the next page, appends its items, swaps in its pagination and updates the URL (history.replaceState).
 * Stops at the last page (no next link), so the footer stays reachable.
 *
 * Markup: [data-infinite-scroll] list of [data-infinite-scroll-item], followed by
 * [data-infinite-scroll-pagination] containing a[data-infinite-scroll-next].
 */
export default class InfiniteScroll {
    loadingClass = 'is-loading';

    constructor({
        selector = '[data-infinite-scroll]',
        itemSelector = '[data-infinite-scroll-item]',
        paginationSelector = '[data-infinite-scroll-pagination]',
        nextSelector = '[data-infinite-scroll-next]',
        rootMargin = '600px 0px',
    } = {}) {
        this.element = document.querySelector(selector);
        this.pagination = document.querySelector(paginationSelector);
        if (this.element === null || this.pagination === null) return;
        this.selector = selector;
        this.itemSelector = itemSelector;
        this.paginationSelector = paginationSelector;
        this.nextSelector = nextSelector;
        this.observer = new IntersectionObserver((entries) => this.#onIntersect(entries), { rootMargin });
        this.main();
    }

    main() {
        if (this.#nextUrl) this.observer.observe(this.pagination);
    }

    get #nextUrl() {
        return this.pagination.querySelector(this.nextSelector)?.href ?? '';
    }

    #onIntersect(entries) {
        if (entries.some((entry) => entry.isIntersecting)) this.#loadNext();
    }

    async #loadNext() {
        const url = this.#nextUrl;
        if (!url || this.loading) return;
        this.loading = true;
        this.element.classList.add(this.loadingClass);
        this.element.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, { headers: { Accept: 'text/html' } });
            if (!response.ok) throw new Error(`${response.status} ${url}`);
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const items = page.querySelectorAll(`${this.selector} ${this.itemSelector}`);
            this.element.append(...[...items].map((item) => document.adoptNode(item)));

            const pagination = page.querySelector(this.paginationSelector);
            this.observer.unobserve(this.pagination);
            if (pagination) {
                const next = document.adoptNode(pagination);
                this.pagination.replaceWith(next);
                this.pagination = next;
            } else {
                this.pagination.remove();
            }
            history.replaceState(history.state, '', url);
            if (this.pagination.isConnected && this.#nextUrl) this.observer.observe(this.pagination);
        } catch (error) {
            // Leave the native link in place: it still works.
            console.warn('InfiniteScroll:', error);
        } finally {
            this.loading = false;
            this.element.classList.remove(this.loadingClass);
            this.element.removeAttribute('aria-busy');
        }
    }
}
