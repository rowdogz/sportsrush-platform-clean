<?php
/**
 * Roles and capabilities management
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Roles {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'register_roles'), 5);
    }
    
    public static function register_roles() {
        // Add custom roles
        self::add_tenant_role();
        self::add_landlord_role();
        self::add_agency_staff_role();
        
        // Add capabilities to admin
        self::add_admin_capabilities();
    }
    
    private static function add_tenant_role() {
        $capabilities = array(
            'read' => true,
            'rw_view_own_wallet' => true,
            'rw_view_own_tenancy' => true,
            'rw_request_topup' => true,
            'rw_view_own_transactions' => true,
        );
        
        add_role('rw_tenant', __('Tenant', 'rent-wallet-platform'), $capabilities);
    }
    
    private static function add_landlord_role() {
        $capabilities = array(
            'read' => true,
            'rw_view_own_wallet' => true,
            'rw_view_own_properties' => true,
            'rw_view_own_tenancies' => true,
            'rw_view_own_payouts' => true,
            'rw_export_own_data' => true,
        );
        
        add_role('rw_landlord', __('Landlord', 'rent-wallet-platform'), $capabilities);
    }
    
    private static function add_agency_staff_role() {
        $capabilities = array(
            'read' => true,
            'rw_view_assigned_landlords' => true,
            'rw_view_assigned_properties' => true,
            'rw_manage_assigned_properties' => true,
            'rw_view_assigned_tenancies' => true,
            'rw_export_assigned_data' => true,
        );
        
        add_role('rw_agency_staff', __('Agency Staff', 'rent-wallet-platform'), $capabilities);
    }
    
    private static function add_admin_capabilities() {
        $admin = get_role('administrator');
        if (!$admin) {
            return;
        }
        
        $capabilities = array(
            // Wallet management
            'rw_manage_wallets',
            'rw_credit_wallets',
            'rw_view_all_wallets',
            
            // Property management
            'rw_manage_properties',
            'rw_view_all_properties',
            
            // Tenancy management
            'rw_manage_tenancies',
            'rw_view_all_tenancies',
            
            // Agency management
            'rw_manage_agencies',
            'rw_view_all_agencies',
            
            // Payout management
            'rw_view_all_payouts',
            
            // Fee policy
            'rw_manage_fee_policy',
            
            // Reward tiers
            'rw_manage_reward_tiers',
            
            // Ledger and audit
            'rw_view_ledger',
            'rw_view_audit_log',
            'rw_verify_integrity',
            
            // Notifications
            'rw_view_notifications',
            
            // Exports
            'rw_export_all_data',
            
            // GDPR
            'rw_gdpr_export',
            'rw_gdpr_anonymise',
            
            // Demo data
            'rw_generate_demo_data',
            
            // User profiles
            'rw_manage_user_profiles',
        );
        
        foreach ($capabilities as $cap) {
            $admin->add_cap($cap);
        }
    }
    
    public static function get_user_rw_role($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return null;
        }
        
        if (in_array('administrator', $user->roles)) {
            return 'admin';
        }
        if (in_array('rw_tenant', $user->roles)) {
            return 'tenant';
        }
        if (in_array('rw_landlord', $user->roles)) {
            return 'landlord';
        }
        if (in_array('rw_agency_staff', $user->roles)) {
            return 'agency_staff';
        }
        
        return null;
    }
    
    public static function user_can_access_property($user_id, $property_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Admins can access all
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        $property = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rw_properties WHERE id = %d",
            $property_id
        ));
        
        if (!$property) {
            return false;
        }
        
        // Landlord owns the property
        if (in_array('rw_landlord', $user->roles) && $property->landlord_user_id == $user_id) {
            return true;
        }
        
        // Agency staff assigned to the landlord
        if (in_array('rw_agency_staff', $user->roles)) {
            $agency_id = self::get_user_agency_id($user_id);
            if ($agency_id && $property->agency_id == $agency_id) {
                return true;
            }
            
            // Check if assigned to landlord
            $assigned = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rw_agency_assignments 
                WHERE agency_id = %d AND landlord_user_id = %d",
                $agency_id,
                $property->landlord_user_id
            ));
            if ($assigned) {
                return true;
            }
        }
        
        // Tenant with active tenancy
        if (in_array('rw_tenant', $user->roles)) {
            $tenancy = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rw_tenancies 
                WHERE property_id = %d AND tenant_user_id = %d AND status = 'active'",
                $property_id,
                $user_id
            ));
            if ($tenancy) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function user_can_access_tenancy($user_id, $tenancy_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Admins can access all
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        $tenancy = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rw_tenancies WHERE id = %d",
            $tenancy_id
        ));
        
        if (!$tenancy) {
            return false;
        }
        
        // Tenant of the tenancy
        if (in_array('rw_tenant', $user->roles) && $tenancy->tenant_user_id == $user_id) {
            return true;
        }
        
        // Landlord of the tenancy
        if (in_array('rw_landlord', $user->roles) && $tenancy->landlord_user_id == $user_id) {
            return true;
        }
        
        // Agency staff
        if (in_array('rw_agency_staff', $user->roles)) {
            return self::user_can_access_property($user_id, $tenancy->property_id);
        }
        
        return false;
    }
    
    public static function get_user_agency_id($user_id) {
        // Get agency ID from user meta
        return get_user_meta($user_id, 'rw_agency_id', true);
    }
    
    public static function set_user_agency_id($user_id, $agency_id) {
        update_user_meta($user_id, 'rw_agency_id', $agency_id);
    }
}
