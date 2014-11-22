=== Multi Meta Box ===
Contributors: toxicToad
Author URI: http://strawberryjellyfish.com/
Donate link: http://strawberryjellyfish.com/donate/
Requires at least: 3.5
Tested up to: 4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Utility class to handle input metaboxes for WordPress edit screens

== Description ==

The Multi Meta Box Class is intended to be included by WordPress plugins
or themes to provide an easy way to create meta box input in a variety of
formats and handle saving of their data.

It has evolved from it's origins to suit my own needs, which may or may not
be useful to you. Class, style and function names have changed to avoid
collisions with the original All Types Metabox. There has also been a fair
amount of re-factoring and code clean-up to give a more consistent coding
style and naming conventions.


Refer to README_original.txt for previous history and information

== Multi Meta Box version history ==
* 3.2.3 - image size code moved to own function, assorted cleanups
* 3.2.2 - Renamed to Multi Meta Box changed related namespacing, code cleanups
* 3.2.1 - Changed repeater and image input handling to make multiple image
inputs more use friendly, more cleanups.
* 3.2.0 - Code cleanups, Added jQuery UI Slider number input

TODO:
* more code cleanups, clean up commenting and inline documentation
* add other field types (geotagger etc)
* fix repeater styles for horizontal, alternate label less full width input style, better WordPress 4.0 UI style
* update required JavaScript libraries, CSS
* Merge in taxonomy handling
* Add default validations types
