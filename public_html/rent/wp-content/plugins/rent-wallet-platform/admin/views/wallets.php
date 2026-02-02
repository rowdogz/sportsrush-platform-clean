<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Wallets', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <h2><?php _e('Manual Credit', 'rent-wallet-platform'); ?></h2>
        <form method="post" action="" class="rw-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="manual_credit">
            
            <table class="form-table">
                <tr>
                    <th><label for="tenant_id"><?php _e('Tenant', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="tenant_id" id="tenant_id" required>
                            <option value=""><?php _e('Select Tenant', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($tenants as $tenant): ?>
                                <option value="<?php echo esc_attr($tenant->ID); ?>">
                                    <?php echo esc_html($tenant->display_name . ' (' . $tenant->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="amount"><?php _e('Amount (GBP)', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="number" name="amount" id="amount" step="0.01" min="0.01" required></td>
                </tr>
                <tr>
                    <th><label for="notes"><?php _e('Notes', 'rent-wallet-platform'); ?></label></th>
                    <td><textarea name="notes" id="notes" rows="3" class="large-text"></textarea></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Credit Wallet', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('All Wallets', 'rent-wallet-platform'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Owner', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Role', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Balance', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Currency', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Updated', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wallets as $wallet): 
                    $owner = $wallet->owner_user_id ? get_userdata($wallet->owner_user_id) : null;
                ?>
                <tr>
                    <td><?php echo esc_html($wallet->id); ?></td>
                    <td><?php echo $owner ? esc_html($owner->display_name) : ($wallet->owner_role === 'platform' ? __('Platform', 'rent-wallet-platform') : '-'); ?></td>
                    <td><?php echo esc_html(ucfirst($wallet->owner_role)); ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($wallet->balance_pennies)); ?></td>
                    <td><?php echo esc_html($wallet->currency); ?></td>
                    <td><?php echo esc_html($wallet->updated_at); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
