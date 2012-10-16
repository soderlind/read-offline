<?php
/*
See the Read Offline plugin: http://soderlind.no/archives/2012/10/01/read-offline/
Author: Per Soderlind
Author URI: http://soderlind.no
*/
define('WP_USE_THEMES', false);
require('../../../wp-load.php');

	if ($_GET['id'] && $_GET['read-offline']) {
		$id = $_GET['id'];
		$post = get_post($id);
		if ($post->post_status == 'publish') {
			$docformat = strtolower($_GET['read-offline']);			
			$author = get_the_author_meta('display_name',$post->post_author);
	
			$content = $post->post_content;
			$html = apply_filters('the_content', $content);
			
			switch ($docformat) {
				case 'epub':
					require_once "library/epub/Epub.inc.php";
					
					$epub = new EPub();
					$epub->setTitle($post->post_title); //setting specific options to the EPub library
					$epub->setIdentifier($post->guid, EPub::IDENTIFIER_URI); 										
					$epub->setAuthor($author, "Lastname, First names");
					$epub->setPublisher(get_bloginfo( 'name' ), get_bloginfo( 'url' ));
					$epub->setSourceURL($post->guid);
					$epub->addChapter("Body", "Body.html", $html);
					$epub->finalize();
					$zipData = $epub->sendBook($post->post_name);
				break;
				case 'mobi':
					require_once "library/mobi/Mobi.inc.php";

					$mobi = new MOBI();
					$options = array(
						"title"=> $post->post_title,
						"author"=> $author,
						"subject"=> (count(wp_get_post_categories($id))) ? implode(' ,',array_map("get_cat_name", wp_get_post_categories($id))) : "Unknown subject"
					);
					$mobi->setOptions($options);				
					$mobi->setData($html);
					$zipData = $mobi->download($post->post_name . ".mobi");					
				break;
				case 'pdf':
					require_once "library/mpdf/mpdf.inc.php";

					$pdf = new mPDF();
					$pdf->SetTitle($post->post_title);
					$pdf->SetAuthor($author);
					$pdf->WriteHTML($html);
					$pdf->Output($post->post_name . ".pdf", 'D');
				break;
			}
			exit();	
			
		}
	}
?>