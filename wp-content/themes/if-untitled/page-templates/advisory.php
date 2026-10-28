<?php
/**
 * Template Name: Advisory
 * Template Post Type: page
 *
 * Advisory: intro (page title + content), service areas with their showcase project (anchored), clients.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="advisory" class="advisory">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        if_untitled_component('page-intro', [
            'title'   => get_the_title(),
            'text'    => apply_filters('the_content', get_the_content()),
            'columns' => '2-3',
        ]);
        ?>

        <?php foreach (if_untitled_advisory_features() as $feature) : ?>
            <?php
            if_untitled_component('feature-row', [
                'title'       => $feature['title'],
                'subtitle'    => $feature['subtitle'],
                'description' => $feature['description'],
                'id'          => $feature['anchor'],
                'project'     => $feature['project'],
                'image'       => $feature['project']['image'] ?? 0,
                'columns'     => $feature['columns'],
            ]);
            ?>
        <?php endforeach; ?>

        <?php if (if_untitled_clients()) : ?>
            <?php
            if_untitled_component('section-head', [
                'title'   => __('Clients', 'if-untitled'),
                'content' => ['name' => 'clients'],
            ]);
            ?>
        <?php endif; ?>
    <?php endwhile; ?>
</main>
<?php
get_footer();
