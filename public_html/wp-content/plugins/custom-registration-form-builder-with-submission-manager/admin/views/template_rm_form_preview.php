<div class="rm-modal-container">
    <div class="rm-field-row-wrap" id="rm-fields-data-content">
    <?php
        $gopt = new RM_Options();
        if(defined('REGMAGIC_ADDON'))
            wp_enqueue_script('rm_front', RM_ADDON_BASE_URL . 'public/js/script_rm_front.js', array('jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-tabs', 'jquery-ui-datepicker'), RM_PLUGIN_VERSION, false);
        else
            wp_enqueue_script('rm_front', RM_BASE_URL . 'public/js/script_rm_front.js', array('jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-tabs', 'jquery-ui-datepicker'), RM_PLUGIN_VERSION, false);
        wp_enqueue_style('rm_form_preview', RM_BASE_URL . 'public/css/rm-form-preview.css', array(), RM_PLUGIN_VERSION, 'all');
        $rm_ajax_data= array(
            "url"=>admin_url('admin-ajax.php'),
            "nonce"=>wp_create_nonce('rm_ajax_secure'),
            "gmap_api"=>$gopt->get_value_of("google_map_key"),
            'no_results'=>__('No Results Found','custom-registration-form-builder-with-submission-manager'),
            'invalid_zip'=>__('Invalid Zip Code','custom-registration-form-builder-with-submission-manager'),
            'request_processing'=>__('Please wait...','custom-registration-form-builder-with-submission-manager'),
            'hours'=>__('Hours','custom-registration-form-builder-with-submission-manager'),
            'minutes'=>__('Minutes','custom-registration-form-builder-with-submission-manager'),
            'seconds'=>__('Seconds','custom-registration-form-builder-with-submission-manager'),
            'days'=>__('Days','custom-registration-form-builder-with-submission-manager'),
            'months'=>__('Months','custom-registration-form-builder-with-submission-manager'),
            'years'=>__('Years','custom-registration-form-builder-with-submission-manager'),
            'tax_enabled'=>get_site_option('rm_option_enable_tax', null),
            'tax_type'=>get_site_option('rm_option_tax_type', null),
            'tax_fixed'=>round(floatval(get_site_option('rm_option_tax_fixed', null)),2),
            'tax_percentage'=>round(floatval(get_site_option('rm_option_tax_percentage', null)),2),
            'tax_rename'=>esc_html($gopt->get_value_of('tax_rename')),
        );
        if(defined('REGMAGIC_ADDON')) {
            $login_service= new RM_Login_Service();
            $auth_options= $login_service->get_auth_options();
            $rm_ajax_data['max_otp_attempt'] = !empty($auth_options['en_resend_otp']) ? $auth_options['otp_resend_limit'] : 0;
        }
        wp_localize_script('rm_front','rm_ajax',$rm_ajax_data);
        wp_enqueue_script('rm_front');
        
        wp_enqueue_script('rm_front_form_script', RM_BASE_URL."public/js/rm_front_form.js",array('rm_front', 'jquery'), RM_PLUGIN_VERSION, false);
        wp_localize_script('rm_front_form_script','rm_ajax',$rm_ajax_data);
        wp_enqueue_script('rm_password_utility', RM_BASE_URL."public/js/password-utility.js",array('jquery'));
        //Register jQ validate scripts but don't actually enqueue it. Enqueue it from within the shortcode callback.
        wp_enqueue_script('rm_jquery_validate', RM_BASE_URL."public/js/jquery.validate.min.js", array('jquery'), RM_PLUGIN_VERSION);
        wp_enqueue_script('rm_jquery_validate_add', RM_BASE_URL."public/js/additional-methods.min.js", array('jquery'), RM_PLUGIN_VERSION);
        wp_enqueue_script('rm_jquery_conditionalize', RM_BASE_URL."public/js/conditionize.jquery.js", array('jquery'), RM_PLUGIN_VERSION);
        wp_enqueue_script('rm_jquery_paypal_checkout', RM_BASE_URL."public/js/paypal_checkout_utility.js", array('jquery'), RM_PLUGIN_VERSION);
        wp_enqueue_script('google_charts', 'https://www.gstatic.com/charts/loader.js');
        wp_enqueue_script("rm_chart_widget",RM_BASE_URL."public/js/google_chart_widget.js");
        $service = new RM_Services();
        $gmap_api_key = $service->get_setting('google_map_key');
        if(!empty($gmap_api_key)){
            wp_enqueue_script ('google_map_key', 'https://maps.googleapis.com/maps/api/js?key='.$gmap_api_key.'&libraries=places');
            wp_enqueue_script("rm_map_widget_script",RM_BASE_URL."public/js/map_widget.js");
        }
        wp_enqueue_script("rm_pwd_strength",RM_BASE_URL."public/js/password.min.js", array('jquery'));
        wp_enqueue_script("rm_mobile_script", RM_BASE_URL . "public/js/mobile_field/intlTelInput.min.js");
        wp_enqueue_style("rm_mobile_style", RM_BASE_URL . "public/css/mobile_field/intlTelInput.min.css");
        wp_localize_script('rm_mobile_script','rm_country_list', RM_Utilities::get_countries() );
        wp_enqueue_script("rm_mask_script", RM_BASE_URL . "public/js/jquery.mask.min.js");

        $theme = $gopt->get_value_of('theme');
        $layout = $gopt->get_value_of('form_layout');
        if(defined('REGMAGIC_ADDON'))
             wp_enqueue_style('style_rm_rating', RM_ADDON_BASE_URL . 'public/js/rating3/rateit.css', array(), RM_PLUGIN_VERSION, 'all');

        switch ($theme) {
            case 'classic':
                if ($layout == 'label_top') {
                    wp_enqueue_style('rm_theme_classic_label_top', RM_BASE_URL . 'public/css/theme_rm_classic_label_top.css', array(), RM_PLUGIN_VERSION, 'all');
                    if(defined('REGMAGIC_ADDON'))
                        wp_enqueue_style('rm_theme_classic_label_top_addon', RM_ADDON_BASE_URL . 'public/css/theme_rm_classic_label_top.css', array(), RM_PLUGIN_VERSION, 'all');
                } elseif ($layout == 'two_columns') {
                    wp_enqueue_style('rm_theme_classic_two_columns', RM_BASE_URL . 'public/css/theme_rm_classic_two_columns.css', array(), RM_PLUGIN_VERSION, 'all');
                    if(defined('REGMAGIC_ADDON'))
                        wp_enqueue_style('rm_theme_classic_two_columns_addon', RM_ADDON_BASE_URL . 'public/css/theme_rm_classic_two_columns.css', array(), RM_PLUGIN_VERSION, 'all');
                } else
                    wp_enqueue_style('rm_theme_classic', RM_BASE_URL . 'public/css/theme_rm_classic.css', array(), RM_PLUGIN_VERSION, 'all');
                break;

            case 'matchmytheme':
                if ($layout == 'label_top') {
                    wp_enqueue_style('rm_theme_matchmytheme_label_top', RM_BASE_URL . 'public/css/theme_rm_matchmytheme_label_top.css', array(), RM_PLUGIN_VERSION, 'all');
                    if(defined('REGMAGIC_ADDON'))
                        wp_enqueue_style('rm_theme_matchmytheme_label_top_addon', RM_ADDON_BASE_URL . 'public/css/theme_rm_matchmytheme_label_top.css', array(), RM_PLUGIN_VERSION, 'all');
                } elseif ($layout == 'two_columns') {
                    wp_enqueue_style('rm_theme_matchmytheme_two_columns', RM_BASE_URL . 'public/css/theme_rm_matchmytheme_two_columns.css', array(), RM_PLUGIN_VERSION, 'all');
                    if(defined('REGMAGIC_ADDON'))
                        wp_enqueue_style('rm_theme_matchmytheme_two_columns_addon', RM_ADDON_BASE_URL . 'public/css/theme_rm_matchmytheme_two_columns.css', array(), RM_PLUGIN_VERSION, 'all');
                } else
                    wp_enqueue_style('rm_theme_matchmytheme', RM_BASE_URL . 'public/css/theme_rm_matchmytheme.css', array(), RM_PLUGIN_VERSION, 'all');
                break;
                
            default:
                break;
        }
        wp_enqueue_style(RM_PLUGIN_BASENAME, RM_BASE_URL . 'public/css/style_rm_front_end.css', array(), RM_PLUGIN_VERSION, 'all');
       
        if(defined('REGMAGIC_ADDON')) {
            wp_enqueue_style(RM_PLUGIN_BASENAME . '_addon', RM_ADDON_BASE_URL . 'public/css/style_rm_front_end.css', array(), RM_PLUGIN_VERSION, 'all');
            wp_enqueue_style('rm_stripe_checkout_style', RM_ADDON_BASE_URL . 'public/css/rm_stripe_checkout.css', array(), RM_PLUGIN_VERSION, 'all');
        }
        if($theme == 'default') {
            wp_enqueue_style('rm_default_theme', RM_BASE_URL . 'public/css/rm_default_theme.css', array(), RM_PLUGIN_VERSION, 'all');
        }

        $form_factory = defined('REGMAGIC_ADDON') ? new RM_Form_Factory_Addon() : new RM_Form_Factory();
        $form = $form_factory->create_form($data->form_id);
        $form->set_preview(true);
        echo '<script>jQuery(document).ready(function(){jQuery(".entry-header").remove();}); </script>';
        wp_enqueue_style( 'rm_material_icons', RM_BASE_URL . 'admin/css/material-icons.css' );
        echo '<div class="rm_embedeed_form">' . wp_kses_post((string)$form->render()) . '</div>';
        $form->insert_JS($form);
        ?>
    </div>
</div>