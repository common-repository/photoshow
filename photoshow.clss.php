<?php
/**
CodePeoplePhotoshow allows to insert photos from Flickr in the blog posts
*/

class CodePeoplePhotoshow {

	var $text_domain = 'photoshow';
	var $modules = array();
	var $galleries = array();
	var $loaded_galleries = array();

	function __construct( $modules_path, $galleries_path )
	{
		$this->troubleshoot();

		$modules = array();
		$galleries = array();

		// Load the modules associated to CodePeoplePhotoshow
		$md = dir(  $modules_path  );
		while( false !== ( $entry = $md->read() ) )
		{
            if ( strlen( $entry ) > 3 && is_dir( $md->path.'/'.$entry ) )
			{
				if ( file_exists( $md->path.'/'.$entry.'/module.definition.php' ) )
				{
					require_once $md->path.'/'.$entry.'/module.definition.php';

					$module = end( $modules );
					$keys = array_keys( $modules );
					$lastkey = array_pop( $keys );
					if( !empty( $modules[ $lastkey ][ 'class_path' ] ) )
					{
						require_once $md->path.'/'.$entry.'/'.$modules[ $lastkey ][ 'class_path' ];
						$modules[ $lastkey ][ 'object' ] = new $modules[ $lastkey ][ 'class_name' ]( array( 'text_domain' => $this->text_domain ) );
					}
				}

			}
        }

		$this->modules = $modules;

		// Load the galleries associated to CodePeoplePhotoshow
		$gd = dir(  $galleries_path  );
		while( false !== ( $entry = $gd->read() ) )
		{
            if ( strlen( $entry ) > 3 && is_dir( $gd->path.'/'.$entry ) )
			{
				if ( file_exists( $gd->path.'/'.$entry.'/gallery.definition.php' ) )
				{
					require_once $gd->path.'/'.$entry.'/gallery.definition.php';
					$keys = array_keys( $galleries );
					$lastkey = array_pop( $keys );
					$galleries[ $lastkey ][ 'dir' ] = $entry;
					if( !empty( $galleries[ $lastkey ][ 'class_path' ] ) )
					{
						require_once $gd->path.'/'.urlencode( $entry ).'/'.$galleries[ $lastkey ][ 'class_path' ];
						$galleries[ $lastkey ][ 'object' ] = new $galleries[ $lastkey ][ 'class_name' ]( array( 'text_domain' => $this->text_domain ) );
					}
				}

			}
        }

		$this->galleries = $galleries;
		add_action( 'widgets_init', 'cpps_load_widgets' );
	}

	function init()
	{
		// I18n
		load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if( !empty( $_REQUEST[ 'photoshow_action' ] ) && !empty( $_REQUEST[ 'terms' ] ) )
		{
			$terms = sanitize_text_field($_REQUEST[ 'terms' ]);
			switch( $_REQUEST[ 'photoshow_action' ] )
			{
				case 'get':
					$returns = array();
					foreach( $this->modules as $key => $module )
					{
						if( $module[ 'object' ]->isActive() )
						{
							$returns[ $key ] = $module[ 'object' ]->get( $terms, 0, PHOTOSHOW_GET_AMOUNT );
						}
					}
					print json_encode( $returns );
					exit;
				break;
				case 'more':
					$returns = array();
					if( !empty( $_REQUEST[ 'module' ] ) && isset( $_REQUEST[ 'from' ] ) && isset( $this->modules[ $_REQUEST[ 'module' ] ] ) )
					{
						$_module = sanitize_text_field($_REQUEST[ 'module' ]);
						$_from = sanitize_text_field($_REQUEST[ 'from' ]);
						$returns[ $_module ] = $this->modules[ $_module ][ 'object' ]->get($terms, $_from, PHOTOSHOW_GET_AMOUNT);
					}
					print json_encode( $returns );
					exit;
				break;
			}

		}

		$this->preview();
	}

	/*
		Remove tables
	*/
	function deactivePlugin() {
		// Remove configuration options
		delete_option('photoshow_flickr_api_key');
	} // End deactivePlugin

	/*
		Set a link to plugin settings
	*/
	function settingsLink($links) {
		$settings_link = '<a href="options-general.php?page=photoshow.php">'.__('Settings').'</a>';
		array_unshift($links, $settings_link);
		return $links;
	} // End settingsLink

	/*
		Set a link to contact page
	*/
	function customizationLink($links) {
		$settings_link = '<a href="https://wordpress.dwbooster.com/contact-us" target="_blank">'.__('Request custom changes').'</a>';
		array_unshift($links, $settings_link);
		return $links;
	} // End customizationLink

