<?php
/**
 * Default page template.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="page" class="page">
    <?php while (have_posts()) : the_post(); ?>
        <div class="single-layout">
            <div class="single-layout__title">
                <h1><?php the_title(); ?></h1>
            </div>
            <div class="single-layout__content">
                <?php get_template_part('components/text-render'); ?>
            </div>
        </div>
    <?php endwhile; ?>
</main>
<?php
get_footer();
