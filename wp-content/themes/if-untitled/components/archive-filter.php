<?php
/**
 * Archive filter (vertical): "All" link, a "Filter" label, then the taxonomy terms as term-archive links.
 * $args: ['taxonomy' => string, 'all_url' => string, 'all_label' => string, 'label' => string (nav aria-label)]
 *
 * @package if-untitled
 */

$items = if_untitled_archive_filter(
    $args['taxonomy'],
    $args['all_url'],
    $args['all_label'] ?? __('All', 'if-untitled')
);
$all   = array_shift($items);
?>
<nav class="archive-filter" aria-label="<?php echo esc_attr($args['label'] ?? __('Filter', 'if-untitled')); ?>">
    <a class="archive-filter__link archive-filter__link--all" href="<?php echo esc_url($all['url']); ?>"<?php echo $all['is_active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($all['label']); ?></a>
    <?php if ($items) : ?>
        <p class="archive-filter__label"><?php esc_html_e('Filter', 'if-untitled'); ?></p>
        <ul class="archive-filter__list">
            <?php foreach ($items as $item) : ?>
                <li class="archive-filter__item">
                    <a class="archive-filter__link" href="<?php echo esc_url($item['url']); ?>"<?php echo $item['is_active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</nav>
