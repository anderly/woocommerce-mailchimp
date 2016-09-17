=== WooCommerce MailChimp ===
Contributors: saintsystems, anderly
Donate link: http://ssms.us/hVdk
Tags: woocommerce, mailchimp
Requires at least: 3.5.1
Tested up to: 4.6.1
Stable tag: 2.0.8
License: GPLv3

Simple and flexible MailChimp integration for WooCommerce.

== Description ==

WooCommerce MailChimp provides simple and flexible MailChimp integration for WooCommerce.

Automatically subscribe customers to a designated MailChimp list and, optionally, MailChimp interest groups upon order creation or order completion. This can be done quietly or based on the user's consent with several opt-in settings that support international opt-in laws.

= Features =

**WooCommerce Event Selection**

- Subscribe customers to MailChimp after order creation
- Subscribe customers to MailChimp after order processing
- Subscribe customers to MailChimp after order completion

**Works with MailChimp Interest Groups**

- Set one or more interest groups to add users to based on the selected MailChimp list.

**Opt-In Settings**

- MailChimp double opt-in support (control whether a double opt-in email is sent to the customer)
- Optionally, display an opt-in checkbox on the checkout page (this is required in some countries)
- Control the label displayed next to the opt-in checkbox
- Control whether or not the opt-in checkbox is checked or unchecked by default
- Control the placement of the opt-in checkbox on the checkout page

= Translation Support =

- i18n ready with included woocommerce-mailchimp.pot file
- WPML support via wpml-config.xml

**Included Translations:**

- English (default)
- French.

**Custom Translations**
- Place custom translations in `/wp-content/languages/woocommerce-mailchimp/woocommerce-mailchim_{locale}.[mo|po]`. This ensures they won't get overwritten by plugin updates.
- If no custom translations are present, the plugin will load them from `/wp-content/plugins/woocommerce-mailchimp/languages/woocommerce-mailchimp_{locale}.[mo|po]`

Thanks in advance for your help on any translation efforts!

= Multisite =

- All features should work for each blog in multisite installations.

= Requirements =

WooCommerce MailChimp requires PHP 5.4+ (PHP 7.0+ recommended). You'll also need to be running WordPress 3.5.1+ and have WooCommerce 2.2+.

= Documentation & Support =

