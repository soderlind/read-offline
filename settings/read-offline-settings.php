<?php

 
// Include the library
if ( ! class_exists( 'AdminPageFramework' ) ) 
    include_once( dirname( __FILE__ ) . '/class/admin-page-framework.php' );
 
// extend the class
class Read_Offline_Settings extends AdminPageFramework {
 
    // Define the setup method to set how many pages, page titles and icons etc.
    public function setUp() {
        // Root menu
        $this->setRootMenuPage( 
            'Read Offline',    // specify the name of the page group
            plugins_url( 'images/read_offline16x16.png', __FILE__ )
        );    

// General Options            
        $this->addSubMenuPage(    
            'General Options',        // page title
            'read_offline_options',    // page slug
            'options-general'
        ); 

        $this->addHelpTab(
            array(
                'strPageSlug'         => 'read_offline_options',    // ( mandatory )
                // 'strPageTabSlug'   => null,    // ( optional )
                'strHelpTabTitle'     => 'Introduction',
                'strHelpTabID'        => 'general_options_help_introduction',  // ( mandatory )
                'strHelpTabContent'   => __( 'aaaa This contextual help text can be set with the addHelpTab() method.', 'admin-page-framework' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),

            )
        );
        // what
        $this->addSettingSections(
            array(
                'strSectionID'       => 'what',    // the section ID
                'strPageSlug'        => 'read_offline_options',    // the page slug that the section belongs to
                'strTitle'           => __('What','read-offline')    // the section title
            )
        );        
        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_options',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => 'What',
                'strHelpTabID'       => 'general_options_help_what',  // ( mandatory )
                'strHelpTabContent'  => __( 'what what what', 'admin-page-framework' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );

        $this->addSettingFields(
                array(  // Multiple Checkboxes
                    'strFieldID'     => 'formats',
                    'strSectionID'   => 'what',
                    'strTitle'       => 'Formats available for your visitors',
                    'strDescription' => __(
                        "If direct linking to ePub, mobi and DocX doesn't work, add the following to your .htaccess file:
                        <pre>AddType application/epub+zip epub\nAddType application/x-mobipocket-ebook mobi\nAddType application/vnd.openxmlformats-officedocument.wordprocessingml.document docx</pre>",'read-offline'),
                    'strType'        => 'checkbox',
                    'vLabel'         => array( 'pdf' => 'PDF', 'epub' => 'ePub', 'mobi' => 'mobi', 'docx' => 'DocX' ),
                    'vDefault'       => array( 'pdf' => True, 'epub' => True, 'mobi' => True, 'docx' => False ),
                    'vDisable'       => array('docx' => True),
                    'numOrder'       => 1,
                )
        );

        // where
        $this->addSettingSections(
            array(
                'strSectionID'       => 'where',    // the section ID
                'strPageSlug'        => 'read_offline_options',    // the page slug that the section belongs to
                'strTitle'           => __('Where','read-offline')    // the section title
            )
        ); 
 
        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_options',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => 'Where',
                'strHelpTabID'       => 'general_options_help_where',  // ( mandatory )
                'strHelpTabContent'  => __( 'where where where', 'admin-page-framework' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );


        $this->addSettingFields(     
                array(  // Multiple Checkboxes
                    'strFieldID'     => 'placements',
                    'strSectionID'   => 'where',
                    'strTitle'       => 'Placements',
                    'strDescription' => __("Also available via the the Read Offline widget and the [readoffline] shortcode.",'read-offline'),
                    'strType'        => 'checkbox',
                    'vLabel'         => array( 'top' => 'Top of post type', 'bottom' => 'Bottom of post type'),
                    'vDefault'       => array( 'top' => True, 'bottom' => True),
                    'numOrder'       => 1,
                ),
                array(
                    'strFieldID'     => 'post_types',
                    'strSectionID'   => 'where',
                    'strTitle'       => 'Post Types',
                    'strType'        => 'posttype',
                    'strDescription' => __("supported post types lorem ipsum",'read-offline'),
                    'vDefault'       => array( 'post' => True, 'page' => True),
                    'numOrder'       => 2,
                )
        );
        // how
        $this->addSettingSections(
            array(
                'strSectionID'       => 'how',    // the section ID
                'strPageSlug'        => 'read_offline_options',    // the page slug that the section belongs to
                'strTitle'           => __('How','read-offline')    // the section title
            )
        ); 

        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_options',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => 'How',
                'strHelpTabID'       => 'general_options_help_how',  // ( mandatory )
                'strHelpTabContent'  => __( 'how how how', 'admin-page-framework' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );

        $this->addSettingFields( 
                array(    // Single text field
                    'strFieldID'     => 'link_text',
                    'strSectionID'   => 'how',
                    'strTitle'       => 'Download link text',   
                    'strType'        => 'text',
                    'vDefault'       => 'Read Offline: ',
                    'strDescription' => __("Use %title% to insert the post type title",'read-offline'),
                    'vSize'          => 40,
                    'numOrder'       => 1,
                ),
                array(  // Single set of radio buttons
                    'strFieldID'     => 'icons_only',
                    'strSectionID'   => 'how',
                    'strTitle'       => __('Download link, icons only?','read-offline'),
                    //'strDescription' => 'Choose one from the radio buttons.',
                    'strType'        => 'radio',
                    'vLabel'         => array( '1' => 'Yes', '0' => 'No' ),
                    'vDefault'       => '0',  // banana               
                )
        );
        // miscellaneous
        $this->addSettingSections(
            array(
                'strSectionID'       => 'misc',    // the section ID
                'strPageSlug'        => 'read_offline_options',    // the page slug that the section belongs to
                'strTitle'           => __('Miscellaneous','read-offline')    // the section title
            )
        ); 

        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_options',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => 'Miscellaneous',
                'strHelpTabID'       => 'general_options_help_misc',  // ( mandatory )
                'strHelpTabContent'  => __( 'TBA', 'admin-page-framework' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );
        $this->addSettingFields( 
                array(  // Single set of radio buttons
                    'strFieldID'     => 'google',
                    'strSectionID'   => 'misc',
                    'strTitle'       => __('Google Analytics, track downloads:','read-offline'),
                    'strDescription' => __('Track read-offline events, you can find these under Content » Events in your Google Analytics reports. Assumes you’re using the <a href="https://developers.google.com/analytics/devguides/collection/gajs/asyncTracking">Asynchronous version of Google Analytics</a>','read-offline'),
                    'strType'        => 'radio',
                    'vLabel'         => array( '1' => 'Yes', '0' => 'No' ),
                    'vDefault'       => '1',                
                )
        );

// PDF
        $this->addSubMenuPage(    
            'PDF', 
            'read_offline_pdf',
            'page'
        );


       $this->addSettingSections(
            array(
                'strSectionID'       => 'header_footer',    // the section ID
                'strPageSlug'        => 'read_offline_pdf',    // the page slug that the section belongs to
                'strTitle'           => __('Header and Footer','read-offline')    // the section title
            )
        );        
        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_pdf',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => 'Header and Footer',
                'strHelpTabID'       => 'general_options_help_pdf_header',  // ( mandatory )
                'strHelpTabContent'  => __( 'TBA', 'admin-page-framework' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );

        $this->addSettingFields(
            array(  // Drop-down Lists with Mixed Types
                'strFieldID'         => 'pdf_header',
                'strSectionID'       => 'header_footer',
                'strTitle'           => __( 'Header', 'admin-page-framework-demo' ),
                'strDescription'     => __( 'This is multiple sets of drop down list.', 'admin-page-framework-demo' ),
                'strType'            => 'select',
                'vLabel'             => array( 
                /*
                    'option1' => 'Document Title'
    , 'option2' => 'Site URL'
    , 'option3' => 'Site Title'
    , 'option4' => 'Page number'
                 */
                    'left'   => array( 
                            ''           => 'Left',
                            'document_title' => 'Document Title', 
                            'site_url'       => 'Site URL',
                            'site_title'     => 'Site Title',
                            'page_number'    => 'Page number',
                            'custom'         => 'Custom (added below)' 
                    ),
                    'center' => array( 
                            ''               => 'Center',
                            'document_title' => 'Document Title', 
                            'site_url'       => 'Site URL',
                            'site_title'     => 'Site Title',
                            'page_number'    => 'Page number', 
                            'custom'         => 'Custom (added below)' 
                    ),
                    'right'  => array( 
                            ''               => 'Right',
                            'document_title' => 'Document Title', 
                            'site_url'       => 'Site URL',
                            'site_title'     => 'Site Title',
                            'page_number'    => 'Page number',
                            'custom'         => 'Custom (added below)' 
                    )
                ),
                'vWidth'             => '200px',
                'vDefault'           => array(0,0,0),
            ),
            array(  // Text Area
                'strFieldID'         => 'custom_header',
                'strSectionID'       => 'header_footer',
                'strTitle'           => 'Custom Header',
                'strDescription'     => 'Type a text string here.',
                'strType'            => 'textarea',
                'vDefault'           => '',
                'vRows'              => 3,
                'vCols'              => 85,
            ),
            array(  // Drop-down Lists with Mixed Types
                'strFieldID'         => 'pdf_footer',
                'strSectionID'       => 'header_footer',
                'strTitle'           => __( 'Footer', 'admin-page-framework-demo' ),
                'strDescription'     => __( 'This is multiple sets of drop down list.', 'admin-page-framework-demo' ),
                'strType'            => 'select',
                'vLabel'             => array( 
                    'left'   => array( 
                            ''           => 'Left',
                            'document_title' => 'Document Title', 
                            'site_url'       => 'Site URL',
                            'site_title'     => 'Site Title',
                            'page_number'    => 'Page number', 
                            'custom'         => 'Custom (added below)' 
                    ),
                    'center' => array( 
                            ''               => 'Center',
                            'document_title' => 'Document Title', 
                            'site_url'       => 'Site URL',
                            'site_title'     => 'Site Title',
                            'page_number'    => 'Page number', 
                            'custom'         => 'Custom (added below)' 
                    ),
                    'right'  => array( 
                            ''               => 'Right',
                            'document_title' => 'Document Title', 
                            'site_url'       => 'Site URL',
                            'site_title'     => 'Site Title',
                            'page_number'    => 'Page number', 
                            'custom'         => 'Custom (added below)' 
                      )
               ),
                'vWidth'             => '200px',
                'vDefault'           => array(0,0,0),
            ),
            array(  // Text Area
                'strFieldID'         => 'custom_footer',
                'strSectionID'       => 'header_footer',
                'strTitle'           => 'Custom Footer',
                'strDescription'     => 'Type a text string here.',
                'strType'            => 'textarea',
                'vDefault'           => '',
                'vRows'              => 3,
                'vCols'              => 85,
            )
        );


       $this->addSettingSections(
            array(
                'strSectionID'       => 'pdf_styles_cover',    // the section ID
                'strPageSlug'        => 'read_offline_pdf',    // the page slug that the section belongs to
                'strTitle'           => __('Styles and Cover Art','read-offline')    // the section title
            )
        );        


        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_pdf',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => __('CSS & Styles','read-offline'),
                'strHelpTabID'       => 'general_options_help_pdf_styles',  // ( mandatory )
                'strHelpTabContent'  => __( 'TBA', 'admin-page-framework' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );

        $this->addSettingFields(
            array(  // Single set of radio buttons
                'strFieldID'         => 'pdf_style',
                'strSectionID'       => 'pdf_styles_cover',
                'strTitle'           => 'PDF style',
                'strDescription'     => 'Choose one from the radio buttons.',
                'strType'            => 'radio',
                'vLabel'             => 
                    array( 
                        'default' => 'Default (none)', 
                        'theme_style' => 'The site theme style', 
                        'style_editor' => 'Style editor'
                ),
                'vDefault'           => 'default'              
            ),
            array(  // Text Area
                'strFieldID'         => 'pdf_style_editor',
                'strSectionID'       => 'pdf_styles_cover',
                'strTitle'           => 'Style Editor',
                'strDescription'     => 'Type a text string here.',
                'strType'            => 'textarea',
                'vDefault'           => '',
                'vRows'              => 8,
                'vCols'              => 85,
            ),
            array( // Image Selector
                'strFieldID'        => 'pdf_cover_image',
                'strSectionID'      => 'pdf_styles_cover',
                'strTitle'          => __('Cover Art', 'read-offline' ),
                'strType'           => 'image',
                //'numOrder'          => 1,
            )
        );


       $this->addSettingSections(
            array(
                'strSectionID'       => 'pdf_watermark',    // the section ID
                'strPageSlug'        => 'read_offline_pdf',    // the page slug that the section belongs to
                'strTitle'           => __('Watermark','read-offline'),    // the section title
                //'strDescription'    => __('For per post watermark, please use the shortcode', 'read-offline' )
           )
        );        
        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_pdf',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    =>  __('Watermark','read-offline') ,
                'strHelpTabID'       => 'general_options_help_pdf_watermark',  // ( mandatory )
                'strHelpTabContent'  => __( 'TBA', 'read-offline' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );

        $this->addSettingFields(
            array(
                'strFieldID'        => 'pdf_watermark_text',
                'strSectionID'      => 'pdf_watermark',
                'strTitle'          => __('Text', 'read-offline' ),
                'strType'           => 'text',
                'vSize'             => 85,
            ),
            array( // Image Selector
                'strFieldID'        => 'pdf_watermark_image',
                'strSectionID'      => 'pdf_watermark',
                'strTitle'          => __('Image', 'read-offline' ),
                'strType'           => 'image'
            ),
            array(
                'strFieldID'        => 'pdf_watermark_tranparency',
                'strSectionID'      => 'pdf_watermark',
                'strTitle'          => __( 'Transparency (alpha value)', 'read-offline' ),
                'strType'           => 'number',
                'vMax'              => '1.0',
                'vMin'              => '0.0',
                'vStep'             => '0.1',
                //'strDescription' => 'alpha value',
                'vDefault'          =>  0
            )
        );

      $this->addSettingSections(
            array(
                'strSectionID'       => 'pdf_protection',    // the section ID
                'strPageSlug'        => 'read_offline_pdf',    // the page slug that the section belongs to
                'strTitle'           => __('Protection','read-offline')    // the section title                
            )
        );        
        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_pdf',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    =>  __('Protection','read-offline') ,
                'strHelpTabID'       => 'general_options_help_pdf_protection',  // ( mandatory )
                'strHelpTabContent'  => __( 'TBA', 'read-offline' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );

        $this->addSettingFields(
            array(
                'strFieldID'        => 'pdf_protection_password_owner',
                'strSectionID'      => 'pdf_protection',
                'strTitle'          => __('Owners Password', 'read-offline' ),
                'strDescription'    => __('Empty equals no protection', 'read-offline' ),
                'strType'           => 'text',
                'vSize'             => 85,
            ),
            array(
                'strFieldID'        => 'pdf_protection_password_user',
                'strSectionID'      => 'pdf_protection',
                'strTitle'          => __('User Password', 'read-offline' ),
                'strDescription'    => __('For per post password, use the shortcode', 'read-offline' ),
                'strType'           => 'text',
                'vSize'             => 85,
            ),
            array(  // Multiple Checkboxes
                'strFieldID'        => 'pdf_protection_can_do',
                'strSectionID'      => 'pdf_protection',
                'strTitle'          => 'User can',
                'strType'           => 'checkbox',
                'vLabel'            => 
                    array( 
                        'copy' => 'Copy', 
                        'print' => 'Print', 
                        'modify' => 'Modify' 
                ),
            )
        );




// ePub
        $this->addSubMenuPage(
            'ePub',
            'read_offline_epub',
            'page'
        );

       $this->addSettingSections(
            array(
                'strSectionID'       => 'epub',    // the section ID
                'strPageSlug'        => 'read_offline_epub',    // the page slug that the section belongs to
                'strTitle'           => __('ePub Options','read-offline')    // the section title
            )
        );  
        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_epub',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => __('ePub Options','read-offline'),
                'strHelpTabID'       => 'general_options_help_epub',  // ( mandatory )
                'strHelpTabContent'  => __( 'TBA', 'read-offline' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );
        
        $this->addSettingFields(
            array( // Image Selector
                'strFieldID'        => 'epub_cover_image',
                'strSectionID'      => 'epub',
                'strTitle'          => __('Cover Art', 'read-offline' ),
                'strType'           => 'image',
                'numOrder'          => 1,
            ),
            array(  // Text Area
                'strFieldID'         => 'style_editor',
                'strSectionID'       => 'epub',
                'strTitle'           => __('Style Editor', 'read-offline' ),
                'strDescription'     => __('Type a text string here.', 'read-offline' ),
                'strType'            => 'textarea',
                'vDefault'           => '',
                'vRows'              => 8,
                'vCols'              => 85,
            )
        );
        /*
        setCoverImage
         */



// mobi
        $this->addSubMenuPage(
            'mobi',
            'read_offline_mobi',
            'page'
        );

        $this->addSettingSections(
            array(
                'strSectionID'       => 'mobi',    // the section ID
                'strPageSlug'        => 'read_offline_mobi',    // the page slug that the section belongs to
                'strTitle'           => __('mobi Options','read-offline')    // the section title
            )
        );  
        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_mobi',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => __('mobi options','read-offline'),
                'strHelpTabID'       => 'general_options_help_mobi',  // ( mandatory )
                'strHelpTabContent'  => __( 'TBA', 'read-offline' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );
        
        $this->addSettingFields(
            array( // Image Selector
                'strFieldID'        => 'mobi_cover_image',
                'strSectionID'      => 'mobi',
                'strTitle'          => __('Cover Art', 'read-offline' ),
                'strType'           => 'image',
                'numOrder'          => 1,
            )
        );

// FAQ
       $this->addSubMenuPage(
            'DocX',
            'read_offline_docx',
            'page'
        );


       $this->addSettingSections(
            array(
                'strSectionID'       => 'docx',    // the section ID
                'strPageSlug'        => 'read_offline_docx',    // the page slug that the section belongs to
                'strTitle'           => __('DocX Options','read-offline')    // the section title
                //'strHelp'            => __('Lorem imspum docx','read-offline') 
            )
        );        
        $this->addHelpTab(
            array(
                'strPageSlug'        => 'read_offline_docx',    // ( mandatory )
                // 'strPageTabSlug'  => null,    // ( optional )
                'strHelpTabTitle'    => __('DocX Options','read-offline'),
                'strHelpTabID'       => 'general_options_help_docx_header',  // ( mandatory )
                'strHelpTabContent'  => __( 'TBA', 'admin-page-framework' ),
                //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
            )
        );

        $this->addSettingFields(
            array( // Image Selector
                'strFieldID'        => 'docx_cover_image',
                'strSectionID'      => 'docx',
                'strTitle'          => __('Cover Art', 'read-offline' ),
                'strType'           => 'image',
                'numOrder'          => 1,
            ),
            array(  // Single set of radio buttons
                'strFieldID'     => 'docx_pagenumber',
                'strSectionID'   => 'docx',
                'strTitle'       => __('Page Numbers?','read-offline'),
                //'strDescription' => 'Choose one from the radio buttons.',
                'strType'        => 'radio',
                'vLabel'         => array( '1' => 'Yes', '0' => 'No' ),
                'vDefault'       => '0',  // banana               
            )
        );

       // $this->addSettingSections(
       //      array(
       //          'strSectionID'       => 'docx_header_footer',    // the section ID
       //          'strPageSlug'        => 'read_offline_docx',    // the page slug that the section belongs to
       //          'strTitle'           => __('Header and Footer','read-offline')    // the section title
       //          //'strHelp'            => __('Lorem imspum docx','read-offline') 
       //      )
       //  );        
       //  $this->addHelpTab(
       //      array(
       //          'strPageSlug'        => 'read_offline_docx',    // ( mandatory )
       //          // 'strPageTabSlug'  => null,    // ( optional )
       //          'strHelpTabTitle'    => 'Header and Footer',
       //          'strHelpTabID'       => 'general_options_help_docx_header',  // ( mandatory )
       //          'strHelpTabContent'  => __( 'TBA', 'admin-page-framework' ),
       //          //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
       //      )
       //  );

       //  $this->addSettingFields(
       //      array(  // Drop-down Lists with Mixed Types
       //          'strFieldID'         => 'docx_header',
       //          'strSectionID'       => 'docx_header_footer',
       //          'strTitle'           => __( 'Header', 'admin-page-framework-demo' ),
       //          'strDescription'     => __( 'To be added.', 'admin-page-framework-demo' ),
       //          'strType'            => 'select',
       //          'vLabel'             => array( 
       //              'left'   => array( 
       //                      ''           => 'Left',
       //                      'document_title' => 'Document Title', 
       //                      'site_url'       => 'Site URL',
       //                      'site_title'     => 'Site Title',
       //                      'page_number'    => 'Page number' 
       //              ),
       //              'center' => array( 
       //                      ''               => 'Center',
       //                      'document_title' => 'Document Title', 
       //                      'site_url'       => 'Site URL',
       //                      'site_title'     => 'Site Title',
       //                      'page_number'    => 'Page number' 
       //              ),
       //              'right'  => array( 
       //                      ''               => 'Right',
       //                      'document_title' => 'Document Title', 
       //                      'site_url'       => 'Site URL',
       //                      'site_title'     => 'Site Title',
       //                      'page_number'    => 'Page number' 
       //              )
       //          ),
       //          'vDisable'       => array('left' => True, 'center' => True, 'right' => True),
       //          'vWidth'             => '200px',
       //          'vDefault'           => array(0,0,0),
       //      ),
       //      array(  // Drop-down Lists with Mixed Types
       //          'strFieldID'         => 'docx_footer',
       //          'strSectionID'       => 'docx_header_footer',
       //          'strTitle'           => __( 'Footer', 'admin-page-framework-demo' ),
       //          'strDescription'     => __( 'To be added.', 'admin-page-framework-demo' ),
       //          'strType'            => 'select',
       //          'vLabel'             => array( 
       //              'left'   => array( 
       //                      ''           => 'Left',
       //                      'document_title' => 'Document Title', 
       //                      'site_url'       => 'Site URL',
       //                      'site_title'     => 'Site Title',
       //                      'page_number'    => 'Page number' 
       //              ),
       //              'center' => array( 
       //                      ''               => 'Center',
       //                      'document_title' => 'Document Title', 
       //                      'site_url'       => 'Site URL',
       //                      'site_title'     => 'Site Title',
       //                      'page_number'    => 'Page number' 
       //              ),
       //              'right'  => array( 
       //                      ''               => 'Right',
       //                      'document_title' => 'Document Title', 
       //                      'site_url'       => 'Site URL',
       //                      'site_title'     => 'Site Title',
       //                      'page_number'    => 'Page number' 
       //              )
       //         ),
       //          'vDisable'       => array('left' => True, 'center' => True, 'right' => True),
       //          'vWidth'             => '200px',
       //          'vDefault'           => array(0,0,0),
       //      )
       //  );

      // $this->addSettingSections(
      //       array(
      //           'strSectionID'       => 'docx_watermark',    // the section ID
      //           'strPageSlug'        => 'read_offline_docx',    // the page slug that the section belongs to
      //           'strTitle'           => __('Watermark','read-offline'),    // the section title
      //           //'strDescription'    => __('For per post watermark, please use the shortcode', 'read-offline' )
      //      )
      //   );        
      //   $this->addHelpTab(
      //       array(
      //           'strPageSlug'        => 'read_offline_docx',    // ( mandatory )
      //           // 'strPageTabSlug'  => null,    // ( optional )
      //           'strHelpTabTitle'    =>  __('Watermark','read-offline') ,
      //           'strHelpTabID'       => 'general_options_help_docx_watermark',  // ( mandatory )
      //           'strHelpTabContent'  => __( 'TBA', 'read-offline' ),
      //           //'strHelpTabSidebarContent'  => __( 'This is placed in the sidebar of the help pane.', 'admin-page-framework' ),
      //       )
      //   );

      //   $this->addSettingFields(
      //       array(
      //           'strFieldID'        => 'docx_watermark_text',
      //           'strSectionID'      => 'docx_watermark',
      //           'strTitle'          => __('Text', 'read-offline' ),
      //           'strType'           => 'text',
      //           'vSize'             => 85,
      //       ),
      //       array( // Image Selector
      //           'strFieldID'        => 'docx_watermark_image',
      //           'strSectionID'      => 'docx_watermark',
      //           'strTitle'          => __('Image', 'read-offline' ),
      //           'strType'           => 'image'
      //       ),
      //       array(
      //           'strFieldID'        => 'docx_watermark_tranparency',
      //           'strSectionID'      => 'docx_watermark',
      //           'strTitle'          => __( 'Transparency (alpha value)', 'read-offline' ),
      //           'strType'           => 'number',
      //           'vMax'              => '1.0',
      //           'vMin'              => '0.0',
      //           'vStep'             => '0.1',
      //           //'strDescription' => 'alpha value',
      //           'vDefault'          =>  0
      //       )
      //   );




// FAQ
       // $this->addSubMenuPage(
       //      'FAQ',
       //      'read_offline_faq',
       //      'edit-comments'
       //  );
// Issues        
        $this->addSubMenuLink(
            __( '<span class="warning">Please report issues</span>', 'admin-page-framework-demo' ) ,
            'https://github.com/soderlind/read-offline/issues',
            null,
            null,
            false
        );


//MISC
        $this->addLinkToPluginDescription(
            "Change the settings for: <a href='admin.php?page=read_offline_options'>General Options</a>",
            "<a href='admin.php?page=read_offline_pdf'>PDF</a>",
            "<a href='admin.php?page=read_offline_epub'>ePub</a>",
            "<a href='admin.php?page=read_offline_mobi'>mobi</a>",
            "<a href='admin.php?page=read_offline_docx'>DocX</a>",
            "Please report <a href='https://github.com/soderlind/read-offline/issues'>issues</a> at GitHub"
        );
        // $this->addLinkToPluginTitle(
        //     "Change the settings for: <a href='admin.php?page=read_offline_pdf'>PDF</a>",
        //     "<a href='admin.php?page=read_offline_epub'>ePub</a>",
        //     "<a href='admin.php?page=read_offline_mobi'>mobi</a>",
        //     "<a href='admin.php?page=read_offline_docx'>DocX</a>"
        // );

        // You can add more pages as many as you want!
        // 
        // 
        // 
                // Add form sections.
   
    }
    
    public function do_Read_Offline_Settings() {
        submit_button();
        echo $this->oDebug->getArray( get_option( 'Read_Offline_Settings' ) );       
    }

    // public function validation_read_offline_options( $arrInput, $arrOldOptions ) {
    //     //submit_button();
    //     //echo $this->oDebug->getArray( $arrInput );
    //     var_dump( $arrOldOptions );
    //     return $arrInput;
    // }


    // public function do_read_offline_options() {
    //     var_dump( $this->arrWPReadMe );

    //    //echo $this->oDebug->getArray( get_option( 'Read_Offline_Settings' ) );
    // }

    // public function do_read_offline_pdf() {
    //     submit_button();
    //     echo $this->oDebug->getArray( get_option( 'Read_Offline_Settings' ) );
    // }

    // public function do_read_offline_epub() {
    //     submit_button();
    //     echo $this->oDebug->getArray( get_option( 'Read_Offline_Settings' ) );
    // }

    // public function do_read_offline_mobi() {
    //     submit_button();
    //     echo $this->oDebug->getArray( get_option( 'Read_Offline_Settings' ) );
    // }
    public function do_read_offline_docx() {
        //submit_button();
        //update_option( 'Read_Offline_Settings', '' );
        //echo $this->oDebug->getArray( get_option( 'Read_Offline_Settings' ) );
        //echo "<h3>TBA</h3>";
    }

    
   /* 
    public function do_my_first_forms() {    // do_ + page slug    
                    
        // Show the saved option value.
        // The extended class name is used as the option key. This can be changed by passing a custom string to the constructor.
        echo '<h3>Saved Values</h3>';
        echo $this->oDebug->getArray( get_option( 'Read_Offline_Settings' ) );
        
    }
    */
}
 
// Instantiate the class object.
// if ( is_admin() )
//     new Read_Offline_Settings ('Read_Offline_Settings', dirname(dirname(__FILE__)). '/plugin.php');