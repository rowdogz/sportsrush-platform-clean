<?php
if (!defined('ABSPATH')) exit;
?>
<div class="rw-dashboard rw-tenant-dashboard">
    <h2><?php printf(__('Welcome, %s', 'rent-wallet-platform'), esc_html($user->display_name)); ?></h2>
    
    <div class="rw-dashboard-grid">
        <!-- Wallet Balance Card -->
        <div class="rw-card rw-wallet-card">
            <h3><?php _e('Wallet Balance', 'rent-wallet-platform'); ?></h3>
            <div class="rw-balance"><?php echo esc_html(RW_Wallet::format_pennies($wallet->balance_pennies)); ?></div>
            <p class="rw-currency"><?php echo esc_html($wallet->currency); ?></p>
        </div>
        
        <!-- Coverage & Tier Card -->
        <div class="rw-card rw-tier-card">
            <h3><?php _e('Reward Tier', 'rent-wallet-platform'); ?></h3>
            <?php if ($tier_progress['current_tier']): ?>
                <div class="rw-tier-badge rw-tier-<?php echo esc_attr($tier_progress['current_tier']->tier_key); ?>">
                    <?php echo esc_html($tier_progress['current_tier']->display_name); ?>
                </div>
                <p class="rw-cashback-rate">
                    <?php printf(__('%s%% cashback on rent', 'rent-wallet-platform'), esc_html(RW_Reward_Tiers::get_cashback_percentage($tier_progress['current_tier']))); ?>
                </p>
            <?php endif; ?>
            
            <div class="rw-coverage">
                <span class="rw-coverage-label"><?php _e('Coverage:', 'rent-wallet-platform'); ?></span>
                <span class="rw-coverage-value"><?php echo esc_html(number_format($tier_progress['coverage_months'], 1)); ?> <?php _e('months', 'rent-wallet-platform'); ?></span>
            </div>
            
            <?php if ($tier_progress['next_tier']): ?>
                <div class="rw-tier-progress">
                    <div class="rw-progress-bar">
                        <div class="rw-progress-fill" style="width: <?php echo esc_attr($tier_progress['progress_percentage']); ?>%"></div>
                    </div>
                    <p class="rw-next-tier">
                        <?php printf(
                            __('%s more months to %s tier', 'rent-wallet-platform'),
                            esc_html(number_format($tier_progress['months_to_next'], 1)),
                            esc_html($tier_progress['next_tier']->display_name)
                        ); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tenancy Info Card -->
        <?php if ($tenancy): 
            $property = RW_Property::get($tenancy->property_id);
        ?>
        <div class="rw-card rw-tenancy-card">
            <h3><?php _e('Current Tenancy', 'rent-wallet-platform'); ?></h3>
            <p class="rw-property-address"><?php echo esc_html(RW_Property::get_full_address($property)); ?></p>
            <div class="rw-tenancy-details">
                <div class="rw-detail">
                    <span class="rw-label"><?php _e('Monthly Rent:', 'rent-wallet-platform'); ?></span>
                    <span class="rw-value"><?php echo esc_html(RW_Wallet::format_pennies($tenancy->rent_amount_pennies)); ?></span>
                </div>
                <div class="rw-detail">
                    <span class="rw-label"><?php _e('Due Day:', 'rent-wallet-platform'); ?></span>
                    <span class="rw-value"><?php echo esc_html($tenancy->due_day); ?><?php _e('th of each month', 'rent-wallet-platform'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Cashback Earned Card -->
        <div class="rw-card rw-cashback-card">
            <h3><?php _e('Total Cashback Earned', 'rent-wallet-platform'); ?></h3>
            <div class="rw-cashback-total"><?php echo esc_html(RW_Wallet::format_pennies($total_cashback)); ?></div>
        </div>
    </div>
    
    <!-- Request Top-up -->
    <div class="rw-card rw-topup-card">
        <h3><?php _e('Request Top-up', 'rent-wallet-platform'); ?></h3>
        <p><?php _e('Request a wallet top-up from the administrator.', 'rent-wallet-platform'); ?></p>
        <form id="rw-topup-form" class="rw-form">
            <div class="rw-form-row">
                <label for="topup-amount"><?php _e('Amount (GBP)', 'rent-wallet-platform'); ?></label>
                <input type="number" id="topup-amount" name="amount" step="0.01" min="1" required>
            </div>
            <div class="rw-form-row">
                <label for="topup-notes"><?php _e('Notes (optional)', 'rent-wallet-platform'); ?></label>
                <textarea id="topup-notes" name="notes" rows="2"></textarea>
            </div>
            <button type="submit" class="rw-button"><?php _e('Request Top-up', 'rent-wallet-platform'); ?></button>
        </form>
        <div id="rw-topup-message" class="rw-message" style="display:none;"></div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="rw-card rw-transactions-card">
        <h3><?php _e('Recent Transactions', 'rent-wallet-platform'); ?></h3>
        <?php if (!empty($transactions)): ?>
        <table class="rw-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Type', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Amount', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Balance', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td><?php echo esc_html(date('d M Y H:i', strtotime($tx->created_at))); ?></td>
                    <td><?php echo esc_html(RW_Ledger::get_type_label($tx->type)); ?></td>
                    <td class="<?php echo $tx->amount_pennies >= 0 ? 'rw-positive' : 'rw-negative'; ?>">
                        <?php echo $tx->amount_pennies >= 0 ? '+' : ''; ?><?php echo esc_html(RW_Wallet::format_pennies($tx->amount_pennies)); ?>
                    </td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($tx->running_balance_pennies)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No transactions yet.', 'rent-wallet-platform'); ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#rw-topup-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#rw-topup-message');
        var $button = $form.find('button');
        
        $button.prop('disabled', true).text('<?php esc_attr_e('Sending...', 'rent-wallet-platform'); ?>');
        
        $.ajax({
            url: '<?php echo esc_url(rest_url('rent-wallet/v1/tenant/request-topup')); ?>',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            data: {
                amount: $('#topup-amount').val(),
                notes: $('#topup-notes').val()
            },
            success: function(response) {
                $message.removeClass('rw-error').addClass('rw-success').text(response.message).show();
                $form[0].reset();
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '<?php esc_attr_e('An error occurred', 'rent-wallet-platform'); ?>';
                $message.removeClass('rw-success').addClass('rw-error').text(msg).show();
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_attr_e('Request Top-up', 'rent-wallet-platform'); ?>');
            }
        });
    });
});
</script>
