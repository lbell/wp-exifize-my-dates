=== EXIFize My Dates ===
Contributors: LBell
Donate link: https://github.com/sponsors/lbell 
Tags: EXIF, metadata, photo, date, photoblog
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable Tag: 1.6.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Update Post Dates from Image Metadata (EXIF) in WordPress. Great for photoblogs.

== Description ==

Update the post dates of your WordPress posts, pages, or custom post types based on the EXIF metadata of your images. Can be done in bulk (via Tools) or one at a time (via Edit Post screen).

Perfect for photoblogs where you want the post date to reflect when the photo was actually taken, rather than when it was uploaded.

The dates will be changed using (in order of priority):

1. Custom date of your choice (date or 'skip')**
2. EXIF date of Featured Image
3. EXIF date of the first attached image

**You can override the function with a custom meta field named: 'exifize_date' which accepts:
 - 'YYYY-MM-DD hh:mm:ss' (for example: '2012-06-23 14:07:00')
 - 'skip' (to prevent the EXIFizer from making any changes.)


== Installation ==

1. Upload the `/exifize-my-dates/` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Change post dates one at a time via the 'Edit Post' screen
1. Bulk change dates using 'EXIFize Dates' under the 'Tools' menu

== Frequently Asked Questions ==

= Can this plugin do X,Y, or Z? =

Probably not. It is simple, straightforward, and ugly. If there is a feature you think would be absolutely killer, let me know, and I'll what I can do.

= Can you add feature X,Y, or Z? =

Possibly - though I do this in my spare time.

= I'm getting unexpected results, what should I do? =

Post on the Wordpress.org forums would be your best bet - so others can benefit from the discussion.

== Screenshots ==


== Changelog ==
= 1.6.3 =
* Version bump

= 1.6.0 =
* Added: support for Gutenberg editor
* Added: internationalization support
* Improved: Better admin styling
* Improved: Major code refactoring for better maintainability
* Improved: security 

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
