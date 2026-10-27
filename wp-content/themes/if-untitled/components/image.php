<?php
/**
 * Responsive image with an optional fixed ratio.
 * $args: ['id' => int, 'context' => string, 'ratio' => '' | '3x4' | '4x3' | '16x9', 'priority' => bool, 'alt' => string]
 *
 * @package if-untitled
 */

$id = (int) ($args['id'] ?? 0);
if (!$id) {
    return;
}
$ratio = $args['ratio'] ?? '';
?>
<figure class="image<?php echo $ratio ? ' image--ratio-' . esc_attr($ratio) : ''; ?>">
    <?php // Core markup from wp_get_attachment_image(), already escaped. ?>
    <?php echo if_untitled_image($id, $args['context'] ?? 'full', $args); ?>
</figure>
