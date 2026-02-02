<?php
/**
 * Tenancy management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Tenancy {
    
    public static function create($data) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('tenancies');
        
        $required = array('property_id', 'tenant_user_id', 'landlord_user_id', 'rent_amount_pennies', 'due_day', 'start_date');
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return new WP_Error('missing_field', sprintf(__('Missing required field: %s', 'rent-wallet-platform'), $field));
            }
        }
        
        // Validate due_day
        $due_day = absint($data['due_day']);
        if ($due_day < 1 || $due_day > 28) {
            return new WP_Error('invalid_due_day', __('Due day must be between 1 and 28', 'rent-wallet-platform'));
        }
        
        $insert_data = array(
            'property_id' => absint($data['property_id']),
            'tenant_user_id' => absint($data['tenant_user_id']),
            'landlord_user_id' => absint($data['landlord_user_id']),
            'rent_amount_pennies' => absint($data['rent_amount_pennies']),
            'due_day' => $due_day,
            'start_date' => sanitize_text_field($data['start_date']),
            'end_date' => isset($data['end_date']) && !empty($data['end_date']) ? sanitize_text_field($data['end_date']) : null,
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
        );
        
        $result = $wpdb->insert(
            $table,
            $insert_data,
            array('%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('tenancy_create_failed', __('Failed to create tenancy', 'rent-wallet-platform'));
        }
        
        $tenancy_id = $wpdb->insert_id;
        
        // Ensure tenant has a wallet
        RW_Wallet::get_or_create_wallet($data['tenant_user_id'], 'tenant');
        
        // Ensure landlord has a wallet
        RW_Wallet::get_or_create_wallet($data['landlord_user_id'], 'landlord');
        
        RW_Audit::log('tenancy_created', 'tenancy', $tenancy_id, $insert_data);
        
        return $tenancy_id;
    }
    
    public static function update($tenancy_id, $data) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('tenancies');
        
        $old_tenancy = self::get($tenancy_id);
        if (!$old_tenancy) {
            return new WP_Error('tenancy_not_found', __('Tenancy not found', 'rent-wallet-platform'));
        }
        
        $update_data = array();
        $format = array();
        
        $allowed_fields = array(
            'property_id' => '%d',
            'tenant_user_id' => '%d',
            'landlord_user_id' => '%d',
            'rent_amount_pennies' => '%d',
            'due_day' => '%d',
            'start_date' => '%s',
            'end_date' => '%s',
            'status' => '%s'
        );
        
        foreach ($allowed_fields as $field => $field_format) {
            if (isset($data[$field])) {
                if ($field === 'due_day') {
                    $due_day = absint($data[$field]);
                    if ($due_day < 1 || $due_day > 28) {
                        return new WP_Error('invalid_due_day', __('Due day must be between 1 and 28', 'rent-wallet-platform'));
                    }
                    $update_data[$field] = $due_day;
                } elseif (in_array($field, array('start_date', 'end_date', 'status'))) {
                    $update_data[$field] = $data[$field] === '' ? null : sanitize_text_field($data[$field]);
                } else {
                    $update_data[$field] = absint($data[$field]);
                }
                $format[] = $field_format;
            }
        }
        
        if (empty($update_data)) {
            return true;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $tenancy_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('tenancy_update_failed', __('Failed to update tenancy', 'rent-wallet-platform'));
        }
        
        RW_Audit::log('tenancy_updated', 'tenancy', $tenancy_id, array(
            'old' => (array) $old_tenancy,
            'new' => $update_data
        ));
        
        return true;
    }
    
    public static function end_tenancy($tenancy_id, $end_date = null) {
        if (!$end_date) {
            $end_date = current_time('Y-m-d');
        }
        
        $result = self::update($tenancy_id, array(
            'status' => 'ended',
            'end_date' => $end_date
        ));
        
        if (!is_wp_error($result)) {
            RW_Audit::log('tenancy_ended', 'tenancy', $tenancy_id, array('end_date' => $end_date));
        }
        
        return $result;
    }
    
    public static function get($tenancy_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('tenancies');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $tenancy_id
        ));
    }
    
    public static function get_all($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('tenancies');
        
        $defaults = array(
            'property_id' => 0,
            'tenant_user_id' => 0,
            'landlord_user_id' => 0,
            'status' => '',
            'due_day' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['property_id'])) {
            $where .= " AND property_id = %d";
            $params[] = $args['property_id'];
        }
        
        if (!empty($args['tenant_user_id'])) {
            $where .= " AND tenant_user_id = %d";
            $params[] = $args['tenant_user_id'];
        }
        
        if (!empty($args['landlord_user_id'])) {
            $where .= " AND landlord_user_id = %d";
            $params[] = $args['landlord_user_id'];
        }
        
        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        if (!empty($args['due_day'])) {
            $where .= " AND due_day = %d";
            $params[] = $args['due_day'];
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
    
    public static function get_count($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('tenancies');
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['property_id'])) {
            $where .= " AND property_id = %d";
            $params[] = $args['property_id'];
        }
        
        if (!empty($args['tenant_user_id'])) {
            $where .= " AND tenant_user_id = %d";
            $params[] = $args['tenant_user_id'];
        }
        
        if (!empty($args['landlord_user_id'])) {
            $where .= " AND landlord_user_id = %d";
            $params[] = $args['landlord_user_id'];
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
    
    public static function get_active_tenancies_due_today() {
        global $wpdb;
        
        $table = RW_Database::get_table_name('tenancies');
        $today_day = (int) current_time('j');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = 'active' AND due_day = %d",
            $today_day
        ));
    }
    
    public static function get_by_tenant($tenant_user_id) {
        return self::get_all(array('tenant_user_id' => $tenant_user_id, 'limit' => 1000));
    }
    
    public static function get_by_landlord($landlord_user_id) {
        return self::get_all(array('landlord_user_id' => $landlord_user_id, 'limit' => 1000));
    }
    
    public static function get_active_tenancy_for_tenant($tenant_user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('tenancies');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE tenant_user_id = %d AND status = 'active' LIMIT 1",
            $tenant_user_id
        ));
    }
    
    public static function get_statuses() {
        return array(
            'active' => __('Active', 'rent-wallet-platform'),
            'ended' => __('Ended', 'rent-wallet-platform'),
            'paused' => __('Paused', 'rent-wallet-platform')
        );
    }
    
    public static function get_tenancy_with_details($tenancy_id) {
        global $wpdb;
        
        $tenancy = self::get($tenancy_id);
        if (!$tenancy) {
            return null;
        }
        
        $tenancy->property = RW_Property::get($tenancy->property_id);
        $tenancy->tenant = get_userdata($tenancy->tenant_user_id);
        $tenancy->landlord = get_userdata($tenancy->landlord_user_id);
        
        return $tenancy;
    }
    
    public static function get_arrears_tenancies() {
        global $wpdb;
        
        $tenancies_table = RW_Database::get_table_name('tenancies');
        $wallets_table = RW_Database::get_table_name('wallets');
        
        return $wpdb->get_results(
            "SELECT t.*, w.balance_pennies as tenant_balance
            FROM {$tenancies_table} t
            LEFT JOIN {$wallets_table} w ON w.owner_user_id = t.tenant_user_id AND w.owner_role = 'tenant'
            WHERE t.status = 'active' AND (w.balance_pennies IS NULL OR w.balance_pennies < t.rent_amount_pennies)"
        );
    }
}
