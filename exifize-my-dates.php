<?php
/*
Plugin Name: EXIFize My Dates
Plugin URI: http://wordpress.org/extend/plugins/exifize-my-dates/
Description: Photoblog plugin to change the published dates of a selected post type to the EXIF:capture_date of the Featured or 1st attached image of the post.
Version: 1.1
Author: LBell
Author URI: http://twitter.com/lbell
License: GPL2

	Copyright 2012 -- Loren Bell
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
	
	if(isset($_POST['submit']) && $_POST['ptype'] != 'none') {
		// Check nonce if we are asked to do something...
		if( check_admin_referer('exifize_my_dates_nuclear_nonce') ){
			$ptype = $_POST['ptype'];
			exifizer_nuclear_option($ptype);
		} else {
			wp_die( 'What are you doing, Dave? (Invalid Request)' );
		}
	}
	
	$args=array(
		'public'   => true,
		//'_builtin' => false
	); 
	$output = 'objects';
	$operator = 'and';
	$post_types = get_post_types($args,$output,$operator); 
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

		$exifdate = 'none'; //safety
		$postid = $post->ID;
		$posttitle = $post -> post_title;
		$postdate = $post -> post_date;
		$metadate = trim(get_post_meta($postid, 'exifize_date', true));
		$pediturl = get_admin_url() . "post.php?post=" . $postid . "&action=edit";
	
		echo "<p>Processing " . $ptype . " <a href = \"". $pediturl . "\" title=\"Edit " . $ptype . " \">" . $postid . ": \"" . $posttitle . "\"</a> "; 
	
		if($metadate && $metadate != ''){ 								//If custome meta `efize_date` is set, use it
			switch ($metadate){
			case date('Y-m-d H:i:s', strtotime($metadate)):  //check for correct date format
				$exifdate = $metadate;
				$exifdetails = "exifize_date custom meta";
				break;
			case 'skip':
				$exifdate = 'skip';
				break;
			default:
				$exifdate = 'badmeta';
			}
		}else{
			$attachid = get_post_thumbnail_id($postid);	// First, try to get the featured image id
			
			if($attachid){
				$exifdetails = "Featured Image";
				$attachname = get_post( $attachid )->post_title;	
			}else{										// if no featured image id, then get first attached
				$attachargs = array( 
					'post_parent' => $postid,     	
					'post_type'   => 'attachment', 	
					'numberposts' => 1,    			
					'post_status' => 'any', 		
				);

				$attachment = get_posts($attachargs);
			
				if($attachment){
					$attachid = $attachment[0]->ID;
					$attachname = $attachment[0]->post_name; 
					$exifdetails = "attached image";
				} else {
					$exifdetails = "What are you doing, Dave?";
				}
			} // end if no featured image 

			
		
			if(!$attachid){
				$exifdate = "none";  // No attachment or thumbnail ID found
			} else {
				echo "using EXIF date from " . $exifdetails . " id ". $attachid . ": \"" . $attachname . "\"</p>"; 
			
				$imgmeta = wp_get_attachment_metadata($attachid, false);
					
				if($imgmeta && $imgmeta['image_meta']['created_timestamp'] != 0){			//use EXIF date if not 0	
					$exifdate = date("Y-m-d H:i:s", $imgmeta['image_meta']['created_timestamp']);	
				} else {
					$exifdate = 'badexif';
				}
			}// end get EXIF date
		}// end no metadate
		
		// if we have image meta and it is not 0 then...
		
		switch ($exifdate){
		case 'skip':
			$exifexcuse = __("SKIP: 'exifize_date' meta is set to 'skip'");
			$exifexclass = "updated";
			break;
		case 'none':	
			$exifexcuse = __("SKIP: No attachment, featured image, or 'exifize_date' meta found");
			$exifexclass = "updated";
			break;
		case 'badexif':
			$exifexcuse = __("SKIP: WARNING - image EXIF date missing or can't be read");
			$exifexclass = "error";
			break;
		case 'badmeta':
			$exifexcuse = __("SKIP: WARNING - 'exifize_date' custom meta is formatted wrong: ") . $metadate;
			$exifexclass = "error";
			break;		
		case $postdate:
			$exifexcuse = __("Already EXIFized!");
			$exifexclass = "updated \" style=\" background:none ";
			break;
		default:
			$update_post = array(
				'ID' => $postid,
				'post_date' => $exifdate,
				'post_date_gmt' => $exifdate,
				//'edit_date' => true,
			);
	
			$howditgo = wp_update_post($update_post);
	
			if($howditgo != 0){
				$exifexcuse = "Post " . $howditgo . " EXIFIZED! using " . $exifdetails . " date: " . $exifdate . " " ;
				$exifexclass = "updated highlight";
			}else{
				$exifexcuse = "ERROR... something went wrong... with " . $postid .". You might get that checked out.";
				$exifexclass = "error highlight";
			} //end howditgo				
		} //end switch	
		
		echo "<div class=\"" . $exifexclass . "\"><p>" . $exifexcuse . "</p></div>";
		
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
