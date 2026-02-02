<?php
/**
 * Header Default
 * 
 * slug: soccer-club/header-default
 * title: Header Default
 * categories: soccer-club
 */

return array(
    'title'      =>__( 'Header Default', 'soccer-club' ),
    'categories' => array( 'soccer-club' ),
    'content'    => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"0","right":"0"}}},"backgroundColor":"accent","className":"top-table-header","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group top-table-header has-accent-background-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:0;padding-bottom:var(--wp--preset--spacing--40);padding-left:0"><!-- wp:columns {"verticalAlignment":"center","textColor":"background","className":"wow fadeInDown"} -->
<div class="wp-block-columns are-vertically-aligned-center wow fadeInDown has-background-color has-text-color"><!-- wp:column {"verticalAlignment":"center","width":"25%","className":"header-phone"} -->
<div class="wp-block-column is-vertically-aligned-center header-phone" style="flex-basis:25%"><!-- wp:paragraph {"fontSize":"upper-heading","fontFamily":"abel"} -->
<p class="has-abel-font-family has-upper-heading-font-size"><span class="dashicons dashicons-phone"></span>  +123 456 7890</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"25%","className":"header-mail"} -->
<div class="wp-block-column is-vertically-aligned-center header-mail" style="flex-basis:25%"><!-- wp:paragraph {"align":"left","fontSize":"upper-heading","fontFamily":"abel"} -->
<p class="has-text-align-left has-abel-font-family has-upper-heading-font-size"><span class="dashicons dashicons-email-alt"></span>    '. esc_html('support@example.com','soccer-club') .'</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","className":"header-social"} -->
<div class="wp-block-column is-vertically-aligned-center header-social" style="flex-basis:50%"><!-- wp:social-links {"iconColor":"background","iconColorValue":"#fff","className":"is-style-logos-only","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"}} -->
<ul class="wp-block-social-links has-icon-color is-style-logos-only"><!-- wp:social-link {"url":"#","service":"twitter"} /-->

<!-- wp:social-link {"url":"#","service":"facebook"} /-->

<!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"youtube"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:cover {"url":"'.esc_url(get_template_directory_uri()) .'/assets/images/slider-image.png","id":22,"dimRatio":0,"minHeight":700,"className":"alignfull is-light wp-block-group"} -->
<div class="wp-block-cover alignfull is-light wp-block-group" style="min-height:700px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background wp-image-22" alt="" src="'.esc_url(get_template_directory_uri()) .'/assets/images/slider-image.png" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"tagName":"main","align":"full","className":"wp-block-group alignfull","layout":{"type":"constrained","contentSize":"80%"}} -->
<main class="wp-block-group alignfull"><!-- wp:columns {"verticalAlignment":"center","align":"full","className":"slider-banner"} -->
<div class="wp-block-columns alignfull are-vertically-aligned-center slider-banner"><!-- wp:column {"width":"30%"} -->
<div class="wp-block-column" style="flex-basis:30%"></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"40%","style":{"spacing":{"padding":{"left":"var:preset|spacing|80"}}},"className":"header-text"} -->
<div class="wp-block-column is-vertically-aligned-center header-text" style="padding-left:var(--wp--preset--spacing--80);flex-basis:40%"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"60px","fontStyle":"normal","fontWeight":"700","letterSpacing":"1px"}},"textColor":"background","fontFamily":"teko"} -->
<h2 class="wp-block-heading has-text-align-center has-background-color has-text-color has-teko-font-family" style="font-size:60px;font-style:normal;font-weight:700;letter-spacing:1px"><strong>'. esc_html('We Have Best Sport','soccer-club') .'<br>'. esc_html('WP Theme','soccer-club') .'</strong></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"background","fontSize":"upper-heading","fontFamily":"abel"} -->
<p class="has-text-align-center has-background-color has-text-color has-abel-font-family has-upper-heading-font-size">'. esc_html('Online educational platform for digital skills where students aged between 6-17 makes their dreams come true by designing real projects','soccer-club') .'</p>
<!-- /wp:paragraph -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center","textColor":"background","style":{"typography":{"letterSpacing":"1.5px"},"border":{"radius":"5px","width":"0px","style":"none"}},"className":"is-style-outline","fontSize":"upper-heading","fontFamily":"teko"} -->
<div class="wp-block-button has-custom-font-size is-style-outline has-teko-font-family has-upper-heading-font-size" style="letter-spacing:1.5px"><a class="wp-block-button__link has-background-color has-text-color has-text-align-center wp-element-button" href="#" style="border-style:none;border-width:0px;border-radius:5px"><strong>'. esc_html('Know More','soccer-club') .'</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"30%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%"></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></main>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"className":"mainheader ","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-group mainheader"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"radius":"30px"}},"className":"menu-table-block wow fadeInUp"} -->
<div class="wp-block-columns are-vertically-aligned-center menu-table-block wow fadeInUp" style="border-radius:30px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--50)"><!-- wp:column {"verticalAlignment":"center","width":"37.5%","className":"right-nav"} -->
<div class="wp-block-column is-vertically-aligned-center right-nav" style="flex-basis:37.5%"><!-- wp:navigation {"className":"is-head-menu","layout":{"type":"flex","justifyContent":"left"},"fontFamily":"teko"} -->
<!-- wp:navigation-link {"label":"Home","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"About","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Sport","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Shop","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Get Pro","url":"https://www.wpradiant.net/products/soccer-club-wordpress-theme","kind":"custom","className":"getpro"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"25%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:25%"><!-- wp:site-title {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"700","textTransform":"uppercase"}},"fontSize":"large","fontFamily":"teko"} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"37.5%","className":"left-nav"} -->
<div class="wp-block-column is-vertically-aligned-center left-nav" style="flex-basis:37.5%"><!-- wp:navigation {"className":"is-head-menu","layout":{"type":"flex","justifyContent":"right"},"fontFamily":"teko"} -->
<!-- wp:navigation-link {"label":"Team","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Testimonials","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Blog","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Contact Us","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
);