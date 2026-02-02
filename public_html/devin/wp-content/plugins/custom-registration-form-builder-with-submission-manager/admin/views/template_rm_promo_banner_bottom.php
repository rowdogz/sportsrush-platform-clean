<?php
if (!defined('WPINC')) {
    die('Closed');
}
if(isset($rm_promo_banner_title))
    $title = $rm_promo_banner_title;
else
    $title = __('Upgrade and expand the power of','custom-registration-form-builder-with-submission-manager');

$dismiss_banner = get_option('rm_dismiss_customize_banner', 0);

if(defined('REGMAGIC_ADDON') && $dismiss_banner == 0) { ?>
<div class="rmagic rm-customize-banner-main rm-hide-version-number">
<div class="pg-customize-banner-row rm-box-row">
    <div class="rm-box-col-12">
        <div class="rm-customize-banner-wrap rm-d-flex rm-justify-content-between rm-box-center rm-p-3 rm-box-w-100 rm-white-bg rm-mb-3">
            <div class="rm-position-absolute rm-banner-close-icon rm-cursor">&#x2715</div> 
            <div class="rm-customize-banner-logo"><img width="128" src="<?php echo esc_url(RM_IMG_URL).'rm-logo.png'?>"></div>
            <div class="rm-banner-pitch-content-wrap rm-lh-normal">
                <div class="rm-banner-pitch-head rm-fs-2 rm-fw-bold">
                    <?php esc_html_e('Customize RegistrationMagic', 'custom-registration-form-builder-with-submission-manager'); ?>
                </div>
                <div class="rm-banner-pitch-content rm-fs-5 rm-text-muted">
                    <?php esc_html_e('Have our team build the exact feature that you need.', 'custom-registration-form-builder-with-submission-manager'); ?>                                            
                </div>
            </div>

            <div class="rm-banner-btn-wrap">
                <a target="_blank" href="https://registrationmagic.com/customizations/" class=""><button class="button button-primary rm-customize-banner-btn"><?php esc_html_e('Get Help Now', 'custom-registration-form-builder-with-submission-manager'); ?></button></a>
            </div>


        </div>
    </div>
</div>
</div>
<style>
.rm-customize-banner-main{
    width: 100%;
    float: left;
    clear: left
}
    
.rm-customize-banner-wrap {
    width: 100%;
    max-width: 840px;
    margin: 0px auto;
    border: 1px solid #dcdada;
    border-radius: 3px;
    box-shadow: 1px 1px 3px 2px rgb(215 215 215 / 26%);
    background-color: #fff;
    justify-content: space-between;
    padding: 1rem!important;
    margin-top: 30px;
    gap: 10px;
    position: relative;
    
}

.rm-banner-pitch-head {
    font-size: 2rem!important;
    font-weight: 700;
    line-height: normal!important;
}

.rm-banner-pitch-content{
    opacity: 1;
    font-size: 1.20rem !important;
    color: #6c757d !important;
    padding-top: 4px;
}

.rm-banner-btn-wrap button.rm-customize-banner-btn {
    vertical-align: top;
    transition: .2s;
    padding: 4px 20px;
    font-size: 15px;
}

.rm-banner-close-icon {
    top: 2px;
    right: 9px;
}

.registrationmagic_page_rm_dashboard_widget_dashboard .rmagic.rm-customize-banner-main,
.admin_page_rm_form_sett_manage .rmagic.rm-customize-banner-main,
.registrationmagic_page_rm_submission_manage .rmagic.rm-customize-banner-main,
.toplevel_page_rm_form_manage .rmagic.rm-customize-banner-main,
.admin_page_rm_licensing .rmagic.rm-customize-banner-main,
.admin_page_rm_licensing .rmagic.rmagic-premium-banner,
.registrationmagic_page_rm_user_manage .rmagic.rm-customize-banner-main,
.registrationmagic_page_rm_user_manage .rmagic.rmagic-premium-banner{
    margin: 0px auto;
    float: none;
    display: flex;
    justify-content: center;
}

 .rmagic.rm-customize-banner-main ~ .wrap .rm-footer-promotion{
        max-width: 1120px;
        margin: 5px 10% 0 5%;
    }
    
.registrationmagic_page_rm_submission_manage .rmagic.rm-customize-banner-main ~ .wrap .rm-footer-promotion,
 .registrationmagic_page_rm_user_manage .rmagic.rmagic.rm-customize-banner-main ~ .wrap .rm-footer-promotion,
 .registrationmagic_page_rm_support_forum .rmagic.rmagic.rm-customize-banner-main ~ .wrap .rm-footer-promotion,
 .registrationmagic_page_rm_support_premium_page .rmagic.rmagic.rm-customize-banner-main ~ .wrap .rm-footer-promotion,
 .admin_page_rm_form_sett_manage .rmagic.rmagic.rm-customize-banner-main ~ .wrap .rm-footer-promotion,
 .admin_page_rm_licensing .rmagic.rm-customize-banner-main ~ .wrap .rm-footer-promotion,
.toplevel_page_rm_form_manage .rmagic.rm-customize-banner-main ~ .wrap .rm-footer-promotion{
        max-width: 100%;
        margin:0px;
    }
    
    .registrationmagic_page_rm_ex_chronos_manage_tasks .wrap .rm-footer-promotion{
        max-width: 1120px;
        margin: 5px 10% 0 5%;
    }

</style>
<pre class="rm-pre-wrapper-for-script-tags">
    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery("div.rm-banner-close-icon").click(function(){
                var dismiss_data = {
                    'action': 'rm_dismiss_customize_banner',
                    'rm_sec_nonce': '<?php echo wp_create_nonce('rm_ajax_secure'); ?>'
                };
                
                jQuery.post(ajaxurl, dismiss_data, function(response) {
                    jQuery('div.rm-customize-banner-main').hide();
                });
            });
        });
    </script>
</pre>
<?php } ?>