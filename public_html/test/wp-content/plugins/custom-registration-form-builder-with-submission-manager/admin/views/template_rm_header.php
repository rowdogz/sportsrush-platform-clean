<?php if(defined('RM_ADDON_PLUGIN_VERSION') && version_compare(RM_ADDON_PLUGIN_VERSION, RM_PLUGIN_VERSION, '<')) { ?>
<div class="notice notice-warning rm-upgrade-issue-notice" style="position: relative;">
    <p>
        <strong><?php esc_html_e('You are using an older version of RegistrationMagic Premium', 'custom-registration-form-builder-with-submission-manager'); ?></strong><br/>
        <?php echo sprintf(wp_kses_post(__('To keep Premium up-to-date automatically, make sure you have a valid license key entered <a href="%s" target="blank">here</a>. You can also manually download and install the latest version from <a href="%s" target="blank"> here</a>.', 'custom-registration-form-builder-with-submission-manager')), "admin.php?page=rm_licensing", "https://registrationmagic.com/checkout/order-history/"); ?>
    </p>
</div>
<?php }
if(!defined('REGMAGIC_ADDON') && empty(get_site_option('rm_dismiss_upgrade_notice', false))) { ?>
<div class="rm-upgrade-notice-info is-dismissible rm-text-dark rm-border-bottom">
    <?php esc_html_e('Unlock even more powerful features by upgrading to RegistrationMagic ', 'custom-registration-form-builder-with-submission-manager'); ?>
    <a href="https://registrationmagic.com/comparison/?utm_source=wp_admin&utm_medium=top_alert&utm_campaign=admin_upgrade_premium" target="_blank" class="rm-premium-text">
        <?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?>
    </a>
    <button class="button-link rm-promo-notice-dismiss rm-bg-light rm-text-dark rm-rounded-circle material-icons">close <span class="screen-reader-text">
        <?php esc_html_e('Dismiss notice', 'custom-registration-form-builder-with-submission-manager'); ?></span>
    </button>
</div>
<?php } ?>