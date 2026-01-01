<?php

/**
 * Core EXIF processing functions.
 *
 * @package Exifize_My_Dates
 * @since   1.6.0
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}

/**
 * Process all posts of a given type and update their dates.
 *
 * @since 1.0.0
 *
 * @param string $post_type The post type to process.
 */
function exifize_process_posts($post_type) {
  // Double-check capabilities.
  if (! current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'exifize-my-dates'));
  }

  echo '<div class="exifize-results">';
  echo '<h2>' . esc_html__('Processing...', 'exifize-my-dates') . '</h2>';

  $args = array(
    'post_type'   => $post_type,
    'numberposts' => -1,
    'post_status' => 'any',
  );

  $posts = get_posts($args);

  if (empty($posts)) {
    echo '<p>' . esc_html__('No posts found for the selected post type.', 'exifize-my-dates') . '</p>';
    echo '</div>';
    return;
  }

  foreach ($posts as $post) {
    exifize_process_single_post($post, $post_type);
  }

  exifize_render_results_footer();
  echo '</div>';
}

/**
 * Process a single post and update its date.
 *
 * @since 1.6.0
 *
 * @param WP_Post $post      The post object.
 * @param string  $post_type The post type being processed.
 */
function exifize_process_single_post($post, $post_type) {
  $post_id       = $post->ID;
  $post_title    = $post->post_title;
  $post_date     = $post->post_date;
  $meta_date     = trim(get_post_meta($post_id, 'exifize_date', true));
  $post_edit_url = get_edit_post_link($post_id);

  // Start output for this post.
  echo '<div class="exifize-post-result">';
  printf(
    '<p class="exifize-processing">%s <a href="%s"><strong>#%d:</strong> %s</a></p>',
    esc_html__('Processing', 'exifize-my-dates'),
    esc_url($post_edit_url),
    intval($post_id),
    esc_html($post_title)
  );

  // Determine the date to use.
  $date_result = exifize_determine_date($post_id, $meta_date);

  // Handle the result.
  $result = exifize_apply_date($post_id, $post_date, $date_result);

  // Output the result message.
  printf(
    '<div class="notice %s inline"><p>%s</p></div>',
    esc_attr($result['class']),
    esc_html($result['message'])
  );

  echo '</div>';
}

/**
 * Determine which date to use for a post.
 *
 * @since 1.6.0
 *
 * @param int    $post_id   The post ID.
 * @param string $meta_date The exifize_date meta value, if any.
 * @return array Array with 'date', 'source', and 'status' keys.
 */
function exifize_determine_date($post_id, $meta_date) {
  // Check for custom meta date first.
  if (! empty($meta_date)) {
    return exifize_parse_meta_date($meta_date);
  }

  // Try featured image.
  $attachment_id = get_post_thumbnail_id($post_id);
  if ($attachment_id) {
    $result = exifize_get_attachment_date($attachment_id);
    if ('success' === $result['status']) {
      $result['source'] = __('Featured Image', 'exifize-my-dates');
      return $result;
    }
  }

  // Try first attached image.
  $attachment = exifize_get_first_attachment($post_id);
  if ($attachment) {
    $result = exifize_get_attachment_date($attachment->ID);
    if ('success' === $result['status']) {
      $result['source'] = __('first attached image', 'exifize-my-dates');
      return $result;
    }
    // Return bad EXIF result if we found an attachment but no date.
    if ('badexif' === $result['status']) {
      return $result;
    }
  }

  // No date source found.
  return array(
    'date'   => null,
    'source' => '',
    'status' => 'none',
  );
}

/**
 * Parse the exifize_date meta value.
 *
 * @since 1.6.0
 *
 * @param string $meta_date The meta date value.
 * @return array Array with 'date', 'source', and 'status' keys.
 */
function exifize_parse_meta_date($meta_date) {
  // Check for skip value.
  if ('skip' === strtolower($meta_date)) {
    return array(
      'date'   => null,
      'source' => 'exifize_date meta',
      'status' => 'skip',
    );
  }

  // Validate date format.
  $timestamp = strtotime($meta_date);
  if ($timestamp && gmdate('Y-m-d H:i:s', $timestamp) === $meta_date) {
    return array(
      'date'   => $meta_date,
      'source' => 'exifize_date meta',
      'status' => 'success',
    );
  }

  // Invalid format.
  return array(
    'date'   => $meta_date,
    'source' => 'exifize_date meta',
    'status' => 'badmeta',
  );
}

/**
 * Get the first attached image for a post.
 *
 * @since 1.6.0
 *
 * @param int $post_id The post ID.
 * @return WP_Post|null The attachment post or null.
 */
function exifize_get_first_attachment($post_id) {
  $args = array(
    'post_parent' => $post_id,
    'post_type'   => 'attachment',
    'numberposts' => 1,
    'post_status' => 'any',
  );

  $attachments = get_posts($args);

  return ! empty($attachments) ? $attachments[0] : null;
}

