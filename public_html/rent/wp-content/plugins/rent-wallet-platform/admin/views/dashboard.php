<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Rent Wallet Dashboard', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-kpi-grid">
        <div class="rw-kpi-card">
            <h3><?php _e('Total Tenants', 'rent-wallet-platform'); ?></h3>
            <div class="rw-kpi-value"><?php echo esc_html($total_tenants); ?></div>
        </div>
        
        <div class="rw-kpi-card">
            <h3><?php _e('Total Landlords', 'rent-wallet-platform'); ?></h3>
            <div class="rw-kpi-value"><?php echo esc_html($total_landlords); ?></div>
        </div>
        
        <div class="rw-kpi-card">
            <h3><?php _e('Properties', 'rent-wallet-platform'); ?></h3>
            <div class="rw-kpi-value"><?php echo esc_html($total_properties); ?></div>
        </div>
        
        <div class="rw-kpi-card">
            <h3><?php _e('Active Tenancies', 'rent-wallet-platform'); ?></h3>
            <div class="rw-kpi-value"><?php echo esc_html($total_tenancies); ?></div>
        </div>
        
        <div class="rw-kpi-card">
            <h3><?php _e('Total Wallet Balance', 'rent-wallet-platform'); ?></h3>
            <div class="rw-kpi-value"><?php echo esc_html(RW_Wallet::format_pennies($total_wallet_balance)); ?></div>
        </div>
        
        <div class="rw-kpi-card">
            <h3><?php _e('Total Payouts', 'rent-wallet-platform'); ?></h3>
            <div class="rw-kpi-value"><?php echo esc_html(RW_Wallet::format_pennies($total_payouts)); ?></div>
        </div>
        
        <div class="rw-kpi-card <?php echo $arrears_count > 0 ? 'rw-kpi-warning' : ''; ?>">
            <h3><?php _e('Tenancies in Arrears', 'rent-wallet-platform'); ?></h3>
            <div class="rw-kpi-value"><?php echo esc_html($arrears_count); ?></div>
        </div>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('Quick Actions', 'rent-wallet-platform'); ?></h2>
        <div class="rw-quick-actions">
            <a href="<?php echo admin_url('admin.php?page=rw-wallets'); ?>" class="button button-primary"><?php _e('Credit Wallet', 'rent-wallet-platform'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=rw-tenancies'); ?>" class="button"><?php _e('Manage Tenancies', 'rent-wallet-platform'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=rw-integrity&verify=1'); ?>" class="button"><?php _e('Verify Integrity', 'rent-wallet-platform'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=rw-demo-data'); ?>" class="button"><?php _e('Generate Demo Data', 'rent-wallet-platform'); ?></a>
        </div>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('Manual Rent Release', 'rent-wallet-platform'); ?></h2>
        <p><?php _e('Run the rent release process manually for testing. This will process all active tenancies due today.', 'rent-wallet-platform'); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="run_rent_release">
            <button type="submit" class="button button-secondary"><?php _e('Run Rent Release Now', 'rent-wallet-platform'); ?></button>
        </form>
    </div>
</div>
