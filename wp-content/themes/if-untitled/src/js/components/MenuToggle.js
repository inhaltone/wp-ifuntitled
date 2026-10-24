/**
 * Toggles the off-canvas navigation.
 * Adds `.visible` to the target and `.show` to the button.
 */
export default class MenuToggle {
    className = 'visible';

    constructor({ buttonSelector, targetSelector }) {
        this.buttonElement = document.querySelector(buttonSelector);
        this.targetElement = document.querySelector(targetSelector);
        if (this.buttonElement === null || this.targetElement === null) return;
        this.main();
    }

    main() {
        this.buttonElement.addEventListener('click', () => this.toggle());
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.isOpen) this.toggle();
        });
    }

    get isOpen() {
        return this.targetElement.classList.contains(this.className);
    }

    toggle() {
        const open = !this.isOpen;
        this.targetElement.classList.toggle(this.className, open);
        this.buttonElement.classList.toggle('show', open);
        this.buttonElement.setAttribute('aria-expanded', String(open));
    }
}
