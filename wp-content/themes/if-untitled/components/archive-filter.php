<?php
/**
 * Archive filter (vertical): "All" link, a "Filter" label, then the taxonomy terms as term-archive links.
 * Below md (JS only) the label becomes a toggle (src/js/components/ArchiveFilter.js) and the terms are collapsed;
 * the toggle names the active term. Without JS the list is always shown.
 * $args: ['taxonomy' => string, 'all_url' => string, 'all_label' => string, 'label' => string (nav aria-label)]
 *
 * @package if-untitled
 */

$items   = if_untitled_archive_filter(
    $args['taxonomy'],
    $args['all_url'],
    $args['all_label'] ?? __('All', 'if-untitled')
);
$all     = array_shift($items);
$current = current(array_filter($items, fn(array $item): bool => $item['is_active']));
$list_id = wp_unique_id('archive-filter-');
?>
<nav class="archive-filter" aria-label="<?php echo esc_attr($args['label'] ?? __('Filter', 'if-untitled')); ?>" data-archive-filter>
    <a class="archive-filter__link archive-filter__link--all" href="<?php echo esc_url($all['url']); ?>"<?php echo $all['is_active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($all['label']); ?></a>
    <?php if ($items) : ?>
        <p class="archive-filter__label"><?php esc_html_e('Filter', 'if-untitled'); ?></p>
        <button class="archive-filter__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr($list_id); ?>">
            <?php esc_html_e('Filter', 'if-untitled'); ?><?php if ($current) : ?><span class="archive-filter__current">: <?php echo esc_html($current['label']); ?></span><?php endif; ?>
        </button>
        <ul class="archive-filter__list" id="<?php echo esc_attr($list_id); ?>">
            <?php foreach ($items as $item) : ?>
                <li class="archive-filter__item">
                    <a class="archive-filter__link" href="<?php echo esc_url($item['url']); ?>"<?php echo $item['is_active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</nav>
