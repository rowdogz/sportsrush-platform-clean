<?php
if (!defined('WPINC')) {
    die('Closed');
}

if(!empty($_GET['rm_form_id'])): 
  $form_id= absint(sanitize_text_field($_GET['rm_form_id']));  
  $form= new RM_Forms();
  $form->load_from_db($form_id);
  if(empty($form->form_id))
      return;
?>
<script>
    document.title = "<?php echo esc_html($form->form_name); ?>";
</script>    
<?php endif; ?>
<?php if(!defined('REGMAGIC_ADDON') && (isset($_GET['page']) && $_GET['page'] != 'rm_form_manage')) { ?>
<!--Premium Banner-->
<div class="rmagic rmagic-premium-banner rm-hide-version-number" style="opacity: 0">
        <div class="rm-bg-white rm-border rm-box-wrap rm-my-4 rm-rounded-1">
            <div class="rm-box-row rm-align-items-center">
                <div class="rm-box-col-3">
                    <div class="rm-d-flex rm-box-h-100 rm-align-items-center rm-flex-wrap rm-align-content ">
                    <div class="rm-text-uppercase rm-fs-7 rm-fw-bold rm-mb-1 rm-pb-2 rm-text-dark"><?php esc_html_e("Every website deserves it.", 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    <div class="rm-fs-2 rm-fw-bold rm-mb-1 rm-text-dark rm-pb-1"><?php esc_html_e("RegistrationMagic", 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    <div class="rm-fs-2 rm-fw-bold rm-premium-text rm-py-2"><?php esc_html_e("Premium", 'custom-registration-form-builder-with-submission-manager'); ?></div>
                </div>
            </div>
            <div class="rm-box-col-2 rm-text-center rm-mb-5">
                <img src="<?php echo RM_IMG_URL . 'rm-premium-icon.png'; ?>" width="60px">
            </div>
            <div class="rm-box-col-4">
                <div class="rm-d-flex rm-box-h-100 rm-align-items-center rm-flex-wrap rm-align-content ">
                    <div class="rm-fs-6 rm-text-dark rm-pb-2 rm-mb-2 rm-fw-bold"><?php esc_html_e("Unlock the most powerful version:", 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    <ul class="rm-text-dark rm-p-0 rm-m-0 rm-mb-3">
                        <li class="rm-di-flex rm-align-items-center"><span class="rm-mr-1 rm-lh-0"><img src="<?php echo RM_IMG_URL . 'rm-tick-icon.png'; ?>" width="14px" ></span><?php esc_html_e("Access all advanced options and payment gateways.", 'custom-registration-form-builder-with-submission-manager'); ?></li>
                            <li class="rm-di-flex rm-align-items-center"><span class="rm-mr-1 rm-lh-0"><img src="<?php echo RM_IMG_URL . 'rm-tick-icon.png'; ?>" width="14px" ></span><?php esc_html_e("Even more powerful registration forms and login system.", 'custom-registration-form-builder-with-submission-manager'); ?></li>
                        <li class="rm-di-flex rm-align-items-center"><span class="rm-mr-1 rm-lh-0"><img src="<?php echo RM_IMG_URL . 'rm-tick-icon.png'; ?>" width="14px" ></span><?php esc_html_e("Even more powerful reporting and analysis features.", 'custom-registration-form-builder-with-submission-manager'); ?></li>
                       <!-- <li class="rm-di-flex rm-align-items-center rm-pb-0 rm-mb-0"><span class="rm-mr-1 rm-lh-0"><img src="<?php echo RM_IMG_URL . 'rm-tick-icon.png'; ?>" width="14px" ></span><?php esc_html_e("Even more integrations and payment gateways.", 'custom-registration-form-builder-with-submission-manager'); ?></li>-->
                    </ul>
                </div>
            </div>
                <div class="rm-box-col-3">
                    <div class="rm-text-center">
                        <a href="https://registrationmagic.com/comparison/?utm_source=wp_admin&utm_medium=premium_banner_footer&utm_campaign=admin_upgrade_premium" target="_blabk" class="rm-d-inline-block">
                            <button class="button button-primary rm-px-4 rm-py-1">
                                <?php esc_html_e("Upgrade Now!", 'custom-registration-form-builder-with-submission-manager'); ?>
                            </button>
                        </a>
                        <div class="rm-text-small rm-text-muted"><?php esc_html_e("It takes less than a minute to upgrade.", 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    </div>
                </div>
        </div>
          
    </div>
</div>
<!--Premium Banner End-->
<?php }
$dismiss_icon = get_option('rm_dismiss_floating_banner', 0);
if($dismiss_icon == 0) { ?>
<div class="rm-floating-flyout rm-d-none">
    <div class="rm-floating-flyout-overlay" style="display: none;"></div>
    <div class="rm-floating-items">
        <div class="rm-floating-item-wrap">
            <div class="rm-py-3 rm-px-4 rm-border-bottom rm-lh-0">
                <svg width="200" height="26" viewBox="0 0 260 33" fill="none" xmlns="http://www.w3.org/2000/svg" style="height:auto">
                    <path d="M4.292 24.416C4.292 24.848 4.496 25.1 4.904 25.172C4.952 25.196 5.324 25.208 6.02 25.208V25.82C5.372 25.844 4.4 25.904 3.104 26C1.808 26.096 0.836 26.156 0.188 26.18V25.568C0.716 25.568 1.028 25.556 1.124 25.532C1.412 25.436 1.556 25.196 1.556 24.812V10.016C1.556 9.536 1.34 9.296 0.908 9.296C0.476 9.056 0.26 8.84 0.26 8.648C1.844 8.552 3.152 8.42 4.184 8.252V12.896C5.144 9.704 6.992 8.108 9.728 8.108C10.88 8.108 11.456 8.528 11.456 9.368C11.456 9.752 11.252 10.112 10.844 10.448C10.46 10.76 10.076 10.916 9.692 10.916C9.332 10.916 9.152 10.784 9.152 10.52C9.152 10.232 9.296 9.992 9.584 9.8C9.536 9.8 9.452 9.788 9.332 9.764C9.212 9.764 9.116 9.764 9.044 9.764C7.388 9.764 6.128 10.94 5.264 13.292C4.616 15.044 4.292 16.916 4.292 18.908V24.416ZM27.7274 23.264C26.0234 25.4 23.6714 26.468 20.6714 26.468C18.2474 26.468 16.1954 25.58 14.5154 23.804C12.8594 22.028 12.0314 19.856 12.0314 17.288C12.0314 14.672 12.8474 12.488 14.4794 10.736C16.1354 8.984 18.1994 8.108 20.6714 8.108C22.6634 8.108 24.3554 8.744 25.7474 10.016C27.0674 11.216 27.8474 12.74 28.0874 14.588L15.1274 19.016C15.4154 20.504 15.9314 21.716 16.6754 22.652C17.8274 24.092 19.3274 24.812 21.1754 24.812C23.4554 24.812 25.4954 23.78 27.2954 21.716C27.3674 21.884 27.4394 22.148 27.5114 22.508C27.5834 22.844 27.6554 23.096 27.7274 23.264ZM14.9834 17.468C17.1194 16.652 20.3234 15.416 24.5954 13.76C24.1634 11.096 22.8434 9.764 20.6354 9.764C18.8354 9.764 17.4434 10.4 16.4594 11.672C15.4754 12.944 14.9834 14.708 14.9834 16.964V17.468ZM37.7714 9.692C36.4514 9.692 35.3714 10.112 34.5314 10.952C33.7154 11.768 33.3074 12.848 33.3074 14.192C33.3074 15.56 33.7034 16.664 34.4954 17.504C35.2874 18.32 36.3674 18.728 37.7354 18.728C39.0314 18.728 40.0754 18.32 40.8674 17.504C41.6594 16.664 42.0554 15.572 42.0554 14.228C42.0554 12.884 41.6474 11.792 40.8314 10.952C40.0394 10.112 39.0194 9.692 37.7714 9.692ZM30.4634 14.264C30.4634 12.488 31.1354 11.024 32.4794 9.872C33.8474 8.696 35.5394 8.108 37.5554 8.108C38.2994 8.108 39.0794 8.216 39.8954 8.432C42.3674 7.352 44.0354 6.812 44.8994 6.812C45.6434 6.812 46.0154 7.136 46.0154 7.784C46.0154 8.264 45.7634 8.516 45.2594 8.54L44.3594 8.576C43.0874 8.576 42.3674 8.864 42.1994 9.44C42.3434 9.536 42.5234 9.668 42.7394 9.836C44.1794 10.988 44.8994 12.44 44.8994 14.192C44.8994 15.872 44.2274 17.264 42.8834 18.368C41.5634 19.448 39.9074 19.988 37.9154 19.988C36.4754 19.988 35.3354 19.796 34.4954 19.412L34.3874 19.448C33.9554 19.592 33.7394 19.772 33.7394 19.988C33.7394 20.396 34.2074 20.672 35.1434 20.816C35.7194 20.888 36.9914 20.948 38.9594 20.996C40.9514 21.044 42.5714 21.524 43.8194 22.436C45.1634 23.42 45.8354 24.764 45.8354 26.468C45.8354 28.364 44.9954 29.912 43.3154 31.112C41.6594 32.312 39.6434 32.912 37.2674 32.912C35.3714 32.912 33.5954 32.408 31.9394 31.4C30.2834 30.392 29.4554 29.396 29.4554 28.412C29.4554 27.26 30.6914 26.564 33.1634 26.324C33.4034 26.3 33.7754 26.252 34.2794 26.18C32.4314 26.564 31.5074 27.044 31.5074 27.62C31.5074 28.316 32.0954 29.072 33.2714 29.888C34.5674 30.8 35.9714 31.256 37.4834 31.256C39.0914 31.256 40.4714 30.908 41.6234 30.212C42.8954 29.444 43.5314 28.436 43.5314 27.188C43.5314 26.012 42.9674 25.028 41.8394 24.236C40.7114 23.444 39.1994 23.072 37.3034 23.12L36.0434 23.156C35.9954 23.156 35.2754 23.132 33.8834 23.084C32.5874 23.036 31.9394 22.7 31.9394 22.076C31.9394 21.668 32.2034 21.14 32.7314 20.492C33.2114 19.916 33.6674 19.508 34.0994 19.268C31.6754 18.116 30.4634 16.448 30.4634 14.264ZM49.3994 2.636C49.3994 2.396 49.6394 2.012 50.1194 1.484C50.6234 0.955999 50.9954 0.692 51.2354 0.692C51.4754 0.692 51.8354 0.955999 52.3154 1.484C52.8194 2.012 53.0714 2.396 53.0714 2.636C53.0714 2.876 52.8194 3.26 52.3154 3.788C51.8354 4.316 51.4754 4.58 51.2354 4.58C50.9954 4.58 50.6234 4.316 50.1194 3.788C49.6394 3.26 49.3994 2.876 49.3994 2.636ZM52.8914 24.416C52.8914 24.944 53.1794 25.208 53.7554 25.208H54.2594L54.2954 25.82C53.6954 25.844 52.7834 25.904 51.5594 26C50.3354 26.096 49.4114 26.156 48.7874 26.18V25.568H49.2914C49.8674 25.568 50.1554 25.316 50.1554 24.812V10.16C50.1554 9.68 49.9274 9.44 49.4714 9.44C49.0154 9.248 48.7874 9.032 48.7874 8.792C49.6994 8.696 51.0674 8.516 52.8914 8.252V24.416ZM69.8834 20.852C69.8834 22.532 69.2834 23.888 68.0834 24.92C66.8834 25.952 65.2994 26.468 63.3314 26.468C61.2674 26.468 59.4314 25.772 57.8234 24.38C57.5354 24.38 57.3674 24.572 57.3194 24.956H56.8154V21.86C56.9594 21.764 57.1754 21.644 57.4634 21.5C57.7514 21.356 57.9674 21.248 58.1114 21.176C59.1434 23.6 60.9194 24.812 63.4394 24.812C64.5434 24.812 65.4794 24.524 66.2474 23.948C67.0394 23.348 67.4354 22.616 67.4354 21.752C67.4354 20.768 67.0634 20.012 66.3194 19.484C65.5754 18.956 64.3874 18.524 62.7554 18.188C58.9874 17.42 57.1034 15.728 57.1034 13.112C57.1034 11.672 57.6794 10.484 58.8314 9.548C60.0074 8.588 61.4114 8.108 63.0434 8.108C64.0754 8.108 65.1434 8.312 66.2474 8.72C67.1114 9.056 67.7714 9.416 68.2274 9.8C68.4434 9.8 68.5754 9.644 68.6234 9.332H69.1994V11.96C68.8154 12.248 68.4194 12.524 68.0114 12.788C67.0754 10.772 65.4434 9.764 63.1154 9.764C61.9634 9.764 61.0754 10.016 60.4514 10.52C59.8514 11 59.5514 11.648 59.5514 12.464C59.5514 13.232 59.8634 13.856 60.4874 14.336C61.1114 14.816 62.2274 15.236 63.8354 15.596C66.0914 16.1 67.6634 16.772 68.5514 17.612C69.4394 18.428 69.8834 19.508 69.8834 20.852ZM76.3992 21.932C76.3992 22.988 76.6032 23.732 77.0112 24.164C77.4192 24.572 78.1272 24.776 79.1352 24.776C79.9272 24.776 80.6112 23.972 81.1872 22.364L81.6912 23.336C81.4752 23.864 81.2232 24.332 80.9352 24.74C80.2632 25.7 79.4472 26.24 78.4872 26.36C78.2232 26.384 77.9712 26.396 77.7312 26.396C76.4352 26.396 75.4272 25.964 74.7072 25.1C74.0112 24.212 73.6632 23.036 73.6632 21.572V10.16H71.1792L71.4312 8.504C72.6552 8.504 73.5432 8.12 74.0952 7.352C74.4792 7.064 74.8392 6.332 75.1752 5.156C75.5352 3.788 75.7512 3.044 75.8232 2.924H76.3992V8.504H81.3672V10.16H76.3992V21.932ZM87.3311 24.416C87.3311 24.848 87.5351 25.1 87.9431 25.172C87.9911 25.196 88.3631 25.208 89.0591 25.208V25.82C88.4111 25.844 87.4391 25.904 86.1431 26C84.8471 26.096 83.8751 26.156 83.2271 26.18V25.568C83.7551 25.568 84.0671 25.556 84.1631 25.532C84.4511 25.436 84.5951 25.196 84.5951 24.812V10.016C84.5951 9.536 84.3791 9.296 83.9471 9.296C83.5151 9.056 83.2991 8.84 83.2991 8.648C84.8831 8.552 86.1911 8.42 87.2231 8.252V12.896C88.1831 9.704 90.0311 8.108 92.7671 8.108C93.9191 8.108 94.4951 8.528 94.4951 9.368C94.4951 9.752 94.2911 10.112 93.8831 10.448C93.4991 10.76 93.1151 10.916 92.7311 10.916C92.3711 10.916 92.1911 10.784 92.1911 10.52C92.1911 10.232 92.3351 9.992 92.6231 9.8C92.5751 9.8 92.4911 9.788 92.3711 9.764C92.2511 9.764 92.1551 9.764 92.0831 9.764C90.4271 9.764 89.1671 10.94 88.3031 13.292C87.6551 15.044 87.3311 16.916 87.3311 18.908V24.416ZM105.763 19.844V18.296C105.619 17.552 105.283 17 104.755 16.64C104.251 16.28 103.759 16.124 103.279 16.172L102.847 16.208C101.407 16.328 100.207 16.868 99.2465 17.828C98.2865 18.764 97.8065 19.856 97.8065 21.104C97.8065 22.136 98.1305 22.988 98.7785 23.66C99.4505 24.332 100.351 24.668 101.479 24.668C102.703 24.668 103.723 24.2 104.539 23.264C105.355 22.328 105.763 21.188 105.763 19.844ZM105.907 23.408C104.947 25.448 103.243 26.468 100.795 26.468C98.9705 26.468 97.5185 25.976 96.4385 24.992C95.3585 23.984 94.8185 22.7 94.8185 21.14C94.8185 19.604 95.4665 18.26 96.7625 17.108C98.0585 15.932 99.6425 15.188 101.515 14.876C101.827 14.828 102.235 14.816 102.739 14.84L103.459 14.876C104.131 14.9 104.899 15.068 105.763 15.38V12.5C105.763 11.612 105.391 10.928 104.646 10.448C103.926 9.968 103.015 9.728 101.911 9.728C101.119 9.728 100.243 9.872 99.2825 10.16C98.4905 10.424 97.9145 10.676 97.5545 10.916C98.0825 11.036 98.3465 11.36 98.3465 11.888C98.3465 12.2 98.1905 12.356 97.8785 12.356C97.8065 12.356 97.6505 12.332 97.4105 12.284C96.7865 12.092 96.4745 11.744 96.4745 11.24C96.4745 10.52 97.0025 9.824 98.0585 9.152C99.2105 8.432 100.591 8.072 102.199 8.072C106.399 8.072 108.499 9.776 108.499 13.184V24.164C108.499 24.836 108.679 25.172 109.039 25.172C109.519 25.172 109.759 24.752 109.759 23.912H110.263C110.263 24.56 109.963 25.124 109.363 25.604C108.787 26.084 108.115 26.324 107.347 26.324C106.555 26.324 106.075 25.352 105.907 23.408ZM116.407 21.932C116.407 22.988 116.611 23.732 117.019 24.164C117.427 24.572 118.135 24.776 119.143 24.776C119.935 24.776 120.619 23.972 121.195 22.364L121.699 23.336C121.483 23.864 121.231 24.332 120.943 24.74C120.271 25.7 119.455 26.24 118.495 26.36C118.231 26.384 117.979 26.396 117.739 26.396C116.443 26.396 115.435 25.964 114.715 25.1C114.019 24.212 113.671 23.036 113.671 21.572V10.16H111.187L111.439 8.504C112.663 8.504 113.551 8.12 114.103 7.352C114.487 7.064 114.847 6.332 115.183 5.156C115.543 3.788 115.759 3.044 115.831 2.924H116.407V8.504H121.375V10.16H116.407V21.932ZM124.423 2.636C124.423 2.396 124.663 2.012 125.143 1.484C125.647 0.955999 126.019 0.692 126.259 0.692C126.499 0.692 126.859 0.955999 127.339 1.484C127.843 2.012 128.095 2.396 128.095 2.636C128.095 2.876 127.843 3.26 127.339 3.788C126.859 4.316 126.499 4.58 126.259 4.58C126.019 4.58 125.647 4.316 125.143 3.788C124.663 3.26 124.423 2.876 124.423 2.636ZM127.915 24.416C127.915 24.944 128.203 25.208 128.779 25.208H129.283L129.319 25.82C128.719 25.844 127.807 25.904 126.583 26C125.359 26.096 124.435 26.156 123.811 26.18V25.568H124.315C124.891 25.568 125.179 25.316 125.179 24.812V10.16C125.179 9.68 124.951 9.44 124.495 9.44C124.039 9.248 123.811 9.032 123.811 8.792C124.723 8.696 126.091 8.516 127.915 8.252V24.416ZM150.091 17.288C150.091 19.856 149.215 22.028 147.463 23.804C145.711 25.58 143.575 26.468 141.055 26.468C138.535 26.468 136.399 25.58 134.647 23.804C132.919 22.028 132.055 19.856 132.055 17.288C132.055 14.72 132.919 12.548 134.647 10.772C136.399 8.996 138.535 8.108 141.055 8.108C143.599 8.108 145.735 8.996 147.463 10.772C149.215 12.548 150.091 14.72 150.091 17.288ZM147.103 17.288C147.103 14.984 146.563 13.16 145.483 11.816C144.403 10.448 142.927 9.764 141.055 9.764C139.183 9.764 137.707 10.448 136.627 11.816C135.571 13.16 135.043 14.984 135.043 17.288C135.043 19.592 135.571 21.428 136.627 22.796C137.707 24.14 139.183 24.812 141.055 24.812C142.927 24.812 144.403 24.14 145.483 22.796C146.563 21.428 147.103 19.592 147.103 17.288ZM167.539 24.416C167.539 24.944 167.803 25.208 168.331 25.208H168.799L168.835 25.82C167.083 25.916 165.739 26.036 164.803 26.18V14.012C164.803 11.18 163.555 9.764 161.059 9.764C159.715 9.764 158.575 10.316 157.639 11.42C156.703 12.5 156.235 13.808 156.235 15.344V24.416C156.235 24.944 156.523 25.208 157.099 25.208H157.639V25.82C157.039 25.844 156.139 25.904 154.939 26C153.739 26.096 152.839 26.156 152.239 26.18V25.568H152.707C153.235 25.568 153.499 25.316 153.499 24.812V10.016C153.499 9.536 153.295 9.296 152.887 9.296C152.455 9.08 152.239 8.864 152.239 8.648C152.455 8.624 152.779 8.588 153.211 8.54C153.643 8.492 153.955 8.468 154.147 8.468C154.771 8.42 155.419 8.348 156.091 8.252V11.456C157.555 9.224 159.415 8.108 161.671 8.108C163.543 8.108 164.983 8.684 165.991 9.836C167.023 10.964 167.539 12.572 167.539 14.66V24.416Z" fill="#5C5E6F"/>
                    <path d="M196.242 24.416C196.242 24.944 196.518 25.208 197.07 25.208H197.574V25.82C196.014 25.916 194.658 26.036 193.506 26.18V13.904C193.506 11.144 192.366 9.764 190.086 9.764C188.814 9.764 187.794 10.244 187.026 11.204C186.282 12.164 185.91 13.412 185.91 14.948V24.416C185.91 24.944 186.174 25.208 186.702 25.208H187.17V25.82C185.658 25.892 184.314 26.012 183.138 26.18V13.904C183.138 11.144 181.986 9.764 179.682 9.764C178.386 9.764 177.354 10.256 176.586 11.24C175.842 12.224 175.47 13.556 175.47 15.236V24.416C175.47 24.944 175.758 25.208 176.334 25.208H176.874L176.91 25.82C176.31 25.844 175.398 25.904 174.174 26C172.95 26.096 172.026 26.156 171.402 26.18V25.568H171.906C172.458 25.568 172.734 25.316 172.734 24.812V10.016C172.734 9.536 172.53 9.296 172.122 9.296C171.69 9.08 171.474 8.864 171.474 8.648C171.906 8.6 172.554 8.54 173.418 8.468C174.09 8.42 174.726 8.348 175.326 8.252V11.348C176.502 9.188 178.194 8.108 180.402 8.108C182.994 8.108 184.674 9.248 185.442 11.528C186.57 9.248 188.346 8.108 190.77 8.108C194.418 8.108 196.242 10.184 196.242 14.336V24.416ZM210.774 19.844V18.296C210.63 17.552 210.294 17 209.766 16.64C209.262 16.28 208.77 16.124 208.29 16.172L207.858 16.208C206.418 16.328 205.218 16.868 204.258 17.828C203.298 18.764 202.818 19.856 202.818 21.104C202.818 22.136 203.142 22.988 203.79 23.66C204.462 24.332 205.362 24.668 206.49 24.668C207.714 24.668 208.734 24.2 209.55 23.264C210.366 22.328 210.774 21.188 210.774 19.844ZM210.918 23.408C209.958 25.448 208.254 26.468 205.806 26.468C203.982 26.468 202.53 25.976 201.45 24.992C200.37 23.984 199.83 22.7 199.83 21.14C199.83 19.604 200.478 18.26 201.774 17.108C203.07 15.932 204.654 15.188 206.526 14.876C206.838 14.828 207.246 14.816 207.75 14.84L208.47 14.876C209.142 14.9 209.91 15.068 210.774 15.38V12.5C210.774 11.612 210.402 10.928 209.658 10.448C208.938 9.968 208.026 9.728 206.922 9.728C206.13 9.728 205.254 9.872 204.294 10.16C203.502 10.424 202.926 10.676 202.566 10.916C203.094 11.036 203.358 11.36 203.358 11.888C203.358 12.2 203.202 12.356 202.89 12.356C202.818 12.356 202.662 12.332 202.422 12.284C201.798 12.092 201.486 11.744 201.486 11.24C201.486 10.52 202.014 9.824 203.07 9.152C204.222 8.432 205.602 8.072 207.21 8.072C211.41 8.072 213.51 9.776 213.51 13.184V24.164C213.51 24.836 213.69 25.172 214.05 25.172C214.53 25.172 214.77 24.752 214.77 23.912H215.274C215.274 24.56 214.974 25.124 214.374 25.604C213.798 26.084 213.126 26.324 212.358 26.324C211.566 26.324 211.086 25.352 210.918 23.408ZM224.803 9.692C223.483 9.692 222.403 10.112 221.563 10.952C220.747 11.768 220.339 12.848 220.339 14.192C220.339 15.56 220.735 16.664 221.527 17.504C222.319 18.32 223.399 18.728 224.767 18.728C226.063 18.728 227.107 18.32 227.899 17.504C228.691 16.664 229.087 15.572 229.087 14.228C229.087 12.884 228.679 11.792 227.863 10.952C227.071 10.112 226.051 9.692 224.803 9.692ZM217.495 14.264C217.495 12.488 218.167 11.024 219.511 9.872C220.879 8.696 222.571 8.108 224.587 8.108C225.331 8.108 226.111 8.216 226.927 8.432C229.399 7.352 231.067 6.812 231.931 6.812C232.675 6.812 233.047 7.136 233.047 7.784C233.047 8.264 232.795 8.516 232.291 8.54L231.391 8.576C230.119 8.576 229.399 8.864 229.231 9.44C229.375 9.536 229.555 9.668 229.771 9.836C231.211 10.988 231.931 12.44 231.931 14.192C231.931 15.872 231.259 17.264 229.915 18.368C228.595 19.448 226.939 19.988 224.947 19.988C223.507 19.988 222.367 19.796 221.527 19.412L221.419 19.448C220.987 19.592 220.771 19.772 220.771 19.988C220.771 20.396 221.239 20.672 222.175 20.816C222.751 20.888 224.023 20.948 225.991 20.996C227.983 21.044 229.603 21.524 230.851 22.436C232.195 23.42 232.867 24.764 232.867 26.468C232.867 28.364 232.027 29.912 230.347 31.112C228.691 32.312 226.675 32.912 224.299 32.912C222.403 32.912 220.627 32.408 218.971 31.4C217.315 30.392 216.487 29.396 216.487 28.412C216.487 27.26 217.723 26.564 220.195 26.324C220.435 26.3 220.807 26.252 221.311 26.18C219.463 26.564 218.539 27.044 218.539 27.62C218.539 28.316 219.127 29.072 220.303 29.888C221.599 30.8 223.003 31.256 224.515 31.256C226.123 31.256 227.503 30.908 228.655 30.212C229.927 29.444 230.563 28.436 230.563 27.188C230.563 26.012 229.999 25.028 228.871 24.236C227.743 23.444 226.231 23.072 224.335 23.12L223.075 23.156C223.027 23.156 222.307 23.132 220.915 23.084C219.619 23.036 218.971 22.7 218.971 22.076C218.971 21.668 219.235 21.14 219.763 20.492C220.243 19.916 220.699 19.508 221.131 19.268C218.707 18.116 217.495 16.448 217.495 14.264ZM236.431 2.636C236.431 2.396 236.671 2.012 237.151 1.484C237.655 0.955999 238.027 0.692 238.267 0.692C238.507 0.692 238.867 0.955999 239.347 1.484C239.851 2.012 240.103 2.396 240.103 2.636C240.103 2.876 239.851 3.26 239.347 3.788C238.867 4.316 238.507 4.58 238.267 4.58C238.027 4.58 237.655 4.316 237.151 3.788C236.671 3.26 236.431 2.876 236.431 2.636ZM239.923 24.416C239.923 24.944 240.211 25.208 240.787 25.208H241.291L241.327 25.82C240.727 25.844 239.815 25.904 238.591 26C237.367 26.096 236.443 26.156 235.819 26.18V25.568H236.323C236.899 25.568 237.187 25.316 237.187 24.812V10.16C237.187 9.68 236.959 9.44 236.503 9.44C236.047 9.248 235.819 9.032 235.819 8.792C236.731 8.696 238.099 8.516 239.923 8.252V24.416ZM258.823 22.292C258.967 22.388 259.159 22.556 259.399 22.796C259.639 23.012 259.819 23.18 259.939 23.3C258.235 25.412 255.967 26.468 253.135 26.468C250.543 26.468 248.395 25.604 246.691 23.876C244.987 22.124 244.135 19.94 244.135 17.324C244.135 14.78 245.059 12.608 246.907 10.808C248.779 9.008 251.035 8.108 253.675 8.108C255.259 8.108 256.687 8.516 257.959 9.332C259.135 10.076 259.723 10.832 259.723 11.6C259.723 12.224 259.267 12.62 258.355 12.788C258.091 12.836 257.911 12.86 257.815 12.86C257.335 12.86 257.023 12.656 256.879 12.248C256.879 11.864 257.011 11.624 257.275 11.528C257.563 11.432 257.743 11.384 257.815 11.384L258.175 11.42C257.239 10.316 255.751 9.764 253.711 9.764C251.695 9.764 250.087 10.436 248.887 11.78C247.711 13.124 247.123 14.912 247.123 17.144C247.123 19.4 247.711 21.248 248.887 22.688C250.087 24.104 251.635 24.812 253.531 24.812C254.755 24.812 255.931 24.488 257.059 23.84C257.899 23.336 258.487 22.82 258.823 22.292Z" fill="#1584D8"/>
                </svg>
            </div>
              <?php $support_url = defined('REGMAGIC_ADDON') ? 'https://registrationmagic.com/help-support/' : 'https://wordpress.org/plugins/custom-registration-form-builder-with-submission-manager/' ?>
            <div class="rm-floating-item rm-mt-3"><a href="<?php echo esc_url($support_url); ?>" target="_blank" class="rm-d-flex rm-align-items-center rm-text-decoration-none rm-fw-bold rm-text-dark rm-px-3 rm-py-2"><span class="material-icons rm-pr-2">support</span><?php esc_html_e("Create Support Ticket", 'custom-registration-form-builder-with-submission-manager'); ?></a></div>
            
            <div class="rm-floating-item"><a href="https://www.facebook.com/registrationmagic" target="_blank" class="rm-d-flex rm-align-items-center rm-text-decoration-none rm-fw-bold rm-text-dark rm-px-3 rm-py-2 ep-fb-icon"><span class="rm-pr-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="10px"><path d="M80 299.3V512H196V299.3h86.5l18-97.8H196V166.9c0-51.7 20.3-71.5 72.7-71.5c16.3 0 29.4 .4 37 1.2V7.9C291.4 4 256.4 0 236.2 0C129.3 0 80 50.5 80 159.4v42.1H14v97.8H80z"/></svg>
            </span><?php esc_html_e("Follow Us on Facebook", 'custom-registration-form-builder-with-submission-manager'); ?></a>
            </div>
            
            <?php if(!defined('REGMAGIC_ADDON')) { ?>
            <div class="rm-floating-item"><a href="https://registrationmagic.com/comparison/?utm_source=wp_admin&utm_medium=floating_button&utm_campaign=admin_upgrade_premium" target="_blank" class="rm-d-flex rm-align-items-center rm-text-decoration-none rm-fw-bold rm-text-dark rm-px-3 rm-py-2"><span class="material-icons rm-pr-2">workspace_premium</span><?php esc_html_e("Upgrade to Premium", 'custom-registration-form-builder-with-submission-manager'); ?></a></div>
            <?php }else{ ?>
              <div class="rm-floating-item"><a href="https://registrationmagic.com/customizations/?utm_source=wp_admin&utm_medium=floating_button&utm_campaign=admin_customizations" target="_blank" class="rm-d-flex rm-align-items-center rm-text-decoration-none rm-fw-bold rm-text-dark rm-px-3 rm-py-2"><span class="material-icons rm-pr-2">design_services</span><?php esc_html_e("Customize RegistrationMagic", 'custom-registration-form-builder-with-submission-manager'); ?></a></div>  
           <?php }
            if(defined('REGMAGIC_ADDON')) { ?>
              <div class="rm-align-right rm-px-2 rm-text-muted rm-text-decoration-underline rm-cursor"> <?php esc_html_e("Hide this", 'custom-registration-form-builder-with-submission-manager'); ?></div>
            <?php } ?>
        </div>
    </div>
    <div id="rm-floating-button" class="rm-floating-button rm-floating-flyout-head">
        <div class="rm-floating-flyout-label"><?php esc_html_e("See Quick Links", 'custom-registration-form-builder-with-submission-manager'); ?></div>
        <span><img src="<?php echo RM_IMG_URL . 'rm-logo-icon.svg' ?>"></span>
    </div>
</div>




<div class="wrap">
    
    <div class="rm-footer-promotion rm-w-100 rm-box-w-100 rm-d-flex rm-justify-content-center">
    <div class="rm-text-center"> <a href="https://wordpress.org/plugins/custom-registration-form-builder-with-submission-manager/" target="_blank" class="rm-text-decoration-none rm-di-flex rm-footer-notice-icon-wrap"> <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368" class="rm-mr-1"><path d="M0 0h24v24H0V0z" fill="none" /><path d="M15 4v7H5.17l-.59.59-.58.58V4h11m1-2H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1V3c0-.55-.45-1-1-1zm5 4h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1z"/></svg> <?php esc_html_e('Have a question?', 'custom-registration-form-builder-with-submission-manager'); ?> </a></div>
    </div>
    
<div class="rm-footer-notice rm-border-top rm-mt-4" style="float:left; opacity: 0; display:none">
    <div class="rm-footer-notice-info rm-text-center">
        <div class="rm-footer-notice-icon-wrap rm-di-flex rm-align-items-center"><a href="https://wordpress.org/plugins/custom-registration-form-builder-with-submission-manager/"  target="_blank"  class="rm-footer-notice-icon rm-mr-1 rm-d-flex rm-text-decoration-none"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368" class="rm-mr-1"><path d="M0 0h24v24H0V0z" fill="none" /><path d="M15 4v7H5.17l-.59.59-.58.58V4h11m1-2H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1V3c0-.55-.45-1-1-1zm5 4h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1z"/></svg> <?php esc_html_e('Have a question?', 'custom-registration-form-builder-with-submission-manager'); ?> </a></div>

        <div class="rm-footer-notice-pitch"> <?php esc_html_e("Reach out to the RegistrationMagic Community for help ", 'custom-registration-form-builder-with-submission-manager'); ?><a href="https://wordpress.org/plugins/custom-registration-form-builder-with-submission-manager/" target="_blank" class=""><?php esc_html_e("here", 'custom-registration-form-builder-with-submission-manager'); ?></a>.</div>
    </div>
</div>
    </div>

<?php } ?>
<style>
    
    .rm-footer-notice{
        width: 100% !important;
    }
    
    .rm-footer-notice a {
        vertical-align: bottom;
    }
    
    .rm-footer-notice-icon-wrap {
        font-weight: 600;
        color: #2371b1;
    }
    
    .rm-footer-notice-icon{
        
    }
    
    .rm-footer-notice-icon-wrap svg{
        fill: #2371b1;
    }
    
    .rm-footer-notice-info {
        max-width: 700px;
        margin: 30px auto;
        font-size: 14px;
    }

    
    .rmagic.rmagic-premium-banner {
        float: none;
        display: flex;
    }
    
   .rm-floating-flyout {
    position: fixed;
    z-index: 99999;
    transition: all 0.2s ease-in-out;
    right: 34px;
    bottom: 46px;
    opacity: 1;
}

.rm-floating-button span {
    width: 74px;
    height: 74px;
    display: block;
    border-radius: 50%;
    border: 1px solid #C9D3DF;
    overflow: hidden;
    box-shadow: 0 3px 20px rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease-in-out;
    background-color: #fff;
    text-align: center;
}

.rm-floating-button span img{
    width: 46px;
    margin: 10px auto;
}

.rm-floating-flyout-label {
    position: absolute;
    display: block;
    top: 50%;
    right: calc(100% + 25px);
    transform: translateY(-50%);
    -moz-transform: translateY(-50%);
    -webkit-transform: translateY(-50%);
    color: #fff;
    background: #000 0 0 no-repeat padding-box;
    font-size: 12px;
    white-space: nowrap;
    padding: 5px 10px;
    height: auto !important;
    line-height: initial;
    transition: all 0.2s ease-out;
    border-radius: 3px;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
}

.rm-floating-flyout-label {
    opacity: 0;
    transform: translateY(-50%) scale(0);
    margin-right: -50px;
}

.rm-floating-flyout-head:hover .rm-floating-flyout-label{
    opacity: 1;
    transform: translateY(-50%) scale(1);
    margin-right: 0;
}

.rm-floating-flyout .rm-floating-items {
    position: fixed;
    right: 25px;
    bottom: 116px;
    z-index: 1000;
}

.rm-floating-item-wrap{
     -webkit-transition: all .8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    transition: all .8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.rm-floating-item-wrap{
    width: 290px;
    height: 0px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0px 6px 22px rgba(0, 0, 0, .32);
    border: 1px solid #C9D3DF;
    text-align: center;
    margin: 0 0 10px 0;
    overflow: hidden;
    opacity: 0;
}

.rm-floating-item-wrap.rm-floating-items-open {
    height: 230px;
    opacity: 1;
}

.rm-floating-button.rm-floating-flyout-head{
    position: relative;
    z-index: 99999;
    cursor: pointer
}

.ep-fb-icon span{
    border: 2px solid #141313;
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: space-around;
    padding: 0px;
    border-radius: 50%;
    margin-right: 0.5rem;
}

.rm-premium-text{
   background: -webkit-linear-gradient(left, #2271B1 , #2271B1);
   background: -o-linear-gradient(right, #2271B1, #2271B1);
   background: -moz-linear-gradient(right, #2271B1, #2271B1);
   background: linear-gradient(to right, #2271B1 , #2271B1); 
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.rm-floating-item a{
    outline: 0;
    box-shadow: none;
}

.rm-floating-item a:hover{
    color: #2271b1 !important;
}


.rm-floating-flyout-overlay{
    background-color: rgb(0 0 0 / 0%);
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0px;
    left: 0px;
}

.rm-floating-flyout.rm-floating-flyout-open .rm-floating-flyout-overlay{
    display: block !important;
}


.registrationmagic_page_rm_dashboard_widget_dashboard .rmagic.rmagic-premium-banner,
.admin_page_rm_form_sett_manage .rmagic.rmagic-premium-banner,
.registrationmagic_page_rm_submission_manage .rmagic.rmagic-premium-banner,
.admin_page_rm_licensing .rmagic.rmagic-premium-banner,
.registrationmagic_page_rm_user_manage .rmagic.rmagic-premium-banner,
.registrationmagic_page_rm_support_premium_page .rmagic.rmagic-premium-banner{
    margin: 0px auto;
}

.registrationmagic_page_rm_dashboard_widget_dashboard .rmagic.rm-footer-notice,
.admin_page_rm_form_sett_manage .rmagic.rm-footer-notice,
.registrationmagic_page_rm_submission_manage .rmagic.rm-footer-notice,
.toplevel_page_rm_form_manage .rmagic.rm-footer-notice,
.admin_page_rm_licensing .rmagic.rm-footer-notice,
.registrationmagic_page_rm_user_manage .rmagic.rm-footer-notice,
.registrationmagic_page_rm_support_premium_page .rmagic.rm-footer-notice{
    float: none !important;
    display: flex;
    margin: 0px auto;
    max-width: 100%;
}

.rm-banner-fade-in{
    -webkit-animation: rm-fade-in-ban 0.7s cubic-bezier(0.39, 0.575, 0.565, 1) both;
    animation: rm-fade-in-ban 0.7s cubic-bezier(0.39, 0.575, 0.565, 1) both;
}
@keyframes rm-fade-in-ban{
    0% {
        -webkit-transform: translateZ(80px);
        transform: translateZ(80px);
        opacity: 0;
    }
    100% {
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
        opacity: 1;
    }
}


</style>
<pre class="rm-pre-wrapper-for-script-tags">
    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery("#rm-floating-button,.rm-floating-flyout-overlay").click(function(){
                jQuery('.rm-floating-item-wrap').toggleClass("rm-floating-items-open");
                jQuery('.rm-floating-flyout').toggleClass("rm-floating-flyout-open");
            });

            jQuery("div.rm-cursor").click(function(){
                var dismiss_data = {
                    'action': 'rm_dismiss_floating_banner',
                    'rm_sec_nonce': '<?php echo wp_create_nonce('rm_ajax_secure'); ?>'
                };
                
                jQuery.post(ajaxurl, dismiss_data, function(response) {
                    jQuery('div.rm-floating-flyout').hide();
                });
            });
        });
        
            
    document.addEventListener('DOMContentLoaded', function() {
    var rmPremiumBanner = document.querySelector('.rmagic-premium-banner');
    var rmFooterSupportLink = document.querySelector('.rm-footer-notice');
    if (rmPremiumBanner) {
        //element.style.opacity = '0'; 
        rmPremiumBanner.classList.add('rm-banner-fade-in');
    }
    
    if (rmFooterSupportLink){
        rmFooterSupportLink.classList.add('rm-banner-fade-in');
    }
});
        
        
    </script>
</pre>