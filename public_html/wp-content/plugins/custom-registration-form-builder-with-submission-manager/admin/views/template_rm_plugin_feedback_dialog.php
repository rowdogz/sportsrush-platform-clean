<?php 
if (!defined('WPINC')) {
    die('Closed');
}
$rm_admin_email = get_option('admin_email');
$help_link = "https://registrationmagic.com/feedback-support-form/";
$deactivate_reasons = array(

    'other_reasons' => array(
        'title' => ''.__("Other reasons.",'custom-registration-form-builder-with-submission-manager'),
        'input_placeholder' => __("Please share the reason",'custom-registration-form-builder-with-submission-manager'),
        'feedback_icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 194 194" fill-rule="nonzero" stroke-linejoin="round" stroke-miterlimit="2" fill="#272525" xmlns:v="https://vecta.io/nano"><path d="M46.482 75.118a8.26 8.26 0 0 1 8.259-8.257 8.26 8.26 0 0 1 8.257 8.257c0 4.561-3.696 8.254-8.257 8.254s-8.259-3.693-8.259-8.254zm84.275 0c0-4.561 3.693-8.257 8.262-8.257a8.27 8.27 0 0 1 8.262 8.257c0 4.561-3.709 8.254-8.262 8.254-4.569 0-8.262-3.693-8.262-8.254zm62.993 21.77h-6.766c0 24.882-10.077 47.392-26.394 63.709s-38.811 26.394-63.709 26.394-47.404-10.077-63.722-26.394S6.767 121.77 6.765 96.888c.002-24.902 10.082-47.409 26.394-63.728C49.477 16.847 71.983 6.769 96.881 6.767s47.392 10.08 63.709 26.393c16.317 16.319 26.394 38.825 26.394 63.728h6.766c0-53.514-43.364-96.885-96.869-96.886S.001 43.374 0 96.888c.002 53.505 43.372 96.869 96.881 96.869s96.869-43.364 96.869-96.869zM48.612 125.781h96.536a3.38 3.38 0 0 0 3.375-3.375c0-1.863-1.512-3.391-3.375-3.391H48.612c-1.869 0-3.383 1.528-3.383 3.391a3.38 3.38 0 0 0 3.383 3.375z"></path></svg>',
        'show_help_link' => false
	),
);
?>
<script type="text/javascript">
    jQuery(document).ready(function(){
        
        var rmDeactivateLocation;
        // Shows feedback dialog     
        jQuery('#the-list').find( '[data-slug="custom-registration-form-builder-with-submission-manager"] span.deactivate a' ).click(function(event){
            jQuery("#rm-deactivate-feedback-dialog-wrapper, .rm-modal-overlay").show();
            rmDeactivateLocation = jQuery(this).attr('href');
            event.preventDefault();
        });

        // skip and deactivation
        jQuery(document).on('click', '#rm_save_plugin_feedback_direct_deactivation', function() {
            location.href = rmDeactivateLocation;
        });
        
        jQuery("#rm-feedback-btn").click(function(e) {
            e.preventDefault();
            //var selectedVal = jQuery("input[name='rm_feedback_key']:checked").val();
            var message = jQuery('textarea#rm-plugin-feedback-new').val();
            //var addOption = jQuery('#rm-inform-email-new').prop("checked") == true ? 1 : 0;
            var tempDeac = jQuery('#rm-inform-email-new').prop("checked") == true ? 1 : 0;
            var email = jQuery('input[name=rm_user_support_email_new]').val();
            /*
            if(selectedVal === undefined) {
                //location.href= rmDeactivateLocation;
                return;
            }
            */
            var data = {
                'action': 'rm_post_feedback',
                'rm_sec_nonce': '<?php echo wp_create_nonce('rm_ajax_secure'); ?>',
                'feedback': '',
                'add_option': tempDeac,
                'email': email,
                'msg': message
            };
            jQuery(".rm-ajax-loader").show();
            jQuery.post(ajaxurl, data, function (response) {
                jQuery(".rm-ajax-loader").hide();
                location.href= rmDeactivateLocation;  
            });
        });
        
        jQuery("input[name='rm_feedback_key']").change(function(){
                var selectedVal= jQuery(this).val();
                var reasonElement= jQuery("#reason_" + selectedVal);
                jQuery(".rm-deactivate-feedback-dialog-input-wrapper .rminput").hide();
                if(reasonElement!==undefined)
                {
                    reasonElement.show();  
                }
                var helplinkElement= jQuery("#help_link_" + selectedVal);
                if(typeof helplinkElement !== "undefined")
                {
                    helplinkElement.show();  
                } else {
                    helplinkElement.hide();  
                }
        });
        
        jQuery("#rm-feedback-cancel-btn").click(function(){
            jQuery("#rm-deactivate-feedback-dialog-wrapper").hide();
        });
        
        jQuery(".rm-modal-close, .rm-modal-overlay").click(function(){
            jQuery(".rm-modal-view").hide();
        });
});
</script>    
<div class="rmagic rm-hide-version-number">
    <div id="rm-deactivate-feedback-dialog-wrapper"  class="rm-modal-view" style="display:none; float:right">
        <div class="rm-modal-overlay"></div>
        <div  class="rm-modal-wrap rm-deactivate-feedback" >

            <div class="rm-modal-titlebar rm-new-form-popup-header rm-mt-3 rm-ps-3">
                <div class="rm-modal-title  rm-mt-2 rm-fw-normal rm-mb-1 rm-pt-2 rm-pl-3">
                    <div class="rm-fs-6 rm-text-dark rm-pb-2"> <?php esc_html_e("Uninstalling RegistrationMagic?",'custom-registration-form-builder-with-submission-manager') ?></div>
                    <div class="rm-fs-3 rm-text-dark rm-mt-1"><?php esc_html_e("Maybe we can convince you to return!",'custom-registration-form-builder-with-submission-manager') ?></div>
                    <!--<div class="rm-fs-6 rm-text-small rm-pl-3"><?php esc_html_e("Let us know what went wrong and how we can fix it?",'custom-registration-form-builder-with-submission-manager') ?></div>-->
                </div>
                <span class="rm-modal-close material-icons rm-text-dark rm-fs-5">close</span>
            </div>
            <div class="rm-modal-container">
                <form id="rm-deactivate-feedback-dialog-form" method="post">
                    <input type="hidden" name="action" value="rm_deactivate_feedback" />
                    <div class="rm-px-3 rm-settings-checkout-field-manager">
                        <div class="rm-deactivate-feedback-wrap rm-px-3 rm-pb-2">
                            
                            <div class="rm-plugin-deactivation-message rm-mr-3 rm-text-danger" style="display:none"><?php esc_html_e('Please select one option','custom-registration-form-builder-with-submission-manager'); ?></div>
                            
                            <div id="rm-deactivate-feedback-dialog-form-body" class="rm-box-row">
                            <?php foreach ($deactivate_reasons as $reason_key => $reason) : ?>
                                <div class="rm-deactivate-feedback-dialog-input-wrapper rm-mb-2 rm-box-col-6 rm-d-none" >  
                                    <div class="rm-deactive-feedback-box">
                                        <input id="rm-deactivate-feedback-<?php echo esc_attr($reason_key); ?>" class="rm-deactivate-feedback-dialog-input rm-d-none" type="radio" name="rm_feedback_key" value="re" checked/>
                                        <label for="rm-deactivate-feedback-<?php echo esc_attr($reason_key); ?>" class="rm-deactivate-feedback-dialog-label rm-lh-0 rm-border rm-border-dark rm-border-dark rm-rounded rm-p-3 rm-box-w-100 rm-di-flex rm-align-items-center">
                                            <span class="rm-feedback-emoji rm-mr-2 "><?php echo wp_kses($reason['feedback_icon'],RM_Utilities::expanded_allowed_tags()); ?></span>
                                            <?php echo wp_kses_post((string)$reason['title']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                    
                    <div class="rm-box-wrap">       
            <!-- feature_not_available Feedback Form 1 -->
            <div class="rm-box-row rm-feedback-form-feature-box" id="feature_not_available" data-condition="feature_not_available" style="display: none;">
                <div class="rm-box-col-12">
                    <div class="rm-feedback-form rm-feedback-form-input-bg">
                        <div class="rm-p-4">
                            <label class="rm-inline-block rm-pb-2"><?php esc_html_e('Please tell us about the missing feature (optional)','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <textarea id="rm-plugin-feedback" name="rm-plugin-feedback" class="rm-plugin-feedback-textarea rm-box-w-100" rows="2" cols="50"></textarea>
                            <div class="rm-pt-1 rm-plugin-feedback-email-check rm-d-flex rm-align-items-center rm-mt-1">
                                <input type="checkbox" class="rm-my-0 rm-mr-2" id="rm-inform-email" name="rm-inform-email" value="1">
                                <label for="rm-inform-email" class="rm-text-small"><?php esc_html_e('Create a support ticket to request addition of this feature.','custom-registration-form-builder-with-submission-manager'); ?></label>
                            </div>
                            <div class="rm-feedback-user-email rm-mt-2" style="display: none">
                                <input type="email" name="rm_user_support_email" value="<?php echo sanitize_email( $rm_admin_email ); ?>" placeholder="Your Email">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- feature_not_available Feedback Form 1 End -->
            
            <!-- feature_not_working Feedback Form 2 -->
            
            <div class="rm-box-row rm-feedback-form-feature-box" id="feature_not_working" data-condition="feature_not_working" style="display: none;">
                <div class="rm-box-col-12">
                    <div class="rm-feedback-form">
                        <div class="rm-p-4">
                            <label class="rm-inline-block rm-pb-2"><?php esc_html_e('Please tell us about the broken feature (optional)','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <textarea id="rm-plugin-feedback" name="rm-plugin-feedback" class="rm-plugin-feedback-textarea rm-box-w-100" rows="2" cols="50"></textarea>
                            <div class="rm-pt-1 rm-plugin-feedback-email-check rm-d-flex rm-align-items-center rm-mt-1">
                                <input type="checkbox" class="rm-my-0 rm-mr-2" id="rm-inform-email" name="rm-inform-email" value="1">
                            <label for="rm-inform-email" class="rm-text-small"><?php esc_html_e('Also create support ticket.','custom-registration-form-builder-with-submission-manager'); ?></label>
                            </div>
                            <div class="rm-feedback-user-email rm-mt-2" style="display: none">
                                <input type="email" name="rm_user_support_email" value="<?php echo sanitize_email( $rm_admin_email ); ?>" placeholder="Your Email">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- feature_not_working Feedback Form 2 End --->
            
            <!-- plugin_difficult_to_use Feedback Form 3 -->
            
            <div class="rm-box-row rm-feedback-form-feature-box" id="plugin_difficult_to_use" data-condition="plugin_difficult_to_use" style="display: none;">
                <div class="rm-box-col-12">
                    <div class="rm-feedback-form rm-feedback-form-input-bg">
                        <div class="rm-p-4">
                            <label class="rm-inline-block rm-pb-2"><?php esc_html_e('Please tell us which part of the plugin was confusing (optional)','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <textarea id="rm-plugin-feedback" name="rm-plugin-feedback" class="rm-plugin-feedback-textarea rm-box-w-100" rows="2" cols="50"></textarea>
                        
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- plugin_difficult_to_use Feedback Form 3 End --->
            
            <!-- plugin_broke_site Feedback Form 4 -->
            
            <div class="rm-box-row rm-feedback-form-feature-box" id="plugin_broke_site" data-condition="plugin_broke_site" style="display: none;">
                <div class="rm-box-col-12">
                    <div class="rm-feedback-form rm-feedback-form-input-bg">
                        <div class="rm-p-4">
                            <label class="rm-inline-block rm-pb-2"><?php esc_html_e('Please paste any errors or warnings you see (optional)','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <textarea id="rm-plugin-feedback" name="rm-plugin-feedback" class="rm-plugin-feedback-textarea rm-box-w-100" rows="2" cols="50"></textarea>
                            <div class="rm-pt-1 rm-plugin-feedback-email-check rm-d-flex rm-align-items-center rm-mt-1">
                                <input type="checkbox" class="rm-my-0 rm-mr-2" id="rm-inform-email" name="rm-inform-email" value="1">
                            <label for="rm-inform-email" class="rm-text-small"><?php esc_html_e('Also create support ticket','custom-registration-form-builder-with-submission-manager'); ?></label>
                            </div>
                            <div class="rm-feedback-user-email rm-mt-2" style="display: none">
                                <input type="email" name="rm_user_support_email" value="<?php echo sanitize_email( $rm_admin_email ); ?>" placeholder="Your Email">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- plugin_broke_site Feedback Form 4 End --->
            
            <!-- temporary_deactivation Feedback Form 5 -->
            
            <div class="rm-box-row rm-feedback-form-feature-box" id="temporary_deactivation" data-condition="temporary_deactivation" style="display: none;">
    
            </div>
            
            <!-- temporary_deactivationg Feedback Form 5 End --->
            
            <!-- plugin_has_design_issue Feedback Form 6 -->
            
            <div class="rm-box-row rm-feedback-form-feature-box" id="plugin_has_design_issue" data-condition="plugin_has_design_issue" style="display: none;">
                <div class="rm-box-col-12">
                    <div class="rm-feedback-form rm-feedback-form-input-bg">
                        <div class="rm-p-4">
                            <label class="rm-inline-block rm-pb-2"><?php esc_html_e('Please tell us which page had design issues (optional)','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <textarea id="rm-plugin-feedback" name="rm-plugin-feedback" class="rm-plugin-feedback-textarea rm-box-w-100" rows="2" cols="50"></textarea>
                            <div class="rm-pt-1 rm-plugin-feedback-email-check rm-d-flex rm-align-items-center rm-mt-1">
                                <input type="checkbox" class="rm-my-0 rm-mr-2" id="rm-inform-email" name="rm-inform-email" value="1">
                                <label for="rm-inform-email" class="rm-text-small"><?php esc_html_e('Also create support ticket','custom-registration-form-builder-with-submission-manager'); ?></label>
                            </div>
                            <div class="rm-feedback-user-email rm-mt-2" style="display: none">
                                <input type="email" name="rm_user_support_email" value="<?php echo sanitize_email( $rm_admin_email ); ?>" placeholder="Your Email">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- plugin_has_design_issue Feedback Form 6 End --->
            

            <!-- plugin_missing_documentation Feedback Form 7 -->

            <div class="rm-box-row rm-feedback-form-feature-box" id="plugin_missing_documentation" data-condition="plugin_missing_documentation" style="display: none;">
                <div class="rm-box-col-12">
                    <div class="rm-feedback-form rm-feedback-form-input-bg">
                        <div class="rm-p-4">
                            <label class="rm-inline-block rm-pb-2"><?php esc_html_e('Please tell us which feature lacked documentation (optional)','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <textarea id="rm-plugin-feedback" name="rm-plugin-feedback" class="rm-plugin-feedback-textarea rm-box-w-100" rows="2" cols="50"></textarea>
                            <div class="rm-pt-1 rm-plugin-feedback-email-check rm-d-flex rm-align-items-center rm-mt-1">
                                    <input type="checkbox" class="rm-my-0 rm-mr-2" id="rm-inform-email" name="rm-inform-email" value="1" >
                                    <label for="rm-inform-email" class="rm-text-small"><?php esc_html_e('Also create support ticket','custom-registration-form-builder-with-submission-manager'); ?></label>
                            </div>
                            <div class="rm-feedback-user-email rm-mt-2" style="display: none">
                                <input type="email" name="rm_user_support_email" value="<?php echo sanitize_email( $rm_admin_email ); ?>" placeholder="Your Email">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- plugin_missing_documentation Feedback Form 7 End --->
            
            
            <!-- other_reasons Feedback Form 8 -->

            <div class="rm-box-row rm-feedback-form-feature-box" id="other_reasons" data-condition="other_reasons">
                <div class="rm-box-col-12">
                    <div class="rm-feedback-form">
                        <div class="rm-px-4 rm-mb-4">
                            <label class="rm-inline-block rm-pb-2 rm-fs-6 rm-text-small"><?php esc_html_e('Let us know what went wrong and how we can fix it?','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <textarea id="rm-plugin-feedback-new" name="rm-plugin-feedback-new" class="rm-plugin-feedback-textarea rm-box-w-100" rows="3" cols="50"></textarea>
                             <div class="rm-pt-1 rm-plugin-feedback-email-check rm-d-flex rm-align-items-center rm-mt-1">
                                <input type="checkbox" class="rm-my-0 rm-mr-2" id="rm-inform-email-new" name="rm-inform-email-new" value="1">
                                <label for="rm-inform-email-new" class="rm-text-small" ><?php esc_html_e('This is a temporary deactivation','custom-registration-form-builder-with-submission-manager'); ?></label>
                            </div>
                            <div class="rm-feedback-user-email rm-mt-2" style="display: none">
                                <input type="email" name="rm_user_support_email_new" value="<?php echo sanitize_email($rm_admin_email); ?>" placeholder="Your Email">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- other_reasons Feedback Form 8 End --->
                    
            </div>       
                    <div class="rm-ajax-loader" style="display:none">
                        <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
                        <span class="sr-only"><?php esc_html_e("Loading...",'custom-registration-form-builder-with-submission-manager') ?></span>
                    </div>
                   

                    <div class="rm-box-w-100 rm-d-flex rm-justify-content-between rm-p-2 rm-py-3 rm-px-4 rm-border-top rm-align-items-center">
                    <a href="javascript:void(0);" class="rm-mr-3 button rm-feedback-skip-button" id="rm_save_plugin_feedback_direct_deactivation" title="<?php esc_attr_e("Skip & Deactivate",'custom-registration-form-builder-with-submission-manager'); ?>"><?php esc_html_e("Skip & Deactivate",'custom-registration-form-builder-with-submission-manager'); ?></a>
                    <!--<input type="button" id="rm-feedback-cancel-btn" class="rm-feedback-cancel-btn" value="← &nbsp; Cancel"/>-->
                    <!--<input type="submit" class="button button-primary button-large" id="rm-feedback-btn" value="<?php esc_html_e("Submit & Deactivate",'custom-registration-form-builder-with-submission-manager'); ?>"/>-->
                    <button class="button button-primary button-large" type="submit" id="rm-feedback-btn"><?php esc_html_e("Submit & Deactivate",'custom-registration-form-builder-with-submission-manager'); ?></button>
                    
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<style>
/*-- Feedback Form---*/
.rm-deactivate-feedback-dialog-input {
    display: none;
}

.rm-feedback-emoji svg {
    width: 32px;
    fill: #272525;
}

.rm-deactive-feedback-box label{    
    transition: all .5s;
    padding: 0.6rem 1rem!important;
}

.rm-feedback-form-input-bg {
    background-color: #F0F6FC;
}

.rm-plugin-feedback-textarea {
    border:1px solid #A0C6EA;
}

.rm-feedback-form-feature-box input[type=checkbox] {
    background-color: #fff;
    border: 1px solid #a1a7ab;
    box-shadow: none;
    width: 16px;
    height: 16px;
}

.rmagic .rm-feedback-form-feature-box input[type=checkbox]:checked{
    background-color: transparent;
    border-color: transparent !important;
}

.rm-feedback-form-feature-box input[type=checkbox]:checked::before{
    font-family: "Material Icons";
    content: "\e876" !important;
    color: #fff;
    top: 11px;
    margin-top: 4px;
    background-color: #2371b1;
    width: 16px;
    height: 16px;
    margin: 0px;
    border-radius: 4px;
    padding-top: 0px;
    box-sizing: border-box;
    font-size: 15px;
    border: 0px;
    font-weight: 800;
}

.rm-feedback-skip-button {
    background-color: #fff !important;
}

.rm-feedback-user-email input {
    border: 1px solid #A0C6EA;
    width: 100%;
    max-width: 296px;
    font-size: 12px;
}

#rm-deactivate-feedback-dialog-form .rm-border-dark {
    --rm-border-opacity: 1;
    border-color: rgba(var(--rm-border-dark-color),var(--rm-border-opacity))!important;
}

#rm-deactivate-feedback-dialog-form .rm-deactive-feedback-box input[type="radio"]:checked + label{
    background: #FAFCFF;
    border-color: #2271B1 !important;
}

 #rm-deactivate-feedback-dialog-form .rm-deactive-feedback-box input[type="radio"]:checked + label .rm-feedback-emoji svg{
    fill: #2271B1;
}


#rm-deactivate-feedback-dialog-form .rm-deactive-feedback-box label:hover{
    background: #FAFCFF;
    border-color: #2271B1 !important;
}

#rm-deactivate-feedback-dialog-form .rm-deactive-feedback-box label:hover .rm-feedback-emoji svg{
    fill: #2271B1;
}

#rm-deactivate-feedback-dialog-for .rm-plugin-feedback-email-check label{
    color: #7998B4;
}

#rm-deactivate-feedback-dialog-form .rm-feedback-form-feature-box textarea.rm-plugin-feedback-textarea{
    width: 100% !important;
    min-height: 75px !important;
}

#rm-deactivate-feedback-dialog-wrapper.rm-modal-view{
    height: 80%;
}

