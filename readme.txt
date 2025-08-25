=== Read Offline ===
Contributors: your-name
Tags: pdf, epub, export, offline, download
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 2.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export posts and pages to PDF, EPUB, and Markdown for offline reading or reuse. Shortcode and auto-insert UI, admin bulk exports (single combined file or ZIP), and a REST API (with optional public access & rate limiting).

== Description ==
Read Offline lets site visitors and editors download content as PDF, EPUB, or Markdown (MD).

Highlights:
- Frontend "Save as" control (PDF/EPUB/MD) with shortcode [read_offline] and optional auto-insert.
- Bulk export: combine multiple posts/pages into one PDF or EPUB (default) OR toggle setting to create a ZIP of individual files. (Markdown bulk action coming.)
- REST endpoint for programmatic exports: /wp-json/read-offline/v1/export (format=pdf|epub|md) with configurable public toggle and basic per-IP rate limiting
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
= 2.2.4 =
Version alignment release: bumps plugin to 2.2.4 (was 2.2.3 codebase) to finalize renumbering from 0.x.y -> 2.x.y. No functional changes beyond updated documentation and @since annotations.

= 2.2.3 (formerly 0.2.4) =
- Fix: Restored PDF Table of Contents (regression) with hierarchical structure and page numbers when page numbering enabled (no leading blank page).
- Change: Public REST access default remains disabled; docs clarified (privacy-first).
- Dev: Added `read_offline_toc_title` (PDF/EPUB) and `read_offline_pdf_toc_html` (manual fallback) filters.
- Dev: Added `Read_Offline_Export::invalidate_post_cache( $post_id, $format )` helper for targeted cache purges.
- Dev: Reused EPUB heading parser for PDF TOC/bookmarks; injected mPDF bookmarks when page numbers active.
= 2.2.2 (formerly 0.2.3) =
- Enhancement: Added standard rate limit headers (Retry-After, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset) to REST responses.
- Enhancement: Successful REST responses now include remaining quota when rate limiting applies.

= 2.2.1 (formerly 0.2.2) =
- Feature: Public REST access toggle (allow/deny unauthenticated exports of published posts).
- Feature: Simple per-IP rate limiting for unauthenticated REST exports (configurable requests + window seconds).
- Admin: Added settings for REST security (toggle + limits) on General tab.
- Cleanup: Removed Test export tool from admin UI.

= 2.2.0 (formerly 0.2.0) =
- Feature: Markdown export (single post UI + REST) with fenced code, lists, headings, links, images.
- Admin: Added MD to selectable formats and REST help examples.
- Admin: Moved Custom PDF CSS from General tab to PDF tab (auto migration/fallback).
- Markdown: Improved newline handling (removed literal \n), standardized triple backticks.
- Internal: Added md to cache hash + filename builder.
- Docs: Updated README/readme.txt.
- Note: Bulk action + combined Markdown export planned.

= 2.1.1 (formerly 0.1.1) =
- Add settings validation/sanitization (General, PDF, EPUB).
- Tighten escaping and add translators’ comments.
- Safer fallback when PDF custom size is invalid.

= 2.0.0 (formerly 0.1.0) =
- Initial release with PDF/EPUB export, REST API, caching, and admin bulk ZIP.

== Upgrade Notice ==
= 0.2.4 =
Restores PDF TOC generation (with page numbers when enabled) and removes the initial blank page. Adds TOC title customization and per-post cache invalidation helper.
= 0.2.0 =
Adds Markdown export and relocates Custom PDF CSS setting; update if you need MD downloads or cleaner PDF CSS management.

= 0.1.1 =
Recommended update that adds validation and improves security hardening (escaping).
