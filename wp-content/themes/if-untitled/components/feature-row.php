<?php
/**
 * Feature row (Studio features, Advisory thematics): text in cols 1–2, image on the right at its natural ratio.
 * $args: ['title' => string, 'subtitle' => string, 'description' => string, 'image' => int, 'columns' => '4-5' | '3-5',
 * 'id' => string (anchor), 'project' => ?array (if_untitled_resolve_item(): title + metadata + excerpt pinned to the bottom)]
 *
 * @package if-untitled
 */

$title   = $args['title'] ?? '';
$range   = if_untitled_column_range($args['columns'] ?? '4-5') ?? [4, 5];
$image   = (int) ($args['image'] ?? 0);
$project = $args['project'] ?? null;
?>
<section class="feature-row grid"<?php echo !empty($args['id']) ? ' id="' . esc_attr($args['id']) . '"' : ''; ?>>
    <div class="feature-row__text grid__item" style="--col-start: 1; --col-span: 2">
        <div class="feature-row__body">
            <?php if ($title) : ?>
                <h2 class="feature-row__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if (!empty($args['subtitle'])) : ?>
                <p class="feature-row__subtitle"><?php echo esc_html($args['subtitle']); ?></p>
            <?php endif; ?>
            <?php if (!empty($args['description'])) : ?>
                <div class="feature-row__description"><?php echo wp_kses_post($args['description']); ?></div>
            <?php endif; ?>
        </div>
        <?php if ($project) : ?>
            <div class="feature-row__project">
                <h3 class="feature-row__project-title">
                    <a href="<?php echo esc_url($project['url']); ?>"><?php echo esc_html($project['title']); ?></a>
                </h3>
                <?php if (has_excerpt($project['id'])) : ?>
                    <p class="feature-row__excerpt"><?php echo esc_html(get_the_excerpt($project['id'])); ?></p>
                <?php endif; ?>
                <?php if_untitled_component('project-meta', ['post_id' => $project['id']]); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($image) : ?>
        <div class="feature-row__media grid__item" style="--col-start: <?php echo (int) $range[0]; ?>; --col-span: <?php echo (int) ($range[1] - $range[0] + 1); ?>">
            <?php if_untitled_component('image', ['id' => $image, 'context' => 'span-' . ($range[1] - $range[0] + 1)]); ?>
        </div>
    <?php endif; ?>
</section>
