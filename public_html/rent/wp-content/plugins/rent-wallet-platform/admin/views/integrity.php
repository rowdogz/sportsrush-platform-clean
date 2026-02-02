<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Verify Integrity', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <p><?php _e('This tool verifies the integrity of the transaction ledger and audit log hash chains using HMAC-SHA256.', 'rent-wallet-platform'); ?></p>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=rw-integrity&verify=1'); ?>" class="button button-primary">
                <?php _e('Run Integrity Check', 'rent-wallet-platform'); ?>
            </a>
        </p>
    </div>
    
    <?php if ($verification_result): ?>
    <div class="rw-admin-section">
        <h2><?php _e('Verification Results', 'rent-wallet-platform'); ?></h2>
        
        <div class="rw-verification-result rw-result-<?php echo esc_attr($verification_result['overall_status']); ?>">
            <h3>
                <?php if ($verification_result['overall_status'] === 'pass'): ?>
                    <span class="dashicons dashicons-yes-alt"></span> <?php _e('All Checks Passed', 'rent-wallet-platform'); ?>
                <?php else: ?>
                    <span class="dashicons dashicons-warning"></span> <?php _e('Integrity Check Failed', 'rent-wallet-platform'); ?>
                <?php endif; ?>
            </h3>
            <p><?php _e('Verified at:', 'rent-wallet-platform'); ?> <?php echo esc_html($verification_result['verified_at']); ?></p>
        </div>
        
        <h3><?php _e('Ledger Chain', 'rent-wallet-platform'); ?></h3>
        <table class="widefat">
            <tr>
                <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                <td>
                    <span class="rw-status rw-status-<?php echo esc_attr($verification_result['ledger']['status']); ?>">
                        <?php echo esc_html(strtoupper($verification_result['ledger']['status'])); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><?php _e('Message', 'rent-wallet-platform'); ?></th>
                <td><?php echo esc_html($verification_result['ledger']['message']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Total Records', 'rent-wallet-platform'); ?></th>
                <td><?php echo esc_html($verification_result['ledger']['total_records']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Verified Records', 'rent-wallet-platform'); ?></th>
                <td><?php echo esc_html($verification_result['ledger']['verified_records']); ?></td>
            </tr>
            <?php if ($verification_result['ledger']['first_broken_id']): ?>
            <tr>
                <th><?php _e('First Broken ID', 'rent-wallet-platform'); ?></th>
                <td><?php echo esc_html($verification_result['ledger']['first_broken_id']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Expected Hash', 'rent-wallet-platform'); ?></th>
                <td><code><?php echo esc_html($verification_result['ledger']['expected_hash']); ?></code></td>
            </tr>
            <tr>
                <th><?php _e('Actual Hash', 'rent-wallet-platform'); ?></th>
                <td><code><?php echo esc_html($verification_result['ledger']['actual_hash']); ?></code></td>
            </tr>
            <?php endif; ?>
        </table>
        
        <h3><?php _e('Audit Log Chain', 'rent-wallet-platform'); ?></h3>
        <table class="widefat">
            <tr>
                <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                <td>
                    <span class="rw-status rw-status-<?php echo esc_attr($verification_result['audit']['status']); ?>">
                        <?php echo esc_html(strtoupper($verification_result['audit']['status'])); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><?php _e('Message', 'rent-wallet-platform'); ?></th>
                <td><?php echo esc_html($verification_result['audit']['message']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Total Records', 'rent-wallet-platform'); ?></th>
                <td><?php echo esc_html($verification_result['audit']['total_records']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Verified Records', 'rent-wallet-platform'); ?></th>
                <td><?php echo esc_html($verification_result['audit']['verified_records']); ?></td>
            </tr>
            <?php if ($verification_result['audit']['first_broken_id']): ?>
            <tr>
                <th><?php _e('First Broken ID', 'rent-wallet-platform'); ?></th>
                <td><?php echo esc_html($verification_result['audit']['first_broken_id']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Expected Hash', 'rent-wallet-platform'); ?></th>
                <td><code><?php echo esc_html($verification_result['audit']['expected_hash']); ?></code></td>
            </tr>
            <tr>
                <th><?php _e('Actual Hash', 'rent-wallet-platform'); ?></th>
                <td><code><?php echo esc_html($verification_result['audit']['actual_hash']); ?></code></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
</div>
