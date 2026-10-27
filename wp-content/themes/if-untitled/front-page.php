<?php
/**
 * Front page: full-bleed hero (page featured image, fixed logo over it, short description = page content below it),
 * then the shared content grid whose blocks may link to any public content.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="front-page" class="front-page">
    <?php while (have_posts()) : the_post(); ?>
        <section class="front-page__hero">
            <h1 class="screen-reader-text"><?php bloginfo('name'); ?></h1>
            <?php if (has_post_thumbnail()) : ?>
                <?php
                if_untitled_component('image', [
                    'id'       => get_post_thumbnail_id(),
                    'context'  => 'full',
                    'priority' => true,
                ]);
                ?>
            <?php endif; ?>
        </section>

        <?php if (trim(get_the_content()) !== '') : ?>
            <div class="front-page__intro grid">
                <div class="front-page__description grid__item" style="--col-start: 5; --col-span: 1">
                    <?php the_content(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if_untitled_component('content-grid', ['rows' => if_untitled_content_grid()]); ?>
    <?php endwhile; ?>
</main>
<?php
get_footer();