/**
 * Get the EXIF date from an attachment.
 *
 * @since 1.6.0
 *
 * @param int $attachment_id The attachment ID.
 * @return array Array with 'date', 'source', and 'status' keys.
 */
function exifize_get_attachment_date($attachment_id) {
  $img_meta = wp_get_attachment_metadata($attachment_id);

  if ($img_meta && ! empty($img_meta['image_meta']['created_timestamp']) && 0 !== $img_meta['image_meta']['created_timestamp']) {
    return array(
      'date'   => gmdate('Y-m-d H:i:s', $img_meta['image_meta']['created_timestamp']),
      'source' => '',
      'status' => 'success',
    );
  }

  return array(
    'date'   => null,
    'source' => '',
    'status' => 'badexif',
  );
}

/**
 * Get the status message for a date result.
 *
 * Centralized messages for all status codes to ensure consistent translations.
 *
 * @since 1.6.0
 *
 * @param array $date_result The result from exifize_determine_date().
 * @return array Array with 'message' and 'class' keys.
 */
function exifize_get_status_message($date_result) {
  switch ($date_result['status']) {
    case 'skip':
      return array(
        'message' => __('exifize_date meta is set to "skip"', 'exifize-my-dates'),
        'class'   => 'notice-info',
      );

    case 'none':
      return array(
        'message' => __('No attachment, featured image, or exifize_date meta found', 'exifize-my-dates'),
        'class'   => 'notice-warning',
      );

    case 'badexif':
      return array(
        'message' => __('Image EXIF date missing or cannot be read', 'exifize-my-dates'),
        'class'   => 'notice-error',
      );

    case 'badmeta':
      return array(
        /* translators: %s: the invalid meta value */
        'message' => sprintf(__('exifize_date meta has invalid format: %s', 'exifize-my-dates'), $date_result['date']),
        'class'   => 'notice-error',
      );

    case 'success':
      return array(
        /* translators: 1: date source, 2: the new date */
        'message' => sprintf(__('Date found from %1$s: %2$s', 'exifize-my-dates'), $date_result['source'], $date_result['date']),
        'class'   => 'notice-success',
      );

    default:
      return array(
        'message' => __('Unknown error occurred', 'exifize-my-dates'),
        'class'   => 'notice-error',
      );
  }
}

/**
 * Apply the determined date to a post.
 *
 * @since 1.6.0
 *
 * @param int    $post_id     The post ID.
 * @param string $post_date   The current post date.
 * @param array  $date_result The result from exifize_determine_date().
 * @return array Array with 'message' and 'class' keys.
 */
function exifize_apply_date($post_id, $post_date, $date_result) {
  // For non-success statuses, return the standard message with SKIPPED prefix.
  if ('success' !== $date_result['status']) {
    $status_msg = exifize_get_status_message($date_result);
    return array(
      'message' => __('SKIPPED:', 'exifize-my-dates') . ' ' . $status_msg['message'],
      'class'   => $status_msg['class'],
    );
  }

  // Check if already the same date.
  if ($date_result['date'] === $post_date) {
    return array(
      'message' => __('Already EXIFized!', 'exifize-my-dates'),
      'class'   => 'notice-info',
    );
  }

  // Update the post.
  $update_args = array(
    'ID'            => $post_id,
    'post_date'     => $date_result['date'],
    'post_date_gmt' => get_gmt_from_date($date_result['date']),
    'edit_date'     => true,
  );

  $result = wp_update_post($update_args, true);

  if (is_wp_error($result)) {
    return array(
      /* translators: %s: error message */
      'message' => sprintf(__('ERROR: %s', 'exifize-my-dates'), $result->get_error_message()),
      'class'   => 'notice-error',
    );
  }

  return array(
    /* translators: 1: date source, 2: the new date */
    'message' => sprintf(__('SUCCESS: Updated using %1$s date: %2$s', 'exifize-my-dates'), $date_result['source'], $date_result['date']),
    'class'   => 'notice-success',
  );
}

/**
 * Render the results footer with troubleshooting info.
 *
 * @since 1.6.0
 */
function exifize_render_results_footer() {
?>
  <h2><?php esc_html_e('Complete!', 'exifize-my-dates'); ?></h2>
  <p><?php esc_html_e('Please check your posts for unexpected results. Common issues include:', 'exifize-my-dates'); ?></p>
  <ol>
    <li><?php esc_html_e('EXIF dates are incorrect in the original image', 'exifize-my-dates'); ?></li>
    <li><?php esc_html_e('EXIF dates are missing from the image', 'exifize-my-dates'); ?></li>
    <li><?php esc_html_e('Images were re-saved or edited, losing EXIF data', 'exifize-my-dates'); ?></li>
  </ol>
  <hr>
  <p><a href="<?php echo esc_url(admin_url('tools.php?page=exifize-my-dates')); ?>" class="button"><?php esc_html_e('Run Again', 'exifize-my-dates'); ?></a></p>
<?php
}
