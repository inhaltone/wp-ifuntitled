<?php
/**
 * Post card used in loops. Expects to run inside The Loop.
 * Optional $args: ['show_excerpt' => bool]
 *
 * @package if-untitled
 */

$show_excerpt = $args['show_excerpt'] ?? true;
?>
<article class="post-card">
    <a class="post-card__link" href="<?php the_permalink(); ?>">
        <h2 class="post-card__title"><?php the_title(); ?></h2>
        <time class="post-card__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
    </a>
    <?php if ($show_excerpt && has_excerpt()) : ?>
        <p class="post-card__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
    <?php endif; ?>
</article>
