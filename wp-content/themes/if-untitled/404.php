<?php
/**
 * 404 template.
 *
 * @package if-untitled
 */

get_header();
?>
<main id="not-found" class="not-found">
    <div class="container">
        <h1 class="not-found__title">404</h1>
        <p class="not-found__text"><?php esc_html_e('The page you are looking for does not exist.', 'if-untitled'); ?></p>
        <a class="not-found__link" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back home', 'if-untitled'); ?></a>
    </div>
</main>
<?php
get_footer();
