<?php
/**
 * Plugin Name: SportsRush Gamification
 * Plugin URI: https://sportsrush.co.uk
 * Description: Gamification & DAU layer for SportsRush - rivals, position changes, daily picks, streaks, achievements, banter summaries, and notifications.
 * Version: 1.0.0
 * Author: SportsRush
 * Author URI: https://sportsrush.co.uk
 * Text Domain: sportsrush-gamification
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SR_GAMIFICATION_VERSION', '1.0.7');
define('SR_GAMIFICATION_DB_VERSION', '1.0.0');
define('SR_GAMIFICATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SR_GAMIFICATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SR_GAMIFICATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-feature-flags.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-activator.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-snapshots.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-rivals.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-mini-leaderboards.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-daily-pick.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-streaks.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-achievements.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-banter.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-notifications.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-cron.php';
require_once SR_GAMIFICATION_PLUGIN_DIR . 'includes/class-sr-points-integration.php';

if (is_admin()) {
    require_once SR_GAMIFICATION_PLUGIN_DIR . 'admin/class-sr-admin.php';
}

require_once SR_GAMIFICATION_PLUGIN_DIR . 'public/class-sr-public.php';

/**
 * Main plugin class
 */
class SportsRush_Gamification {
    
    private static $instance = null;
    
    public $feature_flags;
    public $snapshots;
    public $rivals;
    public $mini_leaderboards;
    public $daily_pick;
    public $streaks;
    public $achievements;
    public $banter;
    public $notifications;
    public $cron;
    public $points_integration;
    public $admin;
    public $public;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array('SR_Activator', 'activate'));
        register_deactivation_hook(__FILE__, array('SR_Activator', 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
    }
    
    public function init() {
        // Check if Football Pool plugin is active
        if (!class_exists('Football_Pool')) {
            add_action('admin_notices', array($this, 'football_pool_missing_notice'));
            return;
        }
        
        // Initialize components
        $this->feature_flags = new SR_Feature_Flags();
        $this->snapshots = new SR_Snapshots();
        $this->rivals = new SR_Rivals();
        $this->mini_leaderboards = new SR_Mini_Leaderboards();
        $this->daily_pick = new SR_Daily_Pick();
        $this->streaks = new SR_Streaks();
        $this->achievements = new SR_Achievements();
        $this->banter = new SR_Banter();
        $this->notifications = new SR_Notifications();
        $this->cron = new SR_Cron();
        $this->points_integration = new SR_Points_Integration();
        
        if (is_admin()) {
            $this->admin = new SR_Admin();
        }
        
        $this->public = new SR_Public();
        
        // AJAX handlers
        $this->register_ajax_handlers();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'sportsrush-gamification',
            false,
            dirname(SR_GAMIFICATION_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function football_pool_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('SportsRush Gamification requires the Football Pool plugin to be installed and activated.', 'sportsrush-gamification'); ?></p>
        </div>
        <?php
    }
    
    private function register_ajax_handlers() {
        // Daily pick entry
        add_action('wp_ajax_sr_submit_daily_pick', array($this->daily_pick, 'ajax_submit_entry'));
        
        // Mark notification as read
        add_action('wp_ajax_sr_mark_notification_read', array($this->notifications, 'ajax_mark_read'));
        add_action('wp_ajax_sr_mark_all_notifications_read', array($this->notifications, 'ajax_mark_all_read'));
        
        // Get notifications
        add_action('wp_ajax_sr_get_notifications', array($this->notifications, 'ajax_get_notifications'));
        
        // Admin AJAX
        add_action('wp_ajax_sr_run_cron_now', array($this->cron, 'ajax_run_now'));
    }
    
    /**
     * Get the Football Pool database prefix
     */
    public static function get_fp_prefix() {
        global $wpdb;
        return 'pool_' . $wpdb->prefix;
    }
    
    /**
     * Get WordPress table prefix
     */
    public static function get_wp_prefix() {
        global $wpdb;
        return $wpdb->prefix;
    }
}

/**
 * Returns the main instance of SportsRush_Gamification
 */
function SR_Gamification() {
    return SportsRush_Gamification::get_instance();
}

// Initialize the plugin
SR_Gamification();
