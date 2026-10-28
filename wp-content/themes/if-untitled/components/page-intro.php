<?php
/**
 * Page intro: display XL title + bold paragraph in a column range, filling the first viewport.
 * $args: ['title' => string, 'text' => string (HTML), 'columns' => '1-2' (start-end, 1–5),
 * 'aside' => ['name' => component, 'args' => array] (rendered in col 1, e.g. archive-filter)]
 *
 * @package if-untitled
 */

$title   = $args['title'] ?? '';
$text    = $args['text'] ?? '';
$range   = if_untitled_column_range($args['columns'] ?? '1-2') ?? [1, 2];
$aside   = $args['aside'] ?? null;
$classes = 'page-intro__body grid__item';
?>
<header class="page-intro grid">
    <?php if (!empty($aside['name'])) : ?>
        <div class="page-intro__aside grid__item" style="--col-start: 1; --col-span: 1">
            <?php if_untitled_component($aside['name'], $aside['args'] ?? []); ?>
        </div>
    <?php endif; ?>
    <div class="<?php echo esc_attr($classes); ?>" style="--col-start: <?php echo (int) $range[0]; ?>; --col-span: <?php echo (int) ($range[1] - $range[0] + 1); ?>">
        <?php if ($title) : ?>
            <h1 class="page-intro__title"><?php echo esc_html($title); ?></h1>
        <?php endif; ?>
        <?php if ($text) : ?>
            <div class="page-intro__text"><?php echo wp_kses_post($text); ?></div>
        <?php endif; ?>
    </div>
</header>
