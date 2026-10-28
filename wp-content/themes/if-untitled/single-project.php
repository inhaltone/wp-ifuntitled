<?php
/**
 * Single project: sticky title, category and metadata (col 1, blend difference, over the whole project), hero
 * (featured image cols 2–5), content grid, project text, previous / next project (menu order, looping).
 *
 * @package if-untitled
 */

get_header();
?>
<main id="project" class="project">
    <?php while (have_posts()) : the_post(); ?>
        <div class="project__layout">
            <header class="project__head grid">
                <div class="project__heading grid__item" style="--col-start: 1; --col-span: 1">
                    <h1 class="project__title"><?php the_title(); ?></h1>
                    <?php the_terms(get_the_ID(), 'project_category', '<p class="project__categories">', ', ', '</p>'); ?>
                    <?php if_untitled_component('project-meta', ['variant' => 'list']); ?>
                </div>
            </header>

            <div class="project__body">
                <section class="project__hero grid">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="project__cover grid__item" style="--col-start: 2; --col-span: 4">
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

                <?php if_untitled_component('content-grid', ['rows' => if_untitled_content_grid()]); ?>

                <?php $text = function_exists('get_field') ? (string) get_field('project_text') : ''; ?>
                <?php if (trim($text) !== '') : ?>
                    <div class="project__text-row grid">
                        <div class="project__text grid__item" style="--col-start: 2; --col-span: 3">
                            <?php echo wp_kses_post($text); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $adjacent = array_filter([
            'prev' => if_untitled_adjacent_project('prev'),
            'next' => if_untitled_adjacent_project('next'),
        ]);
        ?>
        <?php if ($adjacent) : ?>
            <nav class="project__pager grid" aria-label="<?php esc_attr_e('Projects', 'if-untitled'); ?>">
                <?php foreach ($adjacent as $direction => $item) : ?>
                    <a class="project__pager-link project__pager-link--<?php echo esc_attr($direction); ?> grid__item" href="<?php echo esc_url($item['url']); ?>" rel="<?php echo esc_attr($direction); ?>"
                       style="--col-start: <?php echo $direction === 'prev' ? 1 : 5; ?>; --col-span: 1">
                        <span class="project__pager-label">
                            <?php echo $direction === 'prev' ? esc_html__('Previous project', 'if-untitled') : esc_html__('Next project', 'if-untitled'); ?>
                        </span>
                        <span class="project__pager-title"><?php echo esc_html($item['title']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    <?php endwhile; ?>
</main>
<?php
get_footer();
