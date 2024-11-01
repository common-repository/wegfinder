<?php
// This class defines all code necessary to run during the plugin's activation.

class Wegfinder_Activator {

	public static function activate() {
		Wegfinder_Activator::create_database();
	}
	
	const WEGFINDER_DB_VERSION = '1.0';
	
	private static function create_database() {
		
		// Create DB	
		global $wpdb;

		$table_name = $wpdb->prefix . 'wegfinder';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name tinytext NOT NULL,
		locid tinytext NOT NULL, 
		locname tinytext, 
		arrival datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );

		// Store DB Version for future Upgrades
		update_option( 'wegfinder_db_version', self::WEGFINDER_DB_VERSION );
		
		// Add Demo Data
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wegfinder ORDER BY `name`");		
		if (count($results) == 0 ) {
			$wpdb->insert( "{$wpdb->prefix}wegfinder", array( 'name' => 'Stephansplatz', 'locname' => 'Wien Stephansplatz (U)','locid' => '1wH5A'));
		}
		
		// Default Settings	
		if(!get_option( 'wegfinder_settings' )) {
			$wegfinderSettingDefaults = Array ( "wegfinder_style" => "pink", "wegfinder_custom_color" => "#ff1c7e", "wegfinder_custom_text_color" => "white", "wegfinder_size" => "4", "wegfinder_target" => "wegfinder" );
			update_option( 'wegfinder_settings', $wegfinderSettingDefaults);
		}
		
	}
	

}
