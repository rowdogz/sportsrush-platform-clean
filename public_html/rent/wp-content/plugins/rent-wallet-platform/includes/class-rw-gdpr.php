<?php
/**
 * GDPR compliance tools class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_GDPR {
    
    public static function export_user_data($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'rent-wallet-platform'));
        }
        
        $data = array(
            'export_info' => array(
                'exported_at' => current_time('mysql'),
                'user_id' => $user_id,
                'export_type' => 'gdpr_data_export'
            ),
            'user_profile' => self::get_user_profile_data($user_id),
            'wallets' => self::get_user_wallets_data($user_id),
            'transactions' => self::get_user_transactions_data($user_id),
            'tenancies' => self::get_user_tenancies_data($user_id),
            'properties' => self::get_user_properties_data($user_id),
            'payouts' => self::get_user_payouts_data($user_id),
            'notifications' => self::get_user_notifications_data($user_id),
            'audit_entries' => self::get_user_audit_data($user_id)
        );
        
        // Log the export
        RW_Audit::log('gdpr_export', 'user', $user_id, array(
            'exported_by' => get_current_user_id()
        ));
        
        return $data;
    }
    
    private static function get_user_profile_data($user_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        $profile_table = RW_Database::get_table_name('user_profile');
        
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$profile_table} WHERE wp_user_id = %d",
            $user_id
        ));
        
        return array(
            'wordpress_user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'registered' => $user->user_registered,
                'roles' => $user->roles
            ),
            'rent_wallet_profile' => $profile ? (array) $profile : null
        );
    }
    
    private static function get_user_wallets_data($user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('wallets');
        
        $wallets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE owner_user_id = %d",
            $user_id
        ));
        
        return array_map(function($wallet) {
            return array(
                'id' => $wallet->id,
                'role' => $wallet->owner_role,
                'currency' => $wallet->currency,
                'balance_pennies' => $wallet->balance_pennies,
                'balance_formatted' => RW_Wallet::format_pennies($wallet->balance_pennies),
                'created_at' => $wallet->created_at,
                'updated_at' => $wallet->updated_at
            );
        }, $wallets);
    }
    
    private static function get_user_transactions_data($user_id) {
        $transactions = RW_Ledger::get_transactions(array(
            'user_id' => $user_id,
            'limit' => 100000
        ));
        
        return array_map(function($tx) {
            return array(
                'id' => $tx->id,
                'wallet_id' => $tx->wallet_id,
                'type' => $tx->type,
                'amount_pennies' => $tx->amount_pennies,
                'running_balance_pennies' => $tx->running_balance_pennies,
                'tenancy_id' => $tx->tenancy_id,
                'metadata' => $tx->metadata_json ? json_decode($tx->metadata_json, true) : null,
                'created_at' => $tx->created_at
            );
        }, $transactions);
    }
    
    private static function get_user_tenancies_data($user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('tenancies');
        
        // Get tenancies where user is tenant or landlord
        $tenancies = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE tenant_user_id = %d OR landlord_user_id = %d",
            $user_id,
            $user_id
        ));
        
        return array_map(function($tenancy) use ($user_id) {
            $property = RW_Property::get($tenancy->property_id);
            return array(
                'id' => $tenancy->id,
                'role' => $tenancy->tenant_user_id == $user_id ? 'tenant' : 'landlord',
                'property_address' => $property ? RW_Property::get_full_address($property) : null,
                'rent_amount_pennies' => $tenancy->rent_amount_pennies,
                'due_day' => $tenancy->due_day,
                'start_date' => $tenancy->start_date,
                'end_date' => $tenancy->end_date,
                'status' => $tenancy->status,
                'created_at' => $tenancy->created_at
            );
        }, $tenancies);
    }
    
    private static function get_user_properties_data($user_id) {
        $properties = RW_Property::get_by_landlord($user_id);
        
        return array_map(function($property) {
            return array(
                'id' => $property->id,
                'address' => RW_Property::get_full_address($property),
                'bedrooms' => $property->bedrooms,
                'property_type' => $property->property_type,
                'status' => $property->status,
                'created_at' => $property->created_at
            );
        }, $properties);
    }
    
    private static function get_user_payouts_data($user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('payouts');
        
        $payouts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE landlord_user_id = %d",
            $user_id
        ));
        
        return array_map(function($payout) {
            return array(
                'id' => $payout->id,
                'tenancy_id' => $payout->tenancy_id,
                'gross_rent_pennies' => $payout->gross_rent_pennies,
                'landlord_fee_pennies' => $payout->landlord_fee_pennies,
                'net_paid_out_pennies' => $payout->net_paid_out_pennies,
                'status' => $payout->status,
                'created_at' => $payout->created_at
            );
        }, $payouts);
    }
    
    private static function get_user_notifications_data($user_id) {
        $notifications = RW_Notifications::get_logs(array(
            'user_id' => $user_id,
            'limit' => 10000
        ));
        
        return array_map(function($notification) {
            return array(
                'id' => $notification->id,
                'channel' => $notification->channel,
                'template_key' => $notification->template_key,
                'subject' => $notification->subject,
                'status' => $notification->status,
                'created_at' => $notification->created_at
            );
        }, $notifications);
    }
    
    private static function get_user_audit_data($user_id) {
        $entries = RW_Audit::get_entries(array(
            'actor_user_id' => $user_id,
            'limit' => 10000
        ));
        
        return array_map(function($entry) {
            return array(
                'id' => $entry->id,
                'action' => $entry->action,
                'entity' => $entry->entity,
                'entity_id' => $entry->entity_id,
                'ip' => $entry->ip,
                'created_at' => $entry->created_at
            );
        }, $entries);
    }
    
    public static function anonymise_user($user_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'rent-wallet-platform'));
        }
        
        // Generate anonymous identifier
        $anon_id = 'anon_' . wp_generate_password(12, false);
        
        // Store original data for audit before anonymising
        $original_data = array(
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name
        );
        
        // Anonymise WordPress user data
        wp_update_user(array(
            'ID' => $user_id,
            'user_email' => $anon_id . '@anonymised.local',
            'display_name' => $anon_id,
            'first_name' => 'Anonymised',
            'last_name' => 'User'
        ));
        
        // Anonymise user profile
        $profile_table = RW_Database::get_table_name('user_profile');
        $wpdb->update(
            $profile_table,
            array('role_label' => 'anonymised'),
            array('wp_user_id' => $user_id),
            array('%s'),
            array('%d')
        );
        
        // Note: We do NOT delete or modify financial ledger entries or tenancy records
        // as these are required for financial record retention
        
        // Log the anonymisation
        RW_Audit::log('gdpr_anonymise', 'user', $user_id, array(
            'anonymised_by' => get_current_user_id(),
            'anon_id' => $anon_id,
            'original_email_hash' => md5($original_data['user_email'])
        ));
        
        return array(
            'success' => true,
            'anon_id' => $anon_id,
            'message' => __('User data has been anonymised. Financial records have been retained for compliance.', 'rent-wallet-platform')
        );
    }
    
    public static function download_export($user_id) {
        $data = self::export_user_data($user_id);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        $json = wp_json_encode($data, JSON_PRETTY_PRINT);
        $filename = 'user_data_export_' . $user_id . '_' . date('Y-m-d_His') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $json;
        exit;
    }
}
