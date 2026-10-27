<?php
/**
 * if-untitled theme bootstrap.
 *
 * Keep this file thin: every concern lives in its own file under /inc.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

define('IF_UNTITLED_VERSION', wp_get_theme()->get('Version'));
define('IF_UNTITLED_DIR', get_template_directory());
define('IF_UNTITLED_URI', get_template_directory_uri());

require_once IF_UNTITLED_DIR . '/inc/vite.php';
require_once IF_UNTITLED_DIR . '/inc/setup.php';
require_once IF_UNTITLED_DIR . '/inc/post-types.php';
require_once IF_UNTITLED_DIR . '/inc/template-tags.php';
require_once IF_UNTITLED_DIR . '/inc/acf.php';
