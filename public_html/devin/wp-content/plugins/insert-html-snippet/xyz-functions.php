<?php
if ( ! defined( 'ABSPATH' ) ) 
	exit;

if(!function_exists('xyz_ihs_plugin_get_version'))
{
	function xyz_ihs_plugin_get_version() 
	{
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugin_folder = get_plugins( '/' . plugin_basename( dirname( XYZ_INSERT_HTML_PLUGIN_FILE ) ) );
		return $plugin_folder['insert-html-snippet.php']['Version'];
	}
}

if(!function_exists('xyz_ihs_run_upgrade_routines'))
{
function xyz_ihs_run_upgrade_routines() {
	global $wpdb;
	if (is_multisite()) {
		$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		foreach ($blog_ids as $blog_id) {
			switch_to_blog($blog_id);
			xyz_ihs_install();
			restore_current_blog();
		}
	} else {
		xyz_ihs_install();
	}
}
}
if(!function_exists('xyz_trim_deep'))
{

	function xyz_trim_deep($value) {
		if ( is_array($value) ) {
			$value = array_map('xyz_trim_deep', $value);
		} elseif ( is_object($value) ) {
			$vars = get_object_vars( $value );
			foreach ($vars as $key=>$data) {
				$value->{$key} = xyz_trim_deep( $data );
			}
		} else {
			$value = trim($value);
		}

		return $value;
	}

}


if(!function_exists('xyz_ihs_links')){
function xyz_ihs_links($links, $file) {
	$base = plugin_basename(XYZ_INSERT_HTML_PLUGIN_FILE);
	if ($file == $base) {

		$links[] = '<a href="https://xyzscripts.com/support/" class="xyz_ihs_support" title="Support"></a>';
		$links[] = '<a href="https://twitter.com/xyzscripts" class="xyz_ihs_twitt" title="Follow us on Twitter"></a>';
		$links[] = '<a href="https://www.facebook.com/xyzscripts" class="xyz_ihs_fbook" title="Like us on Facebook"></a>';
		$links[] = '<a href="https://www.instagram.com/xyz_scripts/" class="xyz_ihs_insta" title="Follow us on Instagram+"></a>';
		$links[] = '<a href="https://www.linkedin.com/company/xyzscripts" class="xyz_ihs_linkedin" title="Follow us on LinkedIn"></a>';
	}
	return $links;
}
}
add_filter( 'plugin_row_meta','xyz_ihs_links',10,2);

?>