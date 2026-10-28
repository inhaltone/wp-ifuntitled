<?php
/**
 * Fallback (search results and anything without a more specific template): page intro + cards.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package if-untitled
 */

get_header();
?>
<main id="index" class="index">
    <?php
    if_untitled_component('page-intro', [
        'title'   => is_search()
            /* translators: %s: search query */
            ? sprintf(__('Search: %s', 'if-untitled'), get_search_query())
            : wp_strip_all_tags(get_the_archive_title()),
        'columns' => '1-2',
    ]);
    ?>

    <?php if (have_posts()) : ?>
        <div class="index__cards grid grid--cards">
            <?php while (have_posts()) : the_post(); ?>
                <?php if_untitled_component('journal-card', ['priority' => $wp_query->current_post === 0 && !is_paged()]); ?>
            <?php endwhile; ?>
        </div>
        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p class="index__empty grid__text"><?php esc_html_e('Nothing found.', 'if-untitled'); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
