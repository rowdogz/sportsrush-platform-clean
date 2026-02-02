<?php
/**
 * Property management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Property {
    
    public static function create($data) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('properties');
        
        $required = array('landlord_user_id', 'address_line1', 'city', 'postcode');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Missing required field: %s', 'rent-wallet-platform'), $field));
            }
        }
        
        $insert_data = array(
            'landlord_user_id' => absint($data['landlord_user_id']),
            'agency_id' => isset($data['agency_id']) ? absint($data['agency_id']) : null,
            'address_line1' => sanitize_text_field($data['address_line1']),
            'address_line2' => isset($data['address_line2']) ? sanitize_text_field($data['address_line2']) : null,
            'city' => sanitize_text_field($data['city']),
            'postcode' => sanitize_text_field($data['postcode']),
            'bedrooms' => isset($data['bedrooms']) ? absint($data['bedrooms']) : null,
            'property_type' => isset($data['property_type']) ? sanitize_text_field($data['property_type']) : null,
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
        );
        
        $result = $wpdb->insert(
            $table,
            $insert_data,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('property_create_failed', __('Failed to create property', 'rent-wallet-platform'));
        }
        
        $property_id = $wpdb->insert_id;
        
        RW_Audit::log('property_created', 'property', $property_id, $insert_data);
        
        return $property_id;
    }
    
    public static function update($property_id, $data) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('properties');
        
        $old_property = self::get($property_id);
        if (!$old_property) {
            return new WP_Error('property_not_found', __('Property not found', 'rent-wallet-platform'));
        }
        
        $update_data = array();
        $format = array();
        
        $allowed_fields = array(
            'landlord_user_id' => '%d',
            'agency_id' => '%d',
            'address_line1' => '%s',
            'address_line2' => '%s',
            'city' => '%s',
            'postcode' => '%s',
            'bedrooms' => '%d',
            'property_type' => '%s',
            'status' => '%s'
        );
        
        foreach ($allowed_fields as $field => $field_format) {
            if (isset($data[$field])) {
                if (in_array($field, array('address_line1', 'address_line2', 'city', 'postcode', 'property_type', 'status'))) {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                } else {
                    $update_data[$field] = $data[$field] === null ? null : absint($data[$field]);
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
            array('id' => $property_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('property_update_failed', __('Failed to update property', 'rent-wallet-platform'));
        }
        
        RW_Audit::log('property_updated', 'property', $property_id, array(
            'old' => (array) $old_property,
            'new' => $update_data
        ));
        
        return true;
    }
    
    public static function delete($property_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('properties');
        
        $property = self::get($property_id);
        if (!$property) {
            return new WP_Error('property_not_found', __('Property not found', 'rent-wallet-platform'));
        }
        
        // Check for active tenancies
        $tenancies_table = RW_Database::get_table_name('tenancies');
        $active_tenancies = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tenancies_table} WHERE property_id = %d AND status = 'active'",
            $property_id
        ));
        
        if ($active_tenancies > 0) {
            return new WP_Error('has_active_tenancies', __('Cannot delete property with active tenancies', 'rent-wallet-platform'));
        }
        
        $result = $wpdb->delete($table, array('id' => $property_id), array('%d'));
        
        if ($result === false) {
            return new WP_Error('property_delete_failed', __('Failed to delete property', 'rent-wallet-platform'));
        }
        
        RW_Audit::log('property_deleted', 'property', $property_id, (array) $property);
        
        return true;
    }
    
    public static function get($property_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('properties');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $property_id
        ));
    }
    
    public static function get_all($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('properties');
        
        $defaults = array(
            'landlord_user_id' => 0,
            'agency_id' => 0,
            'status' => '',
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['landlord_user_id'])) {
            $where .= " AND landlord_user_id = %d";
            $params[] = $args['landlord_user_id'];
        }
        
        if (!empty($args['agency_id'])) {
            $where .= " AND agency_id = %d";
            $params[] = $args['agency_id'];
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
    
    public static function get_count($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('properties');
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['landlord_user_id'])) {
            $where .= " AND landlord_user_id = %d";
            $params[] = $args['landlord_user_id'];
        }
        
        if (!empty($args['agency_id'])) {
            $where .= " AND agency_id = %d";
            $params[] = $args['agency_id'];
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
    
    public static function get_by_landlord($landlord_user_id) {
        return self::get_all(array('landlord_user_id' => $landlord_user_id, 'limit' => 1000));
    }
    
    public static function get_by_agency($agency_id) {
        return self::get_all(array('agency_id' => $agency_id, 'limit' => 1000));
    }
    
    public static function get_full_address($property) {
        $parts = array($property->address_line1);
        if (!empty($property->address_line2)) {
            $parts[] = $property->address_line2;
        }
        $parts[] = $property->city;
        $parts[] = $property->postcode;
        
        return implode(', ', $parts);
    }
    
    public static function get_property_types() {
        return array(
            'flat' => __('Flat', 'rent-wallet-platform'),
            'house' => __('House', 'rent-wallet-platform'),
            'studio' => __('Studio', 'rent-wallet-platform'),
            'maisonette' => __('Maisonette', 'rent-wallet-platform'),
            'bungalow' => __('Bungalow', 'rent-wallet-platform'),
            'room' => __('Room', 'rent-wallet-platform'),
            'other' => __('Other', 'rent-wallet-platform')
        );
    }
    
    public static function get_statuses() {
        return array(
            'active' => __('Active', 'rent-wallet-platform'),
            'inactive' => __('Inactive', 'rent-wallet-platform'),
            'maintenance' => __('Under Maintenance', 'rent-wallet-platform')
        );
    }
}
