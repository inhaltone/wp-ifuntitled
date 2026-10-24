/**
 * Adds `.is-revealed` to elements when they enter the viewport.
 * Lazy-loaded from main.js via lazyComponent().
 */
export default class Reveal {
    className = 'is-revealed';

    constructor({ selector = '[data-reveal]', threshold = 0.15 } = {}) {
        this.elements = document.querySelectorAll(selector);
        if (this.elements.length === 0) return;
        this.observer = new IntersectionObserver((entries) => this.onIntersect(entries), { threshold });
        this.main();
    }

    main() {
        for (const element of this.elements) this.observer.observe(element);
    }

    onIntersect(entries) {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            entry.target.classList.add(this.className);
            this.observer.unobserve(entry.target);
        }
    }
}
