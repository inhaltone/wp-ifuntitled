<?php
/**
 * Hero: full-bleed first viewport with one image (LCP, loaded with priority) — Home, Studio, Advisory featured image.
 * Optionally a silent MP4 loop over it (Home): autoplay, muted, no controls; the image is its poster.
 * $args: ['id' => int (attachment ID), 'title' => string (optional, visually hidden h1),
 *         'video' => ['src' => string, 'mime' => string]|null (optional, if_untitled_hero_video())]
 *
 * @package if-untitled
 */

$id    = (int) ($args['id'] ?? 0);
$title = $args['title'] ?? '';
$video = $args['video'] ?? null;
if (!$id && !$video && $title === '') {
    return;
}
?>
<section class="hero">
    <?php if ($title !== '') : ?>
        <h1 class="screen-reader-text"><?php echo esc_html($title); ?></h1>
    <?php endif; ?>
    <?php if ($video) : ?>
        <?php // Played by AmbientVideo (in view only, never with reduced motion); no pause toggle by design. ?>
        <figure class="media media--video<?php echo $id ? '' : ' media--no-poster'; ?>" data-media-video>
            <?php
            // The poster is the visual stand-in for the video → decorative alt. Core markup, already escaped.
            echo if_untitled_image($id, 'full', ['alt' => '', 'class' => 'media__poster', 'priority' => true]);
            ?>
            <video class="media__video" muted loop playsinline preload="none" aria-hidden="true">
                <source src="<?php echo esc_url($video['src']); ?>" type="<?php echo esc_attr($video['mime']); ?>">
            </video>
        </figure>
    <?php elseif ($id) : ?>
        <?php
        if_untitled_component('image', [
            'id'       => $id,
            'context'  => 'full',
            'priority' => true,
        ]);
        ?>
    <?php endif; ?>
</section>
