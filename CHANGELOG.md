# Changelog

## 2.3.1
- Update: Requires WordPress 6.8+ (tested up to 6.9)
- Update: Requires PHP 8.3+

## 2.3.0
- Feature: Cloudflare Browser Rendering integration for professional PDF generation via headless Chromium
- Feature: Settings UI for Cloudflare Account ID and API Token with connection testing
- Feature: Automatic fallback to mPDF when Cloudflare is unavailable or fails
- Enhancement: REST API format using proper Cloudflare Browser Rendering endpoint
- Enhancement: Configurable PDF options (format, margins, headers, footers) compatible with Cloudflare API
- Fix: Corrected endpoint URL, request format, and parameter formatting for Cloudflare REST API
- Update: Upgraded mpdf/mpdf from v8.2.6 to v8.2.7

## 2.2.8
- Fix: Corrected Cloudflare Browser Rendering endpoint from `/browser/pdf` to `/browser-rendering/pdf`
- Fix: Updated Cloudflare request format from Workers Bindings to REST API format (html + pdfOptions)
- Fix: Fixed margin format to use strings with units (e.g., "15mm") instead of numeric values
- Fix: Corrected PDF format parameter to lowercase ("a4" instead of "A4") per REST API requirements
- Fix: Resolved undefined variable warning in duration calculation
- Update: Upgraded mpdf/mpdf from v8.2.6 to v8.2.7

## 2.2.7
- Feature: Add queue-aware REST hooks for background export processing
- Feature: Add Cloudflare Browser Rendering integration for high-quality PDF generation via headless Chromium
- Enhancement: New capability gate via `read_offline_can_export` filter (default: Editor+)
- Enhancement: Lifecycle signals for export operations (requested/completed/failed)
- Enhancement: Short-circuit hook `read_offline_pre_export` for custom queue integration
- Enhancement: Response shaping via `read_offline_rest_response` filter
- Enhancement: Deduplication lock and cache key filters for concurrent requests
- Enhancement: Auto-detection of Cloudflare credentials with automatic fallback to mPDF
- Enhancement: Settings page integration for Cloudflare Account ID and API Token with connection status indicator
- Enhancement: New filters for Cloudflare PDF options, Puppeteer scripts, and HTML customization
- Enhancement: New actions for Cloudflare PDF generation lifecycle (success/failure tracking)

## 2.2.6
- Minor fixes

## 2.2.5
- Add automatic plugin updates via GitHub

## 2.2.4
- Meta: Switched default branch to `main` (was `master`); preserved prior history at `legacy-master` (previous interim branch was `Refactor`).
- Meta: Version alignment / documentation consolidation for 2.x line (README + readme.txt synchronized).

## 2.2.3
- Fix: Restored PDF Table of Contents generation (regression after refactor) using shared heading parser; respects `toc` + `toc_depth` settings.
- Fix: Eliminated leading blank first page in single + combined PDF exports (first page now starts with TOC or content).
- Change: Clarified & retained default `rest_public` = off (introduced earlier) in documentation to emphasize privacy-first default.
- Dev: Added `read_offline_pdf_toc_html` filter (mirrors EPUB filter) to customize rendered PDF TOC markup/title wrapper.
- Dev: Internal reuse of EPUB heading scanner for PDF TOC; no separate duplicate implementation.
- Dev: (Optional) Added smoke capability method to quickly inspect environment (mPDF / PHPePub availability) via `Read_Offline_Export::debug_smoke_capabilities()`.

## 2.2.2
- Enhancement: Add standard rate limit response headers (Retry-After, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset) to REST export endpoint.
- Enhancement: Successful responses also include current remaining quota when rate limiting active.

## 2.2.1
- Feature: Public REST access toggle to disable unauthenticated exports if desired.
- Feature: Basic per-IP rate limiting (requests/window) for unauthenticated REST usage.
- Admin: Settings fields (public toggle, rate limit, window) and help tab entries.
- Removal: Deprecated Test export admin tool removed.
- Docs: Updated readme/readme.txt with REST security guidance.

## 2.2.0
- Feature: Markdown (MD) export (single post + REST + front-end UI). Includes lightweight HTML→Markdown converter with headings, lists, links, images, emphasis, and fenced code blocks.
- Admin: Added MD to selectable default formats and REST format documentation.
- Admin: Moved Custom PDF CSS from General tab to PDF tab with automatic one-time migration/fallback.
- Markdown: Improved formatting (proper newlines, standard triple backtick fences, spacing cleanup, no literal \n output).
- Internal: Refactored export routing to include md in filename building and caching hash.
- Docs: Updated README to reflect Markdown support and CSS relocation.
- Prep: Bulk action + combined Markdown export planned for a later release.

## 2.1.1
- Add settings sanitization/validation for General, PDF (custom size, margins, TOC depth), and EPUB (meta, cover, CSS profile).
- Tighten escaping in admin UI (CSS classes, health icons, download filename) and add translators’ comments for dynamic strings.
- Minor REST/UI robustness and small validation fallback for invalid PDF custom size (falls back to A4).

## 2.0.0 
- Refactor plugin from scratch
