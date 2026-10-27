<?php
/**
 * Not found: page intro with a short message and a link home.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="not-found" class="not-found">
    <?php
    if_untitled_component('page-intro', [
        'title'   => __('Not found', 'if-untitled'),
        'text'    => sprintf(
            '<p>%s</p><p><a href="%s">%s</a></p>',
            esc_html__('The page you are looking for does not exist.', 'if-untitled'),
            esc_url(home_url('/')),
            esc_html__('Back home', 'if-untitled')
        ),
        'columns' => '1-2',
    ]);
    ?>
</main>
<?php
get_footer();
