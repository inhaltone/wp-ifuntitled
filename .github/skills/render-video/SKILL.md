---
name: render-video
description: Render video in the if-untitled theme as a variant of an image in content_grid — self-hosted MP4 loops (autoplay, muted, loop, no controls) and click-to-play YouTube, with poster, lazy start, pause toggle and reduced-motion support. Use when asked to add, autoplay, loop or style videos or YouTube embeds.
---

# Render video (image variant in `content_grid`)

Theme root: `wp-content/themes/if-untitled/`. **Video exists only in `content_grid`** (Home, projects, journal
entries): wherever a block shows an image it may show a video instead. Cards, heroes and journal leads stay images.
Images follow `render-responsive-image` — this skill builds on it (`if_untitled_image()`, `image` component).

| Variant   | Source                   | Behaviour                                                             |
| --------- | ------------------------ | --------------------------------------------------------------------- |
| `image`   | media library            | `image` component (default)                                           |
| `video`   | MP4 in the media library | autoplay, muted, loop, no controls — plays only in view, pause button |
| `youtube` | YouTube URL              | poster + play button → click → YouTube's player with sound + controls |

**Silent loops = `video`** (self-hosted): no third party, no branding, no consent. **Films with sound = `youtube`**
(click to play). There is deliberately **no autoplaying YouTube**: it can't hide YouTube's branding, costs ~1 MB of
YouTube JS per video and contacts YouTube without consent (plan Q13).

## 1. Fields — image block of `content_grid` (`image` and `image_text` layouts)

| Sub-field       | Type                                        | Notes                                                                                                                |
| --------------- | ------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `media_type`    | button group: `image` · `video` · `youtube` | default `image`                                                                                                      |
| `image`         | image, returns ID, **required**             | the image — or the video's **poster** (a frame of the video, same ratio): sizes the box, no-JS / reduced-motion view |
| `video`         | file (`mp4`, `webm`), returns ID            | shown + required if `video`                                                                                          |
| `youtube_url`   | url                                         | shown + required if `youtube`; validated with `if_untitled_youtube_id()` (`acf/validate_value`); poster 16:9         |
| `image_columns` | column range                                | unchanged                                                                                                            |

**Block `link`:** a `youtube` block is never wrapped in the layout's `link` (no `<a>` around its play button); only
the heading links. Image and `video` blocks may be wrapped — `content-grid.php` places the link on the poster area and
the pause toggle outside it.

Same sub-fields in both layouts (define once, ACF clone into both). Follow `create-acf-field-group`.

## 2. Helpers — `inc/template-tags.php`

```php
/**
 * YouTube video ID from any common URL (watch, youtu.be, embed, shorts, live, nocookie); '' if none.
 */
function if_untitled_youtube_id(string $url): string
{
    $pattern = '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~';

    return preg_match($pattern, $url, $match) ? $match[1] : '';
}

/**
 * Normalises a content_grid media block (image | video | youtube) for components/media.php.
 * Falls back to a plain image when the video / URL is missing or invalid.
 */
function if_untitled_media(array $block): array
{
    $type  = $block['media_type'] ?? 'image';
    $media = ['type' => 'image', 'image' => (int) ($block['image'] ?? 0)];

    if ($type === 'video') {
        $video_id = (int) ($block['video'] ?? 0);
        $src      = $video_id ? wp_get_attachment_url($video_id) : '';
        if ($src) {
            $media += ['src' => $src, 'mime' => (string) get_post_mime_type($video_id)];
            $media['type'] = 'video';
        }
    } elseif ($type === 'youtube') {
        $youtube_id = if_untitled_youtube_id((string) ($block['youtube_url'] ?? ''));
        if ($youtube_id) {
            $media['youtube_id'] = $youtube_id;
            $media['type']       = 'youtube';
        }
    }

    return $media;
}
```

## 3. Component — `components/media.php`

`content-grid.php` renders every image block with
`if_untitled_component('media', ['block' => $row, 'context' => 'span-3'])` (context from the column range, as for
images). An `image` block falls through to the `image` component, so templates never branch on the type.

```php
<?php
/**
 * Image, self-hosted video or YouTube video in the space of an image (content_grid).
 * $args: ['block' => array (content_grid media sub-fields), 'context' => string (render-responsive-image),
 *         'title' => string (accessible name of a player video; default: post title)]
 *
 * @package if-untitled
 */

$media   = if_untitled_media($args['block'] ?? []);
$context = $args['context'] ?? 'full';

if ($media['type'] === 'image') {
    if_untitled_component('image', ['id' => $media['image'], 'context' => $context]);
    return;
}

$title   = $args['title'] ?? get_the_title();
$classes = ['media', 'media--' . $media['type']];
if (!$media['image']) {
    $classes[] = 'media--no-poster';
}
// The poster is the visual stand-in for the video → decorative alt.
$poster = if_untitled_image($media['image'], $context, ['alt' => '', 'class' => 'media__poster']);
?>
<?php if ($media['type'] === 'video') : ?>
    <figure class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-media-video>
        <?php echo $poster; // Core markup, already escaped. ?>
        <video class="media__video" muted loop playsinline preload="none" aria-hidden="true">
            <source src="<?php echo esc_url($media['src']); ?>" type="<?php echo esc_attr($media['mime']); ?>">
        </video>
        <button type="button" class="media__toggle" data-media-toggle
                data-label-play="<?php esc_attr_e('Play video', 'if-untitled'); ?>"
                data-label-pause="<?php esc_attr_e('Pause video', 'if-untitled'); ?>"
                aria-label="<?php esc_attr_e('Pause video', 'if-untitled'); ?>" hidden></button>
    </figure>
<?php else : ?>
    <figure class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-media-youtube
            data-youtube-id="<?php echo esc_attr($media['youtube_id']); ?>"
            data-youtube-title="<?php echo esc_attr($title); ?>">
        <?php echo $poster; // Core markup, already escaped. ?>
        <div class="media__frame"><div data-media-mount></div></div>
        <a class="media__play" data-media-play href="<?php echo esc_url('https://www.youtube.com/watch?v=' . $media['youtube_id']); ?>">
            <span class="screen-reader-text">
                <?php
                /* translators: %s: video title */
                printf(esc_html__('Play video: %s', 'if-untitled'), esc_html($title));
                ?>
            </span>
        </a>
    </figure>
<?php endif; ?>
```

