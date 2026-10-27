<?php
/**
 * Journal (posts page `/journal/`) and, via category.php / tag.php, its filtered views: intro with the entry-type
 * filter, journal cards, pagination (/page/N/ links, enhanced by InfiniteScroll).
 *
 * @package if-untitled
 */

$links = if_untitled_paged_links();

get_header();
?>
<main id="journal" class="journal">
    <?php
    if_untitled_component('page-intro', [
        'title'   => get_the_title((int) get_option('page_for_posts')) ?: __('Journal', 'if-untitled'),
        'text'    => if_untitled_archive_intro('journal_intro'),
        'columns' => '4-5',
        'rule'    => true,
        'aside'   => is_tag() ? null : [
            'name' => 'archive-filter',
            'args' => [
                'taxonomy'  => 'category',
                'all_url'   => get_permalink((int) get_option('page_for_posts')) ?: home_url('/'),
                'all_label' => __('All Entries', 'if-untitled'),
                'label'     => __('Entry types', 'if-untitled'),
            ],
        ],
    ]);
    ?>

    <?php if (have_posts()) : ?>
        <div class="journal__cards grid grid--cards" data-infinite-scroll>
            <?php while (have_posts()) : the_post(); ?>
                <?php if_untitled_component('journal-card', ['priority' => $wp_query->current_post === 0 && !is_paged()]); ?>
            <?php endwhile; ?>
        </div>

        <?php if ($links['prev'] || $links['next']) : ?>
            <nav class="journal__pagination" aria-label="<?php esc_attr_e('Journal pages', 'if-untitled'); ?>" data-infinite-scroll-pagination>
                <?php if ($links['prev']) : ?>
                    <a class="journal__page-link" href="<?php echo esc_url($links['prev']); ?>" rel="prev"><?php esc_html_e('Newer entries', 'if-untitled'); ?></a>
                <?php endif; ?>
                <?php if ($links['next']) : ?>
                    <a class="journal__page-link journal__page-link--next" href="<?php echo esc_url($links['next']); ?>" rel="next" data-infinite-scroll-next><?php esc_html_e('Older entries', 'if-untitled'); ?></a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php else : ?>
        <p class="journal__empty grid__text"><?php esc_html_e('No entries yet.', 'if-untitled'); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
