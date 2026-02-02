<?php
/**
 * Block Styles
 *
 * @link https://developer.wordpress.org/reference/functions/register_block_style/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_One
 * @since 1.0.0
 */

if ( function_exists( 'register_block_style' ) ) {
	/**
	 * Register block styles.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function twenty_twenty_one_register_block_styles() {
		// Columns: Overlap.
		register_block_style(
			'core/columns',
			array(
				'name'  => 'Gymnex-columns-overlap',
				'label' => esc_html__( 'Overlap', 'Gymnex' ),
			)
		);

		// Cover: Borders.
		register_block_style(
			'core/cover',
			array(
				'name'  => 'Gymnex-border',
				'label' => esc_html__( 'Borders', 'Gymnex' ),
			)
		);

		// Group: Borders.
		register_block_style(
			'core/group',
			array(
				'name'  => 'Gymnex-border',
				'label' => esc_html__( 'Borders', 'Gymnex' ),
			)
		);

		// Image: Borders.
		register_block_style(
			'core/image',
			array(
				'name'  => 'Gymnex-border',
				'label' => esc_html__( 'Borders', 'Gymnex' ),
			)
		);

		// Image: Frame.
		register_block_style(
			'core/image',
			array(
				'name'  => 'Gymnex-image-frame',
				'label' => esc_html__( 'Frame', 'Gymnex' ),
			)
		);

		// Latest Posts: Dividers.
		register_block_style(
			'core/latest-posts',
			array(
				'name'  => 'Gymnex-latest-posts-dividers',
				'label' => esc_html__( 'Dividers', 'Gymnex' ),
			)
		);

		// Latest Posts: Borders.
		register_block_style(
			'core/latest-posts',
			array(
				'name'  => 'Gymnex-latest-posts-borders',
				'label' => esc_html__( 'Borders', 'Gymnex' ),
			)
		);

		// Media & Text: Borders.
		register_block_style(
			'core/media-text',
			array(
				'name'  => 'Gymnex-border',
				'label' => esc_html__( 'Borders', 'Gymnex' ),
			)
		);

		// Separator: Thick.
		register_block_style(
			'core/separator',
			array(
				'name'  => 'Gymnex-separator-thick',
				'label' => esc_html__( 'Thick', 'Gymnex' ),
			)
		);

		// Social icons: Dark gray color.
		register_block_style(
			'core/social-links',
			array(
				'name'  => 'Gymnex-social-icons-color',
				'label' => esc_html__( 'Dark gray', 'Gymnex' ),
			)
		);
	}
	add_action( 'init', 'twenty_twenty_one_register_block_styles' );
}
