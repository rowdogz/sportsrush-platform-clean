<?php
class Element_UserPassword extends Element_Textbox {
	public $_attributes = array("type" => "password");

    public function jQueryDocumentReady() {
         if(is_user_logged_in() && !(isset($_GET['page']) && $_GET['page'] == 'rm_form_preview')){
            echo "jQuery('#" . esc_attr($this->_attributes['id']) . "').prop('disabled', true);
                jQuery('#" . esc_attr($this->_attributes['id']) . "').removeAttr('required');
                jQuery('#" . esc_attr($this->_attributes['id']) . "').removeAttr('initial-state');
            ";
        }
    }
}