# Read Offline – Design Document

A WordPress plugin to export posts and pages to offline-friendly formats: PDF and EPUB.

## Goals and requirements

- Admin bulk export: Allow a logged-in user with proper capability to select multiple posts/pages from the list tables and export them to a chosen format (PDF or EPUB).
- Frontend export: Add a "Save as" button/dropdown at the bottom of each post/page for visitors to download the content in a selected format.
- Settings page: Provide a settings page under WP Admin → Settings with global defaults.
- Configurable per-format options (where supported):
  - Page format/size (e.g., A4, Letter) and margins (PDF)
  - Table of Contents (TOC)
  - Page numbers (PDF)
  - Watermark text (PDF)
  - Printable toggle (PDF permissions)
  - Metadata (title, author, publisher, description, cover) (EPUB)

Non-goals (initial release):
- Multi-language content packaging within a single file (only the site language at export time).
- Exporting comments, custom tax archive pages, or complex application pages unless opted in.
- Server-side conversion via external SaaS services.

## Formats and libraries

- PDF
  - Library: mPDF (`mpdf/mpdf`)
  - Rationale: Supports TOC, headers/footers, page numbers, watermarks, custom fonts, CSS, and PDF permissions (printable toggle) with robust HTML/CSS support.
- EPUB
  - Library: PHPePub (`grandt/phpepub`)
  - Rationale: Mature EPUB 2/3 generation, metadata, cover, TOC/nav, and embedding images/fonts.

## High-level architecture

- UI layer
  - Admin list tables: Custom bulk actions (Posts, Pages) for "Export to PDF" and "Export to EPUB".
  - Frontend UI: Auto-injected dropdown/button after content (theme-filtered), plus `[read_offline]` shortcode and a Gutenberg block (later) for precise placement.
  - Settings page: WordPress Settings API-based page under Settings → Read Offline.

- Export controller
  - Orchestrates content extraction, sanitization, asset collection (images, CSS), and delegation to format-specific generators.
  - Handles caching, file naming, and response streaming.

- Generators
  - PDFGenerator (mPDF)
  - EPUBGenerator (PHPePub)

- Storage and cache
  - Generated files stored under UPLOADS + `/read-offline/` using deterministic names with content-version hashes.
  - Cache invalidation on post update or settings changes.

- Delivery
  - Downloads served via signed, nonce-protected URLs (REST or pretty endpoint) with proper headers and range support.
  - Optional ZIP packaging when exporting multiple items via bulk action.

- Security and permissions
  - Frontend: Exports allowed for publicly viewable content; private/protected content requires capability checks and nonces.
  - Admin bulk actions restricted to users with `edit_posts` (configurable; can be elevated to `edit_others_posts` for multi-author sites).

## User experience details

### Admin bulk export
- Adds two bulk actions to Posts and Pages list tables: "Export to PDF" and "Export to EPUB".
- After applying an action, the plugin:
  - Queues generation for selected items.
  - If ≤ N items (configurable, default 20), generates synchronously and streams a ZIP containing all outputs.
  - If > N items, kicks off a background job via WP Cron and presents a status/progress page with a link to download when ready.
- ZIP filename: `site-slug_YYYYMMDD_HHMMSS_{format}.zip`.

### Frontend "Save as"
- Injected after `the_content` (can be disabled in settings).
- Dropdown options: PDF, EPUB (based on enabled formats).
- Clicking triggers a download URL with a signed nonce. No page reload if using AJAX; progressively enhances with standard links.
- Shortcode `[read_offline formats="pdf,epub" include_toc="1"]` and a block (later) allow placement anywhere.

### Settings page (Settings → Read Offline)
- General
  - Enable auto-insert button [on/off]
  - Default formats shown [checkboxes]
  - Filename template (e.g., `{site}-{post_slug}-{format}`)
  - Include featured image as cover [on/off]
  - Include author/date [on/off]
  - Custom CSS for PDF (textarea)
  - Clear cache button
- PDF
  - Page size (A4, Letter, Legal, Custom WxH)
  - Margins (top/right/bottom/left)
  - Header/footer (text or HTML; page numbers toggle)
  - TOC [on/off], TOC depth
  - Watermark text and opacity [optional]
  - Printable [on/off] (sets PDF permissions via mPDF `SetProtection`)
  - Font subset/embedding [auto]
- EPUB
  - Metadata: title template, author, publisher, language
  - TOC [on/off], depth
  - Cover source: featured image/site logo/custom upload
  - CSS profile (light/dark/none/custom)

## Content extraction and preparation

