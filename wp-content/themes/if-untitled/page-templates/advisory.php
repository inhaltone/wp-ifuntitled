<?php
/**
 * Template Name: Advisory
 * Template Post Type: page
 *
 * Advisory (same structure as Studio, without team): hero (featured image), intro (page title + content), then the
 * sections of if_untitled_page_sections(): services (content grid), clients, featured Advisory projects (can be
 * hidden) — each anchored for the header submenu.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="advisory" class="advisory">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        if (has_post_thumbnail()) {
            if_untitled_component('hero', ['id' => get_post_thumbnail_id()]);
        }

        if_untitled_component('page-intro', [
            'title'   => get_the_title(),
            'text'    => apply_filters('the_content', get_the_content()),
            'columns' => '2-3',
            'strong'  => if_untitled_intro_strong_weight(),
        ]);

        foreach (if_untitled_page_sections(get_the_ID()) as $section) {
            if_untitled_component('section-head', $section);
        }
        ?>
    <?php endwhile; ?>
</main>
<?php
get_footer();
