<?php

/**
 * Uninstall handler for EXIFize My Dates.
 *
 * This file runs when the plugin is deleted via the WordPress admin.
 * It cleans up any data stored by the plugin.
 *
 * @package Exifize_My_Dates
 * @since   1.6.0
 */

// Exit if not called by WordPress uninstall.
if (! defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

/**
 * Currently, this plugin does not store any options or custom tables.
 * The exifize_date post meta is intentionally NOT deleted as users
 * may want to preserve their custom date overrides.
 *
 * If you want to remove all exifize_date meta on uninstall, uncomment below:
 */

/*
global $wpdb;
$wpdb->delete(
	$wpdb->postmeta,
	array( 'meta_key' => 'exifize_date' ),
	array( '%s' )
);
*/
