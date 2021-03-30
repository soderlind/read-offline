<?php


// Include the library
if ( ! class_exists( 'AdminPageFramework' ) ) {
	// include_once( dirname( __FILE__ ) . '/lib/admin/source/admin-page-framework.php' );
	include_once( READOFFLINE_PATH . '/lib/admin/admin-page-framework.php' );
}

// extend the class
class Read_Offline_Admin_Settings extends Read_Offline_AdminPageFramework {

	public function start_Read_Offline_Admin_Settings() {
		// start_{extended class name} - this method gets automatically triggered at the end of the class constructor.

		if ( ! class_exists( 'RevealerCustomFieldType' ) ) {
			include_once( READOFFLINE_PATH . '/lib/admin/RevealerCustomFieldType.php' ); }

		if ( ! class_exists( 'AceCustomFieldType' ) ) {
			include_once( READOFFLINE_PATH . '/lib/admin/custom-field-types/ace-custom-field-type/AceCustomFieldType.php' ); }

		$class_name = get_class( $this );

		new RevealerCustomFieldType( $class_name );
		new Read_Offline_AceCustomFieldType( $class_name );

	}



	public function replyToInsertPluginTitle( $content ) {
		return  "<div class='plugin_icon' style='height:64px;width:64px;float:left;'>"
				  .  "<div class='dashicons dashicons-book' style='font-size:64px;'></div>"
			  . '</div>'
			  . "<div class='page_title'>"
				  . '<h1>Read Offline</h1>'
			  . '</div>'
			  . $content;
	}


	public function load_Read_Offline_Admin_Settings() {

		// Modify the title tag.
		add_filter( 'admin_title', array( $this, 'replyToModifyTitleTag' ) , 10, 2 );
		add_filter( 'content_top_Read_Offline_Admin_Settings', array( $this, 'replyToInsertPluginTitle' ) );
		add_filter( 'content_top_Read_Offline_Admin_Settings', array( $this, 'replyToInsertDonationButton' ) );
		add_filter( 'style_common_Read_Offline_Admin_Settings', array( $this, 'replyToAddStyle' ) );

	}

	public function replyToModifyTitleTag( $admin_title, $title ) {
		return  get_bloginfo( 'name' ).' &bull; Admin &bull; Read Offline &bull; ' . $title;
	}


	public function replyToInsertDonationButton( $content ) {
		return "<div class='donate' style=''>"
			. "<a href='" . esc_url( 'https://paypal.me/PerSoderlind' ) . "' target='_blank' >"
				. "<img src='" . READOFFLINE_URL . "/css/donation.gif' alt='" . esc_attr( __( 'Please donate!', 'admin-page-framework' ) ). "' />"
			. '</a>'
			. '</div>'
			. $content;
	}


	public function replyToAddStyle( $css ) {
			  return $css
				  . '

          .page_title {
              display: block; /* inline-block; */
              margin-top: 1em;
              margin-bottom: 0.5em;
              padding-top: 1.5em;
          }
          .page_title h1{
              margin: 0;
              display: inline-block;
              vertical-align: middle;
              font-size: 2.32em;
              color: #222;
              font-weight: 400;
          }

          .donate {
             float: right;
             clear: right;
             margin: 1em;
          }
          .donate img {
              width: 120px;
          }
          .plugin_icon {
             float: left;
             clear: left;
             margin: 0 1em;
             vertical-align: top;
             display: inline-block;
          }
        /*
          #fieldrow-pdf_header_header th span {
            font-size: 1.3em;
        }*/
      ';
	}



