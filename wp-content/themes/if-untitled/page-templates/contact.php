<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * Contact: full-viewport map with the page title, content (intro) and locations over col 5 (dark theme).
 *
 * @package if-untitled
 */

get_header();
?>
<main id="contact" class="contact">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        if_untitled_component('contact-map', [
            'title' => get_the_title(),
            'text'  => apply_filters('the_content', get_the_content()),
        ]);
        ?>
    <?php endwhile; ?>
</main>
<?php
get_footer();
