<?php
/**
 * Notifications management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Notifications {
    
    public static function send($user_id, $template_key, $subject, $body, $channel = 'email') {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'rent-wallet-platform'));
        }
        
        $status = 'pending';
        $error_text = null;
        
        if ($channel === 'email') {
            $result = self::send_email($user->user_email, $subject, $body);
            
            if ($result) {
                $status = 'sent';
            } else {
                $status = 'failed';
                $error_text = __('Failed to send email', 'rent-wallet-platform');
            }
        }
        
        // Log notification
        $table = RW_Database::get_table_name('notification_logs');
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'channel' => $channel,
                'template_key' => $template_key,
                'subject' => $subject,
                'body' => $body,
                'status' => $status,
                'error_text' => $error_text
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    private static function send_email($to, $subject, $body) {
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: Rent Wallet <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        );
        
        return wp_mail($to, $subject, $body, $headers);
    }
    
    public static function send_topup_request($tenant_user_id, $amount_pennies, $notes = '') {
        $tenant = get_userdata($tenant_user_id);
        if (!$tenant) {
            return new WP_Error('user_not_found', __('User not found', 'rent-wallet-platform'));
        }
        
        // Get admin email
        $admin_email = get_option('admin_email');
        
        $subject = sprintf(__('Top-up Request from %s', 'rent-wallet-platform'), $tenant->display_name);
        
        $body = sprintf(
            __("A tenant has requested a wallet top-up:\n\nTenant: %s (%s)\nRequested Amount: %s\nNotes: %s\n\nPlease process this request in the WordPress admin.\n\nRent Wallet Platform", 'rent-wallet-platform'),
            $tenant->display_name,
            $tenant->user_email,
            RW_Wallet::format_pennies($amount_pennies),
            $notes ? $notes : 'None'
        );
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: Rent Wallet <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        );
        
        $result = wp_mail($admin_email, $subject, $body, $headers);
        
        // Log the notification
        global $wpdb;
        $table = RW_Database::get_table_name('notification_logs');
        $wpdb->insert(
            $table,
            array(
                'user_id' => $tenant_user_id,
                'channel' => 'email',
                'template_key' => 'topup_request',
                'subject' => $subject,
                'body' => $body,
                'status' => $result ? 'sent' : 'failed',
                'error_text' => $result ? null : 'Failed to send email'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Audit log
        RW_Audit::log('topup_requested', 'wallet', null, array(
            'tenant_user_id' => $tenant_user_id,
            'amount_pennies' => $amount_pennies,
            'notes' => $notes
        ));
        
        return $result;
    }
    
    public static function get_logs($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('notification_logs');
        
        $defaults = array(
            'user_id' => 0,
            'template_key' => '',
            'status' => '',
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['user_id'])) {
            $where .= " AND user_id = %d";
            $params[] = $args['user_id'];
        }
        
        if (!empty($args['template_key'])) {
            $where .= " AND template_key = %s";
            $params[] = $args['template_key'];
        }
        
        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'id DESC';
        }
        
        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    public static function get_logs_count($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('notification_logs');
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['user_id'])) {
            $where .= " AND user_id = %d";
            $params[] = $args['user_id'];
        }
        
        if (!empty($args['template_key'])) {
            $where .= " AND template_key = %s";
            $params[] = $args['template_key'];
        }
        
        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        
        if (!empty($params)) {
            return $wpdb->get_var($wpdb->prepare($sql, $params));
        }
        
        return $wpdb->get_var($sql);
    }
}
