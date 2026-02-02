<?php
/**
 * Rivals System - Track user above and below in rankings
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Rivals {
    
    private $table_name;
    private $cache_duration = 3600; // 1 hour
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sr_user_rivals';
    }
    
    /**
     * Get rivals for a user in a specific context
     */
    public function get_rivals($user_id, $context_type = 'global', $context_id = null) {
        if (!SR_Gamification()->feature_flags->is_enabled('rivals_enabled')) {
            return null;
        }
        
        global $wpdb;
        
        // Check if we have fresh cached data
        $cached = $this->get_cached_rivals($user_id, $context_type, $context_id);
        
        if ($cached && $this->is_cache_fresh($cached['computed_at'])) {
            return $cached;
        }
        
        // Compute fresh rivals
        return $this->compute_rivals($user_id, $context_type, $context_id);
    }
    
    /**
     * Get cached rivals from database
     */
    private function get_cached_rivals($user_id, $context_type, $context_id) {
        global $wpdb;
        
        $where = array(
            "user_id = %d",
            "context_type = %s"
        );
        $params = array($user_id, $context_type);
        
        if ($context_id !== null) {
            $where[] = "context_id = %d";
            $params[] = $context_id;
        } else {
            $where[] = "context_id IS NULL";
        }
        
        $where_clause = implode(' AND ', $where);
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE $where_clause",
            ...$params
        ), ARRAY_A);
        
        if ($result) {
            // Get user details for rivals
            if ($result['rival_user_id']) {
                $result['rival'] = $this->get_user_details($result['rival_user_id']);
            }
            if ($result['chasing_user_id']) {
                $result['chasing'] = $this->get_user_details($result['chasing_user_id']);
            }
        }
        
        return $result;
    }
    
    /**
     * Check if cached data is still fresh
     */
    private function is_cache_fresh($computed_at) {
        $computed_time = strtotime($computed_at);
        return (time() - $computed_time) < $this->cache_duration;
    }
    
    /**
     * Compute rivals for a user
     */
    public function compute_rivals($user_id, $context_type = 'global', $context_id = null) {
        $snapshots = SR_Gamification()->snapshots;
        
        // Get competition_id for league context
        $competition_id = null;
        if ($context_type === 'league' && $context_id) {
            global $wpdb;
            $competition_id = $wpdb->get_var($wpdb->prepare(
                "SELECT matchtype_id FROM custom_competitions WHERE id = %d",
                $context_id
            ));
        } elseif ($context_type === 'competition') {
            $competition_id = $context_id;
        }
        
        $rankings = $snapshots->get_current_rankings($context_type, $context_id, $competition_id);
        
        if (empty($rankings)) {
            return null;
        }
        
        $user_position = null;
        $rival_user_id = null;
        $chasing_user_id = null;
        
        // Find user's position
        foreach ($rankings as $index => $entry) {
            if ((int) $entry['user_id'] === (int) $user_id) {
                $user_position = $index;
                break;
            }
        }
        
        if ($user_position === null) {
            return null;
        }
        
        // User above (rival)
        if ($user_position > 0) {
            $rival_user_id = $rankings[$user_position - 1]['user_id'];
        }
        
        // User below (chasing)
        if ($user_position < count($rankings) - 1) {
            $chasing_user_id = $rankings[$user_position + 1]['user_id'];
        }
        
        // Save to database
        $this->save_rivals($user_id, $context_type, $context_id, $rival_user_id, $chasing_user_id);
        
        $result = array(
            'user_id' => $user_id,
            'context_type' => $context_type,
            'context_id' => $context_id,
            'rival_user_id' => $rival_user_id,
            'chasing_user_id' => $chasing_user_id,
            'computed_at' => current_time('mysql'),
            'user_ranking' => $rankings[$user_position],
        );
        
        if ($rival_user_id) {
            $result['rival'] = $this->get_user_details($rival_user_id);
            $result['rival']['ranking'] = $rankings[$user_position - 1];
        }
        
        if ($chasing_user_id) {
            $result['chasing'] = $this->get_user_details($chasing_user_id);
            $result['chasing']['ranking'] = $rankings[$user_position + 1];
        }
        
        return $result;
    }
    
    /**
     * Save rivals to database
     */
    private function save_rivals($user_id, $context_type, $context_id, $rival_user_id, $chasing_user_id) {
        global $wpdb;
        
        $data = array(
            'user_id' => $user_id,
            'context_type' => $context_type,
            'context_id' => $context_id,
            'rival_user_id' => $rival_user_id,
            'chasing_user_id' => $chasing_user_id,
            'computed_at' => current_time('mysql'),
        );
        
        $format = array('%d', '%s', '%d', '%d', '%d', '%s');
        
        // Check if exists
        $where = array("user_id = %d", "context_type = %s");
        $params = array($user_id, $context_type);
        
        if ($context_id !== null) {
            $where[] = "context_id = %d";
            $params[] = $context_id;
        } else {
            $where[] = "context_id IS NULL";
        }
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE " . implode(' AND ', $where),
            ...$params
        ));
        
        if ($exists) {
            $wpdb->update(
                $this->table_name,
                $data,
                array('id' => $exists),
                $format,
                array('%d')
            );
        } else {
            $wpdb->insert($this->table_name, $data, $format);
        }
    }
    
    /**
     * Get user details
     */
    private function get_user_details($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return null;
        }
        
        return array(
            'user_id' => $user_id,
            'display_name' => $user->display_name,
            'avatar_url' => get_avatar_url($user_id, array('size' => 48)),
        );
    }
    
    /**
     * Refresh all rivals (called by cron)
     */
    public function refresh_all_rivals() {
        global $wpdb;
        
        // Get all users with predictions
        $fp_prefix = 'pool_' . $wpdb->prefix;
        $users = $wpdb->get_col(
            "SELECT DISTINCT user_id FROM {$fp_prefix}predictions"
        );
        
        foreach ($users as $user_id) {
            // Refresh global rivals
            $this->compute_rivals($user_id, 'global', null);
            
            // Refresh league rivals
            $leagues = $wpdb->get_col($wpdb->prepare(
                "SELECT custom_competition_id FROM custom_competition_users WHERE user_id = %d",
                $user_id
            ));
            
            foreach ($leagues as $league_id) {
                $this->compute_rivals($user_id, 'league', $league_id);
            }
        }
    }
    
    /**
     * Check if rival has overtaken user
     */
    public function check_rival_overtake($user_id, $context_type = 'global', $context_id = null) {
        global $wpdb;
        
        // Get previous rivals
        $where = array("user_id = %d", "context_type = %s");
        $params = array($user_id, $context_type);
        
        if ($context_id !== null) {
            $where[] = "context_id = %d";
            $params[] = $context_id;
        } else {
            $where[] = "context_id IS NULL";
        }
        
        $previous = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE " . implode(' AND ', $where),
            ...$params
        ), ARRAY_A);
        
        if (!$previous || !$previous['chasing_user_id']) {
            return false;
        }
        
        $previous_chasing = $previous['chasing_user_id'];
        
        // Compute current rivals
        $current = $this->compute_rivals($user_id, $context_type, $context_id);
        
        if (!$current) {
            return false;
        }
        
        // Check if the user who was chasing is now the rival (above)
        if ($current['rival_user_id'] && (int) $current['rival_user_id'] === (int) $previous_chasing) {
            return array(
                'overtaker' => $this->get_user_details($previous_chasing),
                'context_type' => $context_type,
                'context_id' => $context_id,
            );
        }
        
        return false;
    }
    
    /**
     * Render rivals widget HTML
     */
    public function render_rivals_widget($user_id, $context_type = 'global', $context_id = null) {
        if (!SR_Gamification()->feature_flags->is_enabled('rivals_enabled')) {
            return '';
        }
        
        $rivals = $this->get_rivals($user_id, $context_type, $context_id);
        
        if (!$rivals) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="sr-rivals-widget">
            <h4 class="sr-rivals-title"><?php esc_html_e('Your Rivals', 'sportsrush-gamification'); ?></h4>
            
            <?php if (!empty($rivals['rival'])): ?>
            <div class="sr-rival-card sr-rival-above">
                <div class="sr-rival-label"><?php esc_html_e('Your Rival', 'sportsrush-gamification'); ?></div>
                <div class="sr-rival-info">
                    <img src="<?php echo esc_url($rivals['rival']['avatar_url']); ?>" alt="" class="sr-rival-avatar">
                    <div class="sr-rival-details">
                        <span class="sr-rival-name"><?php echo esc_html($rivals['rival']['display_name']); ?></span>
                        <?php if (isset($rivals['rival']['ranking'])): ?>
                        <span class="sr-rival-score"><?php echo esc_html($rivals['rival']['ranking']['total_score']); ?> pts</span>
                        <?php endif; ?>
                    </div>
                    <div class="sr-rival-position">
                        <span class="sr-position-badge">#<?php echo esc_html($rivals['rival']['ranking']['ranking'] ?? '?'); ?></span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="sr-rival-card sr-rival-above sr-rival-empty">
                <div class="sr-rival-label"><?php esc_html_e('Your Rival', 'sportsrush-gamification'); ?></div>
                <div class="sr-rival-info">
                    <span class="sr-rival-message"><?php esc_html_e("You're in first place!", 'sportsrush-gamification'); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="sr-rival-card sr-rival-you">
                <div class="sr-rival-label"><?php esc_html_e('You', 'sportsrush-gamification'); ?></div>
                <div class="sr-rival-info">
                    <img src="<?php echo esc_url(get_avatar_url($user_id, array('size' => 48))); ?>" alt="" class="sr-rival-avatar">
                    <div class="sr-rival-details">
                        <span class="sr-rival-name"><?php echo esc_html($rivals['user_ranking']['display_name']); ?></span>
                        <span class="sr-rival-score"><?php echo esc_html($rivals['user_ranking']['total_score']); ?> pts</span>
                    </div>
                    <div class="sr-rival-position">
                        <span class="sr-position-badge sr-position-you">#<?php echo esc_html($rivals['user_ranking']['ranking']); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($rivals['chasing'])): ?>
            <div class="sr-rival-card sr-rival-below">
                <div class="sr-rival-label"><?php esc_html_e('Chasing You', 'sportsrush-gamification'); ?></div>
                <div class="sr-rival-info">
                    <img src="<?php echo esc_url($rivals['chasing']['avatar_url']); ?>" alt="" class="sr-rival-avatar">
                    <div class="sr-rival-details">
                        <span class="sr-rival-name"><?php echo esc_html($rivals['chasing']['display_name']); ?></span>
                        <?php if (isset($rivals['chasing']['ranking'])): ?>
                        <span class="sr-rival-score"><?php echo esc_html($rivals['chasing']['ranking']['total_score']); ?> pts</span>
                        <?php endif; ?>
                    </div>
                    <div class="sr-rival-position">
                        <span class="sr-position-badge">#<?php echo esc_html($rivals['chasing']['ranking']['ranking'] ?? '?'); ?></span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="sr-rival-card sr-rival-below sr-rival-empty">
                <div class="sr-rival-label"><?php esc_html_e('Chasing You', 'sportsrush-gamification'); ?></div>
                <div class="sr-rival-info">
                    <span class="sr-rival-message"><?php esc_html_e("You're in last place - time to climb!", 'sportsrush-gamification'); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
