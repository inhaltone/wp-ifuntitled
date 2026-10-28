import { mediaQueryUp } from '@/js/utils/MediaBreakpoints.js';

/**
 * Primary navigation.
 * - Below md: the "Menu" toggle opens the menu as a full-screen panel (submenus listed, page scroll locked);
 *   Escape, a link click or crossing to md closes it.
 * - From md: CSS opens submenus on :hover / :focus-within; this adds aria-expanded and tap-to-open on touch
 *   (the first tap on a parent opens its submenu, the second follows the link).
 */
export default class PrimaryNav {
    className = 'is-open';
    lockClass = 'has-menu-open';

    constructor({
        selector = '.primary-nav',
        headerSelector = '.site-header',
        toggleSelector = '.site-header__toggle',
        parentSelector = '.has-submenu',
        breakpoint = 'md',
    } = {}) {
        this.element = document.querySelector(selector);
        if (this.element === null) return;
        this.header = this.element.closest(headerSelector);
        this.toggle = this.header?.querySelector(toggleSelector) ?? null;
        this.parents = [...this.element.querySelectorAll(parentSelector)];
        this.desktop = mediaQueryUp(breakpoint);
        this.main();
    }

    main() {
        this.#initPanel();
        this.parents.forEach((parent) => this.#initSubmenu(parent));

        document.addEventListener('click', (event) => {
            if (!this.element.contains(event.target)) this.closeAll();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            this.closeAll();
            if (this.#isPanelOpen()) {
                this.setPanel(false);
                this.toggle.focus();
            }
        });
        this.desktop.addEventListener('change', () => {
            this.setPanel(false);
            this.closeAll();
        });
        // Back / forward cache restores the page as it was left.
        window.addEventListener('pageshow', () => this.setPanel(false));
    }

    closeAll() {
        this.parents.forEach((parent) => this.#setExpanded(parent, false));
    }

    setPanel(open) {
        if (this.toggle === null) return;
        this.header.classList.toggle(this.className, open);
        document.documentElement.classList.toggle(this.lockClass, open);
        this.toggle.setAttribute('aria-expanded', String(open));
        this.toggle.textContent = open ? this.closeLabel : this.openLabel;
    }

    #initPanel() {
        if (this.toggle === null) return;
        this.openLabel = this.toggle.textContent;
        this.closeLabel = this.toggle.dataset.labelClose || this.openLabel;
        this.toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            this.setPanel(!this.#isPanelOpen());
        });
        this.element.addEventListener('click', (event) => {
            if (event.target.closest('a')) this.setPanel(false);
        });
    }

    #initSubmenu(parent) {
        const link = parent.querySelector(':scope > a');
        link.setAttribute('aria-haspopup', 'true');
        this.#setExpanded(parent, false);

        parent.addEventListener('mouseenter', () => this.#setExpanded(parent, true));
        parent.addEventListener('mouseleave', () => this.#setExpanded(parent, false));
        parent.addEventListener('focusin', () => this.#setExpanded(parent, true));
        parent.addEventListener('focusout', (event) => {
            if (!parent.contains(event.relatedTarget)) this.#setExpanded(parent, false);
        });
        link.addEventListener('pointerup', (event) => {
            this.lastPointer = event.pointerType;
        });
        link.addEventListener(
            'click',
            (event) => {
                // In the panel the sub-items are already listed: the parent link just navigates.
                if (!this.desktop.matches || this.lastPointer !== 'touch' || this.#isOpen(parent)) return;
                event.preventDefault();
                event.stopImmediatePropagation();
                this.closeAll();
                this.#setExpanded(parent, true);
            },
            { capture: true },
        );
    }

    #isPanelOpen() {
        return this.header?.classList.contains(this.className) ?? false;
    }

    #isOpen(parent) {
        return parent.classList.contains(this.className);
    }

    #setExpanded(parent, open) {
        parent.classList.toggle(this.className, open);
        // Below md the submenus are always visible in the panel.
        const expanded = open || !this.desktop.matches;
        parent.querySelector(':scope > a').setAttribute('aria-expanded', String(expanded));
    }
}
