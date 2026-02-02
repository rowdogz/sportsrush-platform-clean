<?php
if (!defined('WPINC')) {
    die('Closed');
}
//if(defined('REGMAGIC_ADDON'))
    //include_once(RM_ADDON_ADMIN_DIR . 'views/template_rm_field_picker.php');
//else {
    ?>
<div class="rm-field-selector-wrap rm-d-flex">
    
    <div class="rm-field-selector-left">
        
        <div class="rm-field-search">
            <input type="text" id="rmform-field-search" name="field-search" placeholder="<?php esc_html_e("Search fields...", 'custom-registration-form-builder-with-submission-manager'); ?>" class="rmform-control" onkeyup="rmSearchFields(this);">

        </div>
        
<ul class="rmform-field-tabs">
    
     <?php if($data->form->form_type==1 && (!in_array('Username', $primary_fields) || !in_array('UserPassword', $primary_fields))): ?> 
      <li class="rmform-field-tablinks" onclick="openTab(event, 'rm_user_password_tab')" id="rm_fields_defaultOpen"><?php echo esc_html__('Account Fields', 'custom-registration-form-builder-with-submission-manager'); ?> <span class="rm-field-counter"><?php echo 3 - count($primary_fields); ?></span></li>
    <?php endif; ?> 
    
  <li class="rmform-field-tablinks" onclick="openTab(event, 'rm_common_fields_tab')" id="rm_fields_defaultOpen"><?php esc_html_e('Common Fields', 'custom-registration-form-builder-with-submission-manager'); ?> <span class="rm-field-counter">5</span></li>
  <li class="rmform-field-tablinks" onclick="openTab(event, 'rm_special_fields_tab')"><?php esc_html_e('Special Fields', 'custom-registration-form-builder-with-submission-manager'); ?> <span class="rm-field-counter"><?php echo defined('REGMAGIC_ADDON') ? 27 : 14; ?></span></li>
  <li class="rmform-field-tablinks" onclick="openTab(event, 'rm_profile_fields_tab')"><?php esc_html_e('Profile Fields', 'custom-registration-form-builder-with-submission-manager'); ?> <span class="rm-field-counter"><?php echo defined('REGMAGIC_ADDON') ? 7 : 6; ?></span></li>
  <li class="rmform-field-tablinks" onclick="openTab(event, 'rm_wc_fields_tab')"><?php echo esc_html__('WooCommerce Fields', 'custom-registration-form-builder-with-submission-manager'); ?> <span class="rm-field-counter">3</span></li>
  <li class="rmform-field-tablinks" onclick="openTab(event, 'rm_social_fields_tab')"><?php echo wp_kses_post((string)RM_UI_Strings::get("LABEL_SOCIAL_FIELDS")); ?> <span class="rm-field-counter">8</span></li>
  <li class="rmform-field-tablinks" onclick="openTab(event, 'rm_display_fields_tab')"><?php esc_html_e('Display Fields', 'custom-registration-form-builder-with-submission-manager'); ?>  <span class="rm-field-counter">16</span></li>
  <?php if(!defined('REGMAGIC_ADDON')) { ?>
  <li class="rmform-field-tablinks" onclick="openTab(event, 'rm_premium_fields_tab')"><?php esc_html_e('Premium Fields', 'custom-registration-form-builder-with-submission-manager'); ?>  <span class="rm-field-counter">14</span></li>
  <?php } ?>
