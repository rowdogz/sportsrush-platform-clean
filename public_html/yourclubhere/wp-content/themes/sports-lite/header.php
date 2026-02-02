<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div class="container">
 *
 * @package Sports Lite
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<?php if ( is_singular() && pings_open( get_queried_object() ) ) : ?>
	<link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>">
<?php endif; ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
	if ( function_exists( 'wp_body_open' ) ) {
		wp_body_open();
	} else {
		do_action( 'wp_body_open' );
	}
?>
<a class="skip-link screen-reader-text" href="#sitetabnavi">
<?php esc_html_e( 'Skip to content', 'sports-lite' ); ?>
</a>
<?php
$sports_lite_show_topgetintouch_panel 	   	= esc_attr( get_theme_mod('sports_lite_show_topgetintouch_panel', false) ); 
$sports_lite_show_socialsections  			= esc_attr( get_theme_mod('sports_lite_show_socialsections', false) ); 
$sports_lite_show_homepageslide_panel 	    = esc_attr( get_theme_mod('sports_lite_show_homepageslide_panel', false) );
$sports_lite_show_aboutclubpanel            = esc_attr( get_theme_mod('sports_lite_show_aboutclubpanel', false) ); 
$sports_lite_show_frontpage3bx_sections   	= esc_attr( get_theme_mod('sports_lite_show_frontpage3bx_sections', false) );
?>
<div id="site-layout-type" <?php if( get_theme_mod( 'sports_lite_layoutfixed' ) ) { echo 'class="fixlayout"'; } ?>>
<?php
if ( is_front_page() && !is_home() ) {
	if( !empty($sports_lite_show_homepageslide_panel)) {
	 	$inner_cls = '';
	}
	else {
		$inner_cls = 'siteinner';
	}
}
else {
$inner_cls = 'siteinner';
}
?>

