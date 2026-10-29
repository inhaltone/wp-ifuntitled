<?php
/**
 * Journal entry: hero (title, excerpt, entry type + date, tags in col 1 · lead image cols 2–5), the post text,
 * then the content grid (images).
 *
 * @package if-untitled
 */

get_header();
?>
<main id="journal-entry" class="journal-entry">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        $categories = get_the_category();
        $type       = $categories ? $categories[0] : null;
        ?>
        <section class="journal-entry__hero grid">
            <div class="journal-entry__summary grid__item" style="--col-start: 1; --col-span: 1">
                <h1 class="journal-entry__title"><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?>
                    <p class="journal-entry__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
                <p class="journal-entry__meta">
                    <?php if ($type) : ?>
                        <a class="journal-entry__type" href="<?php echo esc_url(get_category_link($type)); ?>"><?php echo esc_html($type->name); ?></a>
                    <?php endif; ?>
                    <time class="journal-entry__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('F Y')); ?></time>
                </p>
                <?php the_tags('<p class="journal-entry__tags">', ', ', '</p>'); ?>
            </div>
            <?php if (has_post_thumbnail()) : ?>
                <div class="journal-entry__cover grid__item" style="--col-start: 2; --col-span: 4">
                    <?php
                    if_untitled_component('image', [
                        'id'       => get_post_thumbnail_id(),
                        'context'  => 'span-4',
                        'priority' => true,
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (trim(get_the_content()) !== '') : ?>
            <div class="journal-entry__text-row grid">
                <div class="journal-entry__text grid__item" style="--col-start: 2; --col-span: 3">
                    <?php get_template_part('components/text-render'); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if_untitled_component('content-grid', ['rows' => if_untitled_content_grid()]); ?>
    <?php endwhile; ?>
</main>
<?php
get_footer();
