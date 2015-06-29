<?php

class Read_Offline_Create extends Read_Offline {

	private static $instance;

	public static function get_instance() {

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {
		parent::get_instance();
	}


	private $author_firstlast;
	private $author_lastfirst; 
	private $subject;
	private $keywords; 
	private $generator;
	private $html;

	function init($post) {
		$this->author_firstlast = sprintf("%s %s",get_the_author_meta('user_firstname',$post->post_author),get_the_author_meta('user_lastname',$post->post_author));
		$this->author_lastfirst = sprintf("%s, %s",get_the_author_meta('user_firstname',$post->post_author),get_the_author_meta('user_lastname',$post->post_author));

		$this->subject = (count(wp_get_post_categories($post->ID))) ? implode(' ,',array_map("get_cat_name", wp_get_post_categories($post->ID))) : "";
		$this->keywords = $this->_get_taxonomies_terms($post);
		$this->generator = 'Read Offline '. READOFFLINE_VERSION . ' by Per Soderlind, http://wordpress.org/extend/plugins/read-offline/';

		// content
		$html = '<h1 class="entry-title">' . get_the_title($post->ID) . '</h1>';
		$content = $post->post_content;
		$content = preg_replace("/\[\\/?readoffline(\\s+.*?\]|\])/i", "", $content); // remove all [readonline] shortcodes
		$html .= apply_filters('the_content', $content);

		// purify content
		require_once (READOFFLINE_PATH .'/lib/htmlpurifier-4.6.0-standalone/HTMLPurifier.standalone.php');
		$htmlpurifier_config = HTMLPurifier_Config::createDefault();

		$epub_html_whitelist = array ('h1','h2','h3','h4','h5','h6', 'a', 'abbr', 'area', 'audio', 'b', 'bdi', 'bdo', 'br', 'button', 'canvas', 'cite', 'code', 'command', 'datalist', 'del', 'dfn', 'em', 'embed', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'keygen', 'label', 'map', 'mark', 'meter', 'ns1:switch', 'ns2:math', 'ns3:svg', 'object', 'output', 'progress', 'q', 'ruby', 's', 'samp', 'script', 'select', 'small', 'span', 'strong', 'sub', 'sup', 'textarea', 'time', 'u', 'var', 'video' , 'wbr');
		// //$htmlpurifier_config->set('HTML.Doctype', 'XHTML 1.1');
		$htmlpurifier_config->set('HTML.AllowedElements',$epub_html_whitelist);


		$ebub_html_attributes = array('*.accesskey', '*.aria-activedescendant', '*.aria-atomic', '*.aria-autocomplete', '*.aria-busy', '*.aria-checked', '*.aria-controls', '*.aria-describedby', '*.aria-disabled', '*.aria-dropeffect', '*.aria-expanded', '*.aria-flowto', '*.aria-grabbed', '*.aria-haspopup', '*.aria-hidden', '*.aria-invalid', '*.aria-label', '*.aria-labelledby', '*.aria-level', '*.aria-live', '*.aria-multiline', '*.aria-multiselectable', '*.aria-orientation', '*.aria-owns', '*.aria-posinset', '*.aria-pressed', '*.aria-readonly', '*.aria-relevant', '*.aria-required', '*.aria-selected', '*.aria-setsize', '*.aria-sort', '*.aria-valuemax', '*.aria-valuemin', '*.aria-valuenow', '*.aria-valuetext', '*.border', '*.class', '*.contenteditable', '*.contextmenu', '*.dir', '*.draggable', '*.dropzone', '*.hidden', '*.id', '*.lang', '*.ns1:type', '*.ns2:alphabet', '*.ns2:ph', '*.onabort', '*.onblur', '*.oncanplay', '*.oncanplaythrough', '*.onchange', '*.onclick', '*.oncontextmenu', '*.ondblclick', '*.ondrag', '*.ondragend', '*.ondragenter', '*.ondragleave', '*.ondragover', '*.ondragstart', '*.ondrop', '*.ondurationchange', '*.onemptied', '*.onended', '*.onerror', '*.onfocus', '*.oninput', '*.oninvalid', '*.onkeydown', '*.onkeypress', '*.onkeyup', '*.onload', '*.onloadeddata', '*.onloadedmetadata', '*.onloadstart', '*.onmousedown', '*.onmousemove', '*.onmouseout', '*.onmouseover', '*.onmouseup', '*.onmousewheel', '*.onpause', '*.onplay', '*.onplaying', '*.onprogress', '*.onratechange', '*.onreadystatechange', '*.onreset', '*.onscroll', '*.onseeked', '*.onseeking', '*.onselect', '*.onshow', '*.onstalled', '*.onsubmit', '*.onsuspend', '*.ontimeupdate', '*.onvolumechange', '*.onwaiting', '*.role', '*.spellcheck', '*.style', '*.tabindex', '*.title', '*.xml:base', '*.xml:lang' , '*.xml:space');
		$htmlpurifier_config->set('HTML.AllowedAttributes',$ebub_html_attributes);
		$htmlpurifier_config->set( 'AutoFormat.RemoveSpansWithoutAttributes', true );

		$htmlpurifier = new HTMLPurifier($htmlpurifier_config);
		$this->html = $htmlpurifier->purify($html);
	}

	function pprint($post) {
		$print_header = "";
		$print_css  = "";
		if ('0' != parent::$options['print']['header'] ) {
			$print_header = sprintf('BODY:before {  display: block;  content: "%s";  margin-bottom: 10px;  border: 1px solid #bbb;  padding: 3px 5px;  font-style: italic;}'
				, $this->_parse_header_footer($post, parent::$options['print']['headertext'], true)
			);

		}

		$print_style = $this->_get_child_array_key('print',parent::$options['print']['style']);
		switch ($print_style) {
			case 'theme_style':
				$print_css = file_get_contents(get_stylesheet_uri());
				break;

			case 'css':
				$print_css = ( '' != parent::$options['print']['css']) ? parent::$options['print']['css'] : '';
				break;
		}
		printf('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>%s</title><style type="text/css" media="print">%s%s</style></head><body>%s</body></html>'
			, get_the_title($post->ID)
			, $print_css
			, $print_header
			, $this->html
		);
	}


	function epub($post) {

		// require_once (READOFFLINE_PATH .'/lib/PHPePub/EPub.php');
		// //require_once "epub/EPub.inc.php";

		//$epub = new EPub(EPub::BOOK_VERSION_EPUB3);
		$epub = new Read_Offline_Epub();
		$epub->isLogging = false;
		$epub->setGenerator($this->generator);

		$epub->setTitle($post->post_title); //setting specific options to the EPub library
		$epub->setIdentifier($post->guid, EPub::IDENTIFIER_URI);
		$iso6391 = ( '' == get_locale() ) ? 'en' : strtolower( substr(get_locale(), 0, 2) ); // only ISO 639-1
		$epub->setLanguage($iso6391);
		//$epub->setAuthor($author_firstlast, $author_lastfirst); // "Firstname, Lastname", "Lastname, First names"
		$epub->setPublisher(get_bloginfo( 'name' ), get_bloginfo( 'url' ));
		$epub->setSourceURL($post->guid);


		// printf("<pre>%s</pre>",print_r(parent::$options['epub'],true));
		// exit();
		// if ('' != parent::$options['epub']['epub_cover_image'] ) {
		// 	echo parent::$options['epub']['epub_cover_image'];
		// 	exit();
		// 	$epub->setCoverImage(parent::$options['epub']['epub_cover_image']);
		// }
		$print_css = "";
		$print_style = $this->_get_child_array_key('epub',parent::$options['epub']['style']);
		switch ($print_style) {
			case 'theme_style':
				//$print_css = file_get_contents(get_stylesheet_uri());
				break;

			case 'css':
				$print_css = ( '' != parent::$options['epub']['css']) ? parent::$options['epub']['css'] : '';
				break;
		}
		if ("" != $print_css ) {
			$epub->addCSSFile("styles.css", "css1", $print_css);
			//$epub->setCoverCss($print_css);
		}
		/**
		 * Coverart
		 */
		$coverart = $this->_get_child_array_key('epub',parent::$options['epub']['art']);
		$upload_dir = wp_upload_dir();
		if ('none' != $coverart) {

			switch ($coverart) {

				case 'feature_image':
					$image_url = wp_get_attachment_url( get_post_thumbnail_id($post->ID, 'thumbnail') );
					if (false !== file_exists($image_url)) {
						$attachment_data = wp_get_attachment_metadata(get_post_thumbnail_id($post->ID, 'thumbnail'));
						printf("<pre>%s</pre>",print_r($attachment_data,true));
						$image_path = $upload_dir['basedir'] . '/' . $attachment_data['file'];
						if (count($attachment_data)) {
							$epub->setCoverImage($image_path);
						}
					}
					break;

				case 'custom_image':
					$epub->setCoverImage("Cover.jpg", file_get_contents(parent::$options['epub']['custom_image']));
					break;
			}
		}
		$epub->setDate(get_the_date( 'U', $post->ID ));
		$epub->setRights(parent::$options['copyright']['message']);


		$content_start =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
			. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
			. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
			. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
			. "<head>"
			. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
			. "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n"
			. "<title>" . $post->post_title . "</title>\n"
			. "</head>\n"
			. "<body>\n";

		$content_start = sprintf('<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="UTF-8" /><link rel="stylesheet" type="text/css" href="styles.css" /><title>%s</title></head><body>', $post->post_title );


		$content_end = "\n</body>\n</html>\n";

		$html = $this->html;


		$i = 0;
		$doc = new DOMDocument();
		@$doc->loadHTML( mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') );
		//@$doc->loadHTML($html);  // add error check
		$tags = $doc->getElementsByTagName('img');
		foreach ($tags as $tag) {
	    	$url =  $tag->getAttribute('src');

			//if (false !== ($attchment_id = $this->_get_attachment_id_from_url($url))) {
			if (false !== ($attchment_id = attachment_url_to_postid($url))) {
				$attachment_meta = wp_get_attachment_metadata( $attchment_id);
				$mime         = get_post_mime_type($attchment_id ); //$attachment_meta['sizes']['large']['mime-type'];
				$rel_url      = $attachment_meta['file'];
				$html         = str_replace($url, $rel_url , $html);
				$epub_file_id =   'epublargefile' . $i++;
				$upload_dir = wp_upload_dir();
				$epub_file    = sprintf("%s/%s",$upload_dir['basedir'],$rel_url);
				// printf("<p>
				// 	url: %s <br />
				// 	mime: %s<br />
				// 	rel_url: %s<br />
				// 	epub_file: %s<br />
				// 	exists?: %s<br />
				// 	</p>",
				// 	$url,
				// 	$mime,
				// 	$rel_url,
				// 	$epub_file,
				// 	(file_exists($epub_file)? "true" : "false")
				// );
				if (file_exists($epub_file)) {
					$epub->addLargeFile($rel_url, $epub_file_id , $epub_file, $mime);
				}
			}
		}

		// echo $content_start . $html . $content_end;

		// exit();
		$epub->addChapter("Body", "Body.html", $content_start . $html . $content_end);

		$epub->finalize();
		$zipData = $epub->sendBook($post->post_name);

	}

	function mobi($post) {
		require_once (READOFFLINE_PATH . '/lib/phpMobi/MOBIClass/MOBI.php');
		//require_once "mobi/Mobi.inc.php";
		$html = $this->html;

		$mobi = new MOBI();

		$mobi_content = new MOBIFile();
		/*
		asin
		-author
		contributor
		-description
		-imprint
		isbn
		-publisher
		published_at
		review
		rights
		-source
		-subject
		subject_code
		-title
		type
		version
		 */
		$mobi_content->set("title", $post->post_title);
		$mobi_content->set("description", parent::get_excerpt_by_id($post->ID));
		$mobi_content->set("author", $this->author_firstlast);
		$mobi_content->set("publishingdate", get_the_date( 'r', $post->ID ));

		$mobi_content->set("source", $post->guid);
		$mobi_content->set("publisher", get_bloginfo( 'name' ), get_bloginfo( 'url' ));
		$mobi_content->set("subject", $this->subject);
		$mobi_content->set("imprint", parent::$options['copyright']['message']);
		//$mobi->setOptions($options);

		if (parent::$options['mobi']['mobi_cover_image']) {
			$mobi_content->appendImage(parent::image_create_frome_image(parent::$options['mobi']['mobi_cover_image']));
			$mobi_content->appendPageBreak();
		}
		$mobi->setContentProvider($mobi_content);
		$mobi->setData($html);
		$zipData = $mobi->download($post->post_name . ".mobi");
	}


	function pdf($post) {

		$html = $this->html;

		define("_MPDF_TEMP_PATH",  READOFFLINE_CACHE . '/tmp/');
		define('_MPDF_TTFONTDATAPATH',READOFFLINE_CACHE . '/font/');
		// _MPDF_SYSTEM_TTFONTS - us when implementing font management

		require_once (READOFFLINE_PATH . '/lib/mpdf60/mpdf.php');

		$paper_format = sprintf("'%s-%s'",
				('custom_paper_format' == $this->_get_child_array_key('pdf_layout',parent::$options['pdf_layout']['paper_format'])) ? parent::$options['pdf_layout']['custom_paper_format'] : parent::$options['pdf_layout']['paper_format'],
				parent::$options['pdf_layout']['paper_orientation']
		);

		$pdf = new mPDF(
			'', // $mode=''
			$paper_format, // $format='A4'
			0,  // $default_font_size=0
			'', // $default_font=''
			15, // $mgl=15 - margin_left
			15, // $mgr=15 - margin right
			16, // $mgt=16 - margin top
			16, // $mgb=16 - margin bottom
			9,  // $mgh=9 - margin header
			9,  // $mgf=9 - margin footer
			parent::$options['pdf_layout']['paper_orientation'] // $orientation='P'
		);
		// $pdf->fontdata = array(	"dejavusanscondensed" => array(
		// 		'R' => "DejaVuSansCondensed.ttf",
		// 		'B' => "DejaVuSansCondensed-Bold.ttf",
		// 		'I' => "DejaVuSansCondensed-Oblique.ttf",
		// 		'BI' => "DejaVuSansCondensed-BoldOblique.ttf",
		// 	)
		// );


		$pdf->fontdata = array(
			"dejavusanscondensed" => array(
				'R' => "DejaVuSansCondensed.ttf",
				'B' => "DejaVuSansCondensed-Bold.ttf",
				'I' => "DejaVuSansCondensed-Oblique.ttf",
				'BI' => "DejaVuSansCondensed-BoldOblique.ttf",
				'useOTL' => 0xFF,
				'useKashida' => 75,
				),
			"dejavusans" => array(
				'R' => "DejaVuSans.ttf",
				'B' => "DejaVuSans-Bold.ttf",
				'I' => "DejaVuSans-Oblique.ttf",
				'BI' => "DejaVuSans-BoldOblique.ttf",
				'useOTL' => 0xFF,
				'useKashida' => 75,
				),
			"dejavuserif" => array(
				'R' => "DejaVuSerif.ttf",
				'B' => "DejaVuSerif-Bold.ttf",
				'I' => "DejaVuSerif-Italic.ttf",
				'BI' => "DejaVuSerif-BoldItalic.ttf",
				),
			"dejavuserifcondensed" => array(
				'R' => "DejaVuSerifCondensed.ttf",
				'B' => "DejaVuSerifCondensed-Bold.ttf",
				'I' => "DejaVuSerifCondensed-Italic.ttf",
				'BI' => "DejaVuSerifCondensed-BoldItalic.ttf",
				),
			"dejavusansmono" => array(
				'R' => "DejaVuSansMono.ttf",
				'B' => "DejaVuSansMono-Bold.ttf",
				'I' => "DejaVuSansMono-Oblique.ttf",
				'BI' => "DejaVuSansMono-BoldOblique.ttf",
				'useOTL' => 0xFF,
				'useKashida' => 75,
				)
			);

//$pdf->sans_fonts = array('dejavusanscondensed','sans','sans-serif');

		$pdf->SetTitle($post->post_title);
		$pdf->SetAuthor($this->author_firstlast);
		$pdf->SetSubject($this->subject);
		$pdf->SetKeywords($this->keywords);
		$pdf->SetCreator(parent::$options['copyright']['message']);

//							$pdf->autoScriptToLang = true;
//							$pdf->baseScript = 1;
//							$pdf->autoVietnamese = true;
//							$pdf->autoArabic = true;
		$pdf->autoLangToFont = true;

		$pdf->ignore_invalid_utf8 = true;
		$pdf->useSubstitutions=false;
		$pdf->simpleTables = true;
		$pdf->h2bookmarks = array('H1'=>0, 'H2'=>1, 'H3'=>2);
		$pdf->title2annots=true;

		/**
		 * Watermark
		 */
		$watermark = $this->_get_child_array_key('pdf_watermark',parent::$options['pdf_watermark']['watermark']);
		switch ($watermark) {
			case 'watermark_text':
				$pdf->SetWatermarkText(
					parent::$options['pdf_watermark']['watermark_text'],
					parent::$options['pdf_watermark']['watermark_tranparency']
				);
				break;

			case 'watermark_image':
				$pdf->SetWatermarkImage(
					parent::$options['pdf_watermark']['watermark_image'],
					parent::$options['pdf_watermark']['watermark_tranparency']
				);
				break;
		}


		/**
		 * Protection
		 */
		$has_protection = $this->_get_child_array_key('pdf_protection',parent::$options['pdf_protection']['protection']);
		if ('password_owner' == $has_protection) {
			$user_can = array_keys(array_intersect_key(
					array(
	                      'copy'               => 1,
	                      'print'              => 1,
	                      'modify'             => 1,
	                      'extract'            => 1,
	                      'assemble'           => 1,
	                      'print-highres'      => 1,
					),
					array_filter(parent::$options['pdf_protection']['user_can_do'])
			));
			$password_user  = parent::$options['pdf_protection']['password_user'];
			$password_owner = parent::$options['pdf_protection']['password_owner'];
			$pdf->SetProtection($user_can, $password_user, $password_owner, 128);
		}


		/**
		 * PDFA
		 */
		if ('1' == parent::$options['pdf_layout']['pdfa']) {
			/*
			PDFA Fatal Errors
			Some issues cannot be fixed automatically by mPDF and will generate fatal errors:
			- $useCoreFontsOnly is set as TRUE (cannot embed core fonts)
			BIG5, SJIS, UHC or GB fonts cannot be used (cannot be embedded)
			- Watermarks - text or image - are not permitted (transparency is disallowed so will make text unreadable)
			Using CMYK colour in functions SetTextColor() SetDrawColor() or SetFillColor()
			PNG images with alpha channel transparency ('masks' not allowed)
			encryption is enabled
			 */
			$pdf->showWatermarkText = false;
			$pdf->showWatermarkImage = false;
			$pdf->useCoreFontsOnly = false;
			$pdf->PDFA = true;
		}

		/**
		 * header and footer
		 */
		$print_css = "";
		$header    = $this->_get_child_array_key('pdf_header',parent::$options['pdf_header']['header']);
		switch ($header) {
			case 'default_header':
				if (('0' == parent::$options['pdf_header']['default_header'][0] &&
					 '0' == parent::$options['pdf_header']['default_header'][1] &&
					 '0' == parent::$options['pdf_header']['default_header'][2] )) {
					break;
				}
				$pdf->DefHeaderByName('pdfheader', array (
				    'L' => array (
				      'content' => ('0' != parent::$options['pdf_header']['default_header'][0]) ? $this->_header_footer($post, parent::$options['pdf_header']['default_header'][0]) : "",
				      'font-size' => 10,
				      'font-style' => 'B',
				      'font-family' => 'serif',
				      'color'=>'#000000'
				    ),
				    'C' => array (
				      'content' => ('0' != parent::$options['pdf_header']['default_header'][1]) ? $this->_header_footer($post, parent::$options['pdf_header']['default_header'][1]) : "",
				      'font-size' => 10,
				      'font-style' => 'B',
				      'font-family' => 'serif',
				      'color'=>'#000000'
				    ),
				    'R' => array (
				      'content' => ('0' != parent::$options['pdf_header']['default_header'][2]) ? $this->_header_footer($post, parent::$options['pdf_header']['default_header'][2]) : "",
				      'font-size' => 10,
				      'font-style' => 'B',
				      'font-family' => 'serif',
				      'color'=>'#000000'
				    ),
				    'line' => 1,
				));
				break;
			case 'custom_header':
				$pdf->DefHTMLHeaderByName(
					'pdfheader',
					$this->_parse_header_footer($post, parent::$options['pdf_header']['custom_header'])
				);
				break;
		}

		$footer = $this->_get_child_array_key('pdf_footer',parent::$options['pdf_footer']['footer']);
		switch ($footer) {
			case 'default_footer':
				if (('0' == parent::$options['pdffooter']['default_footer'][0] &&
					 '0' == parent::$options['pdffooter']['default_footer'][1] &&
					 '0' == parent::$options['pdffooter']['default_footer'][2] )) {
					break;
				}
				$pdf->DefFooterByName('pdffooter',array (
				    'L' => array (
				      'content' => ('0' != parent::$options['pdf_footer']['default_footer'][0]) ? $this->_header_footer($post, parent::$options['pdf_footer']['default_footer'][0]) : "",
				      'font-size' => 10,
				      'font-style' => 'B',
				      'font-family' => 'serif',
				      'color'=>'#000000'
				    ),
				    'C' => array (
				      'content' => ('0' != parent::$options['pdf_footer']['default_footer'][1]) ? $this->_header_footer($post, parent::$options['pdf_footer']['default_footer'][1]) : "",
				      'font-size' => 10,
				      'font-style' => 'B',
				      'font-family' => 'serif',
				      'color'=>'#000000'
				    ),
				    'R' => array (
				      'content' => ('0' != parent::$options['pdf_footer']['default_footer'][2]) ? $this->_header_footer($post, parent::$options['pdf_footer']['default_footer'][2]) : "",
				      'font-size' => 10,
				      'font-style' => 'B',
				      'font-family' => 'serif',
				      'color'=>'#000000'
				    ),
				    'line' => 1,
				));
				break;
			case 'custom_footer':
				$pdf->DefHTMLFooterByName(
					'pdffooter',
					$this->_parse_header_footer($post, parent::$options['pdf_footer']['custom_footer'])
				);
				break;
			default:
				$print_footer = "";
				break;
		}

		/**
		 * Default CSS
		 */
		if ('default_header' == $header || 'default_footer' == $footer) {
			$pdf->WriteHTML(file_get_contents( READOFFLINE_PATH. '/templates/pdf/default-print.css' ),1);
		}

		/**
		 * Theme / Custom CSS, overrides default css
		 */

		$css = $this->_get_child_array_key('pdf_css',parent::$options['pdf_css']['custom_css']);
		switch ($css) {
			case 'theme_style':
				// $post_styles = $this->_get_post_styles($post->ID);
				// $link = "";
				// foreach ($post_styles as $post_style) {
				// 	$f = file_get_contents($post_style);
				// 	if (false !== $f) {
				// 		$link = $link . "\n" . $f;
				// 	}

				// }
				// $pdf->CSSselectMedia = 'all';
				// $pdf->WriteHTML($link,1);

				$pdf->WriteHTML(file_get_contents(get_stylesheet_uri()),1);
				break;
			case 'css':
				$pdf->WriteHTML(parent::$options['pdf_css']['css'],1);
				break;

		}

		/**
		 * Coverart
		 */
		$coverart = $this->_get_child_array_key('pdf_cover',parent::$options['pdf_cover']['art']);

		if ('none' != $coverart) {
			// $paper_format = ('custom_paper_format' == $this->_get_child_array_key('pdf_layout',parent::$options['pdf_layout']['paper_format'])) ? parent::$options['pdf_layout']['custom_paper_format'] : parent::$options['pdf_layout']['paper_format'];
			// $dimensions = $pdf->_getPageFormat($paper_format);

			// $w = floor($dimensions[0] / _MPDFK);
			// $h = floor($dimensions[1] / _MPDFK);

			switch ($coverart) {

				case 'feature_image':
					$image_url = wp_get_attachment_url( get_post_thumbnail_id($post->ID, 'thumbnail') );
					// $image_data = wp_get_attachment_metadata(get_post_thumbnail_id($post->ID, 'thumbnail'));
					// $left = ($w / 2) - ($image_data['width']  / 2);
					// $top  = ($h / 2) - ($image_data['height'] / 2);
					//$pdf->AddPage('','','','','on');
					if ('' != $image_url) {
						$pdf->AddPageByArray(array(
					    	'suppress' => 'on', // supress header
					    ));
						$pdf->WriteHTML(
							sprintf('
								<div style="position: absolute; left:0; right: 0; top: 0; bottom: 0;">
									<img src="%s" style="width: 210mm; height: 297mm; margin: 1mm;" />
								</div>',
							$image_url
							)
						);
					}
					break;

				case 'custom_image':
					$image_url = parent::$options['pdf_cover']['custom_image'];
					// $image_data = wp_get_attachment_metadata(get_post_thumbnail_id($post->ID, 'thumbnail'));
					// $left = ($w / 2) - ($image_data['width']  / 2);
					// $top  = ($h / 2) - ($image_data['height'] / 2);
					//$pdf->AddPage('','','','','on');
					if ('' != $image_url) {
						$pdf->AddPageByArray(array(
					    	'suppress' => 'on', // supress header
					    ));
						$pdf->WriteHTML(sprintf('<div style="position: absolute; left:0; right: 0; top: 0; bottom: 0;">
							<img src="%s" style="width: 210mm; height: 297mm; margin: 30;" /></div>',$image_url)
						);
					}
					break;
			}
			// we don't want watermarks on the cover page
			$pdf->showWatermarkImage = false;
			$pdf->showWatermarkText  = false;
		}

		$toc = $this->_get_child_array_key('pdf_layout',parent::$options['pdf_layout']['add_toc']);
		$pdf->AddPageByArray(array(
		    'suppress' => 'off', // don't supress headers
		    'ohname' => ('0' != $header ) ? ('custom_header' == $header) ? 'html_pdfheader' : 'pdfheader' : '',
		    'ehname' => ('0' != $header ) ? ('custom_header' == $header) ? 'html_pdfheader' : 'pdfheader' : '',
		    'ofname' => ('0' != $footer ) ? ('custom_footer' == $footer) ? 'html_pdffooter' : 'pdffooter' : '',
		    'efname' => ('0' != $footer ) ? ('custom_footer' == $footer) ? 'html_pdffooter' : 'pdffooter' : '',
		    'ohvalue' => ('0' != $header ) ? 1 : 0,
		    'ehvalue' => ('0' != $header ) ? 1 : 0,
		    'ofvalue' => ('0' != $footer ) ? 1 : 0,
		    'efvalue' => ('0' != $footer ) ? 1 : 0,
		    'resetpagenum' =>  ('0' != $toc) ? 2 : 1,
	    ));

	    /**
	     * Table og contents
	     */

	    if ('0' !== $toc ) {
	    	$toc_start = ('0' == parent::$options['pdf_layout']['toc'][0]) ? 1 : parent::$options['pdf_layout']['toc'][0];
	    	$toc_stop  = ('0' == parent::$options['pdf_layout']['toc'][1]) ? 2 : parent::$options['pdf_layout']['toc'][1];
	    	if ($toc_start > $toc_stop) {
	    		$toc_stop = $toc_start + 1;
	    	}
	    	$toc_arr = array();
	    	$j = 0;
	    	for ($i = $toc_start; $i <= $toc_stop; $i++) {
	    		$toc_arr[sprintf('H%s',$i)] = $j++;
	    	}
		    $pdf->h2toc       = $toc_arr;
			$pdf->TOCpagebreakByArray(array(
			    // 'tocfont' => '',
			    // 'tocfontsize' => '',
			    // 'outdent' => '2em',
			    'TOCusePaging' => true,
			    'TOCuseLinking' => true,
			    // 'toc_orientation' => '',
			    // 'toc_mgl' => '',
			    // 'toc_mgr' => '',
			    // 'toc_mgt' => '',
			    // 'toc_mgb' => '',
			    // 'toc_mgh' => '',
			    // 'toc_mgf' => '',
			    // 'toc_ohname' => ('0' != $header ) ? ('custom_header' == $header) ? 'html_pdfheader' : 'pdfheader' : '',
			    // 'toc_ehname' => ('0' != $header ) ? ('custom_header' == $header) ? 'html_pdfheader' : 'pdfheader' : '',
			    // 'toc_ofname' => ('0' != $footer ) ? ('custom_footer' == $footer) ? 'html_pdffooter' : 'pdffooter' : '',
			    // 'toc_efname' => ('0' != $footer ) ? ('custom_footer' == $footer) ? 'html_pdffooter' : 'pdffooter' : '',
			    // 'toc_ohvalue' => ('0' != $header ) ? 1 : 0,
			    // 'toc_ehvalue' => ('0' != $header ) ? 1 : 0,
			    // 'toc_ofvalue' => ('0' != $footer ) ? 1 : 0,
			    // 'toc_efvalue' => ('0' != $footer ) ? 1 : 0,
			    'toc_ohvalue' => -1,
			    'toc_ehvalue' => -1,
			    'toc_ofvalue' => -1,
			    'toc_efvalue' => -1,
			    'toc_preHTML' => __('<h1>Contents</h1>', 'read-offline'),
			    'toc_postHTML' => '',
			    'toc_bookmarkText' => __('Contents', 'read-offline'),
			    'resetpagenum' => 2,
			    'pagenumstyle' => '',
			    'suppress' => 'off',
			    'orientation' => '',
			    // 'mgl' => '',
			    // 'mgr' => '',
			    // 'mgt' => '',
			    // 'mgb' => '',
			    // 'mgh' => '',
			    // 'mgf' => '',
			    // 'ohname' => ('0' != $header ) ? ('custom_header' == $header) ? 'html_pdfheader' : 'pdfheader' : '',
			    // 'ehname' => ('0' != $header ) ? ('custom_header' == $header) ? 'html_pdfheader' : 'pdfheader' : '',
			    // 'ofname' => ('0' != $footer ) ? ('custom_footer' == $footer) ? 'html_pdffooter' : 'pdffooter' : '',
			    // 'efname' => ('0' != $footer ) ? ('custom_footer' == $footer) ? 'html_pdffooter' : 'pdffooter' : '',
			    // 'ohvalue' => ('0' != $header ) ? 1 : 0,
			    // 'ehvalue' => ('0' != $header ) ? 1 : 0,
			    // 'ofvalue' => ('0' != $footer ) ? 1 : 0,
			    // 'efvalue' => ('0' != $footer ) ? 1 : 0,
			    // 'toc_id' => 0,
			    // 'pagesel' => '',
			    // 'toc_pagesel' => '',
			    // 'sheetsize' => '',
			    // 'toc_sheetsize' => '',
			));
		}
		// if waters are set, show them
		$pdf->showWatermarkImage = true;
		$pdf->showWatermarkText  = true;

		$pdf->WriteHTML($html);
		$pdf->Output($post->post_name . ".pdf", 'D');

	}


	private function _get_child_array_key($parent_element,$org){
		//$org: #fieldrow-pdf_header_default_header
		if (false !== strpos($org, '#fieldrow')) {
			if (false !== strpos($org, ',')) {
				$parts = explode(',', $org);
				$org   = $parts[0];
			}
    		return str_replace('#fieldrow-' . $parent_element . '_', '', $org);
		} else {
			return $org;
		}
	}

	private function _header_footer($post, $type) {
		$val = "";
		switch ($type) {
			case 'document_title':
				$val = $post->post_title;
			break;
			case 'author':
				$val = get_the_author_meta('display_name',$post->post_author);
			break;
			case 'document_url':
				$val = get_permalink($post->ID);
			break;
			case 'site_url':
				$val = home_url();
			break;
			case 'site_title':
				$val = get_bloginfo('name');
			break;
			case 'page_number':
				$val = '{PAGENO}/{nbpg}';
			break;
			case 'date':
				$val = get_the_date(get_option('date_format'), $post->ID);
			break;
		}
		return $val;
	}

	private function _parse_header_footer($post, $html, $strip_tages = false) {
		// {DATE}, {TODAY}, {TITLE}, {AUTHOR}, {DOCURL}, {SITENAME}, {SITEURL}
		if (false !== $strip_tages) {
			$html= addslashes(strip_tags($html));
		}

		$html = str_replace('{DATE}',     get_the_date(get_option('date_format'), $post->ID),     $html);
		$html = str_replace('{TODAY}',    sprintf('{DATE %s}',get_option('date_format')),         $html);
		$html = str_replace('{TITLE}',    $post->post_title,                                      $html);
		$html = str_replace('{AUTHOR}',   get_the_author_meta('display_name',$post->post_author), $html);
		$html = str_replace('{DOCURL}',   get_permalink($post->ID),                               $html);
		$html = str_replace('{SITENAME}', get_bloginfo('name'),                                   $html);
		$html = str_replace('{SITEURL}',  home_url(),                                             $html);
		return $html;
	}


	// get taxonomies terms links
	private function _get_taxonomies_terms($post) {
		// get post type by post
		$post_type = $post->post_type;

		// get post type taxonomies
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		$out = array();
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ){
			// get the terms related to post
			$terms = get_the_terms( $post->ID, $taxonomy_slug );
			if ( !empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$out[] = $term->name;
				}
			}
		}

		return (count($out)) ? implode(', ', $out ) : "";
	}

	// from https://philipnewcomer.net/2012/11/get-the-attachment-id-from-an-image-url-in-wordpress/
	private function _get_attachment_id_from_url( $attachment_url = '' ) {
		global $wpdb;
		$attachment_id = false;
		// If there is no url, return.
		if ( '' == $attachment_url )
			return;
		// Get the upload directory paths
		$upload_dir_paths = wp_upload_dir();
		// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
		if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {
			// If this is the URL of an auto-generated thumbnail, get the URL of the original image
			$attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
			// Remove the upload path base directory from the attachment URL
			$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );
			// Finally, run a custom database query to get the attachment ID from the modified attachment URL
			$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );
		}
		return $attachment_id;
	}

	private function _get_post_styles($post_id) {
		$transient_id = 'read_offline_post_styles_' . $post_id;
		$transient = get_transient( $transient_id );
		return get_transient( $transient_id );
	}
}



