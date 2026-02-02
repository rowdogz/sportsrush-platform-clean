<?php    
/**
 *sports-lite Theme Customizer
 *
 * @package Sports Lite
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function sports_lite_customize_register( $wp_customize ) {	
	function sports_lite_sanitize_dropdown_pages( $page_id, $setting ) {
	  // Ensure $input is an absolute integer.
	  $page_id = absint( $page_id );	
	  // If $page_id is an ID of a published page, return it; otherwise, return the default.
	  return ( 'publish' == get_post_status( $page_id ) ? $page_id : $setting->default );
	}
	function sports_lite_sanitize_checkbox( $checked ) {
		// Boolean check.
		return ( ( isset( $checked ) && true == $checked ) ? true : false );
	} 	
	function sports_lite_sanitize_phone_number( $phone ) {
		// sanitize phone
		return preg_replace( '/[^\d+]/', '', $phone );
	}		
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';	
	 //Panel for section & control
	$wp_customize->add_panel( 'sports_lite_themeoptions_panelsection', array(
		'priority' => null,
		'capability' => 'edit_theme_options',
		'theme_supports' => '',
		'title' => __( 'Theme Options Panel', 'sports-lite' ),		
	) );
	
	//box Layout Options
	$wp_customize->add_section('sports_lite_boxlayout_sections',array(
		'title' => __('Site Layout Options','sports-lite'),			
		'priority' => 1,
		'panel' => 	'sports_lite_themeoptions_panelsection',          
	));		
	
	$wp_customize->add_setting('sports_lite_layoutfixed',array(
		'sanitize_callback' => 'sports_lite_sanitize_checkbox',
	));	 

	$wp_customize->add_control( 'sports_lite_layoutfixed', array(
    	'section'   => 'sports_lite_boxlayout_sections',    	 
		'label' => __('Check to Show Box Layout','sports-lite'),
		'description' => __('If you want to show box layout please check the Box Layout Option.','sports-lite'),
    	'type'      => 'checkbox'
     )); //box Layout Options 
	 
	 $wp_customize->add_setting('sports_lite_menu_activehover_color',array(
		'default' => '#000000',
		'sanitize_callback' => 'sanitize_hex_color'
	));
	
	$wp_customize->add_control(
		new WP_Customize_Color_Control($wp_customize,'sports_lite_menu_activehover_color',array(
			'label' => __('Menu Hover Color','sports-lite'),			
			'section' => 'colors',
			'settings' => 'sports_lite_menu_activehover_color'
		))
	);	
	
	$wp_customize->add_setting('sports_lite_themecolorscheme',array(
		'default' => '#e21e22',
		'sanitize_callback' => 'sanitize_hex_color'
	));
	
	$wp_customize->add_control(
		new WP_Customize_Color_Control($wp_customize,'sports_lite_themecolorscheme',array(
			'label' => __('Color Options','sports-lite'),			
			'description' => __('More color scheme available in PRO Version','sports-lite'),
			'section' => 'colors',
			'settings' => 'sports_lite_themecolorscheme'
		))
	);	
	
	 //Header Contact section
	$wp_customize->add_section('sports_lite_topgetintouch_panel',array(
		'title' => __('Header Contact Section','sports-lite'),				
		'priority' => null,
		'panel' => 	'sports_lite_themeoptions_panelsection',
	));
	
	$wp_customize->add_setting('sports_lite_emailtext',array(
		'default' => null,
		'sanitize_callback' => 'sanitize_text_field'	
	));
	
	$wp_customize->add_control('sports_lite_emailtext',array(	
		'type' => 'text',
		'label' => __('Enter email text here','sports-lite'),
		'section' => 'sports_lite_topgetintouch_panel',
		'setting' => 'sports_lite_emailtext'
	));	
	
	
	$wp_customize->add_setting('sports_lite_header_emailid',array(
		'sanitize_callback' => 'sanitize_email'
	));
	
	$wp_customize->add_control('sports_lite_header_emailid',array(
		'type' => 'email',
		'label' => __('enter email id here.','sports-lite'),
		'section' => 'sports_lite_topgetintouch_panel'
	));		
				
	
	$wp_customize->add_setting('sports_lite_callustext',array(
		'default' => null,
		'sanitize_callback' => 'sanitize_text_field'	
	));
	
	$wp_customize->add_control('sports_lite_callustext',array(	
		'type' => 'text',
		'label' => __('Enter Call Us text here','sports-lite'),
		'section' => 'sports_lite_topgetintouch_panel',
		'setting' => 'sports_lite_callustext'
	));	
	
	
	$wp_customize->add_setting('sports_lite_header_contactno',array(
		'default' => null,
		'sanitize_callback' => 'sports_lite_sanitize_phone_number'	
	));
	
	$wp_customize->add_control('sports_lite_header_contactno',array(	
		'type' => 'text',
		'label' => __('Enter phone number here','sports-lite'),
		'section' => 'sports_lite_topgetintouch_panel',
		'setting' => 'sports_lite_header_contactno'
	));	
	
	$wp_customize->add_setting('sports_lite_worktimetext',array(
		'default' => null,
		'sanitize_callback' => 'sanitize_text_field'	
	));
	
	$wp_customize->add_control('sports_lite_worktimetext',array(	
		'type' => 'text',
		'label' => __('Enter work time text here','sports-lite'),
		'section' => 'sports_lite_topgetintouch_panel',
		'setting' => 'sports_lite_worktimetext'
	));	
	
	
	$wp_customize->add_setting('sports_lite_header_officetime',array(
		'default' => null,
		'sanitize_callback' => 'sanitize_text_field'	
	));
	
	$wp_customize->add_control('sports_lite_header_officetime',array(	
		'type' => 'text',
		'label' => __('Enter work timing here','sports-lite'),
		'section' => 'sports_lite_topgetintouch_panel',
		'setting' => 'sports_lite_header_officetime'
	));	
	
	
	$wp_customize->add_setting('sports_lite_show_topgetintouch_panel',array(
		'default' => false,
		'sanitize_callback' => 'sports_lite_sanitize_checkbox',
		'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'sports_lite_show_topgetintouch_panel', array(
	   'settings' => 'sports_lite_show_topgetintouch_panel',
	   'section'   => 'sports_lite_topgetintouch_panel',
	   'label'     => __('Check To show This Section','sports-lite'),
	   'type'      => 'checkbox'
	 ));//Show top get in touch panel
	 
	 
	 // Front Slider Section		
	$wp_customize->add_section( 'sports_lite_homepageslide_panel', array(
		'title' => __('Frontpage Slider Sections', 'sports-lite'),
		'priority' => null,
		'description' => __('Default image size for slider is 1400 x 824px','sports-lite'), 
		'panel' => 	'sports_lite_themeoptions_panelsection',           			
    ));
	
	$wp_customize->add_setting('sports_lite_slidepge1',array(
		'default' => '0',			
		'capability' => 'edit_theme_options',
		'sanitize_callback' => 'sports_lite_sanitize_dropdown_pages'
	));
	
	$wp_customize->add_control('sports_lite_slidepge1',array(
		'type' => 'dropdown-pages',
		'label' => __('Select page for slide 1:','sports-lite'),
		'section' => 'sports_lite_homepageslide_panel'
	));	
	
	$wp_customize->add_setting('sports_lite_slidepge2',array(
		'default' => '0',			
		'capability' => 'edit_theme_options',
		'sanitize_callback' => 'sports_lite_sanitize_dropdown_pages'
	));
	
	$wp_customize->add_control('sports_lite_slidepge2',array(
		'type' => 'dropdown-pages',
		'label' => __('Select page for slide 2:','sports-lite'),
		'section' => 'sports_lite_homepageslide_panel'
	));	
	
	$wp_customize->add_setting('sports_lite_slidepge3',array(
		'default' => '0',			
		'capability' => 'edit_theme_options',
		'sanitize_callback' => 'sports_lite_sanitize_dropdown_pages'
	));
	
	$wp_customize->add_control('sports_lite_slidepge3',array(
		'type' => 'dropdown-pages',
		'label' => __('Select page for slide 3:','sports-lite'),
		'section' => 'sports_lite_homepageslide_panel'
	));	// Homepage Slider Section
	
	$wp_customize->add_setting('sports_lite_slidepgebutton',array(
		'default' => null,
		'sanitize_callback' => 'sanitize_text_field'	
	));
	
	$wp_customize->add_control('sports_lite_slidepgebutton',array(	
		'type' => 'text',
		'label' => __('slider Read more button name here','sports-lite'),
		'section' => 'sports_lite_homepageslide_panel',
		'setting' => 'sports_lite_slidepgebutton'
	)); // Home Slider Read More Button Text
	
	$wp_customize->add_setting('sports_lite_show_homepageslide_panel',array(
		'default' => false,
		'sanitize_callback' => 'sports_lite_sanitize_checkbox',
		'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'sports_lite_show_homepageslide_panel', array(
	    'settings' => 'sports_lite_show_homepageslide_panel',
	    'section'   => 'sports_lite_homepageslide_panel',
	    'label'     => __('Check To Show This Section','sports-lite'),
	   'type'      => 'checkbox'
	 ));//Show Homepage Slider Section		 
	
		 
	 //Social icons Section
	$wp_customize->add_section('sports_lite_socialsections',array(
		'title' => __('Header Social Sections','sports-lite'),
		'description' => __( 'Add social icons link here to display icons in header ', 'sports-lite' ),			
		'priority' => null,
		'panel' => 	'sports_lite_themeoptions_panelsection', 
	));
	
	$wp_customize->add_setting('sports_lite_facebook_link',array(
		'default' => null,
		'sanitize_callback' => 'esc_url_raw'	
	));
	
	$wp_customize->add_control('sports_lite_facebook_link',array(
		'label' => __('Add facebook link here','sports-lite'),
		'section' => 'sports_lite_socialsections',
		'setting' => 'sports_lite_facebook_link'
	));	
	
	$wp_customize->add_setting('sports_lite_twitter_link',array(
		'default' => null,
		'sanitize_callback' => 'esc_url_raw'
	));
	
	$wp_customize->add_control('sports_lite_twitter_link',array(
		'label' => __('Add twitter link here','sports-lite'),
		'section' => 'sports_lite_socialsections',
		'setting' => 'sports_lite_twitter_link'
	));
	
	$wp_customize->add_setting('sports_lite_linkedin_link',array(
		'default' => null,
		'sanitize_callback' => 'esc_url_raw'
	));
	
	$wp_customize->add_control('sports_lite_linkedin_link',array(
		'label' => __('Add linkedin link here','sports-lite'),
		'section' => 'sports_lite_socialsections',
		'setting' => 'sports_lite_linkedin_link'
	));
	
	$wp_customize->add_setting('sports_lite_instagram_link',array(
		'default' => null,
		'sanitize_callback' => 'esc_url_raw'
	));
	
	$wp_customize->add_control('sports_lite_instagram_link',array(
		'label' => __('Add instagram link here','sports-lite'),
		'section' => 'sports_lite_socialsections',
		'setting' => 'sports_lite_instagram_link'
	));
	
	$wp_customize->add_setting('sports_lite_show_socialsections',array(
		'default' => false,
		'sanitize_callback' => 'sports_lite_sanitize_checkbox',
		'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'sports_lite_show_socialsections', array(
	   'settings' => 'sports_lite_show_socialsections',
	   'section'   => 'sports_lite_socialsections',
	   'label'     => __('Check To show This Section','sports-lite'),
	   'type'      => 'checkbox'
	 ));//Show Social icons Section	
	 
	 //Three Box Section
	$wp_customize->add_section('sports_lite_frontpage3bx_sections', array(
		'title' => __('Three Box Section','sports-lite'),
		'description' => __('Select pages from the dropdown for three box sections','sports-lite'),
		'priority' => null,
		'panel' => 	'sports_lite_themeoptions_panelsection',          
	));	
	
	
	$wp_customize->add_setting('sports_lite_fp3pagebx1',array(
		'default' => '0',			
		'capability' => 'edit_theme_options',
		'sanitize_callback' => 'sports_lite_sanitize_dropdown_pages'
	));
 
	$wp_customize->add_control(	'sports_lite_fp3pagebx1',array(
		'type' => 'dropdown-pages',			
		'section' => 'sports_lite_frontpage3bx_sections',
	));		
	
	$wp_customize->add_setting('sports_lite_fp3pagebx2',array(
		'default' => '0',			
		'capability' => 'edit_theme_options',
		'sanitize_callback' => 'sports_lite_sanitize_dropdown_pages'
	));
 
	$wp_customize->add_control(	'sports_lite_fp3pagebx2',array(
		'type' => 'dropdown-pages',			
		'section' => 'sports_lite_frontpage3bx_sections',
	));
	
	$wp_customize->add_setting('sports_lite_fp3pagebx3',array(
		'default' => '0',			
		'capability' => 'edit_theme_options',
		'sanitize_callback' => 'sports_lite_sanitize_dropdown_pages'
	));
 
	$wp_customize->add_control(	'sports_lite_fp3pagebx3',array(
		'type' => 'dropdown-pages',			
		'section' => 'sports_lite_frontpage3bx_sections',
	));		
	
	$wp_customize->add_setting('sports_lite_show_frontpage3bx_sections',array(
		'default' => false,
		'sanitize_callback' => 'sports_lite_sanitize_checkbox',
		'capability' => 'edit_theme_options',
	));	 	
	
	$wp_customize->add_control( 'sports_lite_show_frontpage3bx_sections', array(
	   'settings' => 'sports_lite_show_frontpage3bx_sections',
	   'section'   => 'sports_lite_frontpage3bx_sections',
	   'label'     => __('Check To Show This Section','sports-lite'),
	   'type'      => 'checkbox'
	 ));//Show three box services Section	 
	 
	 //Abouts Club Panel
	$wp_customize->add_section('sports_lite_aboutclubpanel', array(
		'title' => __('About Club Section','sports-lite'),
		'description' => __('Select Pages from the dropdown for about page section','sports-lite'),
		'priority' => null,
		'panel' => 	'sports_lite_themeoptions_panelsection',          
	));		
	
	$wp_customize->add_setting('sports_lite_aboutclubpage',array(
		'default' => '0',			
		'capability' => 'edit_theme_options',
		'sanitize_callback' => 'sports_lite_sanitize_dropdown_pages'
	));
 
	$wp_customize->add_control(	'sports_lite_aboutclubpage',array(
		'type' => 'dropdown-pages',			
		'section' => 'sports_lite_aboutclubpanel',
	));		
	
	$wp_customize->add_setting('sports_lite_show_aboutclubpanel',array(
		'default' => false,
		'sanitize_callback' => 'sports_lite_sanitize_checkbox',
		'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'sports_lite_show_aboutclubpanel', array(
	    'settings' => 'sports_lite_show_aboutclubpanel',
	    'section'   => 'sports_lite_aboutclubpanel',
	    'label'     => __('Check To Show This Section','sports-lite'),
	    'type'      => 'checkbox'
	));//Show Welcome Page Section
	 
 
	//Sidebar Sections
	$wp_customize->add_section('sports_lite_sidebar_options', array(
		'title' => __('Sidebar Options','sports-lite'),		
		'priority' => null,
		'panel' => 	'sports_lite_themeoptions_panelsection',          
	));	
	 
	 $wp_customize->add_setting('sports_lite_hidesidebar_singlepost',array(
		'default' => false,
		'sanitize_callback' => 'sports_lite_sanitize_checkbox',
		'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'sports_lite_hidesidebar_singlepost', array(
	   'settings' => 'sports_lite_hidesidebar_singlepost',
	   'section'   => 'sports_lite_sidebar_options',
	   'label'     => __('Check to hide sidebar from single post','sports-lite'),
	   'type'      => 'checkbox'
	 ));// hide sidebar single post	
	 
	 $wp_customize->add_setting('sports_lite_hidesidebar_from_homepage',array(
		'default' => false,
		'sanitize_callback' => 'sports_lite_sanitize_checkbox',
		'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'sports_lite_hidesidebar_from_homepage', array(
	   'settings' => 'sports_lite_hidesidebar_from_homepage',
	   'section'   => 'sports_lite_sidebar_options',
	   'label'     => __('Check to hide sidebar from latest post page','sports-lite'),
	   'type'      => 'checkbox'
	 ));// Hide sidebar from latest post page 

		 
}
add_action( 'customize_register', 'sports_lite_customize_register' );

function sports_lite_custom_css(){ 
?>
	<style type="text/css"> 					
        a, .blogpost_styling h2 a:hover,
		.copy_right ul li a:hover,
		.hdr_infbx a:hover,
		.front_3column:hover .blogmore,
        #sidebar ul li a:hover,	        
		.hdr_socialbar a:hover,
		.nivo-caption h2 span,		
		.blog_postmeta a:hover,
		.blog_postmeta a:focus,
		.blogpost_styling h3 a:hover,		
        .postmeta a:hover,	
        .button:hover,
		.front_3column:hover h4 a,		
		.site-footer ul li a:hover, 
		.site-footer ul li.current_page_item a,
		.site-footer ul li.current-cat a 	 		
            { color:<?php echo esc_html( get_theme_mod('sports_lite_themecolorscheme','#e21e22')); ?>;}        
			
		
		.tagcloud a:hover,		
		.hdr_socialbar a:hover
            { border-color:<?php echo esc_html( get_theme_mod('sports_lite_themecolorscheme','#e21e22')); ?>;}			
			
		 button:focus,
		input[type="button"]:focus,		
		input[type="email"]:focus,
		input[type="url"]:focus,
		input[type="password"]:focus,
		input[type="reset"]:focus,
		input[type="submit"]:focus,
		input[type="text"]:focus,
		input[type="search"]:focus,
		input[type="number"]:focus,
		input[type="tel"]:focus,		
		input[type="week"]:focus,
		input[type="time"]:focus,
		input[type="datetime"]:focus,
		input[type="range"]:focus,
		input[type="date"]:focus,
		input[type="month"]:focus,
		input[type="datetime-local"]:focus,
		input[type="color"]:focus,
		textarea:focus,
		#site-layout-type a:focus
            { outline:thin dotted <?php echo esc_html( get_theme_mod('sports_lite_themecolorscheme','#e21e22')); ?>;}		
		
		.site-navigation .menu a:hover,
		.site-navigation .menu a:focus,		
		.site-navigation ul li a:hover, 
		.site-navigation .menu ul a:hover,
		.site-navigation .menu ul a:focus,
		.site-navigation ul li.current-menu-item a,
		.site-navigation ul li.current-menu-parent a.parent,
		.site-navigation ul li.current-menu-item ul.sub-menu li a:hover
            { color:<?php echo esc_html( get_theme_mod('sports_lite_menu_activehover_color','#000000')); ?>;}
			
			.pagination ul li .current, .pagination ul li a:hover, 
        #commentform input#submit:hover,		
        .nivo-controlNav a.active,					
        .wpcf7 input[type='submit'],				
        nav.pagination .page-numbers.current,		
		.blogreadbtn,		
		.nivo-directionNav a:hover,		
        .toggle a,
		.sd-search input, .sd-top-bar-nav .sd-search input,			
		a.blogreadmore,			
		.nivo-caption .slide_morebtn:hover,											
        #sidebar .search-form input.search-submit,	
		.front_abtimgstyle:after,	
		.mainmenu-left-area,
		.mainmenu-left-area:before,
		.mainmenu-left-area:after,
		.site-navigation .menu ul,
		.front_3column .front_imgbx	
            { background-color:<?php echo esc_html( get_theme_mod('sports_lite_themecolorscheme','#e21e22')); ?>;}						
	
    </style> 
<?php                                                                                                                       
}
         
add_action('wp_head','sports_lite_custom_css');	 

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function sports_lite_customize_preview_js() {
	wp_enqueue_script( 'sports_lite_customizer', get_template_directory_uri() . '/js/customize-preview.js', array( 'customize-preview' ), '19062019', true );
}
add_action( 'customize_preview_init', 'sports_lite_customize_preview_js' );