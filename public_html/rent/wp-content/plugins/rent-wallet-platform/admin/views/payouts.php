<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Payouts', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Landlord', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Tenancy', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Gross Rent', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Landlord Fee', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('VAT', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Cashback Share', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Net Paid Out', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Created', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payouts as $payout): 
                    $landlord = get_userdata($payout->landlord_user_id);
                ?>
                <tr>
                    <td><?php echo esc_html($payout->id); ?></td>
                    <td><?php echo $landlord ? esc_html($landlord->display_name) : '-'; ?></td>
                    <td><?php echo esc_html($payout->tenancy_id); ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($payout->gross_rent_pennies)); ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($payout->landlord_fee_pennies)); ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($payout->landlord_vat_pennies)); ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($payout->landlord_cashback_share_pennies)); ?></td>
                    <td><strong><?php echo esc_html(RW_Wallet::format_pennies($payout->net_paid_out_pennies)); ?></strong></td>
                    <td><span class="rw-status rw-status-<?php echo esc_attr($payout->status); ?>"><?php echo esc_html($payout->status); ?></span></td>
                    <td><?php echo esc_html($payout->created_at); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
