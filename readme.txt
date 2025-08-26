=== Read Offline ===
Contributors: soderlind
Tags: pdf, epub, export, offline, download, markdown, bulk-export, documents
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 2.2.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform your WordPress content into beautiful PDF, EPUB, and Markdown documents with one click. Perfect for bloggers, businesses, and content creators who want to share their work offline.

== Description ==

**Turn any WordPress post or page into professional documents in seconds.**

Read Offline transforms your WordPress content into beautiful, portable formats that your readers can enjoy anywhere. Whether you're a blogger sharing articles, an educator distributing materials, or a business creating reports, this plugin makes content export effortless.

= Why Choose Read Offline? =

**Professional PDF Export**
* Beautiful table of contents with automatic page numbers
* Custom branding with headers, footers, and watermarks
* Perfect formatting that looks great on any device
* Combine multiple posts into comprehensive guides

**Publishing-Ready EPUB**
* Industry-standard format compatible with all e-readers
* Custom cover images and professional metadata
* Multiple styling themes (light, dark, or custom)
* Built-in validation for perfect compatibility

**Lightning-Fast Performance**
* Smart caching - exports are generated once, served instantly
* Bulk operations - export dozens of posts at once
* No waiting around for large exports

**Privacy & Security First**
* Zero telemetry - your content stays private
* Local processing - files generated on your server
* Public downloads disabled by default
* Built-in rate limiting to prevent abuse

= Perfect For =

* **Bloggers** - Share your posts as professional PDFs or e-books
* **Businesses** - Create branded reports and documentation  
* **Educators** - Distribute course materials and reading lists
* **Authors** - Convert blog series into publishable e-books
* **Agencies** - Deliver client reports in multiple formats
* **Developers** - Export content for static sites or migration

= Key Features =

* **One-click exports** - PDF, EPUB, and Markdown formats
* **Automatic placement** or use `[read_offline]` shortcode anywhere
* **Bulk export magic** - combine multiple posts or create ZIP archives
* **Smart caching** - lightning-fast repeat downloads
* **REST API** for programmatic access
* **50+ hooks and filters** for complete customization
* **Mobile-friendly** admin interface

= How It Works =

1. **Install & Activate** - No complex setup required
2. **Configure** - Choose formats and customize appearance in Settings → Read Offline
3. **Export** - Add export buttons automatically or use the shortcode

= Rendering Engines =
* **PDF**: Powered by mPDF for professional documents
* **EPUB**: Built with PHPePub for perfect e-reader compatibility  
* **Markdown**: Lightweight converter for static site generators

All libraries are bundled - no additional setup required!

== Installation ==

**Quick Setup (3 Simple Steps)**

1. **Install the Plugin**
   Upload to `/wp-content/plugins/` or install directly from your WordPress admin under Plugins → Add New.

2. **Activate**  
   Activate the plugin through the "Plugins" screen in WordPress. All required libraries are bundled - no additional setup needed!

3. **Configure**
   Visit Settings → Read Offline to choose your default formats and customize appearance.

**That's it!** Your export buttons will appear automatically, or you can use the `[read_offline]` shortcode anywhere.

**For Developers:** Run `composer install` in the plugin folder to update vendor libraries or access testing tools.

== Frequently Asked Questions ==

= How do I add export buttons to my posts? =
Go to Settings → Read Offline and enable auto-insertion for all posts, or add the `[read_offline]` shortcode to specific posts and pages.

= Can I customize the PDF styling? =
Absolutely! Adjust margins, headers, footers, fonts, and colors in the PDF settings section. You can even add your own custom CSS.

= Are exports cached for better performance? =
Yes! Files are cached until content or settings change, ensuring lightning-fast repeat downloads for your visitors.

= Can visitors download without logging in? =
Only if you enable public REST access in settings. By default, only logged-in users can export for security.

= How do I export multiple posts at once? =
Select posts in your admin area, choose a "Read Offline" bulk action, and apply. You can create either a single combined document or a ZIP of individual files.

= I see "mPDF/PHPePub not available" =
This usually means the bundled libraries weren't installed properly. Try re-installing the plugin or run `composer install` in the plugin folder.

= The EPUB has validation errors in some e-readers =
The plugin normalizes HTML to strict XHTML and fixes common issues. Check your post content for unclosed tags or embedded scripts that might cause problems.

