<?php
/**
 * Wallet management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Wallet {
    
    public static function get_or_create_wallet($user_id, $role) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('wallets');
        
        $wallet = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE owner_user_id = %d AND owner_role = %s",
            $user_id,
            $role
        ));
        
        if ($wallet) {
            return $wallet;
        }
        
        // Create new wallet
        $wpdb->insert(
            $table,
            array(
                'owner_user_id' => $user_id,
                'owner_role' => $role,
                'currency' => 'GBP',
                'balance_pennies' => 0
            ),
            array('%d', '%s', '%s', '%d')
        );
        
        $wallet_id = $wpdb->insert_id;
        
        RW_Audit::log('wallet_created', 'wallet', $wallet_id, array(
            'owner_user_id' => $user_id,
            'owner_role' => $role
        ));
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $wallet_id
        ));
    }
    
    public static function get_wallet($wallet_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('wallets');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $wallet_id
        ));
    }
    
    public static function get_wallet_by_user($user_id, $role) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('wallets');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE owner_user_id = %d AND owner_role = %s",
            $user_id,
            $role
        ));
    }
    
    public static function get_platform_wallet() {
        return self::get_or_create_wallet(0, 'platform');
    }
    
    public static function get_balance($wallet_id) {
        $wallet = self::get_wallet($wallet_id);
        return $wallet ? $wallet->balance_pennies : 0;
    }
    
    public static function get_balance_formatted($wallet_id) {
        $balance = self::get_balance($wallet_id);
        return self::format_pennies($balance);
    }
    
    public static function format_pennies($pennies) {
        $pounds = $pennies / 100;
        return '£' . number_format($pounds, 2);
    }
    
    public static function pennies_to_pounds($pennies) {
        return $pennies / 100;
    }
    
    public static function pounds_to_pennies($pounds) {
        return (int) round($pounds * 100);
    }
    
    public static function update_balance($wallet_id, $new_balance) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('wallets');
        
        return $wpdb->update(
            $table,
            array('balance_pennies' => $new_balance),
            array('id' => $wallet_id),
            array('%d'),
            array('%d')
        );
    }
    
    public static function credit($wallet_id, $amount_pennies, $type, $metadata = array(), $tenancy_id = null, $counterparty_wallet_id = null) {
        global $wpdb;
        
        $wallet = self::get_wallet($wallet_id);
        if (!$wallet) {
            return new WP_Error('wallet_not_found', __('Wallet not found', 'rent-wallet-platform'));
        }
        
        $new_balance = $wallet->balance_pennies + $amount_pennies;
        
        // Update wallet balance
        self::update_balance($wallet_id, $new_balance);
        
        // Create ledger entry
        $transaction_id = RW_Ledger::create_transaction(
            $wallet_id,
            $type,
            $amount_pennies,
            $new_balance,
            $metadata,
            $tenancy_id,
            $counterparty_wallet_id
        );
        
        return $transaction_id;
    }
    
    public static function debit($wallet_id, $amount_pennies, $type, $metadata = array(), $tenancy_id = null, $counterparty_wallet_id = null) {
        global $wpdb;
        
        $wallet = self::get_wallet($wallet_id);
        if (!$wallet) {
            return new WP_Error('wallet_not_found', __('Wallet not found', 'rent-wallet-platform'));
        }
        
        $new_balance = $wallet->balance_pennies - $amount_pennies;
        
        // Update wallet balance
        self::update_balance($wallet_id, $new_balance);
        
        // Create ledger entry (negative amount for debit)
        $transaction_id = RW_Ledger::create_transaction(
            $wallet_id,
            $type,
            -$amount_pennies,
            $new_balance,
            $metadata,
            $tenancy_id,
            $counterparty_wallet_id
        );
        
        return $transaction_id;
    }
    
    public static function manual_credit($tenant_user_id, $amount_pennies, $admin_user_id, $notes = '') {
        $wallet = self::get_or_create_wallet($tenant_user_id, 'tenant');
        
        $metadata = array(
            'credited_by' => $admin_user_id,
            'notes' => $notes,
            'timestamp' => current_time('mysql')
        );
        
        $result = self::credit($wallet->id, $amount_pennies, 'manual_credit', $metadata);
        
        if (!is_wp_error($result)) {
            RW_Audit::log('manual_credit', 'wallet', $wallet->id, array(
                'amount_pennies' => $amount_pennies,
                'credited_by' => $admin_user_id,
                'notes' => $notes
            ));
        }
        
        return $result;
    }
    
    public static function get_all_wallets($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('wallets');
        
        $defaults = array(
            'owner_role' => '',
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['owner_role'])) {
            $where .= " AND owner_role = %s";
            $params[] = $args['owner_role'];
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
    
    public static function get_coverage_months($tenant_user_id) {
        global $wpdb;
        
        $wallet = self::get_wallet_by_user($tenant_user_id, 'tenant');
        if (!$wallet || $wallet->balance_pennies <= 0) {
            return 0;
        }
        
        // Get active tenancy for this tenant
        $tenancy = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rw_tenancies WHERE tenant_user_id = %d AND status = 'active' LIMIT 1",
            $tenant_user_id
        ));
        
        if (!$tenancy || $tenancy->rent_amount_pennies <= 0) {
            return 0;
        }
        
        return $wallet->balance_pennies / $tenancy->rent_amount_pennies;
    }
    
    public static function get_tenant_tier($tenant_user_id) {
        $coverage_months = self::get_coverage_months($tenant_user_id);
        return RW_Reward_Tiers::get_tier_for_coverage($coverage_months);
    }
}
