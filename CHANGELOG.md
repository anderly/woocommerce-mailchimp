# Changelog

## [3.2.0](https://github.com/anderly/woocommerce-mailchimp/compare/v3.1.0...v3.2.0) (2026-04-02)


### Features

* add Upgrade to Pro upsell on settings page and style plugin row link ([d02e046](https://github.com/anderly/woocommerce-mailchimp/commit/d02e0467fdab32de54e99f1c1a1e887a76677b0b))

## [3.1.0](https://github.com/anderly/woocommerce-mailchimp/compare/v3.0.5...v3.1.0) (2026-04-02)


### Features

* remove bundled Action Scheduler in favor of WooCommerce built-in ([dd6c9df](https://github.com/anderly/woocommerce-mailchimp/commit/dd6c9df89ed55d7ab7833af2fe5944fe975aa503))


### Bug Fixes

* remove bundled Action Scheduler — use WooCommerce's built-in copy ([abe2d20](https://github.com/anderly/woocommerce-mailchimp/commit/abe2d20f6b51de24f9c164ae1c77774b0b9f277d))
* update readme with block checkout, pro features, and requirements ([9471dd5](https://github.com/anderly/woocommerce-mailchimp/commit/9471dd5c747e26fd875923ee5f08410acde036ac))
* use --ignore-platform-reqs for composer install in CI ([0cc96f4](https://github.com/anderly/woocommerce-mailchimp/commit/0cc96f4744f8a1beffd0ef123cc8496cba50b52f))

## [3.0.5](https://github.com/anderly/woocommerce-mailchimp/compare/v3.0.4...v3.0.5) (2026-04-01)


### Bug Fixes

* bump WP tested up to 6.9.4 ([a802b86](https://github.com/anderly/woocommerce-mailchimp/commit/a802b863905b7fd5bfbdcf7ec8bca68d4b84841f))

## [3.0.4](https://github.com/anderly/woocommerce-mailchimp/compare/v3.0.3...v3.0.4) (2026-04-01)


### Bug Fixes

* rewrite SVN deploy to match proven release.sh approach ([8c3d090](https://github.com/anderly/woocommerce-mailchimp/commit/8c3d0909aabf91c8fe85f818f1b0d1c8f46b09c1))

## [3.0.3](https://github.com/anderly/woocommerce-mailchimp/compare/v3.0.2...v3.0.3) (2026-04-01)


### Bug Fixes

* remove release-please markers from readme.txt Stable tag ([180a048](https://github.com/anderly/woocommerce-mailchimp/commit/180a048d9d8bfc629cf699638aef7f0781c4b3db))

## [3.0.2](https://github.com/anderly/woocommerce-mailchimp/compare/v3.0.1...v3.0.2) (2026-04-01)


### Bug Fixes

* use block annotation for version marker in plugin header ([248434b](https://github.com/anderly/woocommerce-mailchimp/commit/248434b2977dce1db62a45c1648e10a16038b85d))

## [3.0.1](https://github.com/anderly/woocommerce-mailchimp/compare/v3.0.0...v3.0.1) (2026-04-01)


### Bug Fixes

* use full SVN trunk checkout in release workflow ([1064216](https://github.com/anderly/woocommerce-mailchimp/commit/1064216d50f94b66d0ff2c558f65a1ef82a83ac7))

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
