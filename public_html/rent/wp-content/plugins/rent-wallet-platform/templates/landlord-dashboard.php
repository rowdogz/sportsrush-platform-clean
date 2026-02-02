<?php
if (!defined('ABSPATH')) exit;
?>
<div class="rw-dashboard rw-landlord-dashboard">
    <h2><?php printf(__('Welcome, %s', 'rent-wallet-platform'), esc_html($user->display_name)); ?></h2>
    
    <div class="rw-dashboard-grid">
        <!-- Summary Cards -->
        <div class="rw-card rw-summary-card">
            <h3><?php _e('Properties', 'rent-wallet-platform'); ?></h3>
            <div class="rw-stat"><?php echo esc_html(count($properties)); ?></div>
        </div>
        
        <div class="rw-card rw-summary-card">
            <h3><?php _e('Active Tenancies', 'rent-wallet-platform'); ?></h3>
            <div class="rw-stat"><?php echo esc_html(count($tenancies)); ?></div>
        </div>
        
        <div class="rw-card rw-summary-card">
            <h3><?php _e('Total Payouts', 'rent-wallet-platform'); ?></h3>
            <div class="rw-stat"><?php echo esc_html(RW_Wallet::format_pennies($total_payouts)); ?></div>
        </div>
        
        <div class="rw-card rw-summary-card">
            <h3><?php _e('Total Fees Paid', 'rent-wallet-platform'); ?></h3>
            <div class="rw-stat"><?php echo esc_html(RW_Wallet::format_pennies($total_fees)); ?></div>
        </div>
    </div>
    
    <!-- Properties List -->
    <div class="rw-card rw-properties-card">
        <h3><?php _e('Your Properties', 'rent-wallet-platform'); ?></h3>
        <?php if (!empty($properties)): ?>
        <table class="rw-table">
            <thead>
                <tr>
                    <th><?php _e('Address', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Type', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Bedrooms', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($properties as $property): ?>
                <tr>
                    <td><?php echo esc_html(RW_Property::get_full_address($property)); ?></td>
                    <td><?php echo esc_html(ucfirst($property->property_type)); ?></td>
                    <td><?php echo esc_html($property->bedrooms ?: '-'); ?></td>
                    <td><span class="rw-status rw-status-<?php echo esc_attr($property->status); ?>"><?php echo esc_html(ucfirst($property->status)); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No properties yet.', 'rent-wallet-platform'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Tenancies List -->
    <div class="rw-card rw-tenancies-card">
        <h3><?php _e('Active Tenancies', 'rent-wallet-platform'); ?></h3>
        <?php if (!empty($tenancies)): ?>
        <table class="rw-table">
            <thead>
                <tr>
                    <th><?php _e('Property', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Tenant', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Rent', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Due Day', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenancies as $tenancy): 
                    $property = RW_Property::get($tenancy->property_id);
                    $tenant = get_userdata($tenancy->tenant_user_id);
                ?>
                <tr>
                    <td><?php echo $property ? esc_html(RW_Property::get_full_address($property)) : '-'; ?></td>
                    <td><?php echo $tenant ? esc_html($tenant->display_name) : '-'; ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($tenancy->rent_amount_pennies)); ?></td>
                    <td><?php echo esc_html($tenancy->due_day); ?></td>
                    <td><span class="rw-status rw-status-<?php echo esc_attr($tenancy->status); ?>"><?php echo esc_html(ucfirst($tenancy->status)); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No active tenancies.', 'rent-wallet-platform'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Payouts History -->
    <div class="rw-card rw-payouts-card">
        <h3><?php _e('Recent Payouts', 'rent-wallet-platform'); ?></h3>
        <?php if (!empty($payouts)): ?>
        <table class="rw-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Gross Rent', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Fee', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Cashback Share', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Net Payout', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payouts as $payout): ?>
                <tr>
                    <td><?php echo esc_html(date('d M Y', strtotime($payout->created_at))); ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($payout->gross_rent_pennies)); ?></td>
                    <td class="rw-negative">-<?php echo esc_html(RW_Wallet::format_pennies($payout->landlord_fee_pennies + $payout->landlord_vat_pennies)); ?></td>
                    <td class="rw-negative">-<?php echo esc_html(RW_Wallet::format_pennies($payout->landlord_cashback_share_pennies)); ?></td>
                    <td><strong><?php echo esc_html(RW_Wallet::format_pennies($payout->net_paid_out_pennies)); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p class="rw-export-link">
            <a href="<?php echo esc_url(rest_url('rent-wallet/v1/landlord/export/payouts')); ?>?_wpnonce=<?php echo wp_create_nonce('wp_rest'); ?>" class="rw-button rw-button-secondary">
                <?php _e('Export Payouts CSV', 'rent-wallet-platform'); ?>
            </a>
        </p>
        <?php else: ?>
        <p><?php _e('No payouts yet.', 'rent-wallet-platform'); ?></p>
        <?php endif; ?>
    </div>
</div>