<div class="site-header <?php echo esc_attr($inner_cls); ?> ">          
    <div class="hdr_contactdetails">
      <div class="container">        
         <div class="hdr_leftstyle">  
            <div class="logo">
                   <?php sports_lite_the_custom_logo(); ?>
                    <h1><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo('name'); ?></a></h1>
                    <?php $description = get_bloginfo( 'description', 'display' );
                    if ( $description || is_customize_preview() ) : ?>
                        <p><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
            </div><!-- logo --> 
        </div><!--end .hdr_leftstyle-->        
               
        <?php if( $sports_lite_show_topgetintouch_panel != ''){ ?>         
          <div class="hdr_rightstyle">                
			 <?php 
                $email = get_theme_mod('sports_lite_header_emailid');
                   if( !empty($email) ){ ?>                
                     <div class="hdr_infbx">
                         <i class="fas fa-envelope-open-text"></i>
                         <?php $sports_lite_emailtext = get_theme_mod('sports_lite_emailtext');
                        if( !empty($sports_lite_emailtext) ){ ?>              
                            <?php echo esc_html($sports_lite_emailtext); ?>                   
                        <?php } ?> 
                         <span>
                            <a href="<?php echo esc_url('mailto:'.sanitize_email($email)); ?>"><?php echo sanitize_email($email); ?></a>
                        </span> 
                    </div>            
             <?php } ?> 
         
			   <?php $sports_lite_header_contactno = get_theme_mod('sports_lite_header_contactno');
                   if( !empty($sports_lite_header_contactno) ){ ?>              
                     <div class="hdr_infbx">
                         <i class="fas fa-phone-volume"></i>                                                  
                          <?php $sports_lite_callustext = get_theme_mod('sports_lite_callustext');
                        if( !empty($sports_lite_callustext) ){ ?>              
                            <?php echo esc_html($sports_lite_callustext); ?>                   
                        <?php } ?>   
                         <span><?php echo esc_html($sports_lite_header_contactno); ?></span>   
                     </div>       
               <?php } ?>
               
               
               <?php $sports_lite_header_officetime = get_theme_mod('sports_lite_header_officetime');
                   if( !empty($sports_lite_header_officetime) ){ ?>              
                     <div class="hdr_infbx">
                         <i class="far fa-clock"></i>                                                 
                          <?php $sports_lite_worktimetext = get_theme_mod('sports_lite_worktimetext');
                        if( !empty($sports_lite_worktimetext) ){ ?>              
                            <?php echo esc_html($sports_lite_worktimetext); ?>                   
                        <?php } ?>   
                         <span><?php echo esc_html($sports_lite_header_officetime); ?></span>   
                     </div>       
               <?php } ?>         
         </div><!--end .hdr_rightstyle-->
      <?php } ?> 
      <div class="clear"></div>
      </div><!-- .container -->    
    </div><!-- .hdr_contactdetails -->  
           
           
  <div class="hdr_sitemenu"> 
   <div class="container">      
     <div class="mainmenu-left-area">      
        <div id="mainnavigator">       
		   <button class="menu-toggle" aria-controls="main-navigation" aria-expanded="false" type="button">
			<span aria-hidden="true"><?php esc_html_e( 'Menu', 'sports-lite' ); ?></span>
			<span class="dashicons" aria-hidden="true"></span>
		   </button>

		  <nav id="main-navigation" class="site-navigation primary-navigation" role="navigation">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container' => 'ul',
				'menu_id' => 'primary',
				'menu_class' => 'primary-menu menu',
			) );
			?>
		  </nav><!-- .site-navigation -->
	    </div><!-- #mainnavigator -->  
     </div><!-- .mainmenu-left-area -->    
        
         <?php if( $sports_lite_show_socialsections != ''){ ?>                
                    <div class="hdr_socialbar">                                                
					   <?php $sports_lite_facebook_link = get_theme_mod('sports_lite_facebook_link');
                        if( !empty($sports_lite_facebook_link) ){ ?>
                        <a class="fab fa-facebook-f" target="_blank" href="<?php echo esc_url($sports_lite_facebook_link); ?>"></a>
                       <?php } ?>
                    
                       <?php $sports_lite_twitter_link = get_theme_mod('sports_lite_twitter_link');
                        if( !empty($sports_lite_twitter_link) ){ ?>
                        <a class="fab fa-twitter" target="_blank" href="<?php echo esc_url($sports_lite_twitter_link); ?>"></a>
                       <?php } ?>
                
                      <?php $sports_lite_linkedin_link = get_theme_mod('sports_lite_linkedin_link');
                        if( !empty($sports_lite_linkedin_link) ){ ?>
                        <a class="fab fa-linkedin" target="_blank" href="<?php echo esc_url($sports_lite_linkedin_link); ?>"></a>
                      <?php } ?> 
                      
                      <?php $sports_lite_instagram_link = get_theme_mod('sports_lite_instagram_link');
                        if( !empty($sports_lite_instagram_link) ){ ?>
                        <a class="fab fa-instagram" target="_blank" href="<?php echo esc_url($sports_lite_instagram_link); ?>"></a>
                      <?php } ?> 
                 </div><!--end .hdr_socialbar--> 
               <?php } ?>   
       <div class="clear"></div>
      </div><!-- .container -->    
   </div><!-- .hdr_sitemenu -->  
