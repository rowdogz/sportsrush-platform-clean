<?php /* Template Name: Left Sidebar Template */ ?>
 
<?php get_header(); ?>
 
<div id="primary" class="content-area ">
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
                    <div  class="col-lg-4 col-md-5 col-sm-12 blog_lr_column">
                        <?php if ( is_active_sidebar( 'blog-sidebar' ) ) : ?>
                            <ul id="sidebar">
                                <?php dynamic_sidebar( 'blog-sidebar' ); ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-8 col-md-7 col-sm-12 content_column">
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

                </div>
        </div>
    </div>
 
    </main><!-- .site-main -->
 	
 	
 
</div><!-- .content-area -->

<?php get_footer(); ?>