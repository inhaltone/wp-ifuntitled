/**
 * Enum of route ids. Each PHP template renders <main id="<routeId>">.
 * Add a static entry here whenever you add a template with page-specific JS.
 */
export default class Router {
    #id;

    static BASE = new Router('app');
    static FRONT_PAGE = new Router('front-page');
    static PAGE = new Router('page');
    static POST = new Router('post');
    static ARCHIVE = new Router('archive');
    static NOT_FOUND = new Router('not-found');

    constructor(id) {
        this.#id = id;
    }

    get routeId() {
        return this.#id;
    }
}

/**
 * Adds `.router-active` to every link pointing to the current path.
 */
export function useRouterLink(className = 'router-active') {
    const currentPath = window.location.pathname;
    for (const link of document.querySelectorAll('a[href]')) {
        if (new URL(link.href, window.location.href).pathname === currentPath) {
            link.classList.add(className);
            link.setAttribute('aria-current', 'page');
        }
    }
}
