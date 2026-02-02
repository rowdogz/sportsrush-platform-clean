<?php
/**
 * Feature Flags Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Feature_Flags {
    
    const OPTION_KEY = 'sr_feature_flags';
    
    private $flags = array();
    
    private $default_flags = array(
        'rivals_enabled' => true,
        'position_changes_enabled' => true,
        'daily_pick_enabled' => true,
        'streaks_enabled' => true,
        'mini_leaderboards_enabled' => true,
        'achievements_enabled' => true,
        'banter_summaries_enabled' => true,
        'smart_notifications_enabled' => true,
    );
    
    public function __construct() {
        $this->load_flags();
    }
    
    private function load_flags() {
        $saved_flags = get_option(self::OPTION_KEY, array());
        $this->flags = wp_parse_args($saved_flags, $this->default_flags);
    }
    
    public function is_enabled($flag_name) {
        return isset($this->flags[$flag_name]) && $this->flags[$flag_name] === true;
    }
    
    public function get_all_flags() {
        return $this->flags;
    }
    
    public function get_default_flags() {
        return $this->default_flags;
    }
    
    public function set_flag($flag_name, $value) {
        if (array_key_exists($flag_name, $this->default_flags)) {
            $this->flags[$flag_name] = (bool) $value;
            return $this->save_flags();
        }
        return false;
    }
    
    public function set_flags($flags) {
        foreach ($flags as $flag_name => $value) {
            if (array_key_exists($flag_name, $this->default_flags)) {
                $this->flags[$flag_name] = (bool) $value;
            }
        }
        return $this->save_flags();
    }
    
    private function save_flags() {
        return update_option(self::OPTION_KEY, $this->flags);
    }
    
    public function get_flag_labels() {
        return array(
            'rivals_enabled' => array(
                'label' => __('Rivals System', 'sportsrush-gamification'),
                'description' => __('Show "Your Rival" and "Chasing You" users on leaderboards.', 'sportsrush-gamification'),
            ),
            'position_changes_enabled' => array(
                'label' => __('Position Changes', 'sportsrush-gamification'),
                'description' => __('Display position change indicators (arrows and deltas) on leaderboards.', 'sportsrush-gamification'),
            ),
            'daily_pick_enabled' => array(
                'label' => __('Daily Pick', 'sportsrush-gamification'),
                'description' => __('Enable the daily prediction game for bonus points.', 'sportsrush-gamification'),
            ),
            'streaks_enabled' => array(
                'label' => __('Streaks', 'sportsrush-gamification'),
                'description' => __('Track login and prediction streaks with shield protection.', 'sportsrush-gamification'),
            ),
            'mini_leaderboards_enabled' => array(
                'label' => __('Mini Leaderboards', 'sportsrush-gamification'),
                'description' => __('Show current round, last 5 fixtures, and last 7 days leaderboard views.', 'sportsrush-gamification'),
            ),
            'achievements_enabled' => array(
                'label' => __('Achievements & Badges', 'sportsrush-gamification'),
                'description' => __('Award achievements and display badges on profiles and leaderboards.', 'sportsrush-gamification'),
            ),
            'banter_summaries_enabled' => array(
                'label' => __('Weekly Banter Summaries', 'sportsrush-gamification'),
                'description' => __('Generate weekly fun summaries for private leagues.', 'sportsrush-gamification'),
            ),
            'smart_notifications_enabled' => array(
                'label' => __('Smart Notifications', 'sportsrush-gamification'),
                'description' => __('Show in-app notifications for rivals, deadlines, and achievements.', 'sportsrush-gamification'),
            ),
        );
    }
}
