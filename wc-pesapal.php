<?php

/**
 *
 * @link              https://github.com/akhenda
 * @since             1.0.0
 * @package           Wc_Pesapal
 *
 * @wordpress-plugin
 * Plugin Name:       Pesapal - HendaCorp WooCommerce Payment Gateway
 * Plugin URI:        https://github.com/akhenda/wc-pesapal
 * Description:       Adds Pesapal checkout method to Woocommerce payment gateways.
 * Version:           1.0.0
 * Author:            Joseph Akhenda
 * Author URI:        https://github.com/akhenda
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-pesapal
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-pesapal-activator.php
 */
function activate_wc_pesapal() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-pesapal-activator.php';
	Wc_Pesapal_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-pesapal-deactivator.php
 */
function deactivate_wc_pesapal() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-pesapal-deactivator.php';
	Wc_Pesapal_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wc_pesapal' );
register_deactivation_hook( __FILE__, 'deactivate_wc_pesapal' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-pesapal.php';

/**
 * The Pesapal Gateway class,
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-pesapal-gateway.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_pesapal() {

	$plugin = new Wc_Pesapal();
	$plugin->run();

}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
  run_wc_pesapal();
}
