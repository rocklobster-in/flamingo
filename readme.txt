=== Flamingo ===
Contributors: rocklobsterinc, takayukister, megumithemes, itpixelz
Tags: bird, contact, mail, crm
Requires at least: 6.7
Tested up to: 6.8
Stable tag: 2.5
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://contactform7.com/donate/

A trustworthy message storage plugin for Contact Form 7.

== Description ==

Flamingo is a message storage plugin originally created for [Contact Form 7](https://wordpress.org/plugins/contact-form-7/), which doesn't store submitted messages.

After activation of the plugin, you'll find **Flamingo** on the WordPress admin screen menu. All messages through contact forms are listed there and are searchable. With Flamingo, you no longer need to worry about losing important messages due to mail server issues or misconfiguration in mail setup.

For more detailed information, please refer to the [Contact Form 7 documentation page](https://contactform7.com/save-submitted-messages-with-flamingo/).

= Privacy Notices =

This plugin stores submission data collected through contact forms, which may include the submitters' personal information, in the database on the server that hosts the website.

== Installation ==

1. Upload the entire `flamingo` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 2.6 =

* Bumps up the minimum required WordPress version to 6.7.
* Fixes errors reported by PCP.
* Performs a tune-up for the cron job scheduling.

= 2.5 =

* Bumps up the minimum required WordPress version to 6.4.
* Uses `wp_json_encode()` instead of `json_encode()`.
* Uses `get_views_links()`.
* Uses null coalescing operators.
