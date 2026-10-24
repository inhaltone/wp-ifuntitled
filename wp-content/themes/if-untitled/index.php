<?php
/**
 * Fallback template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package if-untitled
 */

get_header();
?>
<main id="index" class="page">
    <div class="container">
        <?php if (have_posts()) : ?>
            <?php if (is_home() && !is_front_page()) : ?>
                <header class="page__header">
                    <h1 class="page__title"><?php single_post_title(); ?></h1>
                </header>
            <?php endif; ?>

            <ul class="post-list">
                <?php while (have_posts()) : the_post(); ?>
                    <li class="post-list__item">
                        <?php get_template_part('components/post-card'); ?>
                    </li>
                <?php endwhile; ?>
            </ul>

            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <h1 class="page__title"><?php esc_html_e('No content', 'if-untitled'); ?></h1>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