</ul>
        
    </div>
    
    

    <div class="rm-field-selector-right">
        <div id="rm-field-search-text" class="rm-field-search-result rm-mb-3 rm-text-dark" style="display: none;"><span></span> <a href="javascript:void(0)" onclick="rmResetFieldSearch();"><?php esc_html_e('Reset search', 'custom-registration-form-builder-with-submission-manager'); ?></a></div>
        <div id="rm-nofield-search-text" class="rm-field-search-result-notfound rm-field-search-result rm-mb-3 rm-text-dark" style="display: none;"><span></span> <a href="javascript:void(0)" onclick="rmResetFieldSearch();"><?php esc_html_e('Reset search', 'custom-registration-form-builder-with-submission-manager'); ?></a></div>
       <div class="rmform-fields"> 
     <?php if($data->form->form_type==1 && (!in_array('Username', $primary_fields) || !in_array('UserPassword', $primary_fields))): ?>   
    
        <!--<div class="rm-field-tab-cat">Account Fields</div>-->
        
            <?php if(!in_array('Username', $primary_fields)) : ?>
                <div title="<?php esc_html_e("This allows user to input their desired username for account creation and login.",'custom-registration-form-builder-with-submission-manager'); ?>" class="rm_button_like_links" data-category="rm_user_password_tab" onclick="add_user_field_to_page('Username')">
                    <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg></span> 
                        <div class="rm-add-fields-text-wrap">
                            <div class="rm-add-fields-text"><?php esc_html_e("Account Username",'custom-registration-form-builder-with-submission-manager'); ?></div>
                            <div class="rm-add-fields-subtext"><?php esc_html_e("This allows user to input their desired username for account creation and login.",'custom-registration-form-builder-with-submission-manager'); ?></div>
                        </div>
                    </a>
                </div> 
            <?php endif; ?>    
            <?php if(!in_array('UserPassword', $primary_fields)) : ?>
                <div title="<?php esc_html_e("This allows user to input a secure password for their account creation and login.",'custom-registration-form-builder-with-submission-manager'); ?>" class="rm_button_like_links" data-category="rm_user_password_tab" onclick="add_user_field_to_page('UserPassword')">
                    <a href="javascript:void(0)">
                        <div class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M280-240q-100 0-170-70T40-480q0-100 70-170t170-70q66 0 121 33t87 87h432v240h-80v120H600v-120H488q-32 54-87 87t-121 33Zm0-80q66 0 106-40.5t48-79.5h246v120h80v-120h80v-80H434q-8-39-48-79.5T280-640q-66 0-113 47t-47 113q0 66 47 113t113 47Zm0-80q33 0 56.5-23.5T360-480q0-33-23.5-56.5T280-560q-33 0-56.5 23.5T200-480q0 33 23.5 56.5T280-400Zm0-80Z"/></svg></div>
                         <div class="rm-add-fields-text-wrap">
                            <div class="rm-add-fields-text"><?php esc_html_e("Account Password",'custom-registration-form-builder-with-submission-manager'); ?></div>
                            <div class="rm-add-fields-subtext"><?php esc_html_e("This allows user to input a secure password for their account creation and login.",'custom-registration-form-builder-with-submission-manager'); ?></div>
                         </div>
                    </a>
                </div> 
            <?php endif; ?>
       
  
    <?php endif; ?> 
        
        <!-- Common Field--->
        
        
            <!--<div class="rm-field-tab-cat"><?php echo wp_kses_post((string)RM_UI_Strings::get("LABEL_COMMON_FIELDS")); ?></div>-->
            <div title="<?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_HELP_TEXT_Textbox")); ?>" data-category="rm_common_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Textbox')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M280-160v-520H80v-120h520v120H400v520H280Zm360 0v-320H520v-120h360v120H760v320H640Z"/></svg></span> 
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_TYPE_TEXT")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_HELP_TEXT_Textbox")); ?></div>
                    </div>
                </a>
            </div>
           
                <div title="<?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_HELP_TEXT_Select")); ?>" data-category="rm_common_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Select')">
                    <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m480-360 160-160H320l160 160Zm0 280q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                        </span> 
                        <div class="rm-add-fields-text-wrap">
                            <div class="rm-add-fields-text"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_TYPE_DROPDOWN")); ?></div>
                             <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_HELP_TEXT_Select")); ?></div>
                        </div>
                    </a>
                </div>

                <div title="<?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_HELP_TEXT_Radio")); ?>" data-category="rm_common_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Radio')">
                    <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                           <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                        </span> 
                        <div class="rm-add-fields-text-wrap">
                            <div class="rm-add-fields-text"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_TYPE_RADIO")); ?></div>
                            <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_HELP_TEXT_Radio")); ?></div>
                        </div>
                    </a>
                </div> 
            
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Textarea")); ?>" data-category="rm_common_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Textarea')">
                    <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M160-200v-80h400v80H160Zm0-160v-80h640v80H160Zm0-160v-80h640v80H160Zm0-160v-80h640v80H160Z"/></svg>                        
                        </span>
                        <div class="rm-add-fields-text-wrap">
                            <div class="rm-add-fields-text"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_TYPE_TEXTAREA")); ?></div>
                            <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_HELP_TEXT_Textarea")); ?></div>
                        </div>
                    </a> 
            </div>  
            
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Checkbox")); ?>" data-category="rm_common_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Checkbox')">
                <a href="javascript:void(0)">
                  <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q8 0 15 1.5t14 4.5l-74 74H200v560h560v-266l80-80v346q0 33-23.5 56.5T760-120H200Zm261-160L235-506l56-56 170 170 367-367 57 55-424 424Z"/></svg>
                  </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_CHECKBOX")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Checkbox")); ?></div>
                    </div>
                </a>
            </div>
        
        
        <!-- Common Field End -->

        <!-- Special Field--->
        
        
            <!--<div class="rm-field-tab-cat" ><?php echo wp_kses_post((string)RM_UI_Strings::get("LABEL_SPECIAL_FIELDS")); ?></div>-->
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_jQueryUIDate")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('jQueryUIDate')">
                <a href="javascript:void(0)">
                  <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_DATE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_jQueryUIDate")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Email")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Email')">      
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200ZM160-640v-80 480-400Z"/></svg>
                    </span>
                      <div class="rm-add-fields-text-wrap">
                          <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_EMAIL")); ?></div>
                          <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Email")); ?></div>
                      </div>
                
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Url")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('URL')">       
                <a href="javascript:void(0)">
                     <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M440-280H280q-83 0-141.5-58.5T80-480q0-83 58.5-141.5T280-680h160v80H280q-50 0-85 35t-35 85q0 50 35 85t85 35h160v80ZM320-440v-80h320v80H320Zm200 160v-80h160q50 0 85-35t35-85q0-50-35-85t-85-35H520v-80h160q83 0 141.5 58.5T880-480q0 83-58.5 141.5T680-280H520Z"/></svg>
                    </span>
                     <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_URL")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Url")); ?></div>
                     </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Password")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Password')">    
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M80-200v-80h800v80H80Zm46-242-52-30 34-60H40v-60h68l-34-58 52-30 34 58 34-58 52 30-34 58h68v60h-68l34 60-52 30-34-60-34 60Zm320 0-52-30 34-60h-68v-60h68l-34-58 52-30 34 58 34-58 52 30-34 58h68v60h-68l34 60-52 30-34-60-34 60Zm320 0-52-30 34-60h-68v-60h68l-34-58 52-30 34 58 34-58 52 30-34 58h68v60h-68l34 60-52 30-34-60-34 60Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_PASSWORD")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Password")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Number")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Number')">     
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m240-160 40-160H120l20-80h160l40-160H180l20-80h160l40-160h80l-40 160h160l40-160h80l-40 160h160l-20 80H660l-40 160h160l-20 80H600l-40 160h-80l40-160H360l-40 160h-80Zm140-240h160l40-160H420l-40 160Z"/></svg>
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_NUMBER")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Number")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Country")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Country')">    
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm-40-82v-78q-33 0-56.5-23.5T360-320v-40L168-552q-3 18-5.5 36t-2.5 36q0 121 79.5 212T440-162Zm276-102q20-22 36-47.5t26.5-53q10.5-27.5 16-56.5t5.5-59q0-98-54.5-179T600-776v16q0 33-23.5 56.5T520-680h-80v80q0 17-11.5 28.5T400-560h-80v80h240q17 0 28.5 11.5T600-440v120h40q26 0 47 15.5t29 40.5Z"/></svg>                    
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_COUNTRY")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Country")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Timezone")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Timezone')">   
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m472-312 56-56-128-128v-184h-80v216l152 152Zm248 172v-88q74-35 117-103t43-149q0-81-43-149T720-732v-88q109 38 174.5 131.5T960-480q0 115-65.5 208.5T720-140Zm-360 20q-75 0-140.5-28.5t-114-77q-48.5-48.5-77-114T0-480q0-75 28.5-140.5t77-114q48.5-48.5 114-77T360-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T720-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T360-120Zm0-80q117 0 198.5-81.5T640-480q0-117-81.5-198.5T360-760q-117 0-198.5 81.5T80-480q0 117 81.5 198.5T360-200Zm0-280Z"/></svg>
                    </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_TIMEZONE")); ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Timezone")); ?></div>
                     </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Terms")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Terms')">  
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M160-120q-33 0-56.5-23.5T80-200v-560q0-33 23.5-56.5T160-840h640q33 0 56.5 23.5T880-760v560q0 33-23.5 56.5T800-120H160Zm0-80h640v-560H160v560Zm40-80h200v-80H200v80Zm382-80 198-198-57-57-141 142-57-57-56 57 113 113Zm-382-80h200v-80H200v80Zm0-160h200v-80H200v80Zm-40 400v-560 560Z"/></svg>
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_T_AND_C")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Terms")); ?></div>
                    </div>
                </a>
            </div>

            
            
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Price")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Price')">      
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM246-720l96 200h280l110-200H246Zm-38-80h590q23 0 35 20.5t1 41.5L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68-39.5t-2-78.5l54-98-144-304H40v-80h130l38 80Zm134 280h280-280Z"/></svg>                   
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_PRICE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Price")); ?></div>
                    </div>
                </a>
            </div>
            
     
            

            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Address")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links">
                <a href="javascript:void(0)" onclick="add_new_field_to_page('Address')">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-480q33 0 56.5-23.5T560-560q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560q0 33 23.5 56.5T480-480Zm0 294q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-80Zm0-480Z"/></svg>                    
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_ADDRESS")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Price")); ?></div>
                    </div>
                </a>
            </div>
                
            
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Mobile")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Mobile')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M280-40q-33 0-56.5-23.5T200-120v-720q0-33 23.5-56.5T280-920h400q33 0 56.5 23.5T760-840v720q0 33-23.5 56.5T680-40H280Zm0-120v40h400v-40H280Zm0-80h400v-480H280v480Zm0-560h400v-40H280v40Zm0 0v-40 40Zm0 640v40-40Z"/></svg>
                    </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_MOBILE")); ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Mobile")); ?></div>
                     </div>
                </a>
            </div>
            

            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Hidden")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Hidden')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m644-428-58-58q9-47-27-88t-93-32l-58-58q17-8 34.5-12t37.5-4q75 0 127.5 52.5T660-500q0 20-4 37.5T644-428Zm128 126-58-56q38-29 67.5-63.5T832-500q-50-101-143.5-160.5T480-720q-29 0-57 4t-55 12l-62-62q41-17 84-25.5t90-8.5q151 0 269 83.5T920-500q-23 59-60.5 109.5T772-302Zm20 246L624-222q-35 11-70.5 16.5T480-200q-151 0-269-83.5T40-500q21-53 53-98.5t73-81.5L56-792l56-56 736 736-56 56ZM222-624q-29 26-53 57t-41 67q50 101 143.5 160.5T480-280q20 0 39-2.5t39-5.5l-36-38q-11 3-21 4.5t-21 1.5q-75 0-127.5-52.5T300-500q0-11 1.5-21t4.5-21l-84-82Zm319 93Zm-151 75Z"/></svg>             
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_HIDDEN")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Hidden")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Image")); ?>" data-category="rm_special_fields_tab"  class="rm_button_like_links" onclick="add_new_field_to_page('ESign')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m499-287 335-335-52-52-335 335 52 52Zm-261 87q-100-5-149-42T40-349q0-65 53.5-105.5T242-503q39-3 58.5-12.5T320-542q0-26-29.5-39T193-600l7-80q103 8 151.5 41.5T400-542q0 53-38.5 83T248-423q-64 5-96 23.5T120-349q0 35 28 50.5t94 18.5l-4 80Zm280 7L353-358l382-382q20-20 47.5-20t47.5 20l70 70q20 20 20 47.5T900-575L518-193Zm-159 33q-17 4-30-9t-9-30l33-159 165 165-159 33Z"/></svg>
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php esc_html_e('ESign','custom-registration-form-builder-with-submission-manager'); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Image")); ?></div>
                    </div>
                </a>
            </div>
            

            <?php if($is_privacy_added==0):  ?>
            <div title="<?php esc_html_e('Displays a customizable privacy policy link with text and an optional checkbox for submitter\'s explicit consent.', 'custom-registration-form-builder-with-submission-manager'); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Privacy')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-80q-139-35-229.5-159.5T160-516v-244l320-120 320 120v244q0 85-29 163.5T688-214L560-342q-18 11-38.5 16.5T480-320q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 22-5.5 42.5T618-398l60 60q20-41 31-86t11-92v-189l-240-90-240 90v189q0 121 68 220t172 132q26-8 49.5-20.5T576-214l56 56q-33 27-71.5 47T480-80Zm0-320q33 0 56.5-23.5T560-480q0-33-23.5-56.5T480-560q-33 0-56.5 23.5T400-480q0 33 23.5 56.5T480-400Zm8-77Z"/></svg>
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php esc_html_e('Privacy Policy', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                        <div class="rm-add-fields-subtext"><?php esc_html_e('Displays a customizable privacy policy link with text and an optional checkbox for submitter\'s explicit consent.', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            <?php if(defined('REGMAGIC_ADDON')) { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_File")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('File')">
            <?php } else { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_File")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
            <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M440-200h80v-167l64 64 56-57-160-160-160 160 57 56 63-63v167ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>
                    </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_FILE")); ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_File")); ?></div>
                         <?php if(!defined('REGMAGIC_ADDON')) { ?>
                         <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                         <?php } ?>
                     </div>
                </a>
            </div> 
                
            <?php if(defined('REGMAGIC_ADDON')) { ?>
              <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Repeatable")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Repeatable')">
                  <?php } else { ?>
                   <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Repeatable")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">

                   <?php } ?>
                  <a class="rm_field_deactivated" href="javascript:void(0)">
                      <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M200-200v-160 4-4 160Zm0 80q-33 0-56.5-23.5T120-200v-160q0-33 23.5-56.5T200-440h560q33 0 56.5 23.5T840-360H200v160h400v80H200Zm0-400q-33 0-56.5-23.5T120-600v-160q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v160q0 33-23.5 56.5T760-520H200Zm0-80h560v-160H200v160Zm0 0v-160 160ZM760-40v-80h-80v-80h80v-80h80v80h80v80h-80v80h-80Z"/></svg>                    
                      </span>
                       <div class="rm-add-fields-text-wrap">
                           <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_REPEAT")); ?></div>
                           <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Repeatable")); ?></div>
                           <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                       </div>
                  </a>
              </div> 
                  
                  
            <?php if(defined('REGMAGIC_ADDON')) { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Map")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Map')">
                 <?php } else { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Map")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
               <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m600-120-240-84-186 72q-20 8-37-4.5T120-170v-560q0-13 7.5-23t20.5-15l212-72 240 84 186-72q20-8 37 4.5t17 33.5v560q0 13-7.5 23T812-192l-212 72Zm-40-98v-468l-160-56v468l160 56Zm80 0 120-40v-474l-120 46v468Zm-440-10 120-46v-468l-120 40v474Zm440-458v468-468Zm-320-56v468-468Z"/></svg></span>
                      <div class="rm-add-fields-text-wrap">
                          <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_MAP")); ?></div>
                          <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Map")); ?></div>
                          <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                      </div>
                </a>
            </div> 
                    
                    
            <?php if(defined('REGMAGIC_ADDON')) { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Phone")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Phone')">
                <?php } else { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Phone")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">

                <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12ZM241-600l66-66-17-94h-89q5 41 14 81t26 79Zm358 358q39 17 79.5 27t81.5 13v-88l-94-19-67 67ZM241-600Zm358 358Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_PHONE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Phone")); ?></div>
                        <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                </a>
            </div> 
                
                
           <?php if(defined('REGMAGIC_ADDON')) { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Language")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Language')">
               <?php } else { ?>
               <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Language")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
                 <?php } ?>
               <a class="rm_field_deactivated" href="javascript:void(0)">
                   <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-80q-82 0-155-31.5t-127.5-86Q143-252 111.5-325T80-480q0-83 31.5-155.5t86-127Q252-817 325-848.5T480-880q83 0 155.5 31.5t127 86q54.5 54.5 86 127T880-480q0 82-31.5 155t-86 127.5q-54.5 54.5-127 86T480-80Zm0-82q26-36 45-75t31-83H404q12 44 31 83t45 75Zm-104-16q-18-33-31.5-68.5T322-320H204q29 50 72.5 87t99.5 55Zm208 0q56-18 99.5-55t72.5-87H638q-9 38-22.5 73.5T584-178ZM170-400h136q-3-20-4.5-39.5T300-480q0-21 1.5-40.5T306-560H170q-5 20-7.5 39.5T160-480q0 21 2.5 40.5T170-400Zm216 0h188q3-20 4.5-39.5T580-480q0-21-1.5-40.5T574-560H386q-3 20-4.5 39.5T380-480q0 21 1.5 40.5T386-400Zm268 0h136q5-20 7.5-39.5T800-480q0-21-2.5-40.5T790-560H654q3 20 4.5 39.5T660-480q0 21-1.5 40.5T654-400Zm-16-240h118q-29-50-72.5-87T584-782q18 33 31.5 68.5T638-640Zm-234 0h152q-12-44-31-83t-45-75q-26 36-45 75t-31 83Zm-200 0h118q9-38 22.5-73.5T376-782q-56 18-99.5 55T204-640Z"/></svg></span>
                   <div class="rm-add-fields-text-wrap">
                       <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_LANGUAGE")); ?></div>
                       <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Language")); ?></div>
                        <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>

                   </div>
               </a>
           </div>
                    
            <?php if(defined('REGMAGIC_ADDON')) { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Bdate")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Bdate')">
                <?php } else { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Bdate")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
                 <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M160-80q-17 0-28.5-11.5T120-120v-200q0-33 23.5-56.5T200-400v-160q0-33 23.5-56.5T280-640h160v-58q-18-12-29-29t-11-41q0-15 6-29.5t18-26.5l42-42q2-2 14-6 2 0 14 6l42 42q12 12 18 26.5t6 29.5q0 24-11 41t-29 29v58h160q33 0 56.5 23.5T760-560v160q33 0 56.5 23.5T840-320v200q0 17-11.5 28.5T800-80H160Zm120-320h400v-160H280v160Zm-80 240h560v-160H200v160Zm80-240h400-400Zm-80 240h560-560Zm560-240H200h560Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_BDATE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Bdate")); ?></div>
                        <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                </a>
            </div>        
                    
          
            
               <?php if(defined('REGMAGIC_ADDON')) { ?>
               <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Gender")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Gender')">
                  <?php } else { ?>
                   <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Gender")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
                    <?php } ?>
                    <a class="rm_field_deactivated" href="javascript:void(0)">
                        <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M220-120v-260h-20q-17 0-28.5-11.5T160-420v-180q0-33 23.5-56.5T240-680h120q33 0 56.5 23.5T440-600v180q0 17-11.5 28.5T400-380h-20v260q0 17-11.5 28.5T340-80h-80q-17 0-28.5-11.5T220-120Zm80-600q-33 0-56.5-23.5T220-800q0-33 23.5-56.5T300-880q33 0 56.5 23.5T380-800q0 33-23.5 56.5T300-720Zm300 600v-200h-65q-20 0-32-16.5t-5-36.5l84-253q8-26 29.5-40t48.5-14q27 0 48.5 14t29.5 40l84 253q7 20-5 36.5T785-320h-65v200q0 17-11.5 28.5T680-80h-40q-17 0-28.5-11.5T600-120Zm60-600q-33 0-56.5-23.5T580-800q0-33 23.5-56.5T660-880q33 0 56.5 23.5T740-800q0 33-23.5 56.5T660-720Z"/></svg></span>
                        <div class="rm-add-fields-text-wrap">
                            <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_GENDER")); ?></div>
                            <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Gender")); ?></div>
                             <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                        </div>
                    </a>
                </div> 
                   

               <?php if(defined('REGMAGIC_ADDON')) { ?>
                 <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Time")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Time')">
                 <?php } else { ?>
                 <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Time")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">

                <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Z"/></svg></span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_TIME")); ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Time")); ?></div>
                         <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                     </div>
                </a>
            </div>
                     
                     
                     
            <?php if(defined('REGMAGIC_ADDON')) { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Image")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Image')">
                 <?php } else { ?>
              <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Image")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">

                <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm40-80h480L570-480 450-320l-90-120-120 160Zm-40 80v-560 560Zm140-360q25 0 42.5-17.5T400-620q0-25-17.5-42.5T340-680q-25 0-42.5 17.5T280-620q0 25 17.5 42.5T340-560Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_IMAGE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Image")); ?></div>
                        <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                </a>
            </div>
                
          <?php if(defined('REGMAGIC_ADDON')) { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Shortcode")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Shortcode')">
                <?php } else { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Shortcode")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">

                  <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M600-160v-80h120v-480H600v-80h200v640H600Zm-440 0v-640h200v80H240v480h120v80H160Z"/></svg>                   
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_SHORTCODE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Shortcode")); ?></div>
                        <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                </a>
            </div>
                
                <?php if(defined('REGMAGIC_ADDON')) { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Multi-Dropdown")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Multi-Dropdown')">
                <?php } else { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Multi-Dropdown")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
                  <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M222-200 80-342l56-56 85 85 170-170 56 57-225 226Zm0-320L80-662l56-56 85 85 170-170 56 57-225 226Zm298 240v-80h360v80H520Zm0-320v-80h360v80H520Z"/></svg>                   
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_MULTI_DROP_DOWN")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Multi-Dropdown")); ?></div>
                        <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>

                    </div>
                </a>
                </div>
                    
                          
        <?php if(defined('REGMAGIC_ADDON')) { ?>
        <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Rating")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Rating')">
                    <?php } else { ?>
                     <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Rating")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
                     <?php } ?>
                        <a class="rm_field_deactivated" href="javascript:void(0)">
                            <span class="rm-add-fields-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m606-286-33-144 111-96-146-13-58-136v312l126 77ZM233-120l65-281L80-590l288-25 112-265 112 265 288 25-218 189 65 281-247-149-247 149Z"/></svg>
                            </span>
                            <div class="rm-add-fields-text-wrap">
                                <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_RATING")); ?></div>
                                <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Rating")); ?></div>
                                <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                            </div>
                        </a>
        </div>
            
            
        <?php if(defined('REGMAGIC_ADDON')) { ?>
                  <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Custom")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('Custom')">
                  <?php } else { ?>
                  <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Custom")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
                    <?php } ?>
                    <a class="rm_field_deactivated" href="javascript:void(0)">
                       <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m358.15-505.84 88.7-89.31-69.08-69.7-45.16 45.16-42.15-42.15L335-707l-61.54-61.54-89.31 89.31 174 173.39Zm320.7 321.3 89.3-89.31-61.54-61.54-45.15 44.54L619.31-333l44.54-45.15-69.31-68.7-88.7 88.7 173.01 173.61ZM697.46-760l63.16 63.15L697.46-760ZM288.08-140H140v-148.08l175.39-175.38L100-679.23l173.46-173.46 216.77 216.38 164.85-165.46q9.31-9.31 20.46-13.77 11.15-4.46 23.31-4.46 12.15 0 23.3 4.46 11.16 4.46 20.46 13.77l59.16 60.93q9.31 9.3 13.57 20.46 4.27 11.15 4.27 23.3 0 12.16-4.27 22.81-4.26 10.65-13.57 19.96L637.69-489.23l215 215.77L679.23-100 463.46-315.39 288.08-140ZM200-200h62.54l392.38-391.77-63.15-63.15L200-262.54V-200Zm423.85-423.23-32.08-31.69 63.15 63.15-31.07-31.46Z"/></svg>                      
                       </span>
                       <div class="rm-add-fields-text-wrap">
                           <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_CUSTOM")); ?></div>
                           <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Custom")); ?></div>
                            <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>

                       </div>
                    </a>
        </div>
        <?php if(defined('REGMAGIC_ADDON')) { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_DigitalSignature")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('DigitalSign')">
                  <?php } else { ?>
                  <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_DigitalSignature")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
                    <?php } ?>
                    <a class="rm_field_deactivated" href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368">
                                <path d="M400-160q0-40 28.5-68.5T480-256q40 0 68.5 28.5T577-160q0 40-28.5 68.5T480-64q-40 0-68.5-28.5T400-160Zm100-108q-11 0-18.5 7.5T480-160q0-11 7.5-18.5T506-186q11 0 18.5 7.5T532-160q0 11-7.5 18.5T506-134ZM560-112q11 0 18.5 7.5T586-96q0 11-7.5 18.5T560-70q-11 0-18.5-7.5T534-96q0-11 7.5-18.5T560-112ZM480-560q70 0 119.5 49.5T649-391q20 32 32 69.5T688-152q0 46-16.5 89.5t-46.5 73.5q-22.5 22.5-49.5 34.5t-61.5 12q-34 0-63-13t-51-38q-25-32-41.5-71T402-411q9-31 24-58t35.5-49q30.5-29.5 69.5-49T480-560Z"></path>
                            </svg>
                        </span>
                        <div class="rm-add-fields-text-wrap">
                            <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_DIGITALSIGNATURE")); ?></div>
                            <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_DigitalSignature")); ?></div>
                             <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>

                        </div>
                    </a>
            </div>
                <?php 
                if(defined('REGMAGIC_ADDON')) {
                    if( class_exists('RMSubscriptions')){
                        if(!$is_subscription_added && !$is_product_added ){
                            ?>
                            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Subscription")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-subscription-field-available rm-premium-field-active" onclick="add_new_field_to_page('Subscription')">
                            <?php
                        }else{
                            ?>
                            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Subscription")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-subscription-field-already-added rm-premium-field-already-added">
                            <?php
                        }
                    }else{
                      ?>
                        <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Subscription")); ?>" data-category="rm_special_fields_tab" class="rm_button_like_links rm-subscription-field-addon-not-installed rm-premium-field">
                    <?php } 
                } else { ?>
                    <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Subscription")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-subscription-field-1 rm-premium-field">
                <?php } ?>
                    <a class="rm_field_deactivated" href="javascript:void(0)">
                       <span class="rm-add-fields-icon">
<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M160-440v80h640v-80H160Zm0-440h640q33 0 56.5 23.5T880-800v440q0 33-23.5 56.5T800-280H640v200l-160-80-160 80v-200H160q-33 0-56.5-23.5T80-360v-440q0-33 23.5-56.5T160-880Zm0 320h640v-240H160v240Zm0 200v-440 440Z"/></svg>                    
                       </span>
                       <div class="rm-add-fields-text-wrap">
                           <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_SUBSCRIPTION")); ?></div>
                           <div class="rm-add-fields-subtext">
                               <?php 
                                /*if( !class_exists('RMSubscriptions')){
                                    echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SUBSCRIPTION_Not_Installed_Resctriction"));
                                }else if($is_product_added){ 
                                    echo '<div class="rm-subscription-subtext">' . wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_PRODUCT_SUBSCRIPTION_Resctriction")) . '</div>';
                                }else if($is_subscription_added){
                                    echo '<div class="rm-subscription-subtext">' . wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SUBSCRIPTION_Resctriction")) . '</div>';
                                }else{
                                    echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Subscription"));
                                }*/
                               
                               ?>
                               <?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Subscription")); ?>
                           </div>
                            <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium +', 'custom-registration-form-builder-with-submission-manager'); ?></div>

                       </div>
                        <?php if(defined('REGMAGIC_ADDON')) {
                            if(!class_exists('RMSubscriptions') || $is_product_added || $is_subscription_added){?>
                            <span class="rmform-field-info"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#EB595E"><path d="M0 0h24v24H0z" fill="none"></path><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path></svg></span>
                            <div class="rm-subscription-field-popover" style="display:none">
                                <?php 
                                if( !class_exists('RMSubscriptions')){
                                    echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SUBSCRIPTION_Not_Installed_Resctriction"));
                                }else if($is_product_added){
                                    echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_PRODUCT_SUBSCRIPTION_Resctriction"));   
                                }else if($is_subscription_added){
                                        echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SUBSCRIPTION_Resctriction"));
                                }else{
                                    echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Subscription"));
                                }
                                ?>

                            </div>
                        <?php } }?>
                    </a>
                </div>        
              
        <!--Special Field End--->
 
            <!----Profile Tab ----->
            
            <!--<div class="rm-field-tab-cat"> <?php echo wp_kses_post((string)RM_UI_Strings::get("LABEL_PROFILE_FIELDS")); ?></div> -->
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Fname")); ?>" data-category="rm_profile_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Fname')">
                <a href="javascript:void(0)">
                       <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg>
                       </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_FNAME")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Fname")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Lname")); ?>" data-category="rm_profile_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Lname')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg>
                        </span>
                        <div class="rm-add-fields-text-wrap">
                            <div class="rm-add-fields-text"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_TYPE_LNAME")); ?></div>
                            <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string) RM_UI_Strings::get("FIELD_HELP_TEXT_Lname")); ?></div>
                        </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_BInfo")); ?>" data-category="rm_profile_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('BInfo')">
                <a href="javascript:void(0)">
                     <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-252.31q-59.08 0-113.92 19.04-54.85 19.04-101.47 57.88.77 5.47 4.24 9.81 3.46 4.35 7.69 5.58h406.54q4.23-1.23 7.69-5.58 3.46-4.34 4.23-9.81-45.85-38.84-100.69-57.88-54.85-19.04-114.31-19.04Zm0-60q69 0 129 21t111 59v-555.38q0-4.62-3.85-8.46-3.84-3.85-8.46-3.85H252.31q-4.62 0-8.46 3.85-3.85 3.84-3.85 8.46v555.38q51-38 111-59t129-21Zm0-146.15q-28.85 0-49.42-20.58Q410-499.61 410-528.46t20.58-49.42q20.57-20.58 49.42-20.58t49.42 20.58Q550-557.31 550-528.46t-20.58 49.42q-20.57 20.58-49.42 20.58ZM252.31-100Q222-100 201-121q-21-21-21-51.31v-615.38Q180-818 201-839q21-21 51.31-21h455.38Q738-860 759-839q21 21 21 51.31v615.38Q780-142 759-121q-21 21-51.31 21H252.31ZM480-398.46q54.15 0 92.08-37.92Q610-474.31 610-528.46t-37.92-92.08q-37.93-37.92-92.08-37.92t-92.08 37.92Q350-582.61 350-528.46t37.92 92.08q37.93 37.92 92.08 37.92Zm0-130Z"/></svg>   
                     </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_BINFO")); ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_BInfo")); ?></div>
                     </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Nickname")); ?>" data-category="rm_profile_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Nickname')">
                <a href="javascript:void(0)">
                     <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M620-520q25 0 42.5-17.5T680-580q0-25-17.5-42.5T620-640q-25 0-42.5 17.5T560-580q0 25 17.5 42.5T620-520Zm-280 0q25 0 42.5-17.5T400-580q0-25-17.5-42.5T340-640q-25 0-42.5 17.5T280-580q0 25 17.5 42.5T340-520Zm140 260q68 0 123.5-38.5T684-400h-66q-22 37-58.5 58.5T480-320q-43 0-79.5-21.5T342-400h-66q25 63 80.5 101.5T480-260Zm0 180q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Z"/></svg>                    
                     </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_NICKNAME")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Nickname")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Website")); ?>" data-category="rm_profile_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Website')">
                <a href="javascript:void(0)">
                     <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H160v400Z"/></svg>                   
                     </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_WEBSITE")); ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Website")); ?></div>
                     </div>
                </a>
            </div>
            
               <?php if(defined('REGMAGIC_ADDON')) { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SecEmail")); ?>" data-category="rm_profile_fields_tab" class="rm_button_like_links rm-premium-field-active" onclick="add_new_field_to_page('SecEmail')">
                 <?php } else { ?>
                <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SecEmail")); ?>" data-category="rm_premium_fields_tab" class="rm_button_like_links rm-premium-field">
                   <?php } ?>  

                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480v58q0 59-40.5 100.5T740-280q-35 0-66-15t-52-43q-29 29-65.5 43.5T480-280q-83 0-141.5-58.5T280-480q0-83 58.5-141.5T480-680q83 0 141.5 58.5T680-480v58q0 26 17 44t43 18q26 0 43-18t17-44v-58q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93h200v80H480Zm0-280q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_SEMAIL")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SecEmail")); ?></div>
                        <div class="rm-premium-tag"><span><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><rect fill="none" height="24" width="24"/><path d="M9.68,13.69L12,11.93l2.31,1.76l-0.88-2.85L15.75,9h-2.84L12,6.19L11.09,9H8.25l2.31,1.84L9.68,13.69z M20,10 c0-4.42-3.58-8-8-8s-8,3.58-8,8c0,2.03,0.76,3.87,2,5.28V23l6-2l6,2v-7.72C19.24,13.87,20,12.03,20,10z M12,4c3.31,0,6,2.69,6,6 s-2.69,6-6,6s-6-2.69-6-6S8.69,4,12,4z M12,19l-4,1.02v-3.1C9.18,17.6,10.54,18,12,18s2.82-0.4,4-1.08v3.1L12,19z"/></svg></span><?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?></div>

                    </div>
                </a>
            </div>
            <?php if(class_exists('Profile_Magic')) { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_PGAvatar")); ?>" data-category="rm_profile_fields_tab" data-field="user-avatar" class="rm_button_like_links rm-pg-integration-link" onclick="add_new_field_to_page('PGAvatar');">
            <?php } else { ?>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_PGAvatar")); ?>" data-category="rm_profile_fields_tab" data-field="user-avatar" class="rm_button_like_links rm-pg-integration-link">
            <?php } ?>
                <a class="rm_field_deactivated" href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_PGAVATAR")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_PGAvatar")); ?></div>
                    <?php if(!class_exists('Profile_Magic')) { ?>
                            <div class="rmfield-pg-icon">
                                <span>
                        <svg width="100%" height="100%" viewBox="0 0 119 105" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
    <g transform="matrix(0.274037,0,0,0.267441,-40.0808,-16.8213)">
        <g id="Background-Figures" serif:id="Background Figures" transform="matrix(3.64915,-0,-0,3.73914,146.261,62.8974)">
            <use xlink:href="#_Image1" x="0" y="9.541" width="118.434px" height="83.405px" transform="matrix(0.995242,0,0,0.992913,0,0)"/>
        </g>
        <g id="Figure-1" serif:id="Figure 1" transform="matrix(1.07825,0,0,1.07825,87.1635,-137.965)">
            <g transform="matrix(0.750418,0,0,0.556529,124.426,-655.791)">
                <path d="M277.519,1964L76.793,1964C76.793,1964 84.442,2016.76 91.897,2068.18C100.036,2124.31 135.716,2164.71 177.154,2164.71C177.155,2164.71 177.156,2164.71 177.157,2164.71C218.595,2164.71 254.276,2124.31 262.415,2068.18C269.87,2016.76 277.519,1964 277.519,1964Z" style="fill:url(#_Linear2);"/>
            </g>
            <g transform="matrix(0.711839,0,0,0.715556,112.134,-1002.59)">
                <path d="M383.959,1978.72C366.668,1925.51 353.056,1905.63 319.433,1852C304.845,1828.73 248.882,1840.04 200.574,1840.13C151.637,1840.23 92.474,1824.66 76.334,1852C51.083,1894.77 21.663,1945.59 17.189,1973.89C14.203,1992.78 29.4,2033.79 56.667,2033.79C56.667,2033.79 152.564,2027.86 200.574,2027.86C248.584,2027.86 344.728,2033.79 344.728,2033.79C371.995,2033.79 390.927,2000.17 383.959,1978.72Z" style="fill:url(#_Linear3);"/>
                <clipPath id="_clip4">
                    <path d="M383.959,1978.72C366.668,1925.51 353.056,1905.63 319.433,1852C304.845,1828.73 248.882,1840.04 200.574,1840.13C151.637,1840.23 92.474,1824.66 76.334,1852C51.083,1894.77 21.663,1945.59 17.189,1973.89C14.203,1992.78 29.4,2033.79 56.667,2033.79C56.667,2033.79 152.564,2027.86 200.574,2027.86C248.584,2027.86 344.728,2033.79 344.728,2033.79C371.995,2033.79 390.927,2000.17 383.959,1978.72Z"/>
                </clipPath>
                <g clip-path="url(#_clip4)">
                    <g transform="matrix(4.75434,-0,-0,4.84629,-80.5314,1661.47)">
                        <use xlink:href="#_Image5" x="20.584" y="52.02" width="75.602px" height="25.602px" transform="matrix(0.994763,0,0,0.984697,0,0)"/>
                    </g>
                    <path d="M383.959,1942.49C383.959,1942.49 359.397,1864.71 319.433,1840.13C295.983,1825.72 248.882,1840.04 200.574,1840.13C151.637,1840.23 90.032,1825.84 73.9,1853.52C36.975,1916.88 54.775,1881.27 17.189,1962.03C5.209,1987.76 26.887,2035.85 54.154,2035.85L383.959,1942.49Z" style="fill:url(#_Linear6);"/>
                </g>
            </g>
            <g id="Ellipse-13" serif:id="Ellipse 13" transform="matrix(1.06805,0,0,0.997093,-14.3874,2.82006)">
                <ellipse cx="252.5" cy="237.5" rx="47.5" ry="53.5" style="fill:url(#_Linear7);"/>
            </g>
        </g>
    </g>
    <defs>
        <image id="_Image1" width="119px" height="84px" xlink:href="data:image/png;base64,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"/>
        <linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(5.45214e-15,123.022,-89.0402,7.5329e-15,189.438,1976.54)"><stop offset="0" style="stop-color:rgb(10,177,75);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(78,232,136);stop-opacity:1"/></linearGradient>
        <linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(-160.93,-56.7709,55.6938,-164.042,324.239,1986.36)"><stop offset="0" style="stop-color:rgb(78,232,136);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(10,177,75);stop-opacity:1"/></linearGradient>
        <image id="_Image5" width="76px" height="26px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEwAAAAaCAYAAAAdQLrBAAAACXBIWXMAAA7EAAAOxAGVKw4bAAADMUlEQVRYheWYzXLaQBCER4CD7SRPloOfNYe8mg1G4BzYNq1Wz0gYnITKVE3t6g9pP3p6V+riP4zHnz+67Njz06+36tr0wluPBMqc8Q6AKcCbB2bAdKaftYg3akd9hnZzwASQ9jvpa+p5EScwyIO0A2j/NLBCPRWchekvYgwOATiHiNhLO4K2us7QrhOJeirVKJDFRDp1AVBPbdf60Y7j3PirwM4AVIFZSn9J+5aRAwOsvuUuIl7lOGdE/GFgBSDuz4GzlFwV2wos4ghg3xKgtnT8QInn+3wPmwFI/QbbqhAGsKLtlUkHLFPWa8tNyxfqb+MIs48TvOsrzEBSUJXfOJUAxF3Scl9h4T4I9qsdnYN9Cng0SVwMbEJFGSRXWqoUBoP8Qn2Fpv7FoNjcUYLRtrVcQ64bxIeAFSpyJebUo4AqONoysMyreMCqKvgSjvOyYrSUoPa8ZQVBqlTkDLoqrQxMBkvLD/dRj2KfwpoqmxU5e3PNvFlyRqlVHoQBZcpROJqu/FhRbtZjNfECdBdHA0e+SD7HyeQZGqvtPQbAJiBVPjQF6C4i1gSE+w6UKsmZuEJSj0JuCQhmQW1xHNAY+lhhRbk5SJkHubJiOGvpV0pSSLwW0lcZV16sKAaykdzS+bs4LSOgsIF/RUR0DVY2o6mKFFClIAeIz6nMO/MkAEJmkFhVG9mHc3EdQ9pTvvsXf63Ag7oVdaWiTD0KSUtvChLCqQhKytTkAHGZcTIkNvk93RvPMQg1Up7RnFE7OPcG1hQkt/bhaZ0HU0GqUgE5SO6rhP0OxsDWMTbsCpCD5JSUzWwc/MAKKIOUActUhJbLjSGxV6WgGNiDQKoAVaBcqemrCT+sKgmDZEDa6j6nIgbUm3taNU2BYmBfEzCcFSRdSHKp6cc4/MNTSpoCtIsxoKzULoakwL7FUWX3pp0DCaCiPUgfp88n2cxWgZpSkJaZmrb7xHwRJAX2vQHiZFgZJKyL+vZb/G+yitS43SuJy3MAZSq6CiSOVUQ8tlRYDIoBQfIR+fSfQVJYvH+qxFypOUBXh8SBWZLLzZXYoQ0EoZ6EVxLnTa516pkLyL0Ufyokjt9+D4LyoIgxGwAAAABJRU5ErkJggg=="/>
        <linearGradient id="_Linear6" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(95.736,146.191,-143.417,97.5875,249.773,1843.08)"><stop offset="0" style="stop-color:rgb(10,177,75);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(78,232,136);stop-opacity:1"/></linearGradient>
        <linearGradient id="_Linear7" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1.50403,108.337,-98.7051,1.65079,251.329,183.405)"><stop offset="0" style="stop-color:rgb(10,177,75);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(78,232,136);stop-opacity:1"/></linearGradient>
    </defs>
