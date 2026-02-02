<?php
/**
 * Ledger management class - append-only transaction log with HMAC hash chaining
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Ledger {
    
    public static function create_transaction($wallet_id, $type, $amount_pennies, $running_balance, $metadata = array(), $tenancy_id = null, $counterparty_wallet_id = null) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('transactions');
        
        // Get the previous hash
        $prev_hash = self::get_last_hash();
        
        // Prepare metadata
        $metadata_json = !empty($metadata) ? wp_json_encode($metadata) : null;
        
        // Create the canonical representation for hashing
        $canonical = self::create_canonical_representation(array(
            'wallet_id' => $wallet_id,
            'counterparty_wallet_id' => $counterparty_wallet_id,
            'tenancy_id' => $tenancy_id,
            'type' => $type,
            'amount_pennies' => $amount_pennies,
            'running_balance_pennies' => $running_balance,
            'metadata_json' => $metadata_json,
            'prev_hash' => $prev_hash
        ));
        
        // Generate HMAC hash
        $hash = self::generate_hash($prev_hash, $canonical);
        
        // Insert the transaction
        $result = $wpdb->insert(
            $table,
            array(
                'wallet_id' => $wallet_id,
                'counterparty_wallet_id' => $counterparty_wallet_id,
                'tenancy_id' => $tenancy_id,
                'type' => $type,
                'amount_pennies' => $amount_pennies,
                'running_balance_pennies' => $running_balance,
                'metadata_json' => $metadata_json,
                'prev_hash' => $prev_hash,
                'hash' => $hash
            ),
            array('%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('transaction_failed', __('Failed to create transaction', 'rent-wallet-platform'));
        }
        
        return $wpdb->insert_id;
    }
    
    public static function get_last_hash() {
        global $wpdb;
        
        $table = RW_Database::get_table_name('transactions');
        
        $last_hash = $wpdb->get_var("SELECT hash FROM {$table} ORDER BY id DESC LIMIT 1");
        
        return $last_hash ? $last_hash : null;
    }
    
    public static function create_canonical_representation($data) {
        // Sort keys for consistent ordering
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
    
    public static function get_transaction($transaction_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('transactions');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $transaction_id
        ));
    }
    
    public static function get_transactions($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('transactions');
        
        $defaults = array(
            'wallet_id' => 0,
            'tenancy_id' => 0,
            'type' => '',
            'user_id' => 0,
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
        
        if (!empty($args['wallet_id'])) {
            $where .= " AND wallet_id = %d";
            $params[] = $args['wallet_id'];
        }
        
        if (!empty($args['tenancy_id'])) {
            $where .= " AND tenancy_id = %d";
            $params[] = $args['tenancy_id'];
        }
        
        if (!empty($args['type'])) {
            $where .= " AND type = %s";
            $params[] = $args['type'];
        }
        
        if (!empty($args['user_id'])) {
            // Get wallet IDs for this user
            $wallets_table = RW_Database::get_table_name('wallets');
            $where .= " AND wallet_id IN (SELECT id FROM {$wallets_table} WHERE owner_user_id = %d)";
            $params[] = $args['user_id'];
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
    
    public static function get_transactions_count($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('transactions');
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['wallet_id'])) {
            $where .= " AND wallet_id = %d";
            $params[] = $args['wallet_id'];
        }
        
        if (!empty($args['tenancy_id'])) {
            $where .= " AND tenancy_id = %d";
            $params[] = $args['tenancy_id'];
        }
        
        if (!empty($args['type'])) {
            $where .= " AND type = %s";
            $params[] = $args['type'];
        }
        
        if (!empty($args['user_id'])) {
            $wallets_table = RW_Database::get_table_name('wallets');
            $where .= " AND wallet_id IN (SELECT id FROM {$wallets_table} WHERE owner_user_id = %d)";
            $params[] = $args['user_id'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        
        if (!empty($params)) {
            return $wpdb->get_var($wpdb->prepare($sql, $params));
        }
        
        return $wpdb->get_var($sql);
    }
    
    public static function get_type_label($type) {
        $labels = array(
            'manual_credit' => __('Manual Credit', 'rent-wallet-platform'),
            'rent_release' => __('Rent Release', 'rent-wallet-platform'),
            'tenant_fee' => __('Tenant Fee', 'rent-wallet-platform'),
            'landlord_fee' => __('Landlord Fee', 'rent-wallet-platform'),
            'vat' => __('VAT', 'rent-wallet-platform'),
            'tenant_vat' => __('Tenant VAT', 'rent-wallet-platform'),
            'cashback_tenant' => __('Cashback (Tenant)', 'rent-wallet-platform'),
            'cashback_platform_share' => __('Cashback (Platform Share)', 'rent-wallet-platform'),
            'cashback_landlord_share' => __('Cashback (Landlord Share)', 'rent-wallet-platform'),
            'payout_simulated' => __('Payout (Simulated)', 'rent-wallet-platform'),
            'adjustment' => __('Adjustment', 'rent-wallet-platform'),
            'platform_fee_income' => __('Platform Fee Income', 'rent-wallet-platform'),
        );
        
        return isset($labels[$type]) ? $labels[$type] : $type;
    }
    
    public static function get_wallet_transactions($wallet_id, $limit = 50) {
        return self::get_transactions(array(
            'wallet_id' => $wallet_id,
            'limit' => $limit
        ));
    }
    
    public static function get_user_cashback_total($user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('transactions');
        $wallets_table = RW_Database::get_table_name('wallets');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount_pennies), 0) FROM {$table} 
            WHERE wallet_id IN (SELECT id FROM {$wallets_table} WHERE owner_user_id = %d)
            AND type = 'cashback_tenant'",
            $user_id
        ));
    }
}
