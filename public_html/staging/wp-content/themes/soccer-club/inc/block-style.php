<?php
/**
 * Block Styles
 *
 * @link https://developer.wordpress.org/reference/functions/register_block_style/
 *
 * @package WordPress
 * @subpackage soccer-club
 * @since soccer-club 1.0
 */

if ( function_exists( 'register_block_style' ) ) {
	/**
	 * Register block styles.
	 *
	 * @since soccer-club 1.0
	 *
	 * @return void
	 */
	function soccer_club_register_block_styles() {
		
		// Image: Borders.
		register_block_style(
			'core/image',
			array(
				'name'  => 'soccer-club-border',
				'label' => esc_html__( 'Borders', 'soccer-club' ),
			)
		);

		
	}
	add_action( 'init', 'soccer_club_register_block_styles' );
}