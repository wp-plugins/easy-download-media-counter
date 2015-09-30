<?php
/*
Plugin Name: Easy Download Media Counter
Plugin URL: http://remicorson.com/easy-download-media-counter-free/
Description: This plugin allows you to easily count downloads for a wordpress media and display it
Version: 1.2
Author: Remi Corson
Author URI: http://remicorson.com
Conytributors: corsonr
*/

/* ----------------------------------------
* Plugin Globals
----------------------------------------- */

global $edmc_base_dir;
$edmc_base_dir = dirname(__FILE__);

global $edmc_prefix;
$edmc_prefix = 'edmc_';

/* ----------------------------------------
* plugin text domain for translations
----------------------------------------- */

// uncomment to use translation, and add language file to languages directory
//load_plugin_textdomain( 'edmc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );


/* ------------------------------------------------------------------*/
/* Add Counter to medias */
/* ------------------------------------------------------------------*/

function edmc_create_field($form_fields, $post) {

	$form_fields['edmc-download-count'] = array(
		'label' => __('Downloads #', 'edmc'),
		'input' => 'text', 
		'value' => get_post_meta($post->ID, '_edmc-download-count', true),
		'helps' => __('Downloads count.'),
	);
   return $form_fields;
}

add_filter("attachment_fields_to_edit", "edmc_create_field", null, 2);


/* ------------------------------------------------------------------*/
/* Save Download Counter */
/* ------------------------------------------------------------------*/

function edmc_save_field($post, $attachment) {

	if( isset($attachment['edmc-download-count']) ){
		update_post_meta($post['ID'], '_edmc-download-count', $attachment['edmc-download-count']);
	}
	return $post;
}

add_filter('attachment_fields_to_save', 'edmc_save_field', null , 2);

/* ----------------------------------------
* Shortcodes
----------------------------------------- */

// [edmc id="xxx"]Download Media Now![/edmc] 

function edmc_shortcode($atts, $content = null) {

	extract(shortcode_atts(array(
		"id" => ''
	), $atts));
	
	return '<a href="'.get_bloginfo('url').'?edmc='.$id.'">'.$content.'</a>';
	
}

add_shortcode("edmc", "edmc_shortcode"); 

// [edmc_show id="xxx"]

function edmc_shortcode_show($atts) {

	extract(shortcode_atts(array(
		"id" 		=> ''
	), $atts));
	
	$mime_type = get_post_mime_type($id);
	
	return __('Downloads', 'edmc').': '.get_post_meta($id, '_edmc-download-count', true).' ('.$mime_type.')';
	
}

add_shortcode("edmc_show", "edmc_shortcode_show"); 

/* ----------------------------------------
* Populate Counter
----------------------------------------- */

if( !is_admin() ) {
	
	if( isset($_GET['edmc']) AND is_numeric($_GET['edmc']) ) {
		
		// Update count
		$count = get_post_meta($_GET['edmc'], '_edmc-download-count', true);
		update_post_meta($_GET['edmc'], '_edmc-download-count', $count+1);
		
		// Fix here: we need only file path not url
		$file = get_attached_file( $_GET['edmc'] );

		// Get just the file name
		$file_name = basename($file);
		
		if(isset($file)){
		    // Checking MIME type and setting accordingly
		    switch(strtolower(substr(strrchr($file_name,'.'),1)))
			  {
			    case 'pdf': $mime = 'application/pdf'; break;
			    case 'zip': $mime = 'application/zip'; break;
			    case 'jpeg': $mime = 'image/jpg'; break;
			    case 'jpg': $mime = 'image/jpg'; break;
			    case 'png': $mime = 'image/jpg'; break;
			    default: $mime = 'application/force-download';
			  }
			  // Send headers
			  header('Pragma: public');   // required
			  header('Expires: 0');    // no cache
			  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			  header('Cache-Control: private',false);
			  header('Content-Type: '.$mime);
			  header('Content-Disposition: attachment; filename="'.$file_name.'"');
			  header('Content-Transfer-Encoding: binary');
			  header('Content-Length: '.filesize($file));  // provide file size
			  header('Connection: close');
			  readfile( $file );    // push it out
			  exit();
		}


		
	}
}

/* ------------------------------------------------------------------*/
/* Media Library Add Count Column */
/* ------------------------------------------------------------------*/

function edmc_add_count_column($posts_columns) {
	  
	// Add a new column
	$posts_columns['downloads'] = _x('Downloads', 'downloads_column');
 
	return $posts_columns;
}
add_filter('manage_media_columns', 'edmc_add_count_column');

/* ------------------------------------------------------------------*/
/* Media Library Populate Count Column */
/* ------------------------------------------------------------------*/

function edmc_populate_count_column($column_name, $id) {

	$downloads = get_post_meta($id, '_edmc-download-count', true);
	
	switch($column_name) {
		case 'downloads':
			if ( $downloads > 0 ) {
				echo $downloads;
			} else {
				_e('Not downloaded yet');
			}
			break;
		default:
			break;
	}
 
}
add_action('manage_media_custom_column', 'edmc_populate_count_column', 10, 2);


/* ------------------------------------------------------------------*/
/* Uninstall plugin */
/* ------------------------------------------------------------------*/

function edmc_uninstall () 
{
    // if you want to delete media count on deinstallation... do it here
}

register_deactivation_hook( __FILE__, 'edmc_uninstall' );
