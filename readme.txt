=== Diffy Visual Regression Testing ===
Contributors: ygerasimov
Tags: visual regression testing, screenshots, plugin updates, testing, comparison
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Screenshot your site before and after every plugin update and see exactly what changed. Visual regression testing by Diffy.

== Description ==

Most plugin updates are fine. Now and then one breaks something you can see but nobody notices: a header that wraps on tablets, a button pushed below the fold, a form that loses its styling on one template. Nothing is logged and nothing goes red in a dashboard.

This plugin adds a visual check to your plugin updates. On the Plugins screen, next to each available update, you get an extra link: **update now with Diffy visual testing**. When you use it:

1. [Diffy](https://diffy.website/wordpress/) takes screenshots of the pages in your Diffy project, before the update.
2. WordPress runs the plugin update.
3. Diffy takes a second set of screenshots and compares the two.
4. You see the result on the Plugins screen, with a link to the report, and Diffy emails it to you.

Ideally the comparison finds no changes. When it does, the report highlights exactly what moved, page by page and screen width by screen width, so you can fix it or roll back before a visitor finds it.

= What you need =

* A Diffy account and a project for this site. The plugin can create both for you with one click.
* A Diffy plan that includes API access. The plugin talks to Diffy through its API, which is included in Diffy's [paid plans](https://diffy.website/pricing/).
* A site that can be reached from the internet. Diffy takes the screenshots from its own servers, so local development sites won't work.

= Why Diffy =

* **Every page and every width.** Diffy screenshots each page in your project at the widths you choose, from 240px to 2000px, in Chrome or WebKit (the engine behind Safari).
* **Works with any theme and page builder.** Diffy screenshots the rendered page, so Elementor, Divi, the block editor and custom themes all work.
* **Stable screenshots.** Hide cookie banners, freeze sliders with a short script, mask videos and replace changing text before screenshots are taken, so the comparison only shows real changes.
* **A report you can share.** Send the comparison to a client or a developer who doesn't have a Diffy account.

Diffy also compares staging with production, runs on a schedule, and integrates with CI. Read more about [visual regression testing for WordPress](https://diffy.website/wordpress/).

== Installation ==

1. Install and activate the plugin from **Plugins → Add New**, or upload the plugin folder to `/wp-content/plugins/`.
2. Open **Diffy** in the admin menu.
3. Click **One-click register** to create a Diffy account and a project for this site with your admin email address, or enter the API key and project ID of an existing Diffy project.
4. Click **Save Changes**. The plugin checks the API key and the project ID with Diffy.
5. When an update is available on the **Plugins** screen, click **update now with Diffy visual testing** instead of **update now**.

== Frequently Asked Questions ==

= Do I need a Diffy account? =

Yes. The plugin can create one for you with one click from its settings page, using your admin email address and a random password. You can also sign up at [diffy.website](https://diffy.website/).

= Which Diffy plan do I need? =

A plan with API access, because the plugin creates screenshots and comparisons through the Diffy API. API access is included in Diffy's paid plans. See [pricing](https://diffy.website/pricing/).

= Which pages are tested? =

The pages in your Diffy project. Pick one or two examples of each template (homepage, a page, a post, an archive, a product, the cart, a form) rather than every page, and you will cover most of what an update can break.

= Does it make updates slower? =

Yes. The update starts only after Diffy has finished the first set of screenshots, and how long that takes depends on the number of pages and screen widths in your project. Very long waits can hit your server's request time limits, so keep the project to your key pages.

= Does it work on a local development site? =

No. Diffy takes screenshots from its own servers, so the site has to be reachable from the internet. Use the plugin on a staging copy or on the live site.

= My firewall or security plugin blocks Diffy =

Allow Diffy's IP address in your firewall, security plugin or CDN. You can find it in your Diffy project settings.

= Does it work with Elementor, Divi and other page builders? =

Yes. Diffy screenshots the rendered page, so the tool that built it doesn't matter. Sliders and animations should be frozen before screenshots are taken; Diffy's project settings let you run a short JavaScript snippet for that.

== External services ==

This plugin connects to the Diffy API (https://app.diffy.website/api/) to take screenshots of your site and compare them. Diffy is a service run by the plugin's authors.

* When you save the settings, the plugin sends the API key and the project ID to Diffy to check that they are valid.
* When you update a plugin with "update now with Diffy visual testing", the plugin sends the API key and asks Diffy to take screenshots of the pages in your project and compare them. Diffy then loads those pages from its own servers.
* When you click "One-click register", the plugin sends your admin email address, a name made from your display name and email address, a generated password and your site's address to create a Diffy account, a project and an API key.

No data is sent to Diffy unless you use one of these features. See Diffy's [terms](https://diffy.website/user-agreement/) and [privacy policy](https://diffy.website/privacy-policy/).

== Screenshots ==

1. Diffy takes screenshots before the plugin update is applied.
2. After the update, Diffy takes a second set of screenshots and compares them with the "before" set.
3. Diffy found changes, and the report is one click away.
4. The report's thumbnail view.
5. Reviewing a single difference in full view.

== Changelog ==

= 1.0.0 =
* Tested with WordPress 7.1 and PHP 8.3.
* Security: nonce and capability checks on all AJAX requests, and sanitized settings.
* Errors from Diffy (for example, a plan without API access) are now shown on the Plugins screen instead of failing silently.
* The "update now with Diffy visual testing" link only appears once the API key and project ID are saved.
* Plugin text is ready for translation (text domain "diffy").
* Updated the plugin description, setup instructions and FAQ.

= 0.9.6 =
* Validate the project ID when saving the settings.

== Upgrade Notice ==

= 1.0.0 =
Tested with WordPress 7.1. Adds security checks to the plugin's AJAX requests and shows Diffy errors during updates.
