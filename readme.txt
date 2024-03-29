=== Diffy Visual Regression Testing ===
Contributors: ygerasimov
Tags: visual regression testing, updates verification, automated testing
Requires at least: 4.8
Tested up to: 6.1.1
Requires PHP: 7.1
Stable tag: 0.9.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Development: https://github.com/DiffyWebsite/wordpress-diffy

Diffy helps to verify plugin updates by taking screenshots of your site before and after update and comparing them.

Ideally you expect zero changes after running updates.

== Set up instructions ==

You need to have active Diffy (https://diffy.website) account in order to use this plugin. Plugin will also allow you to create an account and project for your site with one click.

Diffy provides 2 weeks trial to cover up to 100 pages of your site.

Your site should be publicly available. Diffy runs workers from AWS infrastructure by using single IP address 3.216.56.216. Whitelist it if needed.

Once you registered an account, please create a project. You need to specify your site's URL as Production environment. Add your site's URLs to the project. Diffy can parse sitemaps if you like. Meanwhile you will want to have just key pages covered by visual regression testing and not every page of your site.

After setting up the project, generate API key under My Account -> Keys.

Enter project id and API Key to Diffy's plugin settings page and you should be good to go.

== How it works? ==

During plugins update process plugin will call Diffy via API to create set of screenshots before the update. Expect that it will make update process longer.

After screenshots are ready update process will continue. Once updates are completed plugin will call Diffy once again to create second set of screenshots and compare them with your "before" version.

You will receive an email notifications about screenshots and diffs being completed.

Review the report and ensure that nothing got broken.

== Screenshots ==

1. Diffy embeds to the process of updating plugin. Creates screenshots before updates applied.
2. Diffy creates screenshots after update, compares with "before" version.
3. You can see that Diffy found changes right away and can check the report.
4. Report has thumbnails view.
5. Full view of reviewing single difference.

== Support ==

Welcome to reach out to Diffy's team via Intercom or by email info@diffy.website.

== Changelog ==

1.9.4 Separate link to update plugins with Diffy testing.
