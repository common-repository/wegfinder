<?php

// The file that defines the core plugin class

class Wegfinder {

	protected $loader;

	protected $plugin_name;
	
	protected $version;

	public function __construct() {
		if ( defined( 'WEGFINDER_VERSION' ) ) {
			$this->version = WEGFINDER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wegfinder';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		
		$this->define_shortcodes();		
	}

	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wegfinder-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wegfinder-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wegfinder-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wegfinder-public.php';

		
		$this->loader = new Wegfinder_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wegfinder_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wegfinder_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wegfinder_Admin( $this->get_plugin_name(), $this->get_version() );

		// Enqueue Scripts and Styles
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		
		// Option Page
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'options_page' );
		
		// Link to Options on Plugins List
		$this->loader->add_action( 'plugin_action_links_'.$this->get_plugin_name().'/'.$this->get_plugin_name().'.php', $plugin_admin, 'plugin_action_links' );			
		
		// Editor RichRext Toolbar
		$this->loader->add_action( 'init', $plugin_admin, 'wegfinder_editor_richtext_toolbar_script_register' );	
		$this->loader->add_action( 'enqueue_block_editor_assets', $plugin_admin, 'wegfinder_editor_richtext_toolbar_enqueue_assets_editor' );
		
		// Settings		
		$this->loader->add_action( 'admin_init', $plugin_admin, 'wegfinder_settings_init' );

		// Quicktags for old Editor		
		$this->loader->add_action( 'admin_print_footer_scripts', $plugin_admin, 'quicktags' );
				
		// MCE buttons for old Editor		
		$this->loader->add_filter( 'mce_external_plugins', $plugin_admin, 'enqueue_mce_plugin_scripts' );
		$this->loader->add_filter( 'mce_buttons', $plugin_admin, 'register_mce_buttons_editor' );		
		$this->loader->add_action( 'wp_ajax_wegfinder_menudata_ajax', $plugin_admin, 'wegfinder_menudata_ajax' );
		
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wegfinder_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wegfinder_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
	
	
	// Shortcodes
	private function define_shortcodes() {	
		add_shortcode('wegfinder', array( $this, 'shortcode' ));		
	}
	
	
	// Shortcode for button	
	public static function shortcode($atts) {
  
	  extract(shortcode_atts(array(
		"id" => ''
		), $atts));

		return $this->get_button_HTML($id);

	}
 
	// Render Button
	public static function get_button_HTML($id) {
		
		$options = get_option( 'wegfinder_settings' );
		$sizeclasses=array('xxs', 'xs', 's', 'm', 'l', 'xl', 'xxl');
		
		$classlist = "wegfinder wegfinder-".$sizeclasses[$options['wegfinder_size']-1];
		$cssstyle="";
		
		if ($options['wegfinder_style'] == "pink" || $options['wegfinder_style'] == "blue" || $options['wegfinder_style'] == "red") {
			$classlist .= " wegfinder-".$options['wegfinder_style'];
		} else {
			$cssstyle=' style="background-color: '.$options['wegfinder_custom_color'].';"';
			if ($options['wegfinder_custom_text_color'] == "white") {
				$classlist .= " wegfinder-dark";
			} else {
				$classlist .= " wegfinder-light";
			}		
		}		
		return '<a class="'.$classlist.'"'.$cssstyle.' title="wie wohin - Vergleiche, kombiniere und buche neue Wege" href="'.Wegfinder::get_button_URL($id).'" target="'.$options['wegfinder_target'].'"><span></span></a>';
	}
	
	// Get button url
	public static function get_button_URL($id) {
		
		$url = "https://wegfinder.at/route";
		$locname= "";
		try {
		
			if (isset($id) && !empty($id) ) {
				
				global $wpdb;					
				$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wegfinder WHERE `id`=". $id);
				
				if (!empty($results)) {
					
					$url .= "/from//to/".$results[0]->locid.'/at/';
					$locname = $results[0]->locname;
					
					if ($results[0]->arrival !== "0000-00-00 00:00:00") 
					{
						date_default_timezone_set('Europe/Vienna');
						$ts = date('c',strtotime($results[0]->arrival));
						
						if ( date('c',time()) <= $ts) {									
							$url .= "arrival/".$ts;
						} else {
							$url .= "departure/now";
						}
					} else {
						$url .= "departure/now";
					}
				}
			}
		} catch (Exception $e) {}
				
		$url .= "?pk_campaign=plugins";
		$url .= "&pk_keyword";
		$url .= "&pk_source=".urlencode($_SERVER['HTTP_HOST']);
		$url .= "&pk_medium=wordpress";
		$url .= "&pk_content=".urlencode($locname);
				
		return $url;		
	}
	
	
	
	
	
	
}
