<?php
/*
Plugin Name: Smart Image Gallery
Plugin URI: https://wordpress.dwbooster.com/galleries/smart-image-gallery
Version: 1.0.19
Author: CodePeople
Author URI: https://wordpress.dwbooster.com/galleries/smart-image-gallery
Description: Smart Image Gallery allows to insert images, and pictures, in your blog, directly from the WordPress media library, or eternal images repositories (like: Flickr, Picasa, Instagram, Facebook or Google Images ). The images are searched, and inserted, from the article edition, without importing them to the WordPress media library.
Text Domain: photoshow
*/

require_once 'banner.php';
$codepeople_promote_banner_plugins[ 'photoshow' ] = array(
	'plugin_name' => 'Smart Image Gallery',
	'plugin_url'  => 'https://wordpress.org/support/plugin/photoshow/reviews/#new-post'
);

include "photoshow.clss.php";

if(!function_exists('photoshow_get_site_url')){
    function photoshow_get_site_url(){
        $url_parts = parse_url(get_site_url());
        return rtrim(
                        ((!empty($url_parts["scheme"])) ? $url_parts["scheme"] : "http")."://".
                        $_SERVER["HTTP_HOST"].
                        ((!empty($url_parts["path"])) ? $url_parts["path"] : ""),
                        "/"
                    )."/";
    }
}

define( 'PHOTOSHOW_PLUGIN_NAME', 'Smart Image Gallery' );
define( 'PHOTOSHOW_URL', plugins_url( '', __FILE__ ) );
define( 'PHOTOSHOW_PATH', dirname( __FILE__ ) );
define( 'PHOTOSHOW_H_URL', photoshow_get_site_url() );
define( 'PHOTOSHOW_GET_AMOUNT', 20 );
define( 'PHOTOSHOW_SHORTCODE', 'smart-image-gallery' );


if( class_exists( "CodePeoplePhotoshow" ) )
{
	$photoshow_obj = new CodePeoplePhotoshow( PHOTOSHOW_PATH.'/modules', PHOTOSHOW_PATH.'/galleries' );

	//Initialize the admin panel
	if( !function_exists( "CodePeoplePhotoshow_ap" ) )
	{
		function CodePeoplePhotoshow_ap()
		{
			global $photoshow_obj;
			if (!isset($photoshow_obj))
			{
				return;
			}

			if (function_exists('add_options_page'))
			{
				add_options_page( 'Smart Image Gallery', 'Smart Image Gallery', 'manage_options', basename( __FILE__ ), array( &$photoshow_obj, 'printAdminPage' ) );
			}
		}
	}

	if( isset( $photoshow_obj ) )
	{

		// Plugin deactivation
		register_deactivation_hook( __FILE__, array( &$photoshow_obj, 'deactivePlugin' ) );

		// Set Actions
		add_action( 'init', array( &$photoshow_obj, 'init' ), 1 );
		add_action( 'admin_enqueue_scripts', array( &$photoshow_obj, 'adminScripts' ), 1 );
		add_action( 'media_buttons', array( &$photoshow_obj, 'setPhotoshowButton' ), 100 );
		add_action( 'enqueue_block_editor_assets', array( &$photoshow_obj, 'gutenbergEditor' ) );
		add_action( 'wp_enqueue_scripts', array( &$photoshow_obj, 'loadPhotoshowResources' ), 100 );
		add_action( 'admin_menu', 'CodePeoplePhotoshow_ap');

		// Set Filters
		$plugin = plugin_basename( __FILE__ );
		add_filter( 'plugin_action_links_'.$plugin, array( &$photoshow_obj, 'customizationLink' ) );
		add_filter( 'plugin_action_links_'.$plugin, array( &$photoshow_obj, 'settingsLink' ) );

		// Define shortcode
		add_shortcode( PHOTOSHOW_SHORTCODE, array(&$photoshow_obj, 'replaceShortcode'));
	}
}
?>