<?php
/**
 * Admin - Functions - Scripts.
 *
 * @package Simple_Page_Access_Restriction.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the scripts.
 */
function ps_simple_par_admin_register_scripts() {
	// Set the js url.
	$js_url = SIMPLE_PAGE_ACCESS_RESTRICTION_URL . 'assets/js/';

	// Set the suffix.
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Register the admin scripts.
	wp_register_script( 'ps-simple-par-admin-deactivation', "{$js_url}admin/deactivation{$suffix}.js", array( 'jquery-core' ), SIMPLE_PAGE_ACCESS_RESTRICTION_VER, true );
	wp_register_script( 'ps-simple-par-admin-list-table', "{$js_url}admin/list-table{$suffix}.js", array( 'jquery-core' ), SIMPLE_PAGE_ACCESS_RESTRICTION_VER, true );
	wp_register_script( 'ps-simple-par-admin-review', "{$js_url}admin/review{$suffix}.js", array( 'jquery-core' ), SIMPLE_PAGE_ACCESS_RESTRICTION_VER, true );
	wp_register_script( 'ps-simple-par-admin-settings', "{$js_url}admin/settings{$suffix}.js", array( 'jquery-core' ), SIMPLE_PAGE_ACCESS_RESTRICTION_VER, true );

	// Allow developers to use this.
	do_action( 'ps_simple_par_admin_register_scripts' );
}
add_action( 'admin_enqueue_scripts', 'ps_simple_par_admin_register_scripts' );

/**
 * Enqueue a script.
 *
 * @param string $script_key The script key.
 */
function ps_simple_par_admin_enqueue_script( $script_key ) {
	// Check the script key.
	if ( 'deactivation' === $script_key ) {
		// Set the script handle.
		$script_handle = 'ps-simple-par-admin-deactivation';

		// Check if the script is not enqueued.
		if ( ! wp_script_is( $script_handle, 'enqueued' ) ) {
			// Enqueue the script.
			wp_enqueue_script( $script_handle );
		}

		// Check the script key.
	} elseif ( 'review' === $script_key ) {
		// Set the script handle.
		$script_handle = 'ps-simple-par-admin-review';

		// Check if the script is not enqueued.
		if ( ! wp_script_is( $script_handle, 'enqueued' ) ) {
			// Enqueue the script.
			wp_enqueue_script( $script_handle );
		}

		// Check the script key.
	} elseif ( 'list-table' === $script_key ) {
		// Set the script handle.
		$script_handle = 'ps-simple-par-admin-list-table';

		// Check if the script is not enqueued.
		if ( ! wp_script_is( $script_handle, 'enqueued' ) ) {
			// Enqueue the script.
			wp_enqueue_script( $script_handle );
		}

		// Check the script key.
	} elseif ( 'settings' === $script_key ) {
		// Set the script handle.
		$script_handle = 'ps-simple-par-admin-settings';

		// Check if the script is not enqueued.
		if ( ! wp_script_is( $script_handle, 'enqueued' ) ) {
			// Enqueue the script.
			wp_enqueue_script( $script_handle );
		}
	}

	// Allow developers to use this.
	do_action( 'ps_simple_par_admin_enqueue_script', $script_key );
}

/**
 * Enqueue the scripts.
 */
function ps_simple_par_admin_enqueue_scripts() {
	// Check the current page.
	if ( ps_simple_par_admin_is_page( 'restrictable-list-table' ) ) {
		// Enqueue the admin scripts.
		ps_simple_par_admin_enqueue_script( 'list-table' );

		// Check the current page.
	} elseif ( ps_simple_par_admin_is_page( 'settings' ) ) {
		// Enqueue the admin scripts.
		ps_simple_par_admin_enqueue_script( 'settings' );

		// Check the current page.
	} elseif ( ps_simple_par_admin_is_page( 'wp-plugins' ) ) {
		// Enqueue the admin scripts.
		ps_simple_par_admin_enqueue_script( 'deactivation' );
	}

	// Enqueue the admin scripts.
	ps_simple_par_admin_enqueue_script( 'review' );

	// Allow developers to use this.
	do_action( 'ps_simple_par_admin_enqueue_scripts' );
}
add_action( 'admin_enqueue_scripts', 'ps_simple_par_admin_enqueue_scripts', 20 );
