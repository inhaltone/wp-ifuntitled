import { prefersReducedMotion } from '@/js/utils/MediaBreakpoints.js';

/**
 * Self-hosted silent loop (content_grid media = video): plays while in view, pauses off-screen,
 * never autoplays with reduced motion. Pause / play toggle for WCAG 2.2.2. Markup: components/media.php.
 */
export default class AmbientVideo {
    playingClass = 'is-playing';
    pausedClass = 'is-paused';
    #states = new WeakMap();

    constructor({ selector = '[data-media-video]', threshold = 0.25 } = {}) {
        this.elements = document.querySelectorAll(selector);
        if (this.elements.length === 0) return;
        this.autoplay = !prefersReducedMotion();
        this.observer = new IntersectionObserver((entries) => this.onIntersect(entries), { threshold });
        this.main();
    }

    main() {
        for (const element of this.elements) {
            const video = element.querySelector('video');
            if (!video) continue;
            const state = {
                element,
                video,
                toggle: element.querySelector('[data-media-toggle]'),
                paused: !this.autoplay,
                visible: false,
            };
            this.#states.set(element, state);
            video.addEventListener('playing', () => element.classList.add(this.playingClass));
            if (state.toggle) {
                state.toggle.hidden = false;
                state.toggle.addEventListener('click', () => {
                    state.paused = !state.paused;
                    this.#update(state);
                });
            }
            this.#sync(state);
            this.observer.observe(element);
        }
    }

    onIntersect(entries) {
        for (const entry of entries) {
            const state = this.#states.get(entry.target);
            state.visible = entry.isIntersecting;
            this.#update(state);
        }
    }

    #update(state) {
        if (state.visible && !state.paused) {
            // Blocked autoplay (e.g. data saver) → stay on the poster, show "Play".
            state.video.play().catch(() => {
                state.paused = true;
                this.#sync(state);
            });
        } else {
            state.video.pause();
        }
        this.#sync(state);
    }

    #sync({ element, toggle, paused }) {
        element.classList.toggle(this.pausedClass, paused);
        if (toggle) toggle.setAttribute('aria-label', paused ? toggle.dataset.labelPlay : toggle.dataset.labelPause);
    }
}
