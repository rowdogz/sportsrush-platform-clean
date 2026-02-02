<?php
/**
 * Achievements and Badges System
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Achievements {
    
    private $achievements_table;
    private $user_achievements_table;
    private $fp_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->achievements_table = $wpdb->prefix . 'sr_achievements';
        $this->user_achievements_table = $wpdb->prefix . 'sr_user_achievements';
        $this->fp_prefix = 'pool_' . $wpdb->prefix;
    }
    
    /**
     * Get all achievements
     */
    public function get_all_achievements() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->achievements_table} ORDER BY points DESC, name ASC",
            ARRAY_A
        );
    }
    
    /**
     * Get achievement by key
     */
    public function get_achievement_by_key($key) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->achievements_table} WHERE achievement_key = %s",
            $key
        ), ARRAY_A);
    }
    
    /**
     * Get user's achievements
     */
    public function get_user_achievements($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, ua.awarded_at
            FROM {$this->user_achievements_table} ua
            JOIN {$this->achievements_table} a ON ua.achievement_id = a.id
            WHERE ua.user_id = %d
            ORDER BY ua.awarded_at DESC",
            $user_id
        ), ARRAY_A);
    }
    
    /**
     * Check if user has achievement
     */
    public function user_has_achievement($user_id, $achievement_key) {
        global $wpdb;
        
        $achievement = $this->get_achievement_by_key($achievement_key);
        
        if (!$achievement) {
            return false;
        }
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->user_achievements_table} 
            WHERE user_id = %d AND achievement_id = %d",
            $user_id,
            $achievement['id']
        ));
    }
    
    /**
     * Award achievement to user
     */
    public function award_achievement($user_id, $achievement_key) {
        if (!SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            return false;
        }
        
        global $wpdb;
        
        $achievement = $this->get_achievement_by_key($achievement_key);
        
        if (!$achievement) {
            return false;
        }
        
        // Check if already awarded
        if ($this->user_has_achievement($user_id, $achievement_key)) {
            return false;
        }
        
        $result = $wpdb->insert(
            $this->user_achievements_table,
            array(
                'user_id' => $user_id,
                'achievement_id' => $achievement['id'],
                'awarded_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s')
        );
        
        if ($result) {
            // Create notification
            if (SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
                SR_Gamification()->notifications->create_notification(
                    $user_id,
                    'achievement_earned',
                    array(
                        'achievement_key' => $achievement_key,
                        'achievement_name' => $achievement['name'],
                        'achievement_icon' => $achievement['icon'],
                        'points' => $achievement['points'],
                    )
                );
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Maybe award achievement (checks if not already awarded)
     */
    public function maybe_award_achievement($user_id, $achievement_key) {
        if ($this->user_has_achievement($user_id, $achievement_key)) {
            return false;
        }
        
        return $this->award_achievement($user_id, $achievement_key);
    }
    
    /**
     * Process achievements for all users (cron job)
     */
    public function process_achievements() {
        if (!SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            return;
        }
        
        global $wpdb;
        
        // Get all users with predictions
        $users = $wpdb->get_col(
            "SELECT DISTINCT user_id FROM {$this->fp_prefix}predictions"
        );
        
        foreach ($users as $user_id) {
            $this->check_user_achievements($user_id);
        }
    }
    
    /**
     * Check and award achievements for a user
     */
    public function check_user_achievements($user_id) {
        // First prediction
        $this->check_first_prediction($user_id);
        
        // Perfect round
        $this->check_perfect_round($user_id);
        
        // Big climber
        $this->check_big_climber($user_id);
        
        // Consistent performer
        $this->check_consistent($user_id);
        
        // Joker master
        $this->check_joker_master($user_id);
    }
    
    /**
     * Check first prediction achievement
     */
    private function check_first_prediction($user_id) {
        global $wpdb;
        
        $has_prediction = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->fp_prefix}predictions WHERE user_id = %d",
            $user_id
        ));
        
        if ($has_prediction > 0) {
            $this->maybe_award_achievement($user_id, 'first_prediction');
        }
    }
    
    /**
     * Check perfect round achievement
     */
    private function check_perfect_round($user_id) {
        global $wpdb;
        
        $scorehistory_table = $this->fp_prefix . 'scorehistory_s1_t1';
        
        // Get rounds where user got all predictions correct
        $perfect_rounds = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT m.round) 
            FROM {$scorehistory_table} sh
            JOIN {$this->fp_prefix}matches m ON sh.source_id = m.id AND sh.type = 0
            WHERE sh.user_id = %d
            GROUP BY m.matchtype_id, m.round
            HAVING COUNT(*) = SUM(CASE WHEN sh.full > 0 THEN 1 ELSE 0 END)
            AND COUNT(*) >= 3",
            $user_id
        ));
        
        if ($perfect_rounds > 0) {
            $this->maybe_award_achievement($user_id, 'perfect_round');
        }
    }
    
    /**
     * Check big climber achievement
     */
    private function check_big_climber($user_id) {
        $snapshots = SR_Gamification()->snapshots;
        $change = $snapshots->get_position_change($user_id, 'global');
        
        if ($change['direction'] === 'up' && $change['change'] >= 10) {
            $this->maybe_award_achievement($user_id, 'big_climber');
        }
    }
    
    /**
     * Check consistent performer achievement
     */
    private function check_consistent($user_id) {
        global $wpdb;
        
        // This would require tracking top 25% finishes over 5 rounds
        // Simplified check for now - look at recent snapshots
        $snapshots_table = $wpdb->prefix . 'sr_leaderboard_snapshots';
        
        $recent_snapshots = $wpdb->get_results($wpdb->prepare(
            "SELECT snapshot_payload FROM {$snapshots_table}
            WHERE context_type = 'global'
            ORDER BY created_at DESC
            LIMIT 5"
        ));
        
        if (count($recent_snapshots) < 5) {
            return;
        }
        
        $top_25_count = 0;
        
        foreach ($recent_snapshots as $snapshot) {
            $rankings = json_decode($snapshot->snapshot_payload, true);
            $total_users = count($rankings);
            $top_25_threshold = ceil($total_users * 0.25);
            
            foreach ($rankings as $entry) {
                if ((int) $entry['user_id'] === (int) $user_id && $entry['ranking'] <= $top_25_threshold) {
                    $top_25_count++;
                    break;
                }
            }
        }
        
        if ($top_25_count >= 5) {
            $this->maybe_award_achievement($user_id, 'consistent');
        }
    }
    
    /**
     * Check joker master achievement
     */
    private function check_joker_master($user_id) {
        global $wpdb;
        
        $scorehistory_table = $this->fp_prefix . 'scorehistory_s1_t1';
        
        // Check if user has used a joker on a correct prediction
        $joker_success = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$scorehistory_table}
            WHERE user_id = %d
            AND joker_used > 0
            AND full > 0",
            $user_id
        ));
        
        if ($joker_success > 0) {
            $this->maybe_award_achievement($user_id, 'joker_master');
        }
    }
    
    /**
     * Get icon HTML for achievement
     */
    public function get_icon_html($icon, $size = 'medium') {
        $sizes = array(
            'small' => '16px',
            'medium' => '24px',
            'large' => '32px',
        );
        
        $icon_size = isset($sizes[$size]) ? $sizes[$size] : $sizes['medium'];
        
        // Map icon names to emoji/unicode
        $icon_map = array(
            'trophy' => '&#127942;',
            'star' => '&#11088;',
            'target' => '&#127919;',
            'trending-up' => '&#128200;',
            'award' => '&#127941;',
            'flame' => '&#128293;',
            'zap' => '&#9889;',
            'play' => '&#9654;',
            'calendar' => '&#128197;',
            'crown' => '&#128081;',
            'medal' => '&#127941;',
            'sparkles' => '&#10024;',
        );
        
        $icon_char = isset($icon_map[$icon]) ? $icon_map[$icon] : $icon_map['trophy'];
        
        return sprintf(
            '<span class="sr-achievement-icon" style="font-size: %s;">%s</span>',
            esc_attr($icon_size),
            $icon_char
        );
    }
    
    /**
     * Render user badges (compact view for leaderboard)
     */
    public function render_user_badges($user_id, $limit = 3) {
        if (!SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            return '';
        }
        
        $achievements = $this->get_user_achievements($user_id);
        
        if (empty($achievements)) {
            return '';
        }
        
        $achievements = array_slice($achievements, 0, $limit);
        
        ob_start();
        ?>
        <span class="sr-user-badges">
            <?php foreach ($achievements as $achievement): ?>
            <span class="sr-badge" title="<?php echo esc_attr($achievement['name']); ?>">
                <?php echo $this->get_icon_html($achievement['icon'], 'small'); ?>
            </span>
            <?php endforeach; ?>
            <?php if (count($this->get_user_achievements($user_id)) > $limit): ?>
            <span class="sr-badge-more">+<?php echo count($this->get_user_achievements($user_id)) - $limit; ?></span>
            <?php endif; ?>
        </span>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render achievements widget (full view for profile)
     */
    public function render_achievements_widget($user_id = null) {
        if (!SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            return '';
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return '';
        }
        
        $user_achievements = $this->get_user_achievements($user_id);
        $all_achievements = $this->get_all_achievements();
        
        // Create lookup of earned achievements
        $earned_keys = array();
        foreach ($user_achievements as $ua) {
            $earned_keys[$ua['achievement_key']] = $ua['awarded_at'];
        }
        
        ob_start();
        ?>
        <div class="sr-achievements-widget">
            <h4 class="sr-achievements-title">
                <span class="sr-icon">&#127942;</span>
                <?php esc_html_e('Achievements', 'sportsrush-gamification'); ?>
                <span class="sr-achievements-count"><?php echo count($user_achievements); ?>/<?php echo count($all_achievements); ?></span>
            </h4>
            
            <div class="sr-achievements-grid">
                <?php foreach ($all_achievements as $achievement): 
                    $is_earned = isset($earned_keys[$achievement['achievement_key']]);
                ?>
                <div class="sr-achievement-card <?php echo $is_earned ? 'sr-earned' : 'sr-locked'; ?>">
                    <div class="sr-achievement-icon-wrap">
                        <?php echo $this->get_icon_html($achievement['icon'], 'large'); ?>
                    </div>
                    <div class="sr-achievement-info">
                        <span class="sr-achievement-name"><?php echo esc_html($achievement['name']); ?></span>
                        <span class="sr-achievement-desc"><?php echo esc_html($achievement['description']); ?></span>
                        <?php if ($is_earned): ?>
                        <span class="sr-achievement-date">
                            <?php echo esc_html(date_i18n('j M Y', strtotime($earned_keys[$achievement['achievement_key']]))); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="sr-achievement-points">
                        <?php echo esc_html($achievement['points']); ?> pts
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get total achievement points for user
     */
    public function get_user_achievement_points($user_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(a.points), 0)
            FROM {$this->user_achievements_table} ua
            JOIN {$this->achievements_table} a ON ua.achievement_id = a.id
            WHERE ua.user_id = %d",
            $user_id
        ));
    }
}
