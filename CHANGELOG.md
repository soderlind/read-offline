## Changelog ##

### 0.2.3 ###
* Reduced mPDF library size by 90% (!!), incl removing fonts. Only [DejaVu fonts](http://dejavu-fonts.org/) are included. Will add font management (i.e. option to add fonts) in later version. Please [tell me](https://github.com/soderlind/read-offline/issues/new) if this breaks the plugin.

### 0.2.2 ###
* Added localization (that is, added missing `load_plugin_textdomain()`). 

### 0.2.1 ###
* New Feature: Read Offline->Print->Print Style = "The site theme style"
* Biugfixs
   * PDF: Page numbering
   * ePub: ePub-> Add cover page. Missing cover page gave error.
   * Minor fixes.

### 0.2.0 ###
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

### 0.1.9 ###
* Fixed a bug in permalinks that gave 404 for blogs in a subdirectory. Also removed code that gave error when downloading an ePub.

### 0.1.8 ###
* Added Google Analytics read-offline event tracking. You can find these under Content » Events in your Google Analytics reports. Assumes you’re using the [Asynchronous version of Google Analytics](http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html)

### 0.1.7 ###
*  Fixed a small bug

### 0.1.6 ###
*  Added the option to add custom css to PDF

### 0.1.5 ###
* In Settings->Read Offline, added the option to add custom css to ePub
* Added languages/read-offline.po for easy translation.

### 0.1.4 ###
* Added permalink support (/read-offline/"postid"/"post-name"."type"). I've written [a how-to add permalink to plugins guide at soderlind.no](http://soderlind.no/archives/2012/11/01/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/)
* removed the obsolete download.php

### 0.1.3 ###
* epub will now validate against http://www.epubconversion.com/ePub-validator-iBook.jsp
* Added language variable to the epub file, ISO 639-1 two letter tag based on the WordPress get_locale()

### 0.1.2 ###
* Fix typo in download.php, was including   "Epub.inc.php",  correct is "EPub.inc.php".

### 0.1.1 ###
* bugfix

### 0.1.0 ###
* Added the Read Offline shortcode
* Added, in Settings->Read Offline, option to add Read Offline to top and/or bottom of post and page

### 0.0.2 ###
* Filename based on the posts slug
* Added meta data

### 0.0.1 ###
* Initial release
