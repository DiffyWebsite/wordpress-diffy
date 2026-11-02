# Diffy Visual Regression Testing for WordPress

WordPress plugin that screenshots your site before and after a plugin update and compares the two sets with [Diffy](https://diffy.website/wordpress/). Published on WordPress.org as [diffy](https://wordpress.org/plugins/diffy/).

## Installation

Install it from **Plugins → Add New** in WordPress, or download this repository into `wp-content/plugins/diffy`. The `vendor/` directory is committed, so no Composer step is needed. Then open **Diffy** in the admin menu and enter an API key and project ID, or use **One-click register**.

The plugin uses the Diffy API, which is included in Diffy's paid plans.

## How it works

- `wp_ajax_update-plugin` (priority 0, before core's handler and before maintenance mode): when the update was started with the "update now with Diffy visual testing" link, take the "before" screenshots and wait for them.
- `upgrader_process_complete`: take the "after" screenshots and start the comparison.
- The Plugins screen polls `diffy_first_screenshots` and `diffy_second_screenshots` over AJAX and shows the progress and the result.

## Releasing

1. Bump the version in `wordpress-diffy.php` (header and `DIFFY_PLUGIN_VERSION`), the `Stable tag` in `readme.txt`, and add a changelog entry.
2. Merge to `master`, then push a tag with the same version, without a `v`: `git tag 1.0.1 && git push origin 1.0.1`.
3. The "Deploy to WordPress.org" workflow publishes it to the plugin's SVN repository. It needs the `SVN_USERNAME` and `SVN_PASSWORD` secrets of a WordPress.org account that has commit access to the plugin.

Files listed in `.distignore` are not published. Banner, icon and screenshot images live in the SVN `assets` directory; to manage them from here, add a `.wordpress-org` directory with all of them (the workflow replaces the whole `assets` directory with its contents).
