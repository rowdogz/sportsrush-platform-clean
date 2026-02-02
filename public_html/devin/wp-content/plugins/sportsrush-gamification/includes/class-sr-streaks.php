<?php
/**
 * Streaks System - Login and Prediction Streaks with Shields
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Streaks {
    
    private $table_name;
    private $fp_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sr_user_streaks';
        $this->fp_prefix = 'pool_' . $wpdb->prefix;
        
        // Hook into user login/visit
        add_action('wp_loaded', array($this, 'track_login_streak'));
    }
    
    /**
     * Track login streak when user visits
     */
    public function track_login_streak() {
        if (!SR_Gamification()->feature_flags->is_enabled('streaks_enabled')) {
            return;
        }
        
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $today = current_time('Y-m-d');
        
        $streak = $this->get_user_streak($user_id);
        
        if (!$streak) {
            // Create new streak record
            $this->create_streak_record($user_id, $today);
            return;
        }
        
        // Check if already logged in today
        if ($streak->login_last_date === $today) {
            return;
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));
        
        if ($streak->login_last_date === $yesterday) {
            // Continue streak
            $this->update_login_streak($user_id, $streak->login_streak_count + 1, $today);
            
            // Check for streak achievements
            $this->check_streak_achievements($user_id, $streak->login_streak_count + 1);
        } elseif ($streak->login_last_date && $streak->login_last_date < $yesterday) {
            // Streak broken - check if shield available
            if ($streak->shields_available > 0) {
                // Use shield to protect streak
                $this->use_shield($user_id);
                $this->update_login_streak($user_id, $streak->login_streak_count + 1, $today);
            } else {
                // Reset streak
                $this->update_login_streak($user_id, 1, $today);
            }
        } else {
            // First login or same day
            $this->update_login_streak($user_id, 1, $today);
        }
    }
    
    /**
     * Get user's streak data
     */
    public function get_user_streak($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Create new streak record
     */
    private function create_streak_record($user_id, $date) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'login_streak_count' => 1,
                'login_last_date' => $date,
                'prediction_streak_count' => 0,
                'prediction_streak_last_fixture_id' => null,
                'shields_available' => 0,
                'updated_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%d', '%d', '%d', '%s')
        );
    }
    
    /**
     * Update login streak
     */
    private function update_login_streak($user_id, $count, $date) {
        global $wpdb;
        
        // Get the previous count to check for milestone crossing
        $streak = $this->get_user_streak($user_id);
        $previous_count = $streak ? $streak->login_streak_count : 0;
        
        $wpdb->update(
            $this->table_name,
            array(
                'login_streak_count' => $count,
                'login_last_date' => $date,
                'updated_at' => current_time('mysql'),
            ),
            array('user_id' => $user_id),
            array('%d', '%s', '%s'),
            array('%d')
        );
        
        // Check if we crossed a 7-day milestone
        $this->check_and_award_streak_points($user_id, $previous_count, $count);
    }
    
    /**
     * Check and award points for 7-day streak milestones
     */
    private function check_and_award_streak_points($user_id, $previous_count, $new_count) {
        // Calculate which 7-day milestones were crossed
        $previous_milestone = floor($previous_count / 7);
        $new_milestone = floor($new_count / 7);
        
        // If we crossed a new milestone, award points
        if ($new_milestone > $previous_milestone) {
            $points_per_milestone = (int) get_option('sr_login_streak_points', 3);
            $milestones_crossed = $new_milestone - $previous_milestone;
            $total_points = $points_per_milestone * $milestones_crossed;
            
            if ($total_points > 0) {
                $this->award_streak_points($user_id, $total_points, $new_count);
            }
        }
    }
    
    /**
     * Award streak points to user and add to their total
     */
    private function award_streak_points($user_id, $points, $streak_count) {
        global $wpdb;
        
        // Store the streak points in a tracking table or option
        // We'll use user meta to track total streak points awarded
        $current_streak_points = (int) get_user_meta($user_id, 'sr_streak_points_total', true);
        update_user_meta($user_id, 'sr_streak_points_total', $current_streak_points + $points);
        
        // Create notification for the user
        if (SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            SR_Gamification()->notifications->create_notification(
                $user_id,
                'streak_bonus',
                array(
                    'message' => sprintf(__('You earned %d points for your %d-day login streak!', 'sportsrush-gamification'), $points, $streak_count),
                    'points' => $points,
                    'streak_count' => $streak_count,
                )
            );
        }
    }
    
    /**
     * Get user's total streak points (for points integration)
     */
    public function get_user_streak_points($user_id) {
        return (int) get_user_meta($user_id, 'sr_streak_points_total', true);
    }
    
    /**
     * Use a shield
     */
    private function use_shield($user_id) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
            SET shields_available = shields_available - 1, updated_at = %s 
            WHERE user_id = %d AND shields_available > 0",
            current_time('mysql'),
            $user_id
        ));
    }
    
    /**
     * Award shield to user
     */
    public function award_shield($user_id, $count = 1) {
        global $wpdb;
        
        $streak = $this->get_user_streak($user_id);
        
        if (!$streak) {
            $this->create_streak_record($user_id, null);
        }
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
            SET shields_available = shields_available + %d, updated_at = %s 
            WHERE user_id = %d",
            $count,
            current_time('mysql'),
            $user_id
        ));
    }
    
    /**
     * Track prediction streak
     */
    public function track_prediction_streak($user_id, $fixture_id, $is_correct) {
        if (!SR_Gamification()->feature_flags->is_enabled('streaks_enabled')) {
            return;
        }
        
        global $wpdb;
        
        $streak = $this->get_user_streak($user_id);
        
        if (!$streak) {
            $this->create_streak_record($user_id, current_time('Y-m-d'));
            $streak = $this->get_user_streak($user_id);
        }
        
        // Check if this fixture was already processed
        if ($streak->prediction_streak_last_fixture_id == $fixture_id) {
            return;
        }
        
        if ($is_correct) {
            // Increment streak
            $new_count = $streak->prediction_streak_count + 1;
            
            $wpdb->update(
                $this->table_name,
                array(
                    'prediction_streak_count' => $new_count,
                    'prediction_streak_last_fixture_id' => $fixture_id,
                    'updated_at' => current_time('mysql'),
                ),
                array('user_id' => $user_id),
                array('%d', '%d', '%s'),
                array('%d')
            );
            
            // Check for prediction streak achievements
            $this->check_prediction_streak_achievements($user_id, $new_count);
        } else {
            // Check if shield available
            if ($streak->shields_available > 0) {
                // Use shield - pause streak but don't reset
                $this->use_shield($user_id);
                
                $wpdb->update(
                    $this->table_name,
                    array(
                        'prediction_streak_last_fixture_id' => $fixture_id,
                        'updated_at' => current_time('mysql'),
                    ),
                    array('user_id' => $user_id),
                    array('%d', '%s'),
                    array('%d')
                );
            } else {
                // Reset streak
                $wpdb->update(
                    $this->table_name,
                    array(
                        'prediction_streak_count' => 0,
                        'prediction_streak_last_fixture_id' => $fixture_id,
                        'updated_at' => current_time('mysql'),
                    ),
                    array('user_id' => $user_id),
                    array('%d', '%d', '%s'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * Check and award login streak achievements
     */
    private function check_streak_achievements($user_id, $streak_count) {
        if (!SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            return;
        }
        
        $achievements = SR_Gamification()->achievements;
        
        if ($streak_count >= 7) {
            $achievements->maybe_award_achievement($user_id, 'daily_grinder');
        }
        
        // Award bonus points for milestones
        $bonus_7 = (int) get_option('sr_streak_bonus_7_days', 5);
        $bonus_14 = (int) get_option('sr_streak_bonus_14_days', 10);
        $bonus_30 = (int) get_option('sr_streak_bonus_30_days', 25);
        
        // These would integrate with a points system if implemented
    }
    
    /**
     * Check and award prediction streak achievements
     */
    private function check_prediction_streak_achievements($user_id, $streak_count) {
        if (!SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            return;
        }
        
        $achievements = SR_Gamification()->achievements;
        
        if ($streak_count >= 5) {
            $achievements->maybe_award_achievement($user_id, 'prediction_streak_5');
        }
        
        if ($streak_count >= 10) {
            $achievements->maybe_award_achievement($user_id, 'prediction_streak_10');
        }
    }
    
    /**
     * Render streak widget
     */
    public function render_widget($user_id = null) {
        if (!SR_Gamification()->feature_flags->is_enabled('streaks_enabled')) {
            return '';
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return '';
        }
        
        $streak = $this->get_user_streak($user_id);
        
        if (!$streak) {
            $streak = (object) array(
                'login_streak_count' => 0,
                'prediction_streak_count' => 0,
                'shields_available' => 0,
            );
        }
        
        ob_start();
        ?>
        <div class="sr-streaks-widget">
            <h4 class="sr-streaks-title">
                <span class="sr-icon">&#128293;</span>
                <?php esc_html_e('Your Streaks', 'sportsrush-gamification'); ?>
            </h4>
            
            <div class="sr-streak-cards">
                <div class="sr-streak-card sr-login-streak">
                    <div class="sr-streak-icon">&#128197;</div>
                    <div class="sr-streak-info">
                        <span class="sr-streak-count"><?php echo esc_html($streak->login_streak_count); ?></span>
                        <span class="sr-streak-label"><?php esc_html_e('Day Login Streak', 'sportsrush-gamification'); ?></span>
                    </div>
                </div>
                
                <div class="sr-streak-card sr-prediction-streak">
                    <div class="sr-streak-icon">&#9989;</div>
                    <div class="sr-streak-info">
                        <span class="sr-streak-count"><?php echo esc_html($streak->prediction_streak_count); ?></span>
                        <span class="sr-streak-label"><?php esc_html_e('Correct Predictions', 'sportsrush-gamification'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="sr-shields-info">
                <span class="sr-shield-icon">&#128737;</span>
                <span class="sr-shields-count"><?php echo esc_html($streak->shields_available); ?></span>
                <span class="sr-shields-label"><?php esc_html_e('Shields Available', 'sportsrush-gamification'); ?></span>
                <span class="sr-shields-help" title="<?php esc_attr_e('Shields protect your streak when you miss a day or get a prediction wrong.', 'sportsrush-gamification'); ?>">?</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get streak leaderboard
     */
    public function get_streak_leaderboard($type = 'login', $limit = 10) {
        global $wpdb;
        
        $column = $type === 'login' ? 'login_streak_count' : 'prediction_streak_count';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.user_id, s.{$column} as streak_count, u.display_name
            FROM {$this->table_name} s
            JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
            WHERE s.{$column} > 0
            ORDER BY s.{$column} DESC
            LIMIT %d",
            $limit
        ), ARRAY_A);
    }
}
