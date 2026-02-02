<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Audit Log', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Actor', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Action', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Entity', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Entity ID', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('IP', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Hash', 'rent-wallet-platform'); ?></th>
                    <th><?php _e('Created', 'rent-wallet-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): 
                    $actor = $entry->actor_user_id ? get_userdata($entry->actor_user_id) : null;
                ?>
                <tr>
                    <td><?php echo esc_html($entry->id); ?></td>
                    <td><?php echo $actor ? esc_html($actor->display_name) : __('System', 'rent-wallet-platform'); ?></td>
                    <td><?php echo esc_html(RW_Audit::get_action_label($entry->action)); ?></td>
                    <td><?php echo esc_html($entry->entity); ?></td>
                    <td><?php echo $entry->entity_id ? esc_html($entry->entity_id) : '-'; ?></td>
                    <td><?php echo esc_html($entry->ip); ?></td>
                    <td><code title="<?php echo esc_attr($entry->hash); ?>"><?php echo esc_html(substr($entry->hash, 0, 12) . '...'); ?></code></td>
                    <td><?php echo esc_html($entry->created_at); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
