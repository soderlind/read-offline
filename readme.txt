=== Read Offline ===
Contributors: PerS
Donate link: http://soderlind.no/donate/
Tags: pdf, epub, mobi, print
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 0.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Read Offline allows you to download or print posts and pages. You can download the posts in PDF, ePub or mobi

== Description ==

**NOTE** This is still a beta version

= Features = 

*   Add download links to the top and bottom of a post or page (configurable in Read Offline->General Options)
*   You can download a PDF, ePub or mobi file containing the current post or page, or you can print the post / page.
*   The download filename is based on the posts slug (`$post->post_name`)
*   Adds meta data to the file
	*   Title, Author, Date, Copyright message etc.
* PDF features (set in Read Offline->PDF)
	* Paper formats (A0 - A10, B0 - B10, C0 - C10, 4A0, 2A0, RA0 - RA4, SRA0 - SRA4, Letter, Legal, Executive, Folio, Demy and Royal)
	* Table of Contents
	* Cover Page
	* Header and Footer
	* Theme or Custom CSS
	* Watermark
	* Protection
* ePub (set in Read Offline->ePub)
	* Cover Page
	* Custom CSS
* mobi (set in Read Offline->mobi)
	* Cover Page
* Print features (set in Read Offline->print)
   * Add print header text
   * Custom print style
* Permalink support (/read-offline/"postid"/"post-name"."type"). I've written a "[how-to add a permalink to your plugin](http://soderlind.no/archives/2012/11/01/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/)" guide at soderlind.no
* Google Analytics read-offline event tracking. You can find these under Content » Events in your Google Analytics reports. Assumes you’re using the [Asynchronous version of Google Analytics](http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html)
* languages/read-offline.po for easy translation.


== Installation ==

1. Download the plugin and extract the read-online.zip
1. Upload the extracted `read-online` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Use ==

There are 2 ways you can add the Read Offline link

1. Add it to the top or bottom of each post and/or page (See Settings->Read Offline)
1. Using the Read Offline widget

(shortcode will be added in the next version)

== Screenshots ==

1. Read Offline -> General Options
2. Read Offline -> PDF
3. Read Offline -> Print

== Changelog ==
= 0.2.1 =
* New Feature: Read Offline->Print->Print Style  = "The site theme style"
* Biugfixs
   * PDF: Page numbering
   * ePub: ePub-> Add cover page. Missing cover page gave error.
   * Minor fixes.
= 0.2.0 =
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
= 0.1.9 =
* Fixed a bug in permalinks that gave 404 for blogs in a subdirectory. Also removed code that gave error when downloading an ePub.
= 0.1.8 =
* Added Google Analytics read-offline event tracking. You can find these under Content » Events in your Google Analytics reports. Assumes you’re using the [Asynchronous version of Google Analytics](http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html)
= 0.1.7 =
*  Fixed a small bug
= 0.1.6 =
*  Added the option to add custom css to PDF

= 0.1.5 =
* In Settings->Read Offline, added the option to add custom css to ePub
* Added languages/read-offline.po for easy translation.

= 0.1.4 = 
* Added permalink support (/read-offline/"postid"/"post-name"."type"). I've written [a how-to add permalink to plugins guide at soderlind.no](http://soderlind.no/archives/2012/11/01/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/)
* removed the obsolete download.php

= 0.1.3 =
* epub will now validate against http://www.epubconversion.com/ePub-validator-iBook.jsp
* Added language variable to the epub file, ISO 639-1 two letter tag based on the WordPress get_locale()

= 0.1.2 =
* Fix typo in download.php, was including   "Epub.inc.php",  correct is "EPub.inc.php".

= 0.1.1 =
* bugfix

= 0.1.0 =
* Added the Read Offline shortcode
* Added, in Settings->Read Offline, option to add Read Offline to top and/or bottom of post and page

= 0.0.2 =
* Filename based on the posts slug
* Added meta data

= 0.0.1 =
* Initial release