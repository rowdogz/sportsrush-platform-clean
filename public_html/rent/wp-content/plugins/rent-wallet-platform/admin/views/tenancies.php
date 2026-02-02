<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Tenancies', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <h2><?php _e('Create Tenancy', 'rent-wallet-platform'); ?></h2>
        <form method="post" action="" class="rw-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="create_tenancy">
            
            <table class="form-table">
                <tr>
                    <th><label for="property_id"><?php _e('Property', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="property_id" id="property_id" required>
                            <option value=""><?php _e('Select Property', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?php echo esc_attr($property->id); ?>" data-landlord="<?php echo esc_attr($property->landlord_user_id); ?>">
                                    <?php echo esc_html(RW_Property::get_full_address($property)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="tenant_user_id"><?php _e('Tenant', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="tenant_user_id" id="tenant_user_id" required>
                            <option value=""><?php _e('Select Tenant', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($tenants as $tenant): ?>
                                <option value="<?php echo esc_attr($tenant->ID); ?>"><?php echo esc_html($tenant->display_name . ' (' . $tenant->user_email . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="landlord_user_id"><?php _e('Landlord', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="landlord_user_id" id="landlord_user_id" required>
                            <option value=""><?php _e('Select Landlord', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($landlords as $landlord): ?>
                                <option value="<?php echo esc_attr($landlord->ID); ?>"><?php echo esc_html($landlord->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="rent_amount"><?php _e('Monthly Rent (GBP)', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="number" name="rent_amount" id="rent_amount" step="0.01" min="0.01" required></td>
                </tr>
                <tr>
                    <th><label for="due_day"><?php _e('Due Day (1-28)', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="number" name="due_day" id="due_day" min="1" max="28" required></td>
                </tr>
                <tr>
                    <th><label for="start_date"><?php _e('Start Date', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="date" name="start_date" id="start_date" required></td>
                </tr>
                <tr>
                    <th><label for="end_date"><?php _e('End Date (Optional)', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="date" name="end_date" id="end_date"></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Create Tenancy', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('All Tenancies', 'rent-wallet-platform'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Property', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Tenant', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Landlord', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Rent', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Due Day', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Actions', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenancies as $tenancy): 
                    $property = RW_Property::get($tenancy->property_id);
                    $tenant = get_userdata($tenancy->tenant_user_id);
                    $landlord = get_userdata($tenancy->landlord_user_id);
                ?>
                <tr>
                    <td><?php echo esc_html($tenancy->id); ?></td>
                    <td><?php echo $property ? esc_html(RW_Property::get_full_address($property)) : '-'; ?></td>
                    <td><?php echo $tenant ? esc_html($tenant->display_name) : '-'; ?></td>
                    <td><?php echo $landlord ? esc_html($landlord->display_name) : '-'; ?></td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($tenancy->rent_amount_pennies)); ?></td>
                    <td><?php echo esc_html($tenancy->due_day); ?></td>
                    <td><span class="rw-status rw-status-<?php echo esc_attr($tenancy->status); ?>"><?php echo esc_html(ucfirst($tenancy->status)); ?></span></td>
                    <td>
                        <?php if ($tenancy->status === 'active'): ?>
                        <form method="post" action="" style="display:inline;">
                            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
                            <input type="hidden" name="rw_admin_action" value="run_rent_release">
                            <input type="hidden" name="tenancy_id" value="<?php echo esc_attr($tenancy->id); ?>">
                            <button type="submit" class="button button-small"><?php _e('Process Rent', 'rent-wallet-platform'); ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
