<?php /* Template Name: Format Sidebar Template */ ?>
 
<?php get_header(); ?>
 
<div id="primary" class="content-area">
<div class="banner_breadcrumb">
    <div class="elementor-section elementor-top-section elementor-element elementor-element-28bc4527 animated-fast elementor-section-boxed elementor-section-height-default elementor-section-height-default elementskit-parallax-multi-container animated fadeInUp">
        <div class="elementor-container elementor-column-gap-default">
            <div class="page_title_sidebar"></div>
            <div class="page_title_sidebar_breadcrumb"></div>
        </div>  
    </div>
    </div>
    <main id="main" class="site-main" role="main">
        <div class="elementor-section elementor-top-section elementor-element elementor-element-28bc4527 animated-fast elementor-section-boxed elementor-section-height-default elementor-section-height-default elementskit-parallax-multi-container animated fadeInUp">
            <div class="elementor-container elementor-column-gap-default">
                <div class="elementor-row">

                    <div class="col-sm-8 content_column">
						
                        <?php
                        // Start the loop.
                        while ( have_posts() ) : the_post();
                 
                            // Include the page content template.
                            //get_template_part( 'template-parts/content', 'page' );
                            the_content();
                            // End of the loop.
                        endwhile;
                        ?>
                    </div>

                    <div  class="col-sm-4  left_column format_right">
                        <!-- <div class="home-right"> -->
                            <?php if ( is_active_sidebar( 'format-sidebar' ) ) : ?>
                                <ul id="sidebar">
                                    <?php dynamic_sidebar( 'format-sidebar' ); ?>
                                </ul>
                            <?php endif; ?>
                       <!--  </div> -->
                    </div>

                </div>
        </div>
    </div>
 
    </main><!-- .site-main -->
 	
 	
 
</div><!-- .content-area -->

<?php get_footer(); ?>