	// Define the setup method to set how many pages, page titles and icons etc.
	public function setUp() {
		// Root menu
		$this->setRootMenuPage(
			'Read Offline',    // specify the name of the page group
			'dashicons-book'
		);

		// General Options
		$this->addSubMenuPage(
			array(
				'title'            => __( 'General Options', 'read-offline' ),        // page title
				'page_slug'        => 'read_offline_options',    // page slug
			)
		);

		// what
		$this->addSettingSections(
			array(
				'section_id'       => 'what',    // the section ID
				'page_slug'        => 'read_offline_options',    // the page slug that the section belongs to
				'title'            => __( 'What', 'read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'formats',
				'section_id'       => 'what',
				'title'            => __( 'Formats available for your visitors', 'read-offline' ),
				'description'      => __(
					"If direct linking to ePub and mobi <!--and DocX--> doesn't work, add the following to your .htaccess file:
                    <pre>AddType application/epub+zip epub\nAddType application/x-mobipocket-ebook mobi<!--\nAddType application/vnd.openxmlformats-officedocument.wordprocessingml.document docx--></pre>",'read-offline'),
				'type'             => 'checkbox',
				'label'            => array(
					'pdf' => 'PDF',
					'epub' => 'ePub',
					'mobi' => 'mobi',
					//'docx' => 'DocX',
					'print' => __( 'Print' , 'read-offline' ),
				),
				'default'          => array( 'pdf' => true, 'epub' => true, 'mobi' => true, 'docx' => false,'print' => true ),
				'order'            => 1,
				'help'             => __( 'Help text', 'read-offline' ),
				//'help_aside'       => __( 'Help aside', 'read-offline' ),
			)
		);

		// where
		$this->addSettingSections(
			array(
				'section_id'       => 'where',    // the section ID
				'page_slug'        => 'read_offline_options',    // the page slug that the section belongs to
				'title'            => __( 'Where','read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'placements',
				'section_id'       => 'where',
				'title'            => 'Placements',
				'description'      => __( 'Also available via the [pdf], [epub], [mobi] and [print] shortcodes.' , 'read-offline' ),
				'type'             => 'checkbox',
				'label'            => array(
					'top'                  => __( 'Top of post type', 'read-offline' ),
					'bottom'               => __( 'Bottom of post type', 'read-offline' ),
				),
				'default'          => array(
					'top'                  => true,
					'bottom'               => true,
				),
				'operator'              => 'and',
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'post_types',
				'section_id'       => 'where',
				'title'            => 'Post Types',
				'type'             => 'posttype',
				'query'                 => array(
					'public'   => true,
					// '_builtin' => true,
				),
				'description'      => __( 'Supported post types', 'read-offline' ),
				'default'          => array(
					'post'                 => true,
					'page'                 => true,
				),
				'select_all_button'       => false,
				'select_none_button'       => false,
				'help'             => __( 'Help text', 'read-offline' ),
			)
		);
		// how
		$this->addSettingSections(
			array(
				'section_id'       => 'how',    // the section ID
				'page_slug'        => 'read_offline_options',    // the page slug that the section belongs to
				'title'            => __( 'How','read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'link_text',
				'section_id'       => 'how',
				'title'            => __( 'Download link text', 'read-offline' ),
				'type'             => 'text',
				'default'          => __( 'Read Offline: ', 'read-offline' ),
				'description'      => __( 'Use %title% to insert the post type title','read-offline' ),
			   'help'              => __( 'Help text', 'read-offline' ),
			 ),
			array(
				'field_id'         => 'icons_only',
				'section_id'       => 'how',
				'title'            => __( 'Download link, icons only?', 'read-offline' ),
				'type'             => 'radio',
				'label'            => array(
					'1'                    => __( 'Yes', 'read-offline' ),
					'0'                    => __( 'No', 'read-offline' ),
				),
				'default'          => '0',
				'help'             => __( 'Help text', 'read-offline' ),
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'copyright',    // the section ID
				'page_slug'        => 'read_offline_options',    // the page slug that the section belongs to
				'title'            => __( 'Copyright','read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(  // Text Area
				'field_id'         => 'message',
				'section_id'       => 'copyright',
				'title'            => __( 'Copyright Message', 'read-offline' ),
				'description'      => __( 'The copyright message will be added to the PDF ("Creator"), ePub ("Rights") and mobi ("imprint") file meta data.', 'read-offline' ),
				'type'             => 'textarea',
				'default'          => '',
				'attributes'       => array(
					'style'                 => 'width:600px;max-width:600px;height:100%;max-height:200px;',
				),
			 )
		);

		// miscellaneous
		$this->addSettingSections(
			array(
				'section_id'       => 'misc',    // the section ID
				'page_slug'        => 'read_offline_options',    // the page slug that the section belongs to
				'title'            => __( 'Miscellaneous','read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'cache',
				'section_id'       => 'misc',
				'title'            => __( 'Save in <a href="upload.php">Media Libary</a>:', 'read-offline' ),
				'description'      => __( 'Use the Media Library as a cache. When a post is created or updated, a PDF, Epub and/or mobi of the post will be saved to the Media Library. This file will be served to the enduser when she clicks on the download link on the frontend. ','read-offline' ),
				'type'             => 'radio',
				'label'            => array(
					'1'                    => __( 'Yes', 'read-offline' ),
					'0'                    => __( 'No', 'read-offline' ),
				),
				'default'          => '0',
				'help'             => __( 'Use the Media Library as a cache', 'read-offline' ),
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'google',
				'section_id'       => 'misc',
				'title'            => __( 'Google Analytics, track downloads:', 'read-offline' ),
				'description'      => __( 'Track read-offline events, you can find these under Content » Events in your Google Analytics reports. Assumes you’re using the <a href="https://developers.google.com/analytics/devguides/collection/gajs/asyncTracking">Asynchronous version of Google Analytics</a>','read-offline' ),
				'type'             => 'radio',
				'label'            => array(
					'1'                    => __( 'Yes', 'read-offline' ),
					'0'                    => __( 'No', 'read-offline' ),
				),
				'default'          => '1',
				'help'             => __( 'Track downloads using Google Analytics', 'read-offline' ),
			)
		);

		// PDF
		$this->addSubMenuPage(
			array(
				'title'            => 'PDF',
				'page_slug'        => 'read_offline_pdf',
				'screen_icon'      => 'page',
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'pdf_layout',    // the section ID
				'page_slug'        => 'read_offline_pdf',    // the page slug that the section belongs to
				'title'            => __( 'Layout','read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(  // Drop-down Lists with Mixed Types
				'field_id'         => 'paper_format',
				'section_id'       => 'pdf_layout',
				'title'            => __( 'Paper Format', 'read-offline' ),
				//'description'      => __( 'This is multiple sets of drop down list.', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
					'A4'                   => __( 'A4', 'read-offline' ),
					'LETTER'               => __( 'Letter', 'read-offline' ),
					'#fieldrow-pdf_layout_custom_paper_format' => __( 'Custom (added below)', 'read-offline' ),
				),
				'default'          => 1,
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px; margin-right: 10px;',

					),
				),
				'help'              => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'custom_paper_format',
				'section_id'       => 'pdf_layout',
				//'title'               => 'Custom Paper Format',
				//'description'      => 'Type a text string here.',
				'type'             => 'select',
				'default'          => 'A4',
				'help'             => __( 'Help text', 'read-offline' ),
				'label'            => array(
					  __( 'ISO 216 A', 'read-offline' ) => array(
						  'A0' => 'A0 (841x1189 mm ; 33.11x46.81 in)',
						  'A1' => 'A1 (594x841 mm ; 23.39x33.11 in)',
						  'A2' => 'A2 (420x594 mm ; 16.54x23.39 in)',
						  'A3' => 'A3 (297x420 mm ; 11.69x16.54 in)',
						  'A4' => 'A4 (210x297 mm ; 8.27x11.69 in)',
						  'A5' => 'A5 (148x210 mm ; 5.83x8.27 in)',
						  'A6' => 'A6 (105x148 mm ; 4.13x5.83 in)',
						  'A7' => 'A7 (74x105 mm ; 2.91x4.13 in)',
						  'A8' => 'A8 (52x74 mm ; 2.05x2.91 in)',
						  'A9' => 'A9 (37x52 mm ; 1.46x2.05 in)',
						  'A10' => 'A10 (26x37 mm ; 1.02x1.46 in)',
					   ),
					  __( 'ISO 216 B', 'read-offline' ) => array(
						  'B0' => 'B0 (1000x1414 mm ; 39.37x55.67 in)',
						  'B1' => 'B1 (707x1000 mm ; 27.83x39.37 in)',
						  'B2' => 'B2 (500x707 mm ; 19.69x27.83 in)',
						  'B3' => 'B3 (353x500 mm ; 13.90x19.69 in)',
						  'B4' => 'B4 (250x353 mm ; 9.84x13.90 in)',
						  'B5' => 'B5 (176x250 mm ; 6.93x9.84 in)',
						  'B6' => 'B6 (125x176 mm ; 4.92x6.93 in)',
						  'B7' => 'B7 (88x125 mm ; 3.46x4.92 in)',
						  'B8' => 'B8 (62x88 mm ; 2.44x3.46 in)',
						  'B9' => 'B9 (44x62 mm ; 1.73x2.44 in)',
						  'B10' => 'B10 (31x44 mm ; 1.22x1.73 in)',
					   ),
					  __( 'ISO 216 C', 'read-offline' ) => array(
						  'C0' => 'C0 (917x1297 mm ; 36.10x51.06 in)',
						  'C1' => 'C1 (648x917 mm ; 25.51x36.10 in)',
						  'C2' => 'C2 (458x648 mm ; 18.03x25.51 in)',
						  'C3' => 'C3 (324x458 mm ; 12.76x18.03 in)',
						  'C4' => 'C4 (229x324 mm ; 9.02x12.76 in)',
						  'C5' => 'C5 (162x229 mm ; 6.38x9.02 in)',
						  'C6' => 'C6 (114x162 mm ; 4.49x6.38 in)',
						  'C7' => 'C7 (81x114 mm ; 3.19x4.49 in)',
						  'C8' => 'C8 (57x81 mm ; 2.24x3.19 in)',
						  'C9' => 'C9 (40x57 mm ; 1.57x2.24 in)',
						  'C10' => 'C10 (28x40 mm ; 1.10x1.57 in)',
					   ),
					  __( 'ISO Press', 'read-offline' ) => array(
						  'RA0' => 'RA0 (860x1220 mm ; 33.86x48.03 in)',
						  'RA1' => 'RA1 (610x860 mm ; 24.02x33.86 in)',
						  'RA2' => 'RA2 (430x610 mm ; 16.93x24.02 in)',
						  'RA3' => 'RA3 (305x430 mm ; 12.01x16.93 in)',
						  'RA4' => 'RA4 (215x305 mm ; 8.46x12.01 in)',
						  'SRA0' => 'SRA0 (900x1280 mm ; 35.43x50.39 in)',
						  'SRA1' => 'SRA1 (640x900 mm ; 25.20x35.43 in)',
						  'SRA2' => 'SRA2 (450x640 mm ; 17.72x25.20 in)',
						  'SRA3' => 'SRA3 (320x450 mm ; 12.60x17.72 in)',
						  'SRA4' => 'SRA4 (225x320 mm ; 8.86x12.60 in)',
					   ),
					  __( 'German DIN 476', 'read-offline' ) => array(
						  '4A0' => '4A0 (1682x2378 mm ; 66.22x93.62 in)',
						  '2A0' => '2A0 (1189x1682 mm ; 46.81x66.22 in)',
					   ),

					  __( 'Traditional "Loose" North American Paper Sizes', 'read-offline' ) => array(
						  'LEDGER' => 'LEDGER, USLEDGER (432x279 mm ; 17.00x11.00 in)',
						  'TABLOID' => 'TABLOID, USTABLOID, BIBLE, ORGANIZERK (279x432 mm ; 11.00x17.00 in)',
						  'LETTER' => 'LETTER, USLETTER, ORGANIZERM (216x279 mm ; 8.50x11.00 in)',
						  'LEGAL' => 'LEGAL, USLEGAL (216x356 mm ; 8.50x14.00 in)',
					   ),
					  __( 'Other North American Paper Sizes', 'read-offline' ) => array(
						  'FOLIO' => 'FOLIO, GOVERNMENTLEGAL (216x330 mm ; 8.50x13.00 in)',
						  'EXECUTIVE' => 'EXECUTIVE, MONARCH (184x267 mm ; 7.25x10.50 in)',
					   ),
					  __( 'Old Imperial English (some are still used in USA)', 'read-offline' ) => array(
						   'ROYAL' => 'EN_ROYAL (508x635 mm ; 20.00x25.00 in)',
						   'DEMY' => 'EN_DEMY (445x572 mm ; 17.50x22.50 in)',
					   ),
				),
				'default'          => 'A4',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px; margin-right: 10px;',

					),
				),
				'hidden'           => true,
			),
			array(
				'field_id'         => 'paper_orientation',
				'section_id'       => 'pdf_layout',
				'title'            => __( 'Paper Orientation', 'read-offline' ),
				'type'             => 'radio',
				'label'            => array(
					'P' => __( 'Portrait', 'read-offline' ),
					'L' => __( 'Landscape', 'read-offline' ),
				),
				'default'          => 'P',
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(  // Single set of radio buttons
				'field_id'         => 'add_toc',
				'section_id'       => 'pdf_layout',
				'title'            => __( 'Table of Contents', 'read-offline' ),
				'description'      => __( 'Automatically generate entries for a Table of Contents using all heading elements (H1 - H6)', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
						'0'                => __( 'No ', 'read-offline' ),
						'#fieldrow-pdf_layout_toc'  => __( 'Yes (configured below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'toc',
				'section_id'       => 'pdf_layout',
				//'title'           => 'Custom CSS',
				'description'      => __( 'If Start and Stop are not selected, Table of Contents will be generated using heading elements H1 - H3', 'read-offline' ),
				'type'             => 'select',
				'label'            => array(
					'0'                    => __( '-- Start --', 'read-offline' ),
					'1'                    => __( 'H1', 'read-offline' ),
					'2'                    => __( 'H2', 'read-offline' ),
					'3'                    => __( 'H3', 'read-offline' ),
					'4'                    => __( 'H4', 'read-offline' ),
					'5'                    => __( 'H5', 'read-offline' ),
			   	),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px; margin-right: 10px;',

					),
					'help'             => __( 'Help text', 'read-offline' ),
				),
				array(
					'label'        => array(
						'0'                => __( '-- Stop --', 'read-offline' ),
						'2'                => __( 'H2', 'read-offline' ),
						'3'                => __( 'H3', 'read-offline' ),
						'4'                => __( 'H4', 'read-offline' ),
						'5'                => __( 'H5', 'read-offline' ),
						'6'                => __( 'H6', 'read-offline' ),
					),
					'default'      => 1,
					'attributes'   => array(
						'field'             => array(
							'style'                 => 'display: inline; clear: none', // this makes the field element inline, which means next fields continues from the right end of the field, not from the new line.
						),
					),
					'help'         => __( 'Help text', 'read-offline' ),
			   	),
			),
			array(  // Single set of radio buttons
				'field_id'         => 'annotations',
				'section_id'       => 'pdf_layout',
				'title'            => __( 'Annotations', 'read-offline' ),
				'description'      => __(
					"(experimental feature) Automatically generate annotations from foot- and endnotes. Required format (as created by the <a href='https://wordpress.org/plugins/mammoth-docx-converter/' target='_blank' >Mammoth .docx converter</a>):
					<pre>Mark:\n&lt;sup&gt;\n	&lt;a href=\"#post-992-footnote-2\"&gt;[1]&lt;/a&gt;\n&lt;/sup&gt;\nContent:\n&lt;ol&gt;\n	&lt;li id=\"post-992-footnote-2\"&gt;Annotation content&lt;/li&gt;\n&lt;/ol&gt;</pre>  " , 'read-offline'
				),
				'type'             => 'radio',
				'label'            => array(
					'1'                    => __( 'Yes', 'read-offline' ),
					'0'                    => __( 'No', 'read-offline' ),
				),
				'default'          => '0',
				// 'help'             => __( 'Use the Media Library as a cache', 'read-offline' ),
			)
			//  array(
			//      'field_id'         => 'pdfa',
			//      'section_id'       => 'pdf_layout',
			//      'title'            => __('PDF/A?','read-offline'),
			//      'type'             => 'radio',
			//      'label'            => array(
			//          '1' => __( 'Yes', 'read-offline' ),
			//          '0' => __( 'No', 'read-offline' ),
			//      ),
			//      //'description'      => __("Don't use PDFA if you don't have to", 'read-offline'),
			//      'default'          => '0',  // banana
			//      'help'             => __( 'Help text', 'read-offline' ),
			// )
		);
		/*
		$this->addSettingSections(
            array(
                'section_id'       => 'pdf_typography',
                'page_slug'        => 'read_offline_pdf',
                'title'            => __( 'Typography', 'read-offline')
            )
        );
		*/

		$this->addSettingSections(
			array(
				'section_id'       => 'pdf_header',
				'page_slug'        => 'read_offline_pdf',
				'title'            => __( 'Header and Footer', 'read-offline' ),
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'header',
				'section_id'       => 'pdf_header',
				'title'            => __( 'Header', 'read-offline' ),
				//'description'      => __( 'This is multiple sets of drop down list.', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
					'0'                                   => __( 'None', 'read-offline' ),
					'#fieldrow-pdf_header_default_header' => __( 'Default', 'read-offline' ),
					'#fieldrow-pdf_header_custom_header, #fieldrow-pdf_footer_css'  => __( 'Custom', 'read-offline' ),

				),
				'default'          => 1,
				'attributes'       => array(
					'select'               => array(
						'style'            => 'width: 200px; margin-right: 10px;',

					),
				),
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'default_header',
				'section_id'       => 'pdf_header',
				'title'            => __( 'Default Header', 'read-offline' ),
				//'description'      => __( 'This is multiple sets of drop down list.', 'read-offline' ),
				'type'             => 'select',
				'label'            => array(
					'0'                    => __( '-- Left --', 'read-offline' ),
					'author'               => __( 'Author', 'read-offline' ),
					'document_title'       => __( 'Document Title', 'read-offline' ),
					'document_url'         => __( 'Document URL', 'read-offline' ),
					'site_title'           => __( 'Site Title', 'read-offline' ),
					'site_url'             => __( 'Site URL', 'read-offline' ),
					'page_number'          => __( 'Page number', 'read-offline' ),
					'date'                 => __( 'Date', 'read-offline' ),
				),
				'default'          => 1,
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px; margin-right: 10px;',
					),
					'help'             => __( 'Help text', 'read-offline' ),
				),
				array(
					'label'        => array(
						'0'                => __( '-- Center --', 'read-offline' ),
						'author'               => __( 'Author', 'read-offline' ),
						'document_title'       => __( 'Document Title', 'read-offline' ),
						'document_url'         => __( 'Document URL', 'read-offline' ),
						'site_title'           => __( 'Site Title', 'read-offline' ),
						'site_url'             => __( 'Site URL', 'read-offline' ),
						'page_number'          => __( 'Page number', 'read-offline' ),
						'date'                 => __( 'Date', 'read-offline' ),
					),
					'default'      => 1,
					'attributes'   => array(
						'field'             => array(
							'style'                 => 'display: inline; clear: none', // this makes the field element inline, which means next fields continues from the right end of the field, not from the new line.
						),
					),
					'help'         => __( 'Help text', 'read-offline' ),
			   	),
				array(
					'label'        => array(
					  '0'                  => __( '-- Right --', 'read-offline' ),
					  'author'               => __( 'Author', 'read-offline' ),
					  'document_title'       => __( 'Document Title', 'read-offline' ),
					  'document_url'         => __( 'Document URL', 'read-offline' ),
					  'site_title'           => __( 'Site Title', 'read-offline' ),
					  'site_url'             => __( 'Site URL', 'read-offline' ),
					  'page_number'          => __( 'Page number', 'read-offline' ),
					  'date'                 => __( 'Date', 'read-offline' ),
				   	),
					//'description'  => 'xyz',
					'default'      => 1,
					'attributes'   => array(
						'field'             => array(
							'style'                  => 'display: inline; clear: none', // this makes the field element inline, which means next fields continues from the right end of the field, not from the new line.
						),
					),
				),
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'custom_header',
				'section_id'       => 'pdf_header',
				'title'            => __( 'Custom Header', 'read-offline' ),
				'type'             => 'ace',
				'attributes'       => array(
					'style'                 => 'width:100%;max-width:600px;height:100%;max-height:200px;',
				),
				'options'          => array(
					'language'             => 'html',
					'gutter'               => true,
				),
				'default'          => file_get_contents( READOFFLINE_PATH . '/templates/pdf/custom-print-header.html' ),
				'hidden'           => true,
				'description'      => __( 'The following aliases can be used:', 'read-offline' ) . ' {PAGENO}, {nb​}, {DATE}, {TODAY}, {TITLE}, {AUTHOR}, {DOCURL}, {SITENAME} ' . __( 'and','read-offline' ) . ' {SITEURL} <br />' . __( 'Custom CSS, below, will be used.', 'read-offline' ),
				'help'             => __( 'Help text', 'read-offline' ),
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'pdf_footer',    // the section ID
				'page_slug'        => 'read_offline_pdf',    // the page slug that the section belongs to
				//'title'        => __('Footer','read-offline')    // the section title
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'footer',
				'section_id'       => 'pdf_footer',
				'title'            => __( 'Footer', 'read-offline' ),
				//'description'      => __( 'This is multiple sets of drop down list.', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
					'0'                                    => __( 'None', 'read-offline' ),
					'#fieldrow-pdf_footer_default_footer'  => __( 'Default', 'read-offline' ),
					'#fieldrow-pdf_footer_custom_footer, #fieldrow-pdf_footer_css'   => __( 'Custom', 'read-offline' ),

				),
				'default'          => 1,
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px; margin-right: 10px;',

					),
				),
				'help'             => __( 'Help text', 'read-offline' ),
			),
			//Footer
			array(  // Drop-down Lists with Mixed Types
				'field_id'         => 'default_footer',
				'section_id'       => 'pdf_footer',
				'title'            => __( 'Standard Footer', 'read-offline' ),
				//'description'      => __( 'This is multiple sets of drop down list.', 'read-offline' ),
				'type'             => 'select',
				'label'            => array(
					'0'                    => __( '-- Left --', 'read-offline' ),
					'author'               => __( 'Author', 'read-offline' ),
					'document_title'       => __( 'Document Title', 'read-offline' ),
					'document_url'         => __( 'Document URL', 'read-offline' ),
					'site_title'           => __( 'Site Title', 'read-offline' ),
					'site_url'             => __( 'Site URL', 'read-offline' ),
					'page_number'          => __( 'Page number', 'read-offline' ),
					'date'                 => __( 'Date', 'read-offline' ),
				),
				'default'          => 1,
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px; margin-right: 10px;',
					),
					'hidden'           => true,
					'help'             => __( 'Help text', 'read-offline' ),
				 ),
				array(
					'label'        => array(
						'0'                => __( '-- Center --', 'read-offline' ),
						'author'           => __( 'Author', 'read-offline' ),
						'document_title'   => __( 'Document Title', 'read-offline' ),
						'document_url'     => __( 'Document URL', 'read-offline' ),
						'site_title'       => __( 'Site Title', 'read-offline' ),
						'site_url'         => __( 'Site URL', 'read-offline' ),
						'page_number'      => __( 'Page number', 'read-offline' ),
						'date'             => __( 'Date', 'read-offline' ),
					),
					'default'      => 1,
					'attributes'   => array(
						'field'            => array(
							'style'                => 'display: inline; clear: none', // this makes the field element inline, which means next fields continues from the right end of the field, not from the new line.
						),
					),
				 	'help'            => __( 'Help text', 'read-offline' ),
				 ),
				array(
					'label'        => array(
						'0'                => __( '-- Right --', 'read-offline' ),
						'author'           => __( 'Author', 'read-offline' ),
						'document_title'   => __( 'Document Title', 'read-offline' ),
						'document_url'     => __( 'Document URL', 'read-offline' ),
						'site_title'       => __( 'Site Title', 'read-offline' ),
						'site_url'         => __( 'Site URL', 'read-offline' ),
						'page_number'      => __( 'Page number', 'read-offline' ),
						'date'             => __( 'Date', 'read-offline' ),
					),
					//'description'  => 'xyz',
					'default'      => 1,
					'attributes'   => array(
						'field'            => array(
							'style'                 => 'display: inline; clear: none', // this makes the field element inline, which means next fields continues from the right end of the field, not from the new line.
					  	),
				  	),
			  	),
			 	'help'                => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'custom_footer',
				'section_id'       => 'pdf_footer',
				'title'            => __( 'Custom Footer', 'read-offline' ),
				'type'             => 'ace',
				'attributes'     => array(
					'style'                 => 'width:100%;max-width:600px;height:100%;max-height:200px;',
				),
				'options'          => array(
						'language'             => 'html',
						'gutter'               => true,
					),
				'default'          => file_get_contents( READOFFLINE_PATH . '/templates/pdf/custom-print-footer.html' ),
				'hidden'             => true,
				'description'      => __( 'The following aliases can be used:', 'read-offline' ) . ' {PAGENO}, {nb​}, {DATE}, {TODAY}, {TITLE}, {AUTHOR}, {DOCURL}, {SITENAME} ' . __( 'and','read-offline' ) . ' {SITEURL} <br />' . __( 'Custom CSS, below, will be used.', 'read-offline' ),
				'help'             => __( 'Help text', 'read-offline' ),
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'pdf_cover',    // the section ID
				'page_slug'        => 'read_offline_pdf',    // the page slug that the section belongs to
				'title'            => __( 'Cover Art and Style','read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			// array(  // Single set of radio buttons
			//     'field_id'         => 'style',
			//     'section_id'       => 'pdf_styles_cover',
			//     'title'            => __( 'PDF style', 'read-offline' ),
			//     //'description'      => 'Choose one from the radio buttons.',
			//     'type'             => 'revealer',
			//     'label'            => array(
			//        '0'                     => __( 'None', 'read-offline' ),
			//        'theme_style'           => __( 'The site theme style', 'read-offline' ),
			//       //'#fieldrow-pdf_styles_cover_css' => __( 'Custom CSS', 'read-offline' ),
			//     ),
			//     'default'          => 1,
			//     'attributes'       => array(
			//         'select'               => array(
			//             'style'                    =>  'width: 200px;',
			//         ),
			//     ),
			//    'help'              => __( 'Help text', 'read-offline' ),
			//  ),

			array(
				'field_id'         => 'art',
				'section_id'       => 'pdf_cover',
				'title'            => __( 'Cover Art', 'read-offline' ),
				'type'             => 'revealer',
				//'description'      => __( 'If ', 'read-offline'),
				'label'            => array(
					'none'                 => __( 'None', 'read-offline' ),
					'feature_image'        => __( 'Featured Image', 'read-offline' ),
					'#fieldrow-pdf_cover_custom_image' => __( 'Image (added below)', 'read-offline' ),
				),
				'default'          => 'default',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
				'help'              => __( 'Help text', 'read-offline' ),
			 ),
			array(
				'field_id'         => 'custom_image',
				'section_id'       => 'pdf_cover',
				//'title'          => __('Cover Art', 'read-offline' ),
				'type'             => 'image',
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'pdf_css',    // the section ID
				'page_slug'        => 'read_offline_pdf',    // the page slug that the section belongs to
				//'title'        => __('Footer','read-offline')    // the section title
			)
		);

		$this->addSettingFields(
			array(  // Single set of radio buttons
				'field_id'         => 'custom_css',
				'section_id'       => 'pdf_css',
				'title'            => __( 'Custom CSS', 'read-offline' ),
				//'description'      => __( 'Choose one from the radio buttons.',
				'type'             => 'revealer',
				'label'            => array(
						'0'                => __( 'No ', 'read-offline' ),
						'theme_style'           => __( 'The site theme style', 'read-offline' ),
						'#fieldrow-pdf_css_css'  => __( 'Yes (added below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'css',
				'section_id'       => 'pdf_css',
				//'title'           => 'Custom CSS',
				'description'      => __( 'Lorem ipsum <a href="http://mpdf1.com/manual/index.php?tid=307" target="_blank">using @page</a>', 'read-offline' ),
				'type'             => 'ace',
				'default'          => file_get_contents( READOFFLINE_PATH . '/templates/pdf/custom-print.css' ),
				'attributes'       => array(
					'style'                 => 'width:100%;max-width:600px;height:100%;max-height:400px;',
				),
				'options'          => array(
					'language'             => 'css', // available languages https://github.com/ajaxorg/ace/tree/master/lib/ace/mode
					'gutter'               => true,
				),
				'hidden'           => true,
				'help'             => __( 'Help text', 'read-offline' ),
			 )
			// array( // Reset Submit button
			//     'field_id'      => 'submit_button_reset',
			//     'section_id'    => 'pdf_css',
			//     'title'         => __( 'Reset Button', 'admin-page-framework-demo' ),
			//     'type'          => 'submit',
			//     'label_min_width'   => 0,
			//     'label'         => __( 'Reset', 'admin-page-framework-demo' ),
			//     'reset'         => 'pdf_css',
			//     'error_message' => 'hepp',
			//     'default'       => 'msg',
			//     'attributes'    => array(
			//         'class' => 'button button-secondary',
			//         'style'    => 'float: right;',
			//         'fieldset' => array(
			//             'style' => 'float: right;'
			//         ),
			//         'field'     => array(
			//             'style' => 'float: none;'
			//         ),
			//     ),
			//     'description'   => __( 'If you press this button, a confirmation message will appear and then if you press it again, it resets the option.', 'admin-page-framework-demo' ),
			// ),
			// array()
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'pdf_watermark',
				'page_slug'        => 'read_offline_pdf',
				'title'            => __( 'Watermark', 'read-offline' ),    // the section title
				//'description'    => __('For per post watermark, please use the shortcode', 'read-offline' )
			)
		);

		$this->addSettingFields(
			array(  // Single set of radio buttons
				'field_id'         => 'watermark',
				'section_id'       => 'pdf_watermark',
				'title'            => __( 'Watermark', 'read-offline' ),
				//'description'      => __( 'Choose one from the radio buttons.',
				'type'             => 'revealer',
				'label'            => array(
						'0'                => __( 'None ', 'read-offline' ),
						'#fieldrow-pdf_watermark_watermark_text, #fieldrow-pdf_watermark_watermark_tranparency'  => __( 'Text (added below)', 'read-offline' ),
						'#fieldrow-pdf_watermark_watermark_image, #fieldrow-pdf_watermark_watermark_tranparency' => __( 'Image (added below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
				'help'              => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'watermark_text',
				'section_id'       => 'pdf_watermark',
				'title'            => __( 'Text', 'read-offline' ),
				'type'             => 'text',
				'hidden'           => true,
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'watermark_image',
				'section_id'       => 'pdf_watermark',
				'title'            => __( 'Image', 'read-offline' ),
				'type'             => 'image',
				'hidden'           => true,
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'watermark_tranparency',
				'section_id'       => 'pdf_watermark',
				'title'            => __( 'Transparency (alpha value)', 'read-offline' ),
				'type'             => 'select',
				'label'            => array(
					'0.1' => '0.1',
					'0.2' => '0.2',
					'0.3' => '0.3',
					'0.4' => '0.4',
					'0.5' => '0.5',
					'0.6' => '0.6',
					'0.7' => '0.7',
					'0.8' => '0.8',
					'0.9' => '0.9',
					'1'   => '1',
				  ),
				'default'          => '0.2',
				'hidden'         => true,
				'help'             => __( 'Help text', 'read-offline' ),
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'pdf_protection',    // the section ID
				'page_slug'        => 'read_offline_pdf',    // the page slug that the section belongs to
				'title'            => __( 'Protection', 'read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(  // Single set of radio buttons
				'field_id'         => 'protection',
				'section_id'       => 'pdf_protection',
				'title'            => __( 'Password Protection', 'read-offline' ),
				//'description'      => __( 'Choose one from the radio buttons.',
				'type'             => 'revealer',
				'label'            => array(
						'0'                => __( 'No ', 'read-offline' ),
						'#fieldrow-pdf_protection_password_owner, #fieldrow-pdf_protection_password_user, #fieldrow-pdf_protection_user_can_do'  => __( 'Yes (added below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'password_owner',
				'section_id'       => 'pdf_protection',
				'title'            => __( 'Owners Password', 'read-offline' ),
				'description'      => __( 'If empty, a random (unknown) password will be generated', 'read-offline' ),
				'type'             => 'text',
				'hidden'           => true,
				'help'             => __( 'Help text', 'read-offline' ),
			 ),
			array(
				'field_id'         => 'password_user',
				'section_id'       => 'pdf_protection',
				'title'            => __( 'User Password', 'read-offline' ),
				'description'      => __( 'If empty, no password is required to open the PDF document', 'read-offline' ),
				'type'             => 'text',
				'hidden'           => true,
				'help'             => __( 'Help text', 'read-offline' ),
			 ),
			array(  // Multiple Checkboxes
				'field_id'         => 'user_can_do',
				'section_id'       => 'pdf_protection',
				'title'            => __( 'User can', 'read-offline' ),
				'type'             => 'checkbox',
				'label'            => array(
					  'copy'               => __( 'Copy', 'read-offline' ),
					  'print'              => __( 'Print', 'read-offline' ),
					  'modify'             => __( 'Modify', 'read-offline' ),
					  'extract'            => __( 'Extract', 'read-offline' ),
					  'assemble'           => __( 'Assemble', 'read-offline' ),
					  'print-highres'      => __( 'Print High Resolution', 'read-offline' ),
				),
				'default'          => array( 'print' => true ),
				'hidden'           => true,
				'help'             => __( 'Help text', 'read-offline' ),
			)
		);

		// ePub
		$this->addSubMenuPage(
			array(
				'title'            => 'ePub',
				'page_slug'        => 'read_offline_epub',
				'screen_icon'      => 'page',
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'epub',    // the section ID
				'page_slug'        => 'read_offline_epub',    // the page slug that the section belongs to
				'title'            => __( 'ePub Options','read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(  // Single set of radio buttons
				'field_id'         => 'add_toc',
				'section_id'       => 'epub',
				'title'            => __( 'Table of Contents', 'read-offline' ),
				'description'      => __( 'Automatically generate entries for a Table of Contents using a heading element (H1 - H5)', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
						'0'                => __( 'No ', 'read-offline' ),
						'#fieldrow-epub_toc'  => __( 'Yes (configured below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'toc',
				'section_id'       => 'epub',
				//'title'           => 'Custom CSS',
				'description'      => __( 'Document will be split into chapters based on the header selected', 'read-offline' ),
				'type'             => 'select',
				'label'            => array(
					'0'                    => __( 'Select Heading', 'read-offline' ),
					'1'                    => __( 'H1', 'read-offline' ),
					'2'                    => __( 'H2', 'read-offline' ),
					'3'                    => __( 'H3', 'read-offline' ),
					'4'                    => __( 'H4', 'read-offline' ),
					'5'                    => __( 'H5', 'read-offline' ),
					'6'                    => __( 'H6', 'read-offline' ),
					'all'                  => __( 'All (H1-H6)', 'read-offline' ),
			   	),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px; margin-right: 10px;',

					),
					'help'             => __( 'Help text', 'read-offline' ),
				),
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'art',
				'section_id'       => 'epub',
				'title'            => __( 'Cover Art', 'read-offline' ),
				'type'             => 'revealer',
				//'description'      => __( 'If ', 'read-offline'),
				'label'            => array(
					'none'                 => __( 'None', 'read-offline' ),
					'feature_image'        => __( 'Featured Image', 'read-offline' ),
					'#fieldrow-epub_custom_image' => __( 'Image (added below)', 'read-offline' ),
				),
				'default'          => 'none',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
			   	'help'              => __( 'Help text', 'read-offline' ),
			 ),
			array(
				'field_id'         => 'custom_image',
				'section_id'       => 'epub',
				'type'             => 'image',
			),
			array(  // Single set of radio buttons
				'field_id'         => 'style',
				'section_id'       => 'epub',
				'title'            => __( 'ePub Style', 'read-offline' ),
				//'description'      => __( 'Choose one from the radio buttons.', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
					  '0'                  => __( 'None', 'read-offline' ),
					  //'theme_style'        => __( 'The site theme style', 'read-offline' ),
					  '#fieldrow-epub_css' => __( 'Custom CSS (added below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
			   	'help'              => __( 'Help text', 'read-offline' ),
			),
			array(  // Text Area
				'field_id'         => 'css',
				'section_id'       => 'epub',
				'title'            => __( 'Style Editor', 'read-offline' ),
				'description'      => __( 'Type a text string here.', 'read-offline' ),
				'type'             => 'ace',
				'default'          => '',
				'attributes'       => array(
					'style'                 => 'width:100%;max-width:600px;height:100%;max-height:200px;',
				),
				'options'          => array(
				  'language'               => 'css', // available languages https://github.com/ajaxorg/ace/tree/master/lib/ace/mode
				  // 'theme'               => 'chrome', //available themes https://github.com/ajaxorg/ace/tree/master/lib/ace/theme
				  'gutter'                 => true,
				  // 'readonly'            => false
				),
				'hidden'           => true,
				'help'              => __( 'Help text', 'read-offline' ),
			 )
		);

		// mobi
		$this->addSubMenuPage(
			array(
				'title'            => 'mobi',
				'page_slug'        => 'read_offline_mobi',
				'screen_icon'      => 'page',
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'mobi',    // the section ID
				'page_slug'        => 'read_offline_mobi',    // the page slug that the section belongs to
				'title'            => __( 'mobi Options' , 'read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(
				'field_id'         => 'add_toc',
				'section_id'       => 'mobi',
				'title'            => __( 'Table of Contents', 'read-offline' ),
				'description'      => __( 'Automatically generate entries for a Table of Contents using a heading element (H1 - H5)', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
						'0'                => __( 'No ', 'read-offline' ),
						'#fieldrow-mobi_toc'  => __( 'Yes (configured below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
				'help'             => __( 'Help text', 'read-offline' ),
			),
			array(
				'field_id'         => 'toc',
				'section_id'       => 'mobi',
				//'title'           => 'Custom CSS',
				'description'      => __( 'Document will be split into chapters based on the header selected', 'read-offline' ),
				'type'             => 'select',
				'label'            => array(
					'0'                    => __( 'Select Heading', 'read-offline' ),
					'1'                    => __( 'H1', 'read-offline' ),
					'2'                    => __( 'H2', 'read-offline' ),
					'3'                    => __( 'H3', 'read-offline' ),
					'4'                    => __( 'H4', 'read-offline' ),
					'5'                    => __( 'H5', 'read-offline' ),
					'6'                    => __( 'H6', 'read-offline' ),
					'all'                  => __( 'All (H1-H6)', 'read-offline' ),
			   	),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px; margin-right: 10px;',

					),
					'help'             => __( 'Help text', 'read-offline' ),
				),
			)
		);

		$this->addSettingFields(
			array( // Image Selector
				'field_id'         => 'mobi_cover_image',
				'section_id'       => 'mobi',
				'title'            => __( 'Cover Art', 'read-offline' ),
				'type'             => 'image',
				'help'             => __( 'Help text', 'read-offline' ),
			)
		);

		/*
		// DoxX
		$this->addSubMenuPage(
            array(
                'title'               => 'DocX',
                'page_slug'           => 'read_offline_docx',
                'screen_icon'         => 'page'
            )
        );


		$this->addSettingSections(
            array(
                'section_id'       => 'docx',    // the section ID
                'page_slug'        => 'read_offline_docx',    // the page slug that the section belongs to
                'title'           => __('DocX Options','read-offline')    // the section title
                //'help'            => __('Lorem imspum docx','read-offline')
            )
        );
        // $this->addHelpTab(
        //     array(
        //         'page_slug'        => 'read_offline_docx',    // ( mandatory )
        //         // 'page_tab_slug'  => null,    // ( optional )
        //         'help_tab_title'    => __('DocX Options','read-offline'),
        //         'help_tab_id'       => 'general_options_help_docx_header',  // ( mandatory )
        //         'help_tab_content'  => __( 'TBA', 'admin-page-framework' ),
        //         //'help_tab_sidebar_content'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
        //     )
        // );

        $this->addSettingFields(
            array( // Image Selector
                'field_id'        => 'docx_cover_image',
                'section_id'      => 'docx',
                'title'          => __('Cover Art', 'read-offline' ),
                'type'           => 'image',
                'order'          => 1,
                'attributes'    => array(
                        'input'     => array(
                            'style' => 'width: 100%;max-width:600px;',
                        ),
                ),
                'help'      => __( 'Help text', 'read-offline' ),
             ),
            array(  // Single set of radio buttons
                'field_id'     => 'docx_pagenumber',
                'section_id'   => 'docx',
                'title'       => __('Page Numbers?','read-offline'),
                //'description' => 'Choose one from the radio buttons.',
                'type'        => 'radio',
                'label'         => array( '1' => 'Yes', '0' => 'No' ),
                'default'       => '0',  // banana
                'help'      => __( 'Help text', 'read-offline' ),
            )
        );
		*/

		// Print
		$this->addSubMenuPage(
			array(
				'title'            => __( 'Print', 'read-offline' ),
				'page_slug'        => 'read_offline_print',
				'screen_icon'      => 'page',
			)
		);

		$this->addSettingSections(
			array(
				'section_id'       => 'print',    // the section ID
				'page_slug'        => 'read_offline_print',    // the page slug that the section belongs to
				'title'            => __( 'Print Options','read-offline' ),// the section title
			)
		);

		$this->addSettingFields(
			array(  // Single set of radio buttons
				'field_id'         => 'header',
				'section_id'       => 'print',
				'title'            => __( 'Add Print Header?', 'read-offline' ),
				//'description'      => __( 'Choose one from the radio buttons.', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
					  '0'                          => __( 'No', 'read-offline' ),
					  '#fieldrow-print_headertext' => __( 'Yes (added below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
			   	'help'              => __( 'Help text', 'read-offline' ),
			 ),
			array(  // Text Area
				'field_id'         => 'headertext',
				'section_id'       => 'print',
				//'title'           => 'Custom Style',
				'description'      => __( 'Enter header text here. The following aliases can be used:', 'read-offline' ) . ' {DATE}, {TODAY}, {TITLE}, {AUTHOR}, {DOCURL}, {SITENAME} ' . __( 'and','read-offline' ) . ' {SITEURL}',
				'type'             => 'textarea',
				'default'          => "\"{TITLE}\" is written by {AUTHOR}. You'll find the original article at {DOCURL}",
				'attributes'       => array(
					'style'                 => 'width:600px;max-width:600px;height:100%;max-height:200px;',
				),
				'hidden'           => true,
			 ),
			array(  // Single set of radio buttons
				'field_id'         => 'style',
				'section_id'       => 'print',
				'title'            => __( 'Print Style', 'read-offline' ),
				//'description'      => __( 'Choose one from the radio buttons.', 'read-offline' ),
				'type'             => 'revealer',
				'label'            => array(
					  '0'                          => __( 'None', 'read-offline' ),
					  'theme_style'                => __( 'The site theme style', 'read-offline' ),
					  '#fieldrow-print_css' => __( 'Custom CSS (added below)', 'read-offline' ),
				),
				'default'          => '0',
				'attributes'       => array(
					'select'               => array(
						'style'                    => 'width: 200px;',
					),
				),
			   	'help'              => __( 'Help text', 'read-offline' ),
			 ),
			array(  // Text Area
				'field_id'         => 'css',
				//'section_id'       => 'print',
				//'title'            => __('Print Style', 'read-offline' ),
				//'description'      => __('Type a text string here.', 'read-offline' ),
				'type'             => 'ace',
				'default'          => file_get_contents( READOFFLINE_PATH . '/templates/print/custom-print.css' ),
				'attributes'       => array(
					'style'                 => 'width:100%;max-width:600px;height:100%;max-height:400px;',
				),
				'options'          => array(
				  // 'language'            => 'css', // available languages https://github.com/ajaxorg/ace/tree/master/lib/ace/mode
				  // 'theme'               => 'chrome', //available themes https://github.com/ajaxorg/ace/tree/master/lib/ace/theme
				  'gutter'                 => true,
				  // 'readonly'            => false
				),
				'hidden'           => true,
				'help'             => __( 'Help text', 'read-offline' ),
			 )
		);

		// About (change to readme?)
		// $this->addSubMenuPage(
		//      array(
		//          'title'            => __( 'About', 'read-onlie' ),
		//          'page_slug'        => 'read_offline_faq',
		//          'screen_icon'      => 'edit-comments'
		//      )
		// );
		// Issues
		$this->addSubMenuLink(
			array(
				'title'            => '<span class="warning">' . __( 'Please report issues', 'read-offline' ) . '</span>',
				'href'             => 'https://github.com/soderlind/read-offline/issues/new',
				'capability'       => null,
				'order'            => null,
				'show_page_heading_tab' => false,
			)
		);

		//MISC
		$this->addLinkToPluginDescription(
			"Change the settings for: <a href='admin.php?page=read_offline_options'>General Options</a>",
			"<a href='admin.php?page=read_offline_pdf'>PDF</a>",
			"<a href='admin.php?page=read_offline_epub'>ePub</a>",
			"<a href='admin.php?page=read_offline_mobi'>mobi</a>",
			// "<a href='admin.php?page=read_offline_docx'>DocX</a>",
			"<a href='admin.php?page=read_offline_print'>Print</a>",
			// "<a href='admin.php?page=read_offline_faq'>About</a>",
			"Please <a href='https://github.com/soderlind/read-offline/issues/new'>report issues</a> at GitHub"
		);

	}

	public function do_Read_Offline_Admin_Settings() {
		$screen = get_current_screen();

		if ( 'read-offline_page_read_offline_faq' != $screen->id ) {
			submit_button();
		}

		// update_option( 'Read_Offline_Admin_Settings', '' );

		//printf("<pre>%s</pre>", print_r(get_option( 'Read_Offline_Admin_Settings' ),true));

		//echo $this->oDebug->getArray( get_option( 'Read_Offline_Admin_Settings' ) );
	}
}

// Instantiate the class object.
// if ( is_admin() )
//     new Read_Offline_Settings ('Read_Offline_Settings', dirname(dirname(__FILE__)). '/plugin.php');
