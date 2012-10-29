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
	
			$html = '<h1 class="entry-title">' . get_the_title($post->ID) . '</h1>';
			$content = $post->post_content;
			$content = preg_replace("/\[\\/?readoffline(\\s+.*?\]|\])/i", "", $content); // remove all [readonline] shortcodes
			$html .= apply_filters('the_content', $content);

			switch ($docformat) {
				case 'epub':
					require_once "library/epub/EPub.inc.php";
					
					$epub = new EPub();
					$epub->setTitle($post->post_title); //setting specific options to the EPub library
					$epub->setIdentifier($post->guid, EPub::IDENTIFIER_URI); 
					$iso6391 = ( '' == get_locale() ) ? 'en' : strtolower( substr(get_locale(), 0, 2) ); // only ISO 639-1	
					$epub->setLanguage($iso6391);									
					$epub->setAuthor($author, "Lastname, First names");
					$epub->setPublisher(get_bloginfo( 'name' ), get_bloginfo( 'url' ));
					$epub->setSourceURL($post->guid);
					$cssData = "";
					$epub->addCSSFile("styles.css", "css1", $cssData);
					
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
					
					$content_end = "\n</body>\n</html>\n";
					
					$epub->addChapter("Body", "Body.html", $content_start . $html . $content_end);
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
	
	
function ps_read_style($url) { // from WP-Minify plugin
    $ch = curl_init();
    $timeout = 0; // set to zero for no timeout
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt ($ch, CURLOPT_USERAGENT, 'Read Offline');
    $content = curl_exec($ch);
    //curl_close($ch);
    if ($content) {
      if (is_array($content)) {
        $content = implode($content);
      }
      printf("<pre>%s</pre>",print_r($content,true));	
    } else {
    
     printf(
        '%s: '.$url.'. %s<br/>',
        __('Error: Could not fetch and cache URL'),
        __('You might need to exclude this file in WP Minify options.')
      );
      
      echo curl_error($ch);
    }
}

?>