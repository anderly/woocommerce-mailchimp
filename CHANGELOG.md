# Changelog

## [3.0.0](https://github.com/anderly/woocommerce-mailchimp/compare/2.5.1...v3.0.0) (2026-04-01)


### ⚠ BREAKING CHANGES

* Minimum requirements raised to PHP 7.4+, WordPress 6.2+, and WooCommerce 8.3+. Sites running older versions must upgrade before updating this plugin.

### Features

* add block-based checkout support ([76ad567](https://github.com/anderly/woocommerce-mailchimp/commit/76ad5675f4b03000cc9b52e185adde7ddbdb7e8e))


### Bug Fixes

* install SVN in CI for WordPress test suite setup ([d50358b](https://github.com/anderly/woocommerce-mailchimp/commit/d50358bf1ece345be5a926fe10197b21ba267b57))
* match existing readme.txt changelog format in release-please workflow ([215d3d5](https://github.com/anderly/woocommerce-mailchimp/commit/215d3d5be669e199cb4c4dde590d75a0ad458d5c))
* symlink WooCommerce for test bootstrap and add SVN to release workflow ([9451378](https://github.com/anderly/woocommerce-mailchimp/commit/945137824801de9a53dff59541ba46e18e364985))


### Miscellaneous Chores

* mark 3.0 release ([4b887e1](https://github.com/anderly/woocommerce-mailchimp/commit/4b887e1e884bca5e7b48bf153ddfde46cd47d6fd))

## [2.5.1](https://github.com/anderly/woocommerce-mailchimp/compare/v2.5.0...v2.5.1) (2025-10-27)

### Bug Fixes

* Fix plugin text domain hook.

## [2.5.0](https://github.com/anderly/woocommerce-mailchimp/compare/v2.4.17...v2.5.0) (2025-10-22)

### Features

* Add additional caching of MailChimp lists, interest groups, segments and categories.
* Add explicit cache clearing to settings page.
