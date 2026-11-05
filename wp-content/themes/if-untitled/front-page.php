<?php
/**
 * Front page: full-bleed hero (page featured image or a looping MP4 with it as poster, fixed logo over it, short description = page content below it),
 * then the shared content grid whose blocks may link to any public content.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="front-page" class="front-page">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        if_untitled_component('hero', [
            'id'    => has_post_thumbnail() ? get_post_thumbnail_id() : 0,
            'title' => get_bloginfo('name'),
            'video' => if_untitled_hero_video(),
        ]);
        ?>

        <?php if (trim(get_the_content()) !== '') : ?>
            <div class="front-page__intro grid">
                <div class="front-page__description grid__item" style="--col-start: 5; --col-span: 1">
                    <?php the_content(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if_untitled_component('content-grid', ['rows' => if_untitled_content_grid(), 'hover_titles' => true]); ?>
    <?php endwhile; ?>
    <?php if_untitled_component('hover-title'); ?>
</main>
<?php
get_footer();
