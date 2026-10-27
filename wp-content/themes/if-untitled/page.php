<?php
/**
 * Default page (legal, privacy): title in col 1, content in cols 2–4.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="page" class="page">
    <?php while (have_posts()) : the_post(); ?>
        <div class="page__layout grid">
            <h1 class="page__title grid__item" style="--col-start: 1; --col-span: 1"><?php the_title(); ?></h1>
            <div class="page__content grid__item" style="--col-start: 2; --col-span: 3">
                <?php get_template_part('components/text-render'); ?>
            </div>
        </div>
    <?php endwhile; ?>
</main>
<?php
get_footer();
