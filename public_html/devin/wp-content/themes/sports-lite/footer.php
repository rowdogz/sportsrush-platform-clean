<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package Sports Lite
 */
 
?>
<div class="site-footer">         
      <div class="container fixfooter">    
          <?php if ( is_active_sidebar( 'footer-widget-1' ) ) : ?>
                <div class="footer-widget-1">  
                    <?php dynamic_sidebar( 'footer-widget-1' ); ?>
                </div>
           <?php endif; ?>          
          <?php if ( is_active_sidebar( 'footer-widget-2' ) ) : ?>
                <div class="footer-widget-2">  
                    <?php dynamic_sidebar( 'footer-widget-2' ); ?>
                </div>
           <?php endif; ?>           
           <?php if ( is_active_sidebar( 'footer-widget-3' ) ) : ?>
                <div class="footer-widget-3">  
                    <?php dynamic_sidebar( 'footer-widget-3' ); ?>
                </div>
           <?php endif; ?>           
           <div class="clear"></div>      
       </div><!--.fixfooter-->      
        <div class="copyrigh-wrapper"> 
            <div class="container">               
                <div class="copy_left">
				   <?php bloginfo('name'); ?> - <?php esc_html_e('Theme by Grace Themes','sports-lite'); ?>  
                </div>
                <div class="copy_right"><?php wp_nav_menu( array( 'theme_location' => 'footer') ); ?></div>
                <div class="clear"></div>                                
             </div><!--end .container-->             
        </div><!--end .copyrigh-wrapper-->                              
     </div><!--end #site-footer-->
</div><!--#end site-layout-type-->
<?php wp_footer(); ?>
</body>
</html>