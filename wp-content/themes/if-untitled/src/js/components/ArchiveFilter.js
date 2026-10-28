import { mediaQueryUp } from '@/js/utils/MediaBreakpoints.js';

/**
 * Archive filter below md: the "Filter" toggle expands / collapses the term list (CSS hides it until .is-open).
 * From md the list is always shown and the toggle is hidden; crossing the breakpoint resets to collapsed.
 */
export default class ArchiveFilter {
    className = 'is-open';

    constructor({
        selector = '[data-archive-filter]',
        toggleSelector = '.archive-filter__toggle',
        breakpoint = 'md',
    } = {}) {
        this.elements = [...document.querySelectorAll(selector)].filter((element) =>
            element.querySelector(toggleSelector),
        );
        if (this.elements.length === 0) return;
        this.toggleSelector = toggleSelector;
        this.desktop = mediaQueryUp(breakpoint);
        this.main();
    }

    main() {
        this.elements.forEach((element) => {
            element.querySelector(this.toggleSelector).addEventListener('click', () => {
                this.#setOpen(element, !element.classList.contains(this.className));
            });
        });
        this.desktop.addEventListener('change', () =>
            this.elements.forEach((element) => this.#setOpen(element, false)),
        );
    }

    #setOpen(element, open) {
        element.classList.toggle(this.className, open);
        element.querySelector(this.toggleSelector).setAttribute('aria-expanded', String(open));
    }
}
