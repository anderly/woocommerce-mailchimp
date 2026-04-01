# WP WooCommerce Mailchimp — Free Plugin

## Overview

Core Mailchimp integration for WooCommerce. Connects a store to a single Mailchimp audience list with opt-in checkbox support for both classic and block-based checkout.

- **Repo:** `anderly/woocommerce-mailchimp` on GitHub
- **WordPress.org:** https://wordpress.org/plugins/woocommerce-mailchimp/
- **Minimum Requirements:** PHP 7.4+, WordPress 6.2+, WooCommerce 8.3+

## Architecture

- Entry: `woocommerce-mailchimp.php` → `SS_WC_MailChimp_Plugin` (singleton, accessed via `SSWCMC()`)
- Loads at priority 11 on `plugins_loaded`
- All classes in `includes/`

### Key Classes

| Class | Purpose |
|---|---|
| `SS_WC_MailChimp_Plugin` | Singleton. Settings, constants, hooks. `get_subscribe_options_for_order()` builds base merge_tags (FNAME/LNAME). |
| `SS_WC_MailChimp_Handler` | Checkout lifecycle. Renders opt-in checkbox, saves opt-in meta, queues subscriptions via Action Scheduler. Registers Store API endpoint data for block checkout. |
| `SS_WC_MailChimp` | Mailchimp API wrapper. `subscribe()`, `unsubscribe()`, `get_lists()`, `get_tags()`, `get_interest_categories_with_interests()`, `get_merge_fields()`. Uses transient caching. |
| `SS_WC_MailChimp_Blocks_Integration` | `IntegrationInterface` implementation. Registers `checkout.js` for block-based checkout. |
| `SS_WC_Settings_MailChimp` | WooCommerce settings tab. Fires positional filters (`ss_wc_mailchimp_settings_general_after_*`) for pro plugin to inject settings. |

### Block Checkout

- `assets/js/blocks/checkout.js` — IIFE, no build step. Uses `getSetting('woocommerce-mailchimp_data')` for settings, `ExperimentalOrderMeta` slot, native `wc-block-components-checkbox` markup.
- `checkout.asset.php` — declares dependencies (`wp-element`, `wp-i18n`, `wp-plugins`, `wc-blocks-checkout`, `wc-settings`). Version uses `SS_WC_MAILCHIMP_VERSION` constant.
- Store API: `woocommerce_store_api_register_endpoint_data` registers `woocommerce-mailchimp` namespace with `ss_wc_mailchimp_opt_in` boolean field.
- Save: `woocommerce_store_api_checkout_update_order_from_request` reads from `$request->get_param('extensions')`.
- **Important:** Uses `interface_exists()` not `class_exists()` to check for `IntegrationInterface` (it's a PHP interface, not a class).

### Subscribe Flow

1. `order_status_changed()` → queues `queue_ss_wc_mailchimp_maybe_subscribe` via Action Scheduler
2. `maybe_subscribe()` → reads opt-in meta, builds subscribe options, fires filters:
   - `ss_wc_mailchimp_subscribe_interest_groups`
   - `ss_wc_mailchimp_subscribe_tags`
   - `ss_wc_mailchimp_subscribe_merge_tags` ← pro plugin hooks here for merge field mapping
   - `ss_wc_mailchimp_subscribe_options`
3. Calls `SS_WC_MailChimp::subscribe()` → Mailchimp API (PUT member + POST tags)
4. Fires `ss_wc_mailchimp_after_subscribe` ← pro plugin hooks here for per-product lists

### Settings Storage

Individual `wp_options` rows with `ss_wc_mailchimp_` prefix. Config defaults in `config/default-settings.php`.

## Release

Automated via release-please. Conventional commits (`feat:`, `fix:`, `BREAKING CHANGE`).
- Workflow: `.github/workflows/release-please.yml`
- Deploys to GitHub Releases + WordPress.org SVN
- Version markers: PHP header uses `x-release-please-start-version`/`x-release-please-end` block annotation. `readme.txt` Stable tag/Version updated via `sed` in the workflow (no inline markers — WordPress.org reads them literally).

## Testing

```bash
WP_TESTS_PHPUNIT_POLYFILLS_PATH=vendor/yoast/phpunit-polyfills \
WP_TESTS_DIR=/tmp/wordpress-tests-lib \
vendor/bin/phpunit
```

Tests in `tests/`. Bootstrap loads WooCommerce + this plugin. Uses `WP_REST_Request` objects for block checkout tests (not `$_POST`).

## Action Scheduler

Uses WooCommerce's built-in Action Scheduler (no bundled copy). All Mailchimp API calls are async via `as_schedule_single_action()`.
