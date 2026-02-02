<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Reward Tiers', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <p><?php _e('Configure cashback reward tiers based on coverage months (wallet balance / monthly rent).', 'rent-wallet-platform'); ?></p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Tier', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Display Name', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Min Coverage Months', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Cashback Rate', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Actions', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tiers as $tier): ?>
                <tr>
                    <td><strong><?php echo esc_html(ucfirst($tier->tier_key)); ?></strong></td>
                    <td><?php echo esc_html($tier->display_name); ?></td>
                    <td><?php echo esc_html($tier->min_buffer_months); ?> <?php _e('months', 'rent-wallet-platform'); ?></td>
                    <td><?php echo esc_html(RW_Fee_Policy::bps_to_percentage($tier->cashback_bps)); ?>%</td>
                    <td>
                        <button type="button" class="button button-small rw-edit-tier" 
                            data-tier-id="<?php echo esc_attr($tier->id); ?>"
                            data-display-name="<?php echo esc_attr($tier->display_name); ?>"
                            data-min-months="<?php echo esc_attr($tier->min_buffer_months); ?>"
                            data-cashback="<?php echo esc_attr(RW_Fee_Policy::bps_to_percentage($tier->cashback_bps)); ?>">
                            <?php _e('Edit', 'rent-wallet-platform'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('Edit Tier', 'rent-wallet-platform'); ?></h2>
        <form method="post" action="" class="rw-form" id="edit-tier-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="update_reward_tier">
            <input type="hidden" name="tier_id" id="edit_tier_id" value="">
            
            <table class="form-table">
                <tr>
                    <th><label for="display_name"><?php _e('Display Name', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="text" name="display_name" id="edit_display_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="min_buffer_months"><?php _e('Min Coverage Months', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="number" name="min_buffer_months" id="edit_min_buffer_months" step="0.01" min="0" required></td>
                </tr>
                <tr>
                    <th><label for="cashback_percentage"><?php _e('Cashback Rate (%)', 'rent-wallet-platform'); ?></label></th>
                    <td><input type="number" name="cashback_percentage" id="edit_cashback_percentage" step="0.01" min="0" required></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Update Tier', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.rw-edit-tier').on('click', function() {
        $('#edit_tier_id').val($(this).data('tier-id'));
        $('#edit_display_name').val($(this).data('display-name'));
        $('#edit_min_buffer_months').val($(this).data('min-months'));
        $('#edit_cashback_percentage').val($(this).data('cashback'));
        $('#edit-tier-form')[0].scrollIntoView({behavior: 'smooth'});
    });
});
</script>
