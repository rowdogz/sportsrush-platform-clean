<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Notification Logs', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('User', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Channel', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Template', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Subject', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Status', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Sent', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notification): 
                    $user = get_userdata($notification->user_id);
                ?>
                <tr>
                    <td><?php echo esc_html($notification->id); ?></td>
                    <td><?php echo $user ? esc_html($user->display_name) : '-'; ?></td>
                    <td><?php echo esc_html(ucfirst($notification->channel)); ?></td>
                    <td><?php echo esc_html($notification->template_key); ?></td>
                    <td><?php echo esc_html($notification->subject); ?></td>
                    <td><span class="rw-status rw-status-<?php echo esc_attr($notification->status); ?>"><?php echo esc_html(ucfirst($notification->status)); ?></span></td>
                    <td><?php echo esc_html($notification->created_at); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