## 4. SCSS — `src/styles/components/_media.scss`

Register with `@use 'media';` in `components/_index.scss`.

```scss
@use 'abstracts' as *;

// Image, self-hosted video or YouTube in the space of an image. The poster <img> sets the box size;
// the video / player lies on top and fades in once it plays.
.media {
    position: relative;
    margin: 0;
    overflow: hidden;

    &__video,
    &__frame {
        position: absolute;
        inset: 0;
        opacity: 0;
        transition: opacity 0.6s ease;
    }

    &__video {
        inline-size: 100%;
        block-size: 100%;
        object-fit: cover;
    }

    &.is-playing &__video,
    &.is-active &__frame {
        opacity: 1;
    }

    &--no-poster &__video {
        position: static;
        opacity: 1;
    }

    // YouTube player (after the click): fills the box, YouTube's own controls.
    &__frame iframe {
        inline-size: 100%;
        block-size: 100%;
        border: 0;
    }

    &__play {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        color: #fff;

        &::before {
            content: '';
            inline-size: 4rem;
            aspect-ratio: 1;
            background: currentcolor;
            clip-path: polygon(25% 15%, 85% 50%, 25% 85%);
        }
    }

    &.is-active &__play {
        display: none;
    }

    // Pause / play for looping video (WCAG 2.2.2). Shown on hover / focus, always on touch screens.
    &__toggle {
        position: absolute;
        inset-block-end: 1rem;
        inset-inline-end: 1rem;
        display: grid;
        place-items: center;
        inline-size: 2.5rem;
        aspect-ratio: 1;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: rgb(0 0 0 / 40%);
        color: #fff;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s ease;

        &::before {
            content: '';
            inline-size: 0.75rem;
            aspect-ratio: 1;
            background: linear-gradient(90deg, currentcolor 0 35%, transparent 35% 65%, currentcolor 65%);
        }

        &[hidden] {
            display: none;
        }
    }

    &.is-paused &__toggle::before {
        background: currentcolor;
        clip-path: polygon(15% 0, 100% 50%, 15% 100%);
    }

    &:hover &__toggle,
    &__toggle:focus-visible {
        opacity: 1;
    }

    @media (hover: none) {
        &__toggle {
            opacity: 1;
        }
    }
}
```

## 5. JS

`src/js/components/AmbientVideo.js`:

```js
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
```

`src/js/components/YoutubeVideo.js`:

```js
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
```

Wire in `src/main.js` inside `onMounted()` — code-split, downloaded only on pages with video:

```js
lazyComponent('[data-media-video]', () => import('@/js/components/AmbientVideo.js'));
lazyComponent('[data-media-youtube]', () => import('@/js/components/YoutubeVideo.js'));
```

## 6. Rules

- **Poster always** (`image` required): it reserves the space (no CLS) and is what no-JS, reduced-motion and slow
  connections see. For `youtube` use a 16:9 frame of the film (the player is 16:9).
- **`video`:** autoplay only muted (browsers block anything else); plays only in view (`IntersectionObserver`), pauses
  off-screen; `prefers-reduced-motion` → stays on the poster until the visitor presses play.
- **Pause toggle** on every looping video (WCAG 2.2.2) — our own button, shown on hover / focus, always on touch.
- **Video file:** MP4 (H.264), no audio track, ≤ 1920 px wide, 10–30 s, aim < 10 MB. `preload="none"` — nothing
  loads before it's in view.
- **`youtube` is always click to play** — never autoplay YouTube. Nothing is requested from YouTube before the click
  (no API, no iframe, poster is ours); the click swaps in a plain `youtube-nocookie` iframe (no API key) and moves focus
  to it. Desktop / Android play with sound on the first click; iPhone may need a second tap on YouTube's button.
- **Consent (plan Q13, TODO):** the click is the visitor's action; if the consent solution requires it, `YoutubeVideo`
  asks before embedding — the single place to change.
- **Uploads:** the server's `upload_max_filesize` / `post_max_size` must exceed the largest MP4 (plan Q24).
- Never `priority` on a video poster (the LCP is the hero / first image); never the YouTube thumbnail from `i.ytimg.com`.

## 7. Verify

```bash
cd wp-content/themes/if-untitled
npm run check
```

In the browser: `video` starts only when scrolled into view, pauses when scrolled away and loops without a visible
jump; the toggle pauses / resumes and is reachable with Tab. With DevTools → Rendering → `prefers-reduced-motion:
reduce` nothing autoplays. `youtube`: Network shows **no** YouTube request until the play button is clicked (mouse and
Enter), then the film plays with sound and focus is on the player. With JS disabled the poster shows and the play
button opens YouTube.
