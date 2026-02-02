<?php 
if ( ! defined( 'ABSPATH' ) ) 
	exit;
	
global $wpdb;

include_once  'admin/constants.php';
add_shortcode('xyz-ihs','xyz_ihs_display_content');		
$table_name = $wpdb->prefix . 'xyz_ihs_short_code';
$snippets = $wpdb->get_results("SELECT * FROM $table_name WHERE insertionMethod = 1 AND status = 1");


foreach ($snippets as $snippet) {
	
	switch ($snippet->insertionLocation) {
		

		case XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_HEADER']:
			if (is_admin()) {
				add_action('admin_head', function() use ($snippet) {
					echo xyz_execute_ihs_snippet($snippet);
				}, 10);
			}
			
			break;	
			case XYZ_IHS_INSERTION_LOCATION['ADMIN_RUN_ON_FOOTER']:
				if (is_admin()) {

					
				add_action('admin_footer', function() use ($snippet) {
					echo xyz_execute_ihs_snippet($snippet);
				}, 10);
			}
			
			break;	

			
		
			case XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_HEADER']:
				if (!is_admin()) {
				add_action('wp_head', function() use ($snippet) {
				echo xyz_execute_ihs_snippet($snippet);
				}, 10);
			}
			
			break;	
		
			case XYZ_IHS_INSERTION_LOCATION['FRONTEND_RUN_ON_FOOTER']:
				if (!is_admin()) {
			
					
				add_action('wp_footer', function() use ($snippet) {

					echo xyz_execute_ihs_snippet($snippet);
					
				}, 10);
			}
			
			break;	
		
			  
			
				
				

	}
}

function xyz_execute_ihs_snippet($sippetdetails)
{

if($sippetdetails->status==1){
	$xyz_ihs_content=$sippetdetails->content;
    if (!empty($xyz_ihs_content)) {
        return $xyz_ihs_content;
    } else {
        return ''; 
    }
		
	  }
	  else{
		  return '';
	  }


}
/* customization ends */

function xyz_ihs_display_content($xyz_snippet_name){
	global $wpdb;
	$xyz_ihs_exec_in_editor = get_option('xyz_ihs_exec_in_editor');
    if ( $xyz_ihs_exec_in_editor ) {
        // Page Builder checks (Elementor, WPBakery, Divi, Beaver Builder)
        if ( wp_doing_ajax() ) {
            $builder_actions = ['elementor_preview', 'wpb_pb_preview', 'et_pb_preview'];
            // Check for Elementor, WPBakery, Divi actions
            if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $builder_actions, true ) ) {
                // Allow shortcode execution in page builder previews
            }
        // Beaver Builder detection using URL parameters
        if ( isset( $_REQUEST['fl_builder'] ) && isset( $_REQUEST['fl_builder'] ) ) {
            // Allow execution for Beaver Builder preview
            }
        }
        // Classic Editor or Gutenberg Editor check (editing posts)
        if ( is_admin() && isset( $_GET['post'] ) && 'edit' === $_GET['action'] ) {
            // Allow shortcode execution in Classic Editor or Gutenberg (when editing posts)
        }
    } elseif ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return ''; // Do not execute shortcode in other admin areas or REST API requests
	}
	if(is_array($xyz_snippet_name)&& isset($xyz_snippet_name['snippet'])){
	   
		$snippet_name = $xyz_snippet_name['snippet'];
		
		$query = $wpdb->get_results($wpdb->prepare( "SELECT * FROM ".$wpdb->prefix."xyz_ihs_short_code WHERE title=%s" ,$snippet_name));
		
		if(!empty($query))//if(count($query)>0)
		{
			foreach ($query as $sippetdetails){
			if($sippetdetails->status==1)
				return do_shortcode($sippetdetails->content) ;
			else 
				return '';
				break;
			}
			
		}else{

			return '';		
		}
		
	}
}


add_filter('widget_text', 'do_shortcode'); // to run shortcodes in text widgets
