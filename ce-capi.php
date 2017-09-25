<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.connectingelement.co.uk
 * @since             1.0.0
 * @package           CE-CAPI
 *
 * @wordpress-plugin
 * Plugin Name:       CE CAPI
 * Plugin URI:        https://github.com/ConnectingElement/wp-capi
 * Description:       Connecting Element Content API integration for Wordpress
 * Version:           1.0.7
 * Author:            Connecting Element
 * Author URI:        http://www.connectingelement.co.uk
 * Text Domain:       ce-capi
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/ce-capi-activator.php
 */
function activate_ce_capi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/ce-capi-activator.php';
	CE_CAPI_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/ce-capi-deactivator.php
 */
function deactivate_ce_capi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/ce-capi-deactivator.php';
	CE_CAPI_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ce_capi' );
register_deactivation_hook( __FILE__, 'deactivate_ce_capi' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/ce-capi.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ce_capi() {

	$plugin = new CE_CAPI();
	$plugin->run();

}
run_ce_capi();

/*
	Plugin Name: Smashing Plugin
	Description: This is for updating your Wordpress plugin.
	Version: 1.0.0
	Author: Matthew Ray
	Author URI: http://www.matthewray.com
*/
if (!class_exists('Smashing_Updater')){
	include_once( plugin_dir_path( __FILE__ ) . '/classes/SmashingUpdater.php');
}
$updater = new Smashing_Updater(__FILE__);
$updater->set_username('ConnectingElement');
$updater->set_repository('wp-capi');
//$updater->authorize( 'abcdefghijk1234567890' ); // Your auth code goes here for private repos
$updater->initialize();