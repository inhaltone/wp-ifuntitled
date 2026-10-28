/**
 * Keeps [data-sticky-center] blocks vertically centred in the viewport while their parent scrolls past: writes
 * --sticky-center-top = (viewport height − block height) / 2 for the CSS `position: sticky; top: …` rule, and
 * --sticky-center-end = height of a [data-sticky-center-end] block in the same parent, which the block must stop
 * above. CSS can't read element heights, hence this tiny observer.
 */
export default class StickyCenter {
    constructor({ selector = '[data-sticky-center]', endSelector = '[data-sticky-center-end]' } = {}) {
        this.elements = [...document.querySelectorAll(selector)];
        if (this.elements.length === 0) return;
        this.items = this.elements.map((element) => ({
            element,
            end: element.parentElement.querySelector(endSelector),
        }));
        this.main();
    }

    main() {
        const update = () => this.items.forEach((item) => this.#update(item));
        const observer = new ResizeObserver(update);
        this.items.forEach(({ element, end }) => {
            observer.observe(element);
            if (end) observer.observe(end);
        });
        window.addEventListener('resize', update, { passive: true });
    }

    #update({ element, end }) {
        const top = Math.max(0, (window.innerHeight - element.offsetHeight) / 2);
        element.style.setProperty('--sticky-center-top', `${top}px`);
        if (end) element.style.setProperty('--sticky-center-end', `${end.offsetHeight}px`);
    }
}
