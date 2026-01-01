<?php

/**
 * Plugin Name: EXIFize My Dates
 * Plugin URI:  https://wordpress.org/plugins/exifize-my-dates/
 * Description: Photoblog plugin to change the published dates of a selected post type to the EXIF capture date of the Featured or first attached image.
 * Version:     1.6.3
 * Author:      LBell
 * Author URI:  https://lorenbell.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: exifize-my-dates
 *
 * @package Exifize_My_Dates
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}

/**
 * Plugin constants.
 */
define('EXIFIZE_VERSION', '1.6.3');
define('EXIFIZE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EXIFIZE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EXIFIZE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Include required files.
 */
require_once EXIFIZE_PLUGIN_DIR . 'includes/admin-page.php';
require_once EXIFIZE_PLUGIN_DIR . 'includes/exifizer.php';
require_once EXIFIZE_PLUGIN_DIR . 'includes/editor.php';

/**
 * Register the admin menu item.
 *
 * @since 1.0.0
 */
function exifize_admin_menu() {
  $hook = add_submenu_page(
    'tools.php',
    __('EXIFize Dates', 'exifize-my-dates'),
    __('EXIFize Dates', 'exifize-my-dates'),
    'manage_options',
    'exifize-my-dates',
    'exifize_render_admin_page'
  );

  // Enqueue assets only on our admin page.
  add_action('load-' . $hook, 'exifize_enqueue_admin_assets');
}
add_action('admin_menu', 'exifize_admin_menu');

/**
 * Enqueue admin CSS and JS.
 *
 * @since 1.6.0
 */
function exifize_enqueue_admin_assets() {
  wp_enqueue_style(
    'exifize-admin',
    EXIFIZE_PLUGIN_URL . 'public/css/admin.css',
    array(),
    EXIFIZE_VERSION
  );
}

/**
 * Add settings link to plugins page.
 *
 * @since 1.6.0
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function exifize_plugin_action_links($links) {
  $settings_link = sprintf(
    '<a href="%s">%s</a>',
    esc_url(admin_url('tools.php?page=exifize-my-dates')),
    esc_html__('Settings', 'exifize-my-dates')
  );
  array_unshift($links, $settings_link);
  return $links;
}
add_filter('plugin_action_links_' . EXIFIZE_PLUGIN_BASENAME, 'exifize_plugin_action_links');