</svg>
                                </span></div>
                    <?php } ?>
                    </div>
                </a>
            </div>

    <!----Profile Tab end----->

    <!--WooCommerce Field--->
        <?php if ( class_exists( 'WooCommerce' ) ): ?>
        <!--<div class="rm-field-tab-cat"> <?php echo esc_html__('WooCommerce Fields', 'custom-registration-form-builder-with-submission-manager'); ?></div> -->
       
            <div title="<?php echo esc_html__('WooCommerce Billing Field', 'custom-registration-form-builder-with-submission-manager'); ?>" data-category="rm_wc_fields_tab" data-field="woo-commerce-field" class="rm_button_like_links rm-woo-commerce-field" onclick="add_new_field_to_page('WCBilling')">
                <a href="javascript:void(0)" >
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><defs><style>.cls-1{fill:#7f54b3;}.cls-2{fill:#fff;}</style></defs><title>woocommerce</title><path class="cls-1" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path class="cls-2" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo esc_html__('WooCommerce Billing Field', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo esc_html__('WooCommerce Billing Field', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo esc_html__('WooCommerce Shipping Field', 'custom-registration-form-builder-with-submission-manager'); ?>" data-category="rm_wc_fields_tab" data-field="woo-commerce-field" class="rm_button_like_links rm-woo-commerce-field" onclick="add_new_field_to_page('WCShipping')">
                <a href="javascript:void(0)">
                      <span class="rm-add-fields-icon"><svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><defs><style>.cls-1{fill:#7f54b3;}.cls-2{fill:#fff;}</style></defs><title>woocommerce</title><path class="cls-1" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path class="cls-2" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg></span>
                      <div class="rm-add-fields-text-wrap">
                          <div class="rm-add-fields-text"><?php echo esc_html__('WooCommerce Shipping Field', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                          <div class="rm-add-fields-subtext"><?php echo esc_html__('WooCommerce Shipping Field', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                      </div>
                </a>
            </div>
            <div title="<?php echo esc_html__('Billing Phone Number', 'custom-registration-form-builder-with-submission-manager'); ?>" data-category="rm_wc_fields_tab" data-field="woo-commerce-field" class="rm_button_like_links rm-woo-commerce-field" onclick="add_new_field_to_page('WCBillingPhone')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><defs><style>.cls-1{fill:#7f54b3;}.cls-2{fill:#fff;}</style></defs><title>woocommerce</title><path class="cls-1" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path class="cls-2" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo esc_html__('Billing Phone Number', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo esc_html__('Billing Phone Number', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                </a>
            </div>
        
        <?php else: ?>
        <!--<div class="rm-field-tab-cat" class="rmform-fields"> <?php echo esc_html__('WooCommerce Fields (Install and activate WooCommerce to enable these fields)', 'custom-registration-form-builder-with-submission-manager'); ?></div> -->

            <div title="<?php echo esc_html__('WooCommerce Billing Field', 'custom-registration-form-builder-with-submission-manager'); ?>" data-category="rm_wc_fields_tab" data-field="woo-commerce-field" class="rm_button_like_links rm-woo-commerce-field">
                <a href="javascript:void(0)" class="rm_field_deactivated">
                       <span class="rm-add-fields-icon"><svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><defs><style>.cls-1{fill:#7f54b3;}.cls-2{fill:#fff;}</style></defs><title>woocommerce</title><path class="cls-1" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path class="cls-2" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg></span>
                       <div class="rm-add-fields-text-wrap">
                          <div class="rm-add-fields-text"><?php echo esc_html__('WooCommerce Billing Field', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                          <div class="rm-add-fields-subtext"><?php echo esc_html__('WooCommerce Billing Field', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                       </div>
                    <span class="rmform-field-info"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#EB595E"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></span>
                    <div class="rm-woo-commerce-field-popover" style="display:none" ><?php echo esc_html__('WooCommerce is required for this field to work. Please install and activate the WooCommerce plugin.', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                </a>
                  
            </div>
            <div title="<?php echo esc_html__('WooCommerce Shipping Field', 'custom-registration-form-builder-with-submission-manager'); ?>" data-category="rm_wc_fields_tab" data-field="woo-commerce-field" class="rm_button_like_links rm-woo-commerce-field">
                    <a href="javascript:void(0)" class="rm_field_deactivated">
                        <span class="rm-add-fields-icon"><svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><defs><style>.cls-1{fill:#7f54b3;}.cls-2{fill:#fff;}</style></defs><title>woocommerce</title><path class="cls-1" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path class="cls-2" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg></span>
                         <div class="rm-add-fields-text-wrap">
                             <div class="rm-add-fields-text"><?php echo esc_html__('WooCommerce Shipping Field', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                             <div class="rm-add-fields-subtext"><?php echo esc_html__('WooCommerce Shipping Field', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                         </div>
                        <span class="rmform-field-info"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#EB595E"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></span>
                        <div class="rm-woo-commerce-field-popover" style="display:none" ><?php echo esc_html__('WooCommerce is required for this field to work. Please install and activate the WooCommerce plugin.', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </a>
            </div>
            <div title="<?php echo esc_html__('Billing Phone Number', 'custom-registration-form-builder-with-submission-manager'); ?>" data-category="rm_wc_fields_tab" data-field="woo-commerce-field" class="rm_button_like_links rm-woo-commerce-field">
                <a href="javascript:void(0)" class="rm_field_deactivated">
                    <span class="rm-add-fields-icon"><svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><defs><style>.cls-1{fill:#7f54b3;}.cls-2{fill:#fff;}</style></defs><title>woocommerce</title><path class="cls-1" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path class="cls-2" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg></span>
                    <div class="rm-add-fields-text-wrap">
                       <div class="rm-add-fields-text"><?php echo esc_html__('Billing Phone Number', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                       <div class="rm-add-fields-subtext"><?php echo esc_html__('Billing Phone Number', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                    <span class="rmform-field-info"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#EB595E"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></span>
                    <div class="rm-woo-commerce-field-popover" style="display:none" ><?php echo esc_html__('WooCommerce is required for this field to work. Please install and activate the WooCommerce plugin.', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                </a>
            </div>
        
        <?php endif; ?>
   
<!--Woocommerce Field Ends-->
    
        
<!---Social Field--->

      <!--  <div class="rm-field-tab-cat"><?php echo wp_kses_post((string)RM_UI_Strings::get("LABEL_SOCIAL_FIELDS")); ?></div> -->

            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Facebook")); ?>" data-category="rm_social_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Facebook')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
                            <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/>
                        </svg>                       
                    </span>
                      <div class="rm-add-fields-text-wrap">
                          <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_FACEBOOK")); ?></div>
                          <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Facebook")); ?></div>
                      </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Twitter")); ?>" data-category="rm_social_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Twitter')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
                                <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
                            </svg>                   
                        </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_TWITTER")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Twitter")); ?></div>
                    </div>
                </a>
            </div>
            <!--
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Google")); ?>" class="rm_button_like_links" onclick="add_new_field_to_page('Google')"><a href="javascript:void(0)"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_GOOGLE")); ?></a></div>
            -->
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Instagram")); ?>" data-category="rm_social_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Instagram')">
                <a href="javascript:void(0)">
                     <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
                          <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334"/>
                        </svg>                        
                     </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_INSTAGRAM")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Instagram")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Linked")); ?>" data-category="rm_social_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Linked')">
                <a href="javascript:void(0)">
                     <span class="rm-add-fields-icon">
                         <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16">
                            <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"/>
                          </svg>
                        </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_LINKED")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Linked")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Youtube")); ?>" data-category="rm_social_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Youtube')">
                <a href="javascript:void(0)">
                     <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-youtube" viewBox="0 0 16 16">
                          <path d="M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.108-.082 2.06l-.008.105-.009.104c-.05.572-.124 1.14-.235 1.558a2.01 2.01 0 0 1-1.415 1.42c-1.16.312-5.569.334-6.18.335h-.142c-.309 0-1.587-.006-2.927-.052l-.17-.006-.087-.004-.171-.007-.171-.007c-1.11-.049-2.167-.128-2.654-.26a2.01 2.01 0 0 1-1.415-1.419c-.111-.417-.185-.986-.235-1.558L.09 9.82l-.008-.104A31 31 0 0 1 0 7.68v-.123c.002-.215.01-.958.064-1.778l.007-.103.003-.052.008-.104.022-.26.01-.104c.048-.519.119-1.023.22-1.402a2.01 2.01 0 0 1 1.415-1.42c.487-.13 1.544-.21 2.654-.26l.17-.007.172-.006.086-.003.171-.007A100 100 0 0 1 7.858 2zM6.4 5.209v4.818l4.157-2.408z"/>
                        </svg>                       
                     </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_YOUTUBE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Youtube")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_VKontacte")); ?>" data-category="rm_social_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('VKontacte')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                            <svg width="16" height="16" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 23.04C0 12.1788 0 6.74826 3.37413 3.37413C6.74826 0 12.1788 0 23.04 0H24.96C35.8212 0 41.2517 0 44.6259 3.37413C48 6.74826 48 12.1788 48 23.04V24.96C48 35.8212 48 41.2517 44.6259 44.6259C41.2517 48 35.8212 48 24.96 48H23.04C12.1788 48 6.74826 48 3.37413 44.6259C0 41.2517 0 35.8212 0 24.96V23.04Z" fill="#0077FF"/>
                                <path d="M25.54 34.5801C14.6 34.5801 8.3601 27.0801 8.1001 14.6001H13.5801C13.7601 23.7601 17.8 27.6401 21 28.4401V14.6001H26.1602V22.5001C29.3202 22.1601 32.6398 18.5601 33.7598 14.6001H38.9199C38.0599 19.4801 34.4599 23.0801 31.8999 24.5601C34.4599 25.7601 38.5601 28.9001 40.1201 34.5801H34.4399C33.2199 30.7801 30.1802 27.8401 26.1602 27.4401V34.5801H25.54Z" fill="white"/>
                            </svg>                        
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_VKONTACTE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_VKontacte")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Skype")); ?>" data-category="rm_social_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('Skype')">
                <a href="javascript:void(0)">
                    <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-skype" viewBox="0 0 16 16">
                                <path d="M4.671 0c.88 0 1.733.247 2.468.702a7.42 7.42 0 0 1 6.02 2.118 7.37 7.37 0 0 1 2.167 5.215q0 .517-.072 1.026a4.66 4.66 0 0 1 .6 2.281 4.64 4.64 0 0 1-1.37 3.294A4.67 4.67 0 0 1 11.18 16c-.84 0-1.658-.226-2.37-.644a7.42 7.42 0 0 1-6.114-2.107A7.37 7.37 0 0 1 .529 8.035q0-.545.08-1.081a4.644 4.644 0 0 1 .76-5.59A4.68 4.68 0 0 1 4.67 0zm.447 7.01c.18.309.43.572.729.769a7 7 0 0 0 1.257.653q.737.308 1.145.523c.229.112.437.264.615.448.135.142.21.331.21.528a.87.87 0 0 1-.335.723c-.291.196-.64.289-.99.264a2.6 2.6 0 0 1-1.048-.206 11 11 0 0 1-.532-.253 1.3 1.3 0 0 0-.587-.15.72.72 0 0 0-.501.176.63.63 0 0 0-.195.491.8.8 0 0 0 .148.482 1.2 1.2 0 0 0 .456.354 5.1 5.1 0 0 0 2.212.419 4.6 4.6 0 0 0 1.624-.265 2.3 2.3 0 0 0 1.08-.801c.267-.39.402-.855.386-1.327a2.1 2.1 0 0 0-.279-1.101 2.5 2.5 0 0 0-.772-.792A7 7 0 0 0 8.486 7.3a1 1 0 0 0-.145-.058 18 18 0 0 1-1.013-.447 1.8 1.8 0 0 1-.54-.387.73.73 0 0 1-.2-.508.8.8 0 0 1 .385-.723 1.76 1.76 0 0 1 .968-.247c.26-.003.52.03.772.096q.412.119.802.293c.105.049.22.075.336.076a.6.6 0 0 0 .453-.19.7.7 0 0 0 .18-.496.72.72 0 0 0-.17-.476 1.4 1.4 0 0 0-.556-.354 3.7 3.7 0 0 0-.708-.183 6 6 0 0 0-1.022-.078 4.5 4.5 0 0 0-1.536.258 2.7 2.7 0 0 0-1.174.784 1.9 1.9 0 0 0-.45 1.287c-.01.37.076.736.25 1.063"/>
                            </svg>                        
                    </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_SKYPE")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Skype")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SoundCloud")); ?>" data-category="rm_social_fields_tab" class="rm_button_like_links" onclick="add_new_field_to_page('SoundCloud')">
                <a href="javascript:void(0)">
                     <span class="rm-add-fields-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M639.8 298.6c-1.3 23.1-11.5 44.8-28.4 60.5s-39.2 24.4-62.3 24.1h-218c-4.8 0-9.4-2-12.8-5.4s-5.3-8-5.3-12.8V130.2c-.2-4 .9-8 3.1-11.4s5.3-6.1 9-7.7c0 0 20.1-13.9 62.3-13.9c25.8 0 51.1 6.9 73.3 20.1c17.3 10.2 32.3 23.8 44.1 40.1s20 34.8 24.2 54.4c7.5-2.1 15.3-3.2 23.1-3.2c11.7-.1 23.3 2.2 34.2 6.7S606.8 226.6 615 235s14.6 18.3 18.9 29.3s6.3 22.6 5.9 34.3zm-354-153.5c.1-1 0-2-.3-2.9s-.8-1.8-1.5-2.6s-1.5-1.3-2.4-1.7s-1.9-.6-2.9-.6s-2 .2-2.9 .6s-1.7 1-2.4 1.7s-1.2 1.6-1.5 2.6s-.4 1.9-.3 2.9c-6 78.9-10.6 152.9 0 231.6c.2 1.7 1 3.3 2.3 4.5s3 1.8 4.7 1.8s3.4-.6 4.7-1.8s2.1-2.8 2.3-4.5c11.3-79.4 6.6-152 0-231.6zm-44 27.3c-.2-1.8-1.1-3.5-2.4-4.7s-3.1-1.9-5-1.9s-3.6 .7-5 1.9s-2.2 2.9-2.4 4.7c-7.9 67.9-7.9 136.5 0 204.4c.3 1.8 1.2 3.4 2.5 4.5s3.1 1.8 4.8 1.8s3.5-.6 4.8-1.8s2.2-2.8 2.5-4.5c8.8-67.8 8.8-136.5 .1-204.4zm-44.3-6.9c-.2-1.8-1-3.4-2.3-4.6s-3-1.8-4.8-1.8s-3.5 .7-4.8 1.8s-2.1 2.8-2.3 4.6c-6.7 72-10.2 139.3 0 211.1c0 1.9 .7 3.7 2.1 5s3.1 2.1 5 2.1s3.7-.7 5-2.1s2.1-3.1 2.1-5c10.5-72.8 7.3-138.2 .1-211.1zm-44 20.6c0-1.9-.8-3.8-2.1-5.2s-3.2-2.1-5.2-2.1s-3.8 .8-5.2 2.1s-2.1 3.2-2.1 5.2c-8.1 63.3-8.1 127.5 0 190.8c.2 1.8 1 3.4 2.4 4.6s3.1 1.9 4.8 1.9s3.5-.7 4.8-1.9s2.2-2.8 2.4-4.6c8.8-63.3 8.9-127.5 .3-190.8zM109 233.7c0-1.9-.8-3.8-2.1-5.1s-3.2-2.1-5.1-2.1s-3.8 .8-5.1 2.1s-2.1 3.2-2.1 5.1c-10.5 49.2-5.5 93.9 .4 143.6c.3 1.6 1.1 3.1 2.3 4.2s2.8 1.7 4.5 1.7s3.2-.6 4.5-1.7s2.1-2.5 2.3-4.2c6.6-50.4 11.6-94.1 .4-143.6zm-44.1-7.5c-.2-1.8-1.1-3.5-2.4-4.8s-3.2-1.9-5-1.9s-3.6 .7-5 1.9s-2.2 2.9-2.4 4.8c-9.3 50.2-6.2 94.4 .3 144.5c.7 7.6 13.6 7.5 14.4 0c7.2-50.9 10.5-93.8 .3-144.5zM20.3 250.8c-.2-1.8-1.1-3.5-2.4-4.8s-3.2-1.9-5-1.9s-3.6 .7-5 1.9s-2.3 2.9-2.4 4.8c-8.5 33.7-5.9 61.6 .6 95.4c.2 1.7 1 3.3 2.3 4.4s2.9 1.8 4.7 1.8s3.4-.6 4.7-1.8s2.1-2.7 2.3-4.4c7.5-34.5 11.2-61.8 .4-95.4z"/></svg>                        
                     </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_TYPE_SOUNDCLOUD")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_SoundCloud")); ?></div>
                    </div>
                </a>
            </div>
      

<!--Special Field Ends-->

<!--Display Field--->

        <!--<div class="rm-field-tab-cat"> <?php echo wp_kses_post((string)RM_UI_Strings::get("LABEL_DISPLAY_FIELDS")); ?></div>-->
        

            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_HTMLH")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('HTMLH')">
                <a href="javascript:void(0)">
                       <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M420-160v-520H200v-120h560v120H540v520H420Z"/></svg>
                       </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("WIDGET_TYPE_HEADING")); ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_HTMLH")); ?></div>
                     </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_HTMLP")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('HTMLP')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M160-200v-80h400v80H160Zm0-160v-80h640v80H160Zm0-160v-80h640v80H160Zm0-160v-80h640v80H160Z"/></svg>
                        </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("WIDGET_TYPE_PARAGRAPH")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_HTMLH")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Divider")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('Divider')">
                <a href="javascript:void(0)">
                       <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M160-440v-80h640v80H160Z"/></svg>
                       </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("WIDGET_TYPE_DIVIDER")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Divider")); ?></div>
                    </div>
                </a>
            </div>         
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Spacing")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('Spacing')">
              
                <a href="javascript:void(0)">
                       <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M240-160 80-320l56-56 64 62v-332l-64 62-56-56 160-160 160 160-56 56-64-62v332l64-62 56 56-160 160Zm240-40v-80h400v80H480Zm0-240v-80h400v80H480Zm0-240v-80h400v80H480Z"/></svg>
                       </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("WIDGET_TYPE_SPACING")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_Spacing")); ?></div>
                    </div>
                </a>
                 
            </div>  
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_RICHTEXT")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('RichText')">
                <a href="javascript:void(0)">
                       <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M272-200v-560h221q65 0 120 40t55 111q0 51-23 78.5T602-491q25 11 55.5 41t30.5 90q0 89-65 124.5T501-200H272Zm121-112h104q48 0 58.5-24.5T566-372q0-11-10.5-35.5T494-432H393v120Zm0-228h93q33 0 48-17t15-38q0-24-17-39t-44-15h-95v109Z"/></svg>
                       </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("WIDGET_TYPE_RICHTEXT")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_RICHTEXT")); ?></div>
                    </div>
                </a>
            </div>  
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_TIMER")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('Timer')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M520-320h200v-320H520v320Zm-280 0h200v-320H240v320Zm-80 160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm640-560H160v480h640v-480Zm-640 0v480-480Z"/></svg>
                        </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("WIDGET_TYPE_TIMER")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_TIMER")); ?></div>
                    </div>
                </a>
            </div> 
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_LINK")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('Link')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>                       
                        </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php echo wp_kses_post((string)RM_UI_Strings::get("WIDGET_TYPE_LINK")); ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_LINK")); ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_YOUTUBE")); ?>"  data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('YouTubeV')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" style="width:22px;"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zm-317.5 213.5V175.2l142.7 81.2-142.7 81.2z"/></svg>
                        </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php esc_html_e('YouTube Video','custom-registration-form-builder-with-submission-manager') ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_YOUTUBE")); ?></div>
                     </div>
                </a>
            </div> 
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_IFRAME")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('Iframe')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M460-420h200v-80H460v80Zm-60 60v-200h320v200H400ZM160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H160v400Z"/></svg>
                        </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php esc_html_e('IFrame Embed','custom-registration-form-builder-with-submission-manager') ?></div>
                        <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_IFRAME")); ?>"</div>
                    </div>
                </a>
            </div> 
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_IMAGEV")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('ImageV')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm40-80h480L570-480 450-320l-90-120-120 160Zm-40 80v-560 560Z"/></svg>
                        </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php esc_html_e('Image','custom-registration-form-builder-with-submission-manager') ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_IMAGEV")); ?></div>
                     </div>
                </a>
            </div>
            <div title="<?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_PRICEV")); ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('PriceV')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M260-361v-40H160v-80h200v-80H200q-17 0-28.5-11.5T160-601v-160q0-17 11.5-28.5T200-801h60v-40h80v40h100v80H240v80h160q17 0 28.5 11.5T440-601v160q0 17-11.5 28.5T400-401h-60v40h-80Zm298 240L388-291l56-56 114 114 226-226 56 56-282 282Z"/></svg>
                        </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php esc_html_e('Total Price','custom-registration-form-builder-with-submission-manager') ?></div>
                         <div class="rm-add-fields-subtext"><?php echo wp_kses_post((string)RM_UI_Strings::get("FIELD_HELP_TEXT_PRICEV")); ?></div>
                     </div>
                </a>
            </div>
            <div title="<?php esc_html_e('If you have set form limits, you can display the limit status using this widget','custom-registration-form-builder-with-submission-manager') ?>" data-category="rm_display_fields_tab" class="rm_button_like_links rm-widget-lg" onclick="add_new_widget_to_page('SubCountV')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M320-160h320v-120q0-66-47-113t-113-47q-66 0-113 47t-47 113v120Zm160-360q66 0 113-47t47-113v-120H320v120q0 66 47 113t113 47ZM160-80v-80h80v-120q0-61 28.5-114.5T348-480q-51-32-79.5-85.5T240-680v-120h-80v-80h640v80h-80v120q0 61-28.5 114.5T612-480q51 32 79.5 85.5T720-280v120h80v80H160Z"/></svg>
                        </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php esc_html_e('Submission Countdown','custom-registration-form-builder-with-submission-manager') ?></div>
                        <div class="rm-add-fields-subtext"><?php esc_html_e('If you have set form limits, you can display the limit status using this widget','custom-registration-form-builder-with-submission-manager') ?></div>
                    </div>
                </a>
            </div>
            <div title="<?php esc_html_e('Display a location with marker using GoogleMaps','custom-registration-form-builder-with-submission-manager') ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('MapV')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="m600-120-240-84-186 72q-20 8-37-4.5T120-170v-560q0-13 7.5-23t20.5-15l212-72 240 84 186-72q20-8 37 4.5t17 33.5v560q0 13-7.5 23T812-192l-212 72Zm-40-98v-468l-160-56v468l160 56Zm80 0 120-40v-474l-120 46v468Zm-440-10 120-46v-468l-120 40v474Zm440-458v468-468Zm-320-56v468-468Z"/></svg>
                        </span>
                    <div class="rm-add-fields-text-wrap">
                        <div class="rm-add-fields-text"><?php esc_html_e('Map','custom-registration-form-builder-with-submission-manager') ?></div>
                        <div class="rm-add-fields-subtext"><?php esc_html_e('Display a location with marker using GoogleMaps','custom-registration-form-builder-with-submission-manager') ?></div>
                    </div>
                </a>
            </div>  
            <div title="<?php esc_html_e('Display stats about the form using graphs and charts','custom-registration-form-builder-with-submission-manager') ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('Form_Chart')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M280-280h80v-280h-80v280Zm160 0h80v-400h-80v400Zm160 0h80v-160h-80v160ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z"/></svg>
                        </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php esc_html_e('Form Data Chart','custom-registration-form-builder-with-submission-manager') ?></div>
                         <div class="rm-add-fields-subtext"><?php esc_html_e('Display stats about the form using graphs and charts','custom-registration-form-builder-with-submission-manager') ?></div>
                     </div>
                </a>
            </div> 
            <div title="<?php esc_html_e('Display various properties of the form','custom-registration-form-builder-with-submission-manager') ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('FormData')"> 
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M372.31-210v-60H820v60H372.31Zm0-240v-60H820v60H372.31Zm0-240v-60H820v60H372.31ZM206.54-173.46q-27.45 0-46.99-19.55Q140-212.55 140-240q0-27.45 19.55-46.99 19.54-19.55 46.99-19.55 27.45 0 46.99 19.55 19.55 19.54 19.55 46.99 0 27.45-19.55 46.99-19.54 19.55-46.99 19.55Zm0-240q-27.45 0-46.99-19.55Q140-452.55 140-480q0-27.45 19.55-46.99 19.54-19.55 46.99-19.55 27.45 0 46.99 19.55 19.55 19.54 19.55 46.99 0 27.45-19.55 46.99-19.54 19.55-46.99 19.55Zm0-240q-27.45 0-46.99-19.55Q140-692.55 140-720q0-27.45 19.55-46.99 19.54-19.55 46.99-19.55 27.45 0 46.99 19.55 19.55 19.54 19.55 46.99 0 27.45-19.55 46.99-19.54 19.55-46.99 19.55Z"/></svg>
                        </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php esc_html_e('Form Meta-Data','custom-registration-form-builder-with-submission-manager') ?></div>
                         <div class="rm-add-fields-subtext"><?php esc_html_e('Display various properties of the form','custom-registration-form-builder-with-submission-manager') ?></div>
                     </div>
                </a>
            </div>
            <div title="<?php esc_html_e('Display latest registrations/ submissions data in your form','custom-registration-form-builder-with-submission-manager') ?>" data-category="rm_display_fields_tab" class="rm_button_like_links" onclick="add_new_widget_to_page('Feed')">
                <a href="javascript:void(0)">
                        <span class="rm-add-fields-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M176.92-144.62q-30.3 0-51.3-21-21-21-21-51.3v-252.31h60v252.31q0 4.61 3.84 8.46 3.85 3.84 8.46 3.84h332.31v60H176.92ZM332.31-300Q302-300 281-321q-21-21-21-51.31v-252.3h60v252.3q0 4.62 3.85 8.46 3.84 3.85 8.46 3.85h332.3v60h-332.3Zm155.38-155.39q-30.3 0-51.3-21-21-21-21-51.3v-215.39q0-30.3 21-51.3 21-21 51.3-21h295.39q30.3 0 51.3 21 21 21 21 51.3v215.39q0 30.3-21 51.3-21 21-51.3 21H487.69Zm0-59.99h295.39q4.61 0 8.46-3.85 3.84-3.85 3.84-8.46v-147.69h-320v147.69q0 4.61 3.85 8.46 3.85 3.85 8.46 3.85Z"/></svg>
                        </span>
                     <div class="rm-add-fields-text-wrap">
                         <div class="rm-add-fields-text"><?php echo $data->form->form_type==0 ? esc_html__('Submission Feed','custom-registration-form-builder-with-submission-manager') : esc_html__('Registration Feed','custom-registration-form-builder-with-submission-manager'); ?></div>
                         <div class="rm-add-fields-subtext"><?php esc_html_e('Display latest registrations/ submissions data in your form','custom-registration-form-builder-with-submission-manager') ?></div>
                     </div>
                </a>
            </div>
     
            <?php do_action('rm_field_picker_model'); ?>
