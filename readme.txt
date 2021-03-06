=== Plugin Name ===
Contributors: LBell
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BTMZ87DJDYBPS
Tags: EXIF, date, photoblog, custom, post, bulk-date-change
Requires at least: 3.0
Tested up to: 5.5
Stable tag: 1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Photoblog plugin to bulk change the published dates of a selected post type to the EXIF:capture_date of the Featured or 1st attached image of the post.

== Description ==

This tool will attempt to irreversably change the actual post dates of all entries in the post type you choose (supports posts, pages, and custom post types).

The dates will be changed using (in order of priority):

1. 'exifize_date' custom meta (date or 'skip')**
2. EXIF date of Featured Image
3. EXIF date of the first attached image
4. Do nothing. Be nothing.

**You can override the function with a custom meta field named: 'exifize_date' which accepts dates: 'YYYY-MM-DD hh:mm:ss' (for example: '2012-06-23 14:07:00') or 'skip' to prevent the EXIFizer from making any changes.
== Installation ==

1. Upload the `/exifize-my-dates/` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Navigate to 'EXIFize Dates' under the 'Tools' menu

== Frequently Asked Questions ==

= Can this plugin do X,Y, or Z? =

Probably not. It is simple, straightforward, and ugly. If there is a feature you think would be absolutely killer, let me know, and I'll what I can do.

= Can you add feature X,Y, or Z? =

Possibly - though I do this in my spare time.

= I'm getting unexpected results, what should I do? =

Post on the Wordpress.org forums would be your best bet - so others can benefit from the discussion.

== Screenshots ==


== Changelog ==

= 1.4 =
* Tested with WP 5.5

= 1.3 =
* Tested with WP 4.8

= 1.1 =
* Added security features to keep others from meddl'n with your affairs

= 1.0 =
* After a year of safe use on many blogs, I declare this plugin stable! (Or if anyone has had issues, they sure haven't talked to me about it)
* Tested to WP 3.7.1
* Note - some needed housecleaning might deactivate this plugin upon update. Since it doesn't do anything automatic... Should be no worries. Just re-activate and go on your merry way.

= 0.1 =
First release
