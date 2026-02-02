<?php
/**
 * Plugin Name: Rent Wallet Platform
 * Plugin URI: https://sportsrush.co.uk/rent
 * Description: A comprehensive rent wallet management system with simulated wallets, tenant/landlord management, and automated rent releases.
 * Version: 1.0.0
 * Author: Rent Wallet Team
 * Author URI: https://sportsrush.co.uk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rent-wallet-platform
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RW_VERSION', '1.0.0');
define('RW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RW_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include core files
require_once RW_PLUGIN_DIR . 'includes/class-rw-database.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-roles.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-wallet.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-ledger.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-audit.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-integrity.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-agency.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-property.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-tenancy.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-fee-policy.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-reward-tiers.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-rent-release.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-notifications.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-exports.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-gdpr.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-demo-data.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-shortcodes.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-rest-api.php';

// Admin files
if (is_admin()) {
    require_once RW_PLUGIN_DIR . 'admin/class-rw-admin.php';
}

class Rent_Wallet_Platform {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array('RW_Database', 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Register cron
        add_action('rw_daily_rent_release', array('RW_Rent_Release', 'process_daily_releases'));
        
        // Initialize components
        add_action('init', array($this, 'init_components'));
    }
    
    public function init() {
        // Register custom roles
        RW_Roles::init();
    }
    
    public function init_components() {
        // Initialize shortcodes
        RW_Shortcodes::init();
        
        // Initialize REST API
        RW_REST_API::init();
        
        // Initialize admin
        if (is_admin()) {
            RW_Admin::init();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('rent-wallet-platform', false, dirname(RW_PLUGIN_BASENAME) . '/languages');
    }
    
    public function deactivate() {
        // Clear scheduled cron
        wp_clear_scheduled_hook('rw_daily_rent_release');
    }
    
    public static function get_hmac_secret() {
        if (defined('RW_HMAC_SECRET')) {
            return RW_HMAC_SECRET;
        }
        return get_option('rw_hmac_secret', '');
    }
}

// Initialize the plugin
Rent_Wallet_Platform::get_instance();
