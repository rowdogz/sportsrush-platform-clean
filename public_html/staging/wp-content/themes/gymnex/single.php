<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_One
 * @since 1.0.0
 */

get_header(); ?>
 
<div id="primary" class="content-area">
	<div class="banner_breadcrumb single_blog">
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

                    <div class="col-lg-8 col-md-7 col-sm-12 blog_content_column">
                        <?php
                        // Start the loop.
                        while ( have_posts() ) :
							the_post();

							get_template_part( 'template-parts/content/content-single' );

							if ( is_attachment() ) {
								// Parent post navigation.
								the_post_navigation(
									array(
										/* translators: %s: parent post link. */
										'prev_text' => sprintf( __( '<span class="meta-nav">Published in</span><span class="post-title">%s</span>', 'Gymnex' ), '%title' ),
									)
								);
							}

							// If comments are open or there is at least one comment, load up the comment template.
							if ( comments_open() || get_comments_number() ) {
								comments_template();
							}

							// Previous/next post navigation.
							$Gymnex_next = is_rtl() ? twenty_twenty_one_get_icon_svg( 'ui', 'arrow_left' ) : twenty_twenty_one_get_icon_svg( 'ui', 'arrow_right' );
							$Gymnex_prev = is_rtl() ? twenty_twenty_one_get_icon_svg( 'ui', 'arrow_right' ) : twenty_twenty_one_get_icon_svg( 'ui', 'arrow_left' );

							$Gymnex_post_type      = get_post_type_object( get_post_type() );
							$Gymnex_post_type_name = '';
							if (
								is_object( $Gymnex_post_type ) &&
								property_exists( $Gymnex_post_type, 'labels' ) &&
								is_object( $Gymnex_post_type->labels ) &&
								property_exists( $Gymnex_post_type->labels, 'singular_name' )
							) {
								$Gymnex_post_type_name = $Gymnex_post_type->labels->singular_name;
							}

							/* translators: %s: The post-type singlular name (example: Post, Page etc) */
							$Gymnex_next_label = sprintf( esc_html__( 'Next %s', 'Gymnex' ), $Gymnex_post_type_name );
							/* translators: %s: The post-type singlular name (example: Post, Page etc) */
							$Gymnex_previous_label = sprintf( esc_html__( 'Previous %s', 'Gymnex' ), $Gymnex_post_type_name );

							the_post_navigation(
								array(
									'next_text' => '<p class="meta-nav">' . $Gymnex_next_label . $Gymnex_next . '</p><p class="post-title">%title</p>',
									'prev_text' => '<p class="meta-nav">' . $Gymnex_prev . $Gymnex_previous_label . '</p><p class="post-title">%title</p>',
								)
							);
						endwhile;
                        ?>
                    </div>

                    

                </div>
        </div>
    </div>
 
    </main><!-- .site-main -->
 	
 	
 
</div><!-- .content-area -->

<?php get_footer(); ?>