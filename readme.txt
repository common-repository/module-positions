=== Module Positions ===
Contributors: philippkuehn
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=R8543KFNL7NR8
Tags: module, modules, widget, widgets, custom, dynamic, hide, logic, show, sidebar, content
Requires at least: 3.3
Tested up to: 3.9
Stable tag: 1.2.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0

A simplified equivalent to Joomla's module positions. Create module positions, assign content and choose on which pages it will be shown.

== Description ==

### What does it do? ###

If you have a site with multiple subpages, you might have some positions in your template where you probably want to manage individual content (like a sidebar, a footer, a banner or whatever). The WordPress widgets can display content in specific widget-positions, but you can't tell WordPress to only display your content on a certain page - That's where my plugin comes in.

You can create different module positions, assign content and choose on which pages it will be shown. If you use Wordpress as a CMS, this will be very helpful.

### Create Module Positions ###

First, you can create different module positions. Rename these as you want. To put a module position in your template you can easily copy & paste the shortcode right next to the positions's name. if you have already created content, you can drag and drop these around to change their order.

### Create Content ###

Now, you can create content for your module positions. You can choose on which pages, categories or posts your content will be displayed. For this information your [custom menus](http://codex.wordpress.org/Appearance_Menus_Screen/ "custom menus") will be loaded (which are defined in your template).

If there are categories with posts in your custom menu, click the "+"-button to show them. You also can choose on which posts your content will be displayed here.

### Define your HTML-markup ###

In the little metabox on the right side, you can simply define your HTML-markup. Try writing <code>{{post_content}}</code> for your content and <code>{{post_title}}</code> for your title - A list of all values can be [found here](http://codex.wordpress.org/Function_Reference/get_post#Return "get_post"). To reset your markup just click on "Reset", that will load the standard markup (which can be changed on the settings page).

### Settings ###

* Change your standard HTML-markup. If you click on "Reset" in your moduleposition-posts, this will be loaded.
* Change the order of posts in the drop-down menus of your categories. That may be useful if you have a lot of posts in a category and you want to find a special post.

### Missing something? Send me an e-mail! ###
I'm open to new ideas! Write me: kontakt@philipp-kuehn.com

== Installation ==

1. Upload the entire <code>module-positions</code> folder to the <code>/wp-content/plugins/</code> directory.
1. Activate the plugin through the "Plugins" menu in WordPress.

== Frequently asked questions ==

Coming soon...

== Screenshots ==

1. Create Module Positions.
2. Create Content.
3. Choose on which pages your content will be shown.
4. Define your HTML-Markup.
5. Settings

== Changelog ==

#### 1.2.6 ####
* Fixed: some errors

#### 1.2.5 ####
* Fixed: some errors

#### 1.2.4 ####
* Fixed: compatible to WordPress 3.8

#### 1.2.3 ####
* Fixed: some errors

#### 1.2.2 ####
* Fixed: compatible to WordPress 3.6

#### 1.2.1 ####
* Fixed: set english as default language if local language is not supported

#### 1.2 ####
* Added: a module positions widget – you now simply can use module positions in a sidebar
* Added: WPML support
* Added: an option to select (and deselect) all subpages with one click
* Added: some descriptions
* Fixed: some errors
* Some visual changes

#### 1.1 ####
* Added: special pages for selecting (404, search, archive, tag, author)
* Added: show name of module positions in content list
* Changed: set permalink for all custom post types of module positions to '/'
* Changed: default visibility values are now "none" instead of "all"
* Fixed: some errors
* Code cleaning

#### 1.0.1 ####
* Fixed: checkboxes are sometimes not visible
* Fixed: error if the requested module position does not exist
* Code cleaning

== Upgrade notice ==
