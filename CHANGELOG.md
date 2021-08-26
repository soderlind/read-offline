## Change Log

### 0.9.14

* Housekeeping

### 0.9.13

* Update mPDF to version 8.0.12

### 0.9.12

* Remove buildTOC, TOC is in the .ncx file. Only add chapters with content

### 0.9.11

* Housekeeping

### 0.9.10

* Add missing H1

### 0.9.9
* Set EPUB chapter autosplit to false

### 0.9.7

* Set FS_CHMOD_DIR if not defined

### 0.9.6

* Add license and copyright

### 0.9.5

* Upgrade mPDF tol v8.0.10 (PHP 8 support)
* Don't test symfony/polyfill-mbstring if PHP < 8

### 0.9.4

* Tested up to WP 5.7
* Revert to mPDF 8.0.6, 8.0.10 isn't compatible with PHP < 8.0

### 0.9.3

* Update mPDF to version 8.0.10 (supports PHP 8)

### 0.9.2

* Fix bug in _url_exists

### 0.9.1

* Housekeeping

### 0.9.0

* Update Admin Page Framework to v3.8.26

### 0.8.2

* Fix ePub validation errors

### 0.8.1

* Fix epub validation errors
* Add changes after fork
* Update soderlind/phpepub to version 4.0.8.5
* Require PHP 7.3

### 0.8.0

* Require PHP 7.3
* Update mPDF to version 8.0.6
* PHPePub supports PHP 7.3

### 0.7.7

* [Prevents formats not selected in plugin settings from being saved](https://github.com/soderlind/read-offline/pull/82)
* [Fixes query url of attachment to use site url instead of home](https://github.com/soderlind/read-offline/pull/79) (allows alternate site url to not break the plugin)

### 0.7.6

* Tested & found compatible with WP 4.7.

### 0.7.5

* Linted CSS files

### 0.7.4

* Removed "XX" that was prefixed to the archive title.

### 0.7.3

* FIX: Option to save, or not, to media library.
* ADD: Annotations for PDF. Converts foot- / endnotes to annotations. Enable in Read Offline->PDF
* ADD: "Don't include content" using a wrapper with `class="not-readoffline"`, eg: `<span class="not-readoffline"> don't include this text in the PDF/ePub/mobi</span>`

### 0.7.2

* ADD: Support for custom post type

### 0.7.1

* FIX: Bug in Table of Contents settings for ePub and mobi.

### 0.7.0

* Add `[pdf]`, `[epub]`, `[mobi]` and `[print]` shortcodes.

### 0.6.4

* Add, for ePub and mobi table of contents, option to select all headers (h1-h6).
* Use `wp_safe_remote_get()` instead og `wp_remote_get()`

### 0.6.3

* Fix load feature image for ePub.
* Tested & found compatible with WP 4.6.

### 0.6.2

* Remove notice that you should upgrade to PHP 5.6 (bur really, you should).

### 0.6.1

* Add RTL for PDF, ePub and print. PDF needs the [Read Offline Fonts](https://github.com/soderlind/read-offline-fonts) add-on plugin.

### 0.6.0

* Add support for all mPDF fonts via the [Read Offline Fonts](https://github.com/soderlind/read-offline-fonts) add-on plugin
* Update mPDF to version 6.1

### 0.5.0

* Add Table of Contents to ePub and mobi, default off. Set it in Read Offline->ePub and Read Offline->mobi
* Readded mobi cover page (kind of catch 22, you must have Table of Contents to get a cover page)

### 0.4.1

* Added missing folder

### 0.4.0

* Fix ePub and mobi bugs
* Add option in admin to select if you want to cache pdf, epub or mobi files in the Media Libray, default is "No"
* Update PHPePub to version 4.0.7
* Update phpMobi to latest version
* Remove HTMLPurifier

### 0.3.1

* Fixed HTML purification (previous version stripped html P-tags, sorry). html-purify is now only used when creating ePub

### 0.3.0

* A lot of changes since last commit, please see [CHANGELOG.md](https://github.com/soderlind/read-offline/blob/master/CHANGELOG.md) at GitHub

### 0.2.8

* ePub: rewrote routine for embedding images

### 0.2.7

* ePub: Added option to add Featured Image as a coverpage
* ePub: Fixed bug with adding images
* Read Offline ePub validates using the [EPUB Validator](http://validator.idpf.org/)

### 0.2.6

* Read Offline now works with Pages

### 0.2.5

* Solved a bug that prevented a user from adding a custom css to PDF

### 0.2.4

* Solved a bug that prevented a user from adding a custom css to ePub

### 0.2.3

* Reduced mPDF library size by 90% (!!), incl removing fonts. Only [DejaVu fonts](http://dejavu-fonts.org/) are included. Will add font management (i.e. option to add fonts) in later version. Please [tell me](https://github.com/soderlind/read-offline/issues/new) if this breaks the plugin.

### 0.2.2

* Added localization (that is, added missing `load_plugin_textdomain()`)

### 0.2.1

* New Feature: Read Offline->Print->Print Style  = "The site theme style"
* Biugfixs
   * PDF: Page numbering
   * ePub: ePub-> Add cover page. Missing cover page gave error.
   * Minor fixes.

### 0.2.0

* **Complete rewrite**. NOTE, I haven't added support for the `[readoffline]` shortcodes in this version, it will be added in 0.3.0
* Added more PDF features
   * Paper formats
   * Table of Contents
   * Cover Page
   * Header and Footer
   * Use Theme or Custom CSS
   * Add Watermark
   * Add Protection
* **Print**: In addition to downloading a PDF, ePub or mobi, you can now print the page
   * Add print header text
   * Custom print style
* Updated libraries should give better UTF-8 support for PDF, ePub and mobi.

### 0.1.9

* Fixed a bug in permalinks that gave 404 for blogs in a subdirectory. Also removed code that gave error when downloading an ePub.

### 0.1.8

* Added Google Analytics read-offline event tracking. You can find these under Content » Events in your Google Analytics reports. Assumes you’re using the [Asynchronous version of Google Analytics](http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html)

### 0.1.7

*  Fixed a small bug

### 0.1.6

*  Added the option to add custom css to PDF


### 0.1.5

* In Settings->Read Offline, added the option to add custom css to ePub
* Added languages/read-offline.po for easy translation.


### 0.1.4

* Added permalink support (/read-offline/"postid"/"post-name"."type"). I've written [a how-to add permalink to plugins guide at soderlind.no](http://soderlind.no/archives/2012/11/01/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/)
* removed the obsolete download.php


### 0.1.3

* epub will now validate against http://www.epubconversion.com/ePub-validator-iBook.jsp
* Added language variable to the epub file, ISO 639-1 two letter tag based on the WordPress get_locale()


### 0.1.2

* Fix typo in download.php, was including   "Epub.inc.php",  correct is "EPub.inc.php".


### 0.1.1

* bugfix


### 0.1.0

* Added the Read Offline shortcode
* Added, in Settings->Read Offline, option to add Read Offline to top and/or bottom of post and page


### 0.0.2

* Filename based on the posts slug
* Added meta data


### 0.0.1

* Initial release