<?php
/**
 * Sports Lite About Theme
 *
 * @package Sports Lite
 */

//about theme info
add_action( 'admin_menu', 'sports_lite_abouttheme' );
function sports_lite_abouttheme() {    	
	add_theme_page( __('About Theme Info', 'sports-lite'), __('About Theme Info', 'sports-lite'), 'edit_theme_options', 'sports_lite_guide', 'sports_lite_mostrar_guide');   
} 

//Info of the theme
function sports_lite_mostrar_guide() { 	
?>
<div class="wrap-GT">
	<div class="gt-left">
   		<div class="heading-gt">
		 <h3><?php esc_html_e('About Theme Info', 'sports-lite'); ?></h3>
		</div>
       <p><?php esc_html_e('Sports Lite is a dynamic and colorful, creative and responsive, lightweight and flexible, sleek and smooth, powerful and engaging sports academy WordPress theme for sports professionals. It is a perfect platform to create a elegant website for sports club, sports blogs, sports magazines and all similar sports related websites. ', 'sports-lite'); ?></p>
<div class="heading-gt"> <?php esc_html_e('Theme Features', 'sports-lite'); ?></div>
 

<div class="col-2">
  <h4><?php esc_html_e('Theme Customizer', 'sports-lite'); ?></h4>
  <div class="description"><?php esc_html_e('The built-in customizer panel quickly change aspects of the design and display changes live before saving them.', 'sports-lite'); ?></div>
</div>

<div class="col-2">
  <h4><?php esc_html_e('Responsive Ready', 'sports-lite'); ?></h4>
  <div class="description"><?php esc_html_e('The themes layout will automatically adjust and fit on any screen resolution and looks great on any device. Fully optimized for iPhone and iPad.', 'sports-lite'); ?></div>
</div>

<div class="col-2">
<h4><?php esc_html_e('Cross Browser Compatible', 'sports-lite'); ?></h4>
<div class="description"><?php esc_html_e('Our themes are tested in all mordern web browsers and compatible with the latest version including Chrome,Firefox, Safari, Opera, IE11 and above.', 'sports-lite'); ?></div>
</div>

<div class="col-2">
<h4><?php esc_html_e('E-commerce', 'sports-lite'); ?></h4>
<div class="description"><?php esc_html_e('Fully compatible with WooCommerce plugin. Just install the plugin and turn your site into a full featured online shop and start selling products.', 'sports-lite'); ?></div>
</div>
<hr />  
</div><!-- .gt-left -->
	
<div class="gt-right">    
     <a href="<?php echo esc_url('http://www.gracethemesdemo.com/documentation/sporting/#homepage-lite'); ?>" target="_blank"><?php esc_html_e('Documentation', 'sports-lite'); ?></a>    
</div><!-- .gt-right-->
<div class="clear"></div>
</div><!-- .wrap-GT -->
<?php } ?>