	/**
		Print out the admin page
	*/
	function printAdminPage(){
		//if(isset($_POST['photoshow_settings']))

		?>
		<div class="wrap">
			<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
				For reporting an issue or to request a customization, <a href="https://wordpress.dwbooster.com/contact-us" target="_blank">CLICK HERE</a><br />
				If you want test the premium version of Smart Image Gallery go to the following links:<br/> <a href="https://demos.dwbooster.com/smart-image-gallery/wp-login.php" target="_blank">Administration area: Click to access the administration area demo</a><br/>
				<a href="https://demos.dwbooster.com/smart-image-gallery/" target="_blank">Public page: Click to access the public website</a>
			</p>

			<form method="post">
				<h2><?php print PHOTOSHOW_PLUGIN_NAME; ?></h2>
				<?php

					$_update_settings = ( isset($_POST['cp-photoshow-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cp-photoshow-nonce'] ) ), 'cp-photoshow-settings-page-form' ) ) ? true : false;

					foreach( $this->modules as $module )
					{
						$module[ 'object' ]->settings( $_update_settings );
					}
				?>
				<input type="hidden" name="photoshow_settings" value="true" />
				<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
					To obtain a copy of premium version of plugin <a href="http://wordpress.dwbooster.com/galleries/smart-image-gallery#download" target="_blank">CLICK HERE</a>
				</p>
				<div class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Update Settings', $this->text_domain ); ?>" /></div>
				<?php
					wp_nonce_field( 'cp-photoshow-settings-page-form', 'cp-photoshow-nonce' );
				?>
			</form>
		</div>
		<?php
	}

	/**
		Set the Photoshow button in media bar over the post editor
	*/
	function setPhotoshowButton(){
		print '<a href="javascript:photoshowAdmin.open();" title="'.__('Select Images', $this->text_domain).'"><img src="'.PHOTOSHOW_URL.'/images/photoshow.gif" alt="'.__('Select Images', $this->text_domain).'" /></a>';
	} // End setPhotoshowButton

	/**
		Integrates the plugin with the Gutenberg Editor
	*/
	function gutenbergEditor()
	{
		wp_enqueue_style('photoshow-gutenberg-editor-css', PHOTOSHOW_URL.'/css/gutenberg.css');
		wp_enqueue_script('photoshow-gutenberg-editor', PHOTOSHOW_URL.'/js/gutenberg.js', array('wp-blocks', 'wp-element'), null, true);

		$url = PHOTOSHOW_H_URL;
		$url .= ((strpos('?', $url) === false) ? '?' : '&').'photoshow-preview=';

		wp_localize_script('photoshow-gutenberg-editor', 'photoshow_settings', array('url' => $url));
	} // End gutenbergEditor

	/**
		Load the scripts used for Photoshow insertion
	*/
	function adminScripts(){
		wp_enqueue_style('wp-jquery-ui-dialog');
		wp_enqueue_style(
			'admin_photoshow_style',
			PHOTOSHOW_URL.'/css/photoshow.admin.css'
		);

		wp_enqueue_script(
			'admin_photoshow_script',
			PHOTOSHOW_URL.'/js/photoshow.admin.js',
			array('jquery', 'jquery-ui-dialog')
		);

		$modules = array();
		foreach( $this->modules as $key => $module )
		{
			if( $module[ 'object' ]->isActive() )
			{
				$modules[ $key ] = $module[ 'title' ];
			}
		}

		$galleries = array();
		foreach( $this->galleries as $key => $gallery )
		{
			$galleries[ $key ] = array(
								'title'	   =>	$gallery[ 'title' ],
								'settings' =>	str_replace(
													"\n", "",
													$gallery[ 'object' ]->settings()
												)
								);

			// Load the javascript files related with galleries
			if( !empty( $gallery[ 'javascript_admin' ] ) )
			{
				for( $i = 0; $i < count( $gallery[ 'javascript_admin' ] ); $i++ )
				{
					wp_enqueue_script(
						'admin_photoshow_script'.$key.$i,
						PHOTOSHOW_URL.'/galleries/'.urlencode( $gallery[ 'dir' ] ).'/'.$gallery[ 'javascript_admin' ][ $i ],
						array( 'admin_photoshow_script' )
					);
				}
			}
		}

		wp_localize_script('admin_photoshow_script', 'photoshow', array(
			'title'  	=> __('Select Images', $this->text_domain),
			'modules'   => $modules,
			'galleries' => $galleries,
			'site_url'  => PHOTOSHOW_H_URL,
			'shortcode' => PHOTOSHOW_SHORTCODE,
			'texts'		=> array(
							'terms'  	=> __('Terms', $this->text_domain),
							'images'  	=> __('Images', $this->text_domain),
							'galleries' => __('Galleries Design', $this->text_domain),
							'gallery-layout' => __('Select a gallery layout', $this->text_domain),
							'images_required_error' => __( 'At least an image should be selected', $this->text_domain ),
							'insert_bttn' => __('Insert', $this->text_domain)
						)
		));
	} // End adminScripts

	/**
		Check if photoshow was inserted in the content and load the corresponding scripts and style files
	*/
	function loadPhotoshowResources(){
		return;
	} // End loadPhotoshowResources

	/**
		Replace the photoshow shortcode by the corresponding html code
	*/
	function replaceShortcode( $attrs, $content ){

		$output = '';

		$obj =  json_decode(str_replace( array( '&#8220;', '&#8221;', '&#8243;' ), array( '"', '"', '"' ),  $content ) );

		if( !is_null( $obj ) )
		{
			if( isset( $obj->settings ) && !empty( $obj->settings->gallery ) && isset( $this->galleries[ $obj->settings->gallery ] ) )
			{
				$gallery = $this->galleries[ $obj->settings->gallery ];
				$output = $gallery[ 'object' ]->gallery( $obj );
				if( !in_array( $obj->settings->gallery, $this->loaded_galleries ) )
				{
					// Add the gallery to the list to load its resources
					array_push( $this->loaded_galleries, $obj->settings->gallery );
					$jsFiles  = $gallery[ 'javascript_public' ];
					$cssFiles = $gallery[ 'styles_public' ];

					foreach( $cssFiles as $cssFile )
					{
						$output .= '<link rel="stylesheet" type="text/css" media="all" href="'.PHOTOSHOW_URL.'/galleries/'.urlencode( $gallery[ 'dir' ] ).'/'.$cssFile.'" />';
					}

					foreach( $jsFiles as $jsFile )
					{
						wp_enqueue_script('photoshow-'.$jsFile, PHOTOSHOW_URL.'/galleries/'.urlencode( $gallery[ 'dir' ] ).'/'.$jsFile, array('jquery'), null, true);
					}
				}
			}
		}

		return $output;
	} // End replaceShortcode

	function  preview()
	{
		$user = wp_get_current_user();
		$allowed_roles = array('editor', 'administrator', 'author');

		if(array_intersect($allowed_roles, $user->roles ))
		{
			if(!empty($_REQUEST['photoshow-preview']))
			{
				// Sanitizing variable
				$preview = stripcslashes($_REQUEST['photoshow-preview']);
				$preview = strip_tags($preview);

				// Remove every shortcode that is not in the music store list
				remove_all_shortcodes();

				add_shortcode(PHOTOSHOW_SHORTCODE, array(&$this, 'replaceShortcode'));

				if(
					has_shortcode($preview, PHOTOSHOW_SHORTCODE)
				)
				{
					print '<!DOCTYPE html>';
					// Deregister all scripts and styles for loading only the plugin styles.
					global  $wp_styles, $wp_scripts;
					if(!empty($wp_scripts)) $wp_scripts->reset();
					wp_enqueue_script('jquery');
					$output = do_shortcode($preview);
					if(!empty($wp_styles))  $wp_styles->do_items();
					if(!empty($wp_scripts)) $wp_scripts->do_items();

					print '<div class="smartig-preview-container">'.$output.'</div>';

					print'<script type="text/javascript">jQuery(window).on("load", function(){ var frameEl = window.frameElement; if(frameEl) frameEl.height = jQuery(".smartig-preview-container").outerHeight(true); });</script>';

					exit;
				}
			}
		}
	} // End preview

	/**	TROUBLESHOOT METHODS **/
	function troubleshoot()
	{
		if(!is_admin())
		{
			add_filter('option_sbp_settings', array($this, 'troubleshoot_sbp'));
		}
	}

	function troubleshoot_sbp($options)
	{
		// Solves a conflict caused by the "Speed Booster Pack" plugin
		if(is_array($options) && isset($options['jquery_to_footer'])) unset($options['jquery_to_footer']);
		return $options;
	}

} // End Photoshow

