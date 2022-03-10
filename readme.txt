=== WP WooCommerce Mailchimp ===
Contributors: saintsystems, anderly
Donate link: http://ssms.us/hVdk
Tags: woocommerce, mailchimp, ecommerce, email
Requires at least: 4.7.0
Tested up to: 5.9.1
WC tested up to: 6.3.0
Requires PHP: 5.6
Stable tag: 2.4.9
Version: 2.4.9
License: GPLv3

Simple and flexible Mailchimp integration for WooCommerce.

== Description ==

WP WooCommerce Mailchimp provides simple and flexible Mailchimp integration for WooCommerce.

Automatically subscribe customers to a designated Mailchimp list and, optionally, Mailchimp interest groups and tags upon order creation or order completion.
This can be done quietly or based on the user's consent with several opt-in settings that support international opt-in laws.

Upgrade to [WP WooCommerce Mailchimp Pro](https://www.saintsystems.com/products/woocommerce-mailchimp-pro/) for more advanced features including WooCommerce Subscriptions support and the ability to set product-specific lists, groups and tags.

= Features =

**WooCommerce Event Selection**

- Subscribe customers to Mailchimp after order creation
- Subscribe customers to Mailchimp after order processing
- Subscribe customers to Mailchimp after order completion

**Works with Mailchimp Interest Groups and Tags**

- Set one or more interest groups to add users to based on the selected Mailchimp list.
- Set one or more tags to add users to based on the selected Mailchimp list.

**Opt-In Settings**

- MailChimp double opt-in support (control whether a double opt-in email is sent to the customer)
- Optionally, display an opt-in checkbox on the checkout page (this is required in some countries)
- Control the label displayed next to the opt-in checkbox
- Control whether or not the opt-in checkbox is checked or unchecked by default
- Control the placement of the opt-in checkbox on the checkout page

= Translation Support =

Would you like to help translate the plugin into more languages? Join our Translations Community at https://translate.wordpress.org/projects/wp-plugins/woocommerce-mailchimp.

WooCommerce Mailchimp translation is managed through WordPress language packs here: https://translate.wordpress.org. This allows WooCommerce Mailchimp to be translated into other languages. The preferred tool for translating plugins is called [GlotPress](https://make.wordpress.org/polyglots/handbook/tools/glotpress-translate-wordpress-org/). You can [read about how GlotPress works in the WordPress Translator's Handbook](https://make.wordpress.org/polyglots/handbook/tools/glotpress-translate-wordpress-org/).

Thanks in advance for your help on any translation efforts!

We also support bundled translations via:

- Included woocommerce-mailchimp.pot file
- WPML support via wpml-config.xml

**Included Translations:**

- English (US) (default)
- French.

**Custom Translations**

If you don't want to use WordPress language packs or bundled translations, you can use your own custom translations.

- Place custom translations in `/wp-content/languages/woocommerce-mailchimp/woocommerce-mailchimp_{lang}_{country}.mo`. This ensures they won't get overwritten by plugin updates.

**Translation Loading**

If no custom translations are present, languages will be loaded in the following order:

- From WordPress language packs in: `/wp-content/languages/plugins/woocommerce-mailchimp/woocommerce-mailchimp_{lang}_{country}.mo`
- From the plugin bundled in: `/wp-content/plugins/woocommerce-mailchimp/languages/woocommerce-mailchimp_{lang}_{country}.mo`

= Multisite =

- All features should work for each blog in multisite installations.

= Requirements =

WP WooCommerce Mailchimp requires PHP 5.6+ (PHP 7.0+ recommended). You'll also need to be running WordPress 4.7.0+ and have WooCommerce 3.5.0+.

= Documentation & Support =

Online documentation and code samples are available via our [Help Center](https://support.saintsystems.com/hc/en-us/sections/201959566).

Please visit the
[WP WooCommerce MailChimp support forum on WordPress.org](https://wordpress.org/support/plugin/woocommerce-mailchimp) for basic support and help from other users. Since this is a free plugin, we respond to these as we have time.

Dedicated support will is available for [WP WooCommerce MailChimp Pro](https://www.saintsystems.com/products/woocommerce-mailchimp-pro/) customers.

= Contribute =
All development for WP WooCommerce MailChimp is [handled via GitHub](https://github.com/anderly/woocommerce-mailchimp). Opening new issues and submitting pull requests are welcome.

[Our public roadmap is available on Trello](https://trello.com/b/VWBdLVuI/woocommerce-mailchimp-development). We'd love it if you vote and comment on your favorite ideas.

You can also keep up to date with [WooCommerce MailChimp Pro](https://www.saintsystems.com/products/woocommerce-mailchimp-pro/) development by [subscribing to our newsletter](http://eepurl.com/bxcewL).

Also, if you enjoy using the software [we'd love it if you could give us a review](https://wordpress.org/support/plugin/woocommerce-mailchimp/reviews/?rate=5#new-post)!

== Installation ==

1. Upload or extract the `woocommerce-mailchimp` folder to your site's `/wp-content/plugins/` directory. You can also use the *Add new- option found in the *Plugins- menu in WordPress.
2. Enable the plugin from the *Plugins- menu in WordPress.

= Usage =

1. Go to WooCommerce > Settings > Mailchimp
2. First, paste your MailChimp API Key to get started.
3. Select whether you want customers to be subscribed to your Mailchimp list after order creation, order processing or order completion (there's a difference in WooCommerce).
4. Next, select your Mailchimp list and select any interest groups (optional).
5. Select your opt-in, double opt-in settings and hit `Save changes`.
6. That's it, your WooCommerce store is now integrated with Mailchimp!

== Screenshots ==

1. WooCommerce Mailchimp general settings screen.
2. WooCommerce Mailchimp troubleshooting screen.

== Changelog ==

#### 2.4.9 - Mar 10, 2022
- Bug fix for to check for WP_Error.

#### 2.4.8 - Oct 12, 2021
- Bug fix for tags not passing to Mailchimp.

#### 2.4.7 - Oct 6, 2021
- Update to pass skip_merge_validation argument when subscribing users.

#### 2.4.6 - Oct 5, 2021
- Enhance error logging.

#### 2.4.5 - Oct 5, 2021
- Only send merge fields if not empty.
- Bump WC tested version up to 5.7.1.
- Bump WP tested version up to 5.8.1.

#### 2.4.4 - May 26, 2021
- Fix typo.

#### 2.4.3 - May 26, 2021
- Replace extract with explicit retrieval.

#### 2.4.2 - May 24, 2021
- Tested up to WordPress 5.7.2
- Tested up to WooCommerce 5.3.0

#### 2.4.1 - March 27, 2021
- Added new `ss_wc_mailchimp_subscribe_customer` hook to allow easier override of whether or not to subscribe the customer.

#### 2.4.0 - March 24, 2021
- Tested up to WordPress 5.7
- Tested up to WooCommerce 5.1

#### 2.3.13 - January 5, 2021
- Bump WP and WC tested versions.
- Fix for category interests not returning (issue #29)

#### 2.3.12 - April 14, 2020
- Remove explicit private variable to fix integration issue with Aero Checkout.

#### 2.3.11 - April 8, 2020
- Tested up to WordPress 5.4
- Tested up to WooCommerce 4.0.1

#### 2.3.10 - March 3, 2020
- Increase API return limit count to 500.
- Tested up to WooCommerce 3.9.2

#### 2.3.9 - February 1, 2020
- Fix for migration version detection.

#### 2.3.8 - February 1, 2020
- Fix for array_map (Issue #26)
- Tested up to WordPress 5.3.2 (Issue #28)
- Tested up to WooCommerce 3.9.1 (Issue #27)

#### 2.3.7 - October 31, 2019
- Fix for opt-in checkbox not displaying.

#### 2.3.6 - October 30, 2019
- Fix json encode.

#### 2.3.5 - October 30, 2019
- WordPress coding standards updates.

#### 2.3.4 - October 29, 2019
- Change plugin display name.
- Sanitize post data.

#### 2.3.3 - August 29, 2019
- Fix ActionScheduler 2.2.5 version.

#### 2.3.2 - August 27, 2019
- Upgrade ActionScheduler to 2.2.5
- Fix encoding of unicode/international characters.

#### 2.3.1 - August 26, 2019
- Fix tag retrieval to return tag IDs.
- Fix migrations.
- Tested up to WooCommerce 3.7.0

#### 2.3.0 - July 25, 2019
- Added support for tags on main list.
- Improved integration with WooCommerce Mailchimp Pro.

#### 2.2.0 - July 23, 2019
- Bump min WC version to 3.5.0, and min WP version to 4.7.0.
- Bump min PHP version to 5.6 in prep for WooCoommerce 3.7 requirements.

#### 2.1.21 - July 23, 2019
- Prep for getting list tags.

#### 2.1.20 - July 22, 2019
- Bump plugin version and min WP and WC tested versions.

#### 2.1.19 - February 15, 2019
- Update styling of opt-in checkbox.

#### 2.1.18 - February 15, 2019
- Add subscribe_customer param to before/after subscribe hooks.
- Use new logging action for logging.
- Update tested to WP 5.1

#### 2.1.17 - February 14, 2019
- Allow html (hyperlinks, etc.) in Opt-In Field Label.

#### 2.1.16 - February 5, 2019
- Force plugin version update. Wordpress.org not registering the 2.1.15 release.

#### 2.1.15 - February 5, 2019
- Fix fatal error.

#### 2.1.14 - February 5, 2019
- Fix MailChimp interest group limit

#### 2.1.13 - January 29, 2019
- WooCommerce tested up to 3.5.4
- WP tested up to 5.0.3

#### 2.1.12 - May 22, 2018
- WPML Opt-in label fix.
- Plugin now loads after `plugins_loaded` action hook.

#### 2.1.11 - April 27, 2018
- Fix erroneous error saying can't load lists when no interest groups for list.

#### 2.1.10 - February 12, 2018
- Added WooCommerce tested up to header

#### 2.1.9 - February 1, 2018
- Added new opt-in checkbox location for order review above cart/product table.

#### 2.1.8 - February 1, 2018
- Fix "Invalid argument supplied for foreach" warning in settings (Issue #21)

#### 2.1.6 - January 26, 2018
- Restore opt-in checkbox location and add params to filter to allow easier adjustment of its placement.

#### 2.1.5 - January 25, 2018
- Move opt-in checkbox before associated label

#### 2.1.4 - December 20, 2017
- Tested up to WordPres 4.9.1

#### 2.1.3 - December 19, 2017
- Small fix to show MailChimp API error message in UI.

#### 2.1.2 - August 25, 2017
- Fix retrieval of mailchimp lists with numeric ids.

#### 2.1.1 - July 24, 2017
- Fix settings default for older PHP versions.

#### 2.1.0 - April 26, 2017
- Added support for WooCommerce 3.0 changes.
- Added additional options for opt-in checkbox placement.

#### 2.0.20 - February 1, 2017

- Added `woocommerce_mailchimp_admin_email` filter to allow hooking into and overriding email where error messages are sent (defaults to `get_option( 'admin_email' )` ).

**IMPORTANT:** You must upgrade to version 2.X by December 31, 2016 as prior versions of the MailChimp API will stop working at that point.

#### 2.0.19 - October 4, 2016

- Fix to not send double opt-in email to existing subscribers.

#### 2.0.18 - September 23, 2016

- Fix for array_filter not working with lamda for some users.

#### 2.0.15 - 2.0.17 - September 22, 2016

- Fix for activation error running migrations.
- Double opt-in fix.
- Added additional hooks and filters.
- Code cleanup.

#### 2.0.13 - 2.0.14 - September 20, 2016

- Added plugin compatibility checks for minimum supported versions of WooCommerce, WordPress and PHP.
- Added explicit plugin directory to require_once calls.

#### 2.0.9 - 2.0.12 - September 19, 2016

- Small fix for double-loading of lists on api key change.
- Small fix for lists not loading after initial save on new installs.
- Small fix for new installs not loading interest groups.
- Removed functions.php file (no longer used).

#### 2.0.7 - 2.0.8 - September 17, 2016

- Fix for new installs to prevent trying to run upgrade process.
- Small fix to not end WooCommerce Settings section with no api key or list is present.

#### 2.0 - 2.0.6 - September 16, 2016

**WARNING:** This release contains breaking changes to the plugin's action hooks and filters. If you have custom code that hooks into the plugins action hooks and filters, please review the breaking changes below to know how to update your code appropriately.

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
