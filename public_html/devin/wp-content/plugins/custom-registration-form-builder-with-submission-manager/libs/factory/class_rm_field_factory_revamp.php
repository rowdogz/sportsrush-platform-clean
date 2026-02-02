<?php
//Class to create form fields on the frontend (Revamp Project)
final class RM_Field_Factory_Revamp {
    private $user = null;

    public function __construct() {
        $this->user = wp_get_current_user();
    }

    private function print_attributes($atts = array()) {
        $str = '';
        foreach($atts as $k => $v) {
            if (($k == 'required') || ($k == 'readonly') || ($k == 'checked') || ($k == 'selected') || ($k == 'disabled') || ($k == 'multiple')) {
                $str .= esc_attr($k).' ';
            } else {
                $str .= esc_attr($k).'="'.esc_attr($v).'" ';
            }
        }
        return $str;
    }

    private function conditional_attributes($attributes, $field) {
        if (!empty($field->field_options->conditions['rules'])){
            global $wpdb;
            $conditions = $field->field_options->conditions;
            $data_cond_option = "";
            $data_cond_operator = "";
            $data_cond_value = "";
            $data_cond_action = isset($conditions['action']) ? $conditions['action'] : "";
            foreach ($conditions['rules'] as $rule) {
                $controlling_field_id = $rule['controlling_field'];
                $fields = $wpdb->get_results("SELECT field_type FROM {$wpdb->prefix}rm_fields WHERE field_id = $controlling_field_id");

                if(isset($fields[0]->field_type)) {
                    if($fields[0]->field_type == "Username") {
                        $cond_field_name = "username";
                    } elseif($fields[0]->field_type == "Password") {
                        $cond_field_name = "pwd";
                    } elseif($fields[0]->field_type == "Checkbox") {
                        $cond_field_name = $fields[0]->field_type. '_' . $controlling_field_id . '[]';
                    } else {
                        $cond_field_name = $fields[0]->field_type. '_' . $controlling_field_id;
                    }

                    $data_cond_option .= $cond_field_name ."|";
                    $data_cond_operator .= $rule['op'] ."|";
                    $data_cond_value .= $rule['values'][0] ."|";
                }
            }
            $data_cond_option = rtrim($data_cond_option, "|");
            $data_cond_operator = rtrim($data_cond_operator, "|");
            $data_cond_value = rtrim($data_cond_value, "|");
            $attributes['class'] .= " data-conditional-revamp";
            $attributes['data-cond-option'] = $data_cond_option;
            $attributes['data-cond-value'] = $data_cond_value;
            $attributes['data-cond-operator'] = $data_cond_operator;
            $attributes['data-cond-action'] = $data_cond_action;
            if (count($field->field_options->conditions['rules']) > 1) {
                $data_cond_comb = isset($conditions['settings']['combinator']) ? $conditions['settings']['combinator'] : "";
                $attributes['data-cond-comb'] = $data_cond_comb;
            }
        }
        return $attributes;
    }

    private function field_icon($icon) {
        if ($icon->shape == 'square') {
            $radius = '0px';
        }else if ($icon->shape == 'round') {
            $radius = '100px';
        } else if ($icon->shape == 'sticker'){
            $radius = '4px';
        }

        $bg_a = isset($icon->bg_alpha) ? $icon->bg_alpha : 1;

        $icon_style = "style=\"padding:3px;font-size: 18px;color:#{$icon->fg_color};background-color:#{$icon->bg_color};border-radius:{$radius};\"";

        $field_label= '<span><i class="material-icons rm-form-label-icon rm_front_field_icon" ' . $icon_style . ' id="id_show_selected_icon" data-opacity="'.$bg_a.'">' . $icon->codepoint . ';</i></span>';

        return $field_label;
    }

    public function create_username_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $attributes = array(
            'type' => 'text',
            'name' => 'username',
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'autocomplete' => 'username',
            'aria-labelledby' => $label_id
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        echo "<label ".$this->print_attributes($main_label_attributes)." >$icon {$field->field_label}<span class='rmform-req-symbol'>*</span> </label>";
        if(is_user_logged_in()) {
            $attributes['readonly'] = 'readonly';
            $attributes['value'] = $this->user->user_login;
        }
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        echo "<input ".$this->print_attributes($attributes)."  >";

    }

    public function create_userpassword_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'type' => 'password',
            'name' => 'pwd',
            'class' => 'rmform-control rm-pwd-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'required' => 'required',
            'aria-required' => 'true',
            'autocomplete' => 'new-password',
            'placeholder' => isset($field->field_options->field_placeholder) ? $field->field_options->field_placeholder : "",
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(is_user_logged_in()) {
            $attributes['readonly'] = 'readonly';
            unset($attributes['placeholder']);
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        echo "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}<span class='rmform-req-symbol'>*</span> </label>";
        echo "<div class='rmform-toggle-wrap'>";
        echo "<span class='rm-togglePassword rm-pwd-togglePassword'></span>";
        echo "<input ".$this->print_attributes($attributes)." >";
        echo "</div>";
        // scripts
        if (isset($field->field_options->en_pass_strength) && $field->field_options->en_pass_strength == 1) {
            if(!is_user_logged_in()) {
                wp_enqueue_script( 'new-frontend-field-userpassword', RM_BASE_URL.'public/js/new_frontend_password_field.js', array('jquery','jquery-ui-datepicker'), RM_PLUGIN_VERSION, false);
                wp_enqueue_script("rm_pwd_strength_new", RM_BASE_URL . "public/js/password.min.js", array('jquery'));
                // wp_enqueue_style("rm_pwd_strength_new", RM_BASE_URL . 'public/css/style_rm_front_end.css', array());

                $password_field_data= array(
                    'shortPass'=>$field->field_options->pwd_short_msg,
                    'badPass'=>$field->field_options->pwd_weak_msg,
                    'goodPass'=>$field->field_options->pwd_medium_msg,
                    'strongPass'=>$field->field_options->pwd_strong_msg
                );
                //wp_add_inline_script('rm_pwd_strength', "var rm_pass_warnings=". wp_json_encode($password_warnings).";");
                wp_localize_script('rm_pwd_strength_new', 'rm_pass_warnings_new', $password_field_data);
            }
        }
    }

    public function create_cnf_userpassword_field($field = null, $ex_sub_id = 0) {
        $input_id_cnf = 'input_id_'.$field->field_type . '_' . $field->field_id. '_cnf';
        $label_id_cnf = 'label_id_'.$field->field_type . '_' . $field->field_id. '_cnf';
        
        $attributes = array(
            'type' => 'password',
            'name' => 'password_confirmation',
            'class' => 'rmform-control password_confirmation_' .$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id_cnf,
            'aria-labelledby' => $label_id_cnf,
            'required' => 'required',
            'aria-required' => 'true',
            'autocomplete' => 'new-password',
            'placeholder' => esc_html__('Repeat your password', 'custom-registration-form-builder-with-submission-manager'),
        );

        $main_label_attributes = array(
            'for' => $input_id_cnf,
            'id' => $label_id_cnf,
            'class' => 'rmform-label'
        );
        if(is_user_logged_in()) {
            $attributes['readonly'] = 'readonly';
            unset($attributes['placeholder']);
        }
        $cnf_pass_label = isset($field->field_options->cnf_pass_label) && !empty($field->field_options->cnf_pass_label) ? $field->field_options->cnf_pass_label : __('Enter password again', 'custom-registration-form-builder-with-submission-manager');
        echo "<label ".$this->print_attributes($main_label_attributes)." >".esc_html($cnf_pass_label)."<span class='rmform-req-symbol'>*</span> </label>";
        echo "<div class='rmform-c-toggle-wrap'>";
        echo "<span class='rm-togglePassword'></span>";
        echo "<input ".$this->print_attributes($attributes)." >";
        echo "</div>";
    }

