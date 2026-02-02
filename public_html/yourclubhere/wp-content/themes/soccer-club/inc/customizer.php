<?php
/**
 * Customizer
 * 
 * @package WordPress
 * @subpackage soccer-club
 * @since soccer-club 1.0
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function soccer_club_customize_register( $wp_customize ) {
	$wp_customize->add_section( new Soccer_Club_Upsell_Section($wp_customize,'upsell_section',array(
		'title'            => __( 'Soccer Club Pro', 'soccer-club' ),
		'button_text'      => __( 'Upgrade Pro', 'soccer-club' ),
		'url'              => 'https://www.wpradiant.net/products/soccer-club-wordpress-theme',
		'priority'         => 0,
	)));
}
add_action( 'customize_register', 'soccer_club_customize_register' );

/**
 * Enqueue script for custom customize control.
 */
function soccer_club_custom_control_scripts() {
	wp_enqueue_script( 'soccer-club-custom-controls-js', get_template_directory_uri() . '/assets/js/custom-controls.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), '1.0', true );
	wp_enqueue_style( 'soccer-club-customize-controls', trailingslashit( get_template_directory_uri() ) . '/assets/css/customize-controls.css' );
}
add_action( 'customize_controls_enqueue_scripts', 'soccer_club_custom_control_scripts' );