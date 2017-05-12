<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.connectingelement.co.uk
 * @since      1.0.0
 *
 * @package    CE-CAPI
 * @subpackage CE-CAPI/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CE-CAPI
 * @subpackage CE-CAPI/admin
 * @author     Christopher Scarre <a@b.c>
 */
class CE_CAPI_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in CE_CAPI_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The CE_CAPI_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ce-capi-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in CE_CAPI_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The CE_CAPI_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ce-capi-admin.js', array( 'jquery' ), $this->version, false );

	}
    
    /**
    * Register the administration menu for this plugin into the WordPress Dashboard menu.
    *
    * @since    1.0.0
    */
    public function add_plugin_admin_menu() 
    {
        /*
         * Add a settings page for this plugin to the Settings menu.
         *
         * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
         *
         *        Administration Menus: http://codex.wordpress.org/Administration_Menus
         *
         */
        add_menu_page('CE CAPI Integration Settings', 'CE CAPI', 'manage_options', $this->plugin_name, [$this, 'display_plugin_setup_page'], 'dashicons-media-document', 41.1);
    }

    /**
    * Add settings action link to the plugins page.
    *
    * @since    1.0.0
    */
    public function add_action_links($links) 
    {
        /*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
       $settings_link = array(
        '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
       );
       return array_merge($settings_link, $links);

    }
    
    /**
     * Output a notice for incomplete configuration
     * 
     * @since 1.0.2
     */
    public function admin_notice()
    {
        printf('<div class="error notice">
                    <p>The <a href="%s">%s plugin configuration</a> has not been completed yet.</p>
                </div>',
                menu_page_url($this->plugin_name, false), $this->plugin_name);
    }

   /**
    * Render the settings page for this plugin.
    *
    * @since    1.0.0
    */
    public function display_plugin_setup_page() 
    {
        include_once('partials/ce-capi-admin-display.php');
    }
    
    /**
     * @since 1.0.0
     */
    public function options_update() {
        register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
     }
    
    /**
     * Validates the given settings
     * 
     * @since 1.0.0
     * @param array $input
     * @return array
     */
    public function validate($input) { 
        return [
            'api_key'       => sanitize_text_field($input['api_key']),
            'api_secret'    => sanitize_text_field($input['api_secret']),
        ];
    }

}
