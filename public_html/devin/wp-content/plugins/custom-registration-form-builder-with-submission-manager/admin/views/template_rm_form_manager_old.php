<?php
if (!defined('WPINC')) {
    die('Closed');
}
if(defined('REGMAGIC_ADDON')) include_once(RM_ADDON_ADMIN_DIR . 'views/template_rm_form_manager.php'); else {
/**
 * @internal Template File [Form Manager]
 *
 * This file renders the form manager page of the plugin which shows all the forms
 * to manage delete edit or manage
 */

global $rm_env_requirements;
global $regmagic_errors;
 

  
wp_enqueue_style( 'rm_material_icons', RM_BASE_URL . 'admin/css/material-icons.css' );
 
 //Check errors
 RM_Utilities::fatal_errors();
 if(is_array($regmagic_errors)){
     foreach($regmagic_errors as $err)
    {
       //Display only non - fatal errors
       if($err->should_cont)
           echo '<div class="shortcode_notification ext_na_error_notice"><p class="rm-notice-para">'.wp_kses_post($err->msg).'</p></div>';
    }
 }
 
 ?> 
   
<div class="rmagic rm-all-forms">
    <?php if(defined('REGMAGIC_ADDON')) {
        if(version_compare(RM_ADDON_PLUGIN_VERSION, '5.3.0.0') >= 0) { ?>
    <!--
        <div class="rm-new-forms-view-link rm-mb-2 rm-position-absolute">
            <a href="javascript:void(0)" onclick="rm_forms_roll_back()">
                <?php esc_html_e('Switch to List View', 'custom-registration-form-builder-with-submission-manager'); ?> 
            </a>
        </div>
    -->
    <div class="rm-view-toggle-wrap rm-text-right">
        <div class="rm-view-toggle btn-group rm-mb-2" role="group">
  <button type="button" class="rm-view-btn rm-view-btn-left rm-view-btn-active" onclick="rm_forms_roll_back()" title="Card View">
    <!-- Material Icon: grid_view -->
    <?php _e('Card view', 'custom-registration-form-builder-with-submission-manager'); ?>
    <!--
    <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20">
      <path d="M160-520v-280h280v280H160Zm0 320v-280h280v280H160Zm360-320v-280h280v280H520Zm0 320v-280h280v280H520Z"/>
    </svg>
    -->
    <div class="rm-view-toggle-tooltip" style="display: none"> <?php _e('Card view', 'custom-registration-form-builder-with-submission-manager'); ?></div>
  </button>
  <button type="button" class="rm-view-btn rm-view-btn-right" onclick="rm_forms_roll_back()" title="List View">
    <!-- Material Icon: list -->
      <?php _e('List view', 'custom-registration-form-builder-with-submission-manager'); ?>
    <!--
    <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20">
      <path d="M160-200v-80h640v80H160Zm0-240v-80h640v80H160Zm0-240v-80h640v80H160Z"/>
    </svg>
    -->
     <div class="rm-view-toggle-tooltip" style="display: none"><?php _e('List view', 'custom-registration-form-builder-with-submission-manager'); ?></div>
  </button>
</div>
    </div>
    
    
    
    
        <?php }
    } else { ?>
     <div class="rm-view-toggle-wrap rm-text-right">
            <div class="rm-view-toggle btn-group rm-mb-2" role="group">
  <button type="button" class="rm-view-btn rm-view-btn-left rm-view-btn-active" onclick="rm_forms_roll_back()" title="Card View">
   <!-- Material Icon: grid_view -->
    Card View
    <!--
    <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20">
      <path d="M160-520v-280h280v280H160Zm0 320v-280h280v280H160Zm360-320v-280h280v280H520Zm0 320v-280h280v280H520Z"/>
    </svg>
    -->
    <div class="rm-view-toggle-tooltip" style="display: none"> <?php _e('Card view', 'custom-registration-form-builder-with-submission-manager'); ?></div>
  </button>
  <button type="button" class="rm-view-btn rm-view-btn-right" onclick="rm_forms_roll_back()" title="List View">
 <!-- Material Icon: list -->
      List View
    <!--
    <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20">
      <path d="M160-200v-80h640v80H160Zm0-240v-80h640v80H160Zm0-240v-80h640v80H160Z"/>
    </svg>
    -->
     <div class="rm-view-toggle-tooltip" style="display: none"><?php _e('List view', 'custom-registration-form-builder-with-submission-manager'); ?></div>
  </button>
</div>
     </div>
    <?php } ?>
    <!-- Joyride Magic begins -->
    <ol id="rm-form-man-joytips" style="display:none">
        <li data-id="rm-tour-title" data-options="tipLocation:top;nubPosition:hide;tipAdjustmentX:200;tipAdjustmentY:230">
            <h2>
                <?php _e('Welcome to RegistrationMagic', 'custom-registration-form-builder-with-submission-manager'); ?>
            </h2>
            <p><?php _e("RegistrationMagic is a powerful plugin that allows you to build custom registration system on your WordPress site. This is the main landing page - Forms Manager. Click <b>Next</b> to start a quick tour of this page. To stop at anytime, click the close icon on top right.", 'custom-registration-form-builder-with-submission-manager'); ?></p>
        </li>
        <li data-id="rmbar" data-options="tipLocation:bottom"><?php _e('You will see this flat white box on top of different sections inside RegistrationMagic. We call it operations bar. It contains...', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-tour-title" data-options="tipLocation:bottom"><?php _e('The heading of the section you are presently in...', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-ob-icons" data-options="nubPosition:bottom-right;tipAdjustmentX:-330"><?php _e('Quick access icons relevant to the section...', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-ob-sort" data-options="nubPosition:bottom-right;tipAdjustmentX:-320"><?php _e('A filter and sort drop down menu. In this section, it allows you to sort your forms.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-ob-nav"><?php _e("And a navigation menu with most important functions laid horizontally. Let's look at the Form Manager functions one by one.", 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-ob-new"><?php _e('This allows you to create new forms.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-form-template-tab"><?php _e('Start with a readymade form template.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-ob-duplicate"><?php _e("This allows you to duplicate one or multiple forms. Form's configuration and fields are also duplicated. Note: This does not duplicates conditional logic applied to the form.", 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-ob-delete"><?php _e('This allows you to delete one or multiple forms. All associated form data is deleted.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-ob-export"><?php _e('This allows you to export all your forms and associated data in a single XML file. Handy if you are reinstalling your site, moving forms to another site or simply backing up your hard work. Note: This does not exports conditional logic applied to the form.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-ob-import"><?php _e('Import button allows you to import the XML file saved on your computer.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-card-area"><h3> <?php _e('Forms As Cards', 'custom-registration-form-builder-with-submission-manager'); ?></h3>
            <p><?php _e('RegistrationMagic displays all forms as rectangular cards. This is a novel new approach. You will later see that a form card is much more than a symbolic representation of a form. It can show you form related data and stats at a glance.', 'custom-registration-form-builder-with-submission-manager'); ?></p>
        </li>
        <li data-id="rm-card-area"><?php _e("All form cards are displayed as grid, starting from here. You may not need to create more than one registrations form, but it's totally up to you. RegistrationMagic gives a playground to experiment and play to find the best combination for your site. First card slot is reserved for <b>Login Form</b>", 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="rm-card-tour"><?php _e('This is a form card. We automatically created it for you to give you a head start.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="rm-title-tour"><?php _e('This shows title of the form. When you create a new form, you can define its title. You can always change title of this form later, by going into its <b>General Settings</b>', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="rm-checkbox-tour" data-options="tipAdjustmentX:-28;tipAdjustmentY:-5"><?php _e("The checkbox on left side of the title allows you to select multiple forms and perform batch operations. For example, deleting multiple forms. Of course there's nothing stopping you from deleting or duplicating a single form.", 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="rm_formcard_menu_icon" data-options="tipAdjustmentX:-25;tipAdjustmentY:-2"><?php _e('Clicking this will open a popup menu allowing you direct access to different sections of this form.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="unread-box" data-options="tipAdjustmentX:-22;tipAdjustmentY:-5"><?php _e('On top right side of each card is a red number badge. This is the count of total times this form has been filled and submitted on your site by visitors.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="rm-last-submission"><?php _e("This area displays 3 latest submissions for this form. On new forms it will be empty in the start. Each submission will also show user's Gravatar and time stamp.", 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="rm-shortcode-tour" data-options="tipAdjustmentX:50"><?php _e("Now this is important. RegistrationMagic works through shortcodes. That means, to display a form on the site, you must paste its shortcode inside a page, post or a widget (where you want this form to appear). Form shortcodes are always in this format - ", 'custom-registration-form-builder-with-submission-manager'); ?><b>[RM_Forms id='x']</b></li>
        <li data-class="rm_def_star_tour" data-options="tipAdjustmentX:-24;tipAdjustmentY:-5"><?php _e('This little star allows you to mark a form as your default registration form.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="rm-form-settings"><?php _e('Each form has its own dashboard or operations area, that is accessible by clicking the <b>Settings</b> button on the respective form card.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-class="rm-form-fields" data-options="tipAdjustmentX:-12"><?php _e('Any form once created is empty. Form fields need to be added manually. This is where <b>Custom Fields Manager</b> comes in. Clicking it will take you to a separate section, where you can add all sorts of fields and pages to your form.', 'custom-registration-form-builder-with-submission-manager'); ?></li>
        <li data-id="rm-tour-title" data-options="tipLocation:top;nubPosition:hide;tipAdjustmentX:200;tipAdjustmentY:230" data-button="Done"><?php printf(__('This ends our tour of Forms Manager. Feel free to explore other sections of RegistrationMagic. We would recommend visiting the form Dashboard first. If anything does not works as expected, please write to us <a href="%s"><u>here</u></a> and we will help you sort it out asap.', 'custom-registration-form-builder-with-submission-manager'),'https://registrationmagic.com/help-support'); ?></li>
    </ol>
  <!-- Joyride Magic ends -->

    <!--  Operations bar Starts  -->
    <form name="rm_form_manager" id="rm_form_manager_operartionbar" class="rm_static_forms" method="post" action="">
        
        <input type="hidden" name="rm_slug" value="" id="rm_slug_input_field">
        
      <div class="rm-box-head-row rm-box-row">
          <div class="rm-box-col-12">         
              <div class="rm-box-border rm-rounded-2 rm-box-white-bg rm-p-3">
                  <div class="rm-box-row rm-box-mb-25 rm-box-center">
                      <div class="rm-box-col-10">
                          <div class="rm-box-title rm-mb-0" id="rm-tour-title">
                          All Forms
                          </div>
                      </div>
                      <div class="rm-box-col-2 rm-box-setting-icon-col rm-box-text-right">
                          <a href="?page=rm_options_manage" id="rm-ob-icons">
                              <img alt="" src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'images/general-settings.png'); ?>" width="30px">
                          </a> 
                      </div>
                  </div>
                  
                  <div class="rm-box-row  rm-box-center">
                      <div class="rm-box-col-8 rm-box-col-md-6">
                          <div class="rm-box-head-nav" id="rm-ob-nav">
                              <ul class="rm-d-flex ">
                               <li id="rm-ob-new"><a href="#rm_add_new_form_popup" onclick="CallModalBox(this)"><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_ADD_NEW'));?></a></li>
                                <!--<li id="rm-ob-new"><a href="<?php echo admin_url("admin.php?page=rm_form_setup");?>"><?php echo wp_kses_post((string)RM_UI_Strings::get('ADMIN_MENU_SETUP'));?></a></li>-->
                                    <li id="rm-ob-duplicate" class="rm_deactivated" onclick="jQuery.rm_do_action('rm_form_manager_operartionbar','rm_form_duplicate')"><a href="javascript:void(0)"><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_DUPLICATE')); ?></a></li>
                                    <li id="rm-ob-delete" class="rm_deactivated" onclick="jQuery.rm_do_action_with_alert('<?php echo wp_kses_post((string)RM_UI_Strings::get('ALERT_DELETE_FORM')); ?>','rm_form_manager_operartionbar','rm_form_remove')"><a href="javascript:void(0)"><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_REMOVE')); ?></a></li>
                                    <?php $localized_str_exportall = RM_UI_Strings::get('LABEL_EXPORT')." <span class='rm-export-count'>(".RM_UI_Strings::get('LABEL_ALL').")</span>"; $localized_str_exportselected = RM_UI_Strings::get('LABEL_EXPORT'); ?>
                                    <li id="rm-ob-export" data-rmlocalstrall="<?php echo wp_kses_post((string)$localized_str_exportall); ?>" data-rmlocalstrselected="<?php echo esc_attr($localized_str_exportselected); ?>" onclick="jQuery.rm_do_action('rm_form_manager_operartionbar','rm_form_export')"><a href="javascript:void(0)"><?php echo wp_kses_post((string)$localized_str_exportall); ?></a></li>
                                    <li id="rm-ob-import"><a href="admin.php?page=rm_form_import"><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_IMPORT')); ?></a></li>
                                    <li><a href="javascript:void(0)" onclick="rm_start_joyride()"><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_TOUR')); ?></a></li>
                                    <li id="rm-ob-demo" class="rm-starter-guide"><a target="_blank" href="https://registrationmagic.com/create-wordpress-registration-page-starter-guide/"><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_STARTER_GUIDE')); ?></a></li> 
                                </ul>
                          </div>
                      </div>
                      
                      <div class="rm-box-col-4 rm-box-head-ext-nav">
                          <ul class="rm-d-flex rm-justify-content-end rm-p-0 rm-m-0"> 
                                <li class="rm-d-flex rm-align-items-center rm-justify-content-end" id="rm-ob-sort">
                                    <span class="rm-pr-2"> <?php _e('Sort Forms', 'custom-registration-form-builder-with-submission-manager'); ?></span>
                                    <select onchange="rm_sort_forms(this,'<?php echo esc_js($data->curr_page);?>')">
                                        <option value=null><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_SELECT')); ?></option>
                                        <option value="form_name"><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_NAME')); ?></option>
                                        <option value="form_id"><?php echo wp_kses_post((string)RM_UI_Strings::get('FIELD_TYPE_DATE')); ?></option>
                                        <option value="form_submissions"><?php echo wp_kses_post((string)RM_UI_Strings::get('LABEL_SUBMISSIONS')); ?></option>
                                    </select>
                                </li>
                            </ul>
                      </div>
                  
                  </div>
              </div>

          </div>

      </div>
        
    
        <input type="hidden" name="rm_selected" value="">
        <?php wp_nonce_field('rm_form_manager_template'); ?>
        <input type="hidden" name="req_source" value="form_manager">
    </form>

    <!--  *****Operations bar Ends****  -->

    <!--  ****Content area Starts****  -->

    <div class="rmagic-cards rm-box-row" id="rm-card-area">
        <div class="rm-box-col rm-box-col-md-3 rm-box-mt-10">
           <div id="login_form" class="rmcard rm-border">
                <div class="cardtitle">
                    <input class="rm_checkbox" type="checkbox" disabled="disabled"><?php _e('Login Form', 'custom-registration-form-builder-with-submission-manager'); ?></div>                       
                <div class="rm-form-shortcode"><b>[RM_Login]</b></div>
                <div class="rm-form-links">
                    <div class="rm-btn-pill-wrap rm-d-flex">
                        <div class="rm-box-card-setting-item">
                            <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Dashboard', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                            <a  href="admin.php?page=rm_login_sett_manage" class="rm-form-settings rm-d-flex rm-align-items-center rm-justify-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#1f1f1f"><path d="M0 0h24v24H0z" fill="none"/><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                            </a>
                        </div>
                        <div class="rm-box-card-setting-item">
                            <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Field Manager', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                            <a class="rm-form-settings rm-d-flex rm-align-items-center rm-justify-content-center" href="admin.php?page=rm_login_field_manage">
                              <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#1f1f1f"><rect fill="none" height="24" width="24"/><path d="M3,14h4v-4H3V14z M3,19h4v-4H3V19z M3,9h4V5H3V9z M8,14h13v-4H8V14z M8,19h13v-4H8V19z M8,5v4h13V5H8z"/></svg>
                            </a>
                        </div>
                          <div class="rm-box-card-setting-item">
                              <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Login Analytics', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                                <a class="rm-form-settings rm-d-flex rm-align-items-center rm-justify-content-center" href="<?php echo esc_url(admin_url('admin.php?page=rm_login_analytics')); ?>">
                                  <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#1f1f1f"><path d="M0 0h24v24H0z" fill="none"/><path d="M10 20h4V4h-4v16zm-6 0h4v-8H4v8zM16 9v11h4V9h-4z"/></svg>
                                </a>
                        </div>
                        
                        <!--
                           <div class="rm-box-card-setting-item rm-login-validation">
                            <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Validation & Security', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                            <a class="rm-form-settings rm-d-flex rm-align-items-center rm-justify-content-center" href="<?php echo esc_url(admin_url('admin.php?page=rm_login_val_sec')); ?>">
                              <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#2271b1"><path d="M0 0h24v24H0z" fill="none"></path><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"></path></svg>
                            </a>
                        </div>
                        -->
                        <!--
                        <div class="rm-box-card-setting-item rm-login-password-recovery">
                            <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Password Recovery', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                            <a class="rm-form-settings rm-d-flex rm-align-items-center rm-justify-content-center" href="<?php echo esc_url(admin_url('admin.php?page=rm_login_val_sec')); ?>">
                              <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 20 20" height="24px" viewBox="0 0 20 20" width="24px" fill="#2271b1"><g><rect fill="none" height="20" width="20"></rect></g><g><path d="M17.5,8.5h-6.75C10.11,6.48,8.24,5,6,5c-2.76,0-5,2.24-5,5s2.24,5,5,5c2.24,0,4.11-1.48,4.75-3.5h0.75L13,13l1.5-1.5L16,13 l3-3L17.5,8.5z M6,12.5c-1.38,0-2.5-1.12-2.5-2.5S4.62,7.5,6,7.5S8.5,8.62,8.5,10S7.38,12.5,6,12.5z"></path></g></svg>
                            </a>
                        </div>
                        
                        -->
                    
                    </div>

                </div>
              </div>
        </div>
            
        <?php
        $last_form_id= 0;
        if (is_array($data->data) || is_object($data->data))
            foreach ($data->data as $index=>$entry)
            {
                if(!empty($entry->expiry_details) && $entry->expiry_details->state == 'not_expired' && $entry->expiry_details->criteria != 'date')
                   $subcount_display = $entry->expiry_details->remaining_subs;// $subcount_display = $entry->count.'/'.$entry->expiry_details->sub_limit;
                else
                    $subcount_display = null;//$entry->count;
                
                //Check if form is one of the sample forms.
                $ex_form_card_class = '';
                $sample_data = get_site_option('rm_option_inserted_sample_data', null);
                if(isset($sample_data->forms) && is_array($sample_data->forms)):
                    foreach($sample_data->forms as $sample_form):
                        if($entry->form_id == $sample_form->form_id):
                            $ex_form_card_class = ($sample_form->form_type == RM_REG_FORM)? 'rm-sample-reg-form-card' : 'rm-sample-contact-form-card';                            
                        endif;
                    endforeach;
                endif;                
                    
                if($index==0){
                    $last_form_id= $entry->form_id;
                }

                //Check if it is a newly added form
                if($data->new_added_form == $entry->form_id || (isset($_GET['last_form_id']) && $_GET['last_form_id']<$entry->form_id))
                    $ex_form_card_class .= " rm_new_added_form";
                ?>

        <div id="<?php echo esc_attr($entry->form_id); ?>" class="rm-box-col rm-box-col-md-3 rm-box-mt-10">
             <div class="rmcard rm-border rm-card-tour  <?php echo esc_attr($ex_form_card_class); ?>">    
                <?php if($entry->count > 0): ?>
                <div class='unread-box'>
                    <a href="?page=rm_submission_manage&rm_form_id=<?php echo esc_attr($entry->form_id); ?>&rm_interval=<?php echo esc_attr($data->submission_type); ?>"><?php echo esc_html($entry->count); ?></a>
                </div>
                <?php endif; ?>
                    <div class="cardtitle rm-title-tour">
                        <input class="rm_checkbox rm-checkbox-tour" type="checkbox" onclick="rm_on_form_selection_change()" name="rm_selected_forms[]" value="<?php echo esc_attr($entry->form_id); ?>"><span class="rm_form_name rm_formcard_menu_icon" style="float: none; transform: none; margin: 0px;" data-menu-panel="#fcm_<?php echo esc_attr($entry->form_id); ?>"><?php echo esc_html($entry->form_name); ?></span>
                    </div>
                    <span class="rm_formcard_menu_icon" data-menu-panel="#fcm_<?php echo esc_attr($entry->form_id); ?>"><i class="material-icons">&#xE5D3;</i></span>
                    <?php if($entry->form_type == RM_REG_FORM): ?>
                    <div class="rm-form-type">
                        <i class="material-icons rm-form-type__icon">how_to_reg</i>
                        <span class="rm-form-type__label"><?php esc_html_e('Registration Form', 'custom-registration-form-builder-with-submission-manager'); ?></span>
                    </div>
                    <?php elseif($entry->form_type == RM_CONTACT_FORM): ?>
                    <div class="rm-form-type">
                        <i class="material-icons rm-form-type__icon"> person </i>
                        <span class="rm-form-type__label"><?php esc_html_e('Contact Form', 'custom-registration-form-builder-with-submission-manager'); ?></span>
                    </div>
                    <?php endif; ?>
                            <?php if ($entry->count > 0): ?>
                                <div class="rm-last-submission-wrap rm-mt-2">
                                    <div class="rm-latest-submission-title rm-mb-2"><?php esc_html_e('Latest Submissions', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                                    <?php foreach ($entry->submissions as $submission): ?>
                                        
                                           <?php echo wp_kses_post(
                                                '<div class="rm-last-submission-item">' .
                                                    (string) $submission->gravatar .
                                                    ' <span class="rm-submission-time">' . RM_Utilities::localize_time($submission->submitted_on) . '</span>' .
                                                '</div>'
                                            ); ?>
                                                                                    
                                        
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="rm-last-submission-wrap">
                                    <div class="rm-last-submission"></div>
                                </div>
                            <?php endif; ?>
                    
                                <div class="rm-limit-info rm-my-2">
                                    <?php
                                        if ($subcount_display)
                                            printf(RM_UI_Strings::get('RM_SUB_LEFT_CAPTION'), $subcount_display);
                                        ?>
                                    
                                </div>
                    
                    <?php
                    if(!empty($entry->expiry_details) && $entry->expiry_details->state == 'expired')
                        echo "<div class='rm-form-expiry-info'>".wp_kses_post((string)RM_UI_Strings::get('LABEL_FORM_EXPIRED'))."</div>";
                    else if(!empty($entry->expiry_details) && $entry->expiry_details->state == 'not_expired' && $entry->expiry_details->criteria != 'subs')
                    {
                        if($entry->expiry_details->remaining_days < 26)
                           echo "<div class='rm-form-expiry-info'>".wp_kses_post((string)sprintf(RM_UI_Strings::get('LABEL_FORM_EXPIRES_IN'),$entry->expiry_details->remaining_days))."</div>";
                        else
                        {
                           $exp_date = gmdate('d M Y', strtotime($entry->expiry_details->date_limit));
                           echo "<div class='rm-form-expiry-info'>".wp_kses_post((string)RM_UI_Strings::get('LABEL_FORM_EXPIRES_ON'))." {$exp_date}</div>";
                        }
                    }
 
                    ?><div class="rm-form-shortcode">
                        <?php if($data->def_form_id == $entry->form_id && $entry->form_type == 1) { ?>
                   <!-- <i class="material-icons rm_def_form_star rm_def_star_tour" onclick="make_me_a_star(this)" id="rm-star_<?php echo esc_attr($entry->form_id); ?>">dd</i> -->
                    <span class = "rm-default-badge" id = "rm-star_12" onclick = "make_me_a_star(this)">
                                <i class = "material-icons">check_circle</i> Default Form
                            </span>
                        <?php } 
                        else { 
                                if($entry->form_type == 1){ ?>
                            <i class="material-icons rm_not_def_form_star rm_def_star_tour" onclick="make_me_a_star(this)" id="rm-star_<?php echo esc_attr($entry->form_id); ?>"></i>
                            
                              <?php  }
                              else { ?>
                            <i class="material-icons rm_not_def_form_star rm_def_star_tour" id="rm-star_<?php echo esc_attr($entry->form_id); ?>"></i>
                            <span class="rm-star-tip"><?php echo wp_kses_post((string)RM_UI_Strings::get('NOTE_DEFAULT_FORM')); ?></span>
                                <?php }
                         } ?>
                    <span class="rm-shortcode-tour rm-shortcode-copy">[RM_Forms id='<?php echo esc_html($entry->form_id); ?>']</span></div>
                    <div class="rm-form-links">
                        <div class="rm-btn-pill-wrap rm-d-flex">
                              <div class="rm-box-card-setting-item">
                                 <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Dashboard', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                                <a class="rm-form-settings" href="admin.php?page=rm_form_sett_manage&rm_form_id=<?php echo $entry->form_id; ?>" class="rm-form-settings rm-d-flex rm-align-items-center rm-justify-content-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#1f1f1f"><path d="M0 0h24v24H0z" fill="none"/><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                                </a>
                            </div>   
                            
                            <div class="rm-box-card-setting-item">
                                <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Fields Manager', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                                <a class="rm-form-fields" href="admin.php?page=rm_field_manage&rm_form_id=<?php echo $entry->form_id; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#1f1f1f"><rect fill="none" height="24" width="24"/><path d="M3,14h4v-4H3V14z M3,19h4v-4H3V19z M3,9h4V5H3V9z M8,14h13v-4H8V14z M8,19h13v-4H8V19z M8,5v4h13V5H8z"/></svg>
                                </a>
                            </div> 
                             
                            <div class="rm-box-card-setting-item">
                                <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Form Analytics', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                                <a href="?page=rm_analytics_show_form&rm_form_id=<?php echo esc_attr($entry->form_id); ?>" class="rm-form-fields rm-d-flex rm-align-items-center rm-justify-content-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#2271b1"><rect fill="none" height="24" width="24"/><g><path d="M7.5,21H2V9h5.5V21z M14.75,3h-5.5v18h5.5V3z M22,11h-5.5v10H22V11z"/></g></svg>
                                </a>
                            </div>
                             
                            <!--
                            <div class="rm-box-card-setting-item">
                                 <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Custom Status', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                                <a href="?page=rm_form_manage_cstatus&rm_form_id=<?php echo esc_attr($entry->form_id); ?>" class="rm-form-fields rm-d-flex rm-align-items-center rm-justify-content-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#2271b1"><path d="M0 0h24v24H0z" fill="none"></path><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"></path></svg>                                
                                </a>
                            </div>
                            
                            -->
                             <!--
                            <div class="rm-box-card-setting-item">
                                <div class="rm-box-card-setting-info" style="display:none"><?php esc_html_e('Automation', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                                <a href="?page=rm_ex_chronos_manage_tasks&rm_form_id=<?php echo esc_attr($entry->form_id); ?>" class="rm-form-fields rm-d-flex rm-align-items-center rm-justify-content-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#2271b1"><path d="M11 21h-1l1-7H7.5c-.58 0-.57-.32-.38-.66.19-.34.05-.08.07-.12C8.48 10.94 10.42 7.54 13 3h1l-1 7h3.5c.49 0 .56.33.47.51l-.07.15C12.96 17.55 11 21 11 21z"/></svg>
                                </a>
                            </div>
                             -->
                            
                        </div>
                            </div>
                    <?php include RM_ADMIN_DIR."views/template_rm_formcard_menu.php";?>                
                    </div>
                    </div>
                <?php
            } else
            echo "<h4>" . wp_kses_post((string)RM_UI_Strings::get('LABEL_NO_FORMS')) . "</h4>";
        ?>
    </div>
    <?php if ($data->total_pages > 1): ?>
        <ul class="rmpagination rm-d-flex rm-justify-content-center">
            <?php if ($data->curr_page > 1): ?>
                <li><a href="?page=<?php echo esc_attr($data->rm_slug) ?>&rm_reqpage=<?php echo esc_attr($data->curr_page - 1);
        if ($data->sort_by) echo'&rm_sortby=' . esc_attr($data->sort_by);if (!$data->descending) echo'&rm_descending=' . esc_attr($data->descending); ?>">«</a></li>
                <?php
            endif;
            for ($i = 1; $i <= $data->total_pages; $i++):
                if ($i != $data->curr_page):
                    ?>
                    <li><a href="?page=<?php echo esc_attr($data->rm_slug) ?>&rm_reqpage=<?php echo esc_attr($i);
            if ($data->sort_by) echo'&rm_sortby=' . esc_attr($data->sort_by);if (!$data->descending) echo'&rm_descending=' . esc_attr($data->descending); ?>"><?php echo esc_html($i); ?></a></li>
                <?php else:
                    ?>
                    <li><a class="active" href="?page=<?php echo esc_attr($data->rm_slug) ?>&rm_reqpage=<?php echo esc_html($i);
            if ($data->sort_by) echo'&rm_sortby=' . esc_attr($data->sort_by);if (!$data->descending) echo'&rm_descending=' . esc_attr($data->descending); ?>"><?php echo esc_html($i); ?></a></li> <?php
                endif;
            endfor;
            ?>
            <?php if ($data->curr_page < $data->total_pages): ?>
                <li><a href="?page=<?php echo esc_attr($data->rm_slug) ?>&rm_reqpage=<?php echo esc_attr($data->curr_page + 1);
        if ($data->sort_by) echo'&rm_sortby=' . esc_attr($data->sort_by);if (!$data->descending) echo'&rm_descending=' . esc_attr($data->descending); ?>">»</a></li>
            <?php endif;
        ?>
        </ul>
    <?php
    endif;
    
  
    
    /** BEGIN: Banner at Footer **/
if($data->should_show_fb_footer) {
?>
    <div id="fb_sub_footer" class="rm-footer-banner rm-d-none">
        <div class="rm-fb-like-us">
            <a onclick="save_fb_subscribe_action()">
                <img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) . 'images/fb-thumb-up.png'; ?>"> Like us on Facebook for upgrade notifications, important tips and feature updates.
            </a>
        </div>
    </div>
<?php
} 


/** FOOTER ENDS **/
 

  ?> 
    

    <?php $new_form_pop_up_style = (isset($_GET['create_new_form'])) ? 'style="display:block"' : 'style="display:none"';?>
    <!-- Add New Form popup -->
   <div id="rm_add_new_form_popup" class="rm-modal-view rm-create-from-card" <?php echo wp_kses_post($new_form_pop_up_style);?>>
        <div class="rm-modal-overlay rm-form-popup-overlay-fade-in"></div>

        <div class="rm_add_new_form_wrap rm-create-new-from rm-form-popup-out">
            <div class="rm-box-row rm-box-center rm-box-secondary-bg">
                    <div class="rm-box-col-6 rm-box-white-bg rm-form-box">                       
                        <div class="rm-modal-titlebar rm-new-form-popup-header">
                                <div class="rm-modal-title">
                                    <?php esc_html_e('Quick Create Form', 'custom-registration-form-builder-with-submission-manager'); ?>
                                </div>
                            <div class="rm-modal-subtitle"><?php esc_html_e('Creates a new form with all the essential settings.', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                            
                            </div>
                        <div class="rm-modal-container">
                            <?php require RM_ADMIN_DIR . 'views/template_rm_new_form_exerpt.php'; ?>
                        </div>
                    </div>
                    <div class="rm-box-col-6 rm-form-box">
                            <span  class="rm-modal-close material-icons">close</span>
                        <div class="rm-template-modal-heading"><?php esc_html_e('Looking for form templates?', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                        <div class="rm-template-modal-subheading"><?php esc_html_e('Build using our form wizard to create awesome looking ready-to-use forms within minutes!', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                        <div class="rm-template-modal-button"><a href="<?php echo admin_url("admin.php?page=rm_form_setup");?>"><?php esc_html_e('Start Now!', 'custom-registration-form-builder-with-submission-manager'); ?></a></div>
                    </div>
                
            </div>

        </div>
    </div>
    <!-- End: Add New Form popup -->
    
    <!-- Form Template popup -->
    
    <div id="rm_add_new_form_popup_template" class="rm-modal-view" <?php echo wp_kses_post((string)$new_form_pop_up_style);?>>
        <div class="rm-modal-overlay rm-form-popup-overlay-fade-in"></div>

        <div class="rm_add_new_form_wrap rm-create-new-from rm-form-popup-out">
            <div class="rm-modal-titlebar rm-form-template-popup-header">
                <div class="rm-modal-title">
                    <img src="<?php echo RM_BASE_URL;?>images/rm-logo-icon.svg"><?php _e('Select Your Registration Form Template','custom-registration-form-builder-with-submission-manager'); ?>
                    <span class="rm-form-template-subtitle" style="display:none;"> <?php _e('All templates can be modified after selection. You can add, remove or edit form fields, customize emails and fine tune settings.','custom-registration-form-builder-with-submission-manager'); ?></span>
                </div>
                <span  class="rm-modal-close">&times;</span>
            </div>
            <div class="rm-modal-container">                
            <?php require RM_ADMIN_DIR.'views/template_rm_new_form_templates.php'; ?>
            </div>
        </div>
    </div>
    
    
     <!-- End: Form Template popup -->
     
    <!-- Form Publish Pop-up -->
    
    <div id="rm_form_publish_popup" class="rm-modal-view" style="display: none;">
        <div class="rm-modal-overlay"></div>
        <div class="rm-modal-wrap rm-publish-form-popup">

            <div class="rm-modal-titlebar rm-new-form-popup-header">
                <div class="rm-modal-title">
                    Publish
                </div>
                <span class="rm-modal-close">&times;</span>
            </div>
            <div class="rm-modal-container">
                <?php $form_id_to_publish = $entry->form_id; ?>
                <?php include_once RM_ADMIN_DIR . 'views/template_rm_formflow_publish.php'; ?>
            </div>
        </div>

    </div>
    
        <!-- End Form Publish Pop-up -->
    
        <div id="rm_embed_code_dialog" style="display:none"><textarea readonly="readonly" id="rm_embed_code" onclick="jQuery(this).focus().select()"></textarea><img class="rm-close" src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'images/close-rm.png'); ?>" onclick="jQuery('#rm_embed_code_dialog').fadeOut()"></div>
        
</div>


   <div class="rm-side-banner">
        <div class="rm-sidebanner-image">
          <!--  <img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) . 'images/svg/rm-support-banner-icon.svg'; ?>"> -->
        </div>

        <div class="sidebanner-content-wrapper">
            <div class="sidebanner-text-content">
          
                    <div class="sidebanner-help-text">
                        <!--<span><?php esc_html_e('Need Help', 'custom-registration-form-builder-with-submission-manager'); ?></span>-->
                        <?php esc_html_e('Getting Started?', 'custom-registration-form-builder-with-submission-manager'); ?>
                    </div>

                <p><?php esc_html_e('Looking for quick answers? Check our Starter Guide or reach out to us directly.','custom-registration-form-builder-with-submission-manager'); ?></p>			
            </div>
            <div class="rm-sidebanner-buttons">
                <div class="rm-sidebanner-button rm-sidebanner-stater-guide">
                     <a target="_blank" href="https://registrationmagic.com/create-wordpress-registration-page-starter-guide/?utm_source=plugin&utm_medium=helpbox" class="button button-primary"> <?php esc_html_e('Starter Guide','custom-registration-form-builder-with-submission-manager'); ?></a>
                    			
                </div>

                <div class="rm-sidebanner-button ">
                   <a target="_blank" href="https://wordpress.org/plugins/custom-registration-form-builder-with-submission-manager/" class="button"><?php esc_html_e('Create Support Ticket','custom-registration-form-builder-with-submission-manager'); ?></a>
                </div>			
            </div>


        </div> <!-- sidebanner-content-wrapper -->
    </div>


<!---Form Preview Modal---->

<!-- The modal -->

<div class="rmagic rm-hide-version-number">
    <div id="rm-form-preview-modal" class="rm-modal-view modal" style="display: none">
        <div class="rm-modal-overlay rm-field-popup-overlay-fade-in"></div>
        <div class="rm_field_row_setting_wrap rm-select-row-setting rm-field-popup-out">
            <div class="rm-modal-titlebar rm-new-form-popup-header">
                <div class="rm-modal-title">
                    <?php esc_html_e('Form Preview', 'custom-registration-form-builder-with-submission-manager'); ?>
                </div>
                <span class="rm-modal-close rm-text-center">×</span>
            </div>
            <div id="rm-iframe-loader" class="rm-loader"></div>
            
            <iframe src="admin.php?page=rm_form_preview&rm_form_id=<?php echo esc_attr($entry->form_id); ?>" name="iframe_a" id="rm-form-preview-frame">
                <p><?php esc_html_e('Your browser does not support iframes.', 'custom-registration-form-builder-with-submission-manager'); ?></p>
            </iframe>
        </div>
    </div>
</div>

<!---Form preview Modal End--->
<style>
    .rm-new-forms-view-link {
        left: 0px;
    top: 0px;
    }
</style>
  <pre class="rm-pre-wrapper-for-script-tags"><script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('a.rm_form_preview_btn').on('click', function(event) {
            event.preventDefault();
            var content = jQuery(this).data('content');
            var iframe = document.getElementById('rm-form-preview-frame');
            
            jQuery('#rm-form-preview-modal').css('display', 'block');
            
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-out');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-in');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-preview-loaded');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-preview-loading');
            //jQuery('.rmagic .rm-modal-view').addClass('rm-form-popup-show').removeClass('rm-form-popup-hide');
            
            iframe.addEventListener('load', function() {
                jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-preview-loading');
                jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-preview-loaded');
                //iframe.contentWindow.document.querySelector('input[name=rm_sb_btn]').addEventListener('click', function() {
                    //if(this.classList.contains("rm-submit-btn-show")) {
                        // Add jQuery code here to close the modal
                    //}
                //});
            });
            
            iframe.src = "admin.php?page=rm_form_preview&rm_form_id="+content;
        });

    // Close the modal when clicking on the close button
    jQuery('.rm-modal-close').on('click', function() {
        jQuery('#rm-form-preview-modal').css('display', 'none');
    });

    // Close the modal when clicking outside of it
    jQuery(window).on('click', function(event) {
        if (event.target.classList.contains('modal')) {
        jQuery('#rm-form-preview-modal').css('display', 'none');
        }
    });
    
    rmLastSubmisson();
    
    });
    
    
    
    function rmLastSubmisson() {
    jQuery(".rm-last-submission-wrap .rm-last-submission-item").each(function () {
        var img = jQuery(this);
        setTimeout(function(){
        jQuery(img).addClass("rm_img_roll");  
        }, 800);
    });
}
    

    jQuery(document).ready(function(){
       //Configure joyride
       //If autostart is false, call again "jQuery("#rm-form-man-joytips").joyride()" to start the tour.
       <?php if(false && $data->autostart_tour): ?>
       /*jQuery("#rm-form-man-joytips").joyride({tipLocation: 'top',
                                               autoStart: true,
                                               postRideCallback: rm_joyride_tour_taken});*/
        <?php else: ?>
            jQuery("#rm-form-man-joytips").joyride({tipLocation: 'top',
                                               autoStart: false,
                                               postRideCallback: rm_joyride_tour_taken});
        <?php endif; ?>
    });

    function rm_forms_roll_back() {
        var postData = {'action' : 'rm_forms_view_roll_back', 'rm_sec_nonce': '<?php echo wp_create_nonce('rm_ajax_secure'); ?>', 'value': <?php echo absint($data->old_view); ?>};
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', postData, function(response) {
            if(response.success) {
                window.location.href = "<?php echo esc_url(admin_url("admin.php?page=rm_form_manage")); ?>";
            }
        });
    }
   
   function rm_start_joyride(){
       jQuery("#rm-form-man-joytips").joyride();
    }
    
    function rm_joyride_tour_taken(){
        var data = {
			'action': 'joyride_tour_update',
            'rm_sec_nonce': '<?php echo wp_create_nonce('rm_ajax_secure'); ?>',
			'tour_id': 'form_manager_tour',
                        'state': 'taken'
		};

        jQuery.post(ajaxurl, data, function(response) {});
    }
    
    function rm_open_dial(form_id){
        jQuery('textarea#rm_embed_code').html('<?php echo wp_kses_post((string)RM_UI_Strings::get('MSG_BUY_PRO_GOLD_EMBED')); ?>');
        jQuery('#rm_embed_code_dialog').fadeIn(100);
    }
    jQuery(document).mouseup(function (e) {
        var container = jQuery("#rm_embed_code_dialog,.rm_form_card_settings_dialog");
        if (!container.is(e.target) // if the target of the click isn't the container... 
                && container.has(e.target).length === 0) // ... nor a descendant of the container 
        {
            container.hide();
        }
    });
    
    function  rm_on_form_selection_change() {    
        var selected_forms = jQuery("input.rm_checkbox:checked");
        if(selected_forms.length > 0) {   
            jQuery("#rm-ob-export a").html( jQuery("#rm-ob-export").data("rmlocalstrselected") + ' <span class="rm-export-count">(' + selected_forms.length +')</span>');
            jQuery("#rm-ob-delete").removeClass("rm_deactivated");   
            jQuery("#rm-ob-duplicate").removeClass("rm_deactivated");
        } else {
            jQuery("#rm-ob-export a").html(jQuery("#rm-ob-export").data("rmlocalstrall"));
            jQuery("#rm-ob-delete").addClass("rm_deactivated");
             jQuery("#rm-ob-duplicate").addClass("rm_deactivated");
            
        }  
    }
    
    function make_me_a_star(e){
        var form_id = jQuery(e).attr('id').slice(8);
        var variable_id="#rm-star_"+form_id;
        
        if(jQuery(variable_id).hasClass( "rm_def_form_star" ))
        {
             var data = {
			'action': 'unset_default_form',
            'rm_ajaxnonce': '<?php echo wp_create_nonce('rm_formflow'); ?>',
			'rm_def_form_id': form_id
		};
            jQuery.post(ajaxurl, data, function(response) {
                jQuery(variable_id).removeClass( "rm_def_form_star" );
                jQuery(variable_id).addClass( "rm_not_def_form_star" );
                
            });
            return false;
        }
      
        //toggle();
        if(typeof form_id != 'undefined' && !jQuery(e).hasClass('rm_def_form_star')){
        
        var ajaxnonce = '<?php echo wp_create_nonce('rm_formflow'); ?>';
        var data = {
			'action': 'set_default_form',
            'rm_ajaxnonce':ajaxnonce,
			'rm_def_form_id': form_id
		};

        jQuery.post(ajaxurl, data, function(response) {
                        var old_form = jQuery('.rm_def_form_star');
			old_form.removeClass('rm_def_form_star');
                        old_form.addClass('rm_not_def_form_star');
                        
                        var curr_form = jQuery('#rm-star_'+form_id);
                        curr_form.removeClass('rm_not_def_form_star');
                        curr_form.addClass('rm_def_form_star');
		});
            }
    }
    
    function rm_show_form_sett_dialog(form_id){
        jQuery("#rm_settings_dailog_"+form_id).show();
    }
      
jQuery("#rm_rateit_banner").bind('rated', function (event, value) { 
        if(value<=3)
        {
            
             jQuery("#rm-rate-popup-wrap").fadeOut();  
             jQuery("#wordpress_review").fadeOut(100);  
             jQuery("#feedback_message").fadeIn(100);  
             jQuery('#feedback_message').removeClass('rm-blur');
             jQuery('#feedback_message').addClass('rm-hop');
             handle_review_banner_click('rating',value);
        }
        else
        {
             jQuery("#rm-rate-popup-wrap").fadeOut();  
             jQuery("#feedback_message").fadeOut();  
             jQuery("#wordpress_review").fadeIn(100);
             jQuery('#wordpress_review').removeClass('rm-blur');
             jQuery('#wordpress_review').addClass('rm-hop');
             handle_review_banner_click('rating',value);
        }
    
    
    });
    
    function save_fb_subscribe_action()
    {
            window.open("https://www.facebook.com/registrationmagic", '_blank');
        jQuery.ajax({
            url:ajaxurl,
            type:'post',
            data:{action:'rm_fb_subscribe_action','rm_sec_nonce': '<?php echo wp_create_nonce('rm_ajax_secure'); ?>'},
            success:function(data)
            {               
               jQuery('#fb_sub_footer').hide();
            }
        });
    }
    
        function CallModalBox(ele) {
          jQuery(jQuery(ele).attr('href')).toggle().find("input[type='text']").focus();
          if(jQuery(ele).attr('href')=='#rm_add_new_form_popup' || jQuery(ele).attr('href')=='#rm_add_new_form_popup_template'){
            jQuery('.rmagic .rm_add_new_form_wrap.rm-create-new-from').removeClass('rm-form-popup-out');
            jQuery('.rmagic .rm_add_new_form_wrap.rm-create-new-from').addClass('rm-form-popup-in');
            
            jQuery('#rm_add_new_form_popup .rm-modal-overlay').removeClass('rm-form-popup-overlay-fade-out');
            jQuery('#rm_add_new_form_popup .rm-modal-overlay').addClass('rm-form-popup-overlay-fade-in');
          }
      }
    
      jQuery(document).ready(function () {
          jQuery('.rm-modal-close, .rm-modal-overlay').click(function () {
              setTimeout(function(){
                  //jQuery(this).parents('.rm-modal-view').hide();
                  jQuery('.rm-modal-view').hide();
              }, 400);
              
          });
          

            jQuery('.rmagic .rm-create-new-from .rm-new-form-popup-header .rm-modal-close, #rm_add_new_form_popup .rm-modal-overlay, #rm_add_new_form_popup_template .rm-modal-overlay').on('click', function(){
            jQuery('.rmagic .rm_add_new_form_wrap.rm-create-new-from').removeClass('rm-form-popup-in');
            jQuery('.rmagic .rm_add_new_form_wrap.rm-create-new-from').addClass('rm-form-popup-out');
            
            jQuery('#rm_add_new_form_popup .rm-modal-overlay').removeClass('rm-form-popup-overlay-fade-in');
            jQuery('#rm_add_new_form_popup .rm-modal-overlay').addClass('rm-form-popup-overlay-fade-out');
          });
          
      });
    
    function recursive_import(form_id) {
        var id = form_id;
        var ajaxnonce = '<?php echo wp_create_nonce('rm_import_first'); ?>';
        var data = {
            'action': 'import_first',
            'rm_ajaxnonce': ajaxnonce,
            'form_id': id
        };
        jQuery.post(ajaxurl, data, function (response) {
            if (response == 0)
            {
               _getEl("progressBar").value = Math.round(100);
                _getEl("status").innerHTML = '<?php _e('Import Successfully Completed', 'custom-registration-form-builder-with-submission-manager'); ?>';
                setTimeout(function(){
                     new_url= "<?php echo admin_url('admin.php?'); ?>" + update_current_url_with_param("last_form_id","<?php echo esc_html($last_form_id); ?>");
                     window.location= new_url;
                },3000)
            } else {

                //jQuery("#rm_import_progress").append("(Imported)</br></br>Importing RM Form--" + response + "");

                recursive_import(response);
            }
        });
    }
    
    function start_import(){
        jQuery("#rm_import_errors").html();
         var ajaxnonce = '<?php echo wp_create_nonce('rm_import_first'); ?>';
        var data = {
            'action': 'import_first',
            'rm_ajaxnonce': ajaxnonce
        };
        jQuery.post(ajaxurl, data, function (response) {
            if (response == 0)
            {
                _getEl("progressBar").value = Math.round(100);
                _getEl("status").innerHTML = '<?php _e('Import Successfully Completed', 'custom-registration-form-builder-with-submission-manager') ?>';
                setTimeout(function(){
                     new_url= "<?php echo admin_url('admin.php?'); ?>" + update_current_url_with_param("last_form_id","<?php echo esc_html($last_form_id); ?>");
                     window.location= new_url;
                },3000)
              
            } else if (response === "INVALID_FILE") {
                jQuery("#rm_import_errors").html('');
                jQuery("#rm_import_errors").append("<div class='rm_import_error'><?php _e('Invalid RegistrationMagic template file. Please upload valid template file with XML extension.', 'custom-registration-form-builder-with-submission-manager') ?></div>");
                jQuery("#progressBar,#status").hide();
            } else {
                var pre = parseInt(response) - 1;
                recursive_import(response);
            }

        });
    }
    
    /* Upload Handler */
    function _getEl(el) {
     return document.getElementById(el);
    }
    
    function check_file_extension(obj){
        var file = obj.files[0];
        if(file && file.type!="text/xml"){
            jQuery("#rm_import_errors").html("<div class='rm_import_error'><?php _e('Invalid RegistrationMagic template file. Please upload valid template file with XML extension.', 'custom-registration-form-builder-with-submission-manager'); ?>");
            obj.value='';
        }
    }
    var rm_file_ajax=null;    
    function uploadFile() {
      var file = _getEl("xml_file").files[0];
      if(!file){
           jQuery("#rm_import_errors").html("<div class='rm_import_error'><?php _e('Please select  a file.', 'custom-registration-form-builder-with-submission-manager'); ?></div>");
           return;
      }
      jQuery("#rm_import_errors").html('');
      var formdata = new FormData();
      var ajaxnonce = '<?php echo wp_create_nonce('rm_admin_upload_template'); ?>';
      formdata.append("action", "rm_admin_upload_template");
      formdata.append("file", file);
      formdata.append("rm_ajaxnonce", ajaxnonce);
      rm_file_ajax = new XMLHttpRequest();
      rm_file_ajax.upload.addEventListener("progress", progressHandler, false);
      rm_file_ajax.addEventListener("load", completeHandler, false);
      rm_file_ajax.addEventListener("error", errorHandler, false);
      rm_file_ajax.addEventListener("abort", abortHandler, false);
      rm_file_ajax.open("POST", "<?php echo admin_url('admin-ajax.php'); ?>");
      jQuery("#progressBar,#status").show();
      rm_file_ajax.send(formdata);
    }

    function progressHandler(event) {
      var percent = (event.loaded / event.total) * 50;
      _getEl("progressBar").value = Math.round(percent);
      _getEl("status").innerHTML = "<?php _e('File upload is in progress...', 'custom-registration-form-builder-with-submission-manager') ?>";
    }

    function completeHandler(event) {
       var percent = 50;
      _getEl("progressBar").value = Math.round(percent);
      _getEl("status").innerHTML = "<?php _e('Form Import is in progress....', 'custom-registration-form-builder-with-submission-manager') ?>";
      start_import();
    }

    function errorHandler(event) {
      _getEl("status").innerHTML = "<?php _e('Upload Failed', 'custom-registration-form-builder-with-submission-manager') ?>";
    }

    function abortHandler(event) {
      _getEl("status").innerHTML = "<?php _e('Upload Aborted', 'custom-registration-form-builder-with-submission-manager') ?>";
    }     
    
    function cancel_file_upload(){
       // rm_file_ajax.abort();
        location.reload();
    }
    
    
    // When the user clicks on the link, open the modal
    jQuery(document).ready(function() {
    jQuery("#rm_form_preview_action").click(function(event) {
        event.preventDefault(); // Prevent default action of link
        var formId = jQuery(this).data("form-id");
        var iframe = document.getElementById('rm-form-preview-frame');
        showModalWithData(formId);
        
        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-out');
        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-in');
        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-preview-loaded');
        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-preview-loading');
        jQuery('.rmagic .rm-modal-view').addClass('rm-form-popup-show').removeClass('rm-form-popup-hide');

        iframe.addEventListener('load', function() {
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-preview-loading');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-preview-loaded');
//            iframe.contentWindow.document.querySelector('input[name=rm_sb_btn]').addEventListener('click', function() {
//                if(this.classList.contains("rm-submit-btn-show")) {
//                    // Add jQuery code here to close the modal
//                     jQuery("#rm-form-preview-modal").hide();
//                
//                }
//            });
        });
        
        iframe.contentDocument.location.reload(true);
    });
});

    // Function to show modal with data according to form ID
    function showModalWithData(formId) {
        // Here you can fetch data based on the formId and populate the modalContent accordingly
        var modalContent = jQuery("#rm-fields-data-content");
        //modalContent.html("<p>Modal content for form ID: " + formId + "</p>");
        
        // Display the modal
        jQuery("#rm-form-preview-modal").show();
    }
    
        document.addEventListener("DOMContentLoaded", function() {
        let link = document.querySelector("#rm-ob-new a");
        link.classList.add("rm-gradient-animate");

        // Remove gradient after animation finishes (1s * 5 loops = 5s)
        setTimeout(() => {
            link.classList.remove("rm-gradient-animate");
        }, 25000);
    });
    
    
  </script>
  
  <style>
      
      .rmagic.rm-all-forms::before{
          opacity: 0;
      }
      
    #rm-form-preview-modal iframe#rm-form-preview-frame{
        width: 100%;
        min-height: 600px;
        height: 100%;
    }
    
    .rmagic .rm_field_row_setting_wrap.rm-select-row-setting{
      min-height: auto;
    }
    
    #rm-iframe-loader {
        display: none;
        margin-top: 18%;
        margin-bottom: 200px
    }
    
    #rm-form-preview-modal .rm-preview-loading #rm-form-preview-frame{
        display: none;
    }
    
    #rm-form-preview-modal .rm-preview-loaded #rm-form-preview-frame{
        display: block;
    }
    
    .rm-preview-loading #rm-iframe-loader{
        display: block !important;
    }
    
    #rm-form-preview-modal .rm_field_row_setting_wrap{
        max-width: 800px;
        left: calc(50% - 400px);
    }
    #rm-form-preview-modal .rm-modal-titlebar{
        border-bottom: 1px solid #efefef;
    }
    
      
      
  </style>
  
  
  </pre>
<?php } ?>
 