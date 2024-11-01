<?php

/**
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */


class Wegfinder_i18n {

	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wegfinder',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
