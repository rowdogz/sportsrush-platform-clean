<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Transaction Ledger', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Wallet', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Type', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Amount', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Running Balance', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Tenancy', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Hash', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Created', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): 
                    $wallet = RW_Wallet::get_wallet($tx->wallet_id);
                    $owner = $wallet && $wallet->owner_user_id ? get_userdata($wallet->owner_user_id) : null;
                ?>
                <tr>
                    <td><?php echo esc_html($tx->id); ?></td>
                    <td>
                        <?php 
                        if ($wallet) {
                            echo esc_html($wallet->owner_role);
                            if ($owner) {
                                echo ' (' . esc_html($owner->display_name) . ')';
                            } elseif ($wallet->owner_role === 'platform') {
                                echo ' (Platform)';
                            }
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html(RW_Ledger::get_type_label($tx->type)); ?></td>
                    <td class="<?php echo $tx->amount_pennies >= 0 ? 'rw-positive' : 'rw-negative'; ?>">
                        <?php echo esc_html(RW_Wallet::format_pennies($tx->amount_pennies)); ?>
                    </td>
                    <td><?php echo esc_html(RW_Wallet::format_pennies($tx->running_balance_pennies)); ?></td>
                    <td><?php echo $tx->tenancy_id ? esc_html($tx->tenancy_id) : '-'; ?></td>
                    <td><code title="<?php echo esc_attr($tx->hash); ?>"><?php echo esc_html(substr($tx->hash, 0, 12) . '...'); ?></code></td>
                    <td><?php echo esc_html($tx->created_at); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
