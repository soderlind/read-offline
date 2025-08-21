# Changelog

## 0.1.1 - 2025-08-21
- Add settings sanitization/validation for General, PDF (custom size, margins, TOC depth), and EPUB (meta, cover, CSS profile).
- Tighten escaping in admin UI (CSS classes, health icons, download filename) and add translators’ comments for dynamic strings.
- Minor REST/UI robustness and small validation fallback for invalid PDF custom size (falls back to A4).

## 0.1.0 - 2025-08-XX
- Initial implementation: export to PDF (mPDF) and EPUB (PHPePub), REST endpoint, admin settings with tabs, inline help popups, cache, and bulk ZIP.
