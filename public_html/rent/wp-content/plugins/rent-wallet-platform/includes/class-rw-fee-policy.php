<?php
/**
 * Fee policy management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Fee_Policy {
    
    public static function get() {
        global $wpdb;
        
        $table = RW_Database::get_table_name('fee_policy');
        
        $policy = $wpdb->get_row("SELECT * FROM {$table} WHERE id = 1");
        
        if (!$policy) {
            // Create default policy
            $wpdb->insert(
                $table,
                array(
                    'id' => 1,
                    'tenant_fee_bps' => 100,
                    'landlord_fee_bps' => 100,
                    'vat_enabled' => 0,
                    'vat_bps' => 2000
                ),
                array('%d', '%d', '%d', '%d', '%d')
            );
            
            $policy = $wpdb->get_row("SELECT * FROM {$table} WHERE id = 1");
        }
        
        return $policy;
    }
    
    public static function update($data) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('fee_policy');
        
        $old_policy = self::get();
        
        $update_data = array();
        $format = array();
        
        if (isset($data['tenant_fee_bps'])) {
            $update_data['tenant_fee_bps'] = absint($data['tenant_fee_bps']);
            $format[] = '%d';
        }
        
        if (isset($data['landlord_fee_bps'])) {
            $update_data['landlord_fee_bps'] = absint($data['landlord_fee_bps']);
            $format[] = '%d';
        }
        
        if (isset($data['vat_enabled'])) {
            $update_data['vat_enabled'] = $data['vat_enabled'] ? 1 : 0;
            $format[] = '%d';
        }
        
        if (isset($data['vat_bps'])) {
            $update_data['vat_bps'] = absint($data['vat_bps']);
            $format[] = '%d';
        }
        
        if (empty($update_data)) {
            return true;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => 1),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('fee_policy_update_failed', __('Failed to update fee policy', 'rent-wallet-platform'));
        }
        
        RW_Audit::log('fee_policy_updated', 'fee_policy', 1, array(
            'old' => (array) $old_policy,
            'new' => $update_data
        ));
        
        return true;
    }
    
    public static function calculate_tenant_fee($rent_pennies) {
        $policy = self::get();
        return (int) floor($rent_pennies * $policy->tenant_fee_bps / 10000);
    }
    
    public static function calculate_landlord_fee($rent_pennies) {
        $policy = self::get();
        return (int) floor($rent_pennies * $policy->landlord_fee_bps / 10000);
    }
    
    public static function calculate_vat($fee_pennies) {
        $policy = self::get();
        if (!$policy->vat_enabled) {
            return 0;
        }
        return (int) floor($fee_pennies * $policy->vat_bps / 10000);
    }
    
    public static function is_vat_enabled() {
        $policy = self::get();
        return (bool) $policy->vat_enabled;
    }
    
    public static function get_tenant_fee_percentage() {
        $policy = self::get();
        return $policy->tenant_fee_bps / 100;
    }
    
    public static function get_landlord_fee_percentage() {
        $policy = self::get();
        return $policy->landlord_fee_bps / 100;
    }
    
    public static function get_vat_percentage() {
        $policy = self::get();
        return $policy->vat_bps / 100;
    }
    
    public static function bps_to_percentage($bps) {
        return $bps / 100;
    }
    
    public static function percentage_to_bps($percentage) {
        return (int) ($percentage * 100);
    }
}
