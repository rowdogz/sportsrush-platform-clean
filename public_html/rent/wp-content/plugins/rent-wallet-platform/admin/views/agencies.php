<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Agencies', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <h2><?php _e('Create Agency', 'rent-wallet-platform'); ?></h2>
        <form method="post" action="" class="rw-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="create_agency">
            
            <table class="form-table">
                <tr>
                    <th><label for="agency_name"><?php _e('Agency Name', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="text" name="agency_name" id="agency_name" class="regular-text" required></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Create Agency', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('All Agencies', 'rent-wallet-platform'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Name', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Assigned Landlords', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Created', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Actions', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agencies as $agency): 
                    $assigned = RW_Agency::get_assigned_landlords($agency->id);
                ?>
                <tr>
                    <td><?php echo esc_html($agency->id); ?></td>
                    <td><?php echo esc_html($agency->name); ?></td>
                    <td><?php echo count($assigned); ?></td>
                    <td><?php echo esc_html($agency->created_at); ?></td>
                    <td>
                        <button type="button" class="button button-small rw-assign-landlord" data-agency-id="<?php echo esc_attr($agency->id); ?>"><?php _e('Assign Landlord', 'rent-wallet-platform'); ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('Assign Landlord to Agency', 'rent-wallet-platform'); ?></h2>
        <form method="post" action="" class="rw-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="assign_landlord">
            
            <table class="form-table">
                <tr>
                    <th><label for="agency_id"><?php _e('Agency', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="agency_id" id="agency_id" required>
                            <option value=""><?php _e('Select Agency', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($agencies as $agency): ?>
                                <option value="<?php echo esc_attr($agency->id); ?>"><?php echo esc_html($agency->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="landlord_id"><?php _e('Landlord', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="landlord_id" id="landlord_id" required>
                            <option value=""><?php _e('Select Landlord', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($landlords as $landlord): ?>
                                <option value="<?php echo esc_attr($landlord->ID); ?>"><?php echo esc_html($landlord->display_name . ' (' . $landlord->user_email . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Assign Landlord', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
</div>