<!---Display Field End-->

  </div>
            
 <div class="rm-field-selector-footer">
    <div class="rm-field-selector-logo rm-text-center"><img src="<?php echo esc_url(RM_IMG_URL.'svg/rm-logo.svg');?>"></div>
    <div>
        <div class="rm-text-center rm-field-selector-support-head"><?php esc_html_e('Unable to find a field?', 'custom-registration-form-builder-with-submission-manager'); ?></div>
        <div class="rm-field-selector-support rm-text-center">
            <?php if(defined('REGMAGIC_ADDON')) { ?>
            <a href="https://registrationmagic.com/help-support/" target="_blank"><?php esc_html_e('Request our support team', 'custom-registration-form-builder-with-submission-manager'); ?> </a>
               <?php } else { ?>
            <a href="https://wordpress.org/support/plugin/custom-registration-form-builder-with-submission-manager/" target="_blank"><?php esc_html_e('Request our support team', 'custom-registration-form-builder-with-submission-manager'); ?> </a>

            <?php } ?>
        </div>
    </div>
</div>          
            
</div>
            

            
 </div>




        
 <script>
   
(function($){

  var colors = ['#71d0b1', '#6e8ecf', '#70afcf', '#717171', '#e9898a', '#fee292', '#c0deda', '#527471', '#cf6e8d', '#fda629', '#fd6d6f', '#8cafac', '#8fd072',]
   , colorsUsed = {}
   , $divsToColor = $('.rm-field-icon'),
   i=0;
   
 $divsToColor.each(function(){
    
   var $div = $(this);

   $div.css('backgroundColor', colors[i]);
     if( colorsUsed[randomColor] ){
         colorsUsed[randomColor]++;
     } else {
         colorsUsed[randomColor] = 1;
     }
     
   if(i >= 12){
       var $div = $(this)
     , randomColor = colors[ Math.floor( Math.random() * colors.length ) ];

   $div.css('backgroundColor', randomColor);
     if( colorsUsed[randomColor] ){
         colorsUsed[randomColor]++;
     } else {
         colorsUsed[randomColor] = 1;
     }
   }  

   i++;
 });



})(jQuery);  

