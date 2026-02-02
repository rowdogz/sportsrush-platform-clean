<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('GDPR Tools', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <h2><?php _e('Export User Data', 'rent-wallet-platform'); ?></h2>
        <p><?php _e('Export all data associated with a user in JSON format for GDPR compliance.', 'rent-wallet-platform'); ?></p>
        
        <form method="post" action="" class="rw-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="gdpr_export">
            
            <table class="form-table">
                <tr>
                    <th><label for="export_user_id"><?php _e('Select User', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="user_id" id="export_user_id" required>
                            <option value=""><?php _e('Select User', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ') - ' . implode(', ', $user->roles)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Export User Data', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('Anonymise User', 'rent-wallet-platform'); ?></h2>
        <p class="description" style="color: #d63638;">
            <?php _e('WARNING: This action is irreversible. User PII will be replaced with anonymous identifiers. Financial records will be retained for compliance but user identity will be anonymised.', 'rent-wallet-platform'); ?>
        </p>
        
        <form method="post" action="" class="rw-form" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to anonymise this user? This action cannot be undone.', 'rent-wallet-platform'); ?>');">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="gdpr_anonymise">
            
            <table class="form-table">
                <tr>
                    <th><label for="anonymise_user_id"><?php _e('Select User', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <select name="user_id" id="anonymise_user_id" required>
                            <option value=""><?php _e('Select User', 'rent-wallet-platform'); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-secondary"><?php _e('Anonymise User', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
</div>
