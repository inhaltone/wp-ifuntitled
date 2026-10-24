<?php
/**
 * Primary navigation. Toggled by src/js/components/MenuToggle.js.
 *
 * @package if-untitled
 */
?>
<header id="app-header" class="app-header">
    <div class="nav-brand">
        <button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false">
            <span class="screen-reader-text"><?php esc_html_e('Toggle menu', 'if-untitled'); ?></span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path fill-rule="evenodd" d="M6 8a.5.5 0 0 0 .5.5h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L12.293 7.5H6.5A.5.5 0 0 0 6 8Zm-2.5 7a.5.5 0 0 1-.5-.5v-13a.5.5 0 0 1 1 0v13a.5.5 0 0 1-.5.5Z"/>
            </svg>
        </button>
        <div class="nav-brand__logo">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
        </div>
    </div>
    <div class="nav-container" id="primary-navigation">
        <?php
        wp_nav_menu([
            'theme_location'  => 'primary_menu',
            'container'       => 'nav',
            'container_class' => 'nav-bar',
            'menu_class'      => 'nav-bar-list',
            'fallback_cb'     => false,
        ]);
        ?>
    </div>
</header>
