<?php
/**
 * The template for displaying 404 pages (Not Found).
 *
 * @package Sports Lite
 */

get_header(); ?>

<div class="container">
    <div id="sitetabnavi">
        <div class="innerpage_content_layout">
            <header class="page-header">
                <h1 class="entry-title"><?php esc_html_e( '404 Not Found', 'sports-lite' ); ?></h1>                
            </header><!-- .page-header -->
            <div class="page-content">
                <p><?php esc_html_e( 'Looks like you have taken a wrong turn....Dont worry... it happens to the best of us.', 'sports-lite' ); ?></p>  
            </div><!-- .page-content -->
        </div><!-- innerpage_content_layout-->   
        <?php get_sidebar();?>       
        <div class="clear"></div>
    </div>
</div>
<?php get_footer(); ?>