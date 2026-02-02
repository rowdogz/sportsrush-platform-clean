<?php
//Class to create form on the frontend (Revamp Project)
final class RM_Form_Factory_Revamp {
    public function __construct() {}

    private function render_submit_button($form = null, $has_pages = false, $price_fields = 0, $prefilled = false, $ex_sub_id = 0) {
        if($prefilled && $ex_sub_id > 0) {
            $submission = new RM_Submissions();
            $submission->load_from_db($ex_sub_id);
            if($submission->is_pending > 0) {
                $form->form_options->form_submit_btn_label = esc_html__('Submit', 'custom-registration-form-builder-with-submission-manager');
            } else {
                $form->form_options->form_submit_btn_label = esc_html__('Update', 'custom-registration-form-builder-with-submission-manager');
            }
        } else {
            $form->form_options->form_submit_btn_label = empty($form->form_options->form_submit_btn_label) ? esc_html__('Submit', 'custom-registration-form-builder-with-submission-manager') : $form->form_options->form_submit_btn_label;
        }
        $form->form_options->form_next_btn_label = empty($form->form_options->form_next_btn_label) ? esc_html__('Next', 'custom-registration-form-builder-with-submission-manager') : $form->form_options->form_next_btn_label;
        $form->form_options->form_prev_btn_label = empty($form->form_options->form_prev_btn_label) ? esc_html__('Prev', 'custom-registration-form-builder-with-submission-manager') : $form->form_options->form_prev_btn_label;
        $form->form_options->form_btn_align = empty($form->form_options->form_btn_align) ? 'center' : $form->form_options->form_btn_align;
        echo "<div id='rm_form_submit_button' class='rm-form-btn-align-".wp_kses_post((string)$form->form_options->form_btn_align)."'>";
        if($has_pages) {
            if(!$form->form_options->no_prev_button) {
                echo "<input type='button' id='rm-form-prev-btn' value='".wp_kses_post((string)$form->form_options->form_prev_btn_label)."' style='display:none'>";
            }
            echo "<input type='button' id='rm-form-next-btn' value='".wp_kses_post((string)$form->form_options->form_next_btn_label)."'>";

            if(!empty($form->form_options->save_submission_enabled) && defined('RM_SAVE_SUBMISSION_BASENAME')) {
                if($price_fields > 0) {
                    echo "<input type='submit' id='rm-form-save-btn' value='".esc_html__('Save & Exit', 'custom-registration-form-builder-with-submission-manager')."' style='display:none'>";
                } else {
                    echo "<input type='submit' id='rm-form-save-btn' value='".esc_html__('Save & Exit', 'custom-registration-form-builder-with-submission-manager')."'>";   
                }
            }
            echo "<input type='submit' id='rm-form-submit-btn' value='".wp_kses_post((string)$form->form_options->form_submit_btn_label)."' style='display:none'>";
        } else {
            if(!empty($form->form_options->save_submission_enabled) && defined('RM_SAVE_SUBMISSION_BASENAME')) {
                if($price_fields > 0) {
                    echo "<input type='submit' id='rm-form-save-btn' value='".esc_html__('Save & Exit', 'custom-registration-form-builder-with-submission-manager')."' style='display:none'>";
                } else {
                    echo "<input type='submit' id='rm-form-save-btn' value='".esc_html__('Save & Exit', 'custom-registration-form-builder-with-submission-manager')."'>";   
                }
            }
            echo "<input type='submit' id='rm-form-submit-btn' value='".wp_kses_post((string)$form->form_options->form_submit_btn_label)."'>";
        }
        echo "</div>";
    }

