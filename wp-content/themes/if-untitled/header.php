<?php
/**
 * Site header. Opens <div id="app"> which is closed in footer.php.
 *
 * @package if-untitled
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e('Skip to content', 'if-untitled'); ?></a>
<?php get_template_part('components/loader'); ?>
<div id="app">
    <?php get_template_part('components/nav-bar'); ?>
    <div id="content">
