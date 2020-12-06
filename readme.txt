=== Flamingo ===
Contributors: takayukister, megumithemes, itpixelz
Tags: bird, contact, mail, crm
Requires at least: 5.4
Tested up to: 5.6
Stable tag: 2.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A trustworthy message storage plugin for Contact Form 7.

== Description ==

Flamingo is a message storage plugin originally created for [Contact Form 7](https://wordpress.org/plugins/contact-form-7/), which doesn't store submitted messages.

After activation of the plugin, you'll find *Flamingo* on the WordPress admin screen menu. All messages through contact forms are listed there and are searchable. With Flamingo, you are no longer need to worry about losing important messages due to mail server issues or misconfiguration in mail setup.

For more detailed information, please refer to the [Contact Form 7 documentation page](https://contactform7.com/save-submitted-messages-with-flamingo/).

= Privacy Notices =

This plugin stores submission data collected through contact forms, which may include the submitters' personal information, in the database on the server that hosts the website.

== Installation ==

1. Upload the entire `flamingo` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 2.2.1 =

* Outputs a local date/time in a CSV export file.
* Removes `load_plugin_textdomain()` calls.
* Removes a reference to `$_wp_last_object_menu`.
* Removes the `set-screen-option` filter.
* Inherits `post_status` from the previous admin page.
* Avoids using `wp_date()` for MySQL DATETIME values.
* Has been tested with WordPress 5.6.

= 2.2 =

* Sets the `post_date` of an inbound message based on the submission timestamp.
* Allows users to search and filter messages within the Spam subgroup.
* Changes the visibility of the `$found_items` property to private and introduces the `count()` method as an alternative.
* Changes the visibility of the `$id` property to private and introduces the `id()` method as an alternative.
* Introduces the submission result in the inbound message viewer screen.
* Stores the `posted_data_hash` value for search.

= 2.1.1 =

* Security enhancement, CSV: Prefixes a field when its value begins with `=`, `+`, `-`, or `@`. See https://contactform7.com/2020/01/15/heads-up-about-spreadsheet-vulnerabilities/ for details.
* New filter hook: flamingo_csv_field_prefix

= 2.1 =

* UI improvements in displaying JSON reCAPTCHA logs in the Inbound Message editor page.
* Moves to trash automatically after every 30 days of the creation of spam messages.
