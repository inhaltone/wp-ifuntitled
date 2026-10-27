<?php
/**
 * Project metadata (Location · Client · Year · Status).
 * $args: ['post_id' => int|null, 'variant' => 'list' (label | value rows, empty rows dropped — project hero) | 'columns'
 * (4 cells, label above value, empty cells kept — Work card, Advisory)]
 *
 * @package if-untitled
 */

$variant = ($args['variant'] ?? 'list') === 'columns' ? 'columns' : 'list';
$meta    = if_untitled_project_metadata($args['post_id'] ?? null);
if ($variant === 'list') {
    $meta = array_filter($meta, fn(string $value): bool => $value !== '');
}
if (!array_filter($meta)) {
    return;
}
$labels = if_untitled_project_metadata_labels();
?>
<dl class="project-meta project-meta--<?php echo esc_attr($variant); ?>">
    <?php foreach ($meta as $key => $value) : ?>
        <div class="project-meta__item">
            <dt class="project-meta__label"><?php echo esc_html($labels[$key]); ?></dt>
            <dd class="project-meta__value"><?php echo esc_html($value); ?></dd>
        </div>
    <?php endforeach; ?>
</dl>
