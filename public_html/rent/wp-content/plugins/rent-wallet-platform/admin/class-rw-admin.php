<?php
/**
 * Admin functionality class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Admin {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('admin_init', array(__CLASS__, 'handle_admin_actions'));
    }
    
    public static function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Rent Wallet', 'rent-wallet-platform'),
            __('Rent Wallet', 'rent-wallet-platform'),
            'manage_options',
            'rent-wallet',
            array(__CLASS__, 'render_dashboard'),
            'dashicons-money-alt',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'rent-wallet',
            __('Dashboard', 'rent-wallet-platform'),
            __('Dashboard', 'rent-wallet-platform'),
            'manage_options',
            'rent-wallet',
            array(__CLASS__, 'render_dashboard')
        );
        
        // Agencies
        add_submenu_page(
            'rent-wallet',
            __('Agencies', 'rent-wallet-platform'),
            __('Agencies', 'rent-wallet-platform'),
            'rw_manage_agencies',
            'rw-agencies',
            array(__CLASS__, 'render_agencies')
        );
        
        // Properties
        add_submenu_page(
            'rent-wallet',
            __('Properties', 'rent-wallet-platform'),
            __('Properties', 'rent-wallet-platform'),
            'rw_manage_properties',
            'rw-properties',
            array(__CLASS__, 'render_properties')
        );
        
        // Tenancies
        add_submenu_page(
            'rent-wallet',
            __('Tenancies', 'rent-wallet-platform'),
            __('Tenancies', 'rent-wallet-platform'),
            'rw_manage_tenancies',
            'rw-tenancies',
            array(__CLASS__, 'render_tenancies')
        );
        
        // Wallets & Credits
        add_submenu_page(
            'rent-wallet',
            __('Wallets', 'rent-wallet-platform'),
            __('Wallets', 'rent-wallet-platform'),
            'rw_manage_wallets',
            'rw-wallets',
            array(__CLASS__, 'render_wallets')
        );
        
        // Ledger
        add_submenu_page(
            'rent-wallet',
            __('Ledger', 'rent-wallet-platform'),
            __('Ledger', 'rent-wallet-platform'),
            'rw_view_ledger',
            'rw-ledger',
            array(__CLASS__, 'render_ledger')
        );
        
        // Payouts
        add_submenu_page(
            'rent-wallet',
            __('Payouts', 'rent-wallet-platform'),
            __('Payouts', 'rent-wallet-platform'),
            'rw_view_all_payouts',
            'rw-payouts',
            array(__CLASS__, 'render_payouts')
        );
        
        // Fee Policy
        add_submenu_page(
            'rent-wallet',
            __('Fee Policy', 'rent-wallet-platform'),
            __('Fee Policy', 'rent-wallet-platform'),
            'rw_manage_fee_policy',
            'rw-fee-policy',
            array(__CLASS__, 'render_fee_policy')
        );
        
        // Reward Tiers
        add_submenu_page(
            'rent-wallet',
            __('Reward Tiers', 'rent-wallet-platform'),
            __('Reward Tiers', 'rent-wallet-platform'),
            'rw_manage_reward_tiers',
            'rw-reward-tiers',
            array(__CLASS__, 'render_reward_tiers')
        );
        
        // Notifications
        add_submenu_page(
            'rent-wallet',
            __('Notifications', 'rent-wallet-platform'),
            __('Notifications', 'rent-wallet-platform'),
            'rw_view_notifications',
            'rw-notifications',
            array(__CLASS__, 'render_notifications')
        );
        
        // Audit Log
        add_submenu_page(
            'rent-wallet',
            __('Audit Log', 'rent-wallet-platform'),
            __('Audit Log', 'rent-wallet-platform'),
            'rw_view_audit_log',
            'rw-audit-log',
            array(__CLASS__, 'render_audit_log')
        );
        
        // Integrity Checker
        add_submenu_page(
            'rent-wallet',
            __('Verify Integrity', 'rent-wallet-platform'),
            __('Verify Integrity', 'rent-wallet-platform'),
            'rw_verify_integrity',
            'rw-integrity',
            array(__CLASS__, 'render_integrity')
        );
        
        // Exports
        add_submenu_page(
            'rent-wallet',
            __('Exports', 'rent-wallet-platform'),
            __('Exports', 'rent-wallet-platform'),
            'rw_export_all_data',
            'rw-exports',
            array(__CLASS__, 'render_exports')
        );
        
        // GDPR Tools
        add_submenu_page(
            'rent-wallet',
            __('GDPR Tools', 'rent-wallet-platform'),
            __('GDPR Tools', 'rent-wallet-platform'),
            'rw_gdpr_export',
            'rw-gdpr',
            array(__CLASS__, 'render_gdpr')
        );
        
        // Demo Data
        add_submenu_page(
            'rent-wallet',
            __('Demo Data', 'rent-wallet-platform'),
            __('Demo Data', 'rent-wallet-platform'),
            'rw_generate_demo_data',
            'rw-demo-data',
            array(__CLASS__, 'render_demo_data')
        );
    }
    
    public static function enqueue_scripts($hook) {
        if (strpos($hook, 'rent-wallet') === false && strpos($hook, 'rw-') === false) {
            return;
        }
        
        wp_enqueue_style('rw-admin', RW_PLUGIN_URL . 'admin/css/admin.css', array(), RW_VERSION);
        wp_enqueue_script('rw-admin', RW_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), RW_VERSION, true);
        
        wp_localize_script('rw-admin', 'rwAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rw_admin_nonce')
        ));
    }
    
    public static function handle_admin_actions() {
        if (!isset($_POST['rw_admin_action']) || !current_user_can('manage_options')) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['rw_nonce'], 'rw_admin_action')) {
            wp_die(__('Security check failed', 'rent-wallet-platform'));
        }
        
        $action = sanitize_text_field($_POST['rw_admin_action']);
        
        switch ($action) {
            case 'manual_credit':
                self::process_manual_credit();
                break;
            case 'create_agency':
                self::process_create_agency();
                break;
            case 'create_property':
                self::process_create_property();
                break;
            case 'create_tenancy':
                self::process_create_tenancy();
                break;
            case 'update_fee_policy':
                self::process_update_fee_policy();
                break;
            case 'update_reward_tier':
                self::process_update_reward_tier();
                break;
            case 'generate_demo_data':
                self::process_generate_demo_data();
                break;
            case 'run_rent_release':
                self::process_run_rent_release();
                break;
            case 'gdpr_export':
                self::process_gdpr_export();
                break;
            case 'gdpr_anonymise':
                self::process_gdpr_anonymise();
                break;
            case 'assign_landlord':
                self::process_assign_landlord();
                break;
        }
    }
    
    private static function process_manual_credit() {
        $tenant_id = absint($_POST['tenant_id']);
        $amount = floatval($_POST['amount']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if (!$tenant_id || $amount <= 0) {
            self::add_admin_notice('error', __('Invalid tenant or amount', 'rent-wallet-platform'));
            return;
        }
        
        $amount_pennies = RW_Wallet::pounds_to_pennies($amount);
        $result = RW_Wallet::manual_credit($tenant_id, $amount_pennies, get_current_user_id(), $notes);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', sprintf(__('Successfully credited %s to tenant wallet', 'rent-wallet-platform'), RW_Wallet::format_pennies($amount_pennies)));
        }
    }
    
    private static function process_create_agency() {
        $name = sanitize_text_field($_POST['agency_name']);
        
        if (empty($name)) {
            self::add_admin_notice('error', __('Agency name is required', 'rent-wallet-platform'));
            return;
        }
        
        $result = RW_Agency::create($name);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', __('Agency created successfully', 'rent-wallet-platform'));
        }
    }
    
    private static function process_create_property() {
        $data = array(
            'landlord_user_id' => absint($_POST['landlord_user_id']),
            'agency_id' => !empty($_POST['agency_id']) ? absint($_POST['agency_id']) : null,
            'address_line1' => sanitize_text_field($_POST['address_line1']),
            'address_line2' => sanitize_text_field($_POST['address_line2']),
            'city' => sanitize_text_field($_POST['city']),
            'postcode' => sanitize_text_field($_POST['postcode']),
            'bedrooms' => absint($_POST['bedrooms']),
            'property_type' => sanitize_text_field($_POST['property_type'])
        );
        
        $result = RW_Property::create($data);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', __('Property created successfully', 'rent-wallet-platform'));
        }
    }
    
    private static function process_create_tenancy() {
        $data = array(
            'property_id' => absint($_POST['property_id']),
            'tenant_user_id' => absint($_POST['tenant_user_id']),
            'landlord_user_id' => absint($_POST['landlord_user_id']),
            'rent_amount_pennies' => RW_Wallet::pounds_to_pennies(floatval($_POST['rent_amount'])),
            'due_day' => absint($_POST['due_day']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null
        );
        
        $result = RW_Tenancy::create($data);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', __('Tenancy created successfully', 'rent-wallet-platform'));
        }
    }
    
    private static function process_update_fee_policy() {
        $data = array(
            'tenant_fee_bps' => RW_Fee_Policy::percentage_to_bps(floatval($_POST['tenant_fee_percentage'])),
            'landlord_fee_bps' => RW_Fee_Policy::percentage_to_bps(floatval($_POST['landlord_fee_percentage'])),
            'vat_enabled' => isset($_POST['vat_enabled']) ? 1 : 0,
            'vat_bps' => RW_Fee_Policy::percentage_to_bps(floatval($_POST['vat_percentage']))
        );
        
        $result = RW_Fee_Policy::update($data);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', __('Fee policy updated successfully', 'rent-wallet-platform'));
        }
    }
    
    private static function process_update_reward_tier() {
        $tier_id = absint($_POST['tier_id']);
        $data = array(
            'display_name' => sanitize_text_field($_POST['display_name']),
            'min_buffer_months' => floatval($_POST['min_buffer_months']),
            'cashback_bps' => RW_Fee_Policy::percentage_to_bps(floatval($_POST['cashback_percentage']))
        );
        
        $result = RW_Reward_Tiers::update($tier_id, $data);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', __('Reward tier updated successfully', 'rent-wallet-platform'));
        }
    }
    
    private static function process_generate_demo_data() {
        $result = RW_Demo_Data::generate();
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', sprintf(
                __('Demo data generated: %d agency, %d landlords, %d tenants, %d properties, %d tenancies', 'rent-wallet-platform'),
                1,
                count($result['landlords']),
                count($result['tenants']),
                count($result['properties']),
                count($result['tenancies'])
            ));
        }
    }
    
    private static function process_run_rent_release() {
        $tenancy_id = isset($_POST['tenancy_id']) ? absint($_POST['tenancy_id']) : null;
        
        $result = RW_Rent_Release::run_manual($tenancy_id);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } elseif ($result === 'arrears') {
            self::add_admin_notice('warning', __('Tenancy is in arrears - insufficient funds', 'rent-wallet-platform'));
        } elseif (is_array($result) && isset($result['processed'])) {
            self::add_admin_notice('success', sprintf(
                __('Rent release completed: %d processed, %d successful, %d arrears, %d errors', 'rent-wallet-platform'),
                $result['processed'],
                $result['successful'],
                $result['arrears'],
                $result['errors']
            ));
        } else {
            self::add_admin_notice('success', __('Rent release processed successfully', 'rent-wallet-platform'));
        }
    }
    
    private static function process_gdpr_export() {
        $user_id = absint($_POST['user_id']);
        RW_GDPR::download_export($user_id);
    }
    
    private static function process_gdpr_anonymise() {
        $user_id = absint($_POST['user_id']);
        $result = RW_GDPR::anonymise_user($user_id);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', $result['message']);
        }
    }
    
    private static function process_assign_landlord() {
        $agency_id = absint($_POST['agency_id']);
        $landlord_id = absint($_POST['landlord_id']);
        
        $result = RW_Agency::assign_landlord($agency_id, $landlord_id);
        
        if (is_wp_error($result)) {
            self::add_admin_notice('error', $result->get_error_message());
        } else {
            self::add_admin_notice('success', __('Landlord assigned to agency successfully', 'rent-wallet-platform'));
        }
    }
    
    private static function add_admin_notice($type, $message) {
        add_settings_error('rw_messages', 'rw_message', $message, $type);
    }
    
    // Render methods
    public static function render_dashboard() {
        global $wpdb;
        
        // Get KPIs
        $total_tenants = count(get_users(array('role' => 'rw_tenant')));
        $total_landlords = count(get_users(array('role' => 'rw_landlord')));
        $total_properties = RW_Property::get_count();
        $total_tenancies = RW_Tenancy::get_count(array('status' => 'active'));
        
        $wallets_table = RW_Database::get_table_name('wallets');
        $total_wallet_balance = $wpdb->get_var("SELECT COALESCE(SUM(balance_pennies), 0) FROM {$wallets_table} WHERE owner_role = 'tenant'");
        
        $payouts_table = RW_Database::get_table_name('payouts');
        $total_payouts = $wpdb->get_var("SELECT COALESCE(SUM(net_paid_out_pennies), 0) FROM {$payouts_table}");
        
        $arrears_count = count(RW_Tenancy::get_arrears_tenancies());
        
        include RW_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    public static function render_agencies() {
        $agencies = RW_Agency::get_all();
        $landlords = get_users(array('role' => 'rw_landlord'));
        include RW_PLUGIN_DIR . 'admin/views/agencies.php';
    }
    
    public static function render_properties() {
        $properties = RW_Property::get_all(array('limit' => 100));
        $landlords = get_users(array('role' => 'rw_landlord'));
        $agencies = RW_Agency::get_all();
        $property_types = RW_Property::get_property_types();
        include RW_PLUGIN_DIR . 'admin/views/properties.php';
    }
    
    public static function render_tenancies() {
        $tenancies = RW_Tenancy::get_all(array('limit' => 100));
        $properties = RW_Property::get_all(array('limit' => 1000));
        $tenants = get_users(array('role' => 'rw_tenant'));
        $landlords = get_users(array('role' => 'rw_landlord'));
        include RW_PLUGIN_DIR . 'admin/views/tenancies.php';
    }
    
    public static function render_wallets() {
        $wallets = RW_Wallet::get_all_wallets(array('limit' => 100));
        $tenants = get_users(array('role' => 'rw_tenant'));
        include RW_PLUGIN_DIR . 'admin/views/wallets.php';
    }
    
    public static function render_ledger() {
        $transactions = RW_Ledger::get_transactions(array('limit' => 100));
        include RW_PLUGIN_DIR . 'admin/views/ledger.php';
    }
    
    public static function render_payouts() {
        global $wpdb;
        $payouts_table = RW_Database::get_table_name('payouts');
        $payouts = $wpdb->get_results("SELECT * FROM {$payouts_table} ORDER BY created_at DESC LIMIT 100");
        include RW_PLUGIN_DIR . 'admin/views/payouts.php';
    }
    
    public static function render_fee_policy() {
        $policy = RW_Fee_Policy::get();
        include RW_PLUGIN_DIR . 'admin/views/fee-policy.php';
    }
    
    public static function render_reward_tiers() {
        $tiers = RW_Reward_Tiers::get_all();
        include RW_PLUGIN_DIR . 'admin/views/reward-tiers.php';
    }
    
    public static function render_notifications() {
        $notifications = RW_Notifications::get_logs(array('limit' => 100));
        include RW_PLUGIN_DIR . 'admin/views/notifications.php';
    }
    
    public static function render_audit_log() {
        $entries = RW_Audit::get_entries(array('limit' => 100));
        include RW_PLUGIN_DIR . 'admin/views/audit-log.php';
    }
    
    public static function render_integrity() {
        $verification_result = null;
        if (isset($_GET['verify']) && $_GET['verify'] === '1') {
            $verification_result = RW_Integrity::verify_all();
        }
        include RW_PLUGIN_DIR . 'admin/views/integrity.php';
    }
    
    public static function render_exports() {
        // Handle export downloads
        if (isset($_GET['export']) && wp_verify_nonce($_GET['_wpnonce'], 'rw_export')) {
            $type = sanitize_text_field($_GET['export']);
            switch ($type) {
                case 'tenancies':
                    $export = RW_Exports::export_tenancies();
                    break;
                case 'ledger':
                    $export = RW_Exports::export_ledger();
                    break;
                case 'payouts':
                    $export = RW_Exports::export_payouts();
                    break;
                case 'audit':
                    $export = RW_Exports::export_audit_log();
                    break;
                case 'properties':
                    $export = RW_Exports::export_properties();
                    break;
            }
            if (isset($export)) {
                RW_Exports::download_with_manifest($export);
            }
        }
        include RW_PLUGIN_DIR . 'admin/views/exports.php';
    }
    
    public static function render_gdpr() {
        $users = get_users(array('role__in' => array('rw_tenant', 'rw_landlord', 'rw_agency_staff')));
        include RW_PLUGIN_DIR . 'admin/views/gdpr.php';
    }
    
    public static function render_demo_data() {
        include RW_PLUGIN_DIR . 'admin/views/demo-data.php';
    }
}
