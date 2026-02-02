<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Properties', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <h2><?php _e('Create Property', 'rent-wallet-platform'); ?></h2>
        <form method="post" action="" class="rw-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="create_property">
            
            <table class="form-table">
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
                    <th><label for="agency_id"><?php _e('Agency (Optional)', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="agency_id" id="agency_id">
                            <option value=""><?php _e('No Agency', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($agencies as $agency): ?>
                                <option value="<?php echo esc_attr($agency->id); ?>"><?php echo esc_html($agency->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="address_line1"><?php _e('Address Line 1', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="text" name="address_line1" id="address_line1" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="address_line2"><?php _e('Address Line 2', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="text" name="address_line2" id="address_line2" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="city"><?php _e('City', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="text" name="city" id="city" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="postcode"><?php _e('Postcode', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="text" name="postcode" id="postcode" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="bedrooms"><?php _e('Bedrooms', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="number" name="bedrooms" id="bedrooms" min="0"></td>
                </tr>
                <tr>
                    <th><label for="property_type"><?php _e('Property Type', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="property_type" id="property_type">
                            <?php foreach ($property_types as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Create Property', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('All Properties', 'rent-wallet-platform'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Address', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Landlord', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Agency', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Type', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Bedrooms', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($properties as $property): 
                    $landlord = get_userdata($property->landlord_user_id);
                    $agency = $property->agency_id ? RW_Agency::get($property->agency_id) : null;
                ?>
                <tr>
                    <td><?php echo esc_html($property->id); ?></td>
                    <td><?php echo esc_html(RW_Property::get_full_address($property)); ?></td>
                    <td><?php echo $landlord ? esc_html($landlord->display_name) : '-'; ?></td>
                    <td><?php echo $agency ? esc_html($agency->name) : '-'; ?></td>
                    <td><?php echo esc_html($property->property_type); ?></td>
                    <td><?php echo esc_html($property->bedrooms); ?></td>
                    <td><?php echo esc_html($property->status); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
