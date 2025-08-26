# Read Offline 2.x

Refactored, cache‑aware export engine for turning any WordPress post or page into beautiful, portable documents: **PDF**, **EPUB**, and **Markdown**.

The 2.x series focuses on: reliability, privacy‑first defaults, extensibility via hooks, and clean, fast generation with hashing & selective invalidation.

<img src="assets/bulk-export.png">

## 2.x Highlights
**Fast repeat downloads** – Content + settings hashing caches per‑post exports (PDF/EPUB/MD) under uploads; manual or programmatic cache invalidation provided.

**Flexible front‑end UI** – Auto‑insert or shortcode `[read_offline]` with a format selector (PDF/EPUB/Markdown) and rate‑limit friendly REST calls.

**Robust PDF output** – mPDF with restored hierarchical Table of Contents, optional page numbers (automatically switches between bookmark TOC and manual list), custom CSS, margins, headers/footers, watermark, and combined multi‑post exports.

**Standards‑minded EPUB** – Strict XHTML generation, optional cover image strategies, configurable metadata & CSS profile (light/dark/none/custom), plus hookable validation step.

**Developer‑friendly Markdown** – Lightweight deterministic HTML→MD converter for reuse in static site pipelines or content migration.

**Privacy & rate limiting** – Public REST access is OFF by default; enable only if you want anonymous downloads. Simple per‑IP sliding window limiting with standard headers (Retry‑After & X‑RateLimit-*).

**Hook ecosystem** – Dozens of filters/actions (`read_offline_pdf_toc_html`, `read_offline_epub_css`, `read_offline_content_html`, `read_offline_epub_validate`, etc.). See `HOOKS.md` for the catalogue.

**Selective invalidation** – Call `Read_Offline_Export::invalidate_post_cache( $post_id, $format )` after programmatic content changes (or omit `$format` for all formats).

## Recently Added (2.2.x)
- Restored & improved PDF TOC (hierarchical, no numbering; page numbers when numbering active) & removed leading blank page.
- Non‑public mode now still allows front‑end exports via per‑post nonce (secure while disabling blind anonymous hits).
- Integrity headers for downloads (Content-Length plus optional checksum headers if served via helper endpoints).
- EPUB generation action & validation filter for external epubcheck integration.
- Admin UI responsive refinements and environment health card.

## What it does
- Adds a “Save as” control on posts/pages (auto or shortcode) for PDF, EPUB, Markdown.
- Bulk exports: combine multiple posts/pages into one PDF or EPUB – or switch to a ZIP of individual files. (Combined Markdown export on roadmap.)
- Hash‑based caching keyed to content + relevant settings; invalidated on demand.
- Segmented settings UI (General / PDF / EPUB) with inline help popups & responsive layout.

## Requirements
- WordPress 6.5+
- PHP 8.2+
- Tested up to: WordPress 6.8
- Zip extension (for ZIP bulk or multiple file archive mode)
- Bundled Composer libraries:
	- mPDF (PDF)
	- PHPePub (EPUB)

## Install
1. Upload to `wp-content/plugins/read-offline` or install via Plugins → Add New.
2. Activate. (Composer already bundled; no build step needed.)
3. Visit Settings → Read Offline to configure.



Developer: to update vendors or run tests:

```bash
cd wp-content/plugins/read-offline
composer install
```

## Quick start
1. Settings → Read Offline: pick default formats & filename pattern.
2. Open a post, use the “Save as” control (or place `[read_offline]`).
3. Bulk export from Posts list (choose action, apply) – optionally toggle ZIP vs combined.

## Usage details
### Frontend
- Auto-insert the “Save as” control from Settings, or add the shortcode:
	- [read_offline]

### Admin bulk export
- Select multiple posts/pages, pick a Read Offline bulk action (PDF or EPUB), and apply. (Markdown bulk action coming.)
- By default a single combined document is generated. Disable "Combine bulk exports" in General settings to instead receive a ZIP of per‑post files.

## REST API
`GET /wp-json/read-offline/v1/export?postId=ID&format=pdf|epub|md&nonce=...`

Behavior:
- With Public REST OFF (default) a valid per‑post nonce or capability is required.
- With Public REST ON published posts are anonymous‑fetchable, still rate limited.

Rate limiting for unauthenticated requests sets:
`Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`.

## Settings overview
- General: choose visible formats (PDF/EPUB/MD), placement, filename template, bulk combination, cache clearing, REST public toggle (default off), and rate limiting.
- PDF: page size, margins, header/footer, watermark, TOC (depth + automatic page-number TOC when page numbers enabled), and CSS tweaks.
- EPUB: metadata (author/publisher/lang), cover image, TOC, and CSS profile/custom CSS.

## Troubleshooting
| Issue | Likely Cause / Fix |
|-------|--------------------|
| Missing mPDF / PHPePub | Re-install vendors (`composer install`). |
| PDF TOC lacks page numbers | Enable page numbering in PDF settings. |
| ZIP not produced | Zip extension missing OR combine mode enabled. |
| Stale output | Invalidate via helper or Clear Cache button. |
| EPUB warnings | Check source HTML for unclosed tags / scripts. |

## Privacy & security
- Zero telemetry; files generated locally.
- Public REST OFF by default; enable only if you intend anonymous downloads.
- Basic IP rate limiting to reduce scraping load.

## License & credits
- GPLv2 or later. See License URI in readme.txt.
- PDF powered by mPDF. EPUB powered by PHPePub.

—

For deeper details see: `readme.txt` (WP directory format), `CHANGELOG.md`, and `HOOKS.md` for the full action/filter reference.

---
Happy exporting.
