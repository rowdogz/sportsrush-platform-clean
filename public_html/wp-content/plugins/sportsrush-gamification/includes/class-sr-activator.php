<?php
/**
 * Plugin Activation and Database Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Activator {
    
    const DB_VERSION_OPTION = 'sr_gamification_db_version';
    
    public static function activate() {
        self::create_tables();
        self::insert_default_achievements();
        self::schedule_cron_events();
        
        update_option(self::DB_VERSION_OPTION, SR_GAMIFICATION_DB_VERSION);
        
        // Initialize default feature flags
        if (!get_option(SR_Feature_Flags::OPTION_KEY)) {
            $flags = new SR_Feature_Flags();
            $flags->set_flags($flags->get_default_flags());
        }
        
        // Initialize default settings
        self::set_default_settings();
        
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        self::unschedule_cron_events();
        flush_rewrite_rules();
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Daily Picks table
        $table_name = $wpdb->prefix . 'sr_daily_picks';
        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            pick_date DATE NOT NULL,
            competition_id INT UNSIGNED NULL,
            fixture_id INT UNSIGNED NULL,
            pick_type VARCHAR(30) NOT NULL DEFAULT 'winner',
            pick_payload LONGTEXT,
            lock_time DATETIME NOT NULL,
            settle_time DATETIME NULL,
            settled TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_pick_date (pick_date),
            KEY idx_pick_date (pick_date)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Daily Pick Entries table
        $table_name = $wpdb->prefix . 'sr_daily_pick_entries';
        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            daily_pick_id INT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            entry_payload LONGTEXT NOT NULL,
            points_awarded INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_pick (daily_pick_id, user_id),
            KEY idx_daily_pick (daily_pick_id),
            KEY idx_user (user_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // User Streaks table
        $table_name = $wpdb->prefix . 'sr_user_streaks';
        $sql = "CREATE TABLE $table_name (
            user_id BIGINT UNSIGNED PRIMARY KEY,
            login_streak_count INT UNSIGNED NOT NULL DEFAULT 0,
            login_last_date DATE NULL,
            prediction_streak_count INT UNSIGNED NOT NULL DEFAULT 0,
            prediction_streak_last_fixture_id INT UNSIGNED NULL,
            shields_available INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql);
        
        // Achievements table
        $table_name = $wpdb->prefix . 'sr_achievements';
        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            achievement_key VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(100) DEFAULT 'trophy',
            points INT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_key (achievement_key)
        ) $charset_collate;";
        dbDelta($sql);
        
        // User Achievements table
        $table_name = $wpdb->prefix . 'sr_user_achievements';
        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            achievement_id INT UNSIGNED NOT NULL,
            awarded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_achievement (user_id, achievement_id),
            KEY idx_user (user_id),
            KEY idx_achievement (achievement_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // User Rivals table
        $table_name = $wpdb->prefix . 'sr_user_rivals';
        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            context_type VARCHAR(20) NOT NULL,
            context_id INT UNSIGNED NULL,
            rival_user_id BIGINT UNSIGNED NULL,
            chasing_user_id BIGINT UNSIGNED NULL,
            computed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_context (user_id, context_type, context_id),
            KEY idx_user (user_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Leaderboard Snapshots table
        $table_name = $wpdb->prefix . 'sr_leaderboard_snapshots';
        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            context_type VARCHAR(20) NOT NULL,
            context_id INT UNSIGNED NULL,
            competition_id INT UNSIGNED NULL,
            round_number INT NULL,
            snapshot_payload LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_context (context_type, context_id, competition_id, round_number),
            KEY idx_created (created_at)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Notifications table
        $table_name = $wpdb->prefix . 'sr_notifications';
        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            notification_type VARCHAR(30) NOT NULL,
            payload LONGTEXT,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_unread (user_id, is_read, created_at),
            KEY idx_user (user_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Banter Summaries table
        $table_name = $wpdb->prefix . 'sr_banter_summaries';
        $sql = "CREATE TABLE $table_name (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            league_id INT UNSIGNED NOT NULL,
            week_start DATE NOT NULL,
            summary_payload LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_league_week (league_id, week_start),
            KEY idx_league (league_id)
        ) $charset_collate;";
        dbDelta($sql);
    }
    
    public static function insert_default_achievements() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sr_achievements';
        
        $achievements = array(
            array(
                'achievement_key' => 'perfect_round',
                'name' => 'Perfect Round',
                'description' => 'Get all predictions correct in a single round.',
                'icon' => 'star',
                'points' => 50,
            ),
            array(
                'achievement_key' => 'underdog_hunter',
                'name' => 'Underdog Hunter',
                'description' => 'Correctly predict 5 underdog victories.',
                'icon' => 'target',
                'points' => 30,
            ),
            array(
                'achievement_key' => 'big_climber',
                'name' => 'Big Climber',
                'description' => 'Gain 10 or more positions in a single round.',
                'icon' => 'trending-up',
                'points' => 25,
            ),
            array(
                'achievement_key' => 'consistent',
                'name' => 'Consistent Performer',
                'description' => 'Finish in the top 25% for 5 consecutive rounds.',
                'icon' => 'award',
                'points' => 40,
            ),
            array(
                'achievement_key' => 'daily_grinder',
                'name' => 'Daily Grinder',
                'description' => 'Maintain a 7-day login streak.',
                'icon' => 'flame',
                'points' => 20,
            ),
            array(
                'achievement_key' => 'prediction_streak_5',
                'name' => 'Hot Streak',
                'description' => 'Get 5 correct predictions in a row.',
                'icon' => 'zap',
                'points' => 15,
            ),
            array(
                'achievement_key' => 'prediction_streak_10',
                'name' => 'On Fire',
                'description' => 'Get 10 correct predictions in a row.',
                'icon' => 'flame',
                'points' => 35,
            ),
            array(
                'achievement_key' => 'first_prediction',
                'name' => 'Getting Started',
                'description' => 'Make your first prediction.',
                'icon' => 'play',
                'points' => 5,
            ),
            array(
                'achievement_key' => 'daily_pick_winner',
                'name' => 'Daily Pick Winner',
                'description' => 'Win your first Daily Pick.',
                'icon' => 'calendar',
                'points' => 10,
            ),
            array(
                'achievement_key' => 'league_champion',
                'name' => 'League Champion',
                'description' => 'Finish first in a private league.',
                'icon' => 'crown',
                'points' => 100,
            ),
            array(
                'achievement_key' => 'top_10_finish',
                'name' => 'Top 10 Finish',
                'description' => 'Finish in the top 10 overall.',
                'icon' => 'medal',
                'points' => 50,
            ),
            array(
                'achievement_key' => 'joker_master',
                'name' => 'Joker Master',
                'description' => 'Successfully use a joker on a correct prediction.',
                'icon' => 'sparkles',
                'points' => 15,
            ),
        );
        
        foreach ($achievements as $achievement) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE achievement_key = %s",
                $achievement['achievement_key']
            ));
            
            if (!$exists) {
                $wpdb->insert($table_name, $achievement);
            }
        }
    }
    
    public static function set_default_settings() {
        $defaults = array(
            'sr_daily_pick_points_correct' => 3,
            'sr_daily_pick_points_partial' => 1,
            'sr_streak_bonus_7_days' => 5,
            'sr_streak_bonus_14_days' => 10,
            'sr_streak_bonus_30_days' => 25,
            'sr_shield_cost_points' => 50,
            'sr_deadline_warning_minutes' => 60,
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    public static function schedule_cron_events() {
        // Set timezone to UK
        $timezone = new DateTimeZone('Europe/London');
        
        // Daily Pick Generator - 09:00 UK time
        if (!wp_next_scheduled('sr_generate_daily_pick')) {
            $next_run = self::get_next_scheduled_time(9, 0, $timezone);
            wp_schedule_event($next_run, 'daily', 'sr_generate_daily_pick');
        }
        
        // Snapshot Builder - 01:00 UK time
        if (!wp_next_scheduled('sr_build_snapshots')) {
            $next_run = self::get_next_scheduled_time(1, 0, $timezone);
            wp_schedule_event($next_run, 'daily', 'sr_build_snapshots');
        }
        
        // Achievements Awarding - 02:00 UK time
        if (!wp_next_scheduled('sr_award_achievements')) {
            $next_run = self::get_next_scheduled_time(2, 0, $timezone);
            wp_schedule_event($next_run, 'daily', 'sr_award_achievements');
        }
        
        // Banter Summaries - Monday 09:00 UK time
        if (!wp_next_scheduled('sr_generate_banter')) {
            $next_run = self::get_next_monday_time(9, 0, $timezone);
            wp_schedule_event($next_run, 'weekly', 'sr_generate_banter');
        }
        
        // Settle Daily Picks - every hour
        if (!wp_next_scheduled('sr_settle_daily_picks')) {
            wp_schedule_event(time(), 'hourly', 'sr_settle_daily_picks');
        }
        
        // Deadline warnings - every 15 minutes
        if (!wp_next_scheduled('sr_check_deadlines')) {
            wp_schedule_event(time(), 'fifteen_minutes', 'sr_check_deadlines');
        }
    }
    
    public static function unschedule_cron_events() {
        $events = array(
            'sr_generate_daily_pick',
            'sr_build_snapshots',
            'sr_award_achievements',
            'sr_generate_banter',
            'sr_settle_daily_picks',
            'sr_check_deadlines',
        );
        
        foreach ($events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
    }
    
    private static function get_next_scheduled_time($hour, $minute, $timezone) {
        $now = new DateTime('now', $timezone);
        $scheduled = new DateTime('now', $timezone);
        $scheduled->setTime($hour, $minute, 0);
        
        if ($scheduled <= $now) {
            $scheduled->modify('+1 day');
        }
        
        return $scheduled->getTimestamp();
    }
    
    private static function get_next_monday_time($hour, $minute, $timezone) {
        $now = new DateTime('now', $timezone);
        $scheduled = new DateTime('next monday', $timezone);
        $scheduled->setTime($hour, $minute, 0);
        
        if ($now->format('N') == 1 && $now->format('H') < $hour) {
            $scheduled = new DateTime('today', $timezone);
            $scheduled->setTime($hour, $minute, 0);
        }
        
        return $scheduled->getTimestamp();
    }
}
