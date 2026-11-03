<?php
/**
 * Hero: full-bleed first viewport with one image (LCP, loaded with priority) — Home, Studio, Advisory featured image.
 * $args: ['id' => int (attachment ID), 'title' => string (optional, visually hidden h1)]
 *
 * @package if-untitled
 */

$id    = (int) ($args['id'] ?? 0);
$title = $args['title'] ?? '';
if (!$id && $title === '') {
    return;
}
?>
<section class="hero">
    <?php if ($title !== '') : ?>
        <h1 class="screen-reader-text"><?php echo esc_html($title); ?></h1>
    <?php endif; ?>
    <?php if ($id) : ?>
        <?php
        if_untitled_component('image', [
            'id'       => $id,
            'context'  => 'full',
            'priority' => true,
        ]);
        ?>
    <?php endif; ?>
</section>
