<?php
/**
 * Template Name: Studio
 * Template Post Type: page
 *
 * Studio: intro (page title + content), feature rows, services (anchored), team, clients.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="studio" class="studio">
    <?php while (have_posts()) : the_post(); ?>
        <?php
        if_untitled_component('page-intro', [
            'title'   => get_the_title(),
            'text'    => apply_filters('the_content', get_the_content()),
            'columns' => '1-2',
        ]);
        ?>

        <?php foreach (if_untitled_studio_features() as $i => $feature) : ?>
            <?php if_untitled_component('feature-row', $feature + ['columns' => $i % 2 ? '3-5' : '4-5']); ?>
        <?php endforeach; ?>

        <?php $services = if_untitled_studio_services(); ?>
        <?php if ($services) : ?>
            <?php
            if_untitled_component('section-head', [
                'title'   => __('Services', 'if-untitled'),
                'content' => ['name' => 'services', 'args' => ['services' => $services]],
            ]);
            ?>
        <?php endif; ?>

        <?php $team = function_exists('get_field') ? (get_field('studio_team') ?: []) : []; ?>
        <?php if ($team) : ?>
            <?php
            if_untitled_component('section-head', [
                'title'   => __('Team', 'if-untitled'),
                'content' => ['name' => 'team', 'args' => ['ids' => $team]],
            ]);
            ?>
        <?php endif; ?>

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
