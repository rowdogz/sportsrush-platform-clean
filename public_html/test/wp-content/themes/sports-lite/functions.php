<?php                                      
/**
 * Sports Lite functions and definitions
 *
 * @package Sports Lite
 */

/**
 * Set the content width based on the theme's design and stylesheet.
 */

if ( ! function_exists( 'sports_lite_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.  
 */
function sports_lite_setup() { 		
	$GLOBALS['content_width'] = apply_filters( 'sports_lite_content_width', 680 );		
	load_theme_textdomain( 'sports-lite', get_template_directory() . '/languages' );
	add_theme_support( 'automatic-feed-links' );	
	add_theme_support( 'post-thumbnails' );	
	add_theme_support( 'title-tag' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );	
	add_theme_support( 'wp-block-styles' );	
	add_theme_support( 'custom-logo', array(
		'height'      => 100,
		'width'       => 250,
		'flex-height' => true,
	) );
	
	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );
	
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'sports-lite' ),
		'footer' => __( 'Footer Menu', 'sports-lite' ),						
	) );	
	add_theme_support( 'custom-background', array(
		'default-color' => 'ffffff'
	) );	
	add_action( 'wp_enqueue_scripts', function() {
     wp_enqueue_style( 'dashicons' );
	} );	
	
	add_editor_style( 'editor-style.css' );
} 
endif; // sports_lite_setup
add_action( 'after_setup_theme', 'sports_lite_setup' );
function sports_lite_widgets_init() { 	
	
	register_sidebar( array(
		'name'          => __( 'Blog Sidebar', 'sports-lite' ),
		'description'   => __( 'Appears on blog page sidebar', 'sports-lite' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Footer Widget 1', 'sports-lite' ),
		'description'   => __( 'Appears on footer', 'sports-lite' ),
		'id'            => 'footer-widget-1',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Footer Widget 2', 'sports-lite' ),
		'description'   => __( 'Appears on footer', 'sports-lite' ),
		'id'            => 'footer-widget-2',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Footer Widget 3', 'sports-lite' ),
		'description'   => __( 'Appears on footer', 'sports-lite' ),
		'id'            => 'footer-widget-3',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
	) );
	
}
add_action( 'widgets_init', 'sports_lite_widgets_init' );


function sports_lite_font_url(){
		$font_url = '';	
		
		/* Translators: If there are any character that are not
		* supported by Poppins, trsnalate this to off, do not
		* translate into your own language.
		*/
		
		$poppins = _x('on','Poppins:on or off','sports-lite');	
		/* Translators: If there are any character that are not
		* supported by Assistant, trsnalate this to off, do not
		* translate into your own language.
		*/
		$assistant = _x('on','Assistant:on or off','sports-lite');		
		
		
		    if( 'off' !== $poppins || 'off' !== $assistant ){
			    $font_family = array();			
			
			if('off' !== $poppins){
				$font_family[] = 'Poppins:400,600,700,800';
			}
			
			if('off' !== $assistant){
				$font_family[] = 'Assistant:300,400,600';
			}			
						
			$query_args = array(
				'family'	=> urlencode(implode('|',$font_family)),
			);
			
			$font_url = add_query_arg($query_args,'//fonts.googleapis.com/css');
		}
		
	return $font_url;
	}


function sports_lite_scripts() {
	wp_enqueue_style('sports-lite-font', sports_lite_font_url(), array());
	wp_enqueue_style( 'sports-lite-basic-style', get_stylesheet_uri() );	
	wp_enqueue_style( 'nivo-slider', get_template_directory_uri()."/css/nivo-slider.css" );
	wp_enqueue_style( 'fontawesome-all-style', get_template_directory_uri().'/fontsawesome/css/fontawesome-all.css' );
	wp_enqueue_style( 'sports-lite-responsive', get_template_directory_uri()."/css/responsive.css" );
	wp_enqueue_script( 'jquery-nivo-slider', get_template_directory_uri() . '/js/jquery.nivo.slider.js', array('jquery') );
	wp_enqueue_script( 'sports-lite-editable', get_template_directory_uri() . '/js/editable.js' );
	wp_enqueue_script( 'sports-lite', get_template_directory_uri() . '/js/navigation.js', array(), '02062021', true );
	wp_localize_script( 'sports-lite', 'sportslitescreenreadertext', array(
		'expandMain'   => __( 'Open the main menu', 'sports-lite' ),
		'collapseMain' => __( 'Close the main menu', 'sports-lite' ),
		'expandChild'   => __( 'expand submenu', 'sports-lite' ),
		'collapseChild' => __( 'collapse submenu', 'sports-lite' ),
	) );
	
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'sports_lite_scripts' );

function sports_lite_ie_stylesheet(){
	// Load the Internet Explorer specific stylesheet.
	wp_enqueue_style('sports-lite-ie', get_template_directory_uri().'/css/ie.css', array( 'sports-lite-style' ), '20190312' );
	wp_style_add_data('sports-lite-ie','conditional','lt IE 10');
	
	// Load the Internet Explorer 8 specific stylesheet.
	wp_enqueue_style( 'sports-lite-ie8', get_template_directory_uri() . '/css/ie8.css', array( 'sports-lite-style' ), '20190312' );
	wp_style_add_data( 'sports-lite-ie8', 'conditional', 'lt IE 9' );

	// Load the Internet Explorer 7 specific stylesheet.
	wp_enqueue_style( 'sports-lite-ie7', get_template_directory_uri() . '/css/ie7.css', array( 'sports-lite-style' ), '20190312' );
	wp_style_add_data( 'sports-lite-ie7', 'conditional', 'lt IE 8' );	
	}
add_action('wp_enqueue_scripts','sports_lite_ie_stylesheet');

/**
 * Customize Pro included.
 */
require_once get_template_directory() . '/customize-pro/class-customize.php';

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom template for about theme.
 */
if ( is_admin() ) { 
require get_template_directory() . '/inc/about-themes.php';
}

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';

/**
 * WooCommerce Compatibility
 */
add_action( 'after_setup_theme', 'sports_lite_setup_woocommerce_support' );
function sports_lite_setup_woocommerce_support()   
{
  	add_theme_support('woocommerce');
	add_theme_support( 'wc-product-gallery-zoom' ); 
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' ); 
}


/**
 * Fix skip link focus in IE11.
 *
 * This does not enqueue the script because it is tiny and because it is only for IE11,
 * thus it does not warrant having an entire dedicated blocking script being loaded.
 *
 * @link https://git.io/vWdr2
 */
function sports_lite_skip_link_focus_fix() {  
	// The following is minified via `terser --compress --mangle -- js/skip-link-focus-fix.js`.
	?>
	<script>
	/(trident|msie)/i.test(navigator.userAgent)&&document.getElementById&&window.addEventListener&&window.addEventListener("hashchange",function(){var t,e=location.hash.substring(1);/^[A-z0-9_-]+$/.test(e)&&(t=document.getElementById(e))&&(/^(?:a|select|input|button|textarea)$/i.test(t.tagName)||(t.tabIndex=-1),t.focus())},!1);
	</script>
	<?php                
} 
add_action( 'wp_print_footer_scripts', 'sports_lite_skip_link_focus_fix' );

//Custom Excerpt length.
function sports_lite_excerpt_length( $length ) {
    if ( is_admin() ) return $length;
    return 20;
}
add_filter( 'excerpt_length', 'sports_lite_excerpt_length', 999 );

if ( ! function_exists( 'sports_lite_the_custom_logo' ) ) :
/**
 * Displays the optional custom logo.
 *
 * Does nothing if the custom logo is not available.
 *
 */
function sports_lite_the_custom_logo() {
	if ( function_exists( 'the_custom_logo' ) ) {
		the_custom_logo();
	}
}
function fix_nextend_facebook_translation_issue() {
    if (did_action('init')) {
        load_plugin_textdomain('nextend-facebook-connect', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}
add_action('init', 'fix_nextend_facebook_translation_issue', 20); // Higher priority (20) ensures it runs later
endif;


