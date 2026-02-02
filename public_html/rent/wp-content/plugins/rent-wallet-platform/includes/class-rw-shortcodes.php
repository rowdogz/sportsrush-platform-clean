<?php
/**
 * Shortcodes class for frontend display
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Shortcodes {
    
    public static function init() {
        add_shortcode('rw_tenant_dashboard', array(__CLASS__, 'tenant_dashboard'));
        add_shortcode('rw_landlord_dashboard', array(__CLASS__, 'landlord_dashboard'));
        add_shortcode('rw_agency_portal', array(__CLASS__, 'agency_portal'));
        add_shortcode('rw_login_form', array(__CLASS__, 'login_form'));
    }
    
    public static function tenant_dashboard($atts) {
        if (!is_user_logged_in()) {
            return self::login_required_message();
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Check if user is a tenant
        if (!in_array('rw_tenant', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<div class="rw-error">' . __('Access denied. This dashboard is for tenants only.', 'rent-wallet-platform') . '</div>';
        }
        
        // Get wallet
        $wallet = RW_Wallet::get_or_create_wallet($user_id, 'tenant');
        
        // Get active tenancy
        $tenancy = RW_Tenancy::get_active_tenancy_for_tenant($user_id);
        
        // Get tier progress
        $tier_progress = RW_Reward_Tiers::get_tier_progress($user_id);
        
        // Get recent transactions
        $transactions = RW_Ledger::get_wallet_transactions($wallet->id, 10);
        
        // Get total cashback earned
        $total_cashback = RW_Ledger::get_user_cashback_total($user_id);
        
        ob_start();
        include RW_PLUGIN_DIR . 'templates/tenant-dashboard.php';
        return ob_get_clean();
    }
    
    public static function landlord_dashboard($atts) {
        if (!is_user_logged_in()) {
            return self::login_required_message();
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Check if user is a landlord
        if (!in_array('rw_landlord', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<div class="rw-error">' . __('Access denied. This dashboard is for landlords only.', 'rent-wallet-platform') . '</div>';
        }
        
        // Get properties
        $properties = RW_Property::get_by_landlord($user_id);
        
        // Get tenancies
        $tenancies = RW_Tenancy::get_by_landlord($user_id);
        
        // Get payouts
        global $wpdb;
        $payouts_table = RW_Database::get_table_name('payouts');
        $payouts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$payouts_table} WHERE landlord_user_id = %d ORDER BY created_at DESC LIMIT 20",
            $user_id
        ));
        
        // Calculate totals
        $total_rent_received = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(net_paid_out_pennies), 0) FROM {$payouts_table} WHERE landlord_user_id = %d",
            $user_id
        ));
        
        ob_start();
        include RW_PLUGIN_DIR . 'templates/landlord-dashboard.php';
        return ob_get_clean();
    }
    
    public static function agency_portal($atts) {
        if (!is_user_logged_in()) {
            return self::login_required_message();
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Check if user is agency staff
        if (!in_array('rw_agency_staff', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<div class="rw-error">' . __('Access denied. This portal is for agency staff only.', 'rent-wallet-platform') . '</div>';
        }
        
        // Get agency
        $agency_id = RW_Roles::get_user_agency_id($user_id);
        $agency = $agency_id ? RW_Agency::get($agency_id) : null;
        
        if (!$agency && !in_array('administrator', $user->roles)) {
            return '<div class="rw-error">' . __('You are not assigned to any agency.', 'rent-wallet-platform') . '</div>';
        }
        
        // Get assigned landlords
        $landlords = $agency ? RW_Agency::get_assigned_landlords($agency_id) : array();
        
        // Get properties for assigned landlords
        $properties = array();
        $tenancies = array();
        
        foreach ($landlords as $landlord) {
            $landlord_properties = RW_Property::get_by_landlord($landlord->ID);
            $properties = array_merge($properties, $landlord_properties);
            
            $landlord_tenancies = RW_Tenancy::get_by_landlord($landlord->ID);
            $tenancies = array_merge($tenancies, $landlord_tenancies);
        }
        
        // Get arrears tenancies
        $arrears = RW_Tenancy::get_arrears_tenancies();
        
        ob_start();
        include RW_PLUGIN_DIR . 'templates/agency-portal.php';
        return ob_get_clean();
    }
    
    public static function login_form($atts) {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $dashboard_url = home_url();
            
            if (in_array('rw_tenant', $user->roles)) {
                $dashboard_url = home_url('/tenant-dashboard/');
            } elseif (in_array('rw_landlord', $user->roles)) {
                $dashboard_url = home_url('/landlord-dashboard/');
            } elseif (in_array('rw_agency_staff', $user->roles)) {
                $dashboard_url = home_url('/agency-portal/');
            } elseif (in_array('administrator', $user->roles)) {
                $dashboard_url = admin_url();
            }
            
            return '<div class="rw-logged-in">
                <p>' . sprintf(__('Welcome, %s!', 'rent-wallet-platform'), esc_html($user->display_name)) . '</p>
                <p><a href="' . esc_url($dashboard_url) . '" class="rw-button">' . __('Go to Dashboard', 'rent-wallet-platform') . '</a></p>
                <p><a href="' . esc_url(wp_logout_url(home_url())) . '">' . __('Logout', 'rent-wallet-platform') . '</a></p>
            </div>';
        }
        
        $args = array(
            'echo' => false,
            'redirect' => home_url(),
            'form_id' => 'rw-login-form',
            'label_username' => __('Email or Username', 'rent-wallet-platform'),
            'label_password' => __('Password', 'rent-wallet-platform'),
            'label_remember' => __('Remember Me', 'rent-wallet-platform'),
            'label_log_in' => __('Log In', 'rent-wallet-platform'),
            'remember' => true
        );
        
        return '<div class="rw-login-form">' . wp_login_form($args) . '</div>';
    }
    
    private static function login_required_message() {
        return '<div class="rw-login-required">
            <p>' . __('Please log in to access this page.', 'rent-wallet-platform') . '</p>
            <p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="rw-button">' . __('Log In', 'rent-wallet-platform') . '</a></p>
        </div>';
    }
}
