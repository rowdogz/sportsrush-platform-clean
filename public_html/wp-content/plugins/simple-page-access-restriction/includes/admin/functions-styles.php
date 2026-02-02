<?php
/**
 * Admin - Functions - Styles.
 *
 * @package Simple_Page_Access_Restriction.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the styles.
 */
function ps_simple_par_admin_register_styles() {
	// Set the css url.
	$css_url = SIMPLE_PAGE_ACCESS_RESTRICTION_URL . 'assets/css/';

	// Set the suffix.
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Register the admin styles.
	wp_register_style( 'ps-simple-par-admin-deactivation', "{$css_url}admin/deactivation{$suffix}.css", array(), SIMPLE_PAGE_ACCESS_RESTRICTION_VER );
	wp_register_style( 'ps-simple-par-admin-settings', "{$css_url}admin/settings{$suffix}.css", array(), SIMPLE_PAGE_ACCESS_RESTRICTION_VER );

	// Allow developers to use this.
	do_action( 'ps_simple_par_admin_register_styles' );
}
add_action( 'admin_enqueue_scripts', 'ps_simple_par_admin_register_styles' );

/**
 * Enqueue a style.
 *
 * @param string $style_key The style key.
 */
function ps_simple_par_admin_enqueue_style( $style_key ) {
	// Check the style key.
	if ( 'deactivation' === $style_key ) {
		// Set the style handle.
		$style_handle = 'ps-simple-par-admin-deactivation';

		// Check if the style is not enqueued.
		if ( ! wp_style_is( $style_handle, 'enqueued' ) ) {
			// Enqueue the style.
			wp_enqueue_style( $style_handle );
		}
		
		// Check the style key.
	} elseif ( 'settings' === $style_key ) {
		// Set the style handle.
		$style_handle = 'ps-simple-par-admin-settings';

		// Check if the style is not enqueued.
		if ( ! wp_style_is( $style_handle, 'enqueued' ) ) {
			// Enqueue the style.
			wp_enqueue_style( $style_handle );
		}
	}

	// Allow developers to use this.
	do_action( 'ps_simple_par_admin_enqueue_style', $style_key );
}

/**
 * Enqueue the styles.
 */
function ps_simple_par_admin_enqueue_styles() {
	// Check the current page.
	if ( ps_simple_par_admin_is_page( 'settings' ) ) {
		// Enqueue the admin styles.
		ps_simple_par_admin_enqueue_style( 'settings' );

		// Check the current page.
	} elseif ( ps_simple_par_admin_is_page( 'wp-plugins' ) ) {
		// Enqueue the admin styles.
		ps_simple_par_admin_enqueue_style( 'deactivation' );
	}

	// Allow developers to use this.
	do_action( 'ps_simple_par_admin_enqueue_styles' );
}
add_action( 'admin_enqueue_scripts', 'ps_simple_par_admin_enqueue_styles', 20 );
