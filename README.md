# Read Offline

Export WordPress posts and pages to PDF and EPUB for offline reading or printing.

## What it does
- Adds a “Save as” control on posts/pages to download PDF or EPUB.
- Bulk exports: combine multiple selected posts/pages into ONE PDF or EPUB (default) or produce a ZIP of individual files (toggle in settings).
- Settings page with General, PDF, and EPUB tabs to tailor output.
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

Developer note: If you’re modifying dependencies or updating vendors, run:

```bash
cd wp-content/plugins/read-offline
composer install
```

## Quick start
1) Go to Settings → Read Offline and choose which formats to show and how files are named.
2) Open any post or page and use the “Save as” control to download a PDF or EPUB.
3) For many items at once, select posts/pages in the admin list and use the Bulk actions to export a ZIP.

## Usage details
### Frontend
- Auto-insert the “Save as” control from Settings, or add the shortcode:
	- [read_offline]

### Admin bulk export
- Select multiple posts/pages, pick a Read Offline bulk action (PDF or EPUB), and apply.
- By default a single combined document is generated. Disable "Combine bulk exports" in General settings to instead receive a ZIP of per‑post files.

### REST API
- Programmatic exports are available at: /wp-json/read-offline/v1/export
- Useful for scripts/integrations that need on-demand files.

## Settings overview
- General: choose visible formats (PDF/EPUB), placement, filename template, and whether bulk exports are combined or zipped.
- PDF: page size, margins, header/footer, watermark, TOC depth, and CSS tweaks.
- EPUB: metadata (author/publisher/lang), cover image, TOC, and CSS profile/custom CSS.

## Troubleshooting
- mPDF/PHPePub not available: run composer install in the plugin folder. The Settings page shows an Environment card and a test tool to verify.
- ZIP not created: ensure the PHP Zip extension is enabled.
- EPUB reader warnings: the plugin normalizes HTML toward strict XHTML. Persistent issues often stem from unclosed tags or embedded scripts in post content.

## Privacy & security
- No telemetry. Exports are generated locally and cached under uploads.

## License & credits
- GPLv2 or later. See License URI in readme.txt.
- PDF powered by mPDF. EPUB powered by PHPePub.

—

For more details, see readme.txt (WordPress readme) and CHANGELOG.md.
