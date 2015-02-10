# Read Offline #

Read Offline allows you to download or print posts and pages. You can download the post as PDF, ePub or mobi



### NOTE, this is still a beta version ###

## Features ##

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
	* Cover Page (static or per post feature image)
	* Custom CSS
* mobi (set in Read Offline->mobi)
	* Cover Page
* Print features (set in Read Offline->print)
   * Add print header text
   * Custom print style
* Permalink support (/read-offline/"postid"/"post-name"."type"). I've written a "[how-to add a permalink to your plugin](http://soderlind.no/archives/2012/11/01/wordpress-plugins-and-permalinks-how-to-use-pretty-links-in-your-plugin/)" guide at soderlind.no
* Google Analytics read-offline event tracking. You can find these under Content » Events in your Google Analytics reports. Assumes you’re using the [Asynchronous version of Google Analytics](http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html)
* languages/read-offline.po for easy translation.

## Todo ##

Issues tagged [enhancement](https://github.com/soderlind/read-offline/labels/enhancement) are planed enhancements.

## Changelog ##

Please [see the CHANGELOG.md](CHANGELOG.md) file.

## Installation ##

1. Download the plugin and extract the read-online.zip
1. Upload the extracted `read-online` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

## Use ##

Add it to the top or bottom of each post and/or page (See Settings->Read Offline)



([shortcode](https://github.com/soderlind/read-offline/issues/6) and [widget](https://github.com/soderlind/read-offline/issues/10) comming soon)

## Screenshots ##

###1. Read Offline -> General Options###
![Read Offline -> General Options](assets/screenshot-1.jpg)

###2. Read Offline -> PDF###
![Read Offline -> PDF](assets/screenshot-2.jpg)

###3. Read Offline -> Print###
![Read Offline -> Print](assets/screenshot-3.jpg)

