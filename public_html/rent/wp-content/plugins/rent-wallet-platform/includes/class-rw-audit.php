<?php
/**
 * Audit log management class - append-only with HMAC hash chaining
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Audit {
    
    public static function log($action, $entity, $entity_id = null, $delta = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('audit_log');
        
        // Get current user
        $actor_user_id = get_current_user_id();
        if (!$actor_user_id) {
            $actor_user_id = null;
        }
        
        // Get IP and user agent
        $ip = self::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null;
        
        // Get the previous hash
        $prev_hash = self::get_last_hash();
        
        // Prepare delta JSON
        $delta_json = !empty($delta) ? wp_json_encode($delta) : null;
        
        // Create canonical representation
        $canonical = self::create_canonical_representation(array(
            'actor_user_id' => $actor_user_id,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entity_id,
            'delta_json' => $delta_json,
            'ip' => $ip,
            'user_agent' => $user_agent,
            'prev_hash' => $prev_hash
        ));
        
        // Generate HMAC hash
        $hash = self::generate_hash($prev_hash, $canonical);
        
        // Insert the audit entry
        $result = $wpdb->insert(
            $table,
            array(
                'actor_user_id' => $actor_user_id,
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entity_id,
                'delta_json' => $delta_json,
                'ip' => $ip,
                'user_agent' => $user_agent,
                'prev_hash' => $prev_hash,
                'hash' => $hash
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('Rent Wallet: Failed to create audit log entry - ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    public static function get_last_hash() {
        global $wpdb;
        
        $table = RW_Database::get_table_name('audit_log');
        
        $last_hash = $wpdb->get_var("SELECT hash FROM {$table} ORDER BY id DESC LIMIT 1");
        
        return $last_hash ? $last_hash : null;
    }
    
    public static function create_canonical_representation($data) {
        ksort($data);
        
        $parts = array();
        foreach ($data as $key => $value) {
            if ($value === null) {
                $value = 'NULL';
            }
            $parts[] = $key . '=' . $value;
        }
        
        return implode('|', $parts);
    }
    
    public static function generate_hash($prev_hash, $canonical) {
        $secret = Rent_Wallet_Platform::get_hmac_secret();
        $data = ($prev_hash ? $prev_hash : '') . $canonical;
        return hash_hmac('sha256', $data, $secret);
    }
    
    public static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    public static function get_entries($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('audit_log');
        
        $defaults = array(
            'actor_user_id' => 0,
            'action' => '',
            'entity' => '',
            'entity_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['actor_user_id'])) {
            $where .= " AND actor_user_id = %d";
            $params[] = $args['actor_user_id'];
        }
        
        if (!empty($args['action'])) {
            $where .= " AND action = %s";
            $params[] = $args['action'];
        }
        
        if (!empty($args['entity'])) {
            $where .= " AND entity = %s";
            $params[] = $args['entity'];
        }
        
        if (!empty($args['entity_id'])) {
            $where .= " AND entity_id = %d";
            $params[] = $args['entity_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where .= " AND created_at >= %s";
            $params[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND created_at <= %s";
            $params[] = $args['date_to'];
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'id DESC';
        }
        
        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        }
        
        return $wpdb->get_results($sql);
    }
    
    public static function get_entries_count($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('audit_log');
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['actor_user_id'])) {
            $where .= " AND actor_user_id = %d";
            $params[] = $args['actor_user_id'];
        }
        
        if (!empty($args['action'])) {
            $where .= " AND action = %s";
            $params[] = $args['action'];
        }
        
        if (!empty($args['entity'])) {
            $where .= " AND entity = %s";
            $params[] = $args['entity'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        
        if (!empty($params)) {
            return $wpdb->get_var($wpdb->prepare($sql, $params));
        }
        
        return $wpdb->get_var($sql);
    }
    
    public static function get_action_label($action) {
        $labels = array(
            'wallet_created' => __('Wallet Created', 'rent-wallet-platform'),
            'manual_credit' => __('Manual Credit', 'rent-wallet-platform'),
            'rent_released' => __('Rent Released', 'rent-wallet-platform'),
            'rent_arrears' => __('Rent Arrears', 'rent-wallet-platform'),
            'property_created' => __('Property Created', 'rent-wallet-platform'),
            'property_updated' => __('Property Updated', 'rent-wallet-platform'),
            'property_deleted' => __('Property Deleted', 'rent-wallet-platform'),
            'tenancy_created' => __('Tenancy Created', 'rent-wallet-platform'),
            'tenancy_updated' => __('Tenancy Updated', 'rent-wallet-platform'),
            'tenancy_ended' => __('Tenancy Ended', 'rent-wallet-platform'),
            'agency_created' => __('Agency Created', 'rent-wallet-platform'),
            'agency_updated' => __('Agency Updated', 'rent-wallet-platform'),
            'agency_deleted' => __('Agency Deleted', 'rent-wallet-platform'),
            'agency_assignment_created' => __('Agency Assignment Created', 'rent-wallet-platform'),
            'agency_assignment_deleted' => __('Agency Assignment Deleted', 'rent-wallet-platform'),
            'fee_policy_updated' => __('Fee Policy Updated', 'rent-wallet-platform'),
            'reward_tier_updated' => __('Reward Tier Updated', 'rent-wallet-platform'),
            'user_profile_updated' => __('User Profile Updated', 'rent-wallet-platform'),
            'gdpr_export' => __('GDPR Data Export', 'rent-wallet-platform'),
            'gdpr_anonymise' => __('GDPR Anonymisation', 'rent-wallet-platform'),
            'demo_data_generated' => __('Demo Data Generated', 'rent-wallet-platform'),
            'topup_requested' => __('Top-up Requested', 'rent-wallet-platform'),
        );
        
        return isset($labels[$action]) ? $labels[$action] : $action;
    }
}