jQuery(function () {
    var $elem = jQuery('.rm-field-selector');
    jQuery('#rm_fields_up').fadeIn('slow');
    jQuery('#rm_fields_down').fadeIn('slow');

    jQuery('#rm_fields_down').click(function (e) {
        jQuery('#rm-field-selector .rm-modal-wrap').animate({
            scrollTop: $elem.height()
        }, 900);
    });
    jQuery('#rm_fields_up').click(function (e) {
        jQuery('#rm-field-selector .rm-modal-wrap').animate({
            scrollTop: '0px'
        }, 900);
    });
});




function openTab(evt, rm_Field_Cat_Name) {
    var i, fields, rmTabLinks;

    // Hide all fields first
    fields = document.getElementsByClassName("rm_button_like_links");
    for (i = 0; i < fields.length; i++) {
        fields[i].style.display = "none";
    }

    // Remove active state from all tabs
    rmTabLinks = document.getElementsByClassName("rmform-field-tablinks");
    for (i = 0; i < rmTabLinks.length; i++) {
        rmTabLinks[i].className = rmTabLinks[i].className.replace(" rm-field-tab-active", "");
    }

    // Show only fields with the selected data-category
    var matchingFields = document.querySelectorAll(`[data-category='${rm_Field_Cat_Name}']`);
    for (i = 0; i < matchingFields.length; i++) {
        matchingFields[i].style.display = "flex";
    }

    // Add active class to the clicked tab
    evt.currentTarget.className += " rm-field-tab-active";
}

