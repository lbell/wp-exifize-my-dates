=== EXIFize My Dates ===
Contributors: LBell
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BTMZ87DJDYBPS
Tags: EXIF, date, photoblog, custom post type, bulk edit, photography
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Photoblog plugin to bulk change the published dates of a selected post type to the EXIF capture date of the Featured or first attached image.

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

= 2.0.0 =
* Major code refactoring for better maintainability
* Split code into organized file structure
* Improved WordPress coding standards compliance
* Enhanced security with proper escaping and sanitization
* Added proper internationalization support
* Improved admin UI with better styling
* Fixed GMT date calculation for post updates
* Added plugin action link for quick access

= 1.5.1 =
* Tested to WP 6.9

= 1.4 =
* Tested with WP 5.5

= 1.3 =
* Tested with WP 4.8

= 1.1 =
* Added security features to keep others from meddl'n with your affairs

= 1.0 =
* After a year of safe use on many blogs, I declare this plugin stable!
* Tested to WP 3.7.1

= 0.1 =
* First release
