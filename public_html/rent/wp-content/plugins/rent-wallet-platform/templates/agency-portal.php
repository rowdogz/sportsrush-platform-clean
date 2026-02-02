<?php
if (!defined('ABSPATH')) exit;
?>
<div class="rw-dashboard rw-agency-portal">
    <h2><?php printf(__('Agency Portal - %s', 'rent-wallet-platform'), esc_html($agency->name)); ?></h2>
    
    <div class="rw-dashboard-grid">
        <!-- Summary Cards -->
        <div class="rw-card rw-summary-card">
            <h3><?php _e('Assigned Landlords', 'rent-wallet-platform'); ?></h3>
            <div class="rw-stat"><?php echo esc_html(count($landlords)); ?></div>
        </div>
        
        <div class="rw-card rw-summary-card">
            <h3><?php _e('Properties', 'rent-wallet-platform'); ?></h3>
            <div class="rw-stat"><?php echo esc_html(count($properties)); ?></div>
        </div>
        
        <div class="rw-card rw-summary-card">
            <h3><?php _e('Active Tenancies', 'rent-wallet-platform'); ?></h3>
            <div class="rw-stat"><?php echo esc_html(count($tenancies)); ?></div>
        </div>
        
        <div class="rw-card rw-summary-card rw-<?php echo $arrears_count > 0 ? 'warning' : 'success'; ?>">
            <h3><?php _e('In Arrears', 'rent-wallet-platform'); ?></h3>
            <div class="rw-stat"><?php echo esc_html($arrears_count); ?></div>
        </div>
    </div>
    
    <!-- Assigned Landlords -->
    <div class="rw-card">
        <h3><?php _e('Assigned Landlords', 'rent-wallet-platform'); ?></h3>
        <?php if (!empty($landlords)): ?>
        <table class="rw-table">
            <thead>
                <tr>
                    <th><?php _e('Name', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Email', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Properties', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($landlords as $landlord): 
                    $landlord_properties = RW_Property::get_by_landlord($landlord->ID);
                ?>
                <tr>
                    <td><?php echo esc_html($landlord->display_name); ?></td>
                    <td><?php echo esc_html($landlord->user_email); ?></td>
                    <td><?php echo esc_html(count($landlord_properties)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No landlords assigned to this agency.', 'rent-wallet-platform'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Properties -->
    <div class="rw-card">
        <h3><?php _e('Properties', 'rent-wallet-platform'); ?></h3>
        <?php if (!empty($properties)): ?>
        <table class="rw-table">
            <thead>
                <tr>
                    <th><?php _e('Address', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Landlord', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Type', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($properties as $property): 
                    $landlord = get_userdata($property->landlord_user_id);
                ?>
                <tr>
                    <td><?php echo esc_html(RW_Property::get_full_address($property)); ?></td>
                    <td><?php echo $landlord ? esc_html($landlord->display_name) : '-'; ?></td>
                    <td><?php echo esc_html(ucfirst($property->property_type)); ?></td>
                    <td><span class="rw-status rw-status-<?php echo esc_attr($property->status); ?>"><?php echo esc_html(ucfirst($property->status)); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No properties found.', 'rent-wallet-platform'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Tenancies with Coverage/Tier Summary -->
    <div class="rw-card">
        <h3><?php _e('Tenancies Overview', 'rent-wallet-platform'); ?></h3>
        <?php if (!empty($tenancies)): ?>
        <table class="rw-table">
            <thead>
                <tr>
                    <th><?php _e('Property', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Tenant', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Rent', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Coverage', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Tier', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenancies as $tenancy): 
                    $property = RW_Property::get($tenancy->property_id);
                    $tenant = get_userdata($tenancy->tenant_user_id);
                    $wallet = RW_Wallet::get_or_create_wallet($tenancy->tenant_user_id, 'tenant');
                    $coverage = $tenancy->rent_amount_pennies > 0 ? $wallet->balance_pennies / $tenancy->rent_amount_pennies : 0;
                    $tier = RW_Reward_Tiers::get_tier_for_coverage($coverage);
                ?>
                <tr>
                    <td><?php echo $property ? esc_html(RW_Property::get_full_address($property)) : '-'; ?></td>
                    <td><?php echo $tenant ? esc_html($tenant->display_name) : '-'; ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($tenancy->rent_amount_pennies)); ?></td>
                    <td><?php echo esc_html(number_format($coverage, 1)); ?> <?php _e('months', 'rent-wallet-platform'); ?></td>
                    <td>
                        <?php if ($tier): ?>
                        <span class="rw-tier-badge rw-tier-<?php echo esc_attr($tier->tier_key); ?>"><?php echo esc_html($tier->display_name); ?></span>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td><span class="rw-status rw-status-<?php echo esc_attr($tenancy->status); ?>"><?php echo esc_html(ucfirst($tenancy->status)); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No tenancies found.', 'rent-wallet-platform'); ?></p>
        <?php endif; ?>
    </div>
</div>