// Trigger default tab on page load
document.getElementById("rm_fields_defaultOpen").click();




/*
function openTab(evt, rm_Field_Cat_Name) {
  var i, RMFieldtabcontent, rmTabLinks;
  RMFieldtabcontent = document.getElementsByClassName("rmform-fields");
  for (i = 0; i < RMFieldtabcontent.length; i++) {
    RMFieldtabcontent[i].style.display = "none";
  }
  rmTabLinks = document.getElementsByClassName("rmform-field-tablinks");
  for (i = 0; i < rmTabLinks.length; i++) {
    rmTabLinks[i].className = rmTabLinks[i].className.replace(" rm-field-tab-active", "");
  }
  document.getElementById(rm_Field_Cat_Name).style.display = "flex";
  evt.currentTarget.className += " rm-field-tab-active";
}

document.getElementById("rm_fields_defaultOpen").click();*/

function rmSearchFields(el) {
    let value = el.value;
    let foundFields = 0;
    let searchText = document.querySelector('div#rm-field-search-text span');
    let noSearchText = document.querySelector('div#rm-nofield-search-text span');
    searchText.parentElement.style.display = 'none';
    noSearchText.parentElement.style.display = 'none';
    if(value != "") {
        value = value.replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase());
        document.querySelector('ul.rmform-field-tabs').style.display = "none";

        // Getting all fields blocks
        let fieldBlocks = document.getElementsByClassName('rm_button_like_links');
        for(i=0;i<fieldBlocks.length;i++) {
            let textDiv = fieldBlocks[i].querySelector('div.rm-add-fields-text');
            let subTextDiv = fieldBlocks[i].querySelector('div.rm-add-fields-subtext');
            let text = '';
            let subText = '';
            if(textDiv != null) {
                text = textDiv.textContent;
            }
            if(subTextDiv != null) {
                subText = subTextDiv.textContent;
            }
            if(text.includes(value) || subText.includes(value) || text.includes(value.toLowerCase()) || subText.includes(value.toLowerCase())) {
                fieldBlocks[i].style.display = "block";
                foundFields++;
            } else {
                fieldBlocks[i].style.display = "none";
            }
        }

        if(foundFields > 0) {
           searchText.innerHTML = '<?php esc_html_e('Search results for', 'custom-registration-form-builder-with-submission-manager'); ?> ' + '<span>' + el.value + '</span>';
            searchText.parentElement.style.display = 'block';
        } else {
            noSearchText.innerHTML = '<?php esc_html_e('No fields found for', 'custom-registration-form-builder-with-submission-manager'); ?> ' + '<span>' + el.value + '</span>';
            noSearchText.parentElement.style.display = 'block';
        }
    } else {
        let fieldTabsUl = document.querySelector('ul.rmform-field-tabs');
        let fieldTabs = fieldTabsUl.querySelectorAll('li');
        fieldTabsUl.style.display = "block";

        let fieldBlocks = document.getElementsByClassName('rm_button_like_links');
        for(i=0;i<fieldBlocks.length;i++) {
            if(fieldBlocks[i].dataset.category == "rm_common_fields_tab") {
                fieldBlocks[i].style.display = "block";
            } else {
                fieldBlocks[i].style.display = "none";
            }
        }

        for(i=0;i<fieldTabs.length;i++) {
            if(i == 0) {
                fieldTabs[i].classList.add('rm-field-tab-active');
            } else {
                fieldTabs[i].classList.remove('rm-field-tab-active');
            }
        }
    }
}

