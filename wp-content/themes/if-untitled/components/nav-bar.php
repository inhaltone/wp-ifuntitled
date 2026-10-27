<?php
/**
 * Site header: the primary menu (depth 2), one top-level item per grid column. Submenus open on hover /
 * focus (CSS); src/js/components/PrimaryNav.js adds aria-expanded and tap-to-open on touch.
 *
 * @package if-untitled
 */
?>
<header class="site-header">
    <?php
    wp_nav_menu([
        'theme_location'    => 'primary_menu',
        'container'         => 'nav',
        'container_class'   => 'primary-nav',
        'container_aria_label' => __('Primary', 'if-untitled'),
        'menu_class'        => 'primary-nav__list',
        'menu_id'           => '',
        'depth'             => 2,
        'fallback_cb'       => false,
        'if_untitled_block' => 'primary-nav',
    ]);
    ?>
</header>
