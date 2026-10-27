<?php
/**
 * Image, self-hosted video or YouTube video in the space of an image (content_grid).
 * $args: ['block' => array (content_grid media sub-fields), 'context' => string (render-responsive-image),
 *         'title' => string (accessible name of a player video; default: post title),
 *         'link' => ['url' => string, 'hidden' => bool] (optional; wraps the image / video area, never a YouTube block;
 *                   hidden = duplicate of a visible link → out of the tab order and accessibility tree)]
 *
 * @package if-untitled
 */

$media   = if_untitled_media($args['block'] ?? []);
$context = $args['context'] ?? 'full';
$link    = $media['type'] === 'youtube' ? null : ($args['link'] ?? null);
$open    = $link ? sprintf(
    '<a class="media__link" href="%s"%s>',
    esc_url($link['url']),
    !empty($link['hidden']) ? ' tabindex="-1" aria-hidden="true"' : ''
) : '';
$close   = $link ? '</a>' : '';

if ($media['type'] === 'image') {
    echo $open; // Escaped above.
    if_untitled_component('image', ['id' => $media['image'], 'context' => $context]);
    echo $close;
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
        <?php echo $open; // Escaped above. ?>
        <?php echo $poster; // Core markup, already escaped. ?>
        <video class="media__video" muted loop playsinline preload="none" aria-hidden="true">
            <source src="<?php echo esc_url($media['src']); ?>" type="<?php echo esc_attr($media['mime']); ?>">
        </video>
        <?php echo $close; ?>
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
