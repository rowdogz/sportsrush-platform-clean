<?php
/**
 * Reward tiers management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Reward_Tiers {
    
    public static function get_all() {
        global $wpdb;
        
        $table = RW_Database::get_table_name('reward_tiers');
        
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY min_buffer_months ASC");
    }
    
    public static function get($tier_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('reward_tiers');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $tier_id
        ));
    }
    
    public static function get_by_key($tier_key) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('reward_tiers');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE tier_key = %s",
            $tier_key
        ));
    }
    
    public static function update($tier_id, $data) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('reward_tiers');
        
        $old_tier = self::get($tier_id);
        if (!$old_tier) {
            return new WP_Error('tier_not_found', __('Reward tier not found', 'rent-wallet-platform'));
        }
        
        $update_data = array();
        $format = array();
        
        if (isset($data['display_name'])) {
            $update_data['display_name'] = sanitize_text_field($data['display_name']);
            $format[] = '%s';
        }
        
        if (isset($data['min_buffer_months'])) {
            $update_data['min_buffer_months'] = floatval($data['min_buffer_months']);
            $format[] = '%f';
        }
        
        if (isset($data['cashback_bps'])) {
            $update_data['cashback_bps'] = absint($data['cashback_bps']);
            $format[] = '%d';
        }
        
        if (empty($update_data)) {
            return true;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $tier_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('tier_update_failed', __('Failed to update reward tier', 'rent-wallet-platform'));
        }
        
        RW_Audit::log('reward_tier_updated', 'reward_tier', $tier_id, array(
            'old' => (array) $old_tier,
            'new' => $update_data
        ));
        
        return true;
    }
    
    public static function get_tier_for_coverage($coverage_months) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('reward_tiers');
        
        // Get the highest tier that the coverage qualifies for
        $tier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE min_buffer_months <= %f ORDER BY min_buffer_months DESC LIMIT 1",
            $coverage_months
        ));
        
        if (!$tier) {
            // Return the lowest tier (Bronze)
            return $wpdb->get_row("SELECT * FROM {$table} ORDER BY min_buffer_months ASC LIMIT 1");
        }
        
        return $tier;
    }
    
    public static function calculate_cashback($rent_pennies, $coverage_months) {
        $tier = self::get_tier_for_coverage($coverage_months);
        
        if (!$tier || $tier->cashback_bps <= 0) {
            return 0;
        }
        
        return (int) floor($rent_pennies * $tier->cashback_bps / 10000);
    }
    
    public static function get_cashback_percentage($tier) {
        if (!$tier) {
            return 0;
        }
        return $tier->cashback_bps / 100;
    }
    
    public static function get_next_tier($current_tier_key) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('reward_tiers');
        
        $current = self::get_by_key($current_tier_key);
        if (!$current) {
            return null;
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE min_buffer_months > %f ORDER BY min_buffer_months ASC LIMIT 1",
            $current->min_buffer_months
        ));
    }
    
    public static function get_tier_progress($tenant_user_id) {
        $coverage_months = RW_Wallet::get_coverage_months($tenant_user_id);
        $current_tier = self::get_tier_for_coverage($coverage_months);
        $next_tier = $current_tier ? self::get_next_tier($current_tier->tier_key) : null;
        
        $progress = array(
            'coverage_months' => $coverage_months,
            'current_tier' => $current_tier,
            'next_tier' => $next_tier,
            'months_to_next' => null,
            'progress_percentage' => 100
        );
        
        if ($next_tier) {
            $months_to_next = $next_tier->min_buffer_months - $coverage_months;
            $progress['months_to_next'] = max(0, $months_to_next);
            
            // Calculate progress percentage within current tier
            $tier_range = $next_tier->min_buffer_months - $current_tier->min_buffer_months;
            $current_progress = $coverage_months - $current_tier->min_buffer_months;
            $progress['progress_percentage'] = min(100, max(0, ($current_progress / $tier_range) * 100));
        }
        
        return $progress;
    }
}
