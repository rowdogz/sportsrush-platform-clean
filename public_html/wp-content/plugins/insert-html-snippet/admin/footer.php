<?php
if ( ! defined( 'ABSPATH' ) )
    exit;
if(get_option('xyz_ihs_premium_version_ads')==1){?>
<div id="xyz-ihs-premium">
      <div class="xyz_ihs_ContentCenter">
            <a target="_blank"
            href="https://xyzscripts.com/wordpress-plugins/xyz-wp-insert-code-snippet/details">🚀 Upgrade to the Fully Featured XYZ WP Insert Code Snippet Premium Plugin for </a>
             <span style="font-size: 28px; color:#fbff00;"> Just 19 USD</span>
      <br/><span style="font-size: 13px;color:#fff;">Access exclusive features like advanced targeting, automatic snippet placements across your site, syntax highlighting for better code management, and easy snippet export/import!</span>
    </div>
				<div class="clear"></div>
	<div class="xyz_ihs_ButtonWrap">
    <div class="xyz_ihs_BuyNowButton">
        <a target="_blank"
            href="https://xyzscripts.com/members/product/purchase/XYZWPICSPRE">🛒 Buy Now
        </a>
    </div>
    <div class="xyz_ihs_CompareButton">
    <h2>
            <a href="<?php echo admin_url('admin.php?page=insert-html-snippet-about');?>">Compare Features</a>
        </h2>
    </div>
		</div>
</div>
<?php
    }
?>
<div style="clear: both;">
</div>
<div class="xyz-ihs-wrap">
<div class="xyz-ihs-footer-contact">
    🚀 <span style="font-style: italic;font-size:larger;">We are available for custom development</span> — 
    <a href="https://xyzscripts.com/support/" target="_blank">
       Get a quote
    </a>
</div></div>
<div class="xyz-ihs-wrap">
    <div class="xyz_ihs_social_media">
        <h3 class="xyz_ihs_inner_head">
            Follow Us
        </h3>
        <a target="_blank" href="http://facebook.com/xyzscripts" class="xyz_ihs_home_fbook"></a>
        <a target="_blank" href="http://twitter.com/xyzscripts" class="xyz_ihs_home_twitt"></a>
        <a target="_blank" href="https://www.instagram.com/xyz_scripts/" class="xyz_ihs_home_insta"></a>
        <a style="margin-right:0px;" target="_blank" href="https://www.linkedin.com/company/xyzscripts" class="xyz_ihs_home_linkedin"></a>
    </div>
    <div class="xyz_ihs_sugession">
        <h3 class="xyz_ihs_inner_head"> Support</h3>
	  
      <p  style="width:100%;"><a target="_blank" href="https://xyzscripts.com/support/" >Contact Us</a></p>
      <p  ><a target="_blank" href="https://wordpress.org/support/plugin/insert-html-snippet/reviews/" >Please Rate Us
       </a></p>
       <a target="_blank" href="https://wordpress.org/support/plugin/insert-html-snippet/reviews/" ><span class="xyz_ihs_new_star"></span></a>
       <p  style="width:100%;"><a target="_blank" href="https://wordpress.org/support/plugin/insert-html-snippet/" >Create a ticket at Wordpress forum</a>
       </p>
    </div>
    <div class="xyz_ihs_new_subscribe">
        <h3 class="xyz_ihs_inner_head">
            Stay tuned for our updates
        </h3>
         <script language="javascript">
            function check_email(emailString)
            {
                var mailPattern = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,})$/;
                var matchArray = emailString.match(mailPattern);
                if (emailString.length == 0)
                    return false;
                if (matchArray == null)    {
                    return false;
                }
                else{
                    return true;
                }
            }
            function verify_lists(form)
            {
                var total=0;
                var checkBox=form['chk[]'];
                if(checkBox.length){
                    for(var i=0;i<checkBox.length;i++){
                        checkBox[i].checked?total++:null;
                    }
                }
                else{
                    checkBox.checked?total++:null;
                }
                if(total>0){
                    return true;
                }
                else{
                    return false;
                }
            }
            function verify_fields()
            {
                if(check_email(document.email_subscription.email.value) == false){
                    alert("Please check whether the email is correct.");
                    document.email_subscription.email.select();
                    return false;
                }
                else if(verify_lists(document.email_subscription)==false){
                    alert("Select atleast one list.");
                }
                else{
                    document.email_subscription.submit();
                }
            }
        </script>
        <?php global $current_user; wp_get_current_user();?>
       <form action="https://xyzscripts.com/newsletter/index.php?page=list/subscribe" method="post" name="email_subscription" id="email_subscription" target="_blank">
            <input type="hidden" name="fieldNameIds" value="1,">
            <input type="hidden" name="redirActive" value="https://xyzscripts.com/subscription/pending/XYZWPIHSFRE">
            <input type="hidden" name="redirPending" value="https://xyzscripts.com/subscription/active/XYZWPIHSFRE">
            <input type="hidden" name="mode" value="1">
            <input class="xyz_ihs_name"  type="text" placeholder="Name" name="field1" value="
