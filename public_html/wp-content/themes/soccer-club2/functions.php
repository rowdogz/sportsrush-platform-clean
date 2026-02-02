<?php
/**
 * Soccer Club functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package soccer-club
 * @since soccer-club 1.0
 */

if ( ! function_exists( 'soccer_club_support' ) ) :

	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * @since soccer-club 1.0
	 *
	 * @return void
	 */
	function soccer_club_support() {

		load_theme_textdomain( 'soccer-club', get_template_directory() . '/languages' );
		
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		// Add support for block styles.
		add_theme_support( 'wp-block-styles' );

		add_theme_support( 'align-wide' );

		// Enqueue editor styles.
		add_editor_style( 'style.css' );

		add_theme_support( 'responsive-embeds' );

   		add_theme_support( 'woocommerce' );
		
		// Add support for experimental link color control.
		add_theme_support( 'experimental-link-color' );
	}

endif;

add_action( 'after_setup_theme', 'soccer_club_support' );

if ( ! function_exists( 'soccer_club_styles' ) ) :

	/**
	 * Enqueue styles.
	 *
	 * @since soccer-club 1.0
	 *
	 * @return void
	 */
	function soccer_club_styles() {

		// Register theme stylesheet.
		wp_register_style(
			'soccer-club-style',
			get_template_directory_uri() . '/style.css',
			array(),
			wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_style( 
			'soccer-club-animate-css', 
			esc_url(get_template_directory_uri()).'/assets/css/animate.css' 
		);

		// Enqueue theme stylesheet.
		wp_enqueue_style( 'soccer-club-style' );

		wp_style_add_data( 'soccer-club-style', 'rtl', 'replace' );

	}

endif;

add_action( 'wp_enqueue_scripts', 'soccer_club_styles' );

/* Enqueue Wow Js */
function soccer_club_scripts() {
	wp_enqueue_script( 
		'soccer-club-wow', esc_url(get_template_directory_uri()) . '/assets/js/wow.js', 
		array('jquery') 
	);
	wp_enqueue_script(
        'scroll-to-top', 
        esc_url(get_template_directory_uri()) . '/assets/js/scroll-to-top.js', 
        array(), 
        null, 
        true // Load in footer
    );
}
add_action( 'wp_enqueue_scripts', 'soccer_club_scripts' );

// Add block patterns
require get_template_directory() . '/inc/block-pattern.php';

// Add block Style
require get_template_directory() . '/inc/block-style.php';

// Get Started
require get_template_directory() . '/get-started/getstart.php';

// Get Notice
require get_template_directory() . '/get-started/notice.php';

// Add Customizer
require get_template_directory() . '/inc/customizer.php';