Online documentation and code samples are available via our [Help Center](https://support.saintsystems.com/hc/en-us/sections/201959566).

Please visit the
[WooCommerce MailChimp support forum on WordPress.org](https://wordpress.org/support/plugin/woocommerce-mailchimp) for basic support and help from other users. Since this is a free plugin, we respond to these as we have time.

Dedicated support will be available with the upcoming [WooCommerce MailChimp Pro](https://www.saintsystems.com/products/woocommerce-mailchimp-pro/).

= Contribute =
All development for WooCommerce MailChimp is [handled via GitHub](https://github.com/anderly/woocommerce-mailchimp). Opening new issues and submitting pull requests are welcome.

[Our public roadmap is available on Trello](https://trello.com/b/VWBdLVuI/woocommerce-mailchimp-development). We'd love it if you vote and comment on your favorite ideas.

You can also keep up to date with [WooCommerce MailChimp Pro](https://www.saintsystems.com/products/woocommerce-mailchimp-pro/) development by [subscribing to our newsletter](http://eepurl.com/bxcewL).

Also, if you enjoy using the software [we'd love it if you could give us a review](https://wordpress.org/support/plugin/woocommerce-mailchimp/reviews/)!

== Installation ==

1. Upload or extract the `woocommerce-mailchimp` folder to your site's `/wp-content/plugins/` directory. You can also use the *Add new- option found in the *Plugins- menu in WordPress.  
2. Enable the plugin from the *Plugins- menu in WordPress.

= Usage =

1. Go to WooCommerce > Settings > MailChimp
2. First, paste your MailChimp API Key to get started.
3. Select whether you want customers to be subscribed to your MailChimp list after order creation, order processing or order completion (there's a difference in WooCommerce).
4. Next, select your MailChimp list and select any interest groups (optional).
5. Select your opt-in, double opt-in settings and hit `Save changes`.
6. That's it, your WooCommerce store is now integrated with MailChimp!

== Screenshots ==

1. WooCommerce MailChimp general settings screen.
2. WooCommerce MailChimp troubleshooting screen.

== Changelog ==

#### 2.0 - 2.0.8 - September 17, 2016

**WARNING:** This release contains breaking changes to the plugins action hooks and filters. If you have custom code that hooks into the plugins action hooks and filters, please review the breaking changes below to know how to update your code appropriately.

**NOTE:** You must upgrade to version 2.X by December 31, 2016 as prior versions of the MailChimp API will stop working at that point.

**Breaking Changes**

- Action filter `ss_wc_mailchimp_subscribe_merge_vars` is now `ss_wc_mailchimp_subscribe_merge_tags`
    - The filter no longer contains the `GROUPINGS` sub-key for [MailChimp Groups](http://kb.mailchimp.com/groups) due to a change with the MailChimp API v3.0.
    - The filter now only contains the [MailChimp Merge Tags](http://kb.mailchimp.com/merge-tags).
- [MailChimp Groups](http://kb.mailchimp.com/groups) now have their own action filter `ss_wc_mailchimp_subscribe_interest_groups`
- Action filter `ss_wc_mailchimp_subscribe_options` has changed due to changes with the MailChimp API v3.0.
    - The key `listid` has been changed to `list_id` in the `$subscribe_options` parameter
    - The `vars` key has been removed from the `$subscribe_options` parameter (this key previously contained the merge tags and groups together).
    - The `update_existing`, `replace_interests` and `send_welcome` keys have been removed from the `$subscribe_options` parameter since they are no longer supported by the API.
    - The plugin now always updates existing subscribers if they exist.
    - The plugin now always appends interest groups and does not affect existing groups on subscribers.
    - The send welcome email can be configured on the target list and is not required to be sent through the API.
    - A new key `merge_tags` has been added and includes the `$merge_tags` array to be sent to the MailChimp API
    - A new key `interest_groups` has been added and includes the `$interest_groups` array to be sent to the MailChimp API


**Improvements**

- Added ability to pick MailChimp Interest Groups from drop-down list.
- Upgraded MailChimp API to v3.0

**Additions**

- Added `Debug Log` setting to enable/disable logging for troubleshooting purposes.
- Added `System Info` setting for troubleshooting purposes.
- New action hook `ss_wc_mailchimp_loaded` fired after the plugin has loaded.
- New action hook `ss_wc_mailchimp_before_opt_in_checkbox` fired before opt in checkbox is rendered.
- New action filter `ss_wc_mailchimp_opt_in_checkbox` allows for overriding opt in checkbox rendering
- New action hook `ss_wc_mailchimp_after_opt_in_checkbox` fired after opt in checkbox is rendered.

**Fixes**

- Fixed issues with translations and text domains not loading properly.
- Change `list` function to `get_list` to prevent PHP error
- Fix issues with PHP version < 5.5

#### 1.3.9 - September 13, 2016

**Fixes**

- Rename MCAPI class to prevent collisions with other plugins.

#### 1.3.8 - September 9, 2016

**Improvements**

- Tested up to WordPress 4.6.1
- Tested up to WooCommerce 2.6.4
- More flexible opt_in checkbox placement
- Pass $order_id to `ss_wc_mailchimp_subscribe_options` hook

**Fixes**

- Use only one instance of MCAPI
- Fixed Issue #14 MCAPI constructor style
- Fixed Issue #15 `mailchimp_api_error_msg`
- Fixed Issue #16 where lists wouldn't show up until you saved the settings twice

#### 1.3.7 - December 16, 2015

**Improvements**

- WordPress 4.4 Compatible
- WooCommerce 2.4.12 Compatible

**Fixes**

- API response not shown in debug log
- Use only one instance of MCAPI

#### 1.3.6 - February 9, 2015

**Fixes**

- Backout of change to use WC_Logger due to fatal error

#### 1.3.5 - February 6, 2015

**Improvements**

- Change to use WC_Logger instead of error_log
- Updated pot file
- Added French translation
- General code syntax cleanup

**Fixes**

- Fix for undefined variable list and array_merge issue.

#### 1.3.3 & 1.3.4 - January 16, 2016

**Fixes**

- Fix enabled check. Issue #6.
- Fix for transient key length.

**Improvements**

- Tested with WordPress 4.1

#### 1.3.2 - November 4, 2014

**Fixes*

- Fix for headers already sent message. Tested with WordPress 4.0 and WooCommerce 2.2.*

#### 1.3.1 - April 3, 2014

**Fixes**

- Fix for MailChimp merge vars bug introduced in v1.3

#### 1.3 - April 1, 2014

**Breaking Changes**

- Action filer `ss_wc_mailchimp_subscribe_merge_vars` now passes $order_id param to enable retrieving additional order info/meta to use in MailChimp merge vars

**Fixes**

- Small fix to order_created subscribe event to work with PayPal Payment Gateway

#### 1.2.6 - February 28, 2014

**Improvements**

- Added additional debug logging when WP_DEBUG is enabled

#### 1.2.5 - February 27, 2014

**Fixes**

- Bug fix for subscribe when not using opt-in display field

#### 1.2.2, 1.2.3 & 1.2.4 - February 22, 2014

**Fixes**

- WooCommerce 2.1 fix for order custom fields
- Fixed plugin settings link for WooCommerce 2.1
- Bug fix for subscribe

#### 1.2.1 - February 13, 2014

**Improvements**

- WooCommerce 2.1 integration: Change to use wc_enqueue_js instead of add_inline_js
- WooCommerce 2.1 integration: Change to support new default checkout field filter for default opt-in checkbox status

#### 1.2 - January 10, 2014

**Improvements**

- Added new setting to control whether or not the double opt-in checkbox is checked/unchecked by default on the checkout page.
- Added new setting to control display location of the double opt-in checkbox (under billing info or order info)
- Small modification to append to MailChimp interest groups for existing users so that group settings are not lost for users who were already subscribed.
- Preparations for i18n (Internationalization) support. Several users have already asked and offered to translate the plugin into other languages. We had always planned on that, but now are making that a reality.

### 1.1.2 & 1.1.3 - January 2, 2014

**Fixes**

- Update to REALLY address issue with subscriptions not occurring on order create "pending"

**Additions**

- Minor action hook change since order meta (needed for MailChimp API call) is not yet available on 'woocommerce_new_order' hook

###  - January 2, 2014

### 1.1.1 - December 31, 2013

**Fixes**

- Update to address issue with subscriptions not occurring on order create "pending"

### 1.1 - November 12, 2013

**Improvements**

- Add the option to display an opt-in field on checkout

### 1.0.2 - October 16, 2013

**Additions**

- Minor text and comment changes

### 1.0.1 - October 11, 2013

**Improvements**

- Added "Settings" link on the Plugins administration screen

### 1.0 - October 10, 2013

- This is the first public release.
