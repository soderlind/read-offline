<?php
use PHPePub\Core\EPub;
use PHPePub\Core\EPubChapterSplitter;
use PHPePub\Core\Logger;
use PHPePub\Core\Structure\OPF\DublinCore;
use PHPePub\Core\Structure\OPF\MetaValue;
use PHPePub\Helpers\CalibreHelper;
use PHPePub\Helpers\URLHelper;
use PHPZip\Zip\File\Zip;

// require_once READOFFLINE_PATH . '/lib/phpMobi/MOBIClass/MOBI.php';

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

	function init( $post ) {
		if ( '' !== get_the_author_meta( 'user_firstname', $post->post_author ) && '' !== get_the_author_meta( 'user_lastname', $post->post_author ) ) {
			$this->author_firstlast = sprintf( '%s %s', get_the_author_meta( 'user_firstname', $post->post_author ), get_the_author_meta( 'user_lastname', $post->post_author ) );
			$this->author_lastfirst = sprintf( '%s, %s', get_the_author_meta( 'user_firstname', $post->post_author ), get_the_author_meta( 'user_lastname', $post->post_author ) );
		} else {
			$this->author_firstlast = get_the_author_meta( 'display_name', $post->post_author );
			$this->author_lastfirst = get_the_author_meta( 'display_name', $post->post_author );
		}

		$this->subject   = ( count( wp_get_post_categories( $post->ID ) ) ) ? implode( ' ,', array_map( 'get_cat_name', wp_get_post_categories( $post->ID ) ) ) : '';
		$this->keywords  = $this->_get_taxonomies_terms( $post );
		$this->generator = 'Read Offline ' . READOFFLINE_VERSION . ' by Per Soderlind, http://wordpress.org/extend/plugins/read-offline/';

		// content
		$this->html   = '<h1 class="entry-title">' . get_the_title( $post->ID ) . '</h1>';
		$content     = $post->post_content;
		$content     = preg_replace( '/\[\\/?(pdf|epub|mobi|print)(\\s+.*?\]|\])/i', '', $content ); // remove all [readonline] shortcodes
		$this->html .= $this->_remove_dont_readoffline( apply_filters( 'the_content', $content ) );
	}

	function pprint( $post ) {
		$print_header = '';
		$print_css    = '';
		if ( '0' != parent::$options['print']['header'] ) {
			$print_header = sprintf(
				'BODY:before {  display: block;  content: "%s";  margin-bottom: 10px;  border: 1px solid #bbb;  padding: 3px 5px;  font-style: italic;}',
				$this->_parse_header_footer( $post, parent::$options['print']['headertext'], true )
			);

		}

		$print_style = $this->_get_child_array_key( 'print', parent::$options['print']['style'] );
		switch ( $print_style ) {
			case 'theme_style':
				$print_css = file_get_contents( get_stylesheet_uri() );
				break;

			case 'css':
				$print_css = ( '' != parent::$options['print']['css'] ) ? parent::$options['print']['css'] : '';
				break;
		}
		printf(
			'<!DOCTYPE html><html%s><head><meta charset="UTF-8"><title>%s</title><style type="text/css" media="print">%s%s</style></head><body>%s</body></html>',
			( is_rtl() ) ? " dir='rtl'" : '',
			get_the_title( $post->ID ),
			$print_css,
			$print_header,
			$this->html
		);
	}


	function epub( $post ) {

		$iso6391 = ( '' == get_locale() ) ? 'en' : strtolower( substr( get_locale(), 0, 2 ) ); // only ISO 639-1
		if ( is_rtl() ) {
			$writing_direction = EPub::DIRECTION_RIGHT_TO_LEFT;
		} else {
			$writing_direction = EPub::DIRECTION_LEFT_TO_RIGHT;
		}

		$epub            = new EPub( EPub::BOOK_VERSION_EPUB3, $iso6391, $writing_direction );
		$epub->isLogging = false; // @codingStandardsIgnoreLine
		/*
		 * Social DRM:
		 * - Page 2 contains an Ex Libris (image with customer name), that can be customised per publisher and per customer.
		 * - Every chapter ends with a personalised footer text.
		 * - The last page contains a disclaimer and logo, and has a corresponding entry in the table of contents.
		 */
		$epub->setGenerator( $this->generator );
		$epub->setTitle( $post->post_title ); // setting specific options to the EPub library
		$epub->setIdentifier( $post->guid, EPub::IDENTIFIER_URI );
		$epub->setLanguage( $iso6391 );
		$epub->setAuthor( $this->author_firstlast, $this->author_lastfirst ); // "Firstname Lastname", "Lastname, First names"
		$epub->setPublisher( get_bloginfo( 'name' ), get_bloginfo( 'url' ) );
		$epub->setSourceURL( $post->guid );
		$epub->rootLevel();

		$print_css   = '';
		$print_style = $this->_get_child_array_key( 'epub', parent::$options['epub']['style'] );
		switch ( $print_style ) {
			case 'theme_style':
				// $print_css = file_get_contents(get_stylesheet_uri());
				break;

			case 'css':
				$print_css = ( '' != parent::$options['epub']['css'] ) ? parent::$options['epub']['css'] : '';
				break;
		}
		if ( '' != $print_css ) {
			$epub->addCSSFile( 'styles.css', 'css1', $print_css );
			// $epub->setCoverCss($print_css);
		}
		/**
		 * Coverart
		 */
		$coverart   = $this->_get_child_array_key( 'epub', parent::$options['epub']['art'] );
		$upload_dir = wp_upload_dir();
		if ( 'none' != $coverart ) {

			switch ( $coverart ) {

				case 'feature_image':
					$image_url = wp_get_attachment_url( get_post_thumbnail_id( $post->ID, 'thumbnail' ) );
					if ( false !== $this->_url_exists( $image_url ) ) {
						$attachment_data = wp_get_attachment_metadata( get_post_thumbnail_id( $post->ID, 'thumbnail' ) );

						$image_path = $upload_dir['basedir'] . '/' . $attachment_data['file'];
						if ( count( $attachment_data ) ) {
							$epub->setCoverImage( $image_path );
						}
					}
					break;

				case 'custom_image':
					$epub->setCoverImage( 'Cover.jpg', file_get_contents( parent::$options['epub']['custom_image'] ) );
					break;
			}
		}
		$epub->setDate( get_the_date( 'U', $post ) );
		$epub->setRights( parent::$options['copyright']['message'] );

		/*
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
		*/
		if ( '' != $print_css ) {
			$content_start = sprintf( '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="UTF-8" /><link rel="stylesheet" type="text/css" href="styles.css" /><title>%s</title></head><body%s>', $post->post_title, ( is_rtl() ) ? " dir='rtl'" : '' );
		} else {
			$content_start = sprintf( '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="UTF-8" /><title>%s</title></head><body%s>', $post->post_title, ( is_rtl() ) ? " dir='rtl'" : '' );
		}
		$content_end = "\n</body>\n</html>\n";

		$cover = $content_start . sprintf( "<h1>%s</h1>\n<h2>%s: %s</h2>\n", $post->post_title, _x( 'By', 'Rererence between title and author: Title By: Author Name' ), $this->author_firstlast ) . $content_end;
		$epub->addChapter( 'Cover', 'Cover.xhtml', $cover );

		/*
		$epub->addFileToMETAINF("com.apple.ibooks.display-options.xml", "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<display_options>\n    <platform name=\"*\">\n        <option name=\"fixed-layout\">true</option>\n        <option name=\"interactive\">true</option>\n        <option name=\"specified-fonts\">true</option>\n    </platform>\n</display_options>");
		*/

		$html = $this->html;

		$add_toc = $this->_get_child_array_key( 'epub', parent::$options['epub']['add_toc'] );
		$toc     = $this->_get_child_array_key( 'epub', parent::$options['epub']['toc'] );
		$content = array();
		if ( 0 !== $add_toc && 0 !== $toc ) {
			$content = $this->_split_content( $html, $toc );
			if ( 0 != count( $content ) ) {
				$chapter_num = 1;
				// $epub->buildTOC( null, 'toc', __( 'Table of Contents', 'read-offline' ), true, false );
				$levels    = array_map(
					function( $paragraphs ) {
						return $paragraphs['level'];
					},
					$content
				);
				$top_level = min( $levels );
				$epub->setCurrentLevel( 1 );
				foreach ( $content as $paragraph ) {
					// TODO, create hieratical TOC
					// $level = ( $paragraph['level'] > 2 ) ? $paragraph['level'] - 1 : 1;
					// // $level = $paragraph['level'];
					// // if ( $epub->getCurrentLevel() < $level ) {
					// while ( $epub->getCurrentLevel() < $level ) {
					// $epub->subLevel();
					// }
					// for ( $i = $epub->getCurrentLevel(); $i < $level; $i++ ) {
					// $epub->subLevel();
					// }
					// // } elseif ( $epub->getCurrentLevel() > $level ) {
					// $epub->setCurrentLevel( $level );
					// while ( $epub->getCurrentLevel() > $level ) {
					// $epub->backLevel();
					// }
					// for ( $i = $epub->getCurrentLevel(); $i > $level; $i-- ) {
					// $epub->backLevel();
					// }
					// // }
					// // $epub->setCurrentLevel( $level );
					if ( '' !== $paragraph['title'] ) {
						$header = sprintf( '<h%s>%s</h%s>', $paragraph['level'], $paragraph['title'], $paragraph['level'] );
						$epub->addChapter( $paragraph['title'], sprintf( 'Chapter%03d.xhtml', $chapter_num ), $content_start . $header . $paragraph['content'] . $content_end, false, EPub::EXTERNAL_REF_ADD );
						$chapter_num++;
					}
				}
				$epub->rootLevel();
			}
		}

		if ( 0 === $add_toc || 0 === count( $content ) ) {
			$epub->addChapter( 'Body', 'Body.xhtml', $content_start . $html . $content_end, false, EPub::EXTERNAL_REF_ADD );
		}
		$epub->finalize();
		$zip_data = $epub->sendBook( $post->post_name );
	}

	function mobi( $post ) {

		$html = $this->html;
		$mobi = new MOBI();
		/*
		//options:
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

		$add_toc = $this->_get_child_array_key( 'mobi', parent::$options['mobi']['add_toc'] );
		$toc     = $this->_get_child_array_key( 'mobi', parent::$options['mobi']['toc'] );
		$content = array();
		if ( 0 !== $add_toc && 0 !== $toc ) {
			$content = $this->_split_content( $html, $toc );
			if ( 0 != count( $content ) ) {
				$mobi_content = new Read_Offline_MobiFile();
				$mobi_content->set( 'title', $post->post_title );
				$mobi_content->set( 'description', parent::get_excerpt_by_id( $post->ID ) );
				$mobi_content->set( 'author', $this->author_firstlast );
				$mobi_content->set( 'publishingdate', get_the_date( 'r', $post ) );

				$mobi_content->set( 'source', $post->guid );
				$mobi_content->set( 'publisher', get_bloginfo( 'name' ), get_bloginfo( 'url' ) );
				$mobi_content->set( 'subject', $this->subject );
				$mobi_content->set( 'imprint', parent::$options['copyright']['message'] );
				if ( parent::$options['mobi']['mobi_cover_image'] ) {
					$mobi_content->appendImage( parent::image_create_frome_image( parent::$options['mobi']['mobi_cover_image'] ) );
					$mobi_content->appendPageBreak();
				}
				foreach ( $content as $paragraph ) {
					if ( '' !== $paragraph['title'] ) {
						$mobi_content->appendChapterTitle( wp_strip_all_tags( $paragraph['title'] ) );
					}
					// if ( false !== ($imgurl = $this->_get_first_imageurl( $paragraph )) ) {
					// $img = $this->_image_create_from_url( $imgurl );
					// $mobi_content->appendImage( $img );
					// }
					$mobi_content->appendParagraph( $this->_strip_img( $paragraph['content'] ) );
					$mobi_content->appendPageBreak();
				}
				$mobi->setContentProvider( $mobi_content );
			}
		}
		if ( 0 === $add_toc || 0 === count( $content ) ) {
			$options = array(
				'title'          => $post->post_title,
				'description'    => parent::get_excerpt_by_id( $post->ID ),
				'author'         => $this->author_firstlast,
				'subject'        => $this->subject,
				'publishingdate' => get_the_date( 'r', $post ),
				'source'         => $post->guid,
				'publisher'      => get_bloginfo( 'name' ),
				'imprint'        => parent::$options['copyright']['message'],
			);
			$mobi->setData( $this->_strip_img( $html ) );
			$mobi->setOptions( $options );
		}
		$title = $post->post_name;
		if ( false === $title ) {
			$title = 'file'; }
		$title = urlencode( substr( $title, 0, 12 ) );

		// Send the mobi file as download
		$zip_data = $mobi->download( $title . '.mobi' );
	}



	function pdf( $post ) {

		$html = $this->html;

		define( '_MPDF_TEMP_PATH', READOFFLINE_CACHE . '/tmp/' );
		define( '_MPDF_TTFONTDATAPATH', READOFFLINE_CACHE . '/font/' );
		if ( defined( 'READ_OFFLINE_FONTS' ) ) {
			define( '_MPDF_TTFONTPATH', READ_OFFLINE_FONTS );
		}
		// require_once READOFFLINE_PATH . '/lib/mpdf610/mpdf.php';

		$paper_format = sprintf(
			"'%s-%s'",
			( 'custom_paper_format' == $this->_get_child_array_key( 'pdf_layout', parent::$options['pdf_layout']['paper_format'] ) ) ? parent::$options['pdf_layout']['custom_paper_format'] : parent::$options['pdf_layout']['paper_format'],
			parent::$options['pdf_layout']['paper_orientation']
		);

		$pdf = new \Mpdf\Mpdf(
			array(
				'mode'              => 'utf-8',
				'format'            => $paper_format,
				'default_font_size' => 0,
				'default_font'      => 'dejavusans',
				'margin_left'       => 15,
				'margin_right'      => 15,
				'margin_top'        => 16,
				'margin_bottom'     => 16,
				'margin_header'     => 9,
				'margin_footer'     => 9,
				'orientation'       => parent::$options['pdf_layout']['paper_orientation'],
			)
		);

		if ( ! defined( 'READ_OFFLINE_FONTS' ) ) {
			$pdf->fontdata = array(
				'dejavusanscondensed'  => array(
					'R'          => 'DejaVuSansCondensed.ttf',
					'B'          => 'DejaVuSansCondensed-Bold.ttf',
					'I'          => 'DejaVuSansCondensed-Oblique.ttf',
					'BI'         => 'DejaVuSansCondensed-BoldOblique.ttf',
					'useOTL'     => 0xFF,
					'useKashida' => 75,
				),
				'dejavusans'           => array(
					'R'          => 'DejaVuSans.ttf',
					'B'          => 'DejaVuSans-Bold.ttf',
					'I'          => 'DejaVuSans-Oblique.ttf',
					'BI'         => 'DejaVuSans-BoldOblique.ttf',
					'useOTL'     => 0xFF,
					'useKashida' => 75,
				),
				'dejavuserif'          => array(
					'R'  => 'DejaVuSerif.ttf',
					'B'  => 'DejaVuSerif-Bold.ttf',
					'I'  => 'DejaVuSerif-Italic.ttf',
					'BI' => 'DejaVuSerif-BoldItalic.ttf',
				),
				'dejavuserifcondensed' => array(
					'R'  => 'DejaVuSerifCondensed.ttf',
					'B'  => 'DejaVuSerifCondensed-Bold.ttf',
					'I'  => 'DejaVuSerifCondensed-Italic.ttf',
					'BI' => 'DejaVuSerifCondensed-BoldItalic.ttf',
				),
				'dejavusansmono'       => array(
					'R'          => 'DejaVuSansMono.ttf',
					'B'          => 'DejaVuSansMono-Bold.ttf',
					'I'          => 'DejaVuSansMono-Oblique.ttf',
					'BI'         => 'DejaVuSansMono-BoldOblique.ttf',
					'useOTL'     => 0xFF,
					'useKashida' => 75,
				),
			);
		} else {

			$pdf->autoScriptToLang = true; // @codingStandardsIgnoreLine
			// $pdf->baseScript = 1; // @codingStandardsIgnoreLine
			$pdf->autoVietnamese = true; // @codingStandardsIgnoreLine
			$pdf->autoArabic = true; // @codingStandardsIgnoreLine

			if ( is_rtl() ) {
				$pdf->SetDirectionality( 'rtl' );
			}
		}

		$pdf->SetTitle( $post->post_title );
		$pdf->SetAuthor( $this->author_firstlast );
		$pdf->SetSubject( $this->subject );
		$pdf->SetKeywords( $this->keywords );
		$pdf->SetCreator( parent::$options['copyright']['message'] );

		$pdf->autoLangToFont      = true; // @codingStandardsIgnoreLine
		$pdf->ignore_invalid_utf8 = true; // @codingStandardsIgnoreLine
		$pdf->useSubstitutions    = false; // @codingStandardsIgnoreLine
		$pdf->simpleTables        = true; // @codingStandardsIgnoreLine
		$pdf->h2bookmarks         = array(
			'H1' => 0,
			'H2' => 1,
			'H3' => 2,
		);
		$pdf->title2annots        = true;
		/**
		 * Watermark
		 */
		$watermark = $this->_get_child_array_key( 'pdf_watermark', parent::$options['pdf_watermark']['watermark'] );
		switch ( $watermark ) {
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
		$has_protection = $this->_get_child_array_key( 'pdf_protection', parent::$options['pdf_protection']['protection'] );
		if ( 'password_owner' == $has_protection ) {
			$user_can       = array_keys(
				array_intersect_key(
					array(
						'copy'          => 1,
						'print'         => 1,
						'modify'        => 1,
						'extract'       => 1,
						'assemble'      => 1,
						'print-highres' => 1,
					),
					array_filter( parent::$options['pdf_protection']['user_can_do'] )
				)
			);
			$password_user  = parent::$options['pdf_protection']['password_user'];
			$password_owner = parent::$options['pdf_protection']['password_owner'];
			$pdf->SetProtection( $user_can, $password_user, $password_owner, 128 );
		}

		/**
		 * PDFA
		 */
		if ( isset( parent::$options['pdf_layout']['pdfa'] ) && '1' == parent::$options['pdf_layout']['pdfa'] ) {
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
			$pdf->showWatermarkText  = false; // @codingStandardsIgnoreLine
			$pdf->showWatermarkImage = false; // @codingStandardsIgnoreLine
			$pdf->useCoreFontsOnly   = false; // @codingStandardsIgnoreLine
			$pdf->PDFA               = true; // @codingStandardsIgnoreLine

		}

		/**
		 * header and footer
		 */
		$print_css = '';
		$header    = $this->_get_child_array_key( 'pdf_header', parent::$options['pdf_header']['header'] );
		switch ( $header ) {
			case 'default_header':
				if ( ( '0' == parent::$options['pdf_header']['default_header'][0] &&
					 '0' == parent::$options['pdf_header']['default_header'][1] &&
					 '0' == parent::$options['pdf_header']['default_header'][2] ) ) {
					break;
				}
				$pdf->DefHeaderByName(
					'pdfheader',
					array(
						'L'    => array(
							'content'     => ( '0' != parent::$options['pdf_header']['default_header'][0] ) ? $this->_header_footer( $post, parent::$options['pdf_header']['default_header'][0] ) : '',
							'font-size'   => 10,
							'font-style'  => 'B',
							'font-family' => 'serif',
							'color'       => '#000000',
						),
						'C'    => array(
							'content'     => ( '0' != parent::$options['pdf_header']['default_header'][1] ) ? $this->_header_footer( $post, parent::$options['pdf_header']['default_header'][1] ) : '',
							'font-size'   => 10,
							'font-style'  => 'B',
							'font-family' => 'serif',
							'color'       => '#000000',
						),
						'R'    => array(
							'content'     => ( '0' != parent::$options['pdf_header']['default_header'][2] ) ? $this->_header_footer( $post, parent::$options['pdf_header']['default_header'][2] ) : '',
							'font-size'   => 10,
							'font-style'  => 'B',
							'font-family' => 'serif',
							'color'       => '#000000',
						),
						'line' => 1,
					)
				);
				break;
			case 'custom_header':
				$pdf->DefHTMLHeaderByName(
					'pdfheader',
					$this->_parse_header_footer( $post, parent::$options['pdf_header']['custom_header'] )
				);
				break;
		}

		$footer = $this->_get_child_array_key( 'pdf_footer', parent::$options['pdf_footer']['footer'] );
		switch ( $footer ) {
			case 'default_footer':
				if ( ! isset( parent::$options['pdffooter'], parent::$options['pdffooter']['default_footer'] ) ||
					( '0' == parent::$options['pdffooter']['default_footer'][0] &&
					 '0' == parent::$options['pdffooter']['default_footer'][1] &&
					 '0' == parent::$options['pdffooter']['default_footer'][2] ) ) {
					break;
				}
				$pdf->DefFooterByName(
					'pdffooter',
					array(
						'L'    => array(
							'content'     => ( '0' != parent::$options['pdf_footer']['default_footer'][0] ) ? $this->_header_footer( $post, parent::$options['pdf_footer']['default_footer'][0] ) : '',
							'font-size'   => 10,
							'font-style'  => 'B',
							'font-family' => 'serif',
							'color'       => '#000000',
						),
						'C'    => array(
							'content'     => ( '0' != parent::$options['pdf_footer']['default_footer'][1] ) ? $this->_header_footer( $post, parent::$options['pdf_footer']['default_footer'][1] ) : '',
							'font-size'   => 10,
							'font-style'  => 'B',
							'font-family' => 'serif',
							'color'       => '#000000',
						),
						'R'    => array(
							'content'     => ( '0' != parent::$options['pdf_footer']['default_footer'][2] ) ? $this->_header_footer( $post, parent::$options['pdf_footer']['default_footer'][2] ) : '',
							'font-size'   => 10,
							'font-style'  => 'B',
							'font-family' => 'serif',
							'color'       => '#000000',
						),
						'line' => 1,
					)
				);
				break;
			case 'custom_footer':
				$pdf->DefHTMLFooterByName(
					'pdffooter',
					$this->_parse_header_footer( $post, parent::$options['pdf_footer']['custom_footer'] )
				);
				break;
			default:
				$print_footer = '';
				break;
		}

		/**
		 * Default CSS
		 */
		if ( 'default_header' == $header || 'default_footer' == $footer ) {
			$pdf->WriteHTML( file_get_contents( READOFFLINE_PATH . '/templates/pdf/default-print.css' ), 1 );
		}

		/**
		 * Theme / Custom CSS, overrides default css
		 */

		$css = $this->_get_child_array_key( 'pdf_css', parent::$options['pdf_css']['custom_css'] );
		switch ( $css ) {
			case 'theme_style':
				// $post_styles = $this->_get_post_styles($post->ID);
				// $link = "";
				// foreach ($post_styles as $post_style) {
				// $f = file_get_contents($post_style);
				// if (false !== $f) {
				// $link = $link . "\n" . $f;
				// }

				// }
				// $pdf->CSSselectMedia = 'all';
				// $pdf->WriteHTML($link,1);

				$pdf->WriteHTML( file_get_contents( get_stylesheet_uri() ), 1 );
				break;
			case 'css':
				$pdf->WriteHTML( parent::$options['pdf_css']['css'], 1 );
				break;

		}

		/**
		 * Coverart
		 */
		$coverart = $this->_get_child_array_key( 'pdf_cover', parent::$options['pdf_cover']['art'] );

		if ( 'none' != $coverart ) {
			// $paper_format = ('custom_paper_format' == $this->_get_child_array_key('pdf_layout',parent::$options['pdf_layout']['paper_format'])) ? parent::$options['pdf_layout']['custom_paper_format'] : parent::$options['pdf_layout']['paper_format'];
			// $dimensions = $pdf->_getPageFormat($paper_format);

			// $w = floor($dimensions[0] / _MPDFK);
			// $h = floor($dimensions[1] / _MPDFK);

			switch ( $coverart ) {

				case 'feature_image':
					$image_url = wp_get_attachment_url( get_post_thumbnail_id( $post->ID, 'thumbnail' ) );
					// $image_data = wp_get_attachment_metadata(get_post_thumbnail_id($post->ID, 'thumbnail'));
					// $left = ($w / 2) - ($image_data['width']  / 2);
					// $top  = ($h / 2) - ($image_data['height'] / 2);
					// $pdf->AddPage('','','','','on');
					if ( '' != $image_url ) {
						$pdf->AddPageByArray(
							array(
								'suppress' => 'on', // supress header
							)
						);
						$pdf->WriteHTML(
							sprintf(
								'
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
					// $pdf->AddPage('','','','','on');
					if ( '' != $image_url ) {
						$pdf->AddPageByArray(
							array(
								'suppress' => 'on', // supress header
							)
						);
						$pdf->WriteHTML(
							sprintf(
								'<div style="position: absolute; left:0; right: 0; top: 0; bottom: 0;">
							<img src="%s" style="width: 210mm; height: 297mm; margin: 30;" /></div>',
								$image_url
							)
						);
					}
					break;
			}
			// we don't want watermarks on the cover page

			$pdf->showWatermarkImage = false; // @codingStandardsIgnoreLine
			$pdf->showWatermarkText  = false; // @codingStandardsIgnoreLine
		}

		$toc = $this->_get_child_array_key( 'pdf_layout', parent::$options['pdf_layout']['add_toc'] );
		$pdf->AddPageByArray(
			array(
				'suppress'     => 'off', // don't supress headers
				'ohname'       => ( '0' != $header ) ? ( 'custom_header' == $header ) ? 'html_pdfheader' : 'pdfheader' : '',
				'ehname'       => ( '0' != $header ) ? ( 'custom_header' == $header ) ? 'html_pdfheader' : 'pdfheader' : '',
				'ofname'       => ( '0' != $footer ) ? ( 'custom_footer' == $footer ) ? 'html_pdffooter' : 'pdffooter' : '',
				'efname'       => ( '0' != $footer ) ? ( 'custom_footer' == $footer ) ? 'html_pdffooter' : 'pdffooter' : '',
				'ohvalue'      => ( '0' != $header ) ? 1 : 0,
				'ehvalue'      => ( '0' != $header ) ? 1 : 0,
				'ofvalue'      => ( '0' != $footer ) ? 1 : 0,
				'efvalue'      => ( '0' != $footer ) ? 1 : 0,
				'resetpagenum' => ( '0' != $toc ) ? 2 : 1,
			)
		);

		/**
		 * Table og contents
		 */

		if ( '0' !== $toc ) {
			$toc_start = ( '0' == parent::$options['pdf_layout']['toc'][0] ) ? 1 : parent::$options['pdf_layout']['toc'][0];
			$toc_stop  = ( '0' == parent::$options['pdf_layout']['toc'][1] ) ? 2 : parent::$options['pdf_layout']['toc'][1];
			if ( $toc_start > $toc_stop ) {
				$toc_stop = $toc_start + 1;
			}
			$toc_arr = array();
			$j       = 0;
			for ( $i = $toc_start; $i <= $toc_stop; $i++ ) {
				$toc_arr[ sprintf( 'H%s', $i ) ] = $j++;
			}
			$pdf->h2toc = $toc_arr;
			$pdf->TOCpagebreakByArray(
				array(
					// 'tocfont' => '',
					// 'tocfontsize' => '',
					// 'outdent' => '2em',
					'TOCusePaging'     => true,
					'TOCuseLinking'    => true,
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
					'toc_ohvalue'      => -1,
					'toc_ehvalue'      => -1,
					'toc_ofvalue'      => -1,
					'toc_efvalue'      => -1,
					'toc_preHTML'      => __( '<h1>Contents</h1>', 'read-offline' ),
					'toc_postHTML'     => '',
					'toc_bookmarkText' => __( 'Contents', 'read-offline' ),
					'resetpagenum'     => 2,
					'pagenumstyle'     => '',
					'suppress'         => 'off',
					'orientation'      => '',
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
				)
			);
		}

		// if waters are set, show them
		$pdf->showWatermarkImage = true; // @codingStandardsIgnoreLine
		$pdf->showWatermarkText  = true; // @codingStandardsIgnoreLine

		if ( '1' == parent::$options['pdf_layout']['annotations'] ) {
			$html = $this->_pdf_add_annotations( $html );
		}

		$pdf->WriteHTML( $html );
		$pdf->Output( $post->post_name . '.pdf', 'D' );

	}

	/**
	 * Check if URL is valid, code is borrowed form wp_ajax_test_url() (new in 4.6)
	 *
	 * @author soderlind
	 * @version 0.6.3
	 * @param   string $url URL to test
	 * @return  boolean   result, false if URL is 404
	 */
	function _url_exists( $url ) {


		if ( '' === $url ) {
			return false;
		}

		$href = esc_url_raw( $url );

		// Relative URL
		if ( strpos( $href, '//' ) !== 0 && in_array( substr( $href, 0, 1 ) , array( '/', '#', '?' ), true ) ) {
				$href = get_bloginfo( 'url' ) . $href;
		}

		$response              = wp_remote_head( $href, array( 'timeout' => 5 ) );
		$accepted_status_codes = array( 200, 301, 302 );
		if ( ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), $accepted_status_codes, true ) ) {
			return true;
		}

		return false;

	}

	private function _strip_img( $html ) {
		$doc = new DOMDocument();
		// START LibXML error management.
		// Modify state
		$libxml_previous_state = libxml_use_internal_errors( true );
		$doc->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
		// handle errors
		libxml_clear_errors();
		// restore
		libxml_use_internal_errors( $libxml_previous_state );
		// END LibXML error management.

		$doc->preserveWhiteSpace = false; // @codingStandardsIgnoreLine
		// Here we strip all the img tags in the document
		$images = $doc->getElementsByTagName( 'img' );
		$imgs   = array();
		foreach ( $images as $img ) {
			$imgs[] = $img;
		}
		foreach ( $imgs as $img ) {
			$img->parentNode->removeChild( $img ); // @codingStandardsIgnoreLine
		}
		return $doc->saveHTML();
	}

	private function _pdf_add_annotations( $content ) {

		$anchors = array();
		$doc     = new DOMDocument();
		// START LibXML error management.
		// Modify state
		$libxml_previous_state = libxml_use_internal_errors( true );
		$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
		// handle errors
		libxml_clear_errors();
		// restore
		libxml_use_internal_errors( $libxml_previous_state );
		// END LibXML error management.

		$sups     = $doc->getElementsByTagName( 'sup' );
		$tmp_sups = array();
		$tmp_li   = array();
		foreach ( $sups as $sup ) {
			// <sup><a id="footnote-ref-884344386868178-4" href="#footnote-884344386868178-4">[4]</a></sup>
			$node = $sup->firstChild;  // @codingStandardsIgnoreLine
			if ( XML_ELEMENT_NODE == $node->nodeType && $node->hasAttribute( 'href' ) ) {

				if ( false !== ( $ref = $node->getAttribute( 'href' ) ) && false !== stristr( $ref, 'note' ) ) {
					$tmp_sups[] = $sup;
					// find <li id="footnote-884344386868178-2" ..>
					$li       = $doc->getElementById( substr( $ref, 1 ) ); // substr removes leading #
					$tmp_li[] = $li;
					$slug = $tmpslug = str_replace( '↑','', htmlspecialchars( wp_strip_all_tags( $li->nodeValue ), ENT_QUOTES ) ); // @codingStandardsIgnoreLine

					// create annotation https://mpdf.github.io/reference/html-control-tags/annotation.html
					$annotation = $doc->createElement( 'annotation' );
					$annotation->setAttribute( 'content', $slug );
					// $annotation->setAttribute( 'icon', 'Comment' );

					$sup->parentNode->insertBefore( $annotation, $sup ); // @codingStandardsIgnoreLine
				}
			}
		}

		// remove sup
		foreach ( $tmp_sups as $sup ) {
			$sup->parentNode->removeChild( $sup ); // @codingStandardsIgnoreLine
		}
		// remove foot- / endnote list at the end of doc
		foreach ( $tmp_li as $li ) {
			$li->parentNode->removeChild( $li ); // @codingStandardsIgnoreLine
		}

		return $doc->saveHTML();
	}

	private function _remove_dont_readoffline( $content, $classname = 'not-readoffline' ) {

		$doc = new DOMDocument();
		// START LibXML error management.
		// Modify state
		$libxml_previous_state = libxml_use_internal_errors( true );
		$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
		// handle errors
		libxml_clear_errors();
		// restore
		libxml_use_internal_errors( $libxml_previous_state );
		// END LibXML error management.
		$finder = new DomXPath( $doc );

		$nodes     = $finder->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]" );
		$tmp_nodes = array();
		foreach ( $nodes as $node ) {
			$tmp_nodes[] = $node;
		}
		foreach ( $tmp_nodes as $node ) {
			$node->parentNode->removeChild( $node ); // @codingStandardsIgnoreLine
		}
		return $doc->saveHTML();
	}

	private function _get_child_array_key( $parent_element, $org ) {
		// $org: #fieldrow-pdf_header_default_header
		if ( false !== strpos( $org, '#fieldrow' ) ) {
			if ( false !== strpos( $org, ',' ) ) {
				$parts = explode( ',', $org );
				$org   = $parts[0];
			}
			return str_replace( '#fieldrow-' . $parent_element . '_', '', $org );
		} else {
			return $org;
		}
	}

	private function _header_footer( $post, $type ) {
		$val = '';
		switch ( $type ) {
			case 'document_title':
				$val = $post->post_title;
				break;
			case 'author':
				$val = get_the_author_meta( 'display_name', $post->post_author );
				break;
			case 'document_url':
				$val = get_permalink( $post->ID );
				break;
			case 'site_url':
				$val = home_url();
				break;
			case 'site_title':
				$val = get_bloginfo( 'name' );
				break;
			case 'page_number':
				$val = '{PAGENO}/{nbpg}';
				break;
			case 'date':
				$val = get_the_date( get_option( 'date_format' ), $post );
				break;
		}
		return $val;
	}

	private function _parse_header_footer( $post, $html, $strip_tages = false ) {
		// {DATE}, {TODAY}, {TITLE}, {AUTHOR}, {DOCURL}, {SITENAME}, {SITEURL}
		if ( false !== $strip_tages ) {
			$html = addslashes( strip_tags( $html ) );
		}

		$html = str_replace( '{DATE}', get_the_date( get_option( 'date_format' ), $post ), $html );
		$html = str_replace( '{TODAY}', sprintf( '{DATE %s}', get_option( 'date_format' ) ), $html );
		$html = str_replace( '{TITLE}', $post->post_title, $html );
		$html = str_replace( '{AUTHOR}', get_the_author_meta( 'display_name', $post->post_author ), $html );
		$html = str_replace( '{DOCURL}', get_permalink( $post->ID ), $html );
		$html = str_replace( '{SITENAME}', get_bloginfo( 'name' ), $html );
		$html = str_replace( '{SITEURL}', home_url(), $html );
		return $html;
	}


	// get taxonomies terms links
	private function _get_taxonomies_terms( $post ) {
		// get post type by post
		$post_type = $post->post_type;

		// get post type taxonomies
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		$out = array();
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
			// get the terms related to post
			$terms = get_the_terms( $post->ID, $taxonomy_slug );
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$out[] = $term->name;
				}
			}
		}

		return ( count( $out ) ) ? implode( ', ', $out ) : '';
	}

	// from https://philipnewcomer.net/2012/11/get-the-attachment-id-from-an-image-url-in-wordpress/
	private function _get_attachment_id_from_url( $attachment_url = '' ) {
		global $wpdb;
		$attachment_id = false;
		// If there is no url, return.
		if ( '' == $attachment_url ) {
			return; }
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

	private function _get_post_styles( $post_id ) {
		$transient_id = 'read_offline_post_styles_' . $post_id;
		$transient    = get_transient( $transient_id );
		return get_transient( $transient_id );
	}

	/**
	 * Split html on header tag (h1-h6)
	 *
	 * @author soderlind
	 * @version 0.6.4
	 * @param   string $html   HTML content
	 * @param   string $header 1-6 or 'all'
	 * @return  array
	 */
	private function _split_content( $html, $header ) {

		$all_tags   = ( 'all' === $header );
		$content    = array();
		$title      = '';
		$num_title  = -1;
		$have_title = 'end';
		$level      = 0;
		$html_array = wp_html_split( $html );

		foreach ( $html_array as $value ) {
			if ( '' !== $value ) {
				switch ( strtolower( $value ) ) {
					case '<h' . trim( $header ) . '>':
					case ( $all_tags && ( preg_match( '/<h(\d).*?>/i', $value, $matches ) ? $value : ! $value ) ):
						$have_title = 'start';
						$level      = ( $all_tags && isset( $matches ) ) ? $matches[1] : $header;
						$title      = '';
						break;
					case '</h' . trim( $header ) . '>':
					case ( $all_tags && ( preg_match( '/<(\/h\d*)>/i', $value ) ? $value : ! $value ) ):
						$have_title = 'end';
						break;
					default:
						if ( 'start' === $have_title ) {
							$num_title++;
							$title .= trim( wp_strip_all_tags( $value ) );
						}
						if ( 'end' === $have_title ) {
							if ( ! isset( $content[ $num_title ] ) ) {
								$content[ $num_title ] = array(
									'title'   => $title,
									'level'   => $level,
									'content' => '',
								);
							}
							$content[ $num_title ]['content'] .= $value;
						}
						break;
				}
			}
		}
		return $content;
	}


	function _get_first_imageurl( $content ) {

		$doc = new DOMDocument();
		// START LibXML error management.
		// Modify state
		$libxml_previous_state = libxml_use_internal_errors( true );
		$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
		// handle errors
		libxml_clear_errors();
		// restore
		libxml_use_internal_errors( $libxml_previous_state );
		// END LibXML error management.
		$tags = $doc->getElementsByTagName( 'img' );

		foreach ( $tags as $tag ) {
			$url = $tag->getAttribute( 'src' );
			// @codingStandardsIgnoreStart
			// printf( '<pre>%s</pre>', print_r( $url, true ) );
			// @codingStandardsIgnoreEnd
			// if ( file_exists( $url ) ) {
				return $url;
			// }
		}
		return false;
	}

	function _image_create_from_url( $url ) {
		$info = pathinfo( $url );
		switch ( $info['extension'] ) {
			case 'jpeg':
			case 'jpg':
				return imagecreatefromjpeg( $url );
			break;

			case 'png':
				return imagecreatefrompng( $url );
			break;

			case 'gif':
				return imagecreatefromgif( $url );
			break;

			// default:
			// throw new InvalidArgumentException('File "'.$filename.'" is not valid jpg, png or gif image.');
			// break;
		}
	}
}

/**
 *
 */
class Read_Offline_MobiFile extends MobiFile {
	private function addTOC( $str, $entries ) {
		$this->resolveFilepos( $str, self::TOC_LINK );
		$str->append( '<h2>' . __( 'Contentssss', 'read-offline' ) . '</h2>' );
		$str->append( '<blockquote><table summary="' . __( 'Table of Contents', 'read-offline' ) . '"><col/><tbody>' );
		for ( $i = 0, $len = sizeof( $entries ); $i < $len; $i++ ) {
			$entry = $entries[ $i ];

			$str->append( '<tr><td><a href="#' . $entry['id'] . '" filepos=' );
			$this->addFilepos( $str, $entry['id'] );
			$str->append( '>' . $entry['title'] . '</a></td></tr>' );
		}
		$str->append( '</tbody></b></table></blockquote><mbp:pagebreak/>' );
	}
}
