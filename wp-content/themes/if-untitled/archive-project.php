<?php
/**
 * Work archive (`/work/`) and, via taxonomy-project_category.php, its category views: filter + intro, then all
 * projects as cards.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="archive-project" class="archive-project">
    <?php
    if_untitled_component('page-intro', [
        'title'   => __('Work', 'if-untitled'),
        'text'    => if_untitled_archive_intro('work_intro'),
        'columns' => '3-4',
        'aside'   => [
            'name' => 'archive-filter',
            'args' => [
                'taxonomy'  => 'project_category',
                'all_url'   => get_post_type_archive_link('project'),
                'all_label' => __('All Projects', 'if-untitled'),
                'label'     => __('Project categories', 'if-untitled'),
            ],
        ],
    ]);
    ?>

    <?php if (have_posts()) : ?>
        <ul class="archive-project__cards grid grid--cards">
            <?php while (have_posts()) : the_post(); ?>
                <li class="archive-project__card">
                    <?php if_untitled_component('project-card', ['priority' => $wp_query->current_post === 0]); ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else : ?>
        <p class="archive-project__empty grid__text"><?php esc_html_e('No projects yet.', 'if-untitled'); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
