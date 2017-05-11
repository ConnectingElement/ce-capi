<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.connectingelement.co.uk
 * @since      1.0.0
 *
 * @package    CE_CAPI
 * @subpackage CE_CAPI/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CE_CAPI
 * @subpackage CE_CAPI/public
 * @author     Christopher Scarre <a@b.c>
 */
class CE_CAPI_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->ce_capi_options = get_option($this->plugin_name);
	}

    /**
     * This function is called from the shortcode API
     * 
     * @param array $attributes Any attributes set in the shortcode use
     */
	public static function endpoint($attributes = [])
    {
        
    }

}
