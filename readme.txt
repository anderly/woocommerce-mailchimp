=== WooCommerce MailChimp ===
Contributors: anderly, saintsystems
Tags: woocommerce, mailchimp
Requires at least: 3.5.1
Tested up to: 4.4
Stable tag: 1.3.7
License: GPLv3

Simple and flexible MailChimp integration for WooCommerce.

== Description ==

WooCommerce MailChimp provides simple and flexible MailChimp integration for WooCommerce.

Automatically subscribe customers to a designated MailChimp list and, optionally, MailChimp interest groups upon order creation or order completion. This can be done quietly or based on the user's consent with several opt-in settings that support international opt-in laws.

### Features ###

#### WooCommerce Event Selection ####

- Subscribe customers to MailChimp after order creation
- Subscribe customers to MailChimp after order completion

#### Works with MailChimp Interest Groups ####

- Set one or more interest groups to add users to based on the selected MailChimp list.

#### Opt-In Settings ####

- MailChimp double opt-in support (control whether a double opt-in email is sent to the customer)
- Optionally, display an opt-in checkbox on the checkout page (this is required in some countries)
- Control the label displayed next to the opt-in checkbox
- Control whether or not the opt-in checkbox is checked or unchecked by default
- Control the placement of the opt-in checkbox on the checkout page (under billing info or order info)

#### Translation Support ####

- i18n ready with included woocommerce-mailchimp.pot file
- WPML support via wpml-config.xml

**Included Translations:**

- English (default)
- No other translations yet.

Thanks in advance for your help on any translation efforts!

#### Multisite ####

- All features should work for each blog in multisite installations.

### Feedback ###

Feedback is welcome!

If you need help, have problems, want to leave feedback or want to provide constructive criticism, please do so here at the [WooCommerce MailChimp plugin page](http://anderly.com/woocommerce-mailchimp/).

#### Twitter ####

[Follow @anderly on Twitter](http://twitter.com/anderly) for updates on this and other plugins.

== Installation ==

1. Upload or extract the `woocommerce-mailchimp` folder to your site's `/wp-content/plugins/` directory. You can also use the *Add new* option found in the *Plugins* menu in WordPress.  
2. Enable the plugin from the *Plugins* menu in WordPress.

= Usage =

1. Go to WooCommerce > Settings > Integration > MailChimp
2. First, enable the plugin and set your MailChimp API Key and hit save.
3. Select whether you want customers to be subscribed to your MailChimp list after order creation or order completion (there's a difference in WooCommerce).
4. Next, select your MailChimp list and set any interest group settings (optional) and hit save.
5. That's it, now customers who purchase products from your WooCommerce store will automatically be subscribed to the selected list (and optional interest groups) in MailChimp!

== Screenshots ==

1. WooCommerce MailChimp options screen.

== Changelog ==

= 1.3.7 =
* WordPress 4.4 Compatible
* WooCommerce 2.4.12 Compatible
* API response not shown in debug log
* Use only one instance of MCAPI

= 1.3.6 =
* Backout of change to use WC_Logger due to fatal error

= 1.3.5 =
* Fix for undefined variable list and array_merge issue.
* Change to use WC_Logger instead of error_log
* Updated pot file
* Added French translation
* General code syntax cleanup

= 1.3.4 =
* Fix enabled check. Issue #6.

= 1.3.3 =
* Fix for transient key length. Tested with WordPress 4.1

= 1.3.2 =
* Fix for headers already sent message. Tested with WordPress 4.0 and WooCommerce 2.2.*

= 1.3.1 =
* Fix for MailChimp merge vars bug introduced in v1.3

= 1.3 =
* Breaking Change: 'ss_wc_mailchimp_subscribe_merge_vars' action filter now passes $order_id param to enable retrieving additional order info/meta to use in MailChimp merge vars
* Small fix to order_created subscribe event to work with PayPal Payment Gateway

= 1.2.6 =
* Added additional debug logging when WP_DEBUG is enabled

= 1.2.5 =
* Bug fix for subscribe when not using opt-in display field

= 1.2.4 =
* Bug fix for subscribe

= 1.2.3 =
* Fixed plugin settings link for WooCommerce 2.1

= 1.2.2 =
* WooCommerce 2.1 fix for order custom fields

= 1.2.1 =
* WooCommerce 2.1 integration: Change to use wc_enqueue_js instead of add_inline_js
* WooCommerce 2.1 integration: Change to support new default checkout field filter for default opt-in checkbox status

= 1.2 =
* Added new setting to control whether or not the double opt-in checkbox is checked/unchecked by default on the checkout page.
* Added new setting to control display location of the double opt-in checkbox (under billing info or order info)
* Small modification to append to MailChimp interest groups for existing users so that group settings are not lost for users who were already subscribed.
* Preparations for i18n (Internationalization) support. Several users have already asked and offered to translate the plugin into other languages. We had always planned on that, but now are making that a reality.

= 1.1.3 =
* Minor action hook change since order meta (needed for MailChimp API call) is not yet available on 'woocommerce_new_order' hook

= 1.1.2 =
* Update to REALLY address issue with subscriptions not occurring on order create "pending"

= 1.1.1 =
* Update to address issue with subscriptions not occurring on order create "pending"

= 1.1 =
* Add the option to display an opt-in field on checkout

= 1.0.2 =
* Minor text and comment changes

= 1.0.1 =
* Added "Settings" link on the Plugins administration screen

= 1.0 =
* This is the first public release.
