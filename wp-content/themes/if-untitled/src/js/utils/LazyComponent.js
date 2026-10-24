/**
 * Dynamically import a component only if `selector` matches something on the page.
 * Vite splits the import into its own chunk.
 *
 * @param {string} selector
 * @param {() => Promise<{default: new (options: object) => unknown}>} loader
 * @param {object} [options] passed to the component constructor
 */
export async function lazyComponent(selector, loader, options = {}) {
    if (!document.querySelector(selector)) return null;
    const { default: Component } = await loader();
    return new Component(options);
}