function rmResetFieldSearch() {
    let searchBox = document.getElementById('rmform-field-search');
    searchBox.value = '';
    rmSearchFields(searchBox);
}



//Woo Popover








</script>


<style>
    
    /*--- Field Modal----*/
    
    .rmform-fields.rmform-wc-fields .rm_button_like_links .rm-add-fields-icon svg {
        width: 30px
    }

.rm-field-selector-left {
    min-width: 196px;
    margin-right: 30px;
}

.rm-field-selector-right{
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.rm-field-search-result a {
    text-decoration: underline;
    vertical-align: inherit;
}

rm-field-search-result a:hover{
    text-decoration: none;
}

.rmform-fields {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.rm_button_like_links {
    border: 1px solid #DDECF8;
    background-color: #F6FAFD;
    border-radius: 3px;
    flex: calc(33.33% - 18px);
    max-width: calc(33.33% - 12px);
    min-width: 250px;
    transition: 0.8s;
}
    
    .rmform-fields .rm_button_like_links a {
        display: flex;
        align-items: center;
        width: 100%;
    }
    
    .rm_button_like_links.rm-premium-field a.rm_field_deactivated {
        cursor: not-allowed;
    }

    
    .rmform-fields .rm_button_like_links .rm-add-fields-icon{
        margin: 0 6px;
    }
    
    .rmform-fields .rm_button_like_links a .rm-add-fields-icon svg{
        width: 30px;
        fill:#0087BE;
    }
    
    .rmform-fields .rm_button_like_links .rm-add-fields-text{
        color: #000;
        font-size: 12px;
        font-weight: 500;
        text-transform: capitalize;
    }
        
    .rmform-fields .rm_button_like_links .rm-add-fields-subtext{
    color: #1d1d1d;
    white-space: nowrap;
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 11px;
    }
    
    .rm_button_like_links:hover{
        background-color: #fff;
        border: 1px solid #0087BE;
    }
    
 .rmform-field-tabs {
    margin: 0px;
    padding: 0px;
}
    .rmform-field-tabs {}
    
    .rmform-field-tabs .rmform-field-tablinks {
        padding: 14px;
        border-radius: 3px;
        cursor: pointer;
        color: #000;
        transition: 0.4s;
    }
        
    .rmform-field-tabs .rmform-field-tablinks:hover{
        background-color: #F6FAFD;
        color: #0087BE;
    }
        
    .rmform-field-tabs .rmform-field-tablinks.rm-field-tab-active{
        background-color: #F6FAFD;
        color: #0087BE;
    }
        
    .rmform-field-tabs .rmform-field-tablinks .rm-field-counter {
        background-color: #0087BE;
        font-size: 11px;
        color: #fff;
        border-radius: 3px;
        padding: 4px 8px;
        margin-left: 5px;
    }
        
    .rmform-wc-fields .rm_button_like_links{
        border: 1px solid #EAE3F2;
        background-color: #FAF8FC;
    }
        
    #rm_wc_fields_tab.rmform-wc-fields .rm_button_like_links{
        padding-left: 10px
    }
        
    .rmform-fields .rm_button_like_links a{
        position: relative;
        padding: 8px 10px 8px 0px;
    }

    .rm-add-fields-text-wrap {
        position: relative;
        width: calc(100% - 46px);
    }
        
    .rmform-fields .rm_button_like_links span.rmform-field-info {
        position: absolute;
        right: -12px;
        top: -17px;
    }

    .rm_button_like_links.rm-premium-field{
        background: linear-gradient(to right, rgba(128, 128, 128, 0.1), rgba(160, 160, 160, 0.1));
        border: 1px solid rgba(200, 200, 200, 0.5);
        color: rgba(100, 100, 100, 0.8);
        cursor: not-allowed;
        opacity: 0.6;
    }

    .rm_button_like_links.rm-premium-field .rm-add-fields-subtext{
        color: #6e6e6e;
    }

    .rmform-fields .rm_button_like_links.rm-premium-field a svg{
       fill: #606060;
    }


    .rmform-fields .rm_button_like_links.rm-premium-field .rm-premium-tag svg{
        fill: #fff;
        width: 12px;
        height: 12px;
    }

    .rm-premium-tag{
        position: absolute;
        right: -6px;
        background-color: #FF4ED6;
        border-radius: 3px;
        color: #fff;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        padding: 1px 4px;
        top: -23px;
        font-size: 8px;
        line-height: 20px;
        font-weight: 700;
        text-transform: uppercase;
        background-image: linear-gradient(to right, #ff4ed6, #ff39ac, #ff3681, #ff4459, #ff5933);
        display:none;
    }

    .rm-premium-tag span{
        line-height: 0;
         margin-right: 2px;
    }

    .rm-premium-tag span svg{
        width: 20px;
        fill:#fff;
    }

    .rm-field-search {
        margin-bottom: 19px;
    }

    .rm-field-search input{
        width: 100% !important;
    }

    #rm-field-selector .rm-modal-wrap{
        width: 80%;
        left: 10%;
        min-height: 600px;
    }
    
    .rmform-fields.rmform-wc-fields .rm_button_like_links .rm-add-fields-subtext{
        color: #7F54B3
    }
    
    .rmform-fields.rmform-wc-fields .rm_button_like_links  a.rm_field_deactivated .rmform-field-info svg{
        fill:#EB595E;
    }
    
    .rmform-wc-fields-deactive .rm-add-fields-text{}
    
    .rm-field-selector-logo{
        margin: 60px 0 5px 0;
    }
    
    .rm-field-selector-logo img{
        max-width: 134px;
        margin: 0px auto;
    }
    
    .rm-field-selector-support-head{
        font-size: 12px;
        margin-bottom: 2px;
        color: #000;
        font-weight: 400;
    }
    
    .rm-field-selector-support a{
        text-decoration: underline;
        font-size: 11px;
    }
    .rm-field-selector-support a:hover{
        text-decoration: none;
    }
    
    .rm_button_like_links.rm-premium-field-active .rm-premium-tag{
        display: none;
    }
    
    .rmfield-pg-icon span{
    border: 1px solid #DAE1E7;
    position: absolute;
    right: -18px;
    border-radius: 50%;
    color: #fff;
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    padding: 1px 4px;
    top: -23px;
    font-size: 9px;
    line-height: 20px;
    font-weight: 700;
    text-transform: uppercase;
    background-color: #fff;
    width: 34px;
    height: 34px;
    }
    
    .rm_button_like_links.rm-pg-integration-link{
        background-color: #F8FCF9;
        border: 1px solid #E3F2E7;
    }
    
    .rmform-fields .rm_button_like_links.rm-pg-integration-link a svg{
        fill:#5EB576;
    }
    
    .rmform-fields .rm_button_like_links.rm-pg-integration-link .rm-add-fields-subtext{
        color: #5EB576;
    }
    
    .rmform-fields .rm_button_like_links.rm-woo-commerce-field{
        border: 1px solid #EAE3F2;
        background-color: #FAF8FC;
    }
    
    .rmform-fields .rm_button_like_links.rm-woo-commerce-field:hover{
        border: 1px solid #7F54B3;
        background-color: #FAF8FC;
    }
    
    .rmform-fields .rm_button_like_links.rm-woo-commerce-field .rm-add-fields-subtext{ 
        color: #7F54B3;
    }
    
    .rmform-fields .rm_button_like_links.rm-woo-commerce-field .rm-add-fields-icon svg{
        width: 32px;
        margin-right: 4px;
    }
    
    .rmform-fields .rm_button_like_links span.rmform-field-info svg{
        width: 30px;
        height: 30px;
    }
    
    .rm-woo-commerce-field-popover,
    .rm-subscription-field-popover{
        display: none;
        position: absolute;
        background-color: #faf8fc;
        border: 1px solid #EAE3F2;
        border-radius: 4px;
        padding: 10px;
        box-shadow: 0px 1px 6px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        word-break: break-word;
        font-size: 11px;
        color: #333;
        top: -74px;
        right: -20px;
        max-width: 240px;
        line-height: 16px;
    }
    
    .rm-subscription-field-popover{
        /*
        background-color: #f6fafd;
        border: 1px solid #cce5f7;
        */
    }
    
.rm-woo-commerce-field-popover::before,
.rm-subscription-field-popover::before{
    content: '';
    position: absolute;
    top: 100%;
    left: 95%;
    transform: translateX(-95%);
    border-width: 10px;
    border-style: solid;
    border-color: #EAE3F2 transparent transparent transparent;
}

.rm-woo-commerce-field-popover::after,
.rm-subscription-field-popover::after{
    content: '';
    position: absolute;
    top: 100%;
    left: 94%;
    transform: translateX(-95%) translateY(0px);
    border-width: 9px;
    border-style: solid;
    border-color: #faf8fc transparent transparent transparent;
}


.rmform-fields .rm_button_like_links.rm-woo-commerce-field:hover .rm_field_deactivated .rm-woo-commerce-field-popover{
    display: block !important;
}


.rmform-fields .rm_button_like_links.rm-woo-commerce-field:hover .rm_field_deactivated .rm-woo-commerce-field-popover{
    display: block !important;
}


.rmform-fields .rm_button_like_links.rm-subscription-field-already-added:hover .rm_field_deactivated .rm-subscription-field-popover {
    display: block !important;
}

.rmform-fields .rm_button_like_links.rm-subscription-field-addon-not-installed:hover .rm_field_deactivated .rm-subscription-field-popover {
    display: block !important;
}

.rm_button_like_links.rm-premium-field.rm-subscription-field-addon-not-installed{
    opacity: 1;
    border: 1px solid #DDECF8;
    background-color: #F6FAFD;
    background-image: none;
}
    
    .rmform-fields .rm_button_like_links.rm-woo-commerce-field a.rm_field_deactivated{
       position: relative;
    }
    
    #rm-nofield-search-text span span{
        font-weight: 600;
        font-style: italic;
    }
    
    
    @media (min-width: 1550px) {
    #rm-field-selector .rm-modal-wrap {
       width: 60%;
       left: 20%;
       min-height: 650px;
   }

  
}

.rmform-field-tabs .rmform-field-tablinks.rmform-field-tablink-premium {
    color: #EB5989;
    background: linear-gradient(to right, rgb(255 78 214 / 5%), rgb(255 89 51 / 5%));
    border: 1px solid rgb(251 219 229);
    border-radius: 3px;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
    width: max-content;
}

/* Hover Effect */
.rmform-field-tabs .rmform-field-tablinks.rmform-field-tablink-premium:hover {
 background: linear-gradient(to right, rgb(255 78 214 / 8%), rgb(255 89 51 / 8%));   

}


.rmform-field-tabs .rmform-field-tablinks.rmform-field-tablink-premium .rm-field-counter{
        background-image: linear-gradient(to right, #ff4ed6, #ff39ac, #ff3681, #ff4459, #ff5933);
}
    
    
</style>

<?php //} ?>