- Source: `the_content` with `apply_filters`, post title, excerpt (optional), featured image (optional), canonical permalink, author, date.
- Sanitization:
  - Remove share widgets, related posts, ads, forms.
  - Resolve shortcodes to output; allow shortcode whitelist.
  - Rewrite image URLs to absolute; download and embed images when needed (EPUB package, PDF base64 or file path).
  - Optional CSS normalization and minimal print stylesheet.
- TOC generation:
  - Scan headings (h1-h6) to build TOC and anchor links.
  - PDF: mPDF TOC bookmarks; EPUB: nav.xhtml + toc.ncx.

## Caching and invalidation

- File key: `{postId}.{format}.{hash}.ext` where `hash = sha1(post_modified_gmt + settings_version + content_signature)`.
- Location: UPLOADS + `/read-offline/{YYYY}/{MM}/`.
- On post update or relevant settings change, cached files are invalidated.
- Admin can manually clear cache.

## Endpoints and routing

- REST API endpoints (namespace `read-offline/v1`):
  - `POST /export`
    - Auth: nonce required for logged-in admin flows; public for public posts via CSRF-protected nonce.
    - Body (JSON): `{ postIds: number[] | number, format: 'pdf'|'epub', options?: object, context?: 'frontend'|'admin' }`
    - Response: `{ status: 'ok', url?: string, downloadToken?: string, jobId?: string }`
    - Errors: `400 invalid_format`, `403 forbidden`, `404 not_found`, `500 generation_failed`.
  - `GET /download`
    - Query: `token` – time-limited signed token.
    - Response: file stream with appropriate `Content-Type` and `Content-Disposition`.
- Pretty URLs (optional): `/read-offline/{post-slug}.{format}` with nonce or time-limited signature.

## Bulk processing and background jobs

- Hooks:
  - `bulk_actions-edit-post`, `handle_bulk_actions-edit-post` and for pages: `bulk_actions-edit-page`, `handle_bulk_actions-edit-page`.
- Flow:
  1. User selects posts/pages, chooses "Export to {FORMAT}" bulk action.
  2. Handler validates capability and nonce, splits selection into batches.
  3. If `count <= threshold` (default 20): generate synchronously and stream ZIP.
  4. Else schedule batches via `wp_schedule_single_event` storing a `jobId` in an option/transient with progress.
  5. Provide a status admin page: `tools.php?page=read-offline-jobs&job={jobId}` that polls progress via REST.
- ZIP packaging: use `ZipArchive` if available; otherwise fallback to PclZip or error with guidance.

## Error handling and observability

- User-facing errors are friendly and localized.
- Logging (debug mode): WP debug log channel `read_offline` with context: postId, format, duration, memory.
- Telemetry hooks (optional): performance counters, conversion success rate.

## Security

- Nonces for all user-triggered exports (action `read_offline_export`).
- Capability checks: bulk actions require `edit_posts`; settings require `manage_options`.
- Private/protected content only downloadable by authorized users; per-post capability checks before generation.

## Internationalization and accessibility

- All strings wrapped in `__()` / `_e()` with text domain `read-offline`.
- Frontend controls are keyboard-accessible and screen-reader friendly; proper `aria-labels`.

## Extensibility (hooks)

- Filters
  - `read_offline_enabled_formats` – modify available formats per request.
  - `read_offline_content_html` – alter HTML before generation.
  - `read_offline_pdf_css` – inject CSS.
  - `read_offline_epub_metadata` – modify EPUB metadata.
  - `read_offline_filename` – alter output filename.
- Actions
  - `read_offline_before_generate` ($postId, $format, $context)
  - `read_offline_after_generate` ($postId, $format, $path, $context)

## Data model and settings (options)

- Storage model (aligned with UI tabs):
  - Option `read_offline_settings_general`
    - Default: `{ auto_insert: true, formats: ['pdf','epub'], filename: '{site}-{post_slug}-{format}', include_featured: true, include_author: true, css: '' }`
  - Option `read_offline_settings_pdf`
    - Default: `{ size: 'A4', margins: {t:15,r:15,b:15,l:15}, header: '', footer: '', page_numbers: true, toc: true, toc_depth: 3, watermark: '', printable: true, fonts: {} }`
  - Option `read_offline_settings_epub`
    - Default: `{ meta: {author:'',publisher:'',lang:''}, toc: true, toc_depth: 3, cover: 'featured', css_profile: 'light', custom_css: '' }`
  - Internal option `read_offline_settings_version`: schema version for migrations.

## Filename rules

