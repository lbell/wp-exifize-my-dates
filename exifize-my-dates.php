<?php
/*
Plugin Name: EXIFize My Dates
Plugin URI: http://wordpress.org/extend/plugins/exifize-my-dates/
Description: Photoblog plugin to change the published dates of a selected post type to the EXIF:capture_date of the Featured or 1st attached image of the post.
Version: 1.5
Author: LBell
Author URI: lorenbell.com
License: GPL2

	Copyright 2020 -- LBell
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	For a copy of the GNU General Public License write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* TODO (dependent upon demand, time, and income):
	- Add category includer/excluder
	- Add tag includer/excluder
	- Add other exifizing goodies (tags)
*/

add_action( 'admin_menu', 'exifize_date_menu' );
function exifize_date_menu() {
	add_submenu_page( 'tools.php', 'EXIFize Dates', 'EXIFize Dates', 'manage_options', 'exifize-my-dates', 'exifize_my_dates' );
}

function exifize_my_dates() {
	?>
	<div class="">
		<h1>EXIFize My Dates</h1>

	<?php

  // Get Post Types
  $args=array(
    'public'   => true,
    //'_builtin' => false
  );
  $output = 'objects';
  $operator = 'and';
  $post_types = get_post_types($args,$output,$operator);
  $types_list = array();
  foreach ($post_types  as $post_type ){
    if($post_type->name != 'attachment') $types_list[] = $post_type->name;
  }
  
	if(isset($_POST['submit']) && $_POST['ptype'] != 'none') {
    $ptype = sanitize_text_field( $_POST['ptype'] );

    // Check nonce if we are asked to do something...
		if( check_admin_referer('exifize_my_dates_nuclear_nonce') && in_array( $ptype, $types_list ) ){
			exifizer_nuclear_option($ptype);
		} else {
			wp_die( 'What are you doing, Dave? (Invalid Request)' );
		}
	}


	?>

		<p>This tool will attempt to <em>irreversably</em> change the <em>actual</em> post date of Post Type selected below.
		<br /><small><em>Note: since this changes the actual post date, if you are using dates in your permalink structure, this will change them, possibly breaking incomming links.</small></em></p>
		</p>
		<p>The date will be changed using (in order of priority):</p>
		<ol>
			<li>'exifize_date' custom meta (date or 'skip')**</li>
			<li>EXIF date of Featured Image</li>
			<li>EXIF date of the first attached image</li>
			<li>Do nothing. Be nothing.</li>
		</ol>

		<p>Choose the post type who's dates you want to change:</p>
		<form name="input" action="<?php $_SERVER['PHP_SELF'];?>" method="post">
			<?php
			if ( function_exists('wp_nonce_field') ) wp_nonce_field('exifize_my_dates_nuclear_nonce');
			?>

			<select name="ptype">
				<option value="none">None</option>
				<?php
					foreach ($post_types  as $post_type ){
						if($post_type->name != 'attachment') echo '<option value="'. $post_type->name .'">'. $post_type->label . '</option>';
					}
				?>
			</select>
			<input type="submit"  name="submit" value="Submit" />
		</form>

		<p><em>**To override the function with a custom date, create a new custom meta field with the name: 'exifize_date' and value: 'YYYY-MM-DD hh:mm:ss' -- for example: '2012-06-23 14:07:00' (no quotes). You can also enter value: 'skip' to prevent the EXIFizer from making any changes.</em></p>
		<br />
		<p><small>Your life just got a whole lot simpler. Please consider a <a href=https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BTMZ87DJDYBPS>small token of appreciation (paypal).</a></small></p>
	</div>
	<?php
} //end function exifize_my_dates()


