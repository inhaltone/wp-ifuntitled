<?php
/**
 * Site footer component.
 *
 * @package if-untitled
 */
?>
<footer class="site-footer">
    <div class="container site-footer__inner">
        <span class="site-footer__copy">&copy; <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></span>
        <?php
        wp_nav_menu([
            'theme_location'  => 'footer_menu',
            'container'       => 'nav',
            'container_class' => 'site-footer__nav',
            'menu_class'      => 'site-footer__list',
            'depth'           => 1,
            'fallback_cb'     => false,
        ]);
        ?>
    </div>
</footer>
