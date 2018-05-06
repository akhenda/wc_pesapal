<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/akhenda
 * @since      1.0.0
 *
 * @package    Wc_Pesapal
 * @subpackage Wc_Pesapal/includes
 */

/**
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wc_Pesapal
 * @subpackage Wc_Pesapal/includes
 * @author     Joseph Akhenda <akhenda@gmail.com>
 */
class Wc_Pesapal_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wc-pesapal',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
