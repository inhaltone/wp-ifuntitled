import Router from '@/js/utils/Router.js';

/**
 * Payload passed to page hooks.
 */
export class HookResponse {
    constructor({ element, status }) {
        this.element = element;
        this.status = status;
    }
}

const whenDomReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
        callback();
    }
};

/**
 * Runs once the DOM is ready. Receives the #app root element.
 * @param {(root: HTMLElement|null) => void} callback
 */
export function onMounted(callback) {
    if (typeof window === 'undefined') return;
    whenDomReady(() => callback(document.getElementById(Router.BASE.routeId)));
}

/**
 * Runs once the DOM is ready, only if an element with the given id exists
 * (each template renders <main id="...">).
 * @param {string} id
 * @param {(response: HookResponse) => void} callback
 */
export function onPageMounted(id, callback) {
    if (typeof window === 'undefined') return;
    whenDomReady(() => {
        const element = document.getElementById(id);
        if (element === null) return;
        callback(new HookResponse({ element, status: 'mounted' }));
    });
}

/**
 * Runs after all resources (images, fonts) are loaded.
 * @param {(root: HTMLElement|null) => void} callback
 */
export function onLoaded(callback) {
    if (typeof window === 'undefined') return;
    const run = () => callback(document.getElementById(Router.BASE.routeId));
    if (document.readyState === 'complete') run();
    else window.addEventListener('load', run, { once: true });
}
