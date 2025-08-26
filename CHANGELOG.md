# Changelog

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