</div><!--.site-header -->  
<?php 
if ( is_front_page() && !is_home() ) {
if($sports_lite_show_homepageslide_panel != '') {
	for($i=1; $i<=3; $i++) {
	  if( get_theme_mod('sports_lite_slidepge'.$i,false)) {
		$slider_Arr[] = absint( get_theme_mod('sports_lite_slidepge'.$i,true));
	  }
	}
?> 
<div class="hdr_mainslider">              
<?php if(!empty($slider_Arr)){ ?>
<div id="slider" class="nivoSlider">
<?php 
$i=1;
$slidequery = new WP_Query( array( 'post_type' => 'page', 'post__in' => $slider_Arr, 'orderby' => 'post__in' ) );
while( $slidequery->have_posts() ) : $slidequery->the_post();
$image = wp_get_attachment_url( get_post_thumbnail_id($post->ID)); 
$thumbnail_id = get_post_thumbnail_id( $post->ID );
$alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true); 
?>
<?php if(!empty($image)){ ?>
<img src="<?php echo esc_url( $image ); ?>" title="#slidecaption<?php echo esc_attr( $i ); ?>" alt="<?php echo esc_attr($alt); ?>" />
<?php }else{ ?>
<img src="<?php echo esc_url( get_template_directory_uri() ) ; ?>/images/slides/slider-default.jpg" title="#slidecaption<?php echo esc_attr( $i ); ?>" alt="<?php echo esc_attr($alt); ?>" />
<?php } ?>
<?php $i++; endwhile; ?>
</div>   

<?php 
$j=1;
$slidequery->rewind_posts();
while( $slidequery->have_posts() ) : $slidequery->the_post(); ?>                 
    <div id="slidecaption<?php echo esc_attr( $j ); ?>" class="nivo-html-caption">         
    	<h2><?php the_title(); ?></h2>
    	<?php the_excerpt(); ?>
		<?php
        $sports_lite_slidepgebutton = get_theme_mod('sports_lite_slidepgebutton');
        if( !empty($sports_lite_slidepgebutton) ){ ?>
            <a class="slide_morebtn" href="<?php the_permalink(); ?>"><?php echo esc_html($sports_lite_slidepgebutton); ?></a>
        <?php } ?>                  
    </div>   
<?php $j++; 
endwhile;
wp_reset_postdata(); ?>   
<?php } ?>
 </div><!-- .hdr_mainslider -->    
<?php } } ?>   
        
<?php if ( is_front_page() && ! is_home() ) { ?>
   <?php if( $sports_lite_show_frontpage3bx_sections != ''){ ?> 
   <section id="pageboxes_section">
     <div class="container"> 
       <div class="box-equal-height">     
               <?php 
                for($n=1; $n<=3; $n++) {    
                if( get_theme_mod('sports_lite_fp3pagebx'.$n,false)) {      
                    $queryvar = new WP_Query('page_id='.absint(get_theme_mod('sports_lite_fp3pagebx'.$n,true)) );		
                    while( $queryvar->have_posts() ) : $queryvar->the_post(); ?>     
                    <div class="front_3column <?php if($n % 3 == 0) { echo "last_column"; } ?>">
                       <div class="boxstyling">                                                   
							 <?php if(has_post_thumbnail() ) { ?>
                                <div class="front_imgbx"><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail(); ?></a></div>        
                             <?php } ?>
                             <div class="front_page_contentbx">              	
                                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4> 
                                <?php the_excerpt(); ?> 
                                 <a class="blogmore" href="<?php the_permalink(); ?>">  
                                    <?php esc_html_e('Read More','sports-lite'); ?> 
                                </a>                                   
                             </div> 
                        </div>                                     
                    </div>
                    <?php endwhile;
                    wp_reset_postdata();                                  
                } } ?>                                 
            <div class="clear"></div>        
        </div><!-- .box-equal-height -->
      </div><!-- .container -->
    </section><!-- #pageboxes_section -->
  <?php } ?>

<?php if( $sports_lite_show_aboutclubpanel != ''){ ?>  
    <section id="AboutClub_section">
     <div class="container">                               
		<?php 
        if( get_theme_mod('sports_lite_aboutclubpage',false)) {     
        $queryvar = new WP_Query('page_id='.absint(get_theme_mod('sports_lite_aboutclubpage',true)) );			
            while( $queryvar->have_posts() ) : $queryvar->the_post(); ?>           
              <div class="front_describtionbx">   
                <h3><?php the_title(); ?></h3>   
                <?php the_content();  ?>      
              </div>  
               <div class="front_abtimgstyle">
			    <?php the_post_thumbnail();?>
              </div>                                        
            <?php endwhile;
             wp_reset_postdata(); ?>                                    
            <?php } ?>                                 
      <div class="clear"></div>                       
     </div><!-- .container -->
    </section><!-- #AboutClub_section-->
 <?php } ?>
<?php } ?>