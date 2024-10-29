=== Apex Digital Toolbox ===
Contributors: nwells
Donate link: https://www.apexdigital.co.nz/contact.php
Tags: administration, setup, staging, production
Requires at least: 3.0.1
Requires PHP: 7.1
Tested up to: 6.5.5
Stable tag: 1.4.13
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Too many plugins installed to do basic things? Bring some common functions ones into one plugin to make life that little bit easier for developers.

== Description ==

Too many plugins installed to do basic things? This plugin tries to bring some common ones into one plugin to make life that little bit easier.

**Current functionality**

* Identify the production URL so as to apply specific logic or hooks depending on which environment the site is in
* Block visitors to the staging site based on IP or by using a specific cookie - great for showing clients but not the world
* Find & replace functionality - great for changing from a staging URL to a production URL
* Auto 301 redirect to the site domain for WordPress - useful to ensure everyone is using the correct path i.e. with www (or not) and https (or not)
* Add additional classes to the main body tag to easily target device and operating system i.e. iOS, Android, Chrome, etc...
* Sitemap generator to display a list of pages (or any post type) on the site as well as offering the ability to exclude pages
* WooCommerce settings to disable categories list on single product page, remove reviews tab, remove product count on categories
* When using Visual Composer you can automatically load in any PHP files that make use of vc_map() within your theme
* When using Gravity Forms & Bootstrap all correct classes will be applied to input boxes and buttons. Also, a new field type is added to add columns to forms as well as placing the submit button wherever you like
* Gravity Forms confirmation message appear underneath any fixed header when using AJAX. This hook allows you to scroll to the correct position based on the header
* Can specify a stylesheet that you want to appear last in the enqueue - useful for overwriting parent themes or other plugins
* YouTube embedded videos can have the title, related videos, and controls switched off
* Change the sender name and email address for emails sent
* Short code for displaying the current year - useful for keeping copyright notices up-to-date
* WooCommerce template tweaks for improved usability when using the Jupiter theme
* Set parent hierarchy pages as place holders so they don't provide links in menus to empty pages
* Simple short code for the current page title - useful to add in to links
* Disable certain notifications for admin
* Added Relevanssi support for XforWooCommerce filter plugin when AJAX is in use

**Coming soon**

* Drag & drop page re-ordering
* Improve noindexing on WooCommerce hidden products as well as ensuring the don't appear in sitemaps both HTML & XML
* Auto hide a page from any menu when its status is no longer published
* Additional default settings for Visual Composer to make it easier to extend and remove built in elements & templates
* More to come!

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/apex-wordpress-toolbox` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Navigate to `Apex Toolbox->Hooks` to switch on which hooks you want to take advantage of
1. Some hooks provide specific settings that can be found under `Apex Toolbox->Settings`

== Frequently Asked Questions ==

= Why can't I un-check some hooks? =

Some hooks need to be on by default for the plugin to run in its basic state. Being able to remove the menu from WordPress, for example, would mean you could no longer change anything.

= Common hook your after not listed above? =

Let us know what you're after and we can look at adding it to the list. This plugin is developed to be very light weight by allowing the administrator
to switch features on and off as needed. Only when a specific features is required does WordPress even get told about it.

== Screenshots ==

1. Lists available hooks that can be switched on or off
2. Various settings available based on the hooks in use
3. Find & replace hook interface
4. Blocked user for when trying to access development site

== Changelog ==
= 1.4.13 =
* Add a hook to introduce a purchase order number field to the checkout
= 1.4.12 =
* Fix a bug with asset inlining where some CSS or JS files may not be included in the case a script and a style have the same handle.
* Fix a bug with the WC search redirect hook where searching the media library in list would redirect to the frontend.
= 1.4.11 =
* Add hook to better handle WooCommerce session cookies becoming invalid
= 1.4.10 =
* Handle multiple forms correctly in the Gravity Forms Confirmation Scrolling hook
= 1.4.9 =
* Add hook to add Yoast short variables (Title and Excerpt)
= 1.4.8 =
* Fix critical error in Account Menu hook
* Improve performance of WooCommerce search redirect
= 1.4.7 =
* Add hook to disable timeout for WC Cart Fragments request
* Only register Elementor_ListCategories_Widget when required
* Add filter for my account menu label
* Set account menu endpoints to active when endpoint is viewed
= 1.4.6 =
* Add a hook to monitor WP All Import imports
* Add a custom payment gateway to WooCommerce
* Add hook to allow redirection from native to WooCommerce search
* Add hook to hide WooCommerce account menu items
* Add the ability to customize the "My Account" and "Login" text
* Add a custom variable for Yoast to display the primary WC category
= 1.4.5 =
* Avoid WooCommerce schedules being created for download permission
* Adjust output of WooCommerce schema on Elementor pages
= 1.4.4 =
* Added a hook to improve the back button experience on WooCommerce sites using the product filter plugin
= 1.4.3 =
* Added WooCommerce widget for displaying product schema when using Elementor
* Added dynamic WooCommerce my account menus for valid end points
* Improved page title shortcode to allow for other archive types
* Improved styling options for Elementor Category List widget
= 1.3.2 =
* Improved tools for Google Page Speed
= 1.3.1 =
* Added Elementor widget for outputting a list of taxonomy links based on their parent
* Added an option to allow the editor role to manage the privacy policy
* Bug fixes
= 1.3.0 =
* Improved security
* Removed search and replace functionality
* Added start of page speed improvements
* Gravity Forms anchor scroll fix
= 1.2.5 =
* Ignore staging lockout when running from a ddev server
* Ignore redirects when running from CLI or an AJAX call
* Bug fixes
= 1.2.4 =
* Added page title shortcode
* Improved top level menu item being disabled when it is just a placeholder to show the sub-menu
= 1.2.3 =
* Hide WooCommerce shipping destination block
* Add Boostrap classes to time blocks in Gravity Forms
* Don't deny access to cron tasks when checking if there is access to a staging site
* Only show WooCommerce coupons if they have been enabled
* Added a filtering option for showing the shop filter
* New hook for redirecting parent pages to their siblings
= 1.2.2 =
* Updated WooCommerce additional CSS
* Updated website restriction code to allow you to bypass by logging in to the the website
* Notice fixes for queuing CSS & JS files
= 1.2.1 =
* Backwards compatibility fix for serialization of options
= 1.2.0 =
* Fix for serialization of options to work better with WordPress and other plugins
* Added a new hook for the current year
* Added improved template options for WooCommmerce and the Jupiter theme
= 1.1.3 =
* Taxonomy support in sitemaps
* Can now change the sender name and email address for emails sent
= 1.1.2 =
* Update for scanning Visual Composer directory for custom templates - the vc_before_init hook appears to have changed in someway
= 1.1.1 =
* YouTube embedded videos can have the title, related videos, and controls switched off
= 1.1 =
* Gravity Forms support for scrolling confirmation messages in to view
* WordPress 4.9 support
* Bug fixes
= 1.0 =
* Official release
= 0.3.8 =
* Added new sitemap hook and shortcode
* Updated find and replace hook to work better with post meta data when updating URLs
= 0.3.7 =
* Initial release

== Upgrade Notice ==
None
