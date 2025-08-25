# Read Offline

Export WordPress posts and pages to PDF, EPUB, and Markdown for offline reading, printing, or reuse.

## New in 2.2.3 (renumbered from 0.2.4)
- Restored & improved PDF Table of Contents (hierarchical, no numbering; shows page numbers when page numbering enabled).
- No more leading blank page in PDFs when TOC is enabled.
- Privacy-first: public REST access default remains OFF (toggle in General settings).
- Filter `read_offline_toc_title` lets you customize the “Contents” heading (PDF & EPUB). `read_offline_pdf_toc_html` can alter manual (non-page-number) TOC markup.
- Helper `Read_Offline_Export::invalidate_post_cache( $post_id, $format )` to purge cached exports for a single post.

## What it does
- Adds a “Save as” control on posts/pages to download PDF, EPUB, or Markdown (MD).
- Bulk exports: combine multiple selected posts/pages into ONE PDF or EPUB (default) or produce a ZIP of individual files (toggle in settings). MD currently exports per‑post files (combined MD planned).
- Settings page with General, PDF, and EPUB tabs to tailor output (Custom PDF CSS moved to PDF tab in 0.2.0; MD uses core content styles).
- Caches exports by content/settings so repeat downloads are fast.

## Requirements
- WordPress 6.5+
- PHP 8.2+
- Tested up to: WordPress 6.8
- PHP Zip extension (for ZIP downloads)
- Composer dependencies for full-quality output:
	- PDF via mPDF
	- EPUB via PHPePub

## Install
1) Upload to wp-content/plugins/read-offline (or install from Plugins in WP Admin) and activate.
2) No Composer step is required for normal use — vendor libraries are bundled.

Versioning note: Release series 0.1.1–0.2.4 has been renumbered to 2.1.1–2.2.3 (functionally identical; numeric jump aligns with broader project version scheme).

Developer note: If you’re modifying dependencies or updating vendors, run:

```bash
cd wp-content/plugins/read-offline
composer install
```

## Quick start
1) Go to Settings → Read Offline and choose which formats to show and how files are named.
2) Open any post or page and use the “Save as” control to download a PDF, EPUB, or Markdown file.
3) For many items at once, select posts/pages in the admin list and use the Bulk actions to export a ZIP.

## Usage details
### Frontend
- Auto-insert the “Save as” control from Settings, or add the shortcode:
	- [read_offline]

### Admin bulk export
- Select multiple posts/pages, pick a Read Offline bulk action (PDF or EPUB), and apply. (Markdown bulk action coming.)
- By default a single combined document is generated. Disable "Combine bulk exports" in General settings to instead receive a ZIP of per‑post files.

## REST API
- Endpoint: /wp-json/read-offline/v1/export (format=pdf|epub|md)
- Public access to published posts can be toggled in General settings ("Public REST access").
- Simple per-IP unauthenticated rate limiting: configure requests per window and window length.
- Standard headers when rate limiting active: `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`.
- Useful for scripts/integrations that need on-demand files.

## Settings overview
- General: choose visible formats (PDF/EPUB/MD), placement, filename template, bulk combination, cache clearing, REST public toggle (default off), and rate limiting.
- PDF: page size, margins, header/footer, watermark, TOC (depth + automatic page-number TOC when page numbers enabled), and CSS tweaks.
- EPUB: metadata (author/publisher/lang), cover image, TOC, and CSS profile/custom CSS.

## Troubleshooting
- mPDF/PHPePub not available: run composer install in the plugin folder. The Settings page shows an Environment card to verify.
- PDF TOC missing page numbers: ensure page numbering option is enabled. Without page numbers the plugin falls back to a manual TOC (no page refs).
- ZIP not created: ensure the PHP Zip extension is enabled.
- Stale export after content change: call `Read_Offline_Export::invalidate_post_cache( $post_id )` or press Clear cache in settings.
- EPUB reader warnings: typically unclosed HTML in post content.

## Privacy & security
- No telemetry. Exports are generated locally and cached under uploads.
- Public REST access disabled by default (enable if you want anonymous downloads).
- Rate limiting helps blunt abuse of the export endpoint (basic per-IP sliding window).

## License & credits
- GPLv2 or later. See License URI in readme.txt.
- PDF powered by mPDF. EPUB powered by PHPePub.

—

For more details, see readme.txt (WordPress readme) and CHANGELOG.md.

Developer reference: see HOOKS.md for all filters & actions with examples.
