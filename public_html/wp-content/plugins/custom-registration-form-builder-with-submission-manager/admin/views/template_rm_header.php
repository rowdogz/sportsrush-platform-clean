<?php if(defined('RM_ADDON_PLUGIN_VERSION') && version_compare(RM_ADDON_PLUGIN_VERSION, RM_PLUGIN_VERSION, '<')) { ?>
<div class="notice notice-warning rm-upgrade-issue-notice" style="position: relative;">
    <p>
        <strong><?php esc_html_e('You are using an older version of RegistrationMagic Premium', 'custom-registration-form-builder-with-submission-manager'); ?></strong><br/>
        <?php echo sprintf(wp_kses_post(__('To keep Premium up-to-date automatically, make sure you have a valid license key entered <a href="%s" target="blank">here</a>. You can also manually download and install the latest version from <a href="%s" target="blank"> here</a>.', 'custom-registration-form-builder-with-submission-manager')), "admin.php?page=rm_licensing", "https://registrationmagic.com/checkout/order-history/"); ?>
    </p>
</div>
<?php }
// Set timezone to New York
$timezone = new DateTimeZone('America/New_York');

// Get current time
$now = new DateTime('now', $timezone);

// Define target time: July 31, 2025 at 11:59 PM
$expiry = new DateTime('2025-07-31 23:59:00', $timezone);

if($now <= $expiry) {
    $expired = false;
} else {
    $expired = true;
}

if(!defined('REGMAGIC_ADDON')) {
if(!$expired) {
if(!isset($_SESSION['rm_dismiss_sale_banner']) || empty($_SESSION['rm_dismiss_sale_banner'])) { ?>
<!-- Sales banner ---->
<div class="rm-upgrade-notice-info rm-admin-sale-banner is-dismissible rm-text-dark rm-border-bottom" id="rm-sale-banner-20250731">
    <div class="rm-sale-banner-content">
        <div class="rm-sale-banner-text">
            <strong>Save 30% on RegistrationMagic Premium – Limited Time!</strong> Use code<span class="rm-sale-banner-code">RMJULY30</span> at checkout. Offer valid until July 31.
        </div>
        <a href="https://registrationmagic.com/comparison/?utm_source=plugin&utm_medium=banner&utm_campaign=rmjuly30" class="rm-sale-banner-btn button button-primary" target="_blank">
            Upgrade Now
        </a>
    </div>
    <button class="rm-sale-banner-close" aria-label="Close">&times;</button>
</div>
<?php } } elseif (empty(get_site_option('rm_dismiss_upgrade_notice', false))) { ?>
<div class="rm-upgrade-notice-info is-dismissible rm-text-dark rm-border-bottom">
    <?php esc_html_e('Unlock even more powerful features by upgrading to RegistrationMagic ', 'custom-registration-form-builder-with-submission-manager'); ?>
    <!--
    <a href="https://registrationmagic.com/comparison/?utm_source=wp_admin&utm_medium=top_alert&utm_campaign=admin_upgrade_premium" target="_blank" class="rm-premium-text">
    -->
    <a href="admin.php?page=rm_support_premium_page" class="rm-premium-text">
        <?php esc_html_e('Premium', 'custom-registration-form-builder-with-submission-manager'); ?>
    </a>
    <button class="button-link rm-promo-notice-dismiss rm-bg-light rm-text-dark rm-rounded-circle material-icons">close <span class="screen-reader-text">
        <?php esc_html_e('Dismiss notice', 'custom-registration-form-builder-with-submission-manager'); ?></span>
    </button>
</div>
<?php } ?>
<?php } ?>