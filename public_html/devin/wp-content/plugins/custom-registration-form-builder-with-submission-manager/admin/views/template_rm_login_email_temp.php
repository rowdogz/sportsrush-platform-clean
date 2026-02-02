<?php
if (!defined('WPINC')) {
    die('Closed');
}
if(defined('REGMAGIC_ADDON')) include_once(RM_ADDON_ADMIN_DIR . 'views/template_rm_login_email_temp.php'); else {
$params= $data->params;
?>
<div class="rmagic">

    <!--Dialogue Box Starts-->
    <div class="rmcontent">
        <div class="rmheader"><?php echo _e('Email Templates', 'custom-registration-form-builder-with-submission-manager'); ?></div>
        <div class="rmrow"><div class="rmnotice">More email configuration settings are available in <a target="_blank" href="admin.php?page=rm_options_autoresponder">Global Settings</a>.</div></div>
        
        <?php
        $form = new RM_PFBC_Form("login-email-temp");

        $form->configure(array(
            "prevent" => array("bootstrap", "jQuery"),
            "action" => ""
        ));
        
        $form->addElement(new Element_HTML('<div class="rmrow"><h3>'.__('Emails to User', 'custom-registration-form-builder-with-submission-manager').'</h3></div>'));
        $form->addElement(new Element_Textbox("<b>" . RM_UI_Strings::get('LABEL_FAILED_LOGIN_EMAIL_SUB') . "</b>", "failed_login_err_sub", array("class" => "rm_static_field", "value" =>  $params['failed_login_err_sub'], "longDesc"=>RM_UI_Strings::get('HELP_FAILED_LOGIN_EMAIL_SUB'))));
        $form->addElement(new Element_TinyMCEWP("<b>" . __('Failed Login Attempt Email Body', 'custom-registration-form-builder-with-submission-manager') . "</b>", $params['failed_login_err'],"failed_login_err", array('editor_class' => 'rm_TinyMCE', 'editor_height' => '100px'), array("longDesc" => esc_html__('Define the content of the email body sent to users after a failed login attempt.', 'custom-registration-form-builder-with-submission-manager'))));
        $form->addElement(new Element_Textbox("<b>" . RM_UI_Strings::get('LABEL_OTP_MESSAGE_EMAIL_SUB') . "</b>", "otp_message_sub", array("class" => "rm_static_field", "value" =>  $params['otp_message_sub'], "longDesc"=>RM_UI_Strings::get('HELP_OTP_MESSAGE_EMAIL_SUB'))));
        $form->addElement(new Element_TinyMCEWP("<b>" . __('One-Time Password Email Body', 'custom-registration-form-builder-with-submission-manager') . "</b>",$params['otp_message'] , "otp_message", array('editor_class' => 'rm_TinyMCE', 'editor_height' => '100px'), array("longDesc" => esc_html__('Define the content of the email body for the one-time password sent to users.', 'custom-registration-form-builder-with-submission-manager'))));
        $form->addElement(new Element_Textbox("<b>" . RM_UI_Strings::get('LABEL_PASS_RESET_EMAIL_SUB') . "</b>", "pass_reset_sub", array("class" => "rm_static_field", "value" =>  $params['pass_reset_sub'], "longDesc"=>RM_UI_Strings::get('HELP_PASS_RESET_EMAIL_SUB'))));
        $form->addElement(new Element_TinyMCEWP("<b>" . __('Password Reset Email Body', 'custom-registration-form-builder-with-submission-manager') . "</b>",$params['pass_reset'] , "pass_reset", array('editor_class' => 'rm_TinyMCE', 'editor_height' => '100px'), array("longDesc" => esc_html__('Define the content of the email body sent to users for password reset instructions.', 'custom-registration-form-builder-with-submission-manager'))));
        
        $form->addElement(new Element_HTML('<div class="rmrow"><h3>'.__('Emails to Admin', 'custom-registration-form-builder-with-submission-manager').'</h3></div>'));

        $form->addElement(new Element_Textbox("<b>" . RM_UI_Strings::get('LABEL_FAILED_LOGIN_ADMIN_EMAIL_SUB') . "</b>", "failed_login_err_admin_sub", array("class" => "rm_static_field", "value" => $params['failed_login_err_admin_sub'], "longDesc"=>RM_UI_Strings::get('HELP_FAILED_LOGIN_ADMIN_EMAIL_SUB'))));
        $form->addElement(new Element_TinyMCEWP("<b>" . __('Failed Login Attempt Email Body', 'custom-registration-form-builder-with-submission-manager') . "</b>", $params['failed_login_err_admin'], "failed_login_err_admin", array('editor_class' => 'rm_TinyMCE', 'editor_height' => '100px'), array("longDesc" => esc_html__('Define the content of the email body sent to admins when a failed login attempt occurs.', 'custom-registration-form-builder-with-submission-manager'))));
        $form->addElement(new Element_Textbox("<b>" . RM_UI_Strings::get('LABEL_BAN_MESSAGE_ADMIN_EMAIL_SUB') . "</b>", "ban_message_admin_sub", array("class" => "rm_static_field", "value" => $params['ban_message_admin_sub'], "longDesc"=>RM_UI_Strings::get('HELP_BAN_MESSAGE_ADMIN_EMAIL_SUB'))));
        $form->addElement(new Element_TinyMCEWP("<b>" . __('IP Blocked Email Body', 'custom-registration-form-builder-with-submission-manager') . "</b>", $params['ban_message_admin'], "ban_message_admin", array('editor_class' => 'rm_TinyMCE', 'editor_height' => '100px'), array("longDesc" => esc_html__('Define the content of the email body sent to admins when an IP is blocked.', 'custom-registration-form-builder-with-submission-manager'))));
        
       
        $form->addElement(new Element_HTMLL('&#8592; &nbsp; '.__('Cancel','custom-registration-form-builder-with-submission-manager'), '?page=rm_login_sett_manage', array('class' => 'cancel')));
        $form->addElement(new Element_Button(RM_UI_Strings::get('LABEL_SAVE'), "submit", array("id" => "rm_submit_btn", "class" => "rm_btn", "name" => "submit")));
        $form->render();
        ?>
    </div>
</div>
<?php } ?>