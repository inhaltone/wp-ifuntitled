<?php
/**
 * Site header: the primary menu (depth 2), one top-level item per grid column from md. Below md a "Menu" toggle
 * (top right) opens the menu as a full-screen panel with the submenus expanded. Submenus open on hover / focus (CSS);
 * src/js/components/PrimaryNav.js adds the toggle, aria-expanded and tap-to-open on touch. Without JS the menu stays
 * a visible row.
 *
 * @package if-untitled
 */
?>
<header class="site-header">
    <button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="primary-nav" data-label-close="<?php esc_attr_e('Close', 'if-untitled'); ?>"><?php esc_html_e('Menu', 'if-untitled'); ?></button>
    <?php
    wp_nav_menu([
        'theme_location'    => 'primary_menu',
        'container'         => 'nav',
        'container_class'   => 'primary-nav',
        'container_id'      => 'primary-nav',
        'container_aria_label' => __('Primary', 'if-untitled'),
        'menu_class'        => 'primary-nav__list',
        'menu_id'           => '',
        'depth'             => 2,
        'fallback_cb'       => false,
        'if_untitled_block' => 'primary-nav',
    ]);
    ?>
</header>
