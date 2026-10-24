import fs from 'node:fs';
import path from 'node:path';

let exitHandlersRegistered = false;

/**
 * Minimal WordPress integration for Vite.
 *
 * - Writes a `hot` file (containing the dev-server origin) while `vite` dev is
 *   running, so `inc/vite.php` can auto-detect development mode.
 * - Triggers a full page reload when a PHP file inside the Vite root (the theme) changes.
 *   Vite already watches its root (minus node_modules/.git), so no extra globs are needed.
 *
 * @param {{ hotFile?: string }} [options]
 * @returns {import('vite').Plugin}
 */
export default function wordpress(options = {}) {
    let hotFile;

    const removeHotFile = () => {
        if (hotFile && fs.existsSync(hotFile)) fs.rmSync(hotFile);
    };

    return {
        name: 'if-untitled:wordpress',
        apply: 'serve',

        configResolved(config) {
            hotFile = path.resolve(config.root, options.hotFile ?? 'hot');
        },

        configureServer(server) {
            server.httpServer?.once('listening', () => {
                const origin = server.config.server.origin ?? server.resolvedUrls?.local?.[0]?.replace(/\/$/, '');
                if (origin) fs.writeFileSync(hotFile, origin);
            });
            server.httpServer?.once('close', removeHotFile);

            // configureServer runs again on every config-triggered restart; register process hooks once.
            if (!exitHandlersRegistered) {
                exitHandlersRegistered = true;
                process.once('exit', removeHotFile);
                for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
                    process.once(signal, () => {
                        removeHotFile();
                        process.exit();
                    });
                }
            }

            const reload = (file) => {
                if (file.endsWith('.php')) server.ws.send({ type: 'full-reload', path: '*' });
            };
            server.watcher.on('change', reload);
            server.watcher.on('add', reload);
            server.watcher.on('unlink', reload);
        },
    };
}
