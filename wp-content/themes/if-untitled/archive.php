<?php
/**
 * Archive template.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="archive" class="archive">
    <div class="container">
        <header class="archive__header">
            <h1 class="archive__title"><?php the_archive_title(); ?></h1>
            <?php the_archive_description('<div class="archive__description">', '</div>'); ?>
        </header>

        <?php if (have_posts()) : ?>
            <ul class="post-list">
                <?php while (have_posts()) : the_post(); ?>
                    <li class="post-list__item">
                        <?php get_template_part('components/post-card'); ?>
                    </li>
                <?php endwhile; ?>
            </ul>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e('Nothing found.', 'if-untitled'); ?></p>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
