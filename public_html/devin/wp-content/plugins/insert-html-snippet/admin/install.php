<?php
if ( ! defined( 'ABSPATH' ) )
	exit;
function xyz_ihs_network_install($networkwide) {
	global $wpdb;

	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide) {
			$old_blog = $wpdb->blogid;
			// Get all blog ids
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				xyz_ihs_install();
			}
			switch_to_blog($old_blog);
			return;
		}
	}
	xyz_ihs_install();
}


function xyz_ihs_install(){

	global $wpdb;

	$pluginName = 'xyz-wp-insert-code-snippet/xyz-wp-insert-code-snippet.php';
if (is_plugin_active($pluginName)) {
	wp_die( "The plugin Insert HTML Snippet cannot be activated unless the premium version of this plugin is deactivated. Back to <a href='".esc_url(admin_url())."plugins.php'>Plugin Installation</a>." );
}
	if ( version_compare( PHP_VERSION, '7.0.0', '<' ) ) {
		wp_die(
			sprintf(
				'This plugin requires PHP version 7.0 or higher. You are using PHP %s. <a href="%s">Go back to Plugins page</a>.',
				PHP_VERSION,
				esc_url( admin_url( 'plugins.php' ) )
			)
		);
}
	if(get_option('xyz_ihs_sort_order')=='')
	{
		add_option('xyz_ihs_sort_order','desc');
	}
	if(get_option('xyz_ihs_sort_field_name')=='')
	{
		add_option('xyz_ihs_sort_field_name','id');
	}

	$xyz_ihs_installed_date = get_option('xyz_ihs_installed_date');
	if ($xyz_ihs_installed_date=="") {
		$xyz_ihs_installed_date = time();
		update_option('xyz_ihs_installed_date', $xyz_ihs_installed_date);
	}

	if(get_option('xyz_credit_link') == "")
	{
			add_option("xyz_credit_link",0);
	}

	if(get_option('xyz_ihs_dismiss')=='')
	{
		add_option('xyz_ihs_dismiss',0);
	}

	if(get_option('xyz_ihs_premium_version_ads')==""){
    	add_option('xyz_ihs_premium_version_ads',1);
	}

	add_option('xyz_ihs_limit',20);
	add_option('xyz_ihs_exec_in_editor','0');
	
	
	$charset_collate = $wpdb->get_charset_collate();
	$queryInsertHtml = "CREATE TABLE IF NOT EXISTS  ".$wpdb->prefix."xyz_ihs_short_code (
	  `id` int NOT NULL AUTO_INCREMENT,
		  `title` varchar(1000) NOT NULL,
		  `description` TEXT NULL ,
           `content` longtext  NOT NULL,
		  `short_code` varchar(2000) NOT NULL,
		  `status` int NOT NULL,
		  PRIMARY KEY (`id`)
		)ENGINE=InnoDB ".$charset_collate." AUTO_INCREMENT=1";
	$wpdb->query($queryInsertHtml);
	$tblcolums = $wpdb->get_col("SHOW COLUMNS FROM  ".$wpdb->prefix."xyz_ihs_short_code");
    if(!(in_array("insertionMethod", $tblcolums)))
	$wpdb->query("ALTER TABLE ".$wpdb->prefix."xyz_ihs_short_code ADD insertionMethod int NOT NULL default 2");
    if(!(in_array("insertionLocation", $tblcolums)))
	$wpdb->query("ALTER TABLE ".$wpdb->prefix."xyz_ihs_short_code ADD insertionLocation int NOT NULL default 0");
	if(!(in_array("insertionLocationType", $tblcolums)))
	$wpdb->query("ALTER TABLE ".$wpdb->prefix."xyz_ihs_short_code ADD insertionLocationType int NOT NULL default 0");
	if(!(in_array("description", $tblcolums)))
	$wpdb->query("ALTER TABLE ".$wpdb->prefix."xyz_ihs_short_code ADD description TEXT NULL ");
}

register_activation_hook( XYZ_INSERT_HTML_PLUGIN_FILE ,'xyz_ihs_network_install');
