/**
 * YouTube in the space of an image (content_grid media = youtube), click to play. Markup: components/media.php.
 * Poster + play link (opens YouTube without JS). The click swaps in a plain youtube-nocookie iframe that starts with
 * sound — created synchronously inside the click so the browser allows it (iOS may still ask for a second tap).
 * Nothing is requested from YouTube before the click.
 */
export default class YoutubeVideo {
    host = 'https://www.youtube-nocookie.com';
    activeClass = 'is-active';

    constructor({ selector = '[data-media-youtube]' } = {}) {
        this.elements = document.querySelectorAll(selector);
        if (this.elements.length === 0) return;
        this.main();
    }

    main() {
        for (const element of this.elements) {
            const play = element.querySelector('[data-media-play]');
            const mount = element.querySelector('[data-media-mount]');
            if (!element.dataset.youtubeId || !play || !mount) continue;
            play.addEventListener(
                'click',
                (event) => {
                    event.preventDefault();
                    this.embed(element, mount);
                },
                { once: true },
            );
        }
    }

    embed(element, mount) {
        const { youtubeId: id, youtubeTitle: title = document.title } = element.dataset;
        const params = new URLSearchParams({ autoplay: '1', playsinline: '1', rel: '0' });
        const iframe = document.createElement('iframe');
        iframe.src = `${this.host}/embed/${encodeURIComponent(id)}?${params}`;
        iframe.title = title;
        iframe.allow = 'autoplay; encrypted-media; fullscreen; picture-in-picture';
        iframe.allowFullscreen = true;
        mount.replaceWith(iframe);
        element.classList.add(this.activeClass);
        iframe.focus(); // the play link is hidden — keep keyboard focus on the player
    }
}
