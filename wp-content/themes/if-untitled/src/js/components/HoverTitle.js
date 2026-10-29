/**
 * Home: shows the title of the image under the pointer in the fixed [data-hover-title] element (bottom-right,
 * opposite the logo). Titles come from [data-hover-title-text] on each linked content grid image. Mouse / pen only —
 * touch has no hover.
 */
export default class HoverTitle {
    className = 'is-visible';

    constructor({ selector = '[data-hover-title]', itemSelector = '[data-hover-title-text]' } = {}) {
        this.element = document.querySelector(selector);
        this.items = document.querySelectorAll(itemSelector);
        if (!this.element || this.items.length === 0) return;
        this.main();
    }

    main() {
        this.items.forEach((item) => {
            item.addEventListener('pointerenter', (event) => {
                if (event.pointerType === 'touch') return;
                this.#show(item.dataset.hoverTitleText);
            });
            item.addEventListener('pointerleave', () => this.#hide());
        });
    }

    #show(title) {
        this.element.textContent = title;
        this.element.classList.add(this.className);
    }

    // The text stays while the title fades out.
    #hide() {
        this.element.classList.remove(this.className);
    }
}
