=== Flamingo ===
Contributors: takayukister, megumithemes, itpixelz
Tags: bird, contact, mail, crm
Requires at least: 6.1
Tested up to: 6.0
Stable tag: 2.2.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://contactform7.com/donate/

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

= 2.4 =

* Bumps up the minimum required WordPress version to 6.1.
* Bumps up the minimum required PHP version to 7.4.
* Removes unused Outbound Messages codes.
* Introduces `Flamingo_CSV` classes for fully customizable CSV generation.
* New filter hook: `flamingo_contact_csv_class`
* New filter hook: `flamingo_inbound_csv_class`
* Uses `admin_init` for cron job scheduling.

= 2.3 =

* Sets status to previous when restoring data.
