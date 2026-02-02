<?php
/**
 * The Template for displaying all single posts.
 *
 * @package Sports Lite
 */

get_header(); ?>

<div class="container">
     <div id="sitetabnavi">
        <div class="innerpage_content_layout <?php if( esc_attr( get_theme_mod( 'sports_lite_hidesidebar_singlepost' )) ) { ?>fullwidth<?php } ?>">            
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php get_template_part( 'content', 'single' ); ?>
                    <?php the_post_navigation(); ?>
                    <div class="clear"></div>
                    <?php
                    // If comments are open or we have at least one comment, load up the comment template
                    if ( comments_open() || '0' != get_comments_number() )
                    	comments_template();
                    ?>
                <?php endwhile; // end of the loop. ?>                  
         </div>  <!-- .innerpage_content_layout-->        
          <?php if( esc_attr(get_theme_mod( 'sports_lite_hidesidebar_singlepost' )) == '') { ?> 
          	  <?php get_sidebar();?>
          <?php } ?>       
        <div class="clear"></div>
    </div><!-- #sitetabnavi -->
</div><!-- container -->	
<?php get_footer(); ?>