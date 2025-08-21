=== Read Offline ===
Contributors: your-name
Tags: pdf, epub, export, offline, download
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export posts and pages to PDF and EPUB for offline reading. Shortcode and auto-insert UI, admin bulk exports (ZIP), and a REST API.

== Description ==
Read Offline lets site visitors and editors download content as PDF or EPUB.

Highlights:
- Frontend "Save as" control (PDF/EPUB) with shortcode [read_offline] and optional auto-insert.
- Admin bulk export to create a ZIP of multiple posts/pages.
- REST endpoint for programmatic exports: /wp-json/read-offline/v1/export
- Caching keyed by content/settings so repeated downloads are fast.
- EPUB output is strict XHTML; PDF via mPDF.

Works great for blogs, docs, and longform content that readers want to keep.

= Rendering engines =
- PDF: mPDF
- EPUB: PHPePub

Both are provided via Composer; if they’re not installed you’ll see a helpful message in the admin health card and test tool.

== Installation ==
1. Upload the plugin to /wp-content/plugins/ or install it from your site’s plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. (Recommended) Install Composer dependencies for real PDF/EPUB generation:
   - From the plugin folder run: `composer install`.
4. Visit Settings → Read Offline to configure formats, filename template, and PDF/EPUB options.

== Frequently Asked Questions ==
= I see “mPDF/PHPePub not available” =
Install Composer dependencies in the plugin folder. The Settings page shows an Environment health card to verify libraries are loaded.

= The EPUB has validation errors in some readers =
The plugin normalizes HTML to strict XHTML, fixes common entity/attribute issues, and can leverage tidy if available. If you still see issues, check your post content for unclosed tags or embedded scripts.

= Bulk export doesn’t create a ZIP =
Ensure the PHP Zip extension is enabled on your server.

== Screenshots ==
1. Settings page with tabs and inline help popups
2. Frontend Save as control on a post
3. Bulk export actions in the posts list

== Changelog ==
= 0.1.1 =
- Add settings validation/sanitization (General, PDF, EPUB).
- Tighten escaping and add translators’ comments.
- Safer fallback when PDF custom size is invalid.

= 0.1.0 =
- Initial release with PDF/EPUB export, REST API, caching, and admin bulk ZIP.

== Upgrade Notice ==
= 0.1.1 =
Recommended update that adds validation and improves security hardening (escaping).
