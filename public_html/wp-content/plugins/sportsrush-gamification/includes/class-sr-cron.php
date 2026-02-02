<?php
/**
 * Cron Job Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Cron {
    
    public function __construct() {
        // Register custom cron schedules
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
        
        // Hook cron events
        add_action('sr_generate_daily_pick', array($this, 'run_daily_pick_generator'));
        add_action('sr_build_snapshots', array($this, 'run_snapshot_builder'));
        add_action('sr_award_achievements', array($this, 'run_achievements_processor'));
        add_action('sr_generate_banter', array($this, 'run_banter_generator'));
        add_action('sr_settle_daily_picks', array($this, 'run_daily_pick_settler'));
        add_action('sr_check_deadlines', array($this, 'run_deadline_checker'));
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules($schedules) {
        $schedules['fifteen_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 Minutes', 'sportsrush-gamification'),
        );
        
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'sportsrush-gamification'),
        );
        
        return $schedules;
    }
    
    /**
     * Run daily pick generator
     */
    public function run_daily_pick_generator() {
        if (!SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            return;
        }
        
        SR_Gamification()->daily_pick->generate_daily_pick();
        
        // Create notifications for all users
        if (SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            global $wpdb;
            $fp_prefix = 'pool_' . $wpdb->prefix;
            
            $users = $wpdb->get_col(
                "SELECT DISTINCT user_id FROM {$fp_prefix}predictions"
            );
            
            foreach ($users as $user_id) {
                SR_Gamification()->notifications->create_notification(
                    $user_id,
                    'daily_pick_ready',
                    array('message' => __("Today's Daily Pick is ready! Make your prediction.", 'sportsrush-gamification'))
                );
            }
        }
        
        $this->log_cron_run('daily_pick_generator');
    }
    
    /**
     * Run snapshot builder
     */
    public function run_snapshot_builder() {
        if (!SR_Gamification()->feature_flags->is_enabled('position_changes_enabled')) {
            return;
        }
        
        SR_Gamification()->snapshots->build_all_snapshots();
        
        // Also refresh rivals
        if (SR_Gamification()->feature_flags->is_enabled('rivals_enabled')) {
            SR_Gamification()->rivals->refresh_all_rivals();
        }
        
        // Check for rival overtakes
        if (SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            SR_Gamification()->notifications->check_rival_overtakes();
        }
        
        $this->log_cron_run('snapshot_builder');
    }
    
    /**
     * Run achievements processor
     */
    public function run_achievements_processor() {
        if (!SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            return;
        }
        
        SR_Gamification()->achievements->process_achievements();
        
        $this->log_cron_run('achievements_processor');
    }
    
    /**
     * Run banter generator
     */
    public function run_banter_generator() {
        if (!SR_Gamification()->feature_flags->is_enabled('banter_summaries_enabled')) {
            return;
        }
        
        SR_Gamification()->banter->generate_all_summaries();
        
        $this->log_cron_run('banter_generator');
    }
    
    /**
     * Run daily pick settler
     */
    public function run_daily_pick_settler() {
        if (!SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            return;
        }
        
        SR_Gamification()->daily_pick->settle_daily_picks();
        
        $this->log_cron_run('daily_pick_settler');
    }
    
    /**
     * Run deadline checker
     */
    public function run_deadline_checker() {
        if (!SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            return;
        }
        
        SR_Gamification()->notifications->check_deadline_warnings();
        
        $this->log_cron_run('deadline_checker');
    }
    
    /**
     * Log cron run
     */
    private function log_cron_run($job_name) {
        $log = get_option('sr_cron_log', array());
        $log[$job_name] = current_time('mysql');
        
        // Keep only last 10 entries per job
        if (count($log) > 20) {
            $log = array_slice($log, -20, 20, true);
        }
        
        update_option('sr_cron_log', $log);
    }
    
    /**
     * Get cron log
     */
    public function get_cron_log() {
        return get_option('sr_cron_log', array());
    }
    
    /**
     * Get next scheduled times
     */
    public function get_scheduled_times() {
        $events = array(
            'sr_generate_daily_pick' => __('Daily Pick Generator', 'sportsrush-gamification'),
            'sr_build_snapshots' => __('Snapshot Builder', 'sportsrush-gamification'),
            'sr_award_achievements' => __('Achievements Processor', 'sportsrush-gamification'),
            'sr_generate_banter' => __('Banter Generator', 'sportsrush-gamification'),
            'sr_settle_daily_picks' => __('Daily Pick Settler', 'sportsrush-gamification'),
            'sr_check_deadlines' => __('Deadline Checker', 'sportsrush-gamification'),
        );
        
        $scheduled = array();
        
        foreach ($events as $hook => $label) {
            $timestamp = wp_next_scheduled($hook);
            $scheduled[$hook] = array(
                'label' => $label,
                'next_run' => $timestamp ? date_i18n('Y-m-d H:i:s', $timestamp) : __('Not scheduled', 'sportsrush-gamification'),
                'timestamp' => $timestamp,
            );
        }
        
        return $scheduled;
    }
    
    /**
     * AJAX handler for running cron job manually
     */
    public function ajax_run_now() {
        check_ajax_referer('sr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'sportsrush-gamification')));
        }
        
        $job = isset($_POST['job']) ? sanitize_text_field($_POST['job']) : '';
        
        $valid_jobs = array(
            'daily_pick' => 'run_daily_pick_generator',
            'snapshots' => 'run_snapshot_builder',
            'achievements' => 'run_achievements_processor',
            'banter' => 'run_banter_generator',
            'settle_picks' => 'run_daily_pick_settler',
            'deadlines' => 'run_deadline_checker',
        );
        
        if (!isset($valid_jobs[$job])) {
            wp_send_json_error(array('message' => __('Invalid job.', 'sportsrush-gamification')));
        }
        
        $method = $valid_jobs[$job];
        $this->$method();
        
        wp_send_json_success(array(
            'message' => sprintf(__('%s completed successfully.', 'sportsrush-gamification'), $job),
            'log' => $this->get_cron_log(),
        ));
    }
    
    /**
     * Manually trigger all cron jobs (for testing)
     */
    public function run_all_jobs() {
        $this->run_daily_pick_generator();
        $this->run_snapshot_builder();
        $this->run_achievements_processor();
        $this->run_banter_generator();
        $this->run_daily_pick_settler();
        $this->run_deadline_checker();
    }
}
