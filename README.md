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

Releases are published by hand to the plugin's SVN repository on WordPress.org. You need a WordPress.org account that is a committer on the plugin, with two-factor authentication on and an SVN password (profiles.wordpress.org → Edit profile → Account & Security → SVN credentials → Generate Password), and `svn` installed (`brew install subversion` on macOS).

1. Bump the version in `wordpress-diffy.php` (header and `DIFFY_PLUGIN_VERSION`), the `Stable tag` in `readme.txt`, and add a changelog entry. Merge to `master`.
2. Check out the SVN repository next to this one (only `trunk` and the empty `tags` directory):

   ```bash
   svn checkout --depth immediates https://plugins.svn.wordpress.org/diffy diffy-svn
   cd diffy-svn
   svn update --set-depth infinity trunk
   ```

3. Copy the plugin into `trunk`, leaving out the files listed in `.distignore`, and tell SVN about added and removed files:

   ```bash
   rsync -a --delete --exclude-from=../wordpress-diffy/.distignore ../wordpress-diffy/ trunk/
   svn add --force trunk
   svn status trunk | grep '^!' | sed 's/^! *//' | xargs -I% svn rm %
   ```

4. Tag the version (the same number as `Stable tag`, without a `v`), check the changes and commit:

   ```bash
   svn cp trunk tags/1.0.1
   svn status
   svn commit -m "Release 1.0.1" --username YOUR_WORDPRESS_ORG_USERNAME
   ```

5. Tag the release in git too: `git tag 1.0.1 && git push origin 1.0.1`.

Banner, icon and screenshot images live in the SVN `assets` directory, not in this repository.
