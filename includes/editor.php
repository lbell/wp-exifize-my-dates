<?php

/**
 * Gutenberg editor integration.
 *
 * Registers the exifize_date meta field and enqueues the editor script.
 *
 * @package Exifize_My_Dates
 * @since   1.6.0
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}

/**
 * Register the exifize_date meta field for all public post types.
 *
 * @since 1.6.0
 */
function exifize_register_meta() {
  $post_types = get_post_types(array('public' => true), 'names');
  unset($post_types['attachment']);

  foreach ($post_types as $post_type) {
    register_post_meta(
      $post_type,
      'exifize_date',
      array(
        'show_in_rest'  => true,
        'single'        => true,
        'type'          => 'string',
        'auth_callback' => function () {
          return current_user_can('edit_posts');
        },
      )
    );
  }
}
add_action('init', 'exifize_register_meta');

/**
 * Enqueue the block editor script.
 *
 * @since 1.6.0
 */
function exifize_enqueue_editor_assets() {
  $asset_file = EXIFIZE_PLUGIN_DIR . 'assets/js/editor.asset.php';

  // Use default dependencies if asset file doesn't exist.
  if (file_exists($asset_file)) {
    $asset = include $asset_file;
  } else {
    $asset = array(
      'dependencies' => array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n'),
      'version'      => EXIFIZE_VERSION,
    );
  }

  wp_enqueue_script(
    'exifize-editor',
    EXIFIZE_PLUGIN_URL . 'assets/js/editor.js',
    $asset['dependencies'],
    $asset['version'],
    true
  );

  // Set the script translations.
  wp_set_script_translations('exifize-editor', 'exifize-my-dates');

  // Pass AJAX URL and nonce to script.
  wp_localize_script(
    'exifize-editor',
    'exifizeEditor',
    array(
      'ajaxUrl'   => admin_url('admin-ajax.php'),
      'nonce'     => wp_create_nonce('exifize_editor_nonce'),
    )
  );
}
add_action('enqueue_block_editor_assets', 'exifize_enqueue_editor_assets');

/**
 * AJAX handler to apply the date to a post using the full algorithm.
 *
 * Runs the same logic as the bulk tool: checks meta override first, then EXIF.
 *
 * @since 1.6.0
 */
function exifize_ajax_apply_date() {
  // Verify nonce.
  if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'exifize_editor_nonce')) {
    wp_send_json_error(array('message' => __('Security check failed.', 'exifize-my-dates')));
  }

  // Check capabilities.
  if (! current_user_can('edit_posts')) {
    wp_send_json_error(array('message' => __('Permission denied.', 'exifize-my-dates')));
  }

  // Get post ID.
  $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
  if (! $post_id) {
    wp_send_json_error(array('message' => __('Invalid post ID.', 'exifize-my-dates')));
  }

  // Get the post.
  $post = get_post($post_id);
  if (! $post) {
    wp_send_json_error(array('message' => __('Post not found.', 'exifize-my-dates')));
  }

  // Use the full algorithm (same as bulk tool) - check meta first, then EXIF.
  $meta_date   = trim(get_post_meta($post_id, 'exifize_date', true));
  $date_result = exifize_determine_date($post_id, $meta_date);

  // If we found a date, apply it.
  if ('success' === $date_result['status']) {
    $apply_result = exifize_apply_date($post_id, $post->post_date, $date_result);

    if ('notice-success' === $apply_result['class'] || 'notice-info' === $apply_result['class']) {
      wp_send_json_success(array(
        'date'    => $date_result['date'],
        'source'  => $date_result['source'],
        'message' => $apply_result['message'],
      ));
    } else {
      wp_send_json_error(array('message' => $apply_result['message']));
    }
  } else {
    $status_msg = exifize_get_status_message($date_result);
    wp_send_json_error(array('message' => $status_msg['message']));
  }
}
add_action('wp_ajax_exifize_apply_date', 'exifize_ajax_apply_date');

/**
 * AJAX handler to save the exifize_date meta.
 *
 * @since 1.6.0
 */
function exifize_ajax_save_meta() {
  // Verify nonce.
  if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'exifize_editor_nonce')) {
    wp_send_json_error(array('message' => __('Security check failed.', 'exifize-my-dates')));
  }

  // Check capabilities.
  if (! current_user_can('edit_posts')) {
    wp_send_json_error(array('message' => __('Permission denied.', 'exifize-my-dates')));
  }

  // Get post ID.
  $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
  if (! $post_id) {
    wp_send_json_error(array('message' => __('Invalid post ID.', 'exifize-my-dates')));
  }

  // Get the meta value.
  $meta_value = isset($_POST['meta_value']) ? sanitize_text_field(wp_unslash($_POST['meta_value'])) : '';

  // Update the meta.
  update_post_meta($post_id, 'exifize_date', $meta_value);

  wp_send_json_success(array(
    'message' => __('Override saved. Click "Set Post Date" to apply.', 'exifize-my-dates'),
  ));
}
add_action('wp_ajax_exifize_save_meta', 'exifize_ajax_save_meta');

/**
 * AJAX handler to clear the exifize_date meta.
 *
 * @since 1.6.0
 */
function exifize_ajax_clear_meta() {
  // Verify nonce.
  if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'exifize_editor_nonce')) {
    wp_send_json_error(array('message' => __('Security check failed.', 'exifize-my-dates')));
  }

  // Check capabilities.
  if (! current_user_can('edit_posts')) {
    wp_send_json_error(array('message' => __('Permission denied.', 'exifize-my-dates')));
  }

  // Get post ID.
  $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
  if (! $post_id) {
    wp_send_json_error(array('message' => __('Invalid post ID.', 'exifize-my-dates')));
  }

  // Delete the meta.
  delete_post_meta($post_id, 'exifize_date');

  wp_send_json_success(array(
    'message' => __('Override cleared. Will use image EXIF when applied.', 'exifize-my-dates'),
  ));
}
add_action('wp_ajax_exifize_clear_meta', 'exifize_ajax_clear_meta');

/**
 * AJAX handler to get the current exifize_date meta value.
 *
 * @since 1.6.0
 */
function exifize_ajax_get_meta() {
  // Verify nonce.
  if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'exifize_editor_nonce')) {
    wp_send_json_error(array('message' => __('Security check failed.', 'exifize-my-dates')));
  }

  // Check capabilities.
  if (! current_user_can('edit_posts')) {
    wp_send_json_error(array('message' => __('Permission denied.', 'exifize-my-dates')));
  }

  // Get post ID.
  $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
  if (! $post_id) {
    wp_send_json_error(array('message' => __('Invalid post ID.', 'exifize-my-dates')));
  }

  // Get the meta value.
  $meta_value = get_post_meta($post_id, 'exifize_date', true);

  wp_send_json_success(array(
    'meta_value' => $meta_value ? $meta_value : '',
  ));
}
add_action('wp_ajax_exifize_get_meta', 'exifize_ajax_get_meta');
add_action('wp_ajax_exifize_clear_meta', 'exifize_ajax_clear_meta');
