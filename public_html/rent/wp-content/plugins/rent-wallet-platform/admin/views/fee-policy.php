<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Fee Policy', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <form method="post" action="" class="rw-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="update_fee_policy">
            
            <table class="form-table">
                <tr>
                    <th><label for="tenant_fee_percentage"><?php _e('Tenant Fee (%)', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <input type="number" name="tenant_fee_percentage" id="tenant_fee_percentage" step="0.01" min="0" value="<?php echo esc_attr(RW_Fee_Policy::bps_to_percentage($policy->tenant_fee_bps)); ?>">
                        <p class="description"><?php _e('Percentage of rent charged to tenant as service fee', 'rent-wallet-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="landlord_fee_percentage"><?php _e('Landlord Fee (%)', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <input type="number" name="landlord_fee_percentage" id="landlord_fee_percentage" step="0.01" min="0" value="<?php echo esc_attr(RW_Fee_Policy::bps_to_percentage($policy->landlord_fee_bps)); ?>">
                        <p class="description"><?php _e('Percentage of rent deducted from landlord payout as service fee', 'rent-wallet-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="vat_enabled"><?php _e('VAT Enabled', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="vat_enabled" id="vat_enabled" value="1" <?php checked($policy->vat_enabled, 1); ?>>
                            <?php _e('Apply VAT to service fees', 'rent-wallet-platform'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="vat_percentage"><?php _e('VAT Rate (%)', 'rent-wallet-platform'); ?></label></th>
                    <td>
                        <input type="number" name="vat_percentage" id="vat_percentage" step="0.01" min="0" value="<?php echo esc_attr(RW_Fee_Policy::bps_to_percentage($policy->vat_bps)); ?>">
                        <p class="description"><?php _e('VAT percentage applied to fees (UK standard is 20%)', 'rent-wallet-platform'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Update Fee Policy', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
</div>
