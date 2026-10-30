<?php
/**
 * Template Name: Studio
 * Template Post Type: page
 *
 * Studio: intro (page title + content), services (anchored), team, clients, featured items side by side.
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

        $services = if_untitled_studio_services();
        if ($services) {
            if_untitled_component('section-head', [
                'title'   => __('Services', 'if-untitled'),
                'content' => ['name' => 'services', 'args' => ['services' => $services]],
            ]);
        }

        $team = function_exists('get_field') ? (get_field('studio_team') ?: []) : [];
        if ($team) {
            if_untitled_component('section-head', [
                'title'   => __('Team', 'if-untitled'),
                'content' => ['name' => 'team', 'args' => ['ids' => $team]],
            ]);
        }

        if (if_untitled_clients()) {
            if_untitled_component('section-head', [
                'title'   => __('Clients', 'if-untitled'),
                'content' => ['name' => 'clients'],
            ]);
        }

        $featured = if_untitled_studio_features();
        if ($featured) {
            if_untitled_component('section-head', [
                'title'   => __('Featured', 'if-untitled'),
                'content' => ['name' => 'featured', 'args' => ['items' => $featured]],
            ]);
        }
        ?>
    <?php endwhile; ?>
</main>
<?php
get_footer();
