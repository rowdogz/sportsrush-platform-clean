<?php
/**
 * REST API endpoints class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_REST_API {
    
    const NAMESPACE = 'rent-wallet/v1';
    
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }
    
    public static function register_routes() {
        // Tenant endpoints
        register_rest_route(self::NAMESPACE, '/tenant/wallet', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_tenant_wallet'),
            'permission_callback' => array(__CLASS__, 'check_tenant_permission')
        ));
        
        register_rest_route(self::NAMESPACE, '/tenant/transactions', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_tenant_transactions'),
            'permission_callback' => array(__CLASS__, 'check_tenant_permission')
        ));
        
        register_rest_route(self::NAMESPACE, '/tenant/request-topup', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'request_topup'),
            'permission_callback' => array(__CLASS__, 'check_tenant_permission')
        ));
        
        // Landlord endpoints
        register_rest_route(self::NAMESPACE, '/landlord/properties', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_landlord_properties'),
            'permission_callback' => array(__CLASS__, 'check_landlord_permission')
        ));
        
        register_rest_route(self::NAMESPACE, '/landlord/payouts', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_landlord_payouts'),
            'permission_callback' => array(__CLASS__, 'check_landlord_permission')
        ));
        
        register_rest_route(self::NAMESPACE, '/landlord/export/payouts', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_landlord_payouts'),
            'permission_callback' => array(__CLASS__, 'check_landlord_permission')
        ));
    }
    
    public static function check_tenant_permission() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        return in_array('rw_tenant', $user->roles) || in_array('administrator', $user->roles);
    }
    
    public static function check_landlord_permission() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        return in_array('rw_landlord', $user->roles) || in_array('administrator', $user->roles);
    }
    
    public static function get_tenant_wallet($request) {
        $user_id = get_current_user_id();
        $wallet = RW_Wallet::get_or_create_wallet($user_id, 'tenant');
        $tenancy = RW_Tenancy::get_active_tenancy_for_tenant($user_id);
        $tier_progress = RW_Reward_Tiers::get_tier_progress($user_id);
        
        return rest_ensure_response(array(
            'wallet' => array(
                'id' => $wallet->id,
                'balance_pennies' => $wallet->balance_pennies,
                'balance_formatted' => RW_Wallet::format_pennies($wallet->balance_pennies),
                'currency' => $wallet->currency
            ),
            'tenancy' => $tenancy ? array(
                'id' => $tenancy->id,
                'rent_amount_pennies' => $tenancy->rent_amount_pennies,
                'rent_formatted' => RW_Wallet::format_pennies($tenancy->rent_amount_pennies),
                'due_day' => $tenancy->due_day,
                'status' => $tenancy->status
            ) : null,
            'tier' => array(
                'current' => $tier_progress['current_tier'] ? array(
                    'key' => $tier_progress['current_tier']->tier_key,
                    'name' => $tier_progress['current_tier']->display_name,
                    'cashback_percentage' => RW_Reward_Tiers::get_cashback_percentage($tier_progress['current_tier'])
                ) : null,
                'next' => $tier_progress['next_tier'] ? array(
                    'key' => $tier_progress['next_tier']->tier_key,
                    'name' => $tier_progress['next_tier']->display_name,
                    'months_needed' => $tier_progress['months_to_next']
                ) : null,
                'coverage_months' => $tier_progress['coverage_months'],
                'progress_percentage' => $tier_progress['progress_percentage']
            )
        ));
    }
    
    public static function get_tenant_transactions($request) {
        $user_id = get_current_user_id();
        $wallet = RW_Wallet::get_wallet_by_user($user_id, 'tenant');
        
        if (!$wallet) {
            return rest_ensure_response(array('transactions' => array()));
        }
        
        $limit = $request->get_param('limit') ? absint($request->get_param('limit')) : 20;
        $offset = $request->get_param('offset') ? absint($request->get_param('offset')) : 0;
        
        $transactions = RW_Ledger::get_transactions(array(
            'wallet_id' => $wallet->id,
            'limit' => $limit,
            'offset' => $offset
        ));
        
        $formatted = array_map(function($tx) {
            return array(
                'id' => $tx->id,
                'type' => $tx->type,
                'type_label' => RW_Ledger::get_type_label($tx->type),
                'amount_pennies' => $tx->amount_pennies,
                'amount_formatted' => RW_Wallet::format_pennies(abs($tx->amount_pennies)),
                'is_credit' => $tx->amount_pennies > 0,
                'running_balance_pennies' => $tx->running_balance_pennies,
                'running_balance_formatted' => RW_Wallet::format_pennies($tx->running_balance_pennies),
                'created_at' => $tx->created_at
            );
        }, $transactions);
        
        return rest_ensure_response(array('transactions' => $formatted));
    }
    
    public static function request_topup($request) {
        $user_id = get_current_user_id();
        $amount = $request->get_param('amount');
        $notes = $request->get_param('notes');
        
        if (!$amount || $amount <= 0) {
            return new WP_Error('invalid_amount', __('Please enter a valid amount', 'rent-wallet-platform'), array('status' => 400));
        }
        
        $amount_pennies = RW_Wallet::pounds_to_pennies($amount);
        
        $result = RW_Notifications::send_topup_request($user_id, $amount_pennies, $notes);
        
        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => __('Your top-up request has been sent to the administrator.', 'rent-wallet-platform')
            ));
        }
        
        return new WP_Error('request_failed', __('Failed to send top-up request', 'rent-wallet-platform'), array('status' => 500));
    }
    
    public static function get_landlord_properties($request) {
        $user_id = get_current_user_id();
        $properties = RW_Property::get_by_landlord($user_id);
        
        $formatted = array_map(function($property) {
            $tenancies = RW_Tenancy::get_all(array(
                'property_id' => $property->id,
                'status' => 'active'
            ));
            
            return array(
                'id' => $property->id,
                'address' => RW_Property::get_full_address($property),
                'bedrooms' => $property->bedrooms,
                'property_type' => $property->property_type,
                'status' => $property->status,
                'active_tenancies' => count($tenancies)
            );
        }, $properties);
        
        return rest_ensure_response(array('properties' => $formatted));
    }
    
    public static function get_landlord_payouts($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $limit = $request->get_param('limit') ? absint($request->get_param('limit')) : 20;
        $offset = $request->get_param('offset') ? absint($request->get_param('offset')) : 0;
        
        $payouts_table = RW_Database::get_table_name('payouts');
        
        $payouts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$payouts_table} WHERE landlord_user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id,
            $limit,
            $offset
        ));
        
        $formatted = array_map(function($payout) {
            $tenancy = RW_Tenancy::get($payout->tenancy_id);
            $property = $tenancy ? RW_Property::get($tenancy->property_id) : null;
            
            return array(
                'id' => $payout->id,
                'property_address' => $property ? RW_Property::get_full_address($property) : '',
                'gross_rent_formatted' => RW_Wallet::format_pennies($payout->gross_rent_pennies),
                'landlord_fee_formatted' => RW_Wallet::format_pennies($payout->landlord_fee_pennies),
                'net_paid_out_formatted' => RW_Wallet::format_pennies($payout->net_paid_out_pennies),
                'status' => $payout->status,
                'created_at' => $payout->created_at
            );
        }, $payouts);
        
        return rest_ensure_response(array('payouts' => $formatted));
    }
    
    public static function export_landlord_payouts($request) {
        $user_id = get_current_user_id();
        
        $export = RW_Exports::export_payouts(array('landlord_user_id' => $user_id));
        
        return rest_ensure_response(array(
            'csv' => base64_encode($export['csv']),
            'filename' => $export['filename'],
            'manifest' => $export['manifest']
        ));
    }
}
