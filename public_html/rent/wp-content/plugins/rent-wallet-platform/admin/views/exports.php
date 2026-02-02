<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Exports', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <p><?php _e('Export data as CSV files with integrity manifest. Each export includes a manifest.json with row counts and last hash for verification.', 'rent-wallet-platform'); ?></p>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Export Type', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Description', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Action', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php _e('Tenancies', 'rent-wallet-platform'); ?></strong></td>
                    <td><?php _e('All tenancy records with property and user details', 'rent-wallet-platform'); ?></td>
                    <td><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=rw-exports&export=tenancies'), 'rw_export'); ?>" class="button"><?php _e('Download', 'rent-wallet-platform'); ?></a></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Ledger', 'rent-wallet-platform'); ?></strong></td>
                    <td><?php _e('Complete transaction ledger with hash chain', 'rent-wallet-platform'); ?></td>
                    <td><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=rw-exports&export=ledger'), 'rw_export'); ?>" class="button"><?php _e('Download', 'rent-wallet-platform'); ?></a></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Payouts', 'rent-wallet-platform'); ?></strong></td>
                    <td><?php _e('All landlord payout records', 'rent-wallet-platform'); ?></td>
                    <td><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=rw-exports&export=payouts'), 'rw_export'); ?>" class="button"><?php _e('Download', 'rent-wallet-platform'); ?></a></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Audit Log', 'rent-wallet-platform'); ?></strong></td>
                    <td><?php _e('Complete audit trail with hash chain', 'rent-wallet-platform'); ?></td>
                    <td><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=rw-exports&export=audit'), 'rw_export'); ?>" class="button"><?php _e('Download', 'rent-wallet-platform'); ?></a></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Properties', 'rent-wallet-platform'); ?></strong></td>
                    <td><?php _e('All property records', 'rent-wallet-platform'); ?></td>
                    <td><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=rw-exports&export=properties'), 'rw_export'); ?>" class="button"><?php _e('Download', 'rent-wallet-platform'); ?></a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