require_once (READOFFLINE_PATH .'/lib/PHPePub/EPub.php');

class Read_Offline_Epub extends EPub {


    private $isFinalized = FALSE;
    private $isCoverImageSet = FALSE;
	private $fileList = array();
	private $docRoot = NULL;
	private $custom_css = '';
	function __construct() {
		parent::__construct(parent::BOOK_VERSION_EPUB3);
	}


    /**
     * Add a cover image to the book.
     * If the $imageData is not set, the function assumes the $fileName is the path to the image file.
     *
     * The styling and structure of the generated XHTML is heavily inspired by the XHTML generated by Calibre.
     *
     * @param string $fileName Filename to use for the image, must be unique for the book.
     * @param string $imageData Binary image data
     * @param string $mimetype Image mimetype, such as "image/jpeg" or "image/png".
     * @return bool $success
     */
    function setCoverImage($fileName, $imageData = NULL, $mimetype = NULL, $customCSS = NULL) {
        if ($this->isFinalized || $this->isCoverImageSet || array_key_exists("CoverPage.html", $this->fileList)) {
            return FALSE;
        }
        if ($imageData == NULL) {
            // assume $fileName is the valid file path.
            if (!file_exists($fileName)) {
                // Attempt to locate the file using the doc root.
                $rp = realpath($this->docRoot . "/" . $fileName);
               if ($rp !== FALSE) {
                    // only assign the docroot path if it actually exists there.
                    $fileName = $rp;
                }
            }
            $image = $this->getImage($fileName);
			$imageData = $image['image'];
            $mimetype = $image['mime'];
            $fileName = preg_replace("#\.[^\.]+$#", "." . $image['ext'], $fileName);
        }
        $path = pathinfo($fileName);
        $imgPath = "images/" . $path["basename"];
        if (empty($mimetype) && file_exists($fileName)) {
            list($width, $height, $type, $attr) = getimagesize($fileName);
            $mimetype = image_type_to_mime_type($type);
        }
        if (empty($mimetype)) {
            $ext = strtolower($path['extension']);
            if ($ext == "jpg") {
                $ext = "jpeg";
            }
            $mimetype = "image/" . $ext;
        }
		$coverPage = "";
		if ($this->isEPubVersion2()) {
			$coverPage = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
				. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
				. "  \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
				. "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:epub=\"http://www.idpf.org/2007/ops\" xml:lang=\"en\">\n"
				. "\t<head>\n"
				. "\t\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"/>\n"
				. "\t\t<title>Cover Image</title>\n"
				. "\t\t<link type=\"text/css\" rel=\"stylesheet\" href=\"Styles/CoverPage.css\" />\n"
				. "\t</head>\n"
				. "\t<body>\n"
				. "\t\t<div>\n"
				. "\t\t\t<img class=\"coverpage\" src=\"" . $imgPath . "\" alt=\"Cover image\" style=\"height: 100%\"/>\n"
				. "\t\t</div>\n"
				. "\t</body>\n"
				. "</html>\n";
		} else {
		    $coverPage = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
				. "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:epub=\"http://www.idpf.org/2007/ops\">\n"
				. "<head>"
				. "\t<meta http-equiv=\"Default-Style\" content=\"text/html; charset=utf-8\" />\n"
				. "\t\t<title>Cover Image</title>\n"
				. "\t\t<link type=\"text/css\" rel=\"stylesheet\" href=\"Styles/CoverPage.css\" />\n"
				. "\t</head>\n"
				. "\t<body>\n"
				. "\t\t<section epub:type=\"cover\">\n"
				. "\t\t\t<img class=\"coverpage\" src=\"" . $imgPath . "\" alt=\"Cover image\" style=\"height: 100%\"/>\n"
				. "\t\t</section>\n"
				. "\t</body>\n"
				. "</html>\n";
		}
		// if ('' !== $this->custom_css) {
		// 	$coverPageCss = $this->custom_css;
		// } else { 
		$coverPageCss = "@page, body, div, img {\n"
				. "\tpadding: 0pt;\n"
				. "\tmargin:0pt;\n"
				. "}\n\nbody {\n"
				. "\ttext-align: center;\n"
				. "}\n"
				. "\nimg {\n"
				. "\theight:100%;\n"
				. "\tmax-height:100%;\n"
				. "\tmax-width:100%;\n"
				. "}\n";
		//}
		$this->addCSSFile("Styles/CoverPage.css", "CoverPageCss", $coverPageCss);
        $this->addFile($imgPath, "CoverImage", $imageData, $mimetype);
		$this->addReferencePage("CoverPage", "CoverPage.xhtml", $coverPage, "cover");
        $this->isCoverImageSet = TRUE;
        return TRUE;
    }

    function setCoverCss($css) {
    	$this->custom_css = $css;
    }
}