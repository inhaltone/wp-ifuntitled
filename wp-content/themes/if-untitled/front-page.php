<?php
/**
 * Front page.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="front-page" class="front-page">
    <section class="hero">
        <div class="container">
            <h1 class="hero__title"><?php bloginfo('name'); ?></h1>
            <?php if (get_bloginfo('description')) : ?>
                <p class="hero__lead"><?php bloginfo('description'); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <?php while (have_posts()) : the_post(); ?>
        <div class="container">
            <?php get_template_part('components/text-render'); ?>
        </div>
    <?php endwhile; ?>
</main>
<?php
get_footer();