= Bulk export creates a single file instead of ZIP =
Uncheck the "Combine bulk exports" option under Settings → Read Offline → General if you want ZIP archives instead of combined documents.

= Can I use this with custom post types? =
Yes! The plugin works with any public post type. Configure which post types show export buttons in the settings.

= Is there an API for developers? =
Yes! Use the REST endpoint: `/wp-json/read-offline/v1/export?postId=ID&format=pdf|epub|md` 
Check out HOOKS.md for 50+ customization filters and actions.

== Screenshots ==

1. **Settings Dashboard** - Clean, organized settings with tabs and helpful tooltips
2. **Frontend Export Control** - Beautiful "Save as" buttons that appear automatically on posts  
3. **Bulk Export Interface** - Select multiple posts and export as combined documents or ZIP archives
4. **PDF Output Sample** - Professional documents with table of contents and custom branding
5. **EPUB Reader View** - Publishing-quality e-books compatible with all major e-readers

== Changelog ==

= 2.2.5 - Update Plugin =
* Add automatic plugin updates via GitHub

= 2.2.4 - Current Release =
**Stability & Documentation Update**
* Version alignment release: Updated from 0.x.y to 2.x.y numbering system
* Comprehensive documentation improvements
* Updated @since annotations throughout codebase
* No functional changes - purely organizational

= 2.2.3 - PDF Table of Contents Restored =
**Major PDF Improvements**
* **Fixed**: Restored PDF Table of Contents with hierarchical structure
* **New**: Automatic page numbers when page numbering is enabled
* **Fixed**: Removed unwanted leading blank page from PDFs
* **Enhanced**: Privacy-first approach - public REST access remains disabled by default
* **Developer**: Added `read_offline_toc_title` filter for PDF/EPUB TOC customization
* **Developer**: Added `read_offline_pdf_toc_html` filter for manual TOC fallback
* **Developer**: New `Read_Offline_Export::invalidate_post_cache()` helper for targeted cache management

= 2.2.2 - Rate Limiting Improvements =
**Better API Management**  
* **New**: Standard rate limit headers (Retry-After, X-RateLimit-Limit, etc.) in REST responses
* **Enhanced**: REST responses now include remaining quota information when rate limiting applies
* **Improved**: Better feedback for API consumers about usage limits

= 2.2.1 - Public Access & Security =
**Enhanced Security Features**
* **New**: Public REST access toggle - control whether unauthenticated users can export published posts
* **New**: Smart per-IP rate limiting for unauthenticated REST exports with configurable limits
* **Admin**: Added comprehensive REST security settings on General tab
* **Cleanup**: Removed redundant Test export tool from admin interface

= 2.2.0 - Markdown Support =
**Major Feature Addition**
* **New**: Full Markdown export support with fenced code blocks, lists, headings, links, and images
* **Admin**: Added Markdown to selectable formats with REST API examples
* **Improved**: Relocated Custom PDF CSS from General to PDF tab (with automatic migration)
* **Enhanced**: Better newline handling and standardized code formatting in Markdown
* **Internal**: Added Markdown support to cache system and filename builder
* **Note**: Bulk Markdown export coming in future release

= 2.1.1 - Security Hardening =
**Security & Validation**
* **Enhanced**: Complete settings validation and sanitization for all tabs
* **Security**: Improved escaping throughout with proper translators' comments  
* **Fixed**: Safer fallback handling when PDF custom size settings are invalid
* **Recommended**: Security-focused update for all users

= 2.0.0 - Initial Release =
**Foundation Release**
* **Core**: PDF and EPUB export functionality with professional formatting
* **API**: Full REST API with configurable access controls
* **Performance**: Smart caching system for lightning-fast repeat downloads
* **Admin**: Bulk ZIP export capabilities for multiple posts
* **UI**: Clean, intuitive interface with shortcode support

== Upgrade Notice ==

= 2.2.5 =
Preview release adding optional modern settings UI. Safe to skip if you prefer classic admin; enable flag to test.

= 2.2.4 =
Documentation and stability improvements. Recommended update for better user experience and developer documentation.

= 2.2.3 =
Major update: Restores PDF Table of Contents with page numbers and removes blank pages. Adds developer tools for cache management.

= 2.2.0 =
Major feature release: Adds full Markdown export support and improves settings organization. Recommended for all users.

= 2.1.1 =
Important security update with improved validation and escaping. Recommended for all users.
