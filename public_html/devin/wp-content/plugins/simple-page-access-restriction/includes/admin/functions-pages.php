<?php
/**
 * Admin - Functions - Pages.
 *
 * @package Simple_Page_Access_Restriction.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether the key refers to an admin page.
 *
 * @param string $key The key.
 * @return bool Whether the key refers to an admin page.
 */
function ps_simple_par_admin_is_page( $key ) {
	global $pagenow, $typenow;

	// Set the found.
	$found = false;

	// Get the page.
	$page = isset( $_GET['page'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : false;

	// Get the post type.
	$post_type = isset( $_GET['post_type'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) : false;

	// Get the view.
	$view = isset( $_GET['view'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['view'] ) ) ) : false;

	// Get the settings.
	$settings = ps_simple_par_get_settings();

	// Get the post types.
	$post_types = $settings['post_types'];

	// Check the key.
	if ( in_array( $key, array( 'restrictable-list-table', 'any' ) ) ) {
		// Check the post type.
		if ( ( in_array( $typenow, $post_types, true ) || in_array( $post_type, $post_types, true ) ) && 'edit.php' === $pagenow ) {
			// Set the found.
			$found = true;
		}
	}

	// Check the key.
	if ( in_array( $key, array( 'settings', 'any' ) ) ) {
		// Check the pagenow.
		if ( 'admin.php' === $pagenow ) {
			// Check the page.
			if ( 'simple-page-access-restriction' === $page ) {
				// Set the found.
				$found = true;
			}
		}
	}

	// Check the key.
	if ( in_array( $key, array( 'wp-plugins', 'any' ) ) ) {
		// Get the current screen.
		$current_screen = get_current_screen();

		// Check if the current screen id is plugins.
		if ( ! empty( $current_screen->id ) && 'plugins' === $current_screen->id ) {
			// Set the found.
			$found = true;
		}
	}

	// Allow developers to filter this.
	$found = apply_filters( 'ps_simple_par_admin_is_page', $found, $page, $view, $key );

	// Return the found.
	return $found;
}
