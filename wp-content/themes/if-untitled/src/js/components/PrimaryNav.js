/**
 * Primary navigation submenus. CSS opens them on :hover / :focus-within; this adds aria-expanded and
 * tap-to-open on touch: the first tap on a parent opens its submenu, the second follows the link.
 */
export default class PrimaryNav {
    className = 'is-open';

    constructor({ selector = '.primary-nav', parentSelector = '.has-submenu' } = {}) {
        this.element = document.querySelector(selector);
        if (this.element === null) return;
        this.parents = [...this.element.querySelectorAll(parentSelector)];
        if (this.parents.length === 0) return;
        this.main();
    }

    main() {
        this.parents.forEach((parent) => {
            const link = parent.querySelector(':scope > a');
            link.setAttribute('aria-expanded', 'false');
            link.setAttribute('aria-haspopup', 'true');

            parent.addEventListener('mouseenter', () => this.#setExpanded(parent, true));
            parent.addEventListener('mouseleave', () => this.#setExpanded(parent, false));
            parent.addEventListener('focusin', () => this.#setExpanded(parent, true));
            parent.addEventListener('focusout', (event) => {
                if (!parent.contains(event.relatedTarget)) this.#setExpanded(parent, false);
            });
            link.addEventListener('pointerup', (event) => {
                this.lastPointer = event.pointerType;
            });
            link.addEventListener('click', (event) => {
                if (this.lastPointer !== 'touch' || this.#isOpen(parent)) return;
                event.preventDefault();
                this.closeAll();
                this.#setExpanded(parent, true);
            });
        });

        document.addEventListener('click', (event) => {
            if (!this.element.contains(event.target)) this.closeAll();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') this.closeAll();
        });
    }

    closeAll() {
        this.parents.forEach((parent) => this.#setExpanded(parent, false));
    }

    #isOpen(parent) {
        return parent.classList.contains(this.className);
    }

    #setExpanded(parent, open) {
        parent.classList.toggle(this.className, open);
        parent.querySelector(':scope > a').setAttribute('aria-expanded', String(open));
    }
}
