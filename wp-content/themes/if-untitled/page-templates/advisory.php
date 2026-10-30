<?php
/**
 * Template Name: Advisory
 * Template Post Type: page
 *
 * Advisory (same structure as Studio): intro (page title + content), service areas (anchored), team, clients,
 * the service areas' showcase projects side by side.
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

        $services = if_untitled_advisory_features();
        if ($services) {
            if_untitled_component('section-head', [
                'title'   => __('Services', 'if-untitled'),
                'content' => ['name' => 'services', 'args' => ['services' => $services]],
            ]);
        }

        $team = function_exists('get_field') ? (get_field('advisory_team') ?: []) : [];
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

        $featured = if_untitled_advisory_featured();
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
