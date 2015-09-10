=== SEOshop Sign Up Form by Web Whales ===
Contributors: ronald_edelschaap
Tags: seoshop,affiliate,ecommerce,e-commerce
Requires at least: 4.2
Tested up to: 4.3
Stable tag: 1.0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Provides an affiliate sign up form for SEOshop Partners.

== Description ==
This plugin provides an affiliate sign up form for SEOshop Partners. With this sign up form your clients and visitors can request a 14-days trial store at SEOshop. The stores registered with this form automatically show up in your SEOshop Partner Dashboard. To avoid spam sign ups, this plugin has a built in reCAPTCHA by Google functionality.

Not yet a SEOshop Partner? Request a partnership for free at [SEOshop](http://www.getseoshop.com/partners/?utm_source=Web%20Whales&utm_medium=referral&utm_campaign=Web%20Whales%20SEOshop%20Sign%20Up%20WordPress%20plugin).

The plugin language is English. Dutch language files are included. Feel free to send your translation to me, so I can include it in the plugin.

Proudly presented to you by [Web Whales](https://webwhales.nl/).

== Installation ==
1. Download the archive and unzip it in /wp-content/plugins, upload the archive at Plugins > Add New > Upload Plugin or install via Plugins > Add New > Search for the plugin name in the search bar.
2. Activate the plugin through the Plugins menu in WordPress
3. Update the plugin settings in with the settings page located in the WordPress settings menu.

== Frequently Asked Questions ==
= The sign up form doesn't fit well in my theme. Is there anyway to adjust the form? =
Yes there is! Just copy the `sign_up_form.phtml` file from `/wp-content/plugins/web-whales-seoshop-sign-up-form/templates/shortcodes` to `/wp-content/themes/[your (child) theme]/seoshop-sign-up/shortcodes/` and modify it. Please make sure you don't remove any classes, elements or input names to keep the form working.

= How do I use the callback on success setting? =
When you have set the callback on success, that JavaScript code is executed when a the SEOshop store was created successfully. You should use an entire line of JavaScript, not just the function's name. For example: `my_javascript_function()` or `ga('send', 'event', 'SEOshop', 'sign ups', 'new sign up')`.

== Changelog ==

= 1.0.1 =
* Added: Examples for the callback on success setting
* Improved: Added the `$post` variable to the `has_shortcode_filter_post_content` filter
* Updated: Translation files

= 1.0 =
* First release