<?php
if ($current_user->user_firstname != "" || $current_user->user_lastname != "")
{
    echo esc_html($current_user->user_firstname . " " . $current_user->user_lastname);
}
else if (strcasecmp($current_user->display_name, 'admin')!=0 && strcasecmp($current_user->display_name , "administrator")!=0 )
{
    echo esc_html($current_user->display_name);
}
else if (strcasecmp($current_user->user_login ,"admin")!=0 && strcasecmp($current_user->user_login , "administrator")!=0 )
{
    echo esc_html($current_user->user_login);
}
?>"  >
            <input class="xyz_ihs_email" name="email"
            type="text" placeholder="Email" value="
<?php   echo esc_html($current_user->user_email); ?>" />
            <input id="xyz_ihs_submit_btn" type="submit" value="Subscribe" class="xyz_ihs_submit_btn" name="Submit"  onclick="javascript: if(!verify_fields()) return false; " />
            <input type="hidden" name="listName" value="6,1,"/>
     </form>
    </div>
</div>
<div style="clear: both;">
</div>
<div style="width: 100%">
    <div class="xyz_ihs_our_plugins_new">
        <p class="xyz_ihs_plugin_head">
            Our Plugins :
        </p>
        <a target="_blank"  href="https://wordpress.org/plugins/social-media-auto-publish/"><span>1</span>Social Media Auto Publish</a>
        <a target="_blank"  href="https://wordpress.org/plugins/facebook-auto-publish/"><span>2</span>Facebook Auto Publish</a>
        <a target="_blank"  href="https://wordpress.org/plugins/twitter-auto-publish/"><span>3</span>Twitter Auto Publish</a>
        <a target="_blank"  href="https://wordpress.org/plugins/linkedin-auto-publish/"><span>4</span>LinkedIn Auto Publish</a>
        <a target="_blank"  href="https://wordpress.org/plugins/insert-html-snippet/"><span>5</span>Insert HTML Snippet</a>
        <a target="_blank"  href="https://wordpress.org/plugins/insert-php-code-snippet/"><span>6</span>Insert PHP Code Snippet</a>
        <a target="_blank"  href="https://wordpress.org/plugins/contact-form-manager/"><span>7</span>Contact Form Manager</a>
        <a target="_blank"  href="https://wordpress.org/plugins/newsletter-manager/"><span>8</span>Newsletter Manager</a>
        <a target="_blank"  href="https://wordpress.org/plugins/lightbox-pop/"><span>9</span>Lightbox Pop</a>
        <a target="_blank"  href="https://wordpress.org/plugins/full-screen-popup/"><span>10</span>Full Screen Popup</a>
        <a target="_blank"  href="https://wordpress.org/plugins/popup-dialog-box/"><span>11</span>Popup Dialog Box</a>
        <a target="_blank"  href="https://wordpress.org/plugins/quick-bar/"><span>12</span>Quick Bar</a>
        <a target="_blank"  href="https://wordpress.org/plugins/quick-box-popup/"><span>13</span>Quick Box Popup</a>
        <a target="_blank"  href="https://wordpress.org/plugins/custom-field-manager/"><span>14</span> Custom Field Manager</a>
        <a target="_blank"  href="https://wordpress.org/plugins/wp-filter-posts/"><span>15</span>  WP Filter Posts</a>
        <a target="_blank"  href="https://wordpress.org/plugins/wp-gallery-manager/"><span>16</span>  WP Gallery Manager</a>
    </div>
</div>
<div class="xyz_ihs_poweredBy">
    Powered by
    <a href="http://xyzscripts.com" target="_blank">XYZScripts</a>
</div>
<div style="clear: both;"></div>
<p style="clear: both;"></p>
 
