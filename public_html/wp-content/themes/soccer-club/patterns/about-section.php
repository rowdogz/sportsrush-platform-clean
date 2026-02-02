<?php
/**
 * About Us Section
 * 
 * slug: soccer-club/about-section
 * title: About Section
 * categories: soccer-club
 */

return array(
    'title'      =>__( 'About Section', 'soccer-club' ),
    'categories' => array( 'soccer-club' ),
    'content'    => '<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"className":"product-section","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group product-section"><!-- wp:columns {"verticalAlignment":"center","className":"wow fadeInUp"} -->
<div class="wp-block-columns are-vertically-aligned-center wow fadeInUp"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"align":"center","id":51,"scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image aligncenter size-full"><img src="'.esc_url(get_template_directory_uri()) .'/assets/images/aboutus.png" alt="" class="wp-image-51" style="object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"textColor":"black","fontSize":"large","fontFamily":"teko"} -->
<h2 class="wp-block-heading has-text-align-center has-black-color has-text-color has-teko-font-family has-large-font-size" style="font-style:normal;font-weight:700">'. esc_html__('About Us','soccer-club') .'</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#84849a"}},"fontSize":"upper-heading","fontFamily":"abel"} -->
<p class="has-text-align-center has-text-color has-abel-font-family has-upper-heading-font-size" style="color:#84849a">'. esc_html__('There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised','soccer-club') .'</p>
<!-- /wp:paragraph -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"style":{"color":{"background":"#f0f0f3"},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"className":"column12-block"} -->
<div class="wp-block-column column12-block has-background" style="background-color:#f0f0f3;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:image {"align":"center","id":67,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image aligncenter size-full"><img src="'.esc_url(get_template_directory_uri()) .'/assets/images/icon1.png" alt="" class="wp-image-67"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","fontFamily":"teko"} -->
<h2 class="wp-block-heading has-text-align-center has-teko-font-family">'. esc_html__('Award','soccer-club') .'</h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"color":{"background":"#f0f0f3"},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"className":"column12-block"} -->
<div class="wp-block-column column12-block has-background" style="background-color:#f0f0f3;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:image {"align":"center","id":72,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image aligncenter size-full"><img src="'.esc_url(get_template_directory_uri()) .'/assets/images/icon2.png" alt="" class="wp-image-72"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","fontFamily":"teko"} -->
<h2 class="wp-block-heading has-text-align-center has-teko-font-family">'. esc_html__('Goals','soccer-club') .'</h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"color":{"background":"#f0f0f3"},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"className":"column12-block"} -->
<div class="wp-block-column column12-block has-background" style="background-color:#f0f0f3;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:image {"align":"center","id":73,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image aligncenter size-full"><img src="'.esc_url(get_template_directory_uri()) .'/assets/images/icon3.png" alt="" class="wp-image-73"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","fontFamily":"teko"} -->
<h2 class="wp-block-heading has-text-align-center has-teko-font-family">'. esc_html__('Best Player','soccer-club') .'</h2>
<!-- /wp:heading --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group"><!-- wp:columns {"className":"wow fadeInUp"} -->
<div class="wp-block-columns wow fadeInUp"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"backgroundColor":"accent"} -->
<div class="wp-block-column has-accent-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"background","textColor":"primary","style":{"border":{"radius":"30px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"className":"is-style-fill theme-btn","fontFamily":"teko"} -->
<div class="wp-block-button is-style-fill theme-btn has-teko-font-family"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background wp-element-button" style="border-radius:30px;padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><strong>'. esc_html__('Know More About Us','soccer-club') .'</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);