<?php
/**
 * Plugin Name: Football Pool Only Open Matches
 * Description: Extension to only show open matches on the prediction page.
 * Version: 1.1
 * Author: Antoine Hurkmans
 * Author URI: mailto:wordpressfootballpool@gmail.com
 * License: MIT
 */

// Save this plugin in the wp-content/plugins folder and activate it //

// set this to true if you want the plugin to also filter the user predictions page
if ( ! defined( 'ONLY_OPEN_MATCHES_ON_ALL_PAGES' ) ) define( 'ONLY_OPEN_MATCHES_ON_ALL_PAGES', false );

class FootballPoolOnlyOpenMatches {
	public static function init_extension() {
		add_filter( 'footballpool_predictionform_matches_filter', array( __CLASS__, 'filter_matches' ), null, 3 );
	}
	
	public static function filter_matches( $matches, $user_id, $is_user_page ) {
		if ( ONLY_OPEN_MATCHES_ON_ALL_PAGES || ! $is_user_page ) {
			$filtered_matches = array();
			foreach ( $matches as $match ) {
				if ( $match['match_is_editable'] ) $filtered_matches[] = $match;
			}
			$matches = $filtered_matches;
		}
		return $matches;
	}
}

add_filter( 'plugins_loaded', array( 'FootballPoolOnlyOpenMatches', 'init_extension' ) );