// ************************************** CREATE WIDGETS *********************************************/

function cpps_load_widgets(){
    register_widget( 'PhotoShowWidget' );
}

/**
 * PhotoShowWWidget Class
 */
class PhotoShowWidget extends WP_Widget {

    /** constructor */
    function __construct() {
        parent::__construct(false, $name = 'Smart Image Gallery');
    }

    function widget($args, $instance) {
        global $photoshow_obj;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $gallery = $instance[ 'gallery' ];

        if( !empty( $gallery ) )
        {
            $str = ( $gallery[ 0 ] == "{" && $gallery[ strlen( $gallery ) - 1 ] == "}" ) ? $photoshow_obj->replaceShortcode( array(), $gallery ) : $gallery;
            echo $before_widget;
            if ( $title ) echo $before_title . $title . $after_title;
            echo $str;
            echo $after_widget;
        }
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['gallery'] = $new_instance['gallery'];
		return $instance;
    }

    function form( $instance ) {

        /* Set up some default widget settings. */
		$defaults = array( 'title' => '', 'gallery' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults );
        $title      = $instance[ 'title' ];
        $gallery    = esc_textarea( $instance[ 'gallery' ] );

        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('gallery'); ?>"><?php _e('Gallery:'); ?> <textarea style="height:250px;" class="widefat cpsig_shortcode" id="<?php echo $this->get_field_id('gallery'); ?>" name="<?php echo $this->get_field_name('gallery'); ?>"><?php echo $gallery; ?></textarea></label></p>
            <p style="border:1px solid #F0AD4E;background:#FBE6CA;padding:10px;">Be sure to use dimensions for the gallery, that satisfy your website's sidebars</p>
            <p><input type="button" value="Open Dialog" onclick="photoshowAdmin.open();" /></p>
        <?php
    }

} // class PhotoShowWidget
?>