    private function save_submission($sub_data = array(), $form = null, $form_no = null, $prefilled = false, $submission_id = null) {
        // Getting form ID
        $form_id = absint($form->form_id);
        
        // Checking valid submission for this form
        if(!isset($sub_data['form_id']) || !isset($sub_data['form_no']) || absint($sub_data['form_id']) != $form_id || absint($sub_data['form_no']) != $form_no) {
            //esc_html_e('Invalid form submission','custom-registration-form-builder-with-submission-manager');
            return 'ignore';
        }

        // Defining important variables
        global $wpdb;
        $form->rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_rows WHERE form_id = %d ORDER BY page_no, row_order ASC", $form_id));
        if($prefilled) {
            if(current_user_can('manage_options')) {
                $form->fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_fields WHERE form_id = %d AND field_type NOT IN ('Price', 'Subscription') AND is_field_primary = 0", $form_id), OBJECT_K);
            } else {
                if(isset($form->form_options->save_submission_enabled) && !empty($form->form_options->save_submission_enabled) && defined('RM_SAVE_SUBMISSION_BASENAME')) {
                    $form->fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_fields WHERE form_id = %d AND field_type NOT IN ('Price', 'Subscription') AND is_field_primary = 0", $form_id), OBJECT_K);
                } else {
                    $form->fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_fields WHERE form_id = %d AND field_type NOT IN ('Price', 'Subscription') AND is_field_primary = 0 AND field_is_editable = 1", $form_id), OBJECT_K);
                }
            }
        } else {
            $form->fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_fields WHERE form_id = %d", $form_id), OBJECT_K);
        }
        $db_data = array();
        $service = new RM_Front_Form_Service();
        $user_email = null;
        $username = null;
        $password = null;
        $errors = array();
        $has_price = false;
        $has_subscription = false;
        $subscription_id = null;
        $subscription_data = new stdClass();
        $pricing_details = new stdClass();
        $pricing_details->billing = array();
        $pricing_details->total_price = 0.00;
        $pricing_details->tax = 0.00;
        $user_meta_fields = array();
        $profile_meta_arr = array(
            'Fname' => 'first_name',
            'Lname' => 'last_name',
            'BInfo' => 'description',
            'Nickname' => 'nickname',
            'Website' => 'user_url',
            'SecEmail' => '',
            'PGAvatar' => 'pm_user_avatar',
        );
        $social_validation_arr = array(
            'Facebook' => "/(?:https?:\/\/)?(?:www\.)?facebook\.com\/(?:(?:\w)*#!\/)?(?:pages\/)?(?:[\w\-]*\/)*?(\/)?([\w\-\.]*)/",
            'Twitter' => "/(ftp|http|https):\/\/?((www|\w\w)\.)?twitter.com(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/",
            'Instagram' => "/(?:(?:http|https):\/\/)?(?:www.)?(?:instagram.com|instagr.am|instagr.com)\/(\w+)/",
            'Linked' => "/(ftp|http|https):\/\/?((www|\w\w)\.)?linkedin.com(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/",
            'Youtube' => "/(ftp|http|https):\/\/?((www|\w\w)\.)?youtube.com(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/",
            'VKontacte' => "/(ftp|http|https):\/\/?((www|\w\w)\.)?(vkontakte.com|vk.com)(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/",
            'Skype' => "/[a-zA-Z][a-zA-Z0-9_\-\,\.]{5,31}/",
            'SoundCloud' => "/(ftp|http|https):\/\/?((www|\w\w)\.)?soundcloud.com(\w+:{0,1}\w*@)?(\S+)(:([0-9])+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/",
        );
        $save_submission = isset($form->form_options->save_submission_enabled) && !empty($form->form_options->save_submission_enabled) && isset($sub_data['rm_save_submission']) && defined('RM_SAVE_SUBMISSION_BASENAME');
        
        // Collecting submission data in proper page sequence
        $has_pages = isset($form->form_options->form_pages) && is_array($form->form_options->form_pages) && count($form->form_options->form_pages) > 1 && defined('REGMAGIC_ADDON');
        if($has_pages) {
            if(!isset($form->form_options->ordered_form_pages) || empty($form->form_options->ordered_form_pages)) {
                $form->form_options->ordered_form_pages = array();
                foreach($form->form_options->form_pages as $page_k => $page_v) {
                    array_push($form->form_options->ordered_form_pages, $page_k);
                }
            }
            $rows_by_pages = $this->get_rows_by_pages($form->rows, $form->form_options->ordered_form_pages);
        } else {
            $rows_by_pages[0] = $form->rows;
        }

        foreach($rows_by_pages as $page_id => $row_page) {
            foreach($row_page as $row) {
                $fields_in_row = maybe_unserialize($row->field_ids);
                foreach($fields_in_row as $field_id) {
                    $field_id = absint($field_id);
                    if(empty($field_id) || !isset($form->fields[$field_id])) {
                        continue;
                    }
                    $form->fields[$field_id]->field_options = maybe_unserialize($form->fields[$field_id]->field_options);
                    $data_block = new stdClass();
                    $field_name = $form->fields[$field_id]->field_type.'_'.$field_id;
                    // Checking primary email field
                    if($form->fields[$field_id]->field_type == 'Email' && absint($form->fields[$field_id]->is_field_primary) == 1) {
                        if(isset($sub_data[$field_name]) && !empty($sub_data[$field_name])) {
                            $user_email = sanitize_email($sub_data[$field_name]);
                            if($user_email != $sub_data[$field_name]) {
                                array_push($errors, esc_html__('Email address contains invalid characters','custom-registration-form-builder-with-submission-manager'));
                            }
                            $data_block->label = $form->fields[$field_id]->field_label;
                            $data_block->value = $user_email;
                            $data_block->type = $form->fields[$field_id]->field_type;
                            $data_block->meta = null;
                            $db_data[$field_id] = $data_block;
                            if(!is_user_logged_in()) {
                                if(email_exists($user_email) && absint($form->form_type) == RM_REG_FORM) {
                                    array_push($errors, esc_html__('A user with this email address already exists','custom-registration-form-builder-with-submission-manager'));
                                }
                                if(isset($form->fields[$field_id]->field_options->en_confirm_email) && $form->fields[$field_id]->field_options->en_confirm_email == 1) {
                                    if(!isset($sub_data['email_confirmation']) || empty($sub_data['email_confirmation'])) {
                                        array_push($errors, esc_html__('Email confirmation is required','custom-registration-form-builder-with-submission-manager'));
                                    } else {
                                        if($user_email != sanitize_email($sub_data['email_confirmation'])) {
                                            array_push($errors, esc_html__('Emails are not matching','custom-registration-form-builder-with-submission-manager'));
                                        }
                                    }
                                }
                            }

                            if(!$prefilled) {
                                // Checking if submission is more than the limit
                                if(is_user_logged_in() && current_user_can('manage_option')) {
                                    
                                } else {
                                    $sub_count = absint($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}rm_submissions WHERE user_email = %s AND form_id = %d", $user_email, $form_id)));
                                    
                                    if(absint($form->form_options->sub_limit_ind_user) > 0 && $sub_count >= absint($form->form_options->sub_limit_ind_user)) {
                                        esc_html_e('You have reached your form submission limit','custom-registration-form-builder-with-submission-manager');
                                        return false;
                                    }
                                }

                                // Check limit by custom status
                                if(!$this->check_limit_by_cs($form,$user_email)) {
                                    if(!empty($form->form_options->form_message_after_expiry)) {
                                        echo wp_kses_post((string)$form->form_options->form_message_after_expiry);
                                    } else {
                                        esc_html_e('Submission can not be done as it is restricted by admin.', 'custom-registration-form-builder-with-submission-manager');
                                    }
                                    return false;
                                }

                                // Checking email ban
                                if($this->is_email_banned($user_email)) {
                                    $this->update_stat_entry(absint($sub_data['stat_id'], null, true));
                                    esc_html_e("You are banned from submitting the form.", 'custom-registration-form-builder-with-submission-manager');
                                    return false;
                                }

                                // Checking domain access control
                                if(defined('REGMAGIC_ADDON')) {
                                    $factrl = $form->form_options->access_control;
                                    if(!empty($factrl->domain)) {
                                        $domains = explode(',', (string)$factrl->domain);
                                        if(!empty($user_email)) {
                                            $parts = explode('@',(string)$user_email); // Separate string by @ characters (there should be only one)
                                            $domain = array_pop($parts); // Remove and return the last part, which should be the domain
                                            // Check if the domain is in our list
                                            if(!in_array($domain, $domains)) {
                                                $rep = apply_filters('rm_domain_access_submission', $status = 1);
                                                if(!empty($rep)) {
                                                    echo wp_kses_post((string)$factrl->fail_msg);
                                                    return;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            array_push($errors, esc_html__('Account email is a required field','custom-registration-form-builder-with-submission-manager'));
                        }
                    }
                    // Checking username field
                    else if($form->fields[$field_id]->field_type == 'Username') {
                        if(isset($sub_data['username'])) {
                            $username = sanitize_user($sub_data['username'], true);
                            $username_character_error = RM_Utilities::validate_username_characters($username,$form_id);
                            if(!empty($username_character_error)) {
                                array_push($errors, $username_character_error);
                            }
                            $data_block->label = $form->fields[$field_id]->field_label;
                            $data_block->value = $username;
                            $data_block->type = $form->fields[$field_id]->field_type;
                            $data_block->meta = null;
                            $db_data[$field_id] = $data_block;

                            if(!is_user_logged_in()) {
                                if(username_exists($username)) {
                                    array_push($errors, esc_html__('A user with this username already exists','custom-registration-form-builder-with-submission-manager'));
                                }
                            }

                            if(!$prefilled) {
                                if($this->is_username_reserved($username)) {
                                    array_push($errors, esc_html__('This username is reserved/blacklisted by the admin','custom-registration-form-builder-with-submission-manager'));
                                }
                            }
                        }
                    }
                    // Checking password field
                    else if($form->fields[$field_id]->field_type == 'UserPassword') {
                        if(!is_user_logged_in()) {
                            if(isset($sub_data['pwd'])) {
                                $password = trim($sub_data['pwd']);
                                if($form->fields[$field_id]->field_options->en_confirm_pwd) {
                                    if(!isset($sub_data['password_confirmation'])) {
                                        array_push($errors, esc_html__('Password confirmation is required','custom-registration-form-builder-with-submission-manager'));
                                    } else {
                                        if(empty($sub_data['password_confirmation'])) {
                                            array_push($errors, esc_html__('Password confirmation is required','custom-registration-form-builder-with-submission-manager'));
                                        }
                                        $confirm_password = trim($sub_data['password_confirmation']);
                                        if($password != $confirm_password) {
                                            array_push($errors, esc_html__('Password confirmation does not match','custom-registration-form-builder-with-submission-manager'));
                                        }
                                    }
                                }

                                $pass_rules_enabled = get_option('rm_option_enable_custom_pw_rests');
                                if($pass_rules_enabled == 'yes') {
                                    $pass_rules = get_option('rm_option_custom_pw_rests');
                                    if(isset($pass_rules->selected_rules)) {
                                        foreach($pass_rules->selected_rules as $rule) {
                                            switch($rule) {
                                                case 'PWR_UC':
                                                    if(!preg_match('/[A-Z]/', $sub_data['pwd'])) {
                                                        array_push($errors, esc_html__('Password must contain an uppercase letter','custom-registration-form-builder-with-submission-manager'));
                                                    }
                                                    break;
                                                case 'PWR_NUM':
                                                    if(!preg_match('/[0-9]/', $sub_data['pwd'])) {
                                                        array_push($errors, esc_html__('Password must contain a number','custom-registration-form-builder-with-submission-manager'));
                                                    }
                                                    break;
                                                case 'PWR_SC':
                                                    if(!preg_match('/[^A-Za-z0-9]/', $sub_data['pwd'])) {
                                                        array_push($errors, esc_html__('Password must contain a special character','custom-registration-form-builder-with-submission-manager'));
                                                    }
                                                    break;
                                                case 'PWR_MINLEN':
                                                    if(strlen($sub_data['pwd']) < absint($pass_rules->min_len)) {
                                                        array_push($errors, sprintf(esc_html__('Password must be at least %s characters long','custom-registration-form-builder-with-submission-manager'), $pass_rules->min_len));
                                                    }
                                                    break;
                                                case 'PWR_MAXLEN':
                                                    if(strlen($sub_data['pwd']) > absint($pass_rules->max_len)) {
                                                        array_push($errors, sprintf(esc_html__('Password must not be longer than %s characters','custom-registration-form-builder-with-submission-manager'), $pass_rules->max_len));
                                                    }
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }
                                    }
                                }
                            } else {
                                array_push($errors, esc_html__('Password is a required field','custom-registration-form-builder-with-submission-manager'));
                            }
                        }
                    }
                    // Handling file fields
                    else if($form->fields[$field_id]->field_type == 'File' || $form->fields[$field_id]->field_type == 'Image' || $form->fields[$field_id]->field_type == 'ESign' || $form->fields[$field_id]->field_type == 'PGAvatar') {
                        if(absint($form->fields[$field_id]->field_options->field_is_required) == 1 && (!isset($_FILES[$field_name]) || empty($_FILES[$field_name])) && !$save_submission) {
                            array_push($errors, sprintf(esc_html__('%s is a required field','custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                            continue;
                        } else if(!isset($_FILES[$field_name]) || empty($_FILES[$field_name]['name'][0])) {
                            continue;
                        }
                        $attachment_ids = array();
                        $attachment = new RM_Attachment_Service();
                        $default_allowed_exts = $form->fields[$field_id]->field_type == 'File' ? 'jpg|jpeg|png|gif|doc|pdf|docx|txt' : 'jpg|jpeg|png|gif';
                        $allowed_file_types = empty($form->fields[$field_id]->field_value) ? str_replace(' ','',(string)get_option('rm_option_allowed_file_types')) : str_replace(' ','',strtolower($form->fields[$field_id]->field_value));
                        $allowed_file_types = empty($allowed_file_types) ? explode('|',$default_allowed_exts) : explode('|',$allowed_file_types);
                        $file_size = intval(get_option('rm_option_file_size'));
                        $file_size_error = get_option('rm_option_file_size_error');
                        $files = $_FILES[$field_name];
                        
                        //Check for multifile field
                        if(is_array($_FILES[$field_name]['name'])) {
                            $original_files = $_FILES;
                            foreach($files['name'] as $key => $val) {
                                $file_name = sanitize_file_name($val);
                                if(empty($file_name)) {
                                    if(absint($form->fields[$field_id]->field_options->field_is_required) == 1 && !$save_submission) {
                                        array_push($errors, sprintf(esc_html__('%s is a required field','custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                    }
                                } else {
                                    // Error for file field
                                    $file_extension = explode('.', $file_name);
                                    if(isset($file_extension[1])) {
                                        $ext = $file_extension[count($file_extension)-1];
                                        if(!in_array(strtoupper($ext), $allowed_file_types) && !in_array(strtolower($ext), $allowed_file_types)) {
                                            array_push($errors, sprintf(esc_html__('%s file type is not allowed for upload.','custom-registration-form-builder-with-submission-manager'), trim(strtoupper($ext))));
                                        }
                                    } else {
                                        array_push($errors, esc_html__('Invalid file upload.','custom-registration-form-builder-with-submission-manager'));
                                    }

                                    if(!empty($file_size) && (intval(round($_FILES[$field_name]['size'][$key]/1024))) > $file_size) {
                                        if(!empty($file_size_error))
                                            array_push($errors, $file_size_error);
                                        else
                                            array_push($errors, sprintf(esc_html__('%s exceeds the file size limit of %d kBs', 'custom-registration-form-builder-with-submission-manager'), $field_name, $file_size));
                                    }
                                }
                                if($files['name'][$key] && empty($errors)) {
                                    $file = array( 
                                        'name' => sanitize_file_name($file_name),
                                        'type' => $files['type'][$key], 
                                        'tmp_name' => sanitize_text_field($files['tmp_name'][$key]), 
                                        'error' => $files['error'][$key],
                                        'size' => $files['size'][$key]
                                    );
                                    $_FILES = array($field_name => $file); 
                                    foreach($_FILES as $file => $array) {
                                        $aid =  $attachment->media_handle_attachment($file, 0);             
                                        if(is_wp_error($aid))
                                            break;
                                        else
                                            $attachment_ids[$field_name][] = $aid;
                                    }
                                } 
                            }
                            $_FILES = $original_files;
                        } else {
                            $file_name = sanitize_file_name($_FILES[$field_name]['name']);
                            if(empty($file_name)) {
                                if(absint($form->fields[$field_id]->field_options->field_is_required) == 1 && !$save_submission) {
                                    array_push($errors, sprintf(esc_html__('%s is a required field','custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                }
                            } else {
                                // Error for file field
                                $file_extension = explode('.', $file_name);
                                if(isset($file_extension[1])) {
                                    $ext = $file_extension[count($file_extension)-1];
                                    if(!in_array(strtoupper($ext), $allowed_file_types) && !in_array(strtolower($ext), $allowed_file_types)) {
                                        array_push($errors, sprintf(esc_html__('%s file type is not allowed for upload.','custom-registration-form-builder-with-submission-manager'), trim(strtoupper($ext))));
                                    }
                                } else {
                                    array_push($errors, esc_html__('Invalid file upload.','custom-registration-form-builder-with-submission-manager'));
                                }

                                if(!empty($file_size) && (intval(round($_FILES[$field_name]['size']/1024))) > $file_size) {
                                    if(!empty($file_size_error))
                                        array_push($errors, $file_size_error);
                                    else
                                        array_push($errors, sprintf(esc_html__('%s exceeds the file size limit of %d kBs', 'custom-registration-form-builder-with-submission-manager'), $field_name, $file_size));
                                }
                            }
                            if(empty($errors)) {
                                $aid = $attachment->media_handle_attachment($field_name, 0);
                                if(is_wp_error($aid))
                                    break;
                                else
                                    $attachment_ids[$field_name] = $aid;
                            }
                        }
                        $value = null;
                        if(is_array($attachment_ids) || is_object($attachment_ids)) {
                            foreach($attachment_ids as $att_field_name => $att_id) {
                                $value = array();
                                if($att_field_name == $field_name) {
                                    $value['rm_field_type'] = 'File';
                                    if(is_array($att_id))
                                        foreach ($att_id as $abc)
                                            $value[] = $abc;
                                    else
                                        $value[] = $att_id;
                                }
                            }
                        }
                        $data_block->label = $form->fields[$field_id]->field_label;
                        $data_block->value = $value;
                        $data_block->type = $form->fields[$field_id]->field_type;
                        $data_block->meta = null;
                        $db_data[$field_id] = $data_block;

                        if(isset($data_block->value[0])) {
                            if($form->fields[$field_id]->field_type == 'PGAvatar') {
                                $user_meta_fields[$profile_meta_arr[$form->fields[$field_id]->field_type]] = $data_block->value[0];
                            } else {
                                $file_meta_key = isset($form->fields[$field_id]->field_options->field_user_profile) && $form->fields[$field_id]->field_options->field_user_profile == 'existing_user_meta' ? $form->fields[$field_id]->field_options->existing_user_meta_key : (isset($form->fields[$field_id]->field_options->field_user_profile) && $form->fields[$field_id]->field_options->field_user_profile == 'define_new_user_meta' ? $form->fields[$field_id]->field_options->field_meta_add : null);
                                if(!empty($file_meta_key)) {
                                    if(count($data_block->value) > 2) {
                                        foreach($data_block->value as $key => $value) {
                                            if($key !== 'rm_field_type')
                                                $user_meta_fields[$file_meta_key][] = wp_get_attachment_url($value);
                                        }
                                    } else {
                                        $user_meta_fields[$file_meta_key] = wp_get_attachment_url($data_block->value[0]);
                                    }
                                }
                            }
                        }
                    } // Handling price fields
                    else if($form->fields[$field_id]->field_type == 'Price') {
                        $has_price = true;
                        $price_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_paypal_fields WHERE field_id = %d", $form->fields[$field_id]->field_value));
                        if(empty($price_field))
                            continue;
                        $price_field_name = $field_name."_{$price_field->field_id}";
                        $curr_pos = get_option('rm_option_currency_symbol_position', 'before');
                        $curr_sym = RM_Utilities_Revamp::get_currency_symbol(get_option('rm_option_currency', 'USD'));
                        if(isset($sub_data[$price_field_name]) && !empty($sub_data[$price_field_name])) {
                            $price_field->extra_options = maybe_unserialize($price_field->extra_options);
                            switch($price_field->type) {
                                case "fixed":
                                    if(isset($sub_data[$price_field_name."_qty"]) && intval($sub_data[$price_field_name."_qty"]) > -1)
                                        $quantity = intval($sub_data[$price_field_name."_qty"]);
                                    else
                                        $quantity = 1;
                                    
                                    $price = floatval($price_field->value);
                                    $pricing_details->total_price += $price * $quantity;
                                    $tmp_billing = (object) array('label'=>$price_field->name, 'price'=>$price, 'qty'=>$quantity);
                                    $pricing_details->billing[] = apply_filters('rm_field_product_billing_'.$price_field->field_id, $tmp_billing);

                                    $data_block->label = $form->fields[$field_id]->field_label;
                                    $data_block->value = $curr_pos == 'before' ? "{$price_field->name} ({$curr_sym}{$price}) &times; $quantity" : "{$price_field->name} ({$price}{$curr_sym}) &times; $quantity";
                                    $data_block->type = $form->fields[$field_id]->field_type;
                                    $data_block->meta = null;
                                    $db_data[$field_id] = $data_block;
                                    break;
                                case "userdef":
                                    if(defined('REGMAGIC_ADDON')) {
                                        $total_price = floatval($sub_data[$price_field_name]);
                                        $pricing_details->total_price += round($total_price, 2);
                                        $pricing_details->billing[] = (object) array('label'=>$price_field->name, 'price'=>$total_price, 'qty' => 1);
                                        
                                        $data_block->label = $form->fields[$field_id]->field_label;
                                        $data_block->value = $curr_pos == 'before' ? "{$price_field->name} ({$curr_sym}{$total_price})" : "{$price_field->name} ({$total_price}{$curr_sym})";
                                        $data_block->type = $form->fields[$field_id]->field_type;
                                        $data_block->meta = null;
                                        $db_data[$field_id] = $data_block;
                                    }
                                    break;
                                case "multisel":
                                    if(defined('REGMAGIC_ADDON')) {
                                        $tmp_v = maybe_unserialize($price_field->option_price);
                                        $tmp_l = maybe_unserialize($price_field->option_label);
                                        $price_val_arr = array();
                                        foreach($sub_data[$price_field_name] as $pf_single_val) {
                                            $index = (int)substr($pf_single_val, 1);
                                            if(!isset($tmp_v[$index]))
                                                continue;
                                            
                                            if(isset($sub_data[$price_field_name."_qty"], $sub_data[$price_field_name."_qty"][$pf_single_val]) && intval($sub_data[$price_field_name."_qty"][$pf_single_val]) > -1)
                                                $quantity = intval($sub_data[$price_field_name."_qty"][$pf_single_val]);
                                            else
                                                $quantity = 1;
                                            
                                            $pricing_details->total_price += floatval($tmp_v[$index]) * $quantity;
                                            $pricing_details->billing[] = (object)array('label'=>$tmp_l[$index], 'price'=>floatval($tmp_v[$index]),'qty' => $quantity);
                                            $price_val_arr[] = $curr_pos == 'before' ? "{$tmp_l[$index]} ({$curr_sym}{$tmp_v[$index]}) &times; $quantity" : "{$tmp_l[$index]} ({$tmp_v[$index]}{$curr_sym}) &times; $quantity"; 
                                        }

                                        $data_block->label = $form->fields[$field_id]->field_label;
                                        $data_block->value = $price_val_arr;
                                        $data_block->type = $form->fields[$field_id]->field_type;
                                        $data_block->meta = null;
                                        $db_data[$field_id] = $data_block;
                                    }
                                    break;
                                case "dropdown":
                                    if(defined('REGMAGIC_ADDON')) {
                                        $tmp_v = maybe_unserialize($price_field->option_price);
                                        $tmp_l = maybe_unserialize($price_field->option_label);
                                        $index = (int) substr($sub_data[$price_field_name], 1);
                                        if (!isset($tmp_v[$index]))
                                            break;
                                        
                                        if(isset($sub_data[$price_field_name."_qty"]) && intval($sub_data[$price_field_name."_qty"]) > -1)
                                            $quantity = intval($sub_data[$price_field_name."_qty"]);
                                        else
                                            $quantity = 1;
                                        
                                        $pricing_details->total_price += floatval($tmp_v[$index]) * $quantity;
                                        $pricing_details->billing[] = (object)array('label'=>$tmp_l[$index], 'price'=>floatval($tmp_v[$index]), 'qty' => $quantity);
                                        
                                        $data_block->label = $form->fields[$field_id]->field_label;
                                        $data_block->value = $curr_pos == 'before' ? "{$tmp_l[$index]} ({$curr_sym}{$tmp_v[$index]}) &times; $quantity" : "{$tmp_l[$index]} ({$tmp_v[$index]}{$curr_sym}) &times; $quantity";
                                        $data_block->type = $form->fields[$field_id]->field_type;
                                        $data_block->meta = null;
                                        $db_data[$field_id] = $data_block;
                                    }
                                    break;
                            }
                        }
                    }else if($form->fields[$field_id]->field_type == 'Subscription'){
                        if (defined('REGMAGIC_ADDON') && class_exists('RMSubscriptions')) {
                            if(isset($sub_data[$field_name])){
                                $has_subscription = true;
                                $subscription_id = $sub_data[$field_name];
                                $subscription_data->id = $subscription_id;

                                $data_block->label = $form->fields[$field_id]->field_label;
                                $data_block->value = $sub_data[$field_name];
                                $data_block->type = $form->fields[$field_id]->field_type;
                                $data_block->meta = null;
                                $db_data[$field_id] = $data_block;
                            }
                        }
                        
                        
                        
                    } else if($form->fields[$field_id]->field_type == 'DigitalSign'){
                        if(defined('REGMAGIC_ADDON') && isset($sub_data[$field_name]) && absint($form->fields[$field_id]->field_options->field_is_required) == 1 && empty($sub_data[$field_name])) {
                            array_push($errors, sprintf(esc_html__('%s is a required field','custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                            continue;
                        }else if(!empty($sub_data[$field_name])){
                            $attachment_ids = array();
                            $attachment = new RM_Attachment_Service();
                            $signature_name = $attachment->save_base64_image($sub_data[$field_name],'rm-signature','png');
                            
                            $data_block->label = $form->fields[$field_id]->field_label;
                            //$data_block->value = $signature_id;
                            $data_block->value = $signature_name;
                            $data_block->type = $form->fields[$field_id]->field_type;
                            $data_block->meta = null;
                            $db_data[$field_id] = $data_block;
                        }
                        
                    } else {
                        if(in_array($form->fields[$field_id]->field_type, array('WCBilling','WCShipping'))) {
                            $field_name = strtolower($field_name);
                        }
                        if(isset($sub_data[$field_name])) {
                            // Validating social fields
                            if(!empty($sub_data[$field_name])) {
                                foreach($social_validation_arr as $social_k => $social_v) {
                                    if($form->fields[$field_id]->field_type == $social_k) {
                                        if(!preg_match($social_v, $sub_data[$field_name])) {
                                            array_push($errors, sprintf(esc_html__('Incorrect format provided for %s field', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                        }
                                    }
                                }
                            }

                            // Validating number field
                            if($form->fields[$field_id]->field_type == 'Number') {
                                if(!empty($sub_data[$field_name])) {
                                    if(!is_numeric($sub_data[$field_name])) {
                                        array_push($errors, sprintf(esc_html__('%s must be a number', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                    }
                                }
                            }

                            // Validating custom field
                            if($form->fields[$field_id]->field_type == 'Custom') {
                                if(!empty($sub_data[$field_name])) {
                                    $sub_data[$field_name] = sanitize_text_field(stripslashes($sub_data[$field_name]));
                                    if($form->fields[$field_id]->field_options->field_validation == 'custom') {
                                        if(preg_match('/'.$form->fields[$field_id]->field_options->custom_validation.'/', $sub_data[$field_name]) == 0) {
                                            array_push($errors, sprintf(esc_html__('Incorrect format provided for %s field', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                        }
                                    } else {
                                        $valid_ex = empty($form->fields[$field_id]->field_options->field_validation) ? '/^[\p{L}0-9 \'".,():\/&!?-]+$/u' : '/'.$form->fields[$field_id]->field_options->field_validation.'/';
                                        if(preg_match($valid_ex, $sub_data[$field_name]) == 0) {
                                            array_push($errors, sprintf(esc_html__('Incorrect format provided for %s field', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                        }
                                    }
                                }
                            }

                            // Validating Website and URL fields
                            if($form->fields[$field_id]->field_type == 'URL' || $form->fields[$field_id]->field_type == 'Website') {
                                if(!empty($sub_data[$field_name])) {
                                    if(!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/[^\s]*)?$/', $sub_data[$field_name])) {
                                        array_push($errors, sprintf(esc_html__('Incorrect Website/URL format provided for %s field', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                    }
                                }
                            }
                            
                            $data_block->label = $form->fields[$field_id]->field_label;
                            if(is_array($sub_data[$field_name])) {
                                foreach($sub_data[$field_name] as $field_key => $field_val) {
                                    if(empty($field_val)) {
                                        unset($sub_data[$field_name][$field_key]);
                                    } else {
                                        $sub_data[$field_name][$field_key] = sanitize_text_field((string)$field_val);
                                    }
                                }
                                if($form->fields[$field_id]->field_type == 'Checkbox') {
                                    if(isset($sub_data[$field_name.'_other_input'])) {
                                        array_push($sub_data[$field_name], sanitize_text_field($sub_data[$field_name.'_other_input']));
                                        unset($sub_data[$field_name.'_other_input']);
                                    }
                                }
                                $data_block->value = $sub_data[$field_name];
                            } else {
                                if($form->fields[$field_id]->field_type == 'Textarea') {
                                    $sub_data[$field_name] = sanitize_textarea_field((string)$sub_data[$field_name]);
                                } else {
                                    $sub_data[$field_name] = sanitize_text_field((string)$sub_data[$field_name]);
                                }
                                $data_block->value = $sub_data[$field_name];
                            }
                            $data_block->type = $form->fields[$field_id]->field_type;
                            if($form->fields[$field_id]->field_type == 'Rating') {
                                $rc = $form->fields[$field_id]->field_options->rating_conf;
                                $data_block->meta = (object) array('max_stars' => isset($rc->max_stars) ? $rc->max_stars : 5, 'star_face' => isset($rc->star_face) ? $rc->star_face : 'star', 'star_color' => isset($rc->star_color) ? $rc->star_color : 'FBC326');
                            } else {
                                $data_block->meta = null;
                            }
                            $db_data[$field_id] = $data_block;

                            // Checking unique value
                            if(isset($form->fields[$field_id]->field_options->field_is_unique) && $form->fields[$field_id]->field_options->field_is_unique == 1 && $data_block->value != '') {
                                $past_field_values = $wpdb->get_results($wpdb->prepare("SELECT value FROM {$wpdb->prefix}rm_submission_fields WHERE field_id = %d", $field_id));
                                if(!empty($past_field_values)) {
                                    foreach($past_field_values as $pval) {
                                        if(!is_array($data_block->value)) {
                                            if($data_block->value == $pval->value) {
                                                array_push($errors, sprintf(esc_html__('%s should have a unique value. Please try form submission again.', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                                break;
                                            }
                                        } else {
                                            foreach($data_block->value as $inval) {
                                                if($inval == $pval->value) {
                                                    array_push($errors, sprintf(esc_html__('%s should have a unique value. Please try form submission again.', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Doing length check
                            if(isset($form->fields[$field_id]->field_options->field_min_length) && !empty($form->fields[$field_id]->field_options->field_min_length)) {
                                if(!is_array($sub_data[$field_name])) {
                                    if(strlen($sub_data[$field_name]) < absint($form->fields[$field_id]->field_options->field_min_length)) {
                                        array_push($errors, sprintf(esc_html__('%s should have more than %s characters', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label, $form->fields[$field_id]->field_options->field_min_length));
                                    }
                                }
                            }
                            
                            if(isset($form->fields[$field_id]->field_options->field_max_length) && !empty($form->fields[$field_id]->field_options->field_max_length)) {
                                if(!is_array($sub_data[$field_name])) {
                                    if(strlen(str_replace(array("\n", "\r", "\r\n"), "", $sub_data[$field_name])) > absint($form->fields[$field_id]->field_options->field_max_length)) {
                                        array_push($errors, sprintf(esc_html__('%s should have less than %s characters', 'custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label, $form->fields[$field_id]->field_options->field_max_length));
                                    }
                                }
                            }

                            // Adding values to user meta array
                            if(in_array($form->fields[$field_id]->field_type, array('Fname','Lname','BInfo','Nickname','Website','SecEmail','PGAvatar'))) {
                                $user_meta_fields[$profile_meta_arr[$form->fields[$field_id]->field_type]] = $data_block->value;
                            } else if(isset($form->fields[$field_id]->field_options->field_user_profile)) {
                                if ($form->fields[$field_id]->field_options->field_user_profile == 'existing_user_meta') {
                                    $user_meta_fields[$form->fields[$field_id]->field_options->existing_user_meta_key] = $data_block->value;
                                } else if ($form->fields[$field_id]->field_options->field_user_profile == 'define_new_user_meta') {
                                    $user_meta_fields[$form->fields[$field_id]->field_options->field_meta_add] = $data_block->value;
                                }
                            }

                            if(!$save_submission) {
                                if(absint($form->fields[$field_id]->field_options->field_is_required) == 1 && (!isset($sub_data[$field_name]) || empty($sub_data[$field_name]))) {
                                    array_push($errors, sprintf(esc_html__('%s is a required field','custom-registration-form-builder-with-submission-manager'), $form->fields[$field_id]->field_label));
                                }
                            }
                        }
                    }
                }
            }
        }

        if(absint($form->form_type) == RM_REG_FORM && !is_user_logged_in()) {
            // Username check
            if(empty($username)) {
                if(empty($form->form_options->hide_username)) {
                    array_push($errors, esc_html__('Username is a required field','custom-registration-form-builder-with-submission-manager'));
                } else {
                    $username = $user_email;
                    if($this->is_username_reserved($username)) {
                        array_push($errors, esc_html__('This username is reserved/blacklisted by the admin','custom-registration-form-builder-with-submission-manager'));
                    }
                }
            }

            // Check if selected user role is a paid role or not
            $custom_role_data = get_option('rm_option_user_role_custom_data');
            if(!empty($form->form_options->form_should_user_pick)) {
                $form->form_user_role = maybe_unserialize($form->form_user_role);
                $sub_data['rm_user_role'] = sanitize_text_field($sub_data['rm_user_role']);
                if(in_array($sub_data['rm_user_role'], $form->form_user_role)) {
                    if(isset($custom_role_data[$sub_data['rm_user_role']])) {
                        $pricing_details->total_price += floatval($custom_role_data[$sub_data['rm_user_role']]->amount);
                        $pricing_details->billing[] = (object) array('label'=>sprintf(esc_html__('User Role (%s)','custom-registration-form-builder-with-submission-manager'),ucwords($sub_data['rm_user_role'])), 'price'=>floatval($custom_role_data[$sub_data['rm_user_role']]->amount), 'qty' => 1);
                    }
                } else {
                    esc_html_e("Incorrect user role selected. Please try form submission again.", 'custom-registration-form-builder-with-submission-manager');
                    return false;
                }
            } else {
                if(isset($custom_role_data[$form->default_user_role])) {
                    $pricing_details->total_price += floatval($custom_role_data[$form->default_user_role]->amount);
                    $pricing_details->billing[] = (object) array('label'=>sprintf(esc_html__('User Role (%s)','custom-registration-form-builder-with-submission-manager'),ucwords($form->default_user_role)), 'price'=>floatval($custom_role_data[$form->default_user_role]->amount), 'qty' => 1);
                }
            }
        }

        //reCAPTCHA check
        if(get_option('rm_option_enable_captcha') == 'yes') {
            if(defined('REGMAGIC_ADDON') && $form->form_options->enable_captcha == 'no') {
            } else {
                if(!isset($sub_data["g-recaptcha-response"])) {
                    array_push($errors, esc_html__('reCAPTCHA check failed','custom-registration-form-builder-with-submission-manager'));
                } else {
                    require_once(RM_BASE_DIR . "external/PFBC/Resources/recaptchalib.php");
                    $captcha_ver = get_option('rm_option_recaptcha_v');
                    if($captcha_ver == 'v2') {
                        $pvt_key = get_option('rm_option_private_key');
                    } elseif($captcha_ver == 'v3') {
                        $pvt_key = get_option('rm_option_private_key3');
                    }
                    $re_resp = rm_recaptcha_check_answer($captcha_ver == 'v3' ? 3 : 2, $pvt_key, $_SERVER["REMOTE_ADDR"], sanitize_text_field($sub_data["g-recaptcha-response"]));
                    if(!$re_resp->is_valid) {
                        array_push($errors, $re_resp->error ?? esc_html__('reCAPTCHA validation failed. Please try again.', 'custom-registration-form-builder-with-submission-manager'));
                    }
                }
            }
        }

        $errors = apply_filters('rm_validate_before_form_submit', $errors, $form);

        // Checking if there are any validation errors
        if(!empty($errors)) {
            return $errors;
        }
        
        // Adding tax to total price
        if($pricing_details->total_price > 0) {
            if($has_price && (!isset($sub_data['rm_payment_method']) || empty($sub_data['rm_payment_method']))) {
                esc_html_e("No payment method selected. Please try form submission again.", 'custom-registration-form-builder-with-submission-manager');
                return false;
            }
            $tax_enabled = get_option('rm_option_enable_tax');
            if(!empty($tax_enabled)) {
                $tax_type = get_option('rm_option_tax_type');
                if($tax_type == 'fixed') {
                    $tax = get_option('rm_option_tax_fixed');
                    $pricing_details->tax = empty($tax) ? 0.00 : floatval($tax);
                    $pricing_details->total_price += $pricing_details->tax;
                } elseif ($tax_type == 'percentage') {
                    $tax = get_option('rm_option_tax_percentage');
                    $pricing_details->tax = empty($tax) ? 0.00 : floatval(($pricing_details->total_price * $tax) / 100);
                    $pricing_details->total_price += $pricing_details->tax;
                }
            }
        }
        if(!empty($has_subscription) && !empty($subscription_id)){
            if((!isset($sub_data['rm_payment_method']) || empty($sub_data['rm_payment_method']))) {
                esc_html_e("No payment method selected. Please try form submission again.", 'custom-registration-form-builder-with-submission-manager');
                return false;
            }
        }
        // Inserting submission data in the database
        if(!$prefilled) {
            if(!empty($user_email)) {
                $token = $form_id.time().rand(100,10000);
                $insert_data = array(
                    'form_id' => $form_id,
                    'data' => maybe_serialize($db_data),
                    'user_email' => $user_email,
                    'child_id' => 0,
                    'last_child' => 0,
                    'is_read' => 0,
                    'submitted_on' => gmdate('Y-m-d H:i:s'),
                    'unique_token'=> $token
                );
                $insert_format = array(
                    '%d',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s'
                );
                if($save_submission) {
                    $insert_data['is_pending'] = 1;
                    $insert_format[] = '%d';
                }
                $wpdb->insert(
                    "{$wpdb->prefix}rm_submissions",
                    $insert_data,
                    $insert_format
                );
                
                if(empty($wpdb->insert_id)) {
                    if(empty($wpdb->last_error)) {
                        esc_html_e("Could not save submission in database. Please try again.", 'custom-registration-form-builder-with-submission-manager');
                    } else {
                        echo esc_html($wpdb->last_error);
                    }
                    return false;
                } else {
                    $sub_id = $wpdb->insert_id;
                    $submission = new RM_Submissions();
                    $submission->load_from_db($sub_id);
    
                    // Registering user if this is registration form
                    if(absint($form->form_type) == RM_REG_FORM && !is_user_logged_in()) {
                        if(empty($password)) {
                            $password = wp_generate_password();
                        }
    
                        $user_id = wp_create_user($username, $password, $user_email);
                        if(!is_wp_error($user_id)) {
                            $wp_user = new WP_User($user_id);
                        } else {
                            wp_die($wp_user->get_error_message());
                        }
                        // Assigning role
                        if(!empty($form->form_options->form_should_user_pick)) {
                            $form->form_user_role = maybe_unserialize($form->form_user_role);
                            $sub_data['rm_user_role'] = sanitize_text_field($sub_data['rm_user_role']);
                            if(in_array($sub_data['rm_user_role'], $form->form_user_role)) {
                                $wp_user->set_role($sub_data['rm_user_role']);
                            }
                        } else {
                            $form->default_user_role = empty($form->default_user_role) ? 'subscriber' : strtolower($form->default_user_role);
                            $wp_user->set_role($form->default_user_role);
                        }
    
                        // Updating user meta
                        if(!empty($user_meta_fields)) {
                            foreach($user_meta_fields as $meta_key => $meta_value) {
                                update_user_meta($user_id,$meta_key,$meta_value);
                            }
                        }

                        // Adding WC user meta
                        if(class_exists('WooCommerce')) {
                            $service->save_wc_meta($form_id, $db_data, (string)$user_email);
                        }
    
                        if(get_option('rm_option_send_password') == 'yes') {
                            $params = new stdClass();
                            $params->email = $user_email;
                            $params->username = $username;
                            $params->password = $password;
                            $params->form_id = $form_id;
                            RM_Email_Service::notify_new_user($params, $user_id);
                        }
                        update_user_meta($user_id, 'rm_user_status', 1);
                        update_user_meta($user_id, 'RM_UMETA_FORM_ID', $form_id);
                        update_user_meta($user_id, 'RM_UMETA_SUB_ID', $sub_id);
                        do_action('rm_new_user_registered', $user_id);
                        $user_setting = defined('REGMAGIC_ADDON') ? get_option('rm_option_user_auto_approval') : 'yes';
                        if($user_setting === false) {
                            $user_setting = 'yes';
                        }
                        if($form->form_options->user_auto_approval == 'default' || is_null($form->form_options->user_auto_approval)) {
                            do_action('rm_user_registered', $user_id);
                            /* if($user_setting == 'verify' && $pricing_details->total_price <= 0) {
                                //Send verification email
                                $prov_act_acc = get_option('rm_option_prov_act_acc');
                                if($prov_act_acc == 'yes' && $form_auto_approval != 'yes') {
                                    $prov_acc_act_criteria = get_option('rm_option_prov_acc_act_criteria');
                                    if(!empty($prov_acc_act_criteria)) {
                                        if ($prov_acc_act_criteria == 'until_user_logsout') {
                                            update_user_meta($user_id, 'rm_prov_activation', $prov_acc_act_criteria);
                                        }
                                    }
                                }
                                RM_Email_Service::send_activation_link($user_id);
                            } */
                           if($user_setting == 'yes' && ( $pricing_details->total_price <= 0 && empty($has_subscription))) {
                                do_action('rm_user_activated',$user_id);
                                update_user_meta($user_id, 'rm_user_status', 0);
                                update_user_meta($user_id, 'rm_activation_time', current_time('mysql'));
                                $params = new stdClass();
                                $params->email = $user_email;
                                $params->sub_id = $sub_id;
                                $params->form_id = $form_id;
                                $send_act_email = get_option('rm_option_send_act_email');
                                if($send_act_email == 'yes' || $send_act_email == false)
                                    RM_Email_Service::notify_user_on_activation($params);
                            } elseif($user_setting == '') {
                                $params = new stdClass();
                                $user_service = new RM_User_Services();
                                $link = $user_service->create_user_activation_link($user_id);
                                $params->email = $user_email;
                                $params->username = $username;
                                $params->link = $link;
                                $params->form_id = $form_id;
                                $send_act_email = get_option('rm_option_send_act_email');
                                if($send_act_email == 'yes' || $send_act_email == false)
                                    RM_Email_Service::notify_admin_to_activate_user($params);
                            }
                        } elseif($form->form_options->user_auto_approval == 'yes' && ( $pricing_details->total_price <= 0 && empty($has_subscription))) {
                            do_action('rm_user_activated',$user_id);
                            update_user_meta($user_id, 'rm_user_status', 0);
                            update_user_meta($user_id, 'rm_activation_time', current_time('mysql'));
                            $params = new stdClass();
                            $params->email = $user_email;
                            $params->sub_id = $sub_id;
                            $params->form_id = $form_id;
                            RM_Email_Service::notify_user_on_activation($params);
                        } elseif($form->form_options->user_auto_approval == 'no') {
                            $params = new stdClass();
                            $user_service = new RM_User_Services();
                            $link = $user_service->create_user_activation_link($user_id);
                            $params->email = $user_email;
                            $params->username = $username;
                            $params->link = $link;
                            $params->form_id = $form_id;
                            RM_Email_Service::notify_admin_to_activate_user($params);
                        }
                    } else {
                        $current_user = get_user_by('email', $user_email);
                        if(!empty($current_user)) {
                           $user_id = $current_user->ID;  
                        } else {
                            $user_id = null;
                        }
                    }
    
                    if(is_user_logged_in()) {
                        $current_user = wp_get_current_user();
                        $user_id = $current_user->ID;
                        
                        // Updating user meta for logged in user
                        if(!empty($user_meta_fields)) {
                            foreach($user_meta_fields as $meta_key => $meta_value) {
                                update_user_meta($current_user->ID,$meta_key,$meta_value);
                            }
                        }
                    }
    
                    // Token check
                    if(!defined('REGMAGIC_ADDON')) {
                        $token = null;
                    } else if(isset($form->form_options->form_is_unique_token) && absint($form->form_options->form_is_unique_token) == 1) {
                        if ($form->form_options->unique_token_opt == 'id') {
                            $token = $sub_id;
                            $wpdb->update(
                                "{$wpdb->prefix}rm_submissions",
                                array(
                                    'unique_token' => $token
                                ),
                                array(
                                    'submission_id' => $sub_id
                                ),
                                array(
                                    '%s'
                                ),
                                array(
                                    '%d'
                                )
                            );
                        }
                    } else {
                        $token = null;
                    }
    
                    $sub_detail = new stdClass();
                    $sub_detail->submission_id = $sub_id;
                    $sub_detail->token = $token;
    
                    // Subscribing to external services
                    do_action('rm_subscribe_newsletter', $form_id, $sub_data);
                    $front_service = new RM_Front_Form_Service();
                    if(get_option('rm_option_enable_mailchimp') == 'yes' && (!empty($form->form_options->enable_mailchimp) && absint($form->form_options->enable_mailchimp[0]) == 1)) {
                        if($form->form_options->form_is_opt_in_checkbox == 1 || (isset($form->form_options->form_is_opt_in_checkbox[0]) && $form->form_options->form_is_opt_in_checkbox[0] == 1))
                            $should_subscribe = isset($sub_data['rm_subscribe_mc']) && absint($sub_data['rm_subscribe_mc'][0]) == 1 ? 'yes' : 'no';
                        else
                            $should_subscribe = 'yes';
                        if($should_subscribe == 'yes') {
                            try {
                                $front_service->subscribe_to_mailchimp($sub_data, $form->form_options);
                            } catch (Exception $e) {}
                        }
                    }
    
                    if(get_option('rm_option_enable_aweber') == 'yes' && (!empty($form->form_options->enable_aweber) && $form->form_options->enable_aweber[0] == 1)) {
                        if ($form->form_options->form_is_opt_in_checkbox_aw[0] == 1)
                            $should_subscribe = isset($sub_data['rm_subscribe_aw']) && $sub_data['rm_subscribe_aw'][0] == 1 ? 'yes' : 'no';
                        else
                            $should_subscribe = 'yes';
    
                        if ($should_subscribe == 'yes') {
                            try {
                                $front_service->subscribe_to_aweber($sub_data, $form->form_options);
                            } catch (Exception $e) {}
                        }
                    }
    
                    // Send autoresponder email
                    if(absint($form->form_should_send_email) == 1) {
                        $parameters = new stdClass;
                        $parameters->req = $sub_data;
                        $parameters->db_data = $db_data;
                        $parameters->email = $user_email;
                        $parameters->email_content = $form->form_options->form_email_content;
                        $parameters->email_subject = $form->form_options->form_email_subject;
                        $parameters->total_price = empty($total_price) ? 0 : $total_price;
                        $parameters->sub_id = $sub_id;
                        $parameters->form_id = $form_id;
                        $parameters->sub_data = $submission->get_data();
                        RM_Email_Service::auto_responder($parameters, $token);
                    }
    
                    // Creating submission PDF attachment
                    if(defined('REGMAGIC_ADDON')) {
                        $should_attach_sub_pdf = get_option('rm_option_admin_notification_includes_pdf');
                        $sub_pdf_loc = null;
                    
                        if($should_attach_sub_pdf == 'yes' || $form->form_options->enable_dpx == "1") {
                            //Address for submission pdf to create temporarily
                            $sub_pdf_loc = get_temp_dir().'rm_submission_'.$sub_id.'.pdf';
                            //Ouput the pdf to desired location
                            $service->output_pdf_for_submission($submission, array('name' => $sub_pdf_loc, 'type' => 'F'));
                        }
                    
                        $parameters = new stdClass;
                        $parameters->sub_data = $submission->get_data();
                        $parameters->form_name = $form->form_name;
                        //Attachments for the mail
                        $parameters->attachment = $should_attach_sub_pdf == 'yes'? $sub_pdf_loc : null;
                        //Changing attachment URL for PDF merging
                        if(!empty($parameters->attachment))
                            $parameters->attachment = apply_filters('rm_admin_notification_attachment_merger', $parameters->attachment, $sub_id, $form_id);
                        else
                            $parameters->attachment = (string)$parameters->attachment;
                        $parameters->sub_id = $sub_id;
                        $parameters->form_id = $form_id;
                        
                        //do_action('rm_after_submission',$sub_detail,$sub_data,$sub_pdf_loc);
                    } else {
                        $parameters = new stdClass;
                        $parameters->sub_data = $submission->get_data();
                        $parameters->form_name = $form->form_name;
                        $parameters->sub_id = $sub_id;
                        $parameters->form_id = $form_id;
                    }
                    // Sending submission notice to admin
                    RM_Email_Service::notify_submission_to_admin($parameters, $token);
    
                    // Updating last child column in submission
                    $wpdb->update(
                        "{$wpdb->prefix}rm_submissions",
                        array(
                            'last_child' => $sub_id
                        ),
                        array(
                            'submission_id' => $sub_id
                        ),
                    );
    
                    foreach($db_data as $index => $data) {
                        $wpdb->insert(
                            "{$wpdb->prefix}rm_submission_fields",
                            array(
                                'submission_id' => $sub_id,
                                'field_id' => $index,
                                'form_id' => $form_id,
                                'value' => maybe_serialize($data->value),
                            ),
                            array(
                                '%d',
                                '%d',
                                '%d',
                                '%s'
                            )
                        );
                    }
    
                    if(isset($sub_data['stat_id'])) {
                        $this->update_stat_entry(absint($sub_data['stat_id']), $sub_id);
                    }
    
                    // Sending data to external URL
                    if(absint($form->form_options->should_export_submissions) == 1) {
                        $exporter = new RM_Export_POST($form->form_options->export_submissions_to_url);
                        $exporter->prepare_data($db_data);
                        $exporter->send_data();
                    }
    
                    do_action('rm_submission_completed', $form_id, $user_id, $submission->get_data());
    
                    // Charge payment if form has payment
                    if($pricing_details->total_price > 0) {
                        $payment_processor = $sub_data['rm_payment_method'];
                        if($payment_processor == 'paypal') {
                            $paypal_service = new RM_Paypal_Service();
                            $data = new stdClass();
                            $data->form_id = $form_id;
                            $data->submission_id = $sub_id;
                            if(absint($form->form_type) == RM_REG_FORM)
                                $data->user_id = $user_id;
    
                            $payment_html = $paypal_service->charge($data, $pricing_details);
                            if(!empty($payment_html)) {
                                echo $payment_html['html'];
                                return;
                            }
                        } elseif($payment_processor === "stripe") {
                            $stripe_service = RM_Stripe_Service::get_instance();
                            $data = new stdClass();
                            $data->form_id = $form_id;
                            $data->form_name = $form->form_name;
                            $data->submission_id = $sub_id;
                            $data->user_email = $user_email;
                            if(absint($form->form_type) == RM_REG_FORM)
                                $data->user_id = $user_id;
                            $payment_html = $stripe_service->show_card_elements($data, $pricing_details);
                            if(!empty($payment_html)) {
                                echo $payment_html['html'];
                                return;
                            }
                        } else {
                            $form_factory = defined('REGMAGIC_ADDON') ? new RM_Form_Factory_Addon() : new RM_Form_Factory();
                            $form_obj = $form_factory->create_form($form_id);
                            $req_obj = new stdClass();
                            $req_obj->req = $sub_data;
                            $params = array();
                            $params['sub_detail'] = $sub_detail;
                            $params['paystate'] = 'pre_payment';
                            $params['user_email'] = $user_email;
                            $params['form_id'] = $form_id;
                            $payment_done = false;
                            $payment_done = apply_filters('rm_process_payment', $payment_done, $form_obj, $req_obj, $params);
                            $payment_html = $payment_done;
                            if(isset($payment_html['html'])) {
                                echo $payment_html['html'];
                                return;
                            } elseif($payment_processor == 'offline') {
                                // Displaying post submission message
                                if($save_submission) {
                                    $after_sub_msg = esc_html__('Form Submission Saved','custom-registration-form-builder-with-submission-manager');
                                } else {
                                    $after_sub_msg = $form->form_options->form_success_message != "" ? $form->form_options->form_success_message : sprintf(esc_html__('%s Submitted','custom-registration-form-builder-with-submission-manager'), $form->form_name);
                                }
                                echo "<div class='rm_form_submit_msg rm-form-submit-wrap'><div class='rm-form-submit-message-icon'><svg xmlns='http://www.w3.org/2000/svg' height='24px' viewBox='0 0 24 24' width='24px' fill='#000000'><path d='M0 0h24v24H0z' fill='none'></path><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'></path></svg></div><div class='rm-post-sub-msg'>".wp_kses_post((string)$after_sub_msg)."</div></div>";
                                if(!empty($form->form_options->form_is_unique_token)) {
                                    echo "<br>";
                                    echo "<div class='rmform-submisstion-token'>"
                                    . wp_kses_post(sprintf(esc_html__('Submission Token: %s','custom-registration-form-builder-with-submission-manager'), $token));
                                    echo "</div>";
                                }
                            }
                        }
                    }else if(!empty($has_subscription) && !empty($subscription_id)) {
                        $payment_processor = $sub_data['rm_payment_method'];

                        $form_factory = defined('REGMAGIC_ADDON') ? new RM_Form_Factory_Addon() : new RM_Form_Factory();
                            $form_obj = $form_factory->create_form($form_id);
                            $req_obj = new stdClass();
                            $req_obj->req = $sub_data;
                            $data->form_id = $form_id;
                            $data->form_name = $form->form_name;
                            $data->submission_id = $sub_id;
                            $data->user_email = $user_email;
                            $data->sub_detail = $sub_detail;
                            $data->paystate = 'pre_payment';
                            $data->user_email = $user_email;
                            if(absint($form->form_type) == RM_REG_FORM){
                                $data->user_id = $user_id;
                            }
                            $payment_done = false;
                            $payment_done = apply_filters('rm_process_subscription_payment', $payment_done, $form_obj, $req_obj, $data, $subscription_data);

                            $payment_html = $payment_done;
                            if(isset($payment_html['html'])) {
                                echo $payment_html['html'];
                                return;
                            }
                    } else {
                        // Displaying post submission message
                        if($save_submission) {
                            $after_sub_msg = esc_html__('Form Submission Saved','custom-registration-form-builder-with-submission-manager');
                        } else {
                            $after_sub_msg = $form->form_options->form_success_message != "" ? $form->form_options->form_success_message : sprintf(esc_html__('%s Submitted','custom-registration-form-builder-with-submission-manager'), $form->form_name);
                        }
                        echo "<div class='rm_form_submit_msg rm-form-submit-wrap'><div class='rm-form-submit-message-icon'><svg xmlns='http://www.w3.org/2000/svg' height='24px' viewBox='0 0 24 24' width='24px' fill='#000000'><path d='M0 0h24v24H0z' fill='none'></path><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'></path></svg></div><div class='rm-post-sub-msg'>".wp_kses_post((string)$after_sub_msg)."</div></div>";
                        if(!empty($form->form_options->form_is_unique_token)) {
                            echo "<br>";
                            echo "<div class='rmform-submisstion-token'>"
                            . wp_kses_post(sprintf(esc_html__('Submission Token: %s','custom-registration-form-builder-with-submission-manager'), $token));
                            echo "</div>";
                        }
                    }
    
                    // Auto login
                    if(!empty($form->form_options->auto_login) && !is_user_logged_in() && absint($form->form_type) == RM_REG_FORM && $pricing_details->total_price <= 0) {
                        $user_activated = get_user_meta($user_id, 'rm_user_status', true);
                        if($user_activated == 0 || ($user_activated == 1 && !empty(get_option('rm_option_prov_acc_act_criteria')))) {
                            $_SESSION['RM_SLI_UID'] = $user_id;
                            $login_service = new RM_Login_Service();
                            $login_service->insert_login_log(array('email' => $user_email, 'username_used' => $user_email, 'ip' => $_SERVER['REMOTE_ADDR'] === '::1' ? 'localhost' : $_SERVER['REMOTE_ADDR'], 'time' => current_time('timestamp'), 'status' => 1, 'type' => 'normal', 'result' => 'success', 'social_type' => ''));
                            if(!isset($form->form_redirect) || $form->form_redirect == 'none') {
                                echo "<script>setTimeout(function() { document.location.href = '".esc_url(add_query_arg(array('login' => 1), get_permalink()))."'; }, 3000);</script>";
                            }
                        }
                    }
    
                    // Redirecting after form submission
                    if(isset($form->form_redirect) && $form->form_redirect != 'none' && !$save_submission) {
                        if($form->form_redirect == 'page') {
                            $page_url = get_permalink($form->form_redirect_to_page);
                            echo "<p>".sprintf(esc_html__("Redirecting you to %s", 'custom-registration-form-builder-with-submission-manager'), $page_url)."</p>";
                            echo "<script>setTimeout(function() { document.location.href = '".esc_url($page_url)."'; }, 3000);</script>";
                            //wp_redirect($page_url); exit;
                        } elseif($form->form_redirect == 'url') {
                            echo "<p>".sprintf(esc_html__("Redirecting you to %s", 'custom-registration-form-builder-with-submission-manager'), $form->form_redirect_to_url)."</p>";
                            echo "<script>setTimeout(function() { document.location.href = '".esc_url($form->form_redirect_to_url)."'; }, 3000);</script>";
                            //wp_redirect($form->form_redirect_to_url); exit;
                        }
                    }
    
                    return false;
                }
            } else {
                esc_html_e("User email not provided. Please try submission again.", 'custom-registration-form-builder-with-submission-manager');
                return false;
            }
        } else {
            if(!empty($submission_id)) {
                $old_submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_submissions WHERE submission_id = %d", $submission_id));
                $old_db_data = maybe_unserialize($old_submission->data);
                $db_data = array_replace($old_db_data, $db_data);
                foreach($db_data as $fid => $new_data) {
                    $result = $wpdb->update(
                        "{$wpdb->prefix}rm_submission_fields",
                        array(
                            'value' => maybe_serialize($new_data->value),
                        ),
                        array(
                            'submission_id' => $submission_id,
                            'field_id' => $fid,
                            'form_id' => $form_id,
                        ),
                        array(
                            '%s',
                        ),
                        array(
                            '%d',
                            '%d',
                            '%d',
                        ),
                    );

                    if(empty($result)) {
                        $wpdb->insert(
                            "{$wpdb->prefix}rm_submission_fields",
                            array(
                                'value' => maybe_serialize($new_data->value),
                                'field_id' => $fid,
                                'form_id' => $form_id,
                                'submission_id' => $submission_id
                            ),
                            array(
                                '%s',
                                '%d',
                                '%d',
                                '%d'
                            ),
                        );
                    }
                }

                $sub_update_data = array(
                    'data' => maybe_serialize($db_data),
                );
                $sub_update_format = array(
                    '%s',
                );
                if(defined('RM_SAVE_SUBMISSION_BASENAME') && !$save_submission) {
                    $sub_update_data['is_pending'] = 0;
                    $sub_update_format[] = '%d';
                }
                $wpdb->update(
                    "{$wpdb->prefix}rm_submissions",
                    $sub_update_data,
                    array(
                        'submission_id' => $submission_id,
                        'form_id' => $form_id,
                    ),
                    $sub_update_format,
                    array(
                        '%d',
                        '%d',
                    ),
                );

                $old_user = get_user_by('email', $old_submission->user_email);
                if(!empty($old_user)) {
                    // Updating user meta
                    if(!empty($user_meta_fields)) {
                        foreach($user_meta_fields as $meta_key => $meta_value) {
                            update_user_meta($old_user->ID, $meta_key,$meta_value);
                        }
                    }

                    // Adding WC user meta
                    if(class_exists('WooCommerce')) {
                        $service->save_wc_meta($form_id, $db_data, (string)$user_email);
                    }

                    $user_form_id = get_user_meta($old_user->ID,'RM_UMETA_FORM_ID', true);
                    if(!empty($user_form_id) && ($form_id == $user_form_id)) {
                        if(count($db_data) > 0) {
                            update_user_meta($old_user->ID, 'rm_woo_registration_data', maybe_serialize($db_data));
                        }
                    }
                }

                $note = new RM_Notes;
                $note_data = array('submission_id' => $submission_id, 'notes' => '', 'status' => 'draft', 'type' => 'notification');
                $note->set($note_data);
                $note->insert_into_db();

                do_action('rm_submission_edited', $old_submission->user_email);

                echo '<p>'.esc_html__('Submission edited succesfully', 'custom-registration-form-builder-with-submission-manager').'</p>';

                $edit_form_redirect = current_user_can('manage_options') ? admin_url('admin.php?page=rm_submission_view&rm_submission_id='.$submission_id) : get_permalink(get_option('rm_option_front_sub_page_id')).'?submission_id='.$submission_id;
                echo "<p>".esc_html__("Redirecting you back to the submission details", 'custom-registration-form-builder-with-submission-manager')."</p>";
                echo "<script>setTimeout(function() { document.location.href = '".$edit_form_redirect."'; }, 3000);</script>";
            } else {
                esc_html_e("Invalid submission edit.", 'custom-registration-form-builder-with-submission-manager');
                return false;
            }
        }

    }

    public function render_form($form_id = null, $theme = null, $prefilled = false, $ex_sub_id = 0) {
        // Enqueuing important syles and scripts
        wp_enqueue_style(RM_PLUGIN_BASENAME, RM_BASE_URL . 'public/css/style_rm_front_end.css', array(), RM_PLUGIN_VERSION, 'all');
        wp_enqueue_style('rm_material_icons', RM_BASE_URL . 'admin/css/material-icons.css', array(), RM_PLUGIN_VERSION, 'all');
        if(defined('REGMAGIC_ADDON')) {
            wp_enqueue_style(RM_PLUGIN_BASENAME . '_addon', RM_ADDON_BASE_URL . 'public/css/style_rm_front_end.css', array(), RM_PLUGIN_VERSION, 'all');
            wp_register_style('rm_stripe_checkout_style', RM_ADDON_BASE_URL . 'public/css/rm_stripe_checkout.css', array(), RM_PLUGIN_VERSION, 'all');
        }
        if(empty($theme)) {
            $theme = get_option('rm_option_theme','default');
        }
        if(in_array($theme,array('default','classic','matchmytheme'))) {
            wp_enqueue_style('rm-form-revamp-theme', RM_BASE_URL . "public/css/rm-form-theme-{$theme}.css", array(), RM_PLUGIN_VERSION);
        } else {
            wp_enqueue_style('rm-form-revamp-theme', RM_BASE_URL . "public/css/rm-form-theme-custom.css", array(), RM_PLUGIN_VERSION);
        }
        wp_enqueue_style('rm-form-revamp-style', RM_BASE_URL . 'public/css/rm-form-common-utility.css', array(), RM_PLUGIN_VERSION);
        wp_enqueue_script('rm-form-revamp-script', RM_BASE_URL . 'public/js/rm-form-common-utility.js', array('jquery'), RM_PLUGIN_VERSION);
        wp_enqueue_script('rm_password_utility', RM_BASE_URL . 'public/js/password-utility.js', array('jquery'), RM_PLUGIN_VERSION);
        wp_dequeue_script('rm_jquery_conditionalize');
        wp_enqueue_script('rm-form-revamp-conditionize', RM_BASE_URL . 'public/js/conditionize_revamp.jquery.js', array('jquery'), RM_PLUGIN_VERSION);
        if(is_rtl()) {
            wp_enqueue_style('rm-form-revamp-rtl', RM_BASE_URL . 'public/css/rm-form-rtl-style.css', array(), RM_PLUGIN_VERSION);
        }
        wp_enqueue_script('rm_jquery_paypal_checkout', RM_BASE_URL."public/js/paypal_checkout_utility.js", array('jquery'), RM_PLUGIN_VERSION);

        // Localizing data
        $rm_ajax_data = array(
            "url"=>admin_url('admin-ajax.php'),
            "nonce"=>wp_create_nonce('rm_ajax_secure'),
            "gmap_api"=>get_option('rm_option_google_map_key', null),
            'no_results'=>esc_html__('No Results Found','custom-registration-form-builder-with-submission-manager'),
            'invalid_zip'=>esc_html__('Invalid Zip Code','custom-registration-form-builder-with-submission-manager'),
            'request_processing'=>esc_html__('Please wait...','custom-registration-form-builder-with-submission-manager'),
            'hours'=>esc_html__('Hours','custom-registration-form-builder-with-submission-manager'),
            'minutes'=>esc_html__('Minutes','custom-registration-form-builder-with-submission-manager'),
            'seconds'=>esc_html__('Seconds','custom-registration-form-builder-with-submission-manager'),
            'days'=>esc_html__('Days','custom-registration-form-builder-with-submission-manager'),
            'months'=>esc_html__('Months','custom-registration-form-builder-with-submission-manager'),
            'years'=>esc_html__('Years','custom-registration-form-builder-with-submission-manager'),
            'tax_enabled'=>get_option('rm_option_enable_tax', null),
            'tax_type'=>get_option('rm_option_tax_type', null),
            'tax_fixed'=>round(floatval(get_option('rm_option_tax_fixed', null)),2),
            'tax_percentage'=>round(floatval(get_option('rm_option_tax_percentage', null)),2),
            'tax_rename'=>get_option('rm_option_tax_rename',null),
        );
        wp_localize_script('rm-form-revamp-script','rm_ajax',$rm_ajax_data);

        if(empty($form_id)) {
            esc_html_e("No form selected", 'custom-registration-form-builder-with-submission-manager');
            return;
        } else {
            // Auto login condition
            if(isset($_GET['login']) && absint($_GET['login']) == 1) {
                esc_html_e("Auto login successful", 'custom-registration-form-builder-with-submission-manager');
                return;
            }

            // Getting global WPDB object
            global $wpdb;

            // Getting the form from the DB
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_forms WHERE form_id = %d", $form_id));
            if(empty($form)) {
                esc_html_e("Form doesn't exist in the database", 'custom-registration-form-builder-with-submission-manager');
                return;
            }
            $form->form_options = maybe_unserialize($form->form_options);
            
            if(!$prefilled) {
                // Ban check
                if($this->banned_check()) {
                    esc_html_e("You are banned from submitting the form.", 'custom-registration-form-builder-with-submission-manager');
                    return;
                }

                // Expiry check
                if($this->expiry_check($form)) {
                    if($form->form_options->post_expiry_action == 'switch_to_another_form') {
                        if(!empty($form->form_options->post_expiry_form_id)) {
                            $form_id = absint($form->form_options->post_expiry_form_id);
                            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_forms WHERE form_id = %d", $form_id));
                            if(empty($form)) {
                                esc_html_e("Form doesn't exist in the database", 'custom-registration-form-builder-with-submission-manager');
                                return;
                            }
                            $form->form_options = maybe_unserialize($form->form_options);
                        } else {
                            return;
                        }
                    } else {
                        if($form->form_options->form_message_after_expiry) {
                            echo wp_kses_post((string)$form->form_options->form_message_after_expiry);
                            return;
                        } else {
                            esc_html_e("This form has expired.", 'custom-registration-form-builder-with-submission-manager');
                            return;
                        }
                    }
                }

                // Update form diary
                global $rm_form_diary;
                if(isset($rm_form_diary[$form_id]))
                    $rm_form_diary[$form_id]++;
                else
                    $rm_form_diary[$form_id] = 1;

                // Form access interception
                if(defined('REGMAGIC_ADDON')) {
                    $params['form_id'] = $form_id;
                    $fac_responce = $this->test_form_access($form, $_REQUEST, $params);
                    if($fac_responce->status != 'allowed') {
                        echo wp_kses_post((string)$fac_responce->html_str);
                        return;
                    }
                }

                $price_fields = 0;
                $subscription_enabled = 0;
                // Handling Paypal redirect after payment
                if (isset($_GET['rm_pproc'], $_GET['rm_fid'], $_GET['rm_fno'], $rm_form_diary[$form_id], $_GET['sh'])
                    && $_GET['rm_fid'] == $form_id && $_GET['rm_fno'] == $rm_form_diary[$form_id]) {
                    $paypal_service = new RM_Paypal_Service();
                    ob_start();
                    $paypal_service->callback($_GET['rm_pproc'], isset($_GET['rm_pproc_id']) ? $_GET['rm_pproc_id'] : null, $_GET['sh']);
                    $paypal_callback_msg = ob_get_clean();
                    $x = new stdClass;
                    $x->form = $form;
                    $x->form_name = $form->form_name;
                    $after_sub_msg = $this->after_submission_proc($x);
                    echo wp_kses_post((string)$paypal_callback_msg).'<br><br>'.wp_kses_post((string)$after_sub_msg);
                    return;
                }

                if(isset($_GET['rm_success']) && isset($_GET['rm_form_id']) && is_numeric($_GET['rm_form_id']) && $form_id == $_GET['rm_form_id']) {
                    $html = "<div class='rm-post-sub-msg'>";
                    $sub_id = isset($_GET['rm_sub_id']) ? absint($_GET['rm_sub_id']) : 0;
                    $html .= $form->form_options->form_success_message != "" ? apply_filters('rm_form_success_msg',$form->form_options->form_success_message,$form_id,$sub_id) : $form->form_name . " Submitted ";
                    $html .= '</div>';
                    echo wp_kses_post($html);
                    return;
                }

                // Handling Stripe payment
                if (isset($_REQUEST['payment_intent_client_secret'])) {
                    wp_enqueue_script('rm_stripe_script','https://js.stripe.com/v3/');
                    wp_enqueue_script('rm_stripe_status_utility',RM_ADDON_BASE_URL. 'public/js/stripe_status_utility.js');
                    $rm_admin_vars = array('nonce'=>wp_create_nonce('rm_ajax_secure'));
                    wp_localize_script('rm_stripe_status_utility','rm_admin_vars',$rm_admin_vars);
                    wp_enqueue_style('rm_stripe_checkout_style', RM_ADDON_BASE_URL . 'public/css/rm_stripe_checkout.css');
                    
                    $log_entry_id = absint($_REQUEST['log_id']);
                    $total_price = sanitize_text_field($_REQUEST['total_price']);
                    $submission_id = absint($_REQUEST['sub_id']);
                    $description = sanitize_text_field($_REQUEST['description']);
                    $label = esc_html__('Please enter the details to complete the payment:','custom-registration-form-builder-with-submission-manager');
                    $btn_label = esc_html__('Pay','custom-registration-form-builder-with-submission-manager').' '.$total_price;
                    echo "<div class=\"rm_stripe_fields\">
                            <div class=\"rm_stripe_label\" style=\"display:none\">".wp_kses_post((string)$label)."</div>
                            <form id=\"rm-stripe-payment-form\" data-log-id=\"".wp_kses_post((string)$log_entry_id)."\" data-total-price=\"".wp_kses_post((string)$total_price)."\" data-submission-id=\"".wp_kses_post((string)$submission_id)."\" data-description=\"".wp_kses_post((string)$description)."\">
                            <div id=\"rm-stripe-payment-element\" style=\"display:none\">
                                <!--Stripe.js injects the Payment Element-->
                            </div>
                            <button id=\"rm-stripe-submit\" style=\"display:none\">
                                <div class=\"rm-stripe-spinner rm-stripe-hidden\" id=\"rm-stripe-spinner\"></div>
                                <span id=\"rm-stripe-button-text\">".wp_kses_post((string)$btn_label)."</span>
                            </button>
                            <div id=\"rm-stripe-payment-message\" class=\"rm-stripe-hidden\"></div>
                            </form>
                        </div>";
                    return;
                }
                
                do_action('rm_pre_form_proc', $_REQUEST);

                // Off limit check
                if($this->is_off_limit_submission($form_id, $form->form_options)) {
                    esc_html_e("To fight spam admin has fixed the maximum number of submissions for this form from a single device. You can resubmit after 24 hours or you can contact the admin to reset the limit.", 'custom-registration-form-builder-with-submission-manager');
                    return;
                }

                // Handling form submission
                if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_id'])) {
                    $errors = $this->save_submission($_POST, $form, $rm_form_diary[$form_id]);
                    if(empty($errors))
                        return;
                } else {
                    $errors = array();
                }

                // Updating published pages for the selected form
                if(!is_admin()) {
                    $current_page = get_the_ID();
                    $published_pages = maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT published_pages FROM {$wpdb->prefix}rm_forms WHERE form_id = %d", $form_id)));
                    if(is_array($published_pages)) {
                        array_push($published_pages, $current_page);
                    } else {
                        $published_pages = array($current_page);
                    }
                    $published_pages = array_unique($published_pages);
                    $wpdb->update(
                        "{$wpdb->prefix}rm_forms",
                        array('published_pages' => maybe_serialize($published_pages)),
                        array('form_id' => $form_id),
                        '%s'
                    );

                    // Creating stat entry
                    $stat_id = $this->create_stat_entry($form_id);
                } else {
                    $stat_id = -1;
                }
            } else {
                // Update form diary
                global $rm_form_diary;
                if(isset($rm_form_diary[$form_id]))
                    $rm_form_diary[$form_id]++;
                else
                    $rm_form_diary[$form_id] = 1;

                $price_fields = 0;

                if(empty($ex_sub_id)) {
                    esc_html_e('Invalid submission edit', 'custom-registration-form-builder-with-submission-manager');
                    return;
                }
                
                // Handling form submission
                if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_id']) && isset($_POST['rm_prefilled'])) {
                    $errors = $this->save_submission($_POST, $form, $rm_form_diary[$form_id], $prefilled, $ex_sub_id);
                    if(empty($errors))
                        return;
                } else {
                    $errors = array();
                }
            }

            $form->rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_rows WHERE form_id = %d ORDER BY page_no, row_order ASC", $form_id));
            if($prefilled) {
                if(current_user_can('manage_options')) {
                    $form->fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_fields WHERE form_id = %d AND field_type NOT IN ('Price', 'Subscription') AND is_field_primary = 0", $form_id), OBJECT_K);
                } else {
                    if(isset($form->form_options->save_submission_enabled) && !empty($form->form_options->save_submission_enabled) && defined('RM_SAVE_SUBMISSION_BASENAME')) {
                        $form->fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_fields WHERE form_id = %d AND field_type NOT IN ('Price', 'Subscription') AND is_field_primary = 0", $form_id), OBJECT_K);
                    } else {
                        $form->fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_fields WHERE form_id = %d AND field_type NOT IN ('Price', 'Subscription') AND is_field_primary = 0 AND field_is_editable = 1", $form_id), OBJECT_K);
                    }
                }
            } else {
                $form->fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_fields WHERE form_id = %d", $form_id), OBJECT_K);
            }
            if(!empty($form->rows) && is_iterable($form->rows)) {
                // Output CSS from Design area of the form
                $this->output_custom_design($form);

                $field_factory = new RM_Field_Factory_Revamp();
                $field_factory_addon = defined('REGMAGIC_ADDON') ? new RM_Field_Factory_Revamp_Addon() : new stdClass();
                $column_structure_arr = array(
                    '1' => array(12),
                    '1:1' => array(6,6),
                    '2:1' => array(8,4),
                    '1:1:1' => array(4,4,4),
                    '1:1:1:1' => array(3,3,3,3)
                );
                echo "<div class='rmformui'>";
                echo "<div id='rm-form-container' class='rmform-design--".esc_attr((string)$theme)."-container'>";
                // Displaying errors
                if(!empty($errors) && is_array($errors)) {
                    echo "<div id='rm-form-errors' class='rmform-errors-message'>";
                    foreach($errors as $error) {
                        echo "<div class='rm-form-error'>".wp_kses_post((string)$error)."</div>";
                    }
                    echo "</div>";
                }
                // Display content above form
                if((isset($form->form_options->display_progress_bar) && $form->form_options->display_progress_bar == 'yes') || (isset($form->form_options->display_progress_bar) && $form->form_options->display_progress_bar == 'default' && get_option('rm_option_display_progress_bar') == 'yes') || (!isset($form->form_options->display_progress_bar) && get_option('rm_option_display_progress_bar') == 'yes')) {
                    $exp_str = RM_Utilities_Revamp::get_form_expiry_message($form->form_id);
                    echo "<div id='rm_limit_stat_msg'>".wp_kses_post($exp_str)."</div>";
                }
                if(!empty($form->form_options->form_custom_text)) {
                    echo "<div class='rmheader'>".wp_kses_post((string)$form->form_options->form_custom_text)."</div>";
                }
                $label_layout = get_option("rm_option_form_layout", "label_top");
                echo "<form id='rmform-module-".esc_attr((string)$form->form_id)."' class='rmform-ui rmform-custom-form rmform-custom-form-".esc_attr((string)$form->form_id)." rmform-design--".esc_attr((string)$theme)."' action='' method='post' enctype='multipart/form-data'"
                . "data-form-id='".esc_attr((string)$form->form_id)."' data-design='".esc_attr((string)$theme)."' data-style='".esc_attr((string)$label_layout)."' data-type='".esc_attr((string)$form->form_type)."'>";

                $v3_key = '';
                $has_pages = isset($form->form_options->form_pages) && is_array($form->form_options->form_pages) && count($form->form_options->form_pages) > 1 && defined('REGMAGIC_ADDON');
                if($has_pages) {
                    if(!isset($form->form_options->ordered_form_pages) || empty($form->form_options->ordered_form_pages)) {
                        $form->form_options->ordered_form_pages = array();
                        foreach($form->form_options->form_pages as $page_k => $page_v) {
                            array_push($form->form_options->ordered_form_pages, $page_k);
                        }
                    }
                    $rows_by_pages = $this->get_rows_by_pages($form->rows, $form->form_options->ordered_form_pages);
                    echo "<ul id='rmagic-progressbar'>";
                    foreach($form->form_options->ordered_form_pages as $page_id => $ord_page) {
                        if($page_id === 0) {
                            echo "<li class='active'><span class='rm-progressbar-counter'></span><span class='rm-form-page-name'>".wp_kses_post((string)$form->form_options->form_pages[$ord_page])."</span></li>";
                        } else {
                            echo "<li><span class='rm-progressbar-counter'></span><span class='rm-form-page-name'>".wp_kses_post((string)$form->form_options->form_pages[$ord_page])."</span></li>";
                        }
                    }
                    echo "</ul>";
                } else {
                    $rows_by_pages[0] = $form->rows;
                }
                foreach($rows_by_pages as $page_id => $row_page) {
                    if($page_id === array_key_first($rows_by_pages)) {
                        echo "<div class='rmform-page'>";
                    } else {
                        echo "<div class='rmform-page' style='display:none'>";
                    }
                    foreach($row_page as $row) {
                        if(isset($column_structure_arr[$row->columns])) {
                            $col_array = $column_structure_arr[$row->columns];
                        } else {
                            $col_array = array(12);
                        }
                        echo "<div class='rmform-row'>";
                        if(!empty($row->heading)) {
                            echo "<div class='rmform-row-heading'>".wp_kses_post((string)$row->heading)."</div>";
                        }
                        if(!empty($row->subheading)) {
                            echo "<div class='rmform-row-subheading'>".wp_kses_post((string)$row->subheading)."</div>";
                        }
                        $row_style = " style='--rm-field-gutter: ".esc_attr($row->gutter)."px;";
                        if(!empty(absint($row->bmargin))) {
                            $row_style .= " margin-bottom: ".esc_attr($row->bmargin)."px;";
                        }
                        if(!empty(absint($row->width))) {
                            $row_style .= " width: ".esc_attr($row->width)."px;";
                        }
                        if(!empty($row->class)) {
                            $row_class = 'rmform-row-field-wrap ' . $row->class;
                        } else {
                            $row_class = 'rmform-row-field-wrap';
                        }
                        $row_style .= "'";
                        echo "<div class='".esc_attr($row_class)."' ".wp_kses_post($row_style).">";
                        $fields_in_row = maybe_unserialize($row->field_ids);
                        foreach($fields_in_row as $index => $field_id) {
                            $field_id = absint($field_id);
                            if(!isset($col_array[$index])) {
                                continue;
                            }
                            if(empty($field_id)) {
                                echo "<div class='rmform-col rmform-col-".wp_kses_post((string)$col_array[$index])."'></div>";
                                continue;
                            }
                            $field = isset($form->fields[$field_id]) ? $form->fields[$field_id] : null;
                            if(empty($field) || ($field->field_type == 'Username' && !empty($form->form_options->hide_username))) {
                                continue;
                            }
                            $field->field_options = maybe_unserialize($field->field_options);
                            if (isset($field->field_options->field_is_admin_only) && ($field->field_options->field_is_admin_only == 1) && (!current_user_can( 'manage_options' ))) {
                                continue;
                            }
                            // Saving field conditions for later
                            if(!empty($field->field_options->conditions['rules'])) {
                                $field_conditions[strtolower($field->field_type)."_{$field_id}"] = $field->field_options->conditions;
                            }

                            if ($field->field_type != "Address" && $field->field_type != "WCBilling" && $field->field_type != "WCShipping" && $field->field_type != "Radio" && $field->field_type != "Checkbox" && $field->field_type != "Terms" && $field->field_type != "Price" && $field->field_type != "Privacy") {
                                echo "<div id='rm-".wp_kses_post(strtolower($field->field_type))."-".wp_kses_post((string)$field_id)."' class='rmform-col rmform-col-".wp_kses_post((string)$col_array[$index])."'>";
                            } else {
                                echo "<div id='rm-".wp_kses_post(strtolower($field->field_type))."-".wp_kses_post((string)$field_id)."' class='rm-address-field-wrap rmform-col rmform-col-".wp_kses_post((string)$col_array[$index])."'>";
                            }
                            echo "<div class='rmform-field' data-field-id='".wp_kses_post((string)$field_id)."'>";
                            //<!-rmform-has-error rm-form-has-hover rm-form-is-active -->
                            $field_method = strtolower("create_".str_replace("-","",$field->field_type)."_field");
                            if(method_exists($field_factory, $field_method)) {
                                if($prefilled) {
                                    $field_factory->$field_method($field, $ex_sub_id);
                                } else {
                                    $field_factory->$field_method($field);
                                }
                            } elseif(method_exists($field_factory_addon, $field_method)) {
                                $field_factory_addon->$field_method($field);
                            }
                            if($field->field_type == 'Price') {
                                $price_fields++;
                            }
                            if($field->field_type == 'Subscription') {
                                if (defined('REGMAGIC_ADDON') && class_exists('RMSubscriptions')) {
                                   $subscription_enabled = 1;
                                }else{
                                    $subscription_enabled = 0;
                                }
                                
                            }
                            if($field->field_type == 'Username') {
                                $error_span_id = 'username-error';
                            } else if($field->field_type == 'UserPassword') {
                                $error_span_id = 'pwd-error';
                            } else {
                                $error_span_id = strtolower($field->field_type)."_{$field_id}-error";
                            }
                            if ($field->field_type != "Address" && $field->field_type != "WCBilling" && $field->field_type != "WCShipping" && $field->field_type != "Price" && $field->field_type != "Radio" && $field->field_type != "Checkbox" && $field->field_type != "Terms") {
                                echo "<span class='rmform-error-message' id='rmform-".wp_kses_post((string)$error_span_id)."'></span>";
                            }

                            if ($field->field_type != "Address" && $field->field_type != "WCBilling" && $field->field_type != "WCShipping" && $field->field_type != "Price" && $field->field_type != "Radio" && $field->field_type != "Checkbox" && $field->field_type != "Terms") {
                                if (isset($field->field_options->help_text) && $field->field_options->help_text !== "") {
                                    echo "<div id='rm-note-".wp_kses_post((string)$field_id)."' class='rmform-note' style='display: none;'>".wp_kses_post((string)$field->field_options->help_text)."</div>";
                                }
                            }
                            
                            echo "</div>";
                            // 
                            if ($field->field_type == 'UserPassword' && isset($field->field_options->en_confirm_pwd) && $field->field_options->en_confirm_pwd == 1 && isset($field->field_options->cnf_pass_position) && $field->field_options->cnf_pass_position === "below") {
                                echo "<div class='rmform-field' data-field-id='".wp_kses_post((string)$field_id)."'>";
                                //<!-rmform-has-error rm-form-has-hover rm-form-is-active -->
                                $field_method = strtolower("create_cnf_userpassword_field");
                                if(method_exists($field_factory, $field_method)) {
                                    $field_factory->$field_method($field);
                                }
                                // echo "<div id='rm-cnf-note-".wp_kses_post((string)$field_id)."' class='rmform-note' style='display: none;'></div>"; // karan's code updated by akash
                                echo "<span class='rmform-error-message' id='rmform-password_confirmation-error'></span>";
                                echo "</div>";
                                $pass_match_err = $field->field_options->pass_mismatch_err;
                            }
                            // 
                            echo "</div>";
                            
                            if ($field->field_type == 'Email' && !is_user_logged_in() && isset($field->field_options->en_confirm_email) && $field->field_options->en_confirm_email == 1 && absint($field->is_field_primary) == 1) {
                                echo "<div id='rm-cnf-".wp_kses_post(strtolower($field->field_type))."-".wp_kses_post((string)$field_id)."' class='rmform-col rmform-col-".wp_kses_post((string)$col_array[$index])."'>";
                                echo "<div class='rmform-field' data-field-id='".wp_kses_post((string)$field_id)."'>";
                                //<!-rmform-has-error rm-form-has-hover rm-form-is-active -->
                                $field_method = strtolower("create_cnf_email_field");
                                if(method_exists($field_factory, $field_method)) {
                                    $field_factory->$field_method($field);
                                }
                                // echo "<div id='rm-cnf-note-".wp_kses_post((string)$field_id)."' class='rmform-note' style='display: none;'></div>"; // karan's code updated by akash
                                echo "<span class='rmform-error-message' id='rmform-email_confirmation-error'></span>";
                                echo "</div>";
                                echo "</div>";
                            }
                            if ($field->field_type == 'UserPassword' && 
                                    isset($field->field_options->en_confirm_pwd) && 
                                    $field->field_options->en_confirm_pwd == 1 && 
                                    (
                                        (isset($field->field_options->cnf_pass_position) && $field->field_options->cnf_pass_position === "right") || 
                                        (!isset($field->field_options->cnf_pass_position))
                                    ) 
                                ) {
                                echo "<div id='rm-cnf-".wp_kses_post(strtolower($field->field_type))."-".wp_kses_post((string)$field_id)."' class='rmform-col rmform-col-".wp_kses_post((string)$col_array[$index])."'>";
                                echo "<div class='rmform-field' data-field-id='".wp_kses_post((string)$field_id)."'>";
                                //<!-rmform-has-error rm-form-has-hover rm-form-is-active -->
                                $field_method = strtolower("create_cnf_userpassword_field");
                                if(method_exists($field_factory, $field_method)) {
                                    $field_factory->$field_method($field);
                                }
                                // echo "<div id='rm-cnf-note-".wp_kses_post((string)$field_id)."' class='rmform-note' style='display: none;'></div>"; // karan's code updated by akash
                                echo "<span class='rmform-error-message' id='rmform-password_confirmation-error'></span>";
                                echo "</div></div>";
                                $pass_match_err = $field->field_options->pass_mismatch_err;
                            }
                        }
                        echo "</div></div>";
                    }
                    echo "</div>";
                }
                
                if(!empty($form->fields)) {
                    if($has_pages) {
                        echo "<div id='rm-last-fields' style='display:none'>";
                    } else {
                        echo "<div id='rm-last-fields'>";
                    }
                    // Adding user role field
                    if(absint($form->form_type) == RM_REG_FORM && !is_user_logged_in()) {
                        $custom_role_data = get_option('rm_option_user_role_custom_data');
                        if(!empty($form->form_options->form_should_user_pick)) {
                            $roles_arr = array();
                            $paid_role = false;
                            $form->form_user_role = maybe_unserialize($form->form_user_role);
                            foreach($form->form_user_role as $role) {
                                if(isset($custom_role_data[$role]) && $custom_role_data[$role]->is_paid) {
                                    $roles_arr[$role] = $custom_role_data[$role]->amount;
                                    $paid_role = true;
                                } else {
                                    $roles_arr[$role] = "0";
                                }
                            }
                            echo "<div class='rmform-row'>";
                            echo "<div class='rmform-col rmform-col-12'>";
                            echo "<div class='rmform-field'>";
                            echo "<label class='rmform-label'>".wp_kses_post((string)$form->form_options->form_user_field_label)."</label>";
                            if($paid_role) {
                                echo "<select name='rm_user_role' data-rmfieldtype='price' data-rmfieldprice='".esc_html(json_encode($roles_arr))."'>";
                                $price_fields++;
                            } else {
                                echo "<select name='rm_user_role'>";
                            }
                            foreach($form->form_user_role as $role) {
                                if(isset($custom_role_data[$role]) && $custom_role_data[$role]->is_paid) {
                                    $curr_pos = get_option('rm_option_currency_symbol_position', 'before');
                                    $curr_sym = RM_Utilities_Revamp::get_currency_symbol(get_option('rm_option_currency', 'USD'));
                                    if($curr_pos == 'before') {
                                        echo "<option value='".wp_kses_post((string)$role)."'>".wp_kses_post(ucfirst($role))." (".wp_kses_post($curr_sym.$custom_role_data[$role]->amount).")</option>";
                                    } else {
                                        echo "<option value='".wp_kses_post((string)$role)."'>".wp_kses_post(ucfirst($role))." (".wp_kses_post($custom_role_data[$role]->amount.$curr_sym).")</option>";
                                    }
                                } else {
                                    echo "<option value='".wp_kses_post((string)$role)."'>".wp_kses_post(ucfirst($role))."</option>";
                                }
                            }
                            echo "</select>";
                            echo "</div>";
                            echo "</div>";
                            echo "</div>";
                        } else {
                            if(!isset($custom_role_data[$form->default_user_role])) {
                                $form->default_user_role = empty($form->default_user_role) ? 'subscriber' : strtolower($form->default_user_role);
                                echo "<input type='hidden' name='rm_user_role' value='".wp_kses_post((string)$form->default_user_role)."'>";
                            } else {
                                echo "<input type='hidden' name='paid_role".wp_kses_post((string)$form_id)."' id='paid_role_".wp_kses_post((string)$form_id)."_".wp_kses_post((string)$rm_form_diary[$form_id])."' data-rmdefrole='".wp_kses_post((string)$form->default_user_role)."' data-rmcustomroles='".wp_kses_post(wp_json_encode($custom_role_data))."' data-rmfieldtype='price' data-rmfieldprice='".wp_kses_post((string)$custom_role_data[$form->default_user_role]->amount)."' value='".wp_kses_post((string)$custom_role_data[$form->default_user_role]->amount)."'>";
                                $price_fields++;
                            }
                        }
                    }
                    // Adding form ID field
                    echo "<input type='hidden' name='form_id' value='".wp_kses_post((string)$form_id)."'>";
                    echo "<input type='hidden' name='form_no' value='".wp_kses_post((string)$rm_form_diary[$form_id])."'>";
                    echo "<input type='hidden' name='rm_cond_hidden_fields' id='rm_cond_hidden_fields' value=''>";
                    if(!$prefilled) {
                        // Adding stat ID field
                        echo "<input type='hidden' name='stat_id' value='".wp_kses_post((string)$stat_id)."'>";
                        // Adding subscription checkboxes
                        $this->show_subscription_checkboxes($form);
                    } else {
                        // Adding prefilled field
                        echo "<input type='hidden' name='rm_prefilled' value='1'>";
                        echo "<input type='hidden' name='rm_slug' value='rm_user_form_edit_sub'>";
                    }
                    //Adding Payment Gateway for Subscriptions
                    if($price_fields  == 0 && !empty($subscription_enabled)) {
                        $payment_gateways = get_option('rm_option_payment_gateway');
                        if(!empty($payment_gateways)){
                            if(in_array('offline',$payment_gateways)){
                                unset($payment_gateways[array_search ('offline', $payment_gateways)]);
                            }
                            if(in_array('anet',$payment_gateways)){
                                unset($payment_gateways[array_search ('anet', $payment_gateways)]);
                            }
                            if(in_array('rm_wepay',$payment_gateways)){
                                unset($payment_gateways[array_search ('rm_wepay', $payment_gateways)]);
                            }
                        }
                        if(empty($payment_gateways)) {
                            esc_html_e('No payment gateway enabled. Please enable a payment gateway to process payment.', 'custom-registration-form-builder-with-submission-manager'); 
                            //return;
                        } else {
                            if(!defined('REGMAGIC_ADDON')) {
                                $payment_gateways = array("paypal");
                            }
                            $def_proc = get_option('rm_option_default_payment_method');
                            $def_proc = empty($def_proc) ? 'paypal' : $def_proc;
                            if(get_option("rm_option_hide_pay_selector")) {
                                echo "<div id='rm_form_payment_selector' class='rmform-payment-selector'>";
                                echo "<div class='rm_payment_options'>";
                                echo "<div class='rmform-payment-option'>";
                                echo "<input type='hidden' id='rm_gateway_".wp_kses_post((string)$def_proc)."' value='".wp_kses_post((string)$def_proc)."' name='rm_payment_method'>";
                                echo "</div>";
                            } else {
                                $pay_procs_options = array(
                                    "paypal" => "<img src='" . RM_IMG_URL . "/paypal-logo.png" . "'></img>",
                                    "stripe" => "<img src='" . RM_IMG_URL . "/stripe-logo.png" . "'></img>",
                                );
                                
                                echo "<div id='rm_form_payment_selector' class='rmform-payment-selector'>";
                                echo "<label class='rmform-label'>".esc_html__("Select a payment method", 'custom-registration-form-builder-with-submission-manager')."<sup class='required'>&nbsp;*</sup></label>";
                                echo "<div class='rm_payment_options'>";
                                foreach($payment_gateways as $gateway) {
                                    $gateway = $gateway == 'anet' ? 'anet_sim' : $gateway;
                                    $def_proc = $def_proc == 'anet' ? 'anet_sim' : $def_proc;
                                    echo "<div class='rmform-payment-option'>";
                                    if($gateway == $def_proc) {
                                        echo "<input type='radio' id='rm_gateway_".wp_kses_post((string)$gateway)."' value='".wp_kses_post((string)$gateway)."' name='rm_payment_method' checked>";
                                    } else {
                                        echo "<input type='radio' id='rm_gateway_".wp_kses_post((string)$gateway)."' value='".wp_kses_post((string)$gateway)."' name='rm_payment_method'>";
                                    }
                                    echo "<label for='rm_gateway_".wp_kses_post((string)$gateway)."'>".wp_kses_post($pay_procs_options[$gateway])."</label>";
                                    echo "</div>";
                                }
                            }
                            echo "</div>";
                            echo "</div>";
                        }
                    }
                    // Adding payment gateway selector field
                    if($price_fields > 0 && empty($subscription_enabled)) {
                        $payment_gateways = get_option('rm_option_payment_gateway');

                        if(empty($payment_gateways)) {
                            //esc_html_e('No payment gateway enabled. Please enable a payment gateway to process payment.', 'custom-registration-form-builder-with-submission-manager'); return;
                        } else {
                            if(!defined('REGMAGIC_ADDON')) {
                                $payment_gateways = array("paypal");
                            }
                            $def_proc = get_option('rm_option_default_payment_method');
                            $def_proc = empty($def_proc) ? 'paypal' : $def_proc;
                            if(get_option("rm_option_hide_pay_selector")) {
                                echo "<div id='rm_form_payment_selector' class='rmform-payment-selector'>";
                                echo "<div class='rm_payment_options'>";
                                echo "<div class='rmform-payment-option'>";
                                echo "<input type='hidden' id='rm_gateway_".esc_html((string)$def_proc)."' value='".esc_html((string)$def_proc)."' name='rm_payment_method'>";
                                echo "</div>";
                            } else {
                                $pay_procs_options = array(
                                    "paypal" => "<img src='" . RM_IMG_URL . "/paypal-logo.png" . "'></img>",
                                    "stripe" => "<img src='" . RM_IMG_URL . "/stripe-logo.png" . "'></img>",
                                    "anet_sim" => "<img style='width:auto;' src='" . RM_IMG_URL . "premium/adn-small.png" . "'>",
                                    "rm_wepay" => "<img style='width:auto;' src='" . RM_IMG_URL . "premium/rm_wepay.png" . "'>",
                                    "offline"=>"<strong>".esc_html__("Offline", 'custom-registration-form-builder-with-submission-manager')."</strong>"
                                );
                                echo "<div id='rm_form_payment_selector' class='rmform-payment-selector'>";
                                echo "<label class='rmform-label'>".esc_html__("Select a payment method", 'custom-registration-form-builder-with-submission-manager')."<sup class='required'>&nbsp;*</sup></label>";
                                echo "<div class='rm_payment_options'>";
                                foreach($payment_gateways as $gateway) {
                                    $gateway = $gateway == 'anet' ? 'anet_sim' : $gateway;
                                    $def_proc = $def_proc == 'anet' ? 'anet_sim' : $def_proc;
                                    echo "<div class='rmform-payment-option'>";
                                    if($gateway == $def_proc) {
                                        echo "<input type='radio' id='rm_gateway_".wp_kses_post((string)$gateway)."' value='".wp_kses_post((string)$gateway)."' name='rm_payment_method' checked>";
                                    } else {
                                        echo "<input type='radio' id='rm_gateway_".wp_kses_post((string)$gateway)."' value='".wp_kses_post((string)$gateway)."' name='rm_payment_method'>";
                                    }
                                    echo "<label for='rm_gateway_".wp_kses_post((string)$gateway)."'>".wp_kses_post($pay_procs_options[$gateway])."</label>";
                                    echo "</div>";
                                    if ($gateway === 'paypal') {
                                        $modern_paypal = get_option('rm_option_paypal_modern_enable', false);
                                        $client_id = get_option('rm_option_paypal_client_id', '');
                                        $client_secret = get_option('rm_option_paypal_secret_key', '');
                                        if ($modern_paypal && !empty($client_id) && empty($client_secret)) {
                                            echo "<div class='rm-paypal-modern-notice' style='background:#fff3cd;color:#856404;border:1px solid #ffeeba;padding:12px 16px;margin:10px 0;border-radius:4px;display:flex;align-items:center;font-weight:500;font-size:15px;'>"
                                                ."<span style='margin-right:10px;display:inline-flex;align-items:center;'><svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10' fill='#ff0000ff'/><path d='M12 8v4m0 4h.01' stroke='#ffffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></span>"
                                                .esc_html__('PayPal payment gateway isn\'t fully configured. Payments may not get updated correctly. Please contact site administrator to resolve this issue.', 'custom-registration-form-builder-with-submission-manager')
                                                ."</div>";
                                        }
                                    }
                                }
                            }
                            if(isset($form->form_options->show_total_price[0]) && $form->form_options->show_total_price[0] == 1) {
                                $total_price_localized_string = esc_html__('Total Price: %s', 'custom-registration-form-builder-with-submission-manager');
                                $currency = get_option('rm_option_currency');
                                $curr_symbol = RM_Utilities_Revamp::get_currency_symbol($currency);
                                $curr_pos = get_option('rm_option_currency_symbol_position');
                                $price_formatting_data = wp_json_encode(array("loc_total_text" => $total_price_localized_string, "symbol" => $curr_symbol, "pos" => $curr_pos));
                                echo "<div class='rmrow rm_total_price' style='".wp_kses_post((string)$form->form_options->style_label)."' data-rmpriceformat='".wp_kses_post((string)$price_formatting_data)."'></div>";
                            }
                            echo "</div>";
                            echo "</div>";
                        }
                    }
                    // Adding reCaptcha field
                    if(get_option('rm_option_enable_captcha') == 'yes') {
                        if(defined('REGMAGIC_ADDON') && $form->form_options->enable_captcha == 'no') {

                        } else {
                            echo '<div class="rmform-row"> <div class="rmform-row-field-wrap"><div class="rmform-col rmform-col-12"><div class="rmform-field">';
                            $captcha_ver = get_option('rm_option_recaptcha_v');
                            if($captcha_ver == 'v2') {
                                $key = get_option('rm_option_public_key');
                                $locale = get_locale();
                                $lang = explode('_', (string)$locale);
                                wp_enqueue_script('rm-grecaptcha', "https://www.google.com/recaptcha/api.js?onload=rmInitCaptchaV2&render=explicit&hl=$lang[0]");
                                wp_enqueue_script('rm-new-frontend-field', RM_BASE_URL.'public/js/new_frontend_field.js', array('jquery','jquery-ui-datepicker'));
                                echo "<div class='g-recaptcha' data-sitekey='".esc_attr((string)$key)."'></div>";
                            } elseif($captcha_ver == 'v3') {
                                $v3_key = get_option('rm_option_public_key3');
                                wp_enqueue_script('rm-grecaptcha', 'https://www.google.com/recaptcha/api.js?onload=rmInitCaptcha&render='.$v3_key);
                                echo '<input type="hidden" class="g-recaptcha-response" id="g-recaptcha-response-'.esc_attr((string)$form_id).'-'.esc_attr((string)$rm_form_diary[$form_id]).'" name="g-recaptcha-response">';
                                echo "<script>function rmInitCaptcha() { grecaptcha.ready(function() { grecaptcha.execute('".esc_attr((string)$v3_key)."', {action: 'submit'}).then(function(token) { document.getElementById('g-recaptcha-response-".esc_attr((string)$form_id)."-".esc_attr((string)$rm_form_diary[$form_id])."').value = token; }); }); }</script>";
                            }
                            echo "<div id='rm-recaptcha-error' class='rmform-error-message'></div>";
                            echo '</div></div></div></div>';
                        }
                    }

                    do_action('rm_extend_field_before_submit', $form);
                    
                    echo "</div>";
                    // Rendering submit button
                    $this->render_submit_button($form, $has_pages, $price_fields, $prefilled, $ex_sub_id);
                }
                echo "</form></div></div>";
                if(isset($pass_match_err)) {
                    $this->output_validation_js($form->form_id, $pass_match_err);
                } else {
                    $this->output_validation_js($form->form_id);
                }
            }
        }
    }

    private function create_stat_entry($form_id = null) {
        if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            $user_ip = $_SERVER['REMOTE_ADDR'];
        } else {
            esc_html_e("Invalid form access", 'custom-registration-form-builder-with-submission-manager');
            return;
        }

        global $wpdb;
        $visited_on = time();
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $ua_string = $_SERVER['HTTP_USER_AGENT'];
        else
            $ua_string = "no_user_agent_found";

        require_once RM_EXTERNAL_DIR . 'Browser/Browser.php';
        $browser = new RM_Browser($ua_string);
        $browser_name = $browser->getBrowser();

        $wpdb->insert(
            "{$wpdb->prefix}rm_stats",
            array(
                'form_id' => $form_id,
                'user_ip' => $user_ip,
                'browser_name' => $browser_name,
                'ua_string' => $ua_string,
                'visited_on' => $visited_on                
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );

        return $wpdb->insert_id;
    }

    private function update_stat_entry($stat_id = null, $sub_id = null, $banned = false) {
        global $wpdb;
        $submitted_on = time();
        $visited_on = $wpdb->get_var($wpdb->prepare("SELECT visited_on from {$wpdb->prefix}rm_stats WHERE stat_id = %d", $stat_id));
        if(!empty($visited_on)) {
            $diff_in_secs = $submitted_on - $visited_on;
            if($banned) {
                $wpdb->update(
                    "{$wpdb->prefix}rm_stats",
                    array(
                        'submitted_on' => 'banned'
                    ),
                    array(
                        'stat_id' => $stat_id
                    ),
                    array(
                        '%s'
                    ),
                    array(
                        '%d'
                    )
                );
            } else {
                $wpdb->update(
                    "{$wpdb->prefix}rm_stats",
                    array(
                        'submitted_on' => $submitted_on,
                        'time_taken' => $diff_in_secs,
                        'submission_id' => $sub_id
                    ),
                    array(
                        'stat_id' => $stat_id
                    ),
                    array(
                        '%s',
                        '%s',
                        '%d'
                    ),
                    array(
                        '%d'
                    )
                );
            }
        } else {
            return;
        }
    }

    private function banned_check() {
        $banned_ip_formats = maybe_unserialize(get_option('rm_option_banned_ip'));
        if(empty($banned_ip_formats)) {
            return false;
        }
        $banned = false;
        if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            $user_ip = $_SERVER['REMOTE_ADDR'];
        } else {
            return true;
        }
        
        if((bool)filter_var($user_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $sanitized_user_ip = $user_ip;
        } else {
            // Filtering out the multiple IP's
            $ip_as_arr = explode(',', (string)$user_ip);
            if(isset($ip_as_arr[0])) {
                $new_ip_as_arr = explode('.', (string)$ip_as_arr[0]);
                if(count($new_ip_as_arr) !== 4) {
                    return true;
                }
            }

            $sanitized_user_ip = sprintf("%'3s.%'3s.%'3s.%'3s", $new_ip_as_arr[0], $new_ip_as_arr[1], $new_ip_as_arr[2], $new_ip_as_arr[3]);
        }
        
        if(is_array($banned_ip_formats)) {
            foreach($banned_ip_formats as $banned_ip_format) {
                $matchrx = '/[0-9.]/';
                if(preg_match($matchrx, $sanitized_user_ip) === 1 && $sanitized_user_ip === $banned_ip_format) {
                    $banned = true;
                    break;
                }
            }
        }

        return $banned;
    }

    private function expiry_check($form = null) {
        //if($this->ignore_expiration)
            //return false;
        $expired = false;
        if(empty($form->form_should_auto_expire)) {
            return false;
        } else {
            $criterion = $form->form_options->form_expired_by;
            if($criterion == 'status') {
                return false;
            }
            $submission_limit = absint($form->form_options->form_submissions_limit);
            if($criterion == "date" || $criterion == "both") {
                if(isset($form->form_options->form_expiry_date)) {
                    $form_expiry_date = strtotime($form->form_options->form_expiry_date);
                    $current_time = intval(time() + (60 * 60 * floatval(get_option( 'gmt_offset', 0 ))));
        
                    if($current_time > $form_expiry_date) {
                        $expired = true;
                    }
                }
            }
            if($criterion == "submissions" || $criterion == "both") {
                global $wpdb;
                if(isset($form->form_options->exclude_pending_subs) && !empty($form->form_options->exclude_pending_subs)) {
                    $num_submissions = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}rm_submissions as sub left join {$wpdb->prefix}rm_paypal_logs as pl on sub.submission_id=pl.submission_id where pl.status='Completed' and sub.form_id=%d and sub.child_id=0", $form->form_id));
                } else {
                    $num_submissions = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}rm_submissions where form_id = %d AND child_id = 0", $form->form_id));
                }
                if($num_submissions >= $submission_limit) {
                    $expired = true;
                }
            }
        }
        return $expired;
    }

    private function after_submission_proc($params, $prevent_redirection = false) {
        global $wp;
        global $wpdb;
        $form_options = $params->form->form_options;
        if(!empty($_GET['rm_pproc_id'])) {
            $pproc = absint($_GET['rm_pproc_id']);
            $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rm_paypal_logs WHERE id = %d", $pproc));
            $params->form_id = $log->form_id;
            $params->sub_id = $log->submission_id;
        }
        $msg_str = $form_options->form_success_message != "" ? $form_options->form_success_message : $params->form_name . " " . esc_html__('Submitted', 'custom-registration-form-builder-with-submission-manager');
        if($form_options->auto_login) {}
        if (!$prevent_redirection) {
            if(isset($params->form->redirection_type)) {
                $redir_str = "<br>" . esc_html__("Redirecting you to", 'custom-registration-form-builder-with-submission-manager') . "<br>";

                if ($params->form->redirection_type === "page") {
                    $page_id = $form_options->redirect_page;
                    $page = get_post($page_id);
                    if($page instanceof WP_Post) {
                        $page_title = $page->post_title ? : '#' . $page_id . ' ' . esc_html__('(No Title)','custom-registration-form-builder-with-submission-manager');
                        $redir_str .= $page_title;
                        $this->redirect(null, true, $page_id, true);
                    }
                } else {
                    $url = $form_options->redirect_url;
                    $redir_str .= $url;
                    $this->redirect($url, false, 0, true);
                }
                return "<div class='rm_form_submit_msg'><div class='rm-post-sub-msg'>{$msg_str}</div></div><br><br>{$redir_str}";
            }
        }
        
        if($form_options->auto_login && !is_user_logged_in()) {
            $gauto_approval = get_option('rm_option_user_auto_approval');
            $prov_act_acc = get_option('rm_option_prov_act_acc');
            $prov_acc_act_criteria = get_option('rm_option_prov_acc_act_criteria');
            if($form_options->user_auto_approval == "yes" || (in_array($gauto_approval,array('yes','verify')) && $form_options->user_auto_approval=="default")) {
                if(isset($_POST['rm_payment_method']) && $_POST['rm_payment_method']=="offline") {
                    return '<div class="rm_form_submit_msg">' . $msg_str . "</div></div>";
                } elseif(isset($_REQUEST['rm_pproc']) && $_REQUEST['rm_pproc']=="success") {
                    if($form_options->user_auto_approval=="default" && $gauto_approval=='verify' && empty($prov_act_acc)) {
                        return '<div class="rm_form_submit_msg">' . $msg_str . "</div></div>";
                    }
                } elseif(isset($_REQUEST['rm_pproc']) && $_REQUEST['rm_pproc']!="success") {
                    return '<div class="rm_form_submit_msg">' . $msg_str . "</div></div>";
                }

                $msg_str .= '<div id="rm_ajax_login">'.esc_html__("Please wait while we are logging into the system.",'custom-registration-form-builder-with-submission-manager').'</div><br><br>';
                
                if(isset($params->form_id)) {
                    $current_url = home_url(add_query_arg(array(),$wp->request));
                    $current_url = add_query_arg( array('rm_success'=>'1','rm_form_id'=>$params->form_id,'rm_sub_id'=>$params->sub_id), $current_url);
                    if(!in_array($gauto_approval,array('verify')) || (in_array($gauto_approval,array('verify')) && !empty($prov_act_acc) && ($prov_acc_act_criteria=='until_user_logsout' || $prov_acc_act_criteria=='until_act_link_expires'))) {
                        $this->redirect($current_url, false, 0, true);
                    }
                }
            }
        }
        return '<div class="rm_form_submit_msg rm-form-submit-wrap"><div class="rm-form-submit-message-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div>' . $msg_str . '</div>';
    }

    private function redirect($url = '', $is_post = false, $post_id = 0, $delay = false) {
        if ($is_post && $post_id > 0) {
            $url = get_permalink($post_id);
        }

        if(headers_sent() || $delay) {
            if(defined('RM_AJAX_REQ'))
                $prefix = 'parent.';
            else
                $prefix = '';

            echo '<pre class="rm-pre-wrapper-for-script-tags"><script type="text/javascript">';
            if ($delay === true) {
                echo "window.setTimeout(function(){" . wp_kses_post((string)$prefix) . "window.location.href = '" . $url . "';}, 5000);";
            } elseif ((int) $delay) {
                echo "window.setTimeout(function(){" . wp_kses_post((string)$prefix) . "window.location.href = '" . $url . "';}, " . wp_kses_post((string)(int) $delay) . ");";
            } else {
                echo wp_kses_post((string)$prefix) . 'window.location = "' . $url . '"';
            }

            echo '</script></pre>';
        } else {
            if(isset($_SERVER['HTTP_REFERER']) AND ( $url == $_SERVER['HTTP_REFERER']))
                wp_redirect($_SERVER['HTTP_REFERER']);
            else
                wp_redirect($url);

            exit;
        }
    }

    private function is_off_limit_submission($form_id, $form_options) {
        global $wpdb;
        $submission_limit_per_ip_per_form = absint(get_option('rm_option_sub_limit_antispam'));
        
        if(defined('REGMAGIC_ADDON')) {
            $form_limit = $form_options->sub_limit_antispam;
            if($form_limit != null) {
                $submission_limit_per_ip_per_form = absint($form_limit);
            }
        }
        
        if($submission_limit_per_ip_per_form == 0)
            return false;

        //Calculate starting and ending timestamp for today.
        $N = time();
        $n = 24 * 60 * 60;
        $t = $N % $n;

        $start_ts = $N - $t;
        $end_ts = $start_ts + $n - 1;

        if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = null;
        }
    
        $res = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}rm_stats WHERE form_id = %d AND user_ip = %s AND submitted_on != 'banned' AND submitted_on BETWEEN %s AND %s", $form_id, $ip, $start_ts, $end_ts));
        
        if(empty($res))
            return false;
        
        // IMP: Do not use '<='. As it counts already done submissions which excludes current submission.
        // If already done submissios are limit-1 then allow this one. Otherwise there will be one extra submission.
        if(absint($res) < $submission_limit_per_ip_per_form)
            return false;
        else
            return true;
    }

    private function test_form_access($form, $request, $params, $edit= false) {
        $form_options = $form->form_options;
        $tresp = "RM_FAC_TR_".$form->form_id;
        $tstamp = "RM_FAC_TS_".$form->form_id;
        $tdi = "rm_fac_di_".$form->form_id;
        $factrl = $form_options->access_control;  
        $fail_msg = (isset($factrl->fail_msg) && $factrl->fail_msg) ? $factrl->fail_msg : esc_html__("Sorry, you are not authorised to access this content.", 'custom-registration-form-builder-with-submission-manager');
        $act_report = new stdClass;
        $act_report->status = 'failed';
        $act_report->html_str = '<div class="rm_fac_resp">' . $fail_msg . '</div>';

        if(isset($factrl->login) && $factrl->login == 1) {
            if(is_user_logged_in()) {
                return $act_report;
            }
        }

        if(isset($factrl->roles) && is_array($factrl->roles)) {
            $is_allowed = false;
            if(is_user_logged_in()) {
                $curr_user = wp_get_current_user();
                
                $curr_user_roles = $curr_user->roles;
                foreach ($curr_user_roles as $curr_user_role) {
                    if(in_array($curr_user_role, $factrl->roles)) {
                        $is_allowed = true;
                        break;
                    }
                }
            } else {
                $act_report->status = 'failed';
                $act_report->html_str = '';
                echo '<div class="rm_fac_resp">'.esc_html__('You need to login to access this form', 'custom-registration-form-builder-with-submission-manager').'</div>';
                echo do_shortcode('[RM_Login]');
                return $act_report;
            }

            if($is_allowed === false) {
                $act_report->status = 'failed';
                $act_report->html_str = '<div class="rm_fac_resp">' . $fail_msg . '</div>';
                return $act_report;
            } 
        }
        
        if(is_user_logged_in() && !empty($factrl->domain)) {
            $is_allowed = false;
            $user = wp_get_current_user();
            $domains = explode(',', (string)$factrl->domain);
            $parts = explode('@',(string)$user->user_email); // Separate string by @ characters (there should be only one)
            $domain = array_pop($parts); // Remove and return the last part, which should be the domain
            // Check if the domain is in our list
            if (!in_array($domain, $domains)) {
                $act_report->status = 'failed';
                $act_report->html_str = '<div class="rm_fac_resp">'.$fail_msg.'</div>';
                return $act_report;
            } else {
                $is_allowed = true;
            }
        }
       
        //Check if other access controls are enabled or not.
        if(!isset($factrl->date) && !isset($factrl->passphrase)) {
            $act_report->status = 'allowed';
            $act_report->html_str = '';
            return $act_report;
        }
        
        if(isset($_SESSION[$tresp], $_SESSION[$tstamp])) {
            $t2 = time();
            $t1 = intval($_SESSION[$tstamp]);
            
            if(($t2-$t1) < 300) { //Session value is valid only for 300 seconds.
                if($_SESSION[$tresp] === 'allowed') {
                    $act_report->status = 'allowed';
                    $act_report->html_str = '';
                    return $act_report;
                } else if(!isset($factrl->passphrase)) { //Allow reentry in case it was a pssphrase fail
                    $act_report->status = 'failed';
                    $act_report->html_str = '<div class="rm_fac_resp">'.$fail_msg.'</div>';
                    return $act_report;
                }                
            }
        }
        
        if(isset($request[$tdi])) {
            if($this->check_access_control($factrl, $request)) {         
                $act_report->status = 'allowed';
                $act_report->html_str = '';
                $_SESSION[$tresp] = 'allowed';
                $_SESSION[$tstamp] = time();
                return $act_report;
            } else {
                $act_report->status = 'failed';
                $_SESSION[$tresp] = 'failed';
                $_SESSION[$tstamp] = time();
                $act_report->html_str = '<div class="rm_fac_resp">'.$fail_msg.'</div>';
                return $act_report;
            }
        }

        $data = new stdClass;
        $data->actrl = $factrl;
        $data->form_id = $form->form_id;
        if(isset($params['without_form_tag']) && $params['without_form_tag'] == true)
            $data->no_form_tag = true;
        if($edit){
            return null;
        }

        include_once(RM_PUBLIC_DIR."views/template_rm_access_control.php");
        
        $act_report->status = 'transient';
        $act_report->html_str = '';
        
        return $act_report;
    }

    private function check_access_control($factrl, $request) {
        $is_allowed = true;

        if(isset($factrl->date)) {
            $entered_date_str = $request['rm_fac_dyear'] . '-' . $request['rm_fac_dmonth'] . '-' . $request['rm_fac_dday'];
            $entered_date = new DateTime($entered_date_str);

            if ($factrl->date->type == 'diff') {
                $curr_date = new DateTime;
                $diff = $curr_date->diff($entered_date);
                $diff_years = $diff->y;
                if ($factrl->date->lowerlimit) {
                    if ($diff_years < $factrl->date->lowerlimit)
                        $is_allowed = false;
                }
                if ($factrl->date->upperlimit) {
                    if ($diff_years > $factrl->date->upperlimit)
                        $is_allowed = false;
                }
            }
            elseif ($factrl->date->type == 'date') {
                $dt = new DateTime;
                if ($factrl->date->lowerlimit) {
                    $lldt = $dt->createFromFormat('m/d/Y H:i:s', $factrl->date->lowerlimit . ' 00:00:00');
                    if ($entered_date < $lldt)
                        $is_allowed = false;
                }
                if ($factrl->date->upperlimit) {
                    $uldt = $dt->createFromFormat('m/d/Y H:i:s', $factrl->date->upperlimit . ' 00:00:00');
                    if ($entered_date > $uldt)
                        $is_allowed = false;
                }
            }
        }

        if(isset($factrl->passphrase)) {
            $passphrases = explode("|", (string)$factrl->passphrase->passphrase);
            $passphrases = array_map('trim', $passphrases);
            if (!in_array($request['rm_fac_pass'], $passphrases))
                $is_allowed = false;
        }

        return $is_allowed;
    }

    private function output_custom_design($form = null) {
        global $rm_form_diary;
        $form_number = $rm_form_diary[$form->form_id];
        $row_class = empty($form->rows) ? '.rmform-row' : '.rmform-row-field-wrap';
        $important = ' !important';
        if($form->form_options->placeholder_css) {
            $p_css = str_replace("::-", ' #form_' . $form->form_id . "_" . $form_number .' ::-', (string)$form->form_options->placeholder_css);
            echo '<style>'.str_replace("}:-", '} #form_' . wp_kses_post((string)$form->form_id) . "_" . wp_kses_post((string)$form_number) .' ::-', wp_kses_post((string)$p_css)).'</style>';
        }
        echo '<style>';
        if($form->form_options->style_btnfield) {
            echo '.rmformui #rmform-module-' . wp_kses_post((string)$form->form_id) .' #rm_form_submit_button input[type="submit"], #rm-form-container #rm_form_submit_button input[type=\'button\'] {'.wp_kses_post((string)$form->form_options->style_btnfield).wp_kses_post((string)$important).'}';
        }
        if($form->form_options->btn_hover_color) {
            echo '.rmformui #rmform-module-' . wp_kses_post((string)$form->form_id) .' #rm_form_submit_button input[type="submit"]:hover, #rm-form-container #rm_form_submit_button input[type=\'button\']:hover { background-color:'.wp_kses_post((string)$form->form_options->btn_hover_color).wp_kses_post((string)$important).';}';
        }
        if($form->form_options->style_textfield) {
            echo '.rmformui #rmform-module-' . wp_kses_post((string)$form->form_id) . ' ' . wp_kses_post((string)$row_class) . ' input,.rmformui #rmform-module-'.wp_kses_post((string)$form->form_id) . wp_kses_post((string)$row_class) . ' select,.rmformui #rmform-module-'.wp_kses_post((string)$form->form_id) . wp_kses_post((string)$row_class) . ' textarea { '.wp_kses_post((string)$form->form_options->style_textfield).'}';
        }
        if($form->form_options->style_label) {
            echo '.rmformui #rmform-module-' . wp_kses_post((string)$form->form_id) . ' ' . wp_kses_post((string)$row_class) . ' .rmform-field'. ' > '. '.rmform-label { '.wp_kses_post((string)$form->form_options->style_label).'}';
        }
        if($form->form_options->field_bg_focus_color || $form->form_options->text_focus_color) {
            echo '.rmformui #rmform-module-' . wp_kses_post((string)$form->form_id) . ' ' . wp_kses_post((string)$row_class) . ' input:focus,.rmformui #rmform-module-'.wp_kses_post((string)$form->form_id) . wp_kses_post((string)$row_class) . ' select:focus,.rmformui #rmform-module-'.wp_kses_post((string)$form->form_id) . wp_kses_post((string)$row_class) . ' textarea:focus{';
            if($form->form_options->field_bg_focus_color) {
                echo 'background-color:'.wp_kses_post((string)$form->form_options->field_bg_focus_color).wp_kses_post((string)$important).';';
            }
            if($form->form_options->text_focus_color) {
                echo 'color:'.wp_kses_post((string)$form->form_options->text_focus_color).wp_kses_post((string)$important).';';
            }
            echo '}';
        }
        echo '</style>';
    }

    private function check_limit_by_cs($form = null, $email = null) {
        if(empty($form->form_id))
            return false;
        global $wpdb;
        
        if((isset($form->form_should_auto_expire) && !empty($form->form_should_auto_expire)) && $form->form_options->form_expired_by == 'status') {
            $statuses = maybe_unserialize($form->form_options->form_limit_by_cs);
            if(!empty($statuses)) {
                foreach($statuses as $status) {
                    if($status === '')
                        continue;
                    $st_arr = explode(':', (string)$status);
                    
                    if(count($st_arr) == 2) {
                        $form_id = $st_arr[0];
                        $status = $st_arr[1];
                    } else {
                        $status = $st_arr[0];
                    }
                    
                    $count = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}rm_submissions as sub left join {$wpdb->prefix}rm_custom_status as cs on sub.submission_id=cs.submission_id where user_email=%s and sub.form_id=%d and status_index=%s", $email, $form_id, $status));
                    if($count == 0)
                        return false;
                }
                return true;
            }
        }
        
        return true;
    }

    private function is_email_banned($email = null) {
        $banned_email_formats = maybe_unserialize(get_option('rm_option_banned_email'));
        $banned = false;

        if(is_array($banned_email_formats)) {
            foreach($banned_email_formats as $banned_email_format) {
                if(!$banned_email_format)
                    $banned = false;

                $matchrx = '/';

                $gen_regex = array('?' => '.',
                    '*' => '.*',
                    '.' => '\.'
                );

                $formatlen = strlen($banned_email_format);

                for($i = 0; $i < $formatlen; $i++) {
                    if($banned_email_format[$i] == '?' || $banned_email_format[$i] == '.' || $banned_email_format[$i] == '*')
                        $matchrx .= $gen_regex[$banned_email_format[$i]];
                    else
                        $matchrx .= $banned_email_format[$i];
                }

                $matchrx .= '/';

                //Following check is employed instead preg_match so that partial matches
                //will not get selected unless user specifies using wildcard '*'.      
                $test = preg_replace($matchrx, '', $email);

                if($test == '') {
                    $banned = true;
                    return $banned;
                } else {
                    $banned = false;
                }
            }
        }

        return $banned;
    }

    private function get_rows_by_pages($unord_rows = array(), $ord_pages = array()) {
        if(empty($ord_pages)) {
            return array();
        }

        $ord_rows = array();

        foreach($ord_pages as $ord_page) {
            $ord_rows[absint($ord_page)] = array();
        }
        
        foreach($unord_rows as $unord_row) {
            if(isset($ord_rows[absint($unord_row->page_no)-1]))
                array_push($ord_rows[absint($unord_row->page_no)-1],$unord_row);
        }

        return $ord_rows;
    }

    private function output_validation_js($form_id = null, $pass_match_err = "") {
        wp_enqueue_script('revamp-field-validation', RM_BASE_URL . 'public/js/revamp-field-validation.js');
        wp_localize_script('revamp-field-validation', 'rmValidationJS', array(
            'formID' => $form_id,
            'login' => intval(is_user_logged_in()),
            'pwdRules' => get_option('rm_option_enable_custom_pw_rests', false) == 'yes' ? get_option('rm_option_custom_pw_rests', false) : false,
            'texts' => array(
                'required' => esc_html__("This field is required", 'custom-registration-form-builder-with-submission-manager'),
                'passmatch' => empty($pass_match_err) ? esc_html__("Password does not match", 'custom-registration-form-builder-with-submission-manager') : $pass_match_err,
                'emailmatch' => esc_html__("Email does not match", 'custom-registration-form-builder-with-submission-manager'),
                'passupper' => esc_html__("Password must contain an uppercase letter", 'custom-registration-form-builder-with-submission-manager'),
                'passnumber' => esc_html__("Password must contain a number", 'custom-registration-form-builder-with-submission-manager'),
                'passspecial' => esc_html__("Password must contain a special character", 'custom-registration-form-builder-with-submission-manager'),
                'passmin' => esc_html__("Password must be at least %s characters long", 'custom-registration-form-builder-with-submission-manager'),
                'passmax' => esc_html__("Password must not be longer than %s characters", 'custom-registration-form-builder-with-submission-manager'),
                'emailexists' => esc_html__("A user with this email already exists", 'custom-registration-form-builder-with-submission-manager'),
                'emailformat' => esc_html__("Incorrect email format", 'custom-registration-form-builder-with-submission-manager'),
                'urlformat' => esc_html__("Incorrect website/URL format", 'custom-registration-form-builder-with-submission-manager'),
                'fbformat' => esc_html__("Incorrect Facebook URL format", 'custom-registration-form-builder-with-submission-manager'),
                'twformat' => esc_html__("Incorrect Twitter URL format", 'custom-registration-form-builder-with-submission-manager'),
                'instaformat' => esc_html__("Incorrect Instagram URL format", 'custom-registration-form-builder-with-submission-manager'),
                'lkdformat' => esc_html__("Incorrect LinkedIn URL format", 'custom-registration-form-builder-with-submission-manager'),
                'ytformat' => esc_html__("Incorrect YouTube URL format", 'custom-registration-form-builder-with-submission-manager'),
                'vkformat' => esc_html__("Incorrect VKontacte URL format", 'custom-registration-form-builder-with-submission-manager'),
                'skypeformat' => esc_html__("Incorrect Skype format", 'custom-registration-form-builder-with-submission-manager'),
                'scformat' => esc_html__("Incorrect SoundCloud URL format", 'custom-registration-form-builder-with-submission-manager'),
                'customformat' => esc_html__("Incorrect format", 'custom-registration-form-builder-with-submission-manager'),
                'mobileformat' => esc_html__("Incorrect mobile number format", 'custom-registration-form-builder-with-submission-manager'),
                'filetype' => esc_html__("Invalid file type. Allowed types: %s", 'custom-registration-form-builder-with-submission-manager'),
                'minlength' => esc_html__("Value cannot be less than %s characters", 'custom-registration-form-builder-with-submission-manager'),
                'maxlength' => esc_html__("Value cannot be more than %s characters", 'custom-registration-form-builder-with-submission-manager'),
                'minnum' => esc_html__("Value cannot be less than %s", 'custom-registration-form-builder-with-submission-manager'),
                'maxnum' => esc_html__("Value cannot be more than %s", 'custom-registration-form-builder-with-submission-manager'),
                'recaptcha' => esc_html__("Please provide reCaptcha verification", 'custom-registration-form-builder-with-submission-manager'),
            ),
        ));
    }

    private function is_username_reserved($username_to_check) {
        if(empty($username_to_check))
            return false;

        $reserved_usernames = get_option('rm_option_blacklisted_usernames');

        if(!$reserved_usernames || !is_array($reserved_usernames) || count($reserved_usernames) == 0)
            return false;

        if(in_array($username_to_check, $reserved_usernames))
            return true;
        else
            return false;
    }

    private function show_subscription_checkboxes($form = null) {
        if(get_option('rm_option_enable_mailchimp') == 'yes' && $form->form_options->form_is_opt_in_checkbox == 1 && (isset($form->form_options->enable_mailchimp[0]) && $form->form_options->enable_mailchimp[0] == 1)) {
            //This outer div is added so that the optin text can be made full width by CSS.
            echo '<div class="rm_optin_text rm-subscription-wrap">';

            if ($form->form_options->form_opt_in_default_state == 'Checked')
                echo '<input id="rm_subscribe_mc" type="checkbox" name="rm_subscribe_mc[]" value="1" checked>';
            else
                echo '<input id="rm_subscribe_mc" type="checkbox" name="rm_subscribe_mc[]" value="1">';

            if(!empty($form->form_options->form_opt_in_text))
                echo '<label for="rm_subscribe_mc">'.wp_kses_post($form->form_options->form_opt_in_text).'</label>';
            else
                echo '<label for="rm_subscribe_mc">'.esc_html__('Subscribe for emails', 'custom-registration-form-builder-with-submission-manager').'</label>';

            echo '</div>';
        }

        if(isset($form->form_options->form_is_opt_in_checkbox_mp[0]) && isset($form->form_options->enable_mailpoet[0]) && $form->form_options->form_is_opt_in_checkbox_mp[0] == 1 && $form->form_options->enable_mailpoet[0] == 1 && (is_plugin_active('mailpoet/mailpoet.php') || is_plugin_active('wysija-newsletters/index.php'))) {  
            //This outer div is added so that the optin text can be made full width by CSS.
            echo '<div class="rm_optin_text">';
            
            if($form->form_options->form_opt_in_default_state_mp == 'Checked')
                echo '<input id="rm_subscribe_mp" type="checkbox" name="rm_subscribe_mp[]" value="1" checked>';
            else
                echo '<input id="rm_subscribe_mp" type="checkbox" name="rm_subscribe_mp[]" value="1">';

            if(!empty($form->form_options->form_opt_in_text_mp))
                echo '<label for="rm_subscribe_mp">'.wp_kses_post($form->form_options->form_opt_in_text_mp).'</label>';
            else
                echo '<label for="rm_subscribe_mp">'.esc_html__('Subscribe for emails', 'custom-registration-form-builder-with-submission-manager').'</label>';
            echo '</div>';
        }

        if(isset($form->form_options->form_is_opt_in_checkbox_nl[0]) && isset($form->form_options->enable_newsletter[0]) && $form->form_options->form_is_opt_in_checkbox_nl[0] == 1 && $form->form_options->enable_newsletter[0] == 1 && is_plugin_active('newsletter/plugin.php')) {
            //This outer div is added so that the optin text can be made full width by CSS.
            echo '<div class="rm_optin_text">';
            
            if($form->form_options->form_opt_in_default_state_nl == 'Checked')
                echo '<input id="rm_subscribe_nl" type="checkbox" name="rm_subscribe_nl[]" value="1" checked>';
            else
                echo '<input id="rm_subscribe_nl" type="checkbox" name="rm_subscribe_nl[]" value="1">';

            if(!empty($form->form_options->form_opt_in_text_nl))
                echo '<label for="rm_subscribe_nl">'.wp_kses_post($form->form_options->form_opt_in_text_nl).'</label>';
            else
                echo '<label for="rm_subscribe_nl">'.esc_html__('Subscribe for emails', 'custom-registration-form-builder-with-submission-manager').'</label>';
            echo '</div>';
        }
    }

}