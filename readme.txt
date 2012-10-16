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

= Implemented so far = 

*   Settings page (Settings->Read Offline)
*   Read offline widget
*   You can download a pdf, epub or mobi file containing the current post or page (you have to add the Read Offline widget to see the download links)
*   The download filename is based on the posts slug (`$post->post_name`)
*   Add meta data to the file
    *   Title (PDF, ePub and mobi)
    *   Author (PDF, ePub and mobi)
    *   Subject (mobi)
    *   Publisher (ePub)
    *   Identifier (uPub)
    *   Source URL (ePub)

= I plan to implement = 

*   Option: Move the files into a .zip file and only present the link to this .zip file
*   Option: Add download links to the top and bottom of a post or page.
*   Option: Add a custom style to the file
*   Create read-offline.pot for easy translation
*   more ? Please [post a comment](http://soderlind.no/archives/2012/10/01/read-offline/#respond) if you have any suggestions

= Credits = 

The plugin is using the following libraries

*  [Epub](http://www.phpclasses.org/package/6115), License: GNU LGPL, Attribution required for commercial implementations, requested for everything else.
*  [Zip](http://www.phpclasses.org/package/6110), License: GNU LGPL, Attribution required for commercial implementations, requested for everything else. 
*  [phpMobi](https://github.com/raiju/phpMobi), License: Apache license (version 2.0)
*  [mpdf](http://www.mpdf1.com/mpdf/index.php), License: GNU General Public License version 2 

The plugin is using the following icons

*  PDF icon from the [Fugue Icons set](http://p.yusukekamiyamane.com/) by Yusuke Kamiyamane, released under a [Creative Commons attribution license](http://creativecommons.org/licenses/by/3.0/)
*  [ePub and mobi icons](http://smithsrus.com/e-book-download-icons/) by Doug Smith, also released under a [Creative Commons attribution license](http://creativecommons.org/licenses/by/3.0/)

== Installation ==

1. Download the plugin and extract the read-online.zip
1. Upload the extracted `read-online` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 0.0.2 =
* Filename based on the posts slug
* Added meta data

= 0.0.1 =
* Initial release