function exifizer_nuclear_option($ptype){
	if ( ! current_user_can( 'manage_options' ) )
		wp_die( 'What are you doing, Dave? (Insufficient Capability)' );

	echo "<h2>Working...</h2>";

	$args = array(
		'post_type' => $ptype,
		'numberposts' => -1,
		'post_status' => 'any',
	);

	$allposts = get_posts( $args );

	foreach($allposts as $post) : setup_postdata($post);

		$exif_date = 'none'; //safety
		$post_id = $post->ID;
		$post_title = $post -> post_title;
		$post_date = $post -> post_date;
		$meta_date = trim(get_post_meta($post_id, 'exifize_date', true));
		$post_edit_url = get_admin_url() . "post.php?post=" . $post_id . "&action=edit";

		echo "<p>Processing " . $ptype . " <a href = \"". $post_edit_url . "\" title=\"Edit " . $ptype . " \">" . $post_id . ": \"" . $post_title . "\"</a> ";

    //If custom meta `exifize_date` is set, use it
    if($meta_date && $meta_date != ''){ 								
			switch ($meta_date){
			case date('Y-m-d H:i:s', strtotime($meta_date)):
				$exif_date = $meta_date;
				$exif_details = "exifize_date custom meta";
				break;
			case 'skip':
				$exif_date = 'skip';
				break;
			default:
				$exif_date = 'badmeta';
      }
    // Otherwise, try to get the featured image id
		}else{
			$attach_id = get_post_thumbnail_id($post_id);	
			if($attach_id){
				$exif_details = "Featured Image";
        $attach_name = get_post( $attach_id )->post_title;
      // if no featured image id, then get first attached
			}else{										
				$attach_args = array(
					'post_parent' => $post_id,
					'post_type'   => 'attachment',
					'numberposts' => 1,
					'post_status' => 'any',
				);

				$attachment = get_posts($attach_args);

				if($attachment){
					$attach_id = $attachment[0]->ID;
					$attach_name = $attachment[0]->post_name;
					$exif_details = "attached image";
				} else {
					$exif_details = "What are you doing, Dave?";
				}
			} // end if no featured image



			if(!$attach_id){
				$exif_date = "none";  // No attachment or thumbnail ID found
			} else {
				echo "using EXIF date from " . $exif_details . " id ". $attach_id . ": \"" . $attach_name . "\"</p>";

				$img_meta = wp_get_attachment_metadata($attach_id, false);

				if($img_meta && $img_meta['image_meta']['created_timestamp'] != 0){			//use EXIF date if not 0
					$exif_date = date("Y-m-d H:i:s", $img_meta['image_meta']['created_timestamp']);
				} else {
					$exif_date = 'badexif';
				}
			}// end get EXIF date
		}// end no meta_date

		// if we have image meta and it is not 0 then...

		switch ($exif_date){
		case 'skip':
			$exif_excuse = __("SKIP: 'exifize_date' meta is set to 'skip'");
			$excuse_class = "updated";
			break;
		case 'none':
			$exif_excuse = __("SKIP: No attachment, featured image, or 'exifize_date' meta found");
			$excuse_class = "updated";
			break;
		case 'badexif':
			$exif_excuse = __("SKIP: WARNING - image EXIF date missing or can't be read");
			$excuse_class = "error";
			break;
		case 'badmeta':
			$exif_excuse = __("SKIP: WARNING - 'exifize_date' custom meta is formatted wrong: ") . $meta_date;
			$excuse_class = "error";
			break;
		case $post_date:
			$exif_excuse = __("Already EXIFized!");
			$excuse_class = "updated \" style=\" background:none ";
			break;
		default:
			$update_post = array(
				'ID' => $post_id,
				'post_date' => $exif_date,
				'post_date_gmt' => $exif_date,
				//'edit_date' => true,
			);

			$howditgo = wp_update_post($update_post);

			if($howditgo != 0){
				$exif_excuse = "Post " . $howditgo . " EXIFIZED! using " . $exif_details . " date: " . $exif_date . " " ;
				$excuse_class = "updated highlight";
			}else{
				$exif_excuse = "ERROR... something went wrong... with " . $post_id .". You might get that checked out.";
				$excuse_class = "error highlight";
			} //end howditgo
		} //end switch

		echo "<div class=\"" . $excuse_class . "\"><p>" . $exif_excuse . "</p></div>";

	endforeach;

	?>
	<h2>All done!</h2>
	<p>Please check your posts for unexpected results... Common errors include:
	<ol>
		<li>EXIF dates are wrong</li>
		<li>EXIF dates are missing</li>
		<li>The stars have mis-aligned creating a reverse vortex, inserting a bug in the program... please let me know and I'll try to fix it.</li>
	</ol>
	</p>

	<br /><hr><br />
	<h2>Again?</h2>
	<?php
} //end function exifizer_nuclear_option
?>
