<?php
/**
 * Work archive card: featured image (2:3 portrait crop, like the Journal card), title and category. The metadata
 * (4 columns) sits in a black bar over the bottom of the image, shown on hover / focus and always on touch.
 * Runs inside The Loop.
 * $args: ['priority' => bool (first card only — LCP)]
 *
 * @package if-untitled
 */

$category = if_untitled_project_categories();
?>
<article class="project-card" data-infinite-scroll-item>
    <a class="project-card__link" href="<?php the_permalink(); ?>">
        <div class="project-card__media">
            <?php
            // The title in the same link names the target, so the image is decorative here.
            if_untitled_component('image', [
                'id'       => (int) get_post_thumbnail_id(),
                'context'  => 'card',
                'ratio'    => '2x3',
                'alt'      => '',
                'priority' => !empty($args['priority']),
            ]);
            ?>
            <div class="project-card__bar">
                <?php if_untitled_component('project-meta', ['post_id' => get_the_ID(), 'variant' => 'columns']); ?>
            </div>
        </div>
        <h2 class="project-card__title"><?php the_title(); ?></h2>
        <?php if ($category) : ?>
            <p class="project-card__category"><?php echo esc_html($category); ?></p>
        <?php endif; ?>
    </a>
</article>