#rm-deactivate-feedback-dialog-form .rm-plugin-feedback-email-check label {
  
}

</style>
<script>
jQuery( function( $ ) {
    $( document ).on( 'change', 'input[name="rm_feedback_key"]', function() {
        var rm_selectedVal = $(this).val();
        //var rm_reasonElement = $( '#rm_reason_' + rm_selectedVal );
        //jQuery(".rm-feedback-form-feature-box").hide();
        //console.log(rm_selectedVal);
        /*
        $('.rm-feedback-form-feature-box').each(function () {
            var condition = $(this).data('condition');
            
            //console.log(`${rm_selectedVal} and ${condition}`);

            // Check if the condition matches rm_selectedVal
            if (condition == rm_selectedVal) {
                
                //console.log(rm_selectedVal);
            
                // Show the box if the condition is true
                $(this).show();
            } else {
                // Hide the box if the condition is not true
                $(this).hide();
            }
        });*/
    });

/*  
    var $informEmailCheckboxes = $(".rm-plugin-feedback-email-check input");
    var $feedbackUserEmailDivs = $(".rm-feedback-user-email");

    $informEmailCheckboxes.change(function () {
        // Find the index of the checkbox that was changed
        var index = $informEmailCheckboxes.index(this);
        //console.log(this.checked);

        if (this.checked) {
            // Checkbox is checked, so show the corresponding feedbackUserEmailDiv
            $feedbackUserEmailDivs.eq(index).show();
        } else {
            // Checkbox is unchecked, so hide the corresponding feedbackUserEmailDiv
            $feedbackUserEmailDivs.eq(index).hide();
        }
    });
*/
    
    
    
    
    
    $(document).ready(function() {
    $('#rm-feedback-btn').submit(function(e) {
        // Prevent the form from submitting by default
        
        console.log('ssss');
        e.preventDefault();

        // Check if any radio input is checked
        if (!$('input[name="rm_feedback_key"]:checked').length > 0) {
            // If no radio button is checked, show the error message
            $('.rm-plugin-deactivation-message').show();
        } else {
            // If a radio button is checked, hide the error message and submit the form
            $('.rm-plugin-deactivation-message').hide();
            // Here, you might want to proceed with form submission
            // You can do so by either using this.submit() or AJAX to handle form submission
            // For demonstration purposes, let's log a success message
            console.log('Form submitted successfully!');
        }
    });
});



    $( document ).on( 'click', '#rm-feedback-btn', function(e) {
        e.preventDefault();
        let selectedVal = $( 'input[name="rm_feedback_key"]:checked' ).val();

            // Check if any radio input is checked
        if (!$('input[name="rm_feedback_key"]:checked').length > 0) {
            // If no radio button is checked, show the error message
            $('.rm-plugin-deactivation-message').show();
        } else {
            // If a radio button is checked, hide the error message and submit the form
            $('.rm-plugin-deactivation-message').hide();
            // Here, you might want to proceed with form submission
            // You can do so by either using this.submit() or AJAX to handle form submission
            // For demonstration purposes, let's log a success message
            //console.log('Form submitted successfully!');
        }
        
    });

    
});
</script>
