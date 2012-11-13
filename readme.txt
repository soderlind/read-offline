=== Read Offline ===
Contributors: PerS
Donate link: http://soderlind.no/donate/
Tags: pdf, epub, mobi
Requires at least: 3.4
Tested up to: 3.4.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Read Offline allows you to download and read posts and pages offline. You can download the post in PDF, ePub or mobi

== Description ==

Read Offline allows you to download and read posts and pages offline. You can download the post in PDF, ePub or mobi

This is an early version, please [post bugs and feature requests](http://soderlind.no/archives/2012/10/01/read-offline/#respond)

= Features = 

*   Settings page (Settings->Read Offline)
*   Read Offline widget
*   Read Offline shortcode
*   You can download a pdf, epub or mobi file containing the current post or page (you have to add the Read Offline widget to see the download links)
*   The download filename is based on the posts slug (`$post->post_name`)
*   Adds meta data to the file
    *   Title (PDF, ePub and mobi)
    *   Author (PDF, ePub and mobi)
    *   Subject (mobi)
    *   Publisher (ePub)
    *   Identifier (uPub)
    *   Source URL (ePub)
    *   Language (ePub)
*   Option: Add download links to the top and bottom of a post or page.
*   Permalink support (/read-offline/"postid"/"post-name"."type"). I've written a "[how-to add a permalink to your plugin](http://soderlind.no/archives/2012/11/01/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/)" guide at soderlind.no
*   Option: Add a custom style to the ePub / PDF file
*   languages/read-offline.po for easy translation.

= To-do = 

*   [Bookmark posts](http://soderlind.no/archives/2012/10/01/read-offline/#comment-209934)
*	Export post / page as Word (.docx)
*   more ? Please [post a comment](http://soderlind.no/archives/2012/10/01/read-offline/#respond) if you have any suggestions

= Credits = 

Libraries

*  [Epub](http://www.phpclasses.org/package/6115), License: GNU LGPL, Attribution required for commercial implementations, requested for everything else.
*  [Zip](http://www.phpclasses.org/package/6110), License: GNU LGPL, Attribution required for commercial implementations, requested for everything else. 
*  [phpMobi](https://github.com/raiju/phpMobi), License: Apache license (version 2.0)
*  [mpdf](http://www.mpdf1.com/mpdf/index.php), License: GNU General Public License version 2 

Icons

*  PDF icon from the [Fugue Icons set](http://p.yusukekamiyamane.com/) by Yusuke Kamiyamane, released under a [Creative Commons attribution license](http://creativecommons.org/licenses/by/3.0/)
*  [ePub and mobi icons](http://smithsrus.com/e-book-download-icons/) by Doug Smith, also released under a [Creative Commons attribution license](http://creativecommons.org/licenses/by/3.0/)

== Installation ==

1. Download the plugin and extract the read-online.zip
1. Upload the extracted `read-online` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Use ==

There are 3 ways you can add the Read Offline link

1. At the top or bottom of each post and/or page (See Settings->Read Offline)
1. Using the Read Offline widget
1. Using the `[readoffline]` shortcode

The `[readoffline]` shortcode has the following parameters

* format="epub", default: format="pdf,epub,mobi"
* text="Read %title offline:", default: text="". `%title%` will be replaced with the post or page title 
* icononly="true", default="false"

Examples

* `[readoffline]` is the same as `[readoffline text="" format="pdf,epub,mobi" icononly="false"]`
* `[readoffline text="Download %title%:" format="epub"]`

== Style ==

You can modify the look using the included style sheet, read-offline.css:

`
div.readoffline-shortcode {
	margin-bottom: 10px;
	font-size: .8em !important;
}

.readoffline-shortcode div {
	padding: 0 0 1px 20px;
	display: inline;
}

div.readoffline-shortcode-text {
	padding-left: 0;	
}

.readoffline-embed {
	margin-bottom: 10px;
	font-size: .8em !important;
}

.readoffline-embed div {
	padding: 0 0 1px 20px;
	display: inline;
}

div.readoffline-embed-text {
	padding-left: 0;	
}

.readoffline-widget {

}


.readoffline-widget div {
	display: inline;
}
`

== Screenshots ==

1. Example post
2. Read Offline widget
3. Read Offline settings page

== Changelog ==

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