=== Read Offline ===
Contributors: your-name
Tags: pdf, epub, export, offline, download
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export posts and pages to PDF, EPUB, and Markdown for offline reading or reuse. Shortcode and auto-insert UI, admin bulk exports (single combined file or ZIP), and a REST API.

== Description ==
Read Offline lets site visitors and editors download content as PDF, EPUB, or Markdown (MD).

Highlights:
- Frontend "Save as" control (PDF/EPUB/MD) with shortcode [read_offline] and optional auto-insert.
- Bulk export: combine multiple posts/pages into one PDF or EPUB (default) OR toggle setting to create a ZIP of individual files. (Markdown bulk action coming.)
- REST endpoint for programmatic exports: /wp-json/read-offline/v1/export (format=pdf|epub|md)
- Caching keyed by content/settings so repeated downloads are fast.
- EPUB output is strict XHTML; PDF via mPDF.

Works great for blogs, docs, and longform content that readers want to keep.

= Rendering / conversion engines =
- PDF: mPDF
- EPUB: PHPePub
- Markdown: internal lightweight HTML→Markdown converter

Both are provided via Composer; if they’re not installed you’ll see a helpful message in the admin health card and test tool.

== Installation ==
1. Upload the plugin to /wp-content/plugins/ or install it from your site’s plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. No Composer step is required for normal use — vendor libraries are bundled.
4. Visit Settings → Read Offline to configure formats (PDF/EPUB/MD), filename template, and PDF/EPUB options. (Custom PDF CSS moved from General to PDF tab in 0.2.0.)

== Frequently Asked Questions ==
= I see “mPDF/PHPePub not available” =
Install Composer dependencies in the plugin folder. The Settings page shows an Environment health card to verify libraries are loaded.

= The EPUB has validation errors in some readers =
The plugin normalizes HTML to strict XHTML, fixes common entity/attribute issues, and can leverage tidy if available. If you still see issues, check your post content for unclosed tags or embedded scripts.

= Bulk export doesn’t create a ZIP =
If you expected a ZIP, uncheck the "Combine bulk exports" option under Settings → Read Offline → General. Also ensure the PHP Zip extension is enabled if using ZIP mode.

== Screenshots ==
1. Settings page with tabs and inline help popups
2. Frontend Save as control on a post
3. Bulk export actions in the posts list

== Changelog ==
= 0.2.0 =
- Feature: Markdown export (single post UI + REST) with fenced code, lists, headings, links, images.
- Admin: Added MD to selectable formats and REST help examples.
- Admin: Moved Custom PDF CSS from General tab to PDF tab (auto migration/fallback).
- Markdown: Improved newline handling (removed literal \n), standardized triple backticks.
- Internal: Added md to cache hash + filename builder.
- Docs: Updated README/readme.txt.
- Note: Bulk action + combined Markdown export planned.

= 0.1.1 =
- Add settings validation/sanitization (General, PDF, EPUB).
- Tighten escaping and add translators’ comments.
- Safer fallback when PDF custom size is invalid.

= 0.1.0 =
- Initial release with PDF/EPUB export, REST API, caching, and admin bulk ZIP.

== Upgrade Notice ==
= 0.2.0 =
Adds Markdown export and relocates Custom PDF CSS setting; update if you need MD downloads or cleaner PDF CSS management.

= 0.1.1 =
Recommended update that adds validation and improves security hardening (escaping).