    public function create_password_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'password',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes)." >$icon {$field->field_label}";

        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_min_length)){
            $attributes['minlength'] = $field->field_options->field_min_length;
        }
        if (isset($field->field_options->field_max_length)){
            $attributes['maxlength'] = $field->field_options->field_max_length;
        }
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ( $field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;

        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_email_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'email',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            'value' => "",
            'placeholder' => isset($field->field_options->field_placeholder) ? $field->field_options->field_placeholder : "",
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes)." >$icon  $field->field_label";
        if(absint($field->is_field_primary) == 1) {
            $attributes['required'] = 'required';
            $attributes['data-primary'] = '1';
            if(is_user_logged_in()) {
                $attributes['readonly'] = 'readonly';
                $attributes['value'] = $this->user->user_email;
            }
        } else {
            $attributes['data-primary'] = '0';
            if(isset($old_value)) {
                $attributes['value'] = $old_value;
            } elseif(isset($field->field_options->field_default_value)) {
                $attributes['value'] = $field->field_options->field_default_value;
            }
            if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
                if ( $field->field_options->field_user_profile == 'existing_user_meta') {
                    $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
    
                } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                    $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
                }
            }
        }

        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;

        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_cnf_email_field($field = null, $ex_sub_id = 0) {
        if (!is_user_logged_in()) {
            $input_id = 'input_id_cnf_'.$field->field_type . '_' . $field->field_id;
            $label_id = 'label_id_cnf_'.$field->field_type . '_' . $field->field_id;

            $attributes = array(
                'type' => 'email',
                'name' => 'email_confirmation',
                'class' => 'rmform-control',
                'data-fieldtype' => $field->field_type,
                'aria-describedby'=>'rm-note-'.$field->field_id,
                'id' => $input_id,
                'aria-labelledby' => $label_id,
                'required' => 'required',
                'aria-required' => 'true',
                'aria-labelledby' => $label_id,
                'placeholder' => 'Enter email again'
            );
            $main_label_attributes = array(
                'for' => $input_id,
                'id' => $label_id,
                'class' => 'rmform-label'
            );
            // confirmation email
        
            if (isset($field->field_options->en_confirm_email) && $field->field_options->en_confirm_email == 1) {
                echo "<label ".$this->print_attributes($main_label_attributes)."> Enter email again <span class='rmform-req-symbol'>*</span> </label>";

                echo "<input ".$this->print_attributes($attributes)." >";            
            }
        }
    }

    public function create_textbox_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'minlength' => isset($field->field_options->field_min_length) ? $field->field_options->field_min_length : "",
            'maxlength' => isset($field->field_options->field_max_length) ? $field->field_options->field_max_length : "",
            'value' => "",
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $attributes['value'] = $field->field_options->field_default_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                if(!empty($field->field_options->existing_user_meta_key)) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
                }
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                if(!empty($field->field_options->field_meta_add)) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
                }
            }
        }
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";


        $attributes = $this->conditional_attributes($attributes, $field);

        $label =  "<label ".$this->print_attributes($main_label_attributes)."> $icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = "required";
            $attributes['aria-required'] = "true";
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_textarea_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $value = "";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'minlength' => isset($field->field_options->field_min_length) ? $field->field_options->field_min_length : "",
            'maxlength' => isset($field->field_options->field_max_length) ? $field->field_options->field_max_length : "",
            'cols' => isset($field->field_options->field_textarea_columns) ? $field->field_options->field_textarea_columns : "",
            'rows' => isset($field->field_options->field_textarea_rows) ? $field->field_options->field_textarea_rows : "",
            'id' => $input_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if(isset($old_value)) {
            $value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $value = $field->field_options->field_default_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ( $field->field_options->field_user_profile == 'existing_user_meta') {
                $value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                $value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }


        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<textarea ".$this->print_attributes($attributes)." >$value</textarea>";
    }

    public function create_mobile_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $embed= false;
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'minlength' => isset($field->field_options->field_min_length) ? $field->field_options->field_min_length : "",
            'maxlength' => isset($field->field_options->field_max_length) ? $field->field_options->field_max_length : "",
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if($field->field_options->format_type=='international'){
            // 
            wp_enqueue_script("rm_mobile_script", RM_BASE_URL . "public/js/mobile_field/intlTelInput.min.js", array('jquery'));
            wp_enqueue_style("rm_mobile_style", RM_BASE_URL . "public/css/mobile_field/intlTelInput.min.css");
            $util_js= RM_BASE_URL . "public/js/mobile_field/utils.js";
            $tel_params= "{";
            $error_msg= $field->field_options->mobile_err_msg;
            $embed= false;
            if(empty($field->field_options->field_placeholder)) {
                $tel_params .= "autoPlaceholder:'aggressive',";
            }
            if(isset($attributes['value']) && !empty($attributes['value'])) {
                $attributes['aria-invalid'] = "false";
                $attributes['data-validnumber'] = "1";
                $attributes['data-fullnumber'] = $attributes['value'];
            }
            $country_field_id='';
            if(!empty($field->field_options->sync_country) && $field->field_options->country_field!='not_found') {
                global $wpdb;
                $unique_id_name = RM_Table_Tech::get_unique_id_name('FIELDS');        
                $table_name = RM_Table_Tech::get_table_name_for('FIELDS');
                $rm_country_field = $wpdb->get_row($wpdb->prepare("SELECT * from `$table_name` where $unique_id_name = %d", $field->field_options->country_field));
                if($rm_country_field->field_type === 'Address') {
                    $country_field_id = 'input_id_country_label_' . $field->field_options->country_field;
                } else {
                    $country_field_id = 'input_id_Country_' . $field->field_options->country_field;
                }
                
                $force_match_js= '';
                if(!empty($field->field_options->country_match)) {
                    $force_match_js= "document.getElementById('$input_id').closest('.rmform-field').find('.selected-flag').classList.add('disable-flag');";
                    $tel_params .= 'allowDropdown: false,';
                }

                $preferred_countries='';
                if(!empty($field->field_options->preferred_countries)) {
                    $countries= explode(',', (string)$field->field_options->preferred_countries);
                    if(is_array($countries)) {
                        $preferred_countries= '[';
                        foreach($countries as $country){
                            $preferred_countries .= '"'.strtolower(RM_Utilities_Revamp::get_country_code($country)).'",';
                        }
                        $tel_params .= 'preferredCountries:'.rtrim((string)$preferred_countries, ',').'],';
                    }
                }
            } else if(!empty($field->field_options->en_geoip)) {
                $tel_params .= 'initialCountry:"auto", geoIpLookup: function(callback) { fetch("https://ipapi.co/json").then(function(res) { return res.json(); }).then(function(data) { callback(data.country_code); }).catch(function() { callback("us"); }); },';
            }
            if($tel_params!='{')
                $tel_params.= 'utilsScript:"'.$util_js.'?1684676252775"}';
            else{
                $tel_params='';
            }
            $ca_state_type= isset($field->field_options->ca_state_type) ? $field->field_options->ca_state_type : 'all';
            if($ca_state_type=='america'){

            } else {
                if($embed){
                } else {
                    if(empty($country_field_id)) {
                        wp_add_inline_script('rm_mobile_script',"window.addEventListener('load', (event) => { if (typeof telDuplicate_" . $field->field_id . " === 'undefined') { telDuplicate_" . $field->field_id . " = true; const rmMobileEl = document.getElementById('$input_id'); var iti_" . $field->field_id . " = window.intlTelInput(rmMobileEl, {$tel_params}); rmMobileEl.addEventListener('keyup', (event) => { const check = iti_" . $field->field_id . ".isValidNumber() ? 1 : 0; rmMobileEl.dataset.validnumber = check; rmMobileEl.dataset.fullnumber = iti_" . $field->field_id . ".getNumber(intlTelInputUtils.numberFormat.E164); }); } });");
                    } else {
                        wp_add_inline_script('rm_mobile_script',"window.addEventListener('load', (event) => { if (typeof telDuplicate_" . $field->field_id . " === 'undefined') { telDuplicate_" . $field->field_id . " = true; const rmMobileEl = document.getElementById('$input_id'); var iti_" . $field->field_id . " = window.intlTelInput(rmMobileEl, {$tel_params}); rmMobileEl.addEventListener('keyup', (event) => { const check = iti_" . $field->field_id . ".isValidNumber() ? 1 : 0; rmMobileEl.dataset.validnumber = check; rmMobileEl.dataset.fullnumber = iti_" . $field->field_id . ".getNumber(intlTelInputUtils.numberFormat.E164); }); } document.getElementById('" . $country_field_id . "').onchange = function() { var code = this.querySelector('option[value=\"' + this.value + '\"]').dataset.code; var selected_value = this.value; if(code) { iti_" . $field->field_id . ".setCountry(code); ".$force_match_js."} } });");
                    }
                }
            }

            $meta_value='';

            // if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){

            //     echo "<input ".$this->print_attributes($attributes)." >";
            // } else {
            //     echo "<input ".$this->print_attributes($attributes)." >";
            // }

            if(!empty($field->field_options->country_match) && empty($meta_value)  ){ 
                if($embed){
                    // echo "<script>jQuery(document).ready(function(){setTimeout(function(){jQuery('[name=\'".wp_kses_post((string)$country_field_name)."\']').trigger('change'); },3000);});</script>";
                } else {
                    // wp_add_inline_script('rm_mobile_script',"jQuery(document).ready(function(){setTimeout(function(){jQuery('[name=\'".$country_field_name."\']').trigger('change'); },3000);});");
                }

            }

            $attributes['data-fieldtype'] = 'MobileInternational';
            // 
        } elseif ($field->field_options->format_type=='local') {
            wp_enqueue_script("rm_mask_script", RM_BASE_URL . "public/js/jquery.mask.min.js", array('jquery'));
            $attributes['placeholder']= '(000)-000-0000';
            $attributes['pattern']= '.{14}';

            echo "<script>jQuery(document).ready(function(){jQuery('#" .$input_id . "').mask('(000)-000-0000')});</script>";
        } elseif ($field->field_options->format_type=='custom') {
            wp_enqueue_script("rm_mask_script", RM_BASE_URL . "public/js/jquery.mask.min.js", array('jquery'));
            $custom_pattern = $field->field_options->custom_mobile_format;
            $attributes['placeholder']= $custom_pattern;
            $attributes['pattern']= '.{14}';
            // if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            //     $attributes['required'] = 'required';
            //     $attributes['aria-required'] = 'true';

            //     echo "<input ".$this->print_attributes($attributes)." >";
            // }else{
            //     echo "<input ".$this->print_attributes($attributes)." >";
            // }
            echo "<script>jQuery(document).ready(function(){jQuery('#" .$input_id . "').mask('$custom_pattern')});</script>";
        } else {
            wp_enqueue_script("rm_mobile_script", RM_BASE_URL . "public/js/mobile_field/intlTelInput.min.js", array('jquery'));
            wp_enqueue_style("rm_mobile_style", RM_BASE_URL . "public/css/mobile_field/intlTelInput.min.css");
            $util_js= RM_BASE_URL . "public/js/mobile_field/utils.js";

            $tel_params = '{';
            if(!empty($field->field_options->lim_countries)){
                $countries= explode(',', (string)$field->field_options->lim_countries);
                if(is_array($countries)){
                    $limited_countries= '[';
                    foreach($countries as $country){
                        $limited_countries .= '"'.strtolower(RM_Utilities_Revamp::get_country_code($country)).'",';
                    }
                    $tel_params .= 'onlyCountries:'.rtrim((string)$limited_countries, ',').'],';
                }
            }
              
            if(!empty($field->field_options->lim_pref_countries)){
                $countries= explode(',', (string)$field->field_options->lim_pref_countries);
                if(is_array($countries)){
                    $preferred_countries= '[';
                    foreach($countries as $country){
                        $preferred_countries .= '"'.strtolower(RM_Utilities_Revamp::get_country_code($country)).'",';
                    }
                    $tel_params .= 'preferredCountries:'.rtrim((string)$preferred_countries, ',').'],';
                }
            }

            $tel_params.= 'utilsScript:"'.$util_js.'?1684676252775"}';
            
            wp_add_inline_script("rm_mobile_script","window.addEventListener('load', (event) => { if (typeof telDuplicate_" . $field->field_id . " === 'undefined') { telDuplicate_" . $field->field_id . " = true; const rmMobileEl = document.querySelector('#input_id_Mobile_" . $field->field_id . "'); var iti_" . $field->field_id . " = window.intlTelInput(rmMobileEl, " . $tel_params . "); rmMobileEl.addEventListener('keyup', (event) => { jQuery(rmMobileEl).prop('pattern',''); const check = iti_" . $field->field_id . ".isValidNumber() ? 1 : 0; rmMobileEl.dataset.validnumber = check; rmMobileEl.dataset.fullnumber = iti_" . $field->field_id . ".getNumber(intlTelInputUtils.numberFormat.E164); }); } });"); 

            // if($embed){
            //     echo "<script>if (typeof telDuplicate_" . wp_kses_post((string)$this->opts['id']) . " === 'undefined') { telDuplicate_" . wp_kses_post((string)$this->opts['id']) . " = true; const el = document.querySelector('#" . wp_kses_post((string)$this->opts['id']) . "'); var iti_" . wp_kses_post((string)$this->opts['id']) . " = window.intlTelInput(el, " . wp_kses_post((string)$tel_params) . "); jQuery(el).on('keyup', function(e) { jQuery(el).prop('pattern',''); const check = iti_" . wp_kses_post((string)$this->opts['id']) . ".isValidNumber() ? 1 : 0; el.dataset.validnumber = check; el.dataset.fullnumber = iti_" . wp_kses_post((string)$this->opts['id']) . ".getNumber(intlTelInputUtils.numberFormat.E164); }); }</script>";
            // }
            // else{
            //     // var_dump($field->field_id);
            //     wp_add_inline_script("rm_mobile_script","if (typeof telDuplicate_" . $this->opts['id'] . " === 'undefined') { telDuplicate_" . $this->opts['id'] . " = true; const el = document.querySelector('#" . $this->opts['id'] . "'); var iti_" . $this->opts['id'] . " = window.intlTelInput(el, " . $tel_params . "); jQuery(el).on('keyup', function(e) { jQuery(el).prop('pattern',''); const check = iti_" . $this->opts['id'] . ".isValidNumber() ? 1 : 0; el.dataset.validnumber = check; el.dataset.fullnumber = iti_" . $this->opts['id'] . ".getNumber(intlTelInputUtils.numberFormat.E164); }); }"); 

            //     wp_add_inline_script('rm_mobile_script',"if (typeof telDuplicate_" . $field->field_id . " === 'undefined') { telDuplicate_" . $field->field_id . " = true; const el = document.querySelector('#" . $input_id . "'); var iti_" . $field->field_id . " = window.intlTelInput(el, {$tel_params}); jQuery(el).on('keyup', function(e) { const check = iti_" . $field->field_id . ".isValidNumber() ? 1 : 0; el.dataset.validnumber = check; el.dataset.fullnumber = iti_" . $field->field_id . ".getNumber(intlTelInputUtils.numberFormat.E164); }); }");

            // }
        }

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_price_field($field = null, $ex_sub_id = 0){
        global $wpdb;
        $price_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_paypal_fields WHERE field_id=%d", absint($field->field_value)));
        if (!empty($price_field)) {
            if (defined('REGMAGIC_ADDON')) {
                if ($price_field->type == "fixed") {
                    $this->create_fixed_price_field($field, $price_field);
                } elseif ($price_field->type == "multisel") {
                    $this->create_multiselect_price_field($field, $price_field);
                } elseif ($price_field->type == "dropdown") {
                    $this->create_dropdown_price_field($field, $price_field);
                } elseif ($price_field->type == "userdef") {
                    $this->create_userdifined_price_field($field, $price_field);
                }
            } else {
                if ($price_field->type == "fixed") {
                    $this->create_fixed_price_field($field, $price_field);
                } else {
                    esc_html_e('Invalid price field', 'custom-registration-form-builder-with-submission-manager');
                }
            }
        } else {
            esc_html_e('Invalid price field', 'custom-registration-form-builder-with-submission-manager');
        }
    }

    public function create_fixed_price_field($field = null, $price_field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id . '_' .$price_field->field_id,
            'data-rmfieldtype' => 'price',
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'readonly' => 'readonly'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if(empty($price_field))
            return;
        $price_field->extra_options = maybe_unserialize($price_field->extra_options);
        $curr_pos = get_option('rm_option_currency_symbol_position', 'before');
        $curr_sym = RM_Utilities_Revamp::get_currency_symbol(get_option('rm_option_currency', 'USD'));
        if($curr_pos == 'before') {
            $field_value = "{$price_field->name} {$curr_sym}{$price_field->value}";
        } else {
            $field_value = "{$price_field->name} {$price_field->value}{$curr_sym}";
        }
        $attributes['value'] = $field_value;
        $attributes['data-rmfieldprice'] = $price_field->value;
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';

            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
        }
        $label .= "</label>";
        echo $label;
        echo "<div class='rmform-pricefield rm-fixed-price-field'>";
        echo "<div class='rm-fixed-price-field-wrap'>";
        echo "<input ".$this->print_attributes($attributes)." >";

        if (isset($price_field->extra_options) && $price_field->extra_options['allow_quantity'] == "yes") {
            echo "<span>×</span>";
            $fixed_qnt_attributes = array(
                'type' => 'Number',
                'name' => $field->field_type . '_' . $field->field_id . '_' . $price_field->field_id . "_qty",
                'class' => 'rm_price_field_quantity rmform-control',
                'min' => isset($price_field->extra_options['min_quantity']) ? $price_field->extra_options['min_quantity'] : "",
                'max' => isset($price_field->extra_options['max_quantity']) ? $price_field->extra_options['max_quantity'] : "",
            );
            if ($fixed_qnt_attributes['min'] != "" && $fixed_qnt_attributes['min']>0) {
                $fixed_qnt_attributes['value'] = $fixed_qnt_attributes['min'];
            } else {
                $fixed_qnt_attributes['value'] = 0;
            }
            echo "<input ".$this->print_attributes($fixed_qnt_attributes).">";
            echo "</div>";
            echo "<span class='rmform-error-message' id='rmform-" . strtolower($fixed_qnt_attributes['name']) . "-error'></span>";
        } else {
            echo "</div>";
        }
        echo "</div>";
        echo "<span class='rmform-error-message' id='rmform-" . $attributes['name'] . "-error'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";

    }

    public function create_multiselect_price_field($field = null, $price_field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON') || empty($price_field)) {
            return;
        }
        
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $price_field->extra_options = maybe_unserialize($price_field->extra_options);
        $price_field->option_label = maybe_unserialize($price_field->option_label);
        $price_field->option_price = maybe_unserialize($price_field->option_price);
        $curr_pos = get_option('rm_option_currency_symbol_position', 'before');
        $curr_sym = RM_Utilities_Revamp::get_currency_symbol(get_option('rm_option_currency', 'USD'));
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $data_rmfieldprice = array();
        foreach ($price_field->option_price as $key=>$value) {
            $data_rmfieldprice["_".$key] = $value;
        }
        $attributes = array(
            'type' => 'checkbox',
            'name' => $field->field_type . '_' . $field->field_id . '_' . $price_field->field_id . "[]",
            'class' => 'rmform-control',
            'data-rmfieldtype' => 'price',
            'data-rmfieldprice' => wp_json_encode($data_rmfieldprice),
            'aria-describedby' => "rm-note-".$field->field_id,
        );
        
        $main_label_attributes = array(
            'class' => 'rmform-label'
        );

        $secondary_label_attributes = array(
            'class' => 'rmform-multiselect-price-checkbox rmform-check'
        );

        $price_options_label_attributes = array(
            'class' => 'rmform-label rmform-radio-check'
        );

        $label = "<span ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
        }
        $label .= "</span>";
        echo $label;
        
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "<div class='rmform-control-wrap'>";
        }

        for ($x = 0; $x < count($price_field->option_label); $x++) {
            echo "<div class='rmform-pricefield'>";
            if($curr_pos == 'before') {
                $price_drop = $price_field->option_label[$x] . " ( $curr_sym" .$price_field->option_price[$x] . " )";
            } else {
                $price_drop = $price_field->option_label[$x] . " ( " .$price_field->option_price[$x] . "$curr_sym )";
            }
            $attributes['id'] = $input_id . "_multiselect_checkbox_".$x;
            $attributes['value'] = '_'.$x;
            $attributes['aria-labelledby'] = 'label_id_'.$field->field_type . '_' . $field->field_id.'_'.$x;
            
            $checkbox_input = $input_id . "_multiselect_checkbox_".$x;
            $secondary_label_attributes['title'] = $price_drop;
            $secondary_label_attributes['for'] = $checkbox_input;

            $price_options_label_attributes['id'] = $attributes['aria-labelledby'];

            echo "<label ".$this->print_attributes($secondary_label_attributes).">";
            echo "<div class='rm-multiselect-price-wrap'>";
            echo "<input ".$this->print_attributes($attributes).">";
            echo "<span ".$this->print_attributes($price_options_label_attributes)." > $price_drop </span>";    
            if (isset($price_field->extra_options) && $price_field->extra_options['allow_quantity'] == "yes") {
                echo "<span>×</span>";
                $multiselect_qnt_attributes = array(
                    'type' => 'Number',
                    'name' => $field->field_type . '_' . $field->field_id . '_' . $price_field->field_id . "_qty[_$x]",
                    'class' => 'rmform-control rm_price_field_quantity',
                    'min' => isset($price_field->extra_options['min_quantity']) ? $price_field->extra_options['min_quantity'] : "",
                    'max' => isset($price_field->extra_options['max_quantity']) ? $price_field->extra_options['max_quantity'] : "",
                );
                if ($multiselect_qnt_attributes['min'] != "" && $multiselect_qnt_attributes['min']>0) {
                    $multiselect_qnt_attributes['value'] = $multiselect_qnt_attributes['min'];
                } else {
                    $multiselect_qnt_attributes['value'] = 0;
                }
                echo "<input ".$this->print_attributes($multiselect_qnt_attributes).">";
                echo "</div>";
                echo "<span class='rmform-error-message' id='rmform-" . strtolower(str_replace(['[', ']'], '', $multiselect_qnt_attributes['name'])) . "-error'></span>";
            } else {
                echo "</div>";
            }
            echo "</label>";
            echo "</div>";
        }
        echo "<span class='rmform-error-message' id='rmform-" . $field->field_type . '_' . $field->field_id . '_' . $price_field->field_id . "-error'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";
        
        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "</div>";
        }
    }

    public function create_dropdown_price_field($field = null, $price_field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON') || empty($price_field)) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'name' => $field->field_type . '_' . $field->field_id . '_' . $price_field->field_id,
            'data-rmfieldtype' => 'price',
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
            
        $price_field->extra_options = maybe_unserialize($price_field->extra_options);
        $price_field->option_label = maybe_unserialize($price_field->option_label);
        $price_field->option_price = maybe_unserialize($price_field->option_price);
        $curr_pos = get_option('rm_option_currency_symbol_position', 'before');
        $curr_sym = RM_Utilities_Revamp::get_currency_symbol(get_option('rm_option_currency', 'USD'));
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $data_rmfieldprice = array();
        foreach ($price_field->option_price as $key=>$value) {
            $data_rmfieldprice["_".$key] = $value;
        }

        $attributes['data-rmfieldprice'] = wp_json_encode($data_rmfieldprice);

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;

        echo "<div class='rmform-pricefield rm-select-price-field'>";
        echo "<div class='rm-select-price-wrap rm-d-flex'>";
        echo "<select ".$this->print_attributes($attributes)." >";
        echo "<option value=''>".RM_UI_Strings::get('SELECT_FIELD_FIRST_OPTION')."</option>";
        for ($x = 0; $x < count($price_field->option_label); $x++) {
            if($curr_pos == 'before') {
                $price_drop = $price_field->option_label[$x] . " ( $curr_sym" .$price_field->option_price[$x] . " )";
            } else {
                $price_drop = $price_field->option_label[$x] . " ( " .$price_field->option_price[$x] . "$curr_sym )";
            }
            echo "<option value='_$x'> $price_drop </option>";          
        }
        echo "</select>";

        if (isset($price_field->extra_options) && $price_field->extra_options['allow_quantity'] == "yes") {
            echo "<span>×</span>";

            $qnt_attributes = array(
                'type' => 'Number',
                'name' => $field->field_type . '_' . $field->field_id. '_' . $price_field->field_id . '_qty',
                'class' => 'rmform-control rm_price_field_quantity',
                'id' => $input_id . "_qnt",
                'min' => isset($price_field->extra_options['min_quantity']) ? $price_field->extra_options['min_quantity'] : "",
                'max' => isset($price_field->extra_options['max_quantity']) ? $price_field->extra_options['max_quantity'] : "",
                'step' => "1"
            );
            if ($qnt_attributes['min'] != "" && $qnt_attributes['min']>0) {
                $qnt_attributes['value'] = $qnt_attributes['min'];
            } else {
                $qnt_attributes['value'] = 0;
            }
            echo "<input ".$this->print_attributes($qnt_attributes).">";
            echo "</div>";
            echo "<span class='rmform-error-message' id='rmform-" . strtolower($qnt_attributes['name']) . "-error'></span>";
        } else {
            echo "</div>";
        }
        echo "</div>";

        echo "<span class='rmform-error-message' id='rmform-" . strtolower($attributes['name']) . "-error'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";
    }

    public function create_userdifined_price_field($field = null, $price_field = null, $ex_sub_id = 0) {
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'type' => 'number',
            'name' => $field->field_type . '_' . $field->field_id . '_' . $price_field->field_id,
            'data-rmfieldtype' => 'price',
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'min' => '0.01',
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(empty($price_field))
            return;

        $attributes['placeholder'] = $price_field->name;
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";

        echo "<span class='rmform-error-message' id='rmform-" . $attributes['name'] . "-error'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";
    }
    // akash code start
    public function create_select_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $meta_value = "";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control '. 'select_'.$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_placeholder)) {
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        if(isset($old_value)) {
            $meta_value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $meta_value = $field->field_options->field_default_value;
        }

        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ( $field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        
        $options = explode(",",  $field->field_value);
        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<select ".wp_kses_post($this->print_attributes($attributes))." >";
        if (isset($field->field_options->field_select_label)) {
            $default = $field->field_options->field_select_label;
            echo "<option value=''>".esc_html(trim($default))."</option>";
        }

        foreach($options as $option) {
            $option = trim($option);
            if($option === null || $option === false || $option === '') {
                continue;
            }
            if($meta_value == $option) {
                echo "<option value='".esc_attr($option)."' selected>".esc_html($option)."</option>";
            }else{
                echo "<option value='".esc_attr($option)."'>".esc_html($option)."</option>";
            }
        }

        echo "</select>";
        if ( isset($field->field_options->field_enable_search) && $field->field_options->field_enable_search === 1) {
            wp_enqueue_script('rm_select2',RM_BASE_URL.'public/js/script_rm_select2.js', array('jquery'));
            wp_enqueue_style('rm_select2',RM_BASE_URL.'public/css/style_rm_select2.css');
            echo '<script>jQuery(document).ready(function() {jQuery(".select_'.$field->field_id.'").select2();});</script>'; 
        }
    }

    public function create_radio_field($field = null, $ex_sub_id = 0) {
        $checked = "";
        $attributes = array (
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control '. 'radio_'.$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'type' => 'radio',
            'onchange' => 'rmToggleOtherText(this)'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $main_label_attributes = array(
            'class' => 'rmform-label'
        );
        $secondary_label_attributes = array(
            'class' => 'rmform-label rmform-radio-check'
        );

        $options = unserialize($field->field_value);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if(isset($old_value)) {
            $checked = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $checked = $field->field_options->field_default_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ( $field->field_options->field_user_profile == 'existing_user_meta') {
                $checked = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                $checked = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        $label = "<span ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</span>";
        echo $label;

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "<div class='rmform-control-wrap'>";
        }
        $layout = isset($field->field_options->field_layout) && !empty($field->field_options->field_layout) ? sanitize_text_field($field->field_options->field_layout) : 'vertical';
        $layout_size = 1;
        if($layout=='vertical'){
            $layout_size = isset($field->field_options->field_layout_size) && !empty($field->field_options->field_layout_size) ? sanitize_text_field($field->field_options->field_layout_size) : 1;
        }
        $count = 0;
        if($layout == 'vertical'){
            echo "<div class='rmform-field-vertical-row' data-field-col='".$layout_size."' >";
        }elseif($layout == 'horizontal'){
            echo "<div class='rmform-field-horizontal-row'>";
        }
        foreach($options as $option) {
            $attributes['id'] = $field->field_type . '_' . $field->field_id."_".$count;
            $attributes['value'] = $option;
            $attributes['aria-labelledby'] = 'label_id_'.$field->field_type . '_' . $field->field_id.'_'.$count;
            
            echo "<label class='rmform-check' for='" . $attributes['id'] . "'>";
            if ($checked == $option){
                $attributes['checked'] = 'checked';
                echo "<input ".$this->print_attributes($attributes)." >";
            } else {
                echo "<input ".$this->print_attributes($attributes).">";
            }
            unset($attributes['checked']);

            $secondary_label_attributes['id'] = 'label_id_'.$field->field_type ."_".$field->field_id.'_'.$count;
            echo "<span " . $this->print_attributes($secondary_label_attributes) . " >$option</span>";
            echo "</label>";
            $count++;
        }
        if (!isset($field->field_options->field_is_other_option) || $field->field_options->field_is_other_option != "1"){
            if($layout== 'vertical' || $layout == 'horizontal'){
                    echo "</div>";
            }
        }
        if (isset($field->field_options->field_is_other_option) && $field->field_options->field_is_other_option == "1"){
            $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
            $other_text = $field->field_options->rm_textbox;
            $other_radio_attributes = array (
                'name' => $field->field_type . '_' . $field->field_id,
                'class' => 'rmform-control '. 'radio_'.$field->field_id,
                'value' => '',
                'aria-describedby'=>'rm-note-'.$field->field_id,
                'aria-labelledby' => $label_id,
                'type' => 'radio',
                'id' => $field->field_type . '_' . $field->field_id."_other",
                'onchange' => 'rmToggleOtherText(this)'
            );
            if(isset($field->field_options->field_css_class)) {
                $other_radio_attributes['class'] .= " ".$field->field_options->field_css_class;
            }
            echo "<label class='rmform-check' for='".esc_attr($other_radio_attributes['id'])."'>";
            echo "<input ".wp_kses_post($this->print_attributes($other_radio_attributes)).">";
            $secondary_label_attributes['id'] = 'label_id_'.$field->field_id.'_'.$count;
            $secondary_label_attributes['for'] = $attributes['id'];
            echo "<span " . wp_kses_post($this->print_attributes($secondary_label_attributes)) . " >".wp_kses_post($other_text)."</span>";
            echo "</label>";
            
            if($layout== 'vertical' || $layout == 'horizontal'){
                echo "</div>";
            }
            $other_id = $field->field_type . '_' . $field->field_id."_other_input";
            $other_radio_text = array (
                'name' => $field->field_type . '_' . $field->field_id,
                'class' => 'rmform-control '. 'radio_'.$field->field_id,
                'aria-describedby'=>'rm-note-'.$field->field_id,
                'aria-labelledby' => $label_id,
                'type' => 'text',
                'id' => $other_id,
                'style' => "display:none;",
                'disabled' => "true",
            );
            echo "<input ".$this->print_attributes($other_radio_text)." >";
        }

        $error_span_id = strtolower($field->field_type)."_{$field->field_id}-error";
        echo "<span class='rmform-error-message' id='rmform-".wp_kses_post((string)$error_span_id)."'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "</div>";
        }

        wp_enqueue_script( 'rm-new-frontend-field', RM_BASE_URL.'public/js/new_frontend_field.js', array('jquery','jquery-ui-datepicker'));
    }

    public function create_checkbox_field($field = null, $ex_sub_id = 0) {
        $options = unserialize($field->field_value);
        $checked = array();
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array (
            'name' => $field->field_type . '_' . $field->field_id . '[]',
            'class' => 'rmform-control '. 'checkbox_'.$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'type' => 'checkbox',
            'onchange' => 'rmToggleOtherText(this)'
        );
        $main_label_attributes = array(
            'class' => 'rmform-label'
        );

        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        if(isset($old_value)) {
            $checked = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $checked = $field->field_options->field_default_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ( $field->field_options->field_user_profile == 'existing_user_meta') {
                $checked = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                $checked = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $checked = maybe_unserialize($checked);

        $label = "<span ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</span>";
        echo $label;

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "<div class='rmform-control-wrap'>";
        }
        $layout = isset($field->field_options->field_layout) && !empty($field->field_options->field_layout) ? sanitize_text_field($field->field_options->field_layout) : 'vertical';
        $layout_size = 0;
        if($layout=='vertical'){
            $layout_size = isset($field->field_options->field_layout_size) && !empty($field->field_options->field_layout_size) ? sanitize_text_field($field->field_options->field_layout_size) : 1;
        }
        if($layout == 'vertical'){
            echo "<div class='rmform-field-vertical-row' data-field-col='".$layout_size."'>";
        }elseif($layout == 'horizontal'){
            echo "<div class='rmform-field-horizontal-row'>";
        }
        $count = 0;
        foreach($options as $option) {
            $input_id = 'checkbox_'.$field->field_id.'_'.$count;
            $attributes['aria-labelledby'] = 'label_id_'.$field->field_type . '_' . $field->field_id . '_' . $count;
            $attributes['id'] = $input_id;
            $attributes['value'] = $option;
            
            echo "<label class='rmform-check' for='$input_id'>";
            if(is_array($checked)) {
                if(in_array($option, $checked)) {
                    $attributes['checked'] = 'checked';
                }
            } else {
                if($option == $checked) {
                    $attributes['checked'] = 'checked';
                }
            }
            echo "<input ".$this->print_attributes($attributes)." >";
            unset($attributes['checked']);
            echo "<span class='rmform-label rmform-radio-check' id='" . $attributes['aria-labelledby'] . "'>$option</span>";
            echo "</label>";
            
            $count++;
        }
        if (!isset($field->field_options->field_is_other_option) || $field->field_options->field_is_other_option != "1"){
            if($layout== 'vertical' || $layout == 'horizontal'){
                    echo "</div>";
            }
        }
        if (isset($field->field_options->field_is_other_option) && $field->field_options->field_is_other_option == "1"){
            $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
            $other_text = $field->field_options->rm_textbox;
            $other_radio_attributes = array (
                'name' => $field->field_type . '_' . $field->field_id . '[]',
                'class' => 'rmform-control '. 'checkbox_'.$field->field_id,
                'value' => '',
                'aria-describedby'=>'rm-note-'.$field->field_id,
                'aria-labelledby' => $label_id,
                'type' => 'checkbox',
                'id' => $field->field_type . '_' . $field->field_id."_other",
                'onchange' => 'rmToggleOtherText(this)'
            );
            if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
                $other_radio_attributes['required'] = 'required';
                $other_radio_attributes['aria-required'] = 'true';
            }
            echo "<label class='rmform-check' for='checkbox_$count'>";
            echo "<input ".$this->print_attributes($other_radio_attributes).">";
            $secondary_label_attributes['id'] = 'label_id_'.$field->field_id.'_'.$count;
            $secondary_label_attributes['for'] = $attributes['id'];
            echo "<span class='rmform-label rmform-radio-check' " . $this->print_attributes($secondary_label_attributes) . " >$other_text</span>";
            echo "</label>";

            if($layout== 'vertical' || $layout == 'horizontal'){
                echo "</div>";
            }
            $other_id = $field->field_type . '_' . $field->field_id."_other_input";
            $other_radio_text = array (
                'name' => $field->field_type . '_' . $field->field_id . "_other_input",
                'class' => 'rmform-control '. 'checkbox_'.$field->field_id,
                'aria-describedby'=>'rm-note-'.$field->field_id,
                'aria-labelledby' => $label_id,
                'type' => 'text',
                'id' => $other_id,
                'style' => "display:none;",
                'disabled' => "true",
            );
            echo "<input ".$this->print_attributes($other_radio_text)." >";
        }

        $error_span_id = strtolower($field->field_type)."_{$field->field_id}-error";
        echo "<span class='rmform-error-message' id='rmform-".wp_kses_post((string)$error_span_id)."'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "</div>";
        }

        wp_enqueue_script( 'rm-new-frontend-field', RM_BASE_URL.'public/js/new_frontend_field.js', array('jquery','jquery-ui-datepicker'));
    }

    public function create_jQueryUIDate_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control datepicker',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'autocomplete'=>'off',
            'readonly'=>'readonly',
            //'date_format'=> isset($field->field_options->date_format) ? $field->field_options->date_format : 'mm/dd/yy',
            'data-dateformat' => isset($field->field_options->date_format) ? $field->field_options->date_format : 'mm/dd/yy',
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            'id' => $input_id
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        } 
        $label .= "</label>";
        echo $label;
        echo "<input " . $this->print_attributes($attributes) . " >";

        // wp_enqueue_style(  'jquery-ui-date', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' ); 
        
        wp_enqueue_style('jquery-ui-date', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.min.css'); 
        wp_enqueue_script('rm-new-frontend-field', RM_BASE_URL.'public/js/new_frontend_field.js', array('jquery','jquery-ui-datepicker'));
    }

    public function create_url_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'url',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'value' => "",
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $attributes['value'] = $field->field_options->field_default_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ( $field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);

            } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label= "<label ".$this->print_attributes($main_label_attributes)." >$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $label.="<span class='rmform-req-symbol'>*</span>";
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label.="</label>";
        echo $label;
        echo "<input " . $this->print_attributes($attributes) . " >";
    }

    public function create_number_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'number',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'value' =>"",
            //'min' => '0',
            'id' => $input_id,
            'aria-labelledby' => $label_id
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_min_length)){
            $attributes['minlength'] = $field->field_options->field_min_length;
        }
        if (isset($field->field_options->field_max_length)){
            $attributes['maxlength'] = $field->field_options->field_max_length;
        }
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $attributes['value'] = $field->field_options->field_default_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);

            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input " . $this->print_attributes($attributes) . " >";
    }

    public function create_country_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $meta_value = "";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $meta_value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $meta_value = $field->field_options->field_default_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes)." >$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<select " . $this->print_attributes($attributes) . " >";
        foreach(RM_Utilities_Revamp::get_countries() as $name => $country) {
            $code = strtolower(preg_replace('/.*\[(.*)\].*/', '$1', $name));
            if($meta_value == $name || $meta_value == $country) {
                echo "<option value='".esc_attr($country)."' selected>".esc_html($country)."</option>";
            } else {
                echo "<option value='".esc_attr($country)."' data-code='".esc_attr($code)."'>".esc_html($country)."</option>";
            }
        }        
        echo "</select>";
    }

    public function create_timezone_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $meta_value = "";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $meta_value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $meta_value = $field->field_options->field_default_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        $options = RM_Utilities_Revamp::get_timezones();

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<select " . $this->print_attributes($attributes) . " >";
        foreach($options as $name => $timezone) {
            if($meta_value == $timezone) {
                echo "<option value=\"".esc_attr($name)."\" selected>".esc_html($timezone)."</option>";
            } else {
                echo "<option value=\"".esc_attr($name)."\">".esc_html($timezone)."</option>";
            }
        }
        echo "</select>";
    }

    public function create_terms_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'checkbox',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control'.' '.strtolower($field->field_type . '_' . $field->field_id),
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'value' => 'on',
            'rows' => '',
            'id' => $input_id,
            'aria-labelledby' => $label_id
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_is_required_scroll) && $field->field_options->field_is_required_scroll == 1){
            $attributes['disabled'] = "disabled";
        }
        $meta_value = "";
        if(isset($old_value)) {
            $meta_value = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        if($meta_value == $attributes['value']) {
            $attributes['checked'] = 'checked';
        }

        $text = $field->field_label;
        $check_box_label = $field->field_options->tnc_cb_label;

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon  $text ";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
        } 
        $label .= "</label>";
        echo $label;

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "<div class='rmform-control-wrap'>";
        }

        if (isset($field->field_options->field_check_above_tc) && $field->field_options->field_check_above_tc == 1) {
            echo "<div class='rmform-terms-checkbox'>";
            if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            echo "<label for='$input_id' id='$label_id' class='rmform-label'> $check_box_label </label>";
            echo "</div>";

            echo "<div class='rmform-terms-textarea'>";
            echo "<textarea onscroll='scroll_down_end(this);' readonly class='rmform-terms-text-area' >".wp_kses_post((string)$field->field_value)."</textarea>";
            echo "</div>";
        } else {
            echo "<div class='rmform-terms-textarea'>";
            echo "<textarea onscroll='scroll_down_end(this);' readonly class='rmform-terms-text-area' >".wp_kses_post((string)$field->field_value)."</textarea>";
            echo "</div>";

            echo "<div class='rmform-terms-checkbox'>";
            if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
                echo "<input " . $this->print_attributes($attributes) . " >";
            } else {
                echo "<input " . $this->print_attributes($attributes) . " >";
            }
            echo "<label for='$input_id' id='$label_id' class='rmform-label'> $check_box_label </label>";
            echo "</div>";

        }

        $error_span_id = strtolower($field->field_type)."_{$field->field_id}-error";
        echo "<span class='rmform-error-message' id='rmform-".wp_kses_post((string)$error_span_id)."'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "</div>";
        }
        
        
    }

    public function create_address_field($field = null, $ex_sub_id = 0) {
        $meta_value = "";
        $attributes = array(
            'type' => 'text',
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id
        );
        $main_label_attributes = array(
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if(isset($old_value)) {
            $meta_value = $old_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $meta_value = maybe_unserialize($meta_value);

        if ($field->field_options->field_address_type === "ca") {
            $field_ca_address1_en = $field->field_options->field_ca_address1_en ;
            $field_ca_address2_en = $field->field_options->field_ca_address2_en ;
            $field_ca_city_en = $field->field_options->field_ca_city_en ;
            $field_ca_state_en = $field->field_options->field_ca_state_en ;
            $field_ca_country_en = $field->field_options->field_ca_country_en ;
            $field_ca_zip_en = $field->field_options->field_ca_zip_en ;
            $field_ca_lmark_en = $field->field_options->field_ca_lmark_en;
    
            $field_ca_address1_label = $field->field_options->field_ca_address1_label ;
            $field_ca_address2_label = $field->field_options->field_ca_address2_label ;
            $field_ca_city_label = $field->field_options->field_ca_city_label ;
            $field_ca_state_label = $field->field_options->field_ca_state_label ;
            $field_ca_country_label = $field->field_options->field_ca_country_label ;
            $field_ca_zip_label = $field->field_options->field_ca_zip_label ;
            $field_ca_lmark_label = $field->field_options->field_ca_lmark_label;            

            $label = "<label ".$this->print_attributes($main_label_attributes).">$icon  $field->field_label";
            if(isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            }
            $label .= "</label>";
            echo $label;
            
            if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
                echo "<div class='rmform-control-wrap'>";
            }
            // address1
            if (isset($field_ca_address1_en) && $field_ca_address1_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_address1_' . $field->field_id;
                $input_id = 'input_id_address1_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "Address_".$field->field_id."[address1]";
                $attributes['aria-labelledby'] = $label_id;
                $attributes['value'] = isset($meta_value['address1']) ? $meta_value['address1'] : "";
                $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";
                if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_ca_address1_label;
                }
                if (isset($field->field_options->field_ca_address1_req) && $field->field_options->field_ca_address1_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_address1_label";
                if (isset($field->field_options->field_ca_address1_req) && $field->field_options->field_ca_address1_req == 1){
                    $astrick = get_option('rm_option_show_asterix');
                    if(isset($astrick) && $astrick == "yes"){
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                unset($attributes['required']);
                unset($attributes['aria-required']);
            }
            // address2
            if (isset($field_ca_address2_en) && $field_ca_address2_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_address2_label_' . $field->field_id;
                $input_id = 'input_id_address2_label_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "Address_".$field->field_id."[address2]";
                $attributes['aria-labelledby'] = $label_id;
                $attributes['value'] = isset($meta_value['address2']) ? $meta_value['address2'] : "";
                $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_ca_address2_label;
                }
                if (isset($field->field_options->field_ca_address2_req) && $field->field_options->field_ca_address2_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                }
                echo "<input " . $this->print_attributes($attributes) . ">";
                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_address2_label";

                if (isset($field->field_options->field_ca_address2_req) && $field->field_options->field_ca_address2_req == 1){
                    $label .= "<span class='rmform-req-symbol'>*</span> ";
                }
                $label .= "</label>";
                echo $label;
                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                unset($attributes['required']);
                unset($attributes['aria-required']);
            }
            // landmark
            if (isset($field_ca_lmark_en) && $field_ca_lmark_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-6'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_lmark_label_' . $field->field_id;
                $input_id = 'input_id_lmark_label_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "Address_".$field->field_id."[lmark]";
                $attributes['aria-labelledby'] = $label_id;
                $attributes['value'] = isset($meta_value['lmark']) ? $meta_value['lmark'] : "";
                $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_ca_lmark_label;
                }
                if (isset($field->field_options->field_ca_lmark_req) && $field->field_options->field_ca_lmark_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                }
                echo "<input " . $this->print_attributes($attributes) . " >";
                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_lmark_label";
                if (isset($field->field_options->field_ca_lmark_req) && $field->field_options->field_ca_lmark_req == 1){
                    $label .= "<span class='rmform-req-symbol'>*</span> ";
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                unset($attributes['required']);
                unset($attributes['aria-required']);
            }

            // city and state
            if ((isset($field_ca_city_en) && $field_ca_city_en == "1") && (isset($field_ca_state_en) && $field_ca_state_en == "1")) {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";

                // city
                echo "<div class='rmform-col rmform-col-6'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_city_label_' . $field->field_id;
                $input_id = 'input_id_city_label_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "Address_".$field->field_id."[city]";
                $attributes['aria-labelledby'] = $label_id;
                $attributes['value'] = isset($meta_value['city']) ? $meta_value['city'] : "";
                $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_ca_city_label;
                }
                if (isset($field->field_options->field_ca_city_req) && $field->field_options->field_ca_city_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                }
                echo "<input " . $this->print_attributes($attributes) . " >";
                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_city_label";
                if (isset($field->field_options->field_ca_city_req) && $field->field_options->field_ca_city_req == 1){
                    $astrick = get_option('rm_option_show_asterix');
                    if(isset($astrick) && $astrick == "yes"){
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                echo "</div>";
                echo "</div>";
                unset($attributes['required']);
                unset($attributes['aria-required']);

                // state
                echo "<div class='rmform-col rmform-col-6'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_state_label_' . $field->field_id;
                $input_id = 'input_id_state_label_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "Address_".$field->field_id."[state]";
                $attributes['aria-labelledby'] = $label_id;
                $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_ca_state_label;
                }

                if ( isset($field->field_options->ca_state_type) && ($field->field_options->ca_state_type === "all" || $field->field_options->ca_state_type === "limited") ){
                    $attributes['value'] = isset($meta_value['state']) ? $meta_value['state'] : "";
                    if (isset($field->field_options->field_ca_state_req) && $field->field_options->field_ca_state_req == 1){
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';
                    }
                    echo "<input " . $this->print_attributes($attributes) . " >";
                } else {
                    $us_states = RM_Utilities_Revamp::get_usa_states();
                    if (isset($field->field_options->field_ca_state_req) && $field->field_options->field_ca_state_req == 1) {
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';
                    }
                    echo "<select " . $this->print_attributes($attributes) . " >";
                    echo "<option value=''>".esc_html('--Select State--','custom-registration-form-builder-with-submission-manager')."</option>";
                    foreach($us_states as $name => $state) {
                        if(isset($meta_value['state']) && ($meta_value['state'] == $state || $meta_value['state'] == $name)) {
                            echo "<option value='".esc_attr($state)."' selected>".esc_html($state)."</option>";
                        } else {
                            echo "<option value='".esc_attr($state)."'>".esc_html($state)."</option>";
                        }
                    }
                    echo "</select>";
                }

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'>$field_ca_state_label";
                if (isset($field->field_options->field_ca_state_req) && $field->field_options->field_ca_state_req == 1) {
                    $astrick = get_option('rm_option_show_asterix');
                    if(isset($astrick) && $astrick == "yes"){
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                }
                $label .= "</label>";
                echo $label;
                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                echo "</div>";
                echo "</div>";

                echo "</div>";
                echo "</div>";
                unset($attributes['required']);
                unset($attributes['aria-required']);                
            } else {
                // city
                if (isset($field_ca_city_en) && $field_ca_city_en == "1") {
                    echo "<div class='rmform-row'>";
                    echo "<div class='rmform-row-field-wrap'>";
                    echo "<div class='rmform-col rmform-col-12'>";
                    echo "<div class='rmform-field'>";
                    $label_id = 'label_id_city_label_' . $field->field_id;
                    $input_id = 'input_id_city_label_' . $field->field_id;
                    $attributes['id'] = $input_id;
                    $attributes['name'] = "Address_".$field->field_id."[city]";
                    $attributes['aria-labelledby'] = $label_id;
                    $attributes['value'] = isset($meta_value['city']) ? $meta_value['city'] : "";
                    $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                    if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                        $attributes['placeholder'] = $field_ca_city_label;
                    }
                    if (isset($field->field_options->field_ca_city_req) && $field->field_options->field_ca_city_req == 1){
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';                        
                    }
                    echo "<input " . $this->print_attributes($attributes) . " >";
                    $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_city_label";
                    if (isset($field->field_options->field_ca_city_req) && $field->field_options->field_ca_city_req == 1) {
                        $astrick = get_option('rm_option_show_asterix');
                        if(isset($astrick) && $astrick == "yes"){
                            $label .= "<span class='rmform-req-symbol'>*</span>";
                        }
                    }
                    $label .= "</label>";
                    echo $label;

                    echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    unset($attributes['required']);
                    unset($attributes['aria-required']);
                }
                // state
                if (isset($field_ca_state_en) && $field_ca_state_en == "1") {
                    echo "<div class='rmform-row'>";
                    echo "<div class='rmform-row-field-wrap'>";
                    echo "<div class='rmform-col rmform-col-12'>";
                    echo "<div class='rmform-field'>";
                    $label_id = 'label_id_state_label_' . $field->field_id;
                    $input_id = 'input_id_state_label_' . $field->field_id;
                    $attributes['id'] = $input_id;
                    $attributes['name'] = "Address_".$field->field_id."[state]";
                    $attributes['aria-labelledby'] = $label_id;
                    $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                    if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                        $attributes['placeholder'] = $field_ca_state_label;
                    }

                    if ( isset($field->field_options->ca_state_type) && ($field->field_options->ca_state_type === "all" || $field->field_options->ca_state_type === "limited") ){    
                        if (isset($field->field_options->field_ca_state_req) && $field->field_options->field_ca_state_req == 1){
                            $attributes['required'] = 'required';
                            $attributes['aria-required'] = 'true';
                        }
                        $attributes['value'] = isset($meta_value['state']) ? $meta_value['state'] : "";
                        echo "<input " . $this->print_attributes($attributes) . " >";

                    } else {
                        $us_states = RM_Utilities_Revamp::get_usa_states();
                        if (isset($field->field_options->field_ca_state_req) && $field->field_options->field_ca_state_req == 1) {
                            $attributes['required'] = 'required';
                            $attributes['aria-required'] = 'true';
                        }
                        
                        echo "<select " . $this->print_attributes($attributes) . " >";
                        echo "<option value=''>".esc_html('--Select State--','custom-registration-form-builder-with-submission-manager')."</option>";
                        foreach($us_states as $name => $state) {
                            if (isset($meta_value['state']) && ($meta_value['state'] == $state || $meta_value['state'] == $name)) {
                                echo "<option value='".esc_attr($state)."' selected>".esc_html($state)."</option>";
                            } else {
                                echo "<option value='".esc_attr($state)."'>".esc_html($state)."</option>";
                            }
                        }
                        echo "</select>";
                    }
    
                    $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_state_label";
                    if (isset($field->field_options->field_ca_state_req) && $field->field_options->field_ca_state_req == 1) {
                        $astrick = get_option('rm_option_show_asterix');
                        if(isset($astrick) && $astrick == "yes"){
                            $label .= "<span class='rmform-req-symbol'>*</span>";
                        }
                    }
                    $label .= "</label>";
                    echo $label;

                    echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    unset($attributes['required']);
                    unset($attributes['aria-required']);
                }
            }

            // country and zip
            if ((isset($field_ca_country_en) && $field_ca_country_en == "1") && (isset($field_ca_zip_en) && $field_ca_zip_en == "1")) {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                // country
                echo "<div class='rmform-col rmform-col-6'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_country_label_' . $field->field_id;
                $input_id = 'input_id_country_label_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "Address_".$field->field_id."[country]";
                $attributes['aria-labelledby'] = $label_id;
                $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_ca_country_label;
                }

                if ( isset($field->field_options->ca_state_type) && $field->field_options->ca_state_type === "all" ){
                    $countries = explode(",", $field->field_options->field_ca_country_limited);
                    if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';
                    }
                    echo "<select " . $this->print_attributes($attributes) . " >";
                    foreach(RM_Utilities_Revamp::get_countries() as $ccode => $country) {
                        $ccode = strtolower(preg_replace('/.*\[(.*)\].*/', '$1', $ccode));
                        if(empty($ccode)) {
                            echo "<option value=\"\">".esc_html($country)."</option>";
                        } else {
                            if (isset($meta_value['country']) && $meta_value['country'] == $country) {
                                echo "<option value=\"".esc_attr($country)."\" data-code=\"".esc_attr($ccode)."\" selected>".esc_html($country)."</option>";
                            } else {
                                echo "<option value=\"".esc_attr($country)."\" data-code=\"".esc_attr($ccode)."\">".esc_html($country)."</option>";
                            }
                        }
                    }
                    echo "</select>";
                } elseif ( isset($field->field_options->ca_state_type) && $field->field_options->ca_state_type === "america" ) {
                    $attributes['readonly'] = 'readonly';
                    $attributes['value'] = 'United States';
                    if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';
                    }
                    echo "<input " . $this->print_attributes($attributes) . " >";

                } elseif ( isset($field->field_options->ca_state_type) && $field->field_options->ca_state_type === "america_can" ) {
                    $attributes['onchange'] = 'update_state_dropdown(' . $field->field_id . ')';
                    if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';
                    }
                    // if ($meta_value['country'] != "" && $meta_value['country'] == $country) {
                    //     $attributes['checked'] = 'checked';
                    // }
                    echo "<select " . $this->print_attributes($attributes) . " >";
                    echo "<option value='US'>".esc_html__('United States','custom-registration-form-builder-with-submission-manager')."</option>";
                    echo "<option value='Canada'>".esc_html__('Canada','custom-registration-form-builder-with-submission-manager')."</option>";
                    echo "</select>";
                } else {
                    $countries = explode(",", $field->field_options->field_ca_country_limited);
                    if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';
                    }
                    echo "<select " . $this->print_attributes($attributes) . " >";
                    foreach($countries as $country) {
                        echo "<option value=\"".esc_attr($country)."\">".esc_html($country)."</option>";
                    }
                    echo "</select>";
                }

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_country_label";
                if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;
                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                echo "</div>";
                echo "</div>";
                unset($attributes['required']);
                unset($attributes['aria-required']);

                // zip
                echo "<div class='rmform-col rmform-col-6'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_zip_label_' . $field->field_id;
                $input_id = 'input_id_zip_label_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "Address_".$field->field_id."[zip]";
                $attributes['aria-labelledby'] = $label_id;
                $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_ca_zip_label;
                }
                $attributes['value'] = isset($meta_value['zip']) ? $meta_value['zip'] : "";
                if(isset($attributes['onchange'])) {
                    unset($attributes['onchange']);
                }
                if(isset($attributes['readonly'])) {
                    unset($attributes['readonly']);
                }
                if (isset($field->field_options->field_ca_zip_req) && $field->field_options->field_ca_zip_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_zip_label";
                if (isset($field->field_options->field_ca_zip_req) && $field->field_options->field_ca_zip_req == 1){
                    $astrick = get_option('rm_option_show_asterix');
                    if(isset($astrick) && $astrick == "yes"){
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                echo "</div>";
                echo "</div>";

                echo "</div>";
                echo "</div>";
                unset($attributes['required']);
                unset($attributes['aria-required']);
            } else {
                // country
                if (isset($field_ca_country_en) && $field_ca_country_en == "1") {
                    echo "<div class='rmform-row'>";
                    echo "<div class='rmform-row-field-wrap'>";
                    echo "<div class='rmform-col rmform-col-12'>";
                    echo "<div class='rmform-field'>";
                    $label_id = 'label_id_country_label_' . $field->field_id;
                    $input_id = 'input_id_country_label_' . $field->field_id;
                    $attributes['id'] = $input_id;
                    $attributes['name'] = "Address_".$field->field_id."[country]";
                    $attributes['aria-labelledby'] = $label_id;
                    $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                    if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                        $attributes['placeholder'] = $field_ca_country_label;
                    }
    
                    if ( isset($field->field_options->ca_state_type) && $field->field_options->ca_state_type === "all" ){
                        $countries = explode(",", $field->field_options->field_ca_country_limited);
                        if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                            $attributes['required'] = 'required';
                            $attributes['aria-required'] = 'true';
                        }
                        echo "<select " . $this->print_attributes($attributes) . " >";
                        foreach(RM_Utilities_Revamp::get_countries() as $code => $country) {
                            $ccode = strtolower(preg_replace('/.*\[(.*)\].*/', '$1', $code));
                            if(empty($code)) {
                                echo "<option value=\"\">".esc_html($country)."</option>";
                            } else {
                                if (isset($meta_value['country']) && $meta_value['country'] == $country) {
                                    echo "<option value=\"".esc_attr($country)."\" data-code=\"".esc_attr($ccode)."\" selected>".esc_html($country)."</option>";
                                } else {
                                    echo "<option value=\"".esc_attr($country)."\" data-code=\"".esc_attr($ccode)."\">".esc_html($country)."</option>";
                                }
                            }
                        }
                        echo "</select>";
                    } elseif ( isset($field->field_options->ca_state_type) && $field->field_options->ca_state_type === "america" ) {
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';
                        if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                            $attributes['readonly'] = 'readonly';
                            $attributes['value'] = 'United States';                            
                        }
                        echo "<input " . $this->print_attributes($attributes) . " >";
                    } elseif ( isset($field->field_options->ca_state_type) && $field->field_options->ca_state_type === "america_can" ) {
                        $attributes['onchange'] = 'update_state_dropdown(' . $field->field_id . ')';
                        if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                            $attributes['required'] = 'required';
                            $attributes['aria-required'] = 'true';
                        }   
                        echo "<select " . $this->print_attributes($attributes) . " >";
                        echo "<option value='US'>".esc_html__('United States','custom-registration-form-builder-with-submission-manager')."</option>";
                        echo "<option value='Canada'>".esc_html__('Canada','custom-registration-form-builder-with-submission-manager')."</option>";
                        echo "</select>";
                    } else {
                        $countries = explode(",", $field->field_options->field_ca_country_limited);
                        if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                            $attributes['required'] = 'required';
                            $attributes['aria-required'] = 'true';
                        }
                        echo "<select " . $this->print_attributes($attributes) . " >";
                        foreach($countries as $country) {
                            echo "<option value=\"".esc_attr($country)."\">".esc_html($country)."</option>";
                        }
                        echo "</select>";
                    }
    
                    $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_country_label";
                    if (isset($field->field_options->field_ca_country_req) && $field->field_options->field_ca_country_req == 1) {
                        $label .= "<span class='rmform-req-symbol'>*</span> </label>";
                    }
                    $label .= "</label>";
                    echo $label;

                    echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    unset($attributes['required']);
                    unset($attributes['aria-required']);
                }
                // zip
                if (isset($field_ca_zip_en) && $field_ca_zip_en == "1") {
                    echo "<div class='rmform-row'>";
                    echo "<div class='rmform-row-field-wrap'>";
                    echo "<div class='rmform-col rmform-col-12'>";
                    echo "<div class='rmform-field'>";
                    $label_id = 'label_id_zip_label_' . $field->field_id;
                    $input_id = 'input_id_zip_label_' . $field->field_id;
                    $attributes['id'] = $input_id;
                    $attributes['name'] = "Address_".$field->field_id."[zip]";
                    $attributes['aria-labelledby'] = $label_id;
                    $attributes['value'] = isset($meta_value['zip']) ? $meta_value['zip'] : '';
                    if(isset($attributes['onchange'])) {
                        unset($attributes['onchange']);
                    }
                    if(isset($attributes['readonly'])) {
                        unset($attributes['readonly']);
                    }
                    $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

                    if (isset($field->field_options->field_ca_label_as_placeholder) && $field->field_options->field_ca_label_as_placeholder == '1'){
                        $attributes['placeholder'] = $field_ca_zip_label;
                    }
                    if (isset($field->field_options->field_ca_zip_req) && $field->field_options->field_ca_zip_req == 1){
                        $attributes['required'] = 'required';
                        $attributes['aria-required'] = 'true';
                    }
                    echo "<input " . $this->print_attributes($attributes) . " >";
                    $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_ca_zip_label";
                    if (isset($field->field_options->field_ca_zip_req) && $field->field_options->field_ca_zip_req == 1) {
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                    $label .= "</label>";
                    echo $label;

                    echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    unset($attributes['required']);
                    unset($attributes['aria-required']);
                }
            }

            echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";

            if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
                echo "</div>";
            }
        } else {
            $label = "<label ".$this->print_attributes($main_label_attributes).">$icon  $field->field_label";
            if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            }
            $label .= "</label>";
            echo $label;

            if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
                echo "<div class='rmform-control-wrap'>";
            }

            // Powered by GOOGLE MAPS
            $id = "Address_$field->field_id";
            $ca_label_id = 'label_id_address_ca_' . $field->field_id;
            $attributes['id'] = $id;
            $attributes['name'] = "Address_$field->field_id[original]";
            $attributes['placeholder'] = "Start typing your address";
            $attributes['class'] .= " rmgoogleautocompleteapi pac-target-input";
            $attributes['onfocus'] = '(new rmAutocomplete("Address_'.$field->field_id.'")).geolocate()';
            $attributes['onkeydown'] = 'rm_prevent_submission(event)';
            $attributes['autocomplete'] = 'off';
            $attributes['aria-labelledby'] = $ca_label_id;

            if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            }
            $main_label_attributes['class'] .= ' rmform-label-address';

            $main_label_attributes['for'] = $id;
            $main_label_attributes['id'] = $ca_label_id;

            $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field' id='locationField'>";
            echo "<input " . $this->print_attributes($attributes) . ">";
            echo "<label " . $this->print_attributes($main_label_attributes) . ">Powered by GOOGLE MAPS</label>";
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            unset($attributes['required']);
            unset($attributes['aria-required']);

            // street number
            $id = "Address_$field->field_id"."_street_number";
            $ca_label_id = "label_id_address_ca_$field->field_id"."_street_number";
            $attributes['id'] = $id;
            $attributes['name'] = "Address_$field->field_id[st_number]";
            unset($attributes['placeholder']);
            $attributes['class'] = "rmform-control field";
            unset($attributes['onfocus']);
            unset($attributes['onkeydown']);
            unset($attributes['autocomplete']);
            $attributes['aria-labelledby'] = $ca_label_id;

            $main_label_attributes['for'] = $id;
            $main_label_attributes['id'] = $ca_label_id;

            $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            echo "<input " . $this->print_attributes($attributes) . ">";
            echo "<label " . $this->print_attributes($main_label_attributes) . ">Street Number</label>";
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            
            // street address
            $id = "Address_$field->field_id"."_route";
            $ca_label_id = "label_id_address_ca_$field->field_id"."_route";
            $attributes['id'] = $id;
            $attributes['name'] = "Address_$field->field_id[st_route]";
            $attributes['aria-labelledby'] = $ca_label_id;
            $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

            $main_label_attributes['for'] = $id;
            $main_label_attributes['id'] = $ca_label_id;

            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            echo "<input " . $this->print_attributes($attributes) . ">";
            echo "<label " . $this->print_attributes($main_label_attributes) . ">Street Address</label>";
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";

            // city and state

            // city
            $id = "Address_$field->field_id"."_locality";
            $ca_label_id = "label_id_address_ca_$field->field_id"."_locality";
            $attributes['id'] = $id;
            $attributes['name'] = "Address_$field->field_id[city]";
            $attributes['aria-labelledby'] = $ca_label_id;
            $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

            $main_label_attributes['for'] = $id;
            $main_label_attributes['id'] = $ca_label_id;

            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";

            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            echo "<input " . $this->print_attributes($attributes) . ">";
            echo "<label " . $this->print_attributes($main_label_attributes) . ">City</label>";
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
            echo "</div>";
            echo "</div>";

            // state
            $id = "Address_$field->field_id"."_administrative_area_level_1";
            $ca_label_id = "label_id_address_ca_$field->field_id"."_administrative_area_level_1";
            $attributes['id'] = $id;
            $attributes['name'] = "Address_$field->field_id[state]";
            $attributes['aria-labelledby'] = $ca_label_id;
            $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

            $main_label_attributes['for'] = $id;
            $main_label_attributes['id'] = $ca_label_id;

            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            echo "<input " . $this->print_attributes($attributes) . ">";
            echo "<label " . $this->print_attributes($main_label_attributes) . ">State</label>";
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
            echo "</div>";
            echo "</div>";

            echo "</div>";
            echo "</div>";

            // country and zip
            // country
            $id = "Address_$field->field_id"."_country";
            $attributes['id'] = $id;
            $ca_label_id = "label_id_address_ca_$field->field_id"."_country";            
            $attributes['name'] = "Address_$field->field_id[country]";
            $attributes['aria-labelledby'] = $ca_label_id;
            $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

            $main_label_attributes['for'] = $id;
            $main_label_attributes['id'] = $ca_label_id;

            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";

            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            echo "<input " . $this->print_attributes($attributes) . ">";
            echo "<label " . $this->print_attributes($main_label_attributes) . ">Country</label>";
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
            echo "</div>";
            echo "</div>";

            // zip
            $id = "Address_$field->field_id"."_postal_code";
            $attributes['id'] = $id;
            $ca_label_id = "label_id_address_ca_$field->field_id"."_postal_code";
            $attributes['name'] = "Address_$field->field_id[zip]";
            $attributes['aria-labelledby'] = $ca_label_id;
            $error_span_id = str_replace(array("[","]"),"",strtolower($attributes['name']))."-error";

            $main_label_attributes['for'] = $id;
            $main_label_attributes['id'] = $ca_label_id;

            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            echo "<input " . $this->print_attributes($attributes) . ">";
            echo "<label " . $this->print_attributes($main_label_attributes) . ">Zip Code</label>";
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}'></span>";
            echo "</div>";
            echo "</div>";

            echo "</div>";
            echo "</div>";

            echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";

            if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
                echo "</div>";
            }
        }
        $gmap_api_key = get_option('rm_option_google_map_key', '');
        if(!empty($gmap_api_key)) {
            $google_map_api_key = 'https://maps.googleapis.com/maps/api/js?key=' . $gmap_api_key . '&libraries=places&loading=async&callback=rmInitGoogleApi';
            wp_enqueue_script('google_map_api', $google_map_api_key);
        }

        wp_enqueue_script( 'new-frontend-field-ca-address', RM_BASE_URL.'public/js/script_rm_address.js', array('jquery'));

        wp_enqueue_script( 'rm-new-frontend-field', RM_BASE_URL.'public/js/new_frontend_field.js', array('jquery','jquery-ui-datepicker'));
    }

    public function create_hidden_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $attributes = array(
            'type' => 'Hidden',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'value' => ""
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $attributes['value'] = $field->field_options->field_default_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        if (isset($field->field_options->field_ca_address2_req) && $field->field_options->field_ca_address2_req == 1){
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        echo "<input ".$this->print_attributes($attributes)." >";       
    }

    public function create_ESign_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'type' => 'file',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'accept'=>'image/*',
            'id' => $input_id,
            'aria-labelledby' => $label_id
        );
        $multiple= get_option('rm_option_allow_multiple_file_uploads');
        
        if (isset($multiple) && $multiple == "yes") {
            $attributes['name'] = $field->field_type . '_' . $field->field_id . '[]';
            $attributes['multiple'] = 'multiple';
        }
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input " . $this->print_attributes($attributes) . " >";
    }

    public function create_privacy_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'type' => 'checkbox',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control '.strtolower($field->field_type) . '_' . $field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'value' => 'on',
            'aria-labelledby' => $label_id,
            'required' => 'required',
            'aria-required' => 'true'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        $text = $field->field_options->privacy_policy_content;

        $content=  $field->field_options->privacy_policy_content;
        $url = 'javascript:void(0)';
        if(!empty($field->field_options->privacy_policy_page)){
            $url= get_permalink($field->field_options->privacy_policy_page);
        }
        $content = str_replace('{{privacy_policy}}',"<a target='_blank' href='$url'>".__('Privacy Policy','custom-registration-form-builder-with-submission-manager')."</a>",$content);
        echo "<div class='rmform-privicy-wrap'>";
        if (isset($field->field_options->privacy_display_checkbox) && $field->field_options->privacy_display_checkbox == 1) {
            echo "<input " . $this->print_attributes($attributes) . " >";
        }
        echo "<label ".$this->print_attributes($main_label_attributes)."> $content </label>";
        echo "</div>";
    }

    public function create_fname_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $current_user = wp_get_current_user(); 
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'value' => get_user_meta($current_user->ID, 'first_name', true),
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $attributes['value'] = (string)$wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_min_length)){
            $attributes['minlength'] = $field->field_options->field_min_length;
        }
        if (isset($field->field_options->field_max_length)){
            $attributes['maxlength'] = $field->field_options->field_max_length;
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_lname_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $current_user = wp_get_current_user(); 
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'value' => get_user_meta($current_user->ID, 'last_name', true),
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $attributes['value'] = (string)$wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_min_length)){
            $attributes['minlength'] = $field->field_options->field_min_length;
        }
        if (isset($field->field_options->field_max_length)){
            $attributes['maxlength'] = $field->field_options->field_max_length;
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_binfo_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $current_user = wp_get_current_user();
        $value = "";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if(isset($old_value)) {
            $value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $value = $field->field_options->field_default_value;
        }
        if(!empty(get_user_meta($current_user->ID, 'description', true)) && !isset($old_value)) {
            $value = get_user_meta($current_user->ID, 'description', true);
        }
        $attributes = array (
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'row' => isset($field->field_options->field_textarea_rows) ? $field->field_options->field_textarea_rows : "",
            'col' => isset($field->field_options->field_textarea_columns) ? $field->field_options->field_textarea_columns : "",
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_min_length)){
            $attributes['minlength'] = $field->field_options->field_min_length;
        }
        if (isset($field->field_options->field_max_length)){
            $attributes['maxlength'] = $field->field_options->field_max_length;
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<textarea ".$this->print_attributes($attributes)." >$value</textarea>";
    }

    public function create_nickname_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $current_user = wp_get_current_user(); 
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'value' => get_user_meta($current_user->ID, 'nickname', true),
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $attributes['value'] = (string)$wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_min_length)){
            $attributes['minlength'] = $field->field_options->field_min_length;
        }
        if (isset($field->field_options->field_max_length)){
            $attributes['maxlength'] = $field->field_options->field_max_length;
        }

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";


        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_website_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $current_user = wp_get_current_user(); 
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'value' => isset($current_user->user_url)? $current_user->user_url : "",
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            //'pattern' => '(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/[^\s]*)?',
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $attributes['value'] = (string)$wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input " . $this->print_attributes($attributes) . " >";
    }

    public function create_facebook_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            'pattern' => '(?:https?:\/\/)?(?:www\.)?facebook\.com\/(?:(?:\w)*#!\/)?(?:pages\/)?(?:[\w\-]*\/)*?(\/)?([\w\-\.]*)'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_twitter_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            'pattern' => '(ftp|http|https):\/\/?((www|\w\w)\.)?twitter.com(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_instagram_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            'pattern' => '(?:(?:http|https):\/\/)?(?:www.)?(?:instagram.com|instagr.am|instagr.com)\/(\w+)'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_linked_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            'pattern' => '(ftp|http|https):\/\/?((www|\w\w)\.)?linkedin.com(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_youtube_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'aria-labelledby' => $label_id,
            'data-fieldtype' => $field->field_type,
            'pattern' => '(?:https?:\/\/)?(?:youtu\.be\/|(?:www\.|m\.)?youtube\.com\/(?:watch|v|embed)(?:\.php)?(?:\?.*v=|\/))([a-zA-Z0-9\_-]+)'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_vkontacte_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            'pattern' => '(ftp|http|https):\/\/?((www|\w\w)\.)?(vkontakte.com|vk.com)(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        } 
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_skype_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            // 'pattern' => '(ftp|http|https):\/\/?((www|\w\w)\.)?(vkontakte.com|vk.com)(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)) {
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        } 
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_soundcloud_field($field = null, $ex_sub_id = 0) {
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
            'pattern' => '(ftp|http|https):\/\/?((www|\w\w)\.)?soundcloud.com(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)) {
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_htmlh_field($field = null, $ex_sub_id = 0) {
        $attributes = array(
            'class'=> 'rmform-field-type-heading rmform-control'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        $value = $field->field_value;
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        echo "<h1 ".$this->print_attributes($attributes).">$value</h1>";
    }

    public function create_htmlp_field($field = null, $ex_sub_id = 0) {
        $attributes = array(
            'class'=> 'rmform-control'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        $value = $field->field_value;
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        echo "<p ".$this->print_attributes($attributes).">$value</p>";
    }

    public function create_divider_field($field = null, $ex_sub_id = 0) {
        $attributes = array(
            'class'=> 'rmform-divider',
            'width' => '100%',
            'size' => 8,
            'align' => 'center'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        echo "<hr ".$this->print_attributes($attributes).">";
    }

    public function create_spacing_field($field = null, $ex_sub_id = 0) {
        $attributes = array(
            'class'=> 'rmform-control rm_spacing'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        echo "<div ".$this->print_attributes($attributes)."></div>";
    }

    public function create_RichText_field($field = null, $ex_sub_id = 0) {
        $attributes = array(
            'class'=> 'rmform-control'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $value = $field->field_value;
        echo "<div ".$this->print_attributes($attributes).">$value</div>";
    }

    public function create_Link_field($field = null, $ex_sub_id = 0) {
        $attributes = array(
            'class'=> 'rmform-control'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        $value = $field->field_label;
        if ($field->field_options->link_type == "url") {
            $href = $field->field_options->link_href;
        }elseif ($field->field_options->link_type == "page"){
            $href = get_permalink($field->field_options->link_page);
        }else{
            $href = "#";
        }
        $attributes['href'] = $href;
        if (!isset($field->field_options->link_same_window)) {
            $attributes['target'] = "_blank";
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        echo "<a ".$this->print_attributes($attributes).">$icon  $value</a>";
    }

    public function create_YouTubeV_field($field = null, $ex_sub_id = 0) {
        $attributes = array(
            'class'=> 'rmform-control',
            'frameborder' => '0',
            'allowfullscreen' => 'allowfullscreen'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        $value = $field->field_value;

        $video_id= RM_Utilities_Revamp::extract_youtube_embed_src($value);
        $src= "https://www.youtube.com/embed/".$video_id."?autoplay=". $field->field_options->yt_auto_play . "&playlist=".$video_id."&loop=" . $field->field_options->yt_repeat ."&rel=" . $field->field_options->yt_related_videos;

        if (isset($field->field_options->yt_player_width)) {
            $attributes['width'] = $field->field_options->yt_player_width;
        }
        if (isset($field->field_options->yt_player_height)) {
            $attributes['height'] = $field->field_options->yt_player_height;
        }
        $attributes['src'] = $src;
        if (isset($field->field_options->yt_auto_play) && $field->field_options->yt_auto_play == 1) {
            $attributes['allow'] = 'autoplay';
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        echo "<iframe ".$this->print_attributes($attributes)."></iframe>";
    }

    public function create_Iframe_field($field = null, $ex_sub_id = 0) {
        $attributes = array(
            'class'=> 'rmform-control',
            'frameborder' => '0',
            'allowfullscreen' => 'allowfullscreen'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        
        $attributes['width'] = isset($field->field_options->if_width)?$field->field_options->if_width:'auto';
        $attributes['height'] = isset($field->field_options->if_height)?$field->field_options->if_height:'auto';

        $src = $field->field_value;
        $link_type= RM_Utilities_Revamp::check_src_type($src);
        
        if($link_type === 'youtube'){
            $video_id= RM_Utilities_Revamp::extract_youtube_embed_src($src);
            $src= "http://www.youtube.com/embed/".$video_id;        
        }
        elseif($link_type === 'vimeo') {
            $video_id= RM_Utilities_Revamp::extract_vimeo_embed_src($src);
            $src= "http://player.vimeo.com/video/".$video_id; 
        }
        $attributes['src'] = $src;
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        echo "<iframe ".$this->print_attributes($attributes)."></iframe>";
    }

    // conditional not added start
    public function create_ImageV_field($field = null, $ex_sub_id = 0) {
        $class = 'rmform-control';
        if (isset($field->field_options->field_css_class)) {
            $class .= " ".$field->field_options->field_css_class;
        }

        $width= $field->field_options->img_size;
        $img='';
        $styles=array();
        $href='';$caption='';
        $title='';
        $shape_class='';

        if($field->field_options->img_effects_enabled){
            $styles['border:']= 'solid'; 
            $styles['border-color:']= '#'.$field->field_options->border_color;
            $styles['border-width:']= $field->field_options->border_width;
            if(strtolower($field->field_options->border_shape)=="circle"){
                $shape_class= 'imgv_shape_circle';
            }
            else {
                $shape_class= 'imgv_shape_square';  
            }
        }

        if(strtolower($width)!='thumbnail'){
            $styles['width:']= $width;
        }

        if($field->field_options->link_type=="url"){
            $href = $field->field_options->link_href;
        } else if($field->field_options->link_type=="page"){
            $href = get_permalink($field->field_options->link_page);
        } else {
            $href = "#";
        }

        $post = get_post($field->field_value);
        if(!empty($post)) {
            if($field->field_options->img_caption_enabled) {
                $caption= $post->post_excerpt; 
            }

            if($field->field_options->img_title_enabled) {
                $title= $post->post_title;
            }
        }

        $style_str='style="';
        foreach($styles as $key=>$val) {
            $style_str .= $key.$val.';';
        }
        $style_str .= '"';

        if(strtolower($width)=='thumbnail'){
            $src_array=wp_get_attachment_image_src($field->field_value,'thumbnail');
        }
        else{
            $src_array=wp_get_attachment_image_src($field->field_value,'full');
        }
        if(is_array($src_array)) {
            $src= $src_array[0];
            $img= "<img title='".$title."' $style_str src='".$src."' />"; 
        }

        if($href !== "#"){
            if($field->field_options->img_pop_enabled==1){
                add_thickbox();
                $href= esc_url(add_query_arg("TB_iframe",'true', $href));
                $img= '<a class="thickbox" href="'.$href.'">'.$img.'</a>';
            } else {
                $img= '<a target="_blank" href="'.$href.'">'.$img.'</a>';
            }
        } else {
            if($field->field_options->img_pop_enabled==1){
                add_thickbox();
                $src_array= wp_get_attachment_image_src($field->field_value,'full');
                $src= esc_url(add_query_arg("TB_iframe",'true',$src_array[0]));
                $img= '<a class="thickbox" href="'.$src.'">'.$img.'</a>';
            }
        }
       
        $html = "<div class='rmrow'><figure class='rm-image-widget wp-caption ".$shape_class."' ".$class.">$img"."<figcaption class='wp-caption-text'>".$caption."</figcaption></figure></div>";

        echo $html;
    }

    public function create_form_chart_field($field = null, $ex_sub_id = 0) {
        $class = 'rmform-control';
        if (isset($field->field_options->field_css_class)) {
            $class .= " ".$field->field_options->field_css_class;
        }
        $chart_type= $field->field_value;
        $field_label= $field->field_label;
        $stats_service= new RM_Analytics_Service();
        if($chart_type=="sot"){
            $time_range= $field->field_options->time_range;
            $chart_html= $stats_service->{$chart_type}($field->form_id,$time_range,null);
        }
        else{
            $chart_html= $stats_service->{$chart_type}($field->form_id);
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $html= "<div class='rmrow rm-box-graph $class'><div class='rm-box-title'>$icon  $field_label</div><div id='rm_".$chart_type."_div'></div></div> $chart_html"; 
        echo $html;
    }

    public function create_formdata_field($field = null, $ex_sub_id = 0){
        $html = RM_Utilities_Revamp::get_formdata_widget_html($field->field_id);
        echo $html;
    }

    public function create_feed_field($field = null, $ex_sub_id = 0){
        $html= RM_Utilities_Revamp::get_feed_widget_html($field->field_id);
        echo $html;
    }

    public function create_timer_field($field = null, $ex_sub_id = 0){

        wp_enqueue_script( 'new-flipclock', RM_BASE_URL.'public/js/script_rm_new_flipclock.js', array('jquery'));

        wp_enqueue_style( 'new-flipclock-style', RM_BASE_URL.'public/css/script_rm_new_flipclock.css');

        // wp_enqueue_style( 'new-flipclock-style', RM_BASE_URL.'public/css/rm_field_flipclock.css');

        echo "<div class='clock'></div>";
    }
    // conditional not added end
    public function create_file_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }

        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $attributes = array(
            'type' => 'file',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'data-fieldtype' => $field->field_type,
        );
        
        $multiple= get_option('rm_option_allow_multiple_file_uploads');
        if (isset($multiple) && $multiple == "yes") {
            $attributes['name'] = $field->field_type . '_' . $field->field_id . '[]';
            $attributes['multiple'] = 'multiple';
        }
        if (isset($field->field_value) && !empty($field->field_value)) {
            $allowed_types = array_map('trim', explode('|', $field->field_value));
            $attributes['accept'] = '.' . implode(',.', array_map('strtolower', $allowed_types));
        }
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        echo "<input " . $this->print_attributes($attributes) . " >";
    }

    public function create_repeatable_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $value = array('');

        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id . '[]',
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'aria-labelledby' => $label_id,
            'minlength' => isset($field->field_options->field_min_length) ? $field->field_options->field_min_length : "",
            'maxlength' => isset($field->field_options->field_max_length) ? $field->field_options->field_max_length : "",
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        
        $main_label_attributes = array(
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;

        echo "<div class= 'rminput'>";
        echo "<div class= 'rm_field_type_repeatable_container' id='rm_field_type_repeatable_container_".$field->field_id."'>";

        if(isset($old_value)) {
            $value = $old_value;
        }
        if(is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if($field->field_options->field_user_profile == 'existing_user_meta') {
                $value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif($field->field_options->field_user_profile == 'define_new_user_meta') {
                $value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $value = maybe_unserialize($value);
		if(empty($value)) {
			$value = array('');
		}

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        foreach($value as $val) {
            echo "<div class ='appendable_options' >";
            if (isset($field->field_options->field_is_multiline) && $field->field_options->field_is_multiline == 1) {
                if(isset($attributes['type']))
                    unset($attributes['type']);
                echo "<textarea " . $this->print_attributes($attributes) . ">$val</textarea>";
            } else {
                $attributes['value'] = $val;
                echo "<input " . $this->print_attributes($attributes) . " >";
            }

            echo "<div class='rm_actions' id='rm_add_repeatable_field' onclick=\"rm_append_field('div', 'rm_field_type_repeatable_container_".$field->field_id."')\"><a>Add</a></div>";

            echo "<div class='rm_actions' id='rm_delete_repeatable_field' onclick=\"rm_delete_appended_field(this,'rm_field_type_repeatable_container_".$field->field_id."')\"><a href='javascript:void(0)'>Delete</a></div>";
            echo "</div>";
        }
        echo "</div>";
        echo "</div>";
    }

    public function create_mapv_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }

        $style ='';
        $class =  $field->field_options->field_css_class;
        $address = $field->field_value;
        $zoom = empty($field->field_options->zoom) ? 17 : $field->field_options->zoom;
        if(empty($field->field_options->width)) {
            $style = "width:100%";
        } else {
            $style = "width:{$field->field_options->width}".'px';
        }
        $service= new RM_Services();
        $gmap_api_key= $service->get_setting('google_map_key');
        $element_id='';
      
        if(!empty($address) && !empty($gmap_api_key)){
            if(!wp_script_is('google_map_api', 'enqueued')) {
                wp_enqueue_script ('google_map_api', 'https://maps.googleapis.com/maps/api/js?key='.$gmap_api_key.'&libraries=places&loading=async&callback=rmInitGoogleApi');
            } elseif (wp_script_is('google_map_api', 'registered')) {
                wp_enqueue_script('google_map_api');
            }
            wp_enqueue_script("rm_map_widget_script",RM_BASE_URL."public/js/map_widget.js");
            $element_id= 'map'.$field->field_id;
            echo '<script>jQuery(document).ready(function(){rm_show_map_widget("'.wp_kses_post((string)$element_id).'",["'.wp_kses_post((string)$address).'"],'.wp_kses_post((string)$zoom).')});</script>';
        }
       
        $html= "<div style='$style' class='rm_mapv_container $class '><div id='".$element_id."' class='rm-map-widget'></div></div>";

        echo $html;
    }

    public function create_map_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }

        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $value = "";

        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );

        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;

        $gmap_api_key = get_option('rm_option_google_map_key', '');
        $google_map_api_key = 'https://maps.googleapis.com/maps/api/js?key=' . $gmap_api_key . '&libraries=places&loading=async&loading=async&callback=rmInitGoogleApi';

        if(isset($old_value)) {
            $value = $old_value;
        }
        echo "<div class='rminput'>";
        echo "<div class='rmmap_container'>";
        echo "<input type='text' id='Map_$field->field_id' class='rm-map-controls rm_map_autocomplete rm-map-controls-uninitialized pac-target-input' onkeydown='rm_prevent_submission(event)' name='Map_$field->field_id' value='$value' address_type='ga' street_label='Street Address' street_no_label='Street Number' city_label='City' state_label='State' country_label='Country' zip_label='Zip Code'>";
        echo "<div style='height:350px' class='map' id='mapMap_$field->field_id'></div>";
        echo "</div>";
        echo "</div>";
        
        wp_enqueue_script( 'google_map_api', $google_map_api_key);

        wp_enqueue_script( 'rm-form-address', RM_BASE_URL.'public/js/script_rm_map.js', array('jquery'));
    }

    public function create_phone_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $attributes = array(
            'type' => 'tel',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'minlength' => isset($field->field_options->field_min_length) ? $field->field_options->field_min_length : "",
            'maxlength' => isset($field->field_options->field_max_length) ? $field->field_options->field_max_length : "",
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);

            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label =  "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = "required";
            $attributes['aria-required'] = "true";
        }
        $label .= "</label>";
        echo $label;

        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_language_field($field = null, $ex_sub_id = 0) {
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $options = RM_Utilities_Revamp::get_language();
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $meta_value = "";
        $attributes = array (
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control '. 'select_'.$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        if(isset($old_value)) {
            $meta_value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $meta_value = $field->field_options->field_default_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;

        echo "<select ".$this->print_attributes($attributes)." >";
        echo "<option value=''></option>";
        foreach($options as $option) {
            if ($meta_value == $option) {
                echo "<option value=\"".esc_attr($option)."\" selected>".esc_html($option)."</option>";
            } else {
                echo "<option value=\"".esc_attr($option)."\">".esc_html($option)."</option>";
            }
        }
        echo "</select>";
    }

    public function create_bdate_field($field = null, $ex_sub_id = 0) {
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control bdaydatepicker',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'autocomplete'=>'off',
            'readonly'=>'readonly',
            //'date_format'=> isset($field->field_options->date_format) ? $field->field_options->date_format : 'mm/dd/yy',
            'data-dateformat' => isset($field->field_options->date_format) ? $field->field_options->date_format : 'mm/dd/yy',
            'aria-labelledby' => $label_id,
            'id' => $input_id,
            'required_max_range' => '',
            'required_min_range' => ''
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );

        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        $format = "m/d/Y";

        if(isset($attributes['data-dateformat']) && !empty($attributes['data-dateformat'])) {
            $format = str_replace(
                ['dd','mm','yy'],
                ['d','m','Y'],
                $attributes['data-dateformat']
            );
        }

        if (isset($field->field_options->field_is_required_range)) {
            if($field->field_options->field_is_required_max_range) {
                $max_date = DateTime::createFromFormat('m/d/Y', $field->field_options->field_is_required_max_range);
                $attributes['required_max_range'] = $max_date->format($format);
            }
            if($field->field_options->field_is_required_min_range) {
                $min_date = DateTime::createFromFormat('m/d/Y', $field->field_options->field_is_required_min_range);
                $attributes['required_min_range'] = $min_date->format($format);
            }
        }

        $attributes['required_max_range'] = $attributes['required_max_range'] == "" ? date($format) : $attributes['required_max_range'];

        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);

            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;

        echo "<input " . $this->print_attributes($attributes) . " >";

        wp_enqueue_style( 'jquery-ui-bday', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' ); 

        //$bday_min_max = array(
            //'max' => $attributes['required_max_range'] == "" ? date("m/d/Y") : $attributes['required_max_range'],
            //'min' => $attributes['required_min_range'],
        //);

        wp_enqueue_script('rm-new-frontend-field', RM_BASE_URL.'public/js/new_frontend_field.js', array('jquery','jquery-ui-datepicker'));
        //wp_localize_script('rm-new-frontend-field','bday_min_max', $bday_min_max);
    }

    public function create_gender_field($field = null, $ex_sub_id = 0) {
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $meta_value = "";
        $attributes = array (
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control '. 'radio_'.$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'type' => 'radio'
        );
        $main_label_attributes = array(
            'id' => $label_id,
            'class' => 'rmform-label'
        );

        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        
        $field_options = isset($field->field_options->gender_options) && !empty($field->field_options->gender_options) ? maybe_unserialize($field->field_options->gender_options) : array();
        
        $gender_options = array(
            1 => RM_UI_Strings::get("LABEL_GENDER_MALE"), 
            2 => RM_UI_Strings::get("LABEL_GENDER_FEMALE"),
            3 => RM_UI_Strings::get("LABEL_GENDER_NONBINARY"),
            4 => RM_UI_Strings::get("LABEL_GENDER_GENDERQUEER"),
            5 => RM_UI_Strings::get("LABEL_GENDER_GENDERFLUID"),
            6 => RM_UI_Strings::get("LABEL_GENDER_AGENDER"),
            7 => RM_UI_Strings::get("LABEL_GENDER_TRANSGENDER"),
            8 => RM_UI_Strings::get("LABEL_GENDER_TWOSPIRIT"),
            9 => RM_UI_Strings::get("LABEL_GENDER_NOTPREFER"),
            10 => RM_UI_Strings::get("LABEL_GENDER_OTHER"),
        );
        $options = array("Male", "Female");
        $other_option = '';
        if(!empty($field_options)){
            $options = array();
            foreach($field_options as $label_id){
                if(isset($gender_options[$label_id])){
                    $options[] = $gender_options[$label_id];
                    if($label_id == 10){
                        $other_option = $gender_options[$label_id];
                    }
                }
            }
        }

        if(isset($old_value)) {
            $meta_value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $meta_value = $field->field_options->field_default_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";

        echo $label;
        $count = 0;
        $layout = isset($field->field_options->field_layout) && !empty($field->field_options->field_layout) ? sanitize_text_field($field->field_options->field_layout) : 'vertical';
        $layout_size = 1;
        if($layout=='vertical'){
            $layout_size = isset($field->field_options->field_layout_size) && !empty($field->field_options->field_layout_size) ? sanitize_text_field($field->field_options->field_layout_size) : 1;
        }
        if($layout == 'vertical'){
            echo "<div class='rmform-field-vertical-row' data-field-col='".$layout_size."' >";
        }elseif($layout == 'horizontal'){
            echo "<div class='rmform-field-horizontal-row'>";
        }
        $options = apply_filters('rm_gender_modify_options', $options, $field_options, $gender_options);
        foreach($options as $value => $option) {
            $label_id = 'label_id_'.$field->field_id."_".$count;
            $attributes['id'] = "radio_".$field->field_id."_".$count;
            $attributes['value'] = is_string($value) ? $value : $option;
            $attributes['onchange'] = 'rmToggleOtherText(this)';
            if($meta_value == $option) {
                $attributes['checked'] = 'checked';
            } elseif(isset($attributes['checked'])) {
                unset($attributes['checked']);
            }
            $attributes['aria-labelledby'] =  $label_id;
            if($other_option == $option){
                $other_text = $option;
                $other_radio_attributes = array (
                    'name' => $field->field_type . '_' . $field->field_id,
                    'class' => 'rmform-control '. 'radio_'.$field->field_id,
                    'value' => '',
                    'aria-describedby'=>'rm-note-'.$field->field_id,
                    'aria-labelledby' => $label_id,
                    'type' => 'radio',
                    'id' => $field->field_type . '_' . $field->field_id."_other",
                    'onchange' => 'rmToggleOtherText(this)',
                );
                $other_label_for = $field->field_type . '_' . $field->field_id."_other";
                echo "<div class='rmform-check'>";
                echo "<input ".$this->print_attributes($other_radio_attributes).">";
                $secondary_label_attributes['id'] = 'label_id_'.$field->field_id.'_'.$count;
                $secondary_label_attributes['for'] = $attributes['id'];
                echo "<label for='$other_label_for' class='rmform-label rmform-radio-check' id='$label_id'>$option</label>";
                echo "</div>";
            } else {
                echo "<div class='rmform-check'>";
                echo "<input ".$this->print_attributes($attributes).">";
                echo "<label for='radio_$field->field_id".'_'."$count' class='rmform-label rmform-radio-check' id='$label_id'>$option</label>";
                echo "</div>";

            }
            
            $count++;
        }
        if($layout == 'vertical' || $layout == 'horizontal'){
            echo '</div>';
        }
        $other_id = $field->field_type . '_' . $field->field_id."_other_input";
        $other_radio_text = array (
                'name' => $field->field_type . '_' . $field->field_id,
                'class' => 'rmform-control '. 'checkbox_'.$field->field_id,
                'aria-describedby'=>'rm-note-'.$field->field_id,
                'aria-labelledby' => $label_id,
                'type' => 'text',
                'id' => $other_id,
                'style' => "display:none;",
                'disabled' => "true",
        );
        echo "<input ".$this->print_attributes($other_radio_text)." >";
        
        wp_enqueue_script( 'rm-new-frontend-field', RM_BASE_URL.'public/js/new_frontend_field.js', array('jquery','jquery-ui-datepicker'));
    }

    public function create_time_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $attributes = array(
            'type' => 'time',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label =  "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = "required";
            $attributes['aria-required'] = "true";
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_image_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'type' => 'file',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'accept'=>'image/*',
            'id' => $input_id,
            'aria-labelledby' => $label_id
        );
        $multiple= get_option('rm_option_allow_multiple_file_uploads');
        if (isset($multiple) && $multiple == "yes") {
            $attributes['name'] = $field->field_type . '_' . $field->field_id . '[]';
            $attributes['multiple'] = 'multiple';
        }
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input " . $this->print_attributes($attributes) . " >";
    }

    public function create_shortcode_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        echo apply_shortcodes($field->field_value);
    }

    public function create_multidropdown_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $meta_value = "";
        $attributes = array (
            'name' => $field->field_type . '_' . $field->field_id . "[]",
            'class' => 'rmform-control '. 'select_'.$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'multiple' => 'multiple'
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_placeholder)) {
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        if(isset($old_value)) {
            $meta_value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $meta_value = $field->field_options->field_default_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        $meta_value = maybe_unserialize($meta_value);

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $options = explode(",",  $field->field_value);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo '<span class="rm-field-hint">Press ctrl or ⌘ (in Mac) while clicking to select multiple options.</span>';

        echo "<select ".$this->print_attributes($attributes)." >";
        if (isset($field->field_options->field_select_label)) {
            $default = $field->field_options->field_select_label;
            echo "<option value=''>$default</option>";
        }
        
        foreach($options as $option) {
            if(is_array($meta_value)) {
                if(in_array($option, $meta_value)) {
                    echo "<option value=\"".esc_attr($option)."\" selected>".esc_html($option)."</option>";
                } else {
                    echo "<option value=\"".esc_attr($option)."\">".esc_html($option)."</option>";
                }
            } else {
                if($meta_value == $option) {
                    echo "<option value=\"".esc_attr($option)."\" selected>".esc_html($option)."</option>";
                } else {
                    echo "<option value=\"".esc_attr($option)."\">".esc_html($option)."</option>";
                }
            }
        }
        echo "</select>";
    }

    public function create_rating_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $value = "";
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $class = "rateit rm_rating_face_".$field->field_options->rating_conf->star_face." rateit-font";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $data_rateit_min = "0";
        $data_rateit_max = isset($field->field_options->rating_conf->max_stars) ? $field->field_options->rating_conf->max_stars : "";
        
        if (isset($field->field_options->rating_conf->step_size)) {
            if ( $field->field_options->rating_conf->step_size == "half") {
                $data_rateit_step = "0.5";
            } else {
                $data_rateit_step = "1";
            }
        };

        $icons_array = array(
            'star' => '&#xE838;', 
            'heart' => '&#xE87D;', 
            'face' => '&#xE420;', 
            'brush' => '&#xE3AE;', 
            'sun' => '&#xE430;', 
            'flag' => '&#xE153;',
            'snowflake' => '&#xEB3B;',
            'bag' => '&#xEB3F;',
            'circle' => '&#xE061;',
            'thumbup' => '&#xE8DC;',
        );

        $data_rateit_icon = isset($icons_array[$field->field_options->rating_conf->star_face]) ? $icons_array[$field->field_options->rating_conf->star_face] : $icons_array['star'];

        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $main_label_attributes = array(
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
        }
        $label .= "</label>";
        echo $label;

        $style = "font-family:Material Icons;word-wrap:normal";
        if(isset($field->field_options->rating_conf->star_color)) {
            $style .= ";color:#".$field->field_options->rating_conf->star_color;
        }
        $style .= ";";
        $attributes = array (
            'class' => $class,
            'id' => $input_id,
            'data-rateit-min' => $data_rateit_min,
            'data-rateit-max' => $data_rateit_max,
            'data-rateit-step' => $data_rateit_step,
            'data-rateit-ispreset' => 'true',
            'data-rateit-mode' => "font",
            'data-rateit-icon' => $data_rateit_icon,
            'data-rateit-starwidth' => '36',
            'data-rateit-forcestarwidth' => 'true',
            'data-rateit-resetable' => 'false',
            'data-rateit-backingfld' => '#rm_hidden_rate_Rating_'.$field->field_id,
            'style' => $style,
        );
        
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        if(isset($old_value)) {
            $value = $old_value;
        }

        echo "<input type='hidden' class='rateitbackend' id='rm_hidden_rate_Rating_".esc_attr($field->field_id)."' name='Rating_".esc_attr($field->field_id)."' value='".esc_attr($value)."' style='display: none;'>";
        echo "<div ".$this->print_attributes($attributes)."></div>";

        wp_enqueue_script( 'new-frontend-field-rating', RM_ADDON_BASE_URL . 'public/js/rating3/jquery.rateit.js', array('jquery'));
        wp_enqueue_style( 'new-frontend-field-rating', RM_ADDON_BASE_URL . 'public/js/rating3/rateit.css');

    }
    
    public function create_custom_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'minlength' => isset($field->field_options->field_min_length) ? $field->field_options->field_min_length : "",
            'maxlength' => isset($field->field_options->field_max_length) ? $field->field_options->field_max_length : "",
            'value' => isset($field->field_options->field_default_value) ? $field->field_options->field_default_value : "",
            'id' => $input_id,
            'data-fieldtype' => $field->field_type,
            'aria-labelledby' => $label_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        }
        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ($field->field_options->field_user_profile == 'existing_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ($field->field_options->field_user_profile == 'define_new_user_meta') {
                $attributes['value'] = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }

        if (isset($field->field_options->field_validation) && $field->field_options->field_validation != null) {
            if ($field->field_options->field_validation == 'custom' && isset($field->field_options->custom_validation)) {
                $custom_validation = $field->field_options->custom_validation;
            } else {
                $custom_validation = $field->field_options->field_validation;
            }
           $attributes['pattern'] = $custom_validation;
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label =  "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = "required";
            $attributes['aria-required'] = "true";
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_secemail_field($field = null, $ex_sub_id = 0){
        if (!defined('REGMAGIC_ADDON')) {
            return;
        }

        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'type' => 'email',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
            'value' => "",
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if(isset($old_value)) {
            $attributes['value'] = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $attributes['value'] = $field->field_options->field_default_value;
        }

        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes)." >$icon  $field->field_label";

        $attributes = $this->conditional_attributes($attributes, $field);

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_pgavatar_field($field = null, $ex_sub_id = 0){
        if (class_exists("Profile_Magic")){
            $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
            $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
    
            $attributes = array(
                'type' => 'file',
                'name' => $field->field_type . '_' . $field->field_id,
                'class' => 'rmform-control',
                'aria-describedby'=>'rm-note-'.$field->field_id,
                'accept'=>'image/*',
                'id' => $input_id,
                'aria-labelledby' => $label_id
            );
            $main_label_attributes = array(
                'for' => $input_id,
                'id' => $label_id,
                'class' => 'rmform-label'
            );
            // conditional attributes
            $attributes = $this->conditional_attributes($attributes, $field);
            $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

            $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
    
            if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            }
            $label .= "</label>";
            echo $label;
            echo "<input " . $this->print_attributes($attributes) . " >";   
        }
    }

    // woocommerce fields
    public function create_wcbilling_field($field = null, $ex_sub_id = 0) {
        if (!defined('REGMAGIC_ADDON') || !class_exists( 'WooCommerce' )) {
            return;
        }
        $attributes = array(
            'type' => 'text',
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id
        );
        $main_label_attributes = array(
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        $error_span_id = strtolower($field->field_type)."_{$field->field_id}";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if(isset($old_value)) {
            $old_value = maybe_unserialize($old_value);
        }
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $def_state = '';

        $field_wcb_firstname_en = $field->field_options->field_wcb_firstname_en ;
        $field_wcb_lastname_en = $field->field_options->field_wcb_lastname_en ;
        $field_wcb_company_en = $field->field_options->field_wcb_company_en ;
        $field_wcb_address1_en = $field->field_options->field_wcb_address1_en ;
        $field_wcb_address2_en = $field->field_options->field_wcb_address2_en ;
        $field_wcb_city_en = $field->field_options->field_wcb_city_en ;
        $field_wcb_state_en = $field->field_options->field_wcb_state_en;
        $field_wcb_country_en = $field->field_options->field_wcb_country_en ;
        $field_wcb_zip_en = $field->field_options->field_wcb_zip_en ;
        $field_wcb_phone_en = $field->field_options->field_wcb_phone_en ;
        $field_wcb_email_en = $field->field_options->field_wcb_email_en ;

        $field_wcb_firstname_label = $field->field_options->field_wcb_firstname_label ;
        $field_wcb_lastname_label = $field->field_options->field_wcb_lastname_label ;
        $field_wcb_company_label = $field->field_options->field_wcb_company_label ;
        $field_wcb_address1_label = $field->field_options->field_wcb_address1_label ;
        $field_wcb_address2_label = $field->field_options->field_wcb_address2_label ;
        $field_wcb_city_label = $field->field_options->field_wcb_city_label ;
        $field_wcb_state_label = $field->field_options->field_wcb_state_label;
        $field_wcb_country_label = $field->field_options->field_wcb_country_label ;
        $field_wcb_zip_label = $field->field_options->field_wcb_zip_label ;
        $field_wcb_phone_label = $field->field_options->field_wcb_phone_label ;
        $field_wcb_email_label = $field->field_options->field_wcb_email_label ;

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        echo "<label ".$this->print_attributes($main_label_attributes).">$icon  $field->field_label </label>";

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "<div class='rmform-control-wrap'>";
        }

        // firstname and lastname
        if ( (isset($field_wcb_firstname_en) && $field_wcb_firstname_en == "1") && (isset($field_wcb_lastname_en) && $field_wcb_lastname_en == "1")) {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            // firstname
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_firstname_' . $field->field_id;
            $input_id = 'input_id_firstname_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[firstname]";
            $attributes['aria-labelledby'] = $label_id;
            if(isset($old_value)) {
                if(isset($old_value['firstname'])) {
                    $attributes['value'] = $old_value['firstname'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif(is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_first_name', true);
            }

            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_firstname_label;
            }
            if (isset($field->field_options->field_wcb_firstname_req) && $field->field_options->field_wcb_firstname_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_firstname_label";
            if (isset($field->field_options->field_wcb_firstname_req) && $field->field_options->field_wcb_firstname_req == 1){
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            } 
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}firstname-error'></span>";
            echo "</div>";
            echo "</div>";

            // lastname
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_lastname_' . $field->field_id;
            $input_id = 'input_id_lastname_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[lastname]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_lastname_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['lastname'])) {
                    $attributes['value'] = $old_value['lastname'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_last_name', true);
            }
            if (isset($field->field_options->field_wcb_lastname_req) && $field->field_options->field_wcb_lastname_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_lastname_label";
            if (isset($field->field_options->field_wcb_lastname_req) && $field->field_options->field_wcb_lastname_req == 1){
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}lastname-error'></span>";
            echo "</div>";
            echo "</div>";

            echo "</div>";
            echo "</div>";
        } else {
            // firstname
            if (isset($field_wcb_firstname_en) && $field_wcb_firstname_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_firstname_' . $field->field_id;
                $input_id = 'input_id_firstname_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcbilling_".$field->field_id."[firstname]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcb_firstname_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['firstname'])) {
                        $attributes['value'] = $old_value['firstname'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_first_name', true);
                }
                if (isset($field->field_options->field_wcb_firstname_req) && $field->field_options->field_wcb_firstname_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_firstname_label";
                if (isset($field->field_options->field_wcb_firstname_req) && $field->field_options->field_wcb_firstname_req == 1){
                    $astrick = get_option('rm_option_show_asterix');
                    if(isset($astrick) && $astrick == "yes"){
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                }
                $label .= "</label>";
                echo $label;
                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}firstname-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            // lastname
            if (isset($field_wcb_lastname_en) && $field_wcb_lastname_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_lastname_' . $field->field_id;
                $input_id = 'input_id_lastname_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcbilling_".$field->field_id."[lastname]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcb_lastname_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['lastname'])) {
                        $attributes['value'] = $old_value['lastname'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_last_name', true);
                }
                if (isset($field->field_options->field_wcb_lastname_req) && $field->field_options->field_wcb_lastname_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_lastname_label";
                if (isset($field->field_options->field_wcb_lastname_req) && $field->field_options->field_wcb_lastname_req == 1){
                    $astrick = get_option('rm_option_show_asterix');
                    if(isset($astrick) && $astrick == "yes"){
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                }
                $label .= "</label>";
                echo $label;
                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}lastname-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        }
        
        // company
        if (isset($field_wcb_company_en) && $field_wcb_company_en == "1") {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_company_' . $field->field_id;
            $input_id = 'input_id_company_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[company]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_company_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['company'])) {
                    $attributes['value'] = $old_value['company'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_company', true);
            }
            if (isset($field->field_options->field_wcb_company_req) && $field->field_options->field_wcb_company_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_company_label";
            if (isset($field->field_options->field_wcb_company_req) && $field->field_options->field_wcb_company_req == 1){
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            }
            $label .= "</label>";
            echo $label;
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}company-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        // address1
        if (isset($field_wcb_address1_en) && $field_wcb_address1_en == "1") {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_address1_' . $field->field_id;
            $input_id = 'input_id_address1_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[add1]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_address1_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['add1'])) {
                    $attributes['value'] = $old_value['add1'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_address_1', true);
            }
            if (isset($field->field_options->field_wcb_address1_req) && $field->field_options->field_wcb_address1_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_address1_label";
            if (isset($field->field_options->field_wcb_address1_req) && $field->field_options->field_wcb_address1_req == 1){
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            }
            $label .= "</label>";
            echo $label;
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}add1-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        // address2
        if (isset($field_wcb_address2_en) && $field_wcb_address2_en == "1") {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_address2_' . $field->field_id;
            $input_id = 'input_id_address2_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[add2]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_address2_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['add2'])) {
                    $attributes['value'] = $old_value['add2'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_address_2', true);
            }
            if (isset($field->field_options->field_wcb_address2_req) && $field->field_options->field_wcb_address2_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_address2_label";
            if (isset($field->field_options->field_wcb_address2_req) && $field->field_options->field_wcb_address2_req == 1){
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            }
            $label .= "</label>";
            echo $label;
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}add2-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        // city and state
        if ((isset($field_wcb_city_en) && $field_wcb_city_en == "1") && (isset($field_wcb_state_en) && $field_wcb_state_en == "1")) {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            // city
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_city_' . $field->field_id;
            $input_id = 'input_id_city_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[city]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_city_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['city'])) {
                    $attributes['value'] = $old_value['city'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_city', true);
            }
            if (isset($field->field_options->field_wcb_city_req) && $field->field_options->field_wcb_city_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_city_label";
            if (isset($field->field_options->field_wcb_city_req) && $field->field_options->field_wcb_city_req == 1){
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            } 
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}city-error'></span>";
            echo "</div>";
            echo "</div>";

            // state
            echo "<div class='rmform-col rmform-col-6'>";
            $label_id = 'label_id_state_' . $field->field_id;
            $input_id = 'input_id_state_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[state]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_state_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['state'])) {
                    $attributes['value'] = $old_value['state'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_state', true);
            }
            if (isset($field->field_options->field_wcb_state_req) && $field->field_options->field_wcb_state_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            $def_state = $attributes['value'] ?? '';
            $required = $attributes['required'] ?? '';
            $style = $attributes['style'] ?? '';
            echo "<div class='rmform-field' id='wcbilling_".esc_attr($field->field_id)."_state' data-name='".esc_attr($attributes['name'])."' data-class='".esc_attr($attributes['class'])."' data-value='".esc_attr($def_state)."' data-placeholder='' data-style='".esc_attr($style)."' data-required='".esc_attr($required)."'>";
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_state_label";
            if (isset($field->field_options->field_wcb_state_req) && $field->field_options->field_wcb_state_req == 1){
                $astrick = get_option('rm_option_show_asterix');
                if(isset($astrick) && $astrick == "yes"){
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
            }
            $label .= "</label>";
            echo $label;
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}state-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }else {
            // city
            if (isset($field_wcb_city_en) && $field_wcb_city_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_city_' . $field->field_id;
                $input_id = 'input_id_city_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcbilling_".$field->field_id."[city]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcb_city_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['city'])) {
                        $attributes['value'] = $old_value['city'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_city', true);
                }
                if (isset($field->field_options->field_wcb_city_req) && $field->field_options->field_wcb_city_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_city_label";
                if (isset($field->field_options->field_wcb_city_req) && $field->field_options->field_wcb_city_req == 1){
                    $astrick = get_option('rm_option_show_asterix');
                    if(isset($astrick) && $astrick == "yes"){
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                }
                $label .= "</label>";
                echo $label;
                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}city-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            // state
            if (isset($field_wcb_state_en) && $field_wcb_state_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                $label_id = 'label_id_state_' . $field->field_id;
                $input_id = 'input_id_state_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcbilling_".$field->field_id."[state]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcb_state_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['state'])) {
                        $attributes['value'] = $old_value['state'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_state', true);
                }
                if (isset($field->field_options->field_wcb_state_req) && $field->field_options->field_wcb_state_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                $def_state = $attributes['value'] ?? '';
                $required = $attributes['required'] ?? '';
                $style = $attributes['style'] ?? '';
                echo "<div class='rmform-field' id='wcbilling_".esc_attr($field->field_id)."_state' data-name='".esc_attr($attributes['name'])."' data-class='".esc_attr($attributes['class'])."' data-value='".esc_attr($def_state)."' data-placeholder='' data-style='".esc_attr($style)."' data-required='".esc_attr($required)."'>";
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_state_label";
                if (isset($field->field_options->field_wcb_state_req) && $field->field_options->field_wcb_state_req == 1){
                    $astrick = get_option('rm_option_show_asterix');
                    if(isset($astrick) && $astrick == "yes"){
                        $label .= "<span class='rmform-req-symbol'>*</span>";
                    }
                }
                $label .= "</label>";
                echo $label;
                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}state-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        }
        
        // country and zip
        if ((isset($field_wcb_country_en) && $field_wcb_country_en == "1") && (isset($field_wcb_zip_en) && $field_wcb_zip_en == "1")) {
            $country_val = "";
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            // country
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_country_label_' . $field->field_id;
            $input_id = 'input_id_country_label_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[country]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_country_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['country'])) {
                    $country_val = $old_value['country'];
                }
            } elseif(is_user_logged_in()) {
                $country_val = get_user_meta(get_current_user_id(), 'billing_country', true);
            } elseif(function_exists('wc_get_base_location')) {
                $wc_base_loc = wc_get_base_location();
                $country_val = isset($wc_base_loc['country']) ? $wc_base_loc['country'] : "";
            }
            if (isset($field->field_options->field_wcb_country_req) && $field->field_options->field_wcb_country_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }

            if(isset($attributes['value']))
                unset($attributes['value']);
            echo "<select " . $this->print_attributes($attributes) . " >";
            foreach(RM_Utilities_Revamp::get_countries() as $name => $country) {
                $ccode = strtolower(preg_replace('/.*\[(.*)\].*/', '$1', $name));
                if($name == $country_val || strpos($name, "[$country_val]")) {
                    echo "<option value=\"".esc_attr($name)."\" data-code=\"".esc_attr($ccode)."\" selected>".esc_html($country)."</option>";
                } else {
                    echo "<option value=\"".esc_attr($name)."\" data-code=\"".esc_attr($ccode)."\">".esc_html($country)."</option>";
                }
            }
            echo "</select>";
            
            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_country_label";
            if (isset($field->field_options->field_wcb_country_req) && $field->field_options->field_wcb_country_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}country-error'></span>";
            echo "</div>";
            echo "</div>";
            

            // zip
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_zip_' . $field->field_id;
            $input_id = 'input_id_zip_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[zip]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_zip_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['zip'])) {
                    $attributes['value'] = $old_value['zip'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_postcode', true);
            }
            if (isset($field->field_options->field_wcb_zip_req) && $field->field_options->field_wcb_zip_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_zip_label";
            if (isset($field->field_options->field_wcb_zip_req) && $field->field_options->field_wcb_zip_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;
            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}zip-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }else{
            // country
            if (isset($field_wcb_country_en) && $field_wcb_country_en == "1") {
                $country_val = "";
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_country_label_' . $field->field_id;
                $input_id = 'input_id_country_label_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcbilling_".$field->field_id."[country]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcb_country_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['country'])) {
                        $country_val = $old_value['country'];
                    }
                } elseif (is_user_logged_in()) {
                    $country_val = get_user_meta(get_current_user_id(), 'billing_country', true);
                } elseif(function_exists('wc_get_base_location')) {
                    $wc_base_loc = wc_get_base_location();
                    $country_val = isset($wc_base_loc['country']) ? $wc_base_loc['country'] : "";
                }
                if (isset($field->field_options->field_wcb_country_req) && $field->field_options->field_wcb_country_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                if(isset($attributes['value']))
                    unset($attributes['value']);
                echo "<select " . $this->print_attributes($attributes) . " >";
                foreach(RM_Utilities_Revamp::get_countries() as $name => $country) {
                    $ccode = strtolower(preg_replace('/.*\[(.*)\].*/', '$1', $name));
                    if($name == $country_val || strpos($name, "[$country_val]")) {
                        echo "<option value=\"".esc_attr($name)."\" data-code=\"".esc_attr($ccode)."\" selected>".esc_html($country)."</option>";
                    } else {
                        echo "<option value=\"".esc_attr($name)."\" data-code=\"".esc_attr($ccode)."\">".esc_html($country)."</option>";
                    }
                }
                echo "</select>";
                
                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_country_label";
                if (isset($field->field_options->field_wcb_country_req) && $field->field_options->field_wcb_country_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}country-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            // zip
            if (isset($field_wcb_zip_en) && $field_wcb_zip_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_zip_' . $field->field_id;
                $input_id = 'input_id_zip_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcbilling_".$field->field_id."[zip]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcb_zip_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['zip'])) {
                        $attributes['value'] = $old_value['zip'];
                    } else {
                        $attributes['values'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_postcode', true);
                }
                if (isset($field->field_options->field_wcb_zip_req) && $field->field_options->field_wcb_zip_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_zip_label";
                if (isset($field->field_options->field_wcb_zip_req) && $field->field_options->field_wcb_zip_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;
                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}zip-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        }
        
        // phone
        if (isset($field_wcb_phone_en) && $field_wcb_phone_en == "1") {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_phone_' . $field->field_id;
            $input_id = 'input_id_phone_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[phone]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_phone_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['phone'])) {
                    $attributes['value'] = $old_value['phone'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_phone', true);
            }
            if (isset($field->field_options->field_wcb_phone_req) && $field->field_options->field_wcb_phone_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_phone_label";
            if (isset($field->field_options->field_wcb_phone_req) && $field->field_options->field_wcb_phone_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}phone-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        // email
        if (isset($field_wcb_email_en) && $field_wcb_email_en == "1") {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_email_' . $field->field_id;
            $input_id = 'input_id_email_' . $field->field_id;
            $attributes['type'] = 'email';
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcbilling_".$field->field_id."[email]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcb_label_as_placeholder) && $field->field_options->field_wcb_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcb_email_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['email'])) {
                    $attributes['value'] = $old_value['email'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'billing_email', true);
            }
            if (isset($field->field_options->field_wcb_email_req) && $field->field_options->field_wcb_email_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcb_email_label";
            if (isset($field->field_options->field_wcb_email_req) && $field->field_options->field_wcb_email_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}email-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";

        }

        $error_span_id = strtolower($field->field_type)."_{$field->field_id}-error";
        echo "<span class='rmform-error-message' id='rmform-".wp_kses_post((string)$error_span_id)."'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "</div>";
        }

        // Adding state change script
        echo "<script>
        jQuery(document).ready(function () {
            if(jQuery(\"[name='wcbilling_".esc_js((string)$field->field_id)."[country]']\").length && jQuery(\"[name='wcbilling_".esc_js((string)$field->field_id)."[state]']\").length) {
                jQuery(\"[name='wcbilling_".esc_js((string)$field->field_id)."[country]']\").change(function () {
                    if(jQuery(this).val() != '') {
                        jQuery('#wcbilling_".esc_js((string)$field->field_id)."_state').children().first().replaceWith('<div>".esc_html__('Loading States...', 'custom-registration-form-builder-with-submission-manager')."</div>');
                        var data = {
                            'action': 'rm_get_state',
                            'rm_sec_nonce': '".wp_create_nonce('rm_ajax_secure')."',
                            'rm_slug': 'rm_get_state',
                            'country': jQuery(this).val(),
                            'def_state': '".esc_js((string)$def_state)."',
                            'attr': 'data-rm-state-val',
                            'form_id': '".esc_js((string)$field->form_id)."',
                            'state_field_id': 'wcbilling_".esc_js((string)$field->field_id).'_state'."',
                            'type': 'billing'
                        };
                        rm_get_state(this, '".admin_url('admin-ajax.php')."', data);
                    }
                });
                jQuery(\"[name='wcbilling_".esc_js((string)$field->field_id)."[country]']\").trigger('change');
            }
        });
        </script>";
    }

    public function create_wcshipping_field($field = null, $ex_sub_id = 0) {
        if (!defined('REGMAGIC_ADDON') || !class_exists('WooCommerce')) {
            return;
        }
        $attributes = array(
            'type' => 'text',
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id
        );
        $main_label_attributes = array(
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        $error_span_id = strtolower($field->field_type)."_{$field->field_id}";
        
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        if(isset($old_value)) {
            $old_value = maybe_unserialize($old_value);
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $def_state = '';

        $field_wcs_firstname_en = $field->field_options->field_wcs_firstname_en ;
        $field_wcs_lastname_en = $field->field_options->field_wcs_lastname_en ;
        $field_wcs_company_en = $field->field_options->field_wcs_company_en ;
        $field_wcs_address1_en = $field->field_options->field_wcs_address1_en ;
        $field_wcs_address2_en = $field->field_options->field_wcs_address2_en ;
        $field_wcs_city_en = $field->field_options->field_wcs_city_en ;
        $field_wcs_state_en = $field->field_options->field_wcs_state_en;
        $field_wcs_country_en = $field->field_options->field_wcs_country_en ;
        $field_wcs_zip_en = $field->field_options->field_wcs_zip_en ;

        $field_wcs_firstname_label = $field->field_options->field_wcs_firstname_label ;
        $field_wcs_lastname_label = $field->field_options->field_wcs_lastname_label ;
        $field_wcs_company_label = $field->field_options->field_wcs_company_label ;
        $field_wcs_address1_label = $field->field_options->field_wcs_address1_label ;
        $field_wcs_address2_label = $field->field_options->field_wcs_address2_label ;
        $field_wcs_city_label = $field->field_options->field_wcs_city_label ;
        $field_wcs_state_label = $field->field_options->field_wcs_state_label;
        $field_wcs_country_label = $field->field_options->field_wcs_country_label ;
        $field_wcs_zip_label = $field->field_options->field_wcs_zip_label ;

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        echo "<label ".$this->print_attributes($main_label_attributes).">$icon  $field->field_label </label>";

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "<div class='rmform-control-wrap'>";
        }

        // firstname and lastname
        if ((isset($field_wcs_firstname_en) && $field_wcs_firstname_en == "1") && (isset($field_wcs_lastname_en) && $field_wcs_lastname_en == "1")) {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            // firstname
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_firstname_' . $field->field_id;
            $input_id = 'input_id_firstname_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[firstname]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_firstname_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['firstname'])) {
                    $attributes['value'] = $old_value['firstname'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_first_name', true);
            }
            if (isset($field->field_options->field_wcs_firstname_req) && $field->field_options->field_wcs_firstname_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_firstname_label";
            if (isset($field->field_options->field_wcs_firstname_req) && $field->field_options->field_wcs_firstname_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}firstname-error'></span>";
            echo "</div>";
            echo "</div>";

            // lastname
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_lastname_' . $field->field_id;
            $input_id = 'input_id_lastname_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[lastname]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_lastname_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['lastname'])) {
                    $attributes['value'] = $old_value['lastname'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_last_name', true);
            }
            if (isset($field->field_options->field_wcs_lastname_req) && $field->field_options->field_wcs_lastname_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_lastname_label";
            if (isset($field->field_options->field_wcs_lastname_req) && $field->field_options->field_wcs_lastname_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            } 
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}lastname-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        } else {
            // firstname
            if (isset($field_wcs_firstname_en) && $field_wcs_firstname_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_firstname_' . $field->field_id;
                $input_id = 'input_id_firstname_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcshipping_".$field->field_id."[firstname]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcs_firstname_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['firstname'])) {
                        $attributes['value'] = $old_value['firstname'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_first_name', true);
                }
                if (isset($field->field_options->field_wcs_firstname_req) && $field->field_options->field_wcs_firstname_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_firstname_label";
                if (isset($field->field_options->field_wcs_firstname_req) && $field->field_options->field_wcs_firstname_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}firstname-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            // lastname
            if (isset($field_wcs_lastname_en) && $field_wcs_lastname_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_lastname_' . $field->field_id;
                $input_id = 'input_id_lastname_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcshipping_".$field->field_id."[lastname]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcs_lastname_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['lastname'])) {
                        $attributes['value'] = $old_value['lastname'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_last_name', true);
                }
                if (isset($field->field_options->field_wcs_lastname_req) && $field->field_options->field_wcs_lastname_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                echo "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_lastname_label";
                if (isset($field->field_options->field_wcs_lastname_req) && $field->field_options->field_wcs_lastname_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}lastname-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        }
        
        // company
        if (isset($field_wcs_company_en) && $field_wcs_company_en == "1") {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_company_' . $field->field_id;
            $input_id = 'input_id_company_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[company]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_company_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['company'])) {
                    $attributes['value'] = $old_value['company'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_company', true);
            }
            if (isset($field->field_options->field_wcs_company_req) && $field->field_options->field_wcs_company_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_company_label";
            if (isset($field->field_options->field_wcs_company_req) && $field->field_options->field_wcs_company_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}company-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        // address1
        if (isset($field_wcs_address1_en) && $field_wcs_address1_en == "1") {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_address1_' . $field->field_id;
            $input_id = 'input_id_address1_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[add1]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_address1_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['add1'])) {
                    $attributes['value'] = $old_value['add1'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_address_1', true);
            }
            if (isset($field->field_options->field_wcs_address1_req) && $field->field_options->field_wcs_address1_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_address1_label";
            if (isset($field->field_options->field_wcs_address1_req) && $field->field_options->field_wcs_address1_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}add1-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        // address2
        if (isset($field_wcs_address2_en) && $field_wcs_address2_en == "1") {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            echo "<div class='rmform-col rmform-col-12'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_address2_' . $field->field_id;
            $input_id = 'input_id_address2_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[add2]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_address2_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['add2'])) {
                    $attributes['value'] = $old_value['add2'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_address_2', true);
            }
            if (isset($field->field_options->field_wcs_address2_req) && $field->field_options->field_wcs_address2_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_address2_label";
            if (isset($field->field_options->field_wcs_address2_req) && $field->field_options->field_wcs_address2_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}add2-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }

        // city and state
        if ((isset($field_wcs_city_en) && $field_wcs_city_en == "1") && (isset($field_wcs_state_en) && $field_wcs_state_en == "1")) {
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            // city
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_city_' . $field->field_id;
            $input_id = 'input_id_city_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[city]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_city_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['city'])) {
                    $attributes['value'] = $old_value['city'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_city', true);
            }
            if (isset($field->field_options->field_wcs_city_req) && $field->field_options->field_wcs_city_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_city_label";
            if (isset($field->field_options->field_wcs_city_req) && $field->field_options->field_wcs_city_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span> </label>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}city-error'></span>";
            echo "</div>";
            echo "</div>";

            // state
            echo "<div class='rmform-col rmform-col-6'>";
            $label_id = 'label_id_state_' . $field->field_id;
            $input_id = 'input_id_state_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[state]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_state_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['state'])) {
                    $attributes['value'] = $old_value['state'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_state', true);
            }
            if (isset($field->field_options->field_wcs_state_req) && $field->field_options->field_wcs_state_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            $def_state = $attributes['value'] ?? '';
            $required = $attributes['required'] ?? '';
            $style = $attributes['style'] ?? '';
            echo "<div class='rmform-field' id='wcshipping_".esc_attr($field->field_id)."_state' data-name='".esc_attr($attributes['name'])."' data-class='".esc_attr($attributes['class'])."' data-value='".esc_attr($def_state)."' data-placeholder='' data-style='".esc_attr($style)."' data-required='".esc_attr($required)."'>";
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_state_label";
            if (isset($field->field_options->field_wcs_state_req) && $field->field_options->field_wcs_state_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}state-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        } else {
            // city
            if (isset($field_wcs_city_en) && $field_wcs_city_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_city_' . $field->field_id;
                $input_id = 'input_id_city_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcshipping_".$field->field_id."[city]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcs_city_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['city'])) {
                        $attributes['value'] = $old_value['city'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_city', true);
                }
                if (isset($field->field_options->field_wcs_city_req) && $field->field_options->field_wcs_city_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_city_label";
                if (isset($field->field_options->field_wcs_city_req) && $field->field_options->field_wcs_city_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}city-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            // state
            if (isset($field_wcs_state_en) && $field_wcs_state_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                $label_id = 'label_id_state_' . $field->field_id;
                $input_id = 'input_id_state_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcshipping_".$field->field_id."[state]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcs_state_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['state'])) {
                        $attributes['value'] = $old_value['state'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_state', true);
                }
                if (isset($field->field_options->field_wcs_state_req) && $field->field_options->field_wcs_state_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                $def_state = $attributes['value'] ?? '';
                $required = $attributes['required'] ?? '';
                $style = $attributes['style'] ?? '';
                echo "<div class='rmform-field' id='wcshipping_".esc_attr($field->field_id)."_state' data-name='".esc_attr($attributes['name'])."' data-class='".esc_attr($attributes['class'])."' data-value='".esc_attr($def_state)."' data-placeholder='' data-style='".esc_attr($style)."' data-required='".esc_attr($required)."'>";
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_state_label";
                if (isset($field->field_options->field_wcs_state_req) && $field->field_options->field_wcs_state_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}state-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        }
        
        // country and zip
        if ((isset($field_wcs_country_en) && $field_wcs_country_en == "1") && (isset($field_wcs_zip_en) && $field_wcs_zip_en == "1")) {
            $country_val = "";
            echo "<div class='rmform-row'>";
            echo "<div class='rmform-row-field-wrap'>";
            // country
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_country_label_' . $field->field_id;
            $input_id = 'input_id_country_label_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[country]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_country_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['country'])) {
                    $country_val = $old_value['country'];
                }
            } elseif (is_user_logged_in()) {
                $country_val = get_user_meta(get_current_user_id(), 'shipping_country', true);
            } elseif(function_exists('wc_get_base_location')) {
                $wc_base_loc = wc_get_base_location();
                $country_val = isset($wc_base_loc['country']) ? $wc_base_loc['country'] : "";
            }
            if (isset($field->field_options->field_wcs_country_req) && $field->field_options->field_wcs_country_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            if(isset($attributes['value']))
                unset($attributes['value']);
            echo "<select " . $this->print_attributes($attributes) . " >";
            foreach(RM_Utilities_Revamp::get_countries() as $name => $country) {
                $ccode = strtolower(preg_replace('/.*\[(.*)\].*/', '$1', $name));
                if($name == $country_val || strpos($name, "[$country_val]")) {
                    echo "<option value=\"".esc_attr($name)."\" data-code=\"".esc_attr($ccode)."\" selected>".esc_html($country)."</option>";
                } else {
                    echo "<option value=\"".esc_attr($name)."\" data-code=\"".esc_attr($ccode)."\">".esc_html($country)."</option>";
                }
            }        
            echo "</select>";
            
            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_country_label";
            if (isset($field->field_options->field_wcs_country_req) && $field->field_options->field_wcs_country_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}country-error'></span>";
            echo "</div>";
            echo "</div>";
            // zip
            echo "<div class='rmform-col rmform-col-6'>";
            echo "<div class='rmform-field'>";
            $label_id = 'label_id_zip_' . $field->field_id;
            $input_id = 'input_id_zip_' . $field->field_id;
            $attributes['id'] = $input_id;
            $attributes['name'] = "wcshipping_".$field->field_id."[zip]";
            $attributes['aria-labelledby'] = $label_id;
            if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                $attributes['placeholder'] = $field_wcs_zip_label;
            }
            if(isset($old_value)) {
                if(isset($old_value['zip'])) {
                    $attributes['value'] = $old_value['zip'];
                } else {
                    $attributes['value'] = "";
                }
            } elseif (is_user_logged_in()) {
                $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_postcode', true);
            }

            if (isset($field->field_options->field_wcs_zip_req) && $field->field_options->field_wcs_zip_req == 1){
                $attributes['required'] = 'required';
                $attributes['aria-required'] = 'true';
            } else {
                if (isset($attributes['required'])) {
                    unset($attributes['required']);
                }
                if (isset($attributes['aria-required'])) {
                    unset($attributes['aria-required']);
                }
            }
            echo "<input " . $this->print_attributes($attributes) . " >";

            $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_zip_label";
            if (isset($field->field_options->field_wcs_zip_req) && $field->field_options->field_wcs_zip_req == 1) {
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $label .= "</label>";
            echo $label;

            echo "<span class='rmform-error-message' id='rmform-{$error_span_id}zip-error'></span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        } else {
            // country
            if (isset($field_wcs_country_en) && $field_wcs_country_en == "1") {
                $country_val = "";
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_country_label_' . $field->field_id;
                $input_id = 'input_id_country_label_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcshipping_".$field->field_id."[country]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcs_country_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['country'])) {
                        $country_val = $old_value['country'];
                    }
                } elseif (is_user_logged_in()) {
                    $country_val = get_user_meta(get_current_user_id(), 'shipping_country', true);
                } elseif(function_exists('wc_get_base_location')) {
                    $wc_base_loc = wc_get_base_location();
                    $country_val = isset($wc_base_loc['country']) ? $wc_base_loc['country'] : "";
                }
                if (isset($field->field_options->field_wcs_country_req) && $field->field_options->field_wcs_country_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                if(isset($attributes['value']))
                    unset($attributes['value']);
                echo "<select " . $this->print_attributes($attributes) . " >";
                foreach(RM_Utilities_Revamp::get_countries() as $name => $country) {
                    $ccode = strtolower(preg_replace('/.*\[(.*)\].*/', '$1', $name));
                    if($name == $country_val || strpos($name, "[$country_val]")) {
                        echo "<option value=\"".esc_attr($name)."\" data-code=\"".esc_attr($ccode)."\" selected>".esc_html($country)."</option>";
                    } else {
                        echo "<option value=\"".esc_attr($name)."\" data-code=\"".esc_attr($ccode)."\">".esc_html($country)."</option>";
                    }
                }        
                echo "</select>";
                
                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_country_label";
                if (isset($field->field_options->field_wcs_country_req) && $field->field_options->field_wcs_country_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}country-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            // zip
            if (isset($field_wcs_zip_en) && $field_wcs_zip_en == "1") {
                echo "<div class='rmform-row'>";
                echo "<div class='rmform-row-field-wrap'>";
                echo "<div class='rmform-col rmform-col-12'>";
                echo "<div class='rmform-field'>";
                $label_id = 'label_id_zip_' . $field->field_id;
                $input_id = 'input_id_zip_' . $field->field_id;
                $attributes['id'] = $input_id;
                $attributes['name'] = "wcshipping_".$field->field_id."[zip]";
                $attributes['aria-labelledby'] = $label_id;
                if (isset($field->field_options->field_wcs_label_as_placeholder) && $field->field_options->field_wcs_label_as_placeholder == '1'){
                    $attributes['placeholder'] = $field_wcs_zip_label;
                }
                if(isset($old_value)) {
                    if(isset($old_value['zip'])) {
                        $attributes['value'] = $old_value['zip'];
                    } else {
                        $attributes['value'] = "";
                    }
                } elseif (is_user_logged_in()) {
                    $attributes['value'] = get_user_meta(get_current_user_id(), 'shipping_postcode', true);
                }

                if (isset($field->field_options->field_wcs_zip_req) && $field->field_options->field_wcs_zip_req == 1){
                    $attributes['required'] = 'required';
                    $attributes['aria-required'] = 'true';
                } else {
                    if (isset($attributes['required'])) {
                        unset($attributes['required']);
                    }
                    if (isset($attributes['aria-required'])) {
                        unset($attributes['aria-required']);
                    }
                }
                echo "<input " . $this->print_attributes($attributes) . " >";

                $label = "<label for='$input_id' id='$label_id' class='rmform-label rmform-label-address'> $field_wcs_zip_label";
                if (isset($field->field_options->field_wcs_zip_req) && $field->field_options->field_wcs_zip_req == 1) {
                    $label .= "<span class='rmform-req-symbol'>*</span>";
                }
                $label .= "</label>";
                echo $label;

                echo "<span class='rmform-error-message' id='rmform-{$error_span_id}zip-error'></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        }

        $error_span_id = strtolower($field->field_type)."_{$field->field_id}-error";
        echo "<span class='rmform-error-message' id='rmform-".wp_kses_post((string)$error_span_id)."'></span>";

        echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "</div>";
        }

        // Adding state change script
        echo "<script>
        jQuery(document).ready(function () {
            if(jQuery(\"[name='wcshipping_".esc_js((string)$field->field_id)."[country]']\").length && jQuery(\"[name='wcshipping_".esc_js((string)$field->field_id)."[state]']\").length) {
                jQuery(\"[name='wcshipping_".esc_js((string)$field->field_id)."[country]']\").change(function () {
                    if(jQuery(this).val() != '') {
                        jQuery('#wcshipping_".esc_js((string)$field->field_id)."_state').children().first().replaceWith('<div>".esc_html__('Loading States...', 'custom-registration-form-builder-with-submission-manager')."</div>');
                        var data = {
                            'action': 'rm_get_state',
                            'rm_sec_nonce': '".wp_create_nonce('rm_ajax_secure')."',
                            'rm_slug': 'rm_get_state',
                            'country': jQuery(this).val(),
                            'def_state': '".esc_js((string)$def_state)."',
                            'attr': 'data-rm-state-val',
                            'form_id': '".esc_js((string)$field->form_id)."',
                            'state_field_id': 'wcshipping_".esc_js((string)$field->field_id).'_state'."',
                            'type': 'shipping'
                        };
                        rm_get_state(this, '".admin_url('admin-ajax.php')."', data);
                    }
                });
                jQuery(\"[name='wcshipping_".esc_js((string)$field->field_id)."[country]']\").trigger('change');
            }
        });
        </script>";
    }

    public function create_wcbillingphone_field($field = null, $ex_sub_id = 0) {
        if (!defined('REGMAGIC_ADDON') || !class_exists('WooCommerce')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_placeholder)){
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        if (isset($field->field_options->field_css_class)){
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        if(!empty($ex_sub_id)) {
            global $wpdb;
            $attributes['value'] = (string)$wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);
        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label =  "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1){
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = "required";
            $attributes['aria-required'] = "true";
        }
        $label .= "</label>";
        echo $label;
        echo "<input ".$this->print_attributes($attributes)." >";
    }

    public function create_pricev_field($field = null, $ex_sub_id = 0) {
        $total_price_localized_string = esc_html__('Total Price: %s', 'custom-registration-form-builder-with-submission-manager');
        $curr_pos = get_option('rm_option_currency_symbol_position', 'before');
        $curr_symbol = RM_Utilities_Revamp::get_currency_symbol(get_option('rm_option_currency', 'USD'));
        $price_formatting_data = wp_json_encode(array("loc_total_text" => $total_price_localized_string, "symbol" => $curr_symbol, "pos" => $curr_pos));
        echo "<div class='rmrow rm-total-price-widget {$field->field_options->field_css_class}' data-rmpriceformat='{$price_formatting_data}'></div>";
    }

    public function create_subcountv_field($field = null, $ex_sub_id = 0){
        $exp_str = RM_Utilities_Revamp::get_form_expiry_message($field->form_id);
        echo "<div class='rmrow rm_expiry_stat_container {$field->field_options->field_css_class}'>{$exp_str}</div>";
    }
    
    public function create_DigitalSign_field($field = null) {
        if(!defined('REGMAGIC_ADDON')) {
            return;
        }
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;

        $attributes = array(
            'type' => 'text',
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control rm-form-hidden-signature',
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id
        );
        
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';

            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
        }
        $label .= "</label>";
        echo $label;
        //echo "<input " . $this->print_attributes($attributes) . " >";
        ?>
        
        <div id="<?php echo $input_id;?>-capture" class="rm-sign-canvas">
            <div id="<?php echo $input_id;?>-clear" class="rm-sign-clear">
            <span class="material-icons">close</span>
        </div>
        </div>
        
	
        <?php
        echo "<input " . $this->print_attributes($attributes) . " >";
        
        wp_enqueue_style("rm-signature-style", RM_BASE_URL . "public/css/jquery.signature.css");
        //wp_enqueue_script('rm-jquery-touchpad');
        //wp_enqueue_script('rm-jquery-signature');
        wp_enqueue_script('rm-jquery-touchpad', RM_BASE_URL.'public/js/jquery.ui.touch-punch.js',['jquery', 'jquery-ui-core', 'jquery-ui-mouse']);
        wp_enqueue_script('rm-jquery-signature', RM_BASE_URL.'public/js/jquery.signature.js', ['jquery', 'jquery-ui-core', 'jquery-ui-mouse'], RM_PLUGIN_VERSION, false);
    }
    
    public function create_Profilegridgroups_field($field=null, $ex_sub_id = 0){
        if(!class_exists('PM_DBhandler')){
            return;
        }
        $dbhandler = new PM_DBhandler();
        $input_id = 'input_id_'.$field->field_type . '_' . $field->field_id;
        $label_id = 'label_id_'.$field->field_type . '_' . $field->field_id;
        $meta_value = "";
        if(!empty($ex_sub_id)) {
            global $wpdb;
            $old_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE submission_id = %d AND field_id = %d AND form_id = %d", $ex_sub_id, $field->field_id, $field->form_id));
        }
        $attributes = array(
            'name' => $field->field_type . '_' . $field->field_id.'[]',
            'class' => 'rmform-control '. 'select_'.$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'id' => $input_id,
            'aria-labelledby' => $label_id,
        );
        $main_label_attributes = array(
            'for' => $input_id,
            'id' => $label_id,
            'class' => 'rmform-label'
        );
        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }
        if (isset($field->field_options->field_placeholder)) {
            $attributes['placeholder'] = $field->field_options->field_placeholder;
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        if(isset($old_value)) {
            $meta_value = $old_value;
        } elseif(isset($field->field_options->field_default_value)) {
            $meta_value = $field->field_options->field_default_value;
        }

        if (is_user_logged_in() && isset($field->field_options->field_user_profile) && !isset($old_value)) {
            if ( $field->field_options->field_user_profile == 'existing_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->existing_user_meta_key, true);
            } elseif ( $field->field_options->field_user_profile == 'define_new_user_meta') {
                $meta_value = get_user_meta(get_current_user_id(), $field->field_options->field_meta_add, true);
            }
        }
        
        $groups   = $dbhandler->get_all_result( 'GROUPS', array( 'id', 'group_name' ) );
        $label = "<label ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";

        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</label>";
        echo $label;
        echo "<select ".wp_kses_post($this->print_attributes($attributes))." multiple>";
        if (isset($field->field_options->field_select_label)) {
            $default = $field->field_options->field_select_label;
            echo "<option value=''>".esc_html(trim($default))."</option>";
        }

        foreach($groups as $group) {
            $group_name = trim($group->group_name);
            if($group_name === null || $group_name === false || $group_name === '') {
                continue;
            }
            if($meta_value == $group->id) {
                echo "<option value='".esc_attr($group->id)."' selected>".esc_html($group_name)."</option>";
            }else{
                echo "<option value='".esc_attr($group->id)."'>".esc_html($group_name)."</option>";
            }
        }

        echo "</select>";
        wp_enqueue_script('rm_select2',RM_BASE_URL.'public/js/script_rm_select2.js', array('jquery'));
        wp_enqueue_style('rm_select2',RM_BASE_URL.'public/css/style_rm_select2.css');
        echo '<script>jQuery(document).ready(function() {jQuery(".select_'.esc_attr($field->field_id).'").select2();});</script>';
    }
    
    public function create_subscription_field($field = null) {
        if (!defined('REGMAGIC_ADDON') || !class_exists('RMSubscriptions')) {
            return;
        }
        $service = new RMSubscriptions_Service();
        
        $selected_plans = isset($field->field_options->subscription_plans) ? maybe_unserialize($field->field_options->subscription_plans) : array();
        
        $checked = "";
        $attributes = array (
            'name' => $field->field_type . '_' . $field->field_id,
            'class' => 'rmform-control '. 'radio_'.$field->field_id,
            'aria-describedby'=>'rm-note-'.$field->field_id,
            'type' => 'radio',
            //'onchange' => 'rmToggleOtherText(this)'
        );
        
        $main_label_attributes = array(
            'class' => 'rmform-label'
        );
        $secondary_label_attributes = array(
            'class' => 'rmform-label rmform-radio-check'
        );

        $icon = isset($field->field_options->icon) && $field->field_options->icon->codepoint ? $this->field_icon($field->field_options->icon) : "";

        if (isset($field->field_options->field_css_class)) {
            $attributes['class'] .= " ".$field->field_options->field_css_class;
        }

        // conditional attributes
        $attributes = $this->conditional_attributes($attributes, $field);


        $label = "<span ".$this->print_attributes($main_label_attributes).">$icon {$field->field_label}";
        if (isset($field->field_options->field_is_required) && $field->field_options->field_is_required == 1) {
            $astrick = get_option('rm_option_show_asterix');
            if(isset($astrick) && $astrick == "yes"){
                $label .= "<span class='rmform-req-symbol'>*</span>";
            }
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        $label .= "</span>";
        echo $label;

        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "<div class='rmform-control-wrap'>";
        }
        
        $count = 0;
        
        $subscription_plans = array();
        if(class_exists('RMSubscriptions')){
            $subscription_plans = RMSubscriptions_DBManager::get_subscription_list_dropdown();
            
        }
        $subscription_plan_exist = false;
        if(!empty($subscription_plans) && !empty($selected_plans)){
            foreach($subscription_plans as $plan){
                if(in_array($plan['id'], $selected_plans)){
                    $subscription_plan_exist = true;
                    break;
                }
            }
        }
        
        if(!empty($subscription_plans) && !empty($selected_plans) && !empty($subscription_plan_exist)){
            echo "<div class='rm-subscription-cards'>";
            foreach($subscription_plans as $plan) {
                if(in_array($plan['id'], $selected_plans)){
                    $plan_details = $plan['details'];
                    
                    $attributes['id'] = $field->field_type . '_' . $field->field_id."_".$count;
                    $attributes['value'] = esc_attr(trim($plan['id']));
                    $attributes['aria-labelledby'] = 'label_id_'.$field->field_type . '_' . $field->field_id.'_'.$count;
                    
                    echo "<label class='rmform-check' for='" . esc_attr($attributes['id']) . "'>";
                    
                    echo "<input ".$this->print_attributes($attributes).">";
                    
                    $secondary_label_attributes['id'] = 'label_id_'.$field->field_type ."_".$field->field_id.'_'.$count;
                    echo "<div " . $this->print_attributes($secondary_label_attributes) . " >";
                    echo "<div class='rm-sub-plan-name'>".esc_attr($plan['name'])."</div>";
                    $service->formated_price_with_message($plan_details);
                    
                    echo "</div>";
                    echo "</label>";
                    
                    $count++;
                }
            }
            echo "</div>";
        }

        if($count){
            echo "<div id='rm-note-".wp_kses_post((string)$field->field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";
        }
        if (get_option('rm_option_form_layout', 'label_top') == "label_left") {
            echo "</div>";
        }

        wp_enqueue_script( 'rm-new-frontend-field', RM_BASE_URL.'public/js/new_frontend_field.js', array('jquery','jquery-ui-datepicker'));
    }
}