- Template tokens: `{site}`, `{post_slug}`, `{post_id}`, `{title}`, `{format}`, `{date}`, `{lang}`.
- Sanitization to safe filenames; ensure predictable extensions: `.pdf`, `.epub`.
- Example: `myblog-how-to-bake-bread-pdf.pdf`.

## Generator specifics (v1)

### PDF (mPDF)
- Instantiate with: `new \Mpdf\Mpdf(['format' => size, 'margin_left'=>l, 'margin_right'=>r, 'margin_top'=>t, 'margin_bottom'=>b]);`
- Header/footer: use `SetHTMLHeader`/`SetHTMLFooter`; enable page numbers via `{PAGENO}` placeholders.
- TOC: `TOCpagebreak` with `TOC-preHTML` and heading levels up to `toc_depth`.
- Watermark: `SetWatermarkText` and `showWatermarkText`.
- Printable toggle: `SetProtection(['print'])` when printable is true; when false, restrict printing.
- CSS: merge custom CSS from General settings; add minimal print CSS.

### EPUB (PHPePub)
- Create book with metadata from settings and post.
- Build chapters by splitting content at `<h2>` (fallback to full content if none).
- Embed images referenced in content; rewrite to internal resources.
- Generate TOC/nav with levels up to `toc_depth`.
- Cover: featured image → book cover; otherwise site logo; otherwise none.

## Shortcode and frontend UI

- Shortcode: `[read_offline]`
  - Attributes:
    - `formats`: CSV of `pdf`, `epub` (default from settings)
    - `include_toc`: `0|1` (default from per-format settings)
    - `class`: extra CSS class
    - `post_id`: override target post (default current)
  - Output: button + dropdown with nonce-protected links to REST download.
- Auto-insert: filter `the_content` to append UI when enabled and for singular posts/pages.

## ZIP packaging

- File names inside ZIP: `{sanitized-title}-{postId}.{ext}`.
- Top-level folder optional; by default files at root of ZIP.
- Use `ZipArchive`; if missing, display admin notice with instructions to enable zip extension.

## Performance considerations

- Limit synchronous bulk export items to threshold.
- Downscale large images for PDF to max width (e.g., 1600px) to reduce memory.
- Stream file responses; avoid reading entire files into memory.

## QA and test plan (v1)

- Settings
  - Save/restore each tab; defaults applied; capability checks enforced.
- Frontend
  - Button appears on posts/pages when enabled; respects format toggles; downloads work for public posts.
  - Private/protected posts require auth/password.
- Admin bulk
  - Bulk actions present for posts and pages; small selection downloads ZIP immediately; large selection schedules job and completes.
- PDF
  - Verify page size, margins, header/footer, page numbers, TOC, watermark, printable flag.
- EPUB
  - Verify metadata, cover, TOC, images embedded.
- Caching
  - Re-export after content change yields new file; unchanged content serves cached file.
- Error paths
  - Missing ZipArchive: error notice; REST returns 500 with code `zip_extension_missing`.
  - Invalid format requests return 400.

## Packaging and distribution

- Composer dependencies (suggested constraints):
  - `mpdf/mpdf: ^8.2`
  - `grandt/phpepub: ^4.0`
- Ship vendor directory in releases.
- No external network calls.

## Development notes

- Code structure (proposed):
  - `read-offline.php` – plugin bootstrap
  - `includes/` – classes and helpers (Generators, Controller, Admin, Frontend, REST)
  - `assets/` – CSS/JS for admin and frontend
  - `templates/` – markup templates for exports if needed
  - `languages/` – `.pot` file for i18n
  - `vendor/` – Composer dependencies
  - UPLOADS + `/read-offline/` – runtime output (not in repo)
- Coding standards: WordPress PHP coding standards; PHPCS config.

## Roadmap

- v1
  - PDF and EPUB generation, bulk ZIP export, frontend button, settings page, caching.
- v1.x
  - Gutenberg block for Save As, per-post overrides, advanced CSS profiles, ZIP customization.
- v2
  - Background processing UI improvements, multi-language metadata, per-user personalization. Check if Action Scheduler is needed.

## Acceptance criteria (summary)

- Admin can select multiple posts/pages and export to PDF or EPUB, receiving a ZIP download when appropriate.
- Visitors see a Save as dropdown on posts/pages and can download PDF or EPUB.
- Settings page exists under Settings → Read Offline with the options outlined above.
- PDF supports page size, TOC, page numbers, watermark, and printable toggle.
- EPUB supports metadata, TOC, and cover.
