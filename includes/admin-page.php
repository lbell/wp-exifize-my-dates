<?php

/**
 * Admin page rendering functions.
 *
 * @package Exifize_My_Dates
 * @since   2.0.0
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}

/**
 * Get available post types for EXIFizing.
 *
 * @since 2.0.0
 *
 * @return array Array of post type objects.
 */
function exifize_get_post_types() {
  $args = array(
    'public' => true,
  );

  $post_types = get_post_types($args, 'objects');

  // Remove attachment post type.
  unset($post_types['attachment']);

  return $post_types;
}

/**
 * Render the admin page.
 *
 * @since 1.0.0
 */
function exifize_render_admin_page() {
  // Check user capabilities.
  if (! current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'exifize-my-dates'));
  }

  $post_types      = exifize_get_post_types();
  $post_type_names = array_keys($post_types);

  // Handle form submission.
  if (isset($_POST['exifize_submit']) && isset($_POST['ptype']) && 'none' !== $_POST['ptype']) {
    $ptype = sanitize_text_field(wp_unslash($_POST['ptype']));

    // Verify nonce and post type.
    if (! isset($_POST['exifize_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['exifize_nonce'])), 'exifize_process')) {
      wp_die(esc_html__('Security check failed. Please try again.', 'exifize-my-dates'));
    }

    if (! in_array($ptype, $post_type_names, true)) {
      wp_die(esc_html__('Invalid post type selected.', 'exifize-my-dates'));
    }

    // Process the posts.
    exifize_process_posts($ptype);
  }

  // Render the form.
  exifize_render_form($post_types);
}

/**
 * Render the admin form.
 *
 * @since 2.0.0
 *
 * @param array $post_types Array of available post type objects.
 */
function exifize_render_form($post_types) {
?>
  <div class="wrap exifize-wrap">
    <h1><?php esc_html_e('EXIFize My Dates', 'exifize-my-dates'); ?></h1>

    <div class="exifize-intro">
      <p>
        <?php esc_html_e('This tool will attempt to irreversibly change the actual post date of the selected post type.', 'exifize-my-dates'); ?>
      </p>
      <p class="description">
        <?php esc_html_e('Note: Since this changes the actual post date, if you are using dates in your permalink structure, this will change them, possibly breaking incoming links.', 'exifize-my-dates'); ?>
      </p>
    </div>

    <div class="exifize-priority">
      <h2><?php esc_html_e('Date Priority', 'exifize-my-dates'); ?></h2>
      <p><?php esc_html_e('The date will be changed using (in order of priority):', 'exifize-my-dates'); ?></p>
      <ol>
        <li><?php echo wp_kses_post(__('<code>exifize_date</code> custom meta (date or "skip")', 'exifize-my-dates')); ?></li>
        <li><?php esc_html_e('EXIF date of Featured Image', 'exifize-my-dates'); ?></li>
        <li><?php esc_html_e('EXIF date of the first attached image', 'exifize-my-dates'); ?></li>
        <li><?php esc_html_e('Do nothing if no date source found', 'exifize-my-dates'); ?></li>
      </ol>
    </div>

    <form method="post" class="exifize-form">
      <?php wp_nonce_field('exifize_process', 'exifize_nonce'); ?>

      <table class="form-table" role="presentation">
        <tr>
          <th scope="row">
            <label for="ptype"><?php esc_html_e('Post Type', 'exifize-my-dates'); ?></label>
          </th>
          <td>
            <select name="ptype" id="ptype">
              <option value="none"><?php esc_html_e('— Select —', 'exifize-my-dates'); ?></option>
              <?php foreach ($post_types as $post_type) : ?>
                <option value="<?php echo esc_attr($post_type->name); ?>">
                  <?php echo esc_html($post_type->label); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="description">
              <?php esc_html_e('Choose the post type whose dates you want to change.', 'exifize-my-dates'); ?>
            </p>
          </td>
        </tr>
      </table>

      <?php submit_button(__('EXIFize Dates', 'exifize-my-dates'), 'primary', 'exifize_submit'); ?>
    </form>

    <div class="exifize-meta-info">
      <h2><?php esc_html_e('Custom Meta Override', 'exifize-my-dates'); ?></h2>
      <p>
        <?php
        printf(
          /* translators: 1: meta field name, 2: date format example */
          esc_html__('To override the EXIF date with a custom date, create a custom meta field named %1$s with a value in the format: %2$s', 'exifize-my-dates'),
          '<code>exifize_date</code>',
          '<code>YYYY-MM-DD hh:mm:ss</code>'
        );
        ?>
      </p>
      <p>
        <?php
        printf(
          /* translators: %s: example date */
          esc_html__('Example: %s', 'exifize-my-dates'),
          '<code>2012-06-23 14:07:00</code>'
        );
        ?>
      </p>
      <p>
        <?php
        printf(
          /* translators: %s: skip value */
          esc_html__('To skip a post entirely, set the value to: %s', 'exifize-my-dates'),
          '<code>skip</code>'
        );
        ?>
      </p>
    </div>

    <div class="exifize-donate">
      <p>
        <?php
        printf(
          /* translators: %s: PayPal donation link */
          esc_html__('Found this useful? Consider a %s.', 'exifize-my-dates'),
          '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BTMZ87DJDYBPS" target="_blank" rel="noopener noreferrer">' . esc_html__('small donation', 'exifize-my-dates') . '</a>'
        );
        ?>
      </p>
    </div>
  </div>
<?php
}
