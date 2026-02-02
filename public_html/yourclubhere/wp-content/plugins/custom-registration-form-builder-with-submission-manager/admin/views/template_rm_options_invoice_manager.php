<?php
if (!defined('WPINC')) {
    die('Closed');
}
if(defined('REGMAGIC_ADDON') && class_exists('RM_Payments_Controller_Addon')) include_once(RM_ADDON_ADMIN_DIR . 'views/template_rm_options_invoice_manager.php'); else {
?>
<div class="rmagic">

    <!--Dialogue Box Starts-->
    <div class="rmcontent">
              <div class="rmheader"><?php esc_html_e('Invoice Configuration', 'custom-registration-form-builder-with-submission-manager') ?></div>
              <div class="rmrow">
                  <div class="rmfield" for="id_rm_admin_enable_invoice">
                      <label> <?php esc_html_e('Enable Invoice', 'custom-registration-form-builder-with-submission-manager') ?></label>
                  </div>
                  <div class="rminput">
                      <ul class="rmradio">
                          <li><span class="rm-pricefield-wrap"> 
                                  <input id="id_rm_admin_enable_invoice-1" type="checkbox" name="enable_invoice" class="id_rm_admin_enable_invoice"  disabled>
                                  <label for="id_rm_admin_enable_invoice-1"><span></span></label> 
                              </span>
                          </li> 
                       </ul>
                  </div>
                  <div class="rmnote">
                      <div class="rmprenote">
                          
                      </div>
                      <div class="rmnotecontent"><?php esc_html_e('Enable invoice downloadable by the users and the admins.', 'custom-registration-form-builder-with-submission-manager') ?></div>
                         <span class="rm_buy_pro_inline"><?php printf(esc_html__('To unlock this feature (and many more), please upgrade. <a href="%s" target="blank">Click here</a>', 'custom-registration-form-builder-with-submission-manager'), RM_Utilities::comparison_page_link()); ?> </span>
                  </div>
                      
              </div>
              <div class="buttonarea">
                  <div class="cancel">
                      <a value="&amp;#8592; &amp;nbsp; Cancel" href="?page=rm_options_manage" id="options_manage_invoice-element-20" "="">
                          ← &nbsp; Cancel</a>
                  </div> 
                 
                  <input type="submit" value="<?php esc_html_e('Save', 'custom-registration-form-builder-with-submission-manager') ?>" name="" class="rm-btn rm-btn-primary button button-primary" id="options_manage_invoice-element-21">
              </div>
       
 
        

        <?php
        $rm_promo_banner_title = esc_html__('Unlock invoice and more by upgrading','custom-registration-form-builder-with-submission-manager');
        //include RM_ADMIN_DIR . 'views/template_rm_promo_banner_bottom.php';
        ?>
    </div>
</div>
<?php } ?>