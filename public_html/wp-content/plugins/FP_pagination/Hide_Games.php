<?php
/**
 * Plugin Name: Football Pool Pagination
 * Description: Adds pagination to the matches screens and ranking page (for plugin version 2.7.0 and up).
 * Version: 1.2
 * Author: Antoine Hurkmans
 * Author URI: mailto:wordpressfootballpool@gmail.com
 * License: MIT
 */

// Save this plugin in the "/wp-content/plugins" folder and activate it //

class FootballPoolPagination {	
	private static $page_size = 12; // change this if you want to alter the number of items per page
	
	public static function init_extension() {
		if ( ! is_admin() ) {
			// add a simple pagination to the ranking page
			add_filter( 'footballpool_print_ranking_ranking', array( __CLASS__, 'fp_pagination' ), 90 );
			add_filter( 'footballpool_ranking_page_html', array( __CLASS__, 'fp_pagination_html' ), null, 2 );
			// and, with the same functions, add a simple pagination to the matches page and prediction page
			add_filter( 'footballpool_filtered_matches', array( __CLASS__, 'fp_pagination' ), 90 );
			add_filter( 'footballpool_matches_page_html', array( __CLASS__, 'fp_pagination_html' ), null, 2 );
			add_filter( 'footballpool_page_pool_matches_filter', array( __CLASS__, 'fp_pagination' ), 90 );
			// add_filter( 'footballpool_predictionform_matches_filter', array( __CLASS__, 'fp_pagination' ), 90 );
			add_filter( 'footballpool_pool_page_html', array( __CLASS__, 'fp_pagination_html' ), null, 2 );
		}
	}
	
	public static function fp_pagination( $items ) {
		$pagination = new Football_Pool_Pagination( count( $items ) );
		$pagination->set_page_param( 'fp_page' );
		$pagination->set_page_size( self::$page_size );
		$offset = ( ( $pagination->current_page - 1 ) * $pagination->get_page_size() );
		$length = $pagination->get_page_size();
		return array_slice( $items, $offset, $length );
	}
	
	public static function fp_pagination_html( $html, $items ) {
		$pagination = new Football_Pool_Pagination( count( $items ), true );
		$pagination->set_page_param( 'fp_page' );
		$pagination->set_page_size( self::$page_size );
		return $html . $pagination->show( 'return' );
	}
}

add_filter( 'plugins_loaded', array( 'FootballPoolPagination', 'init_extension' ) );