# Changelog

## 0.2.2 - 2025-08-22
- Feature: Public REST access toggle to disable unauthenticated exports if desired.
- Feature: Basic per-IP rate limiting (requests/window) for unauthenticated REST usage.
- Admin: Settings fields (public toggle, rate limit, window) and help tab entries.
- Removal: Deprecated Test export admin tool removed.
- Docs: Updated readme/readme.txt with REST security guidance.

## 0.2.0 - 2025-08-21
- Feature: Markdown (MD) export (single post + REST + front-end UI). Includes lightweight HTML→Markdown converter with headings, lists, links, images, emphasis, and fenced code blocks.
- Admin: Added MD to selectable default formats and REST format documentation.
- Admin: Moved Custom PDF CSS from General tab to PDF tab with automatic one-time migration/fallback.
- Markdown: Improved formatting (proper newlines, standard triple backtick fences, spacing cleanup, no literal \n output).
- Internal: Refactored export routing to include md in filename building and caching hash.
- Docs: Updated README to reflect Markdown support and CSS relocation.
- Prep: Bulk action + combined Markdown export planned for a later release.

## 0.1.1 - 2025-08-21
- Add settings sanitization/validation for General, PDF (custom size, margins, TOC depth), and EPUB (meta, cover, CSS profile).
- Tighten escaping in admin UI (CSS classes, health icons, download filename) and add translators’ comments for dynamic strings.
- Minor REST/UI robustness and small validation fallback for invalid PDF custom size (falls back to A4).

## 0.1.0 - 2025-08-XX
- Initial implementation: export to PDF (mPDF) and EPUB (PHPePub), REST endpoint, admin settings with tabs, inline help popups, cache, and bulk ZIP.
