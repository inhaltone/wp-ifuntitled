<?php
/**
 * Single post template.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="post" class="single">
    <?php while (have_posts()) : the_post(); ?>
        <div class="single-layout">
            <div class="single-layout__title">
                <h1><?php the_title(); ?></h1>
                <time class="single-layout__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                    <?php echo esc_html(get_the_date()); ?>
                </time>
            </div>
            <?php if (has_post_thumbnail()) : ?>
                <figure class="single-layout__hero">
                    <?php the_post_thumbnail('large'); ?>
                </figure>
            <?php endif; ?>
            <div class="single-layout__content">
                <?php get_template_part('components/text-render'); ?>
            </div>
        </div>
    <?php endwhile; ?>
</main>
<?php
get_footer();
