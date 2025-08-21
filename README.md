# Read Offline

Export WordPress posts and pages to PDF and EPUB.

## Features (v1)
- Frontend Save as button (PDF/EPUB)
- Admin bulk export with ZIP packaging
- Settings page with General/PDF/EPUB tabs
- Caching by content/settings hash

## Install dependencies (optional but recommended)
If you want real PDF/EPUB generation (not placeholders), install dependencies:

```bash
cd wp-content/plugins/read-offline
composer install
```

Then activate the plugin in WP Admin → Plugins.

## Usage
- Settings → Read Offline to configure formats, filename, PDF/EPUB options.
- On posts/pages, use the Save as dropdown to download.
- In Posts/Pages list, use bulk actions to export and download a ZIP.

## Notes
- If mPDF/PHPePub are not installed, generation will fallback and report a missing dependency.
- Ensure PHP Zip extension is enabled for ZIP downloads.
