<?php
/**
 * Mini Leaderboards - Current round, last 5 fixtures, last 7 days
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Mini_Leaderboards {
    
    private $fp_prefix;
    private $cache_group = 'sr_mini_leaderboards';
    private $cache_duration = 300; // 5 minutes
    
    public function __construct() {
        global $wpdb;
        $this->fp_prefix = 'pool_' . $wpdb->prefix;
    }
    
    /**
     * Get current round leaderboard
     */
    public function get_current_round_leaderboard($competition_id = null, $league_id = null, $limit = 10) {
        if (!SR_Gamification()->feature_flags->is_enabled('mini_leaderboards_enabled')) {
            return array();
        }
        
        $cache_key = "current_round_{$competition_id}_{$league_id}_{$limit}";
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        
        // Get current round
        $current_round = $this->get_current_round($competition_id);
        
        if (!$current_round) {
            return array();
        }
        
        // Get matches in current round
        $where_comp = $competition_id ? $wpdb->prepare("AND m.matchtype_id = %d", $competition_id) : "";
        
        $match_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->fp_prefix}matches 
            WHERE CAST(round AS UNSIGNED) = %d 
            AND home_score IS NOT NULL 
            AND away_score IS NOT NULL
            $where_comp",
            $current_round
        ));
        
        if (empty($match_ids)) {
            return array();
        }
        
        $match_ids_str = implode(',', array_map('intval', $match_ids));
        
        // Get user filter for league
        $user_filter = "";
        if ($league_id) {
            $user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM custom_competition_users WHERE custom_competition_id = %d",
                $league_id
            ));
            if (empty($user_ids)) {
                return array();
            }
            $user_ids_str = implode(',', array_map('intval', $user_ids));
            $user_filter = "AND sh.user_id IN ($user_ids_str)";
        }
        
        $scorehistory_table = $this->fp_prefix . 'scorehistory_s1_t1';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                sh.user_id,
                u.display_name,
                SUM(sh.score) as round_score
            FROM {$scorehistory_table} sh
            JOIN {$wpdb->prefix}users u ON sh.user_id = u.ID
            WHERE sh.type = 0 
            AND sh.source_id IN ($match_ids_str)
            $user_filter
            GROUP BY sh.user_id
            ORDER BY round_score DESC
            LIMIT %d",
            $limit
        ), ARRAY_A);
        
        // Add rankings
        $rank = 0;
        $prev_score = null;
        $skip = 0;
        
        foreach ($results as &$row) {
            if ($prev_score !== $row['round_score']) {
                $rank += 1 + $skip;
                $skip = 0;
                $prev_score = $row['round_score'];
            } else {
                $skip++;
            }
            $row['ranking'] = $rank;
            $row['avatar_url'] = get_avatar_url($row['user_id'], array('size' => 32));
        }
        
        $data = array(
            'round' => $current_round,
            'rankings' => $results,
        );
        
        wp_cache_set($cache_key, $data, $this->cache_group, $this->cache_duration);
        
        return $data;
    }
    
    /**
     * Get last N fixtures form table
     */
    public function get_last_fixtures_leaderboard($num_fixtures = 5, $competition_id = null, $league_id = null, $limit = 10) {
        if (!SR_Gamification()->feature_flags->is_enabled('mini_leaderboards_enabled')) {
            return array();
        }
        
        $cache_key = "last_fixtures_{$num_fixtures}_{$competition_id}_{$league_id}_{$limit}";
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        
        // Get last N completed fixtures
        $where_comp = $competition_id ? $wpdb->prepare("AND matchtype_id = %d", $competition_id) : "";
        
        $match_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->fp_prefix}matches 
            WHERE home_score IS NOT NULL 
            AND away_score IS NOT NULL
            $where_comp
            ORDER BY play_date DESC
            LIMIT %d",
            $num_fixtures
        ));
        
        if (empty($match_ids)) {
            return array();
        }
        
        $match_ids_str = implode(',', array_map('intval', $match_ids));
        
        // Get user filter for league
        $user_filter = "";
        if ($league_id) {
            $user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM custom_competition_users WHERE custom_competition_id = %d",
                $league_id
            ));
            if (empty($user_ids)) {
                return array();
            }
            $user_ids_str = implode(',', array_map('intval', $user_ids));
            $user_filter = "AND sh.user_id IN ($user_ids_str)";
        }
        
        $scorehistory_table = $this->fp_prefix . 'scorehistory_s1_t1';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                sh.user_id,
                u.display_name,
                SUM(sh.score) as form_score,
                COUNT(CASE WHEN sh.full > 0 THEN 1 END) as correct_scores,
                COUNT(CASE WHEN sh.toto > 0 THEN 1 END) as correct_results
            FROM {$scorehistory_table} sh
            JOIN {$wpdb->prefix}users u ON sh.user_id = u.ID
            WHERE sh.type = 0 
            AND sh.source_id IN ($match_ids_str)
            $user_filter
            GROUP BY sh.user_id
            ORDER BY form_score DESC
            LIMIT %d",
            $limit
        ), ARRAY_A);
        
        // Add rankings
        $rank = 0;
        $prev_score = null;
        $skip = 0;
        
        foreach ($results as &$row) {
            if ($prev_score !== $row['form_score']) {
                $rank += 1 + $skip;
                $skip = 0;
                $prev_score = $row['form_score'];
            } else {
                $skip++;
            }
            $row['ranking'] = $rank;
            $row['avatar_url'] = get_avatar_url($row['user_id'], array('size' => 32));
        }
        
        $data = array(
            'num_fixtures' => $num_fixtures,
            'rankings' => $results,
        );
        
        wp_cache_set($cache_key, $data, $this->cache_group, $this->cache_duration);
        
        return $data;
    }
    
    /**
     * Get last N days leaderboard
     */
    public function get_last_days_leaderboard($num_days = 7, $competition_id = null, $league_id = null, $limit = 10) {
        if (!SR_Gamification()->feature_flags->is_enabled('mini_leaderboards_enabled')) {
            return array();
        }
        
        $cache_key = "last_days_{$num_days}_{$competition_id}_{$league_id}_{$limit}";
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$num_days} days"));
        
        // Get matches in date range
        $where_comp = $competition_id ? $wpdb->prepare("AND matchtype_id = %d", $competition_id) : "";
        
        $match_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->fp_prefix}matches 
            WHERE home_score IS NOT NULL 
            AND away_score IS NOT NULL
            AND play_date >= %s
            $where_comp",
            $cutoff_date
        ));
        
        if (empty($match_ids)) {
            return array();
        }
        
        $match_ids_str = implode(',', array_map('intval', $match_ids));
        
        // Get user filter for league
        $user_filter = "";
        if ($league_id) {
            $user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM custom_competition_users WHERE custom_competition_id = %d",
                $league_id
            ));
            if (empty($user_ids)) {
                return array();
            }
            $user_ids_str = implode(',', array_map('intval', $user_ids));
            $user_filter = "AND sh.user_id IN ($user_ids_str)";
        }
        
        $scorehistory_table = $this->fp_prefix . 'scorehistory_s1_t1';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                sh.user_id,
                u.display_name,
                SUM(sh.score) as period_score,
                COUNT(sh.source_id) as matches_predicted
            FROM {$scorehistory_table} sh
            JOIN {$wpdb->prefix}users u ON sh.user_id = u.ID
            WHERE sh.type = 0 
            AND sh.source_id IN ($match_ids_str)
            $user_filter
            GROUP BY sh.user_id
            ORDER BY period_score DESC
            LIMIT %d",
            $limit
        ), ARRAY_A);
        
        // Add rankings
        $rank = 0;
        $prev_score = null;
        $skip = 0;
        
        foreach ($results as &$row) {
            if ($prev_score !== $row['period_score']) {
                $rank += 1 + $skip;
                $skip = 0;
                $prev_score = $row['period_score'];
            } else {
                $skip++;
            }
            $row['ranking'] = $rank;
            $row['avatar_url'] = get_avatar_url($row['user_id'], array('size' => 32));
        }
        
        $data = array(
            'num_days' => $num_days,
            'start_date' => $cutoff_date,
            'rankings' => $results,
        );
        
        wp_cache_set($cache_key, $data, $this->cache_group, $this->cache_duration);
        
        return $data;
    }
    
    /**
     * Get current round number
     */
    private function get_current_round($competition_id = null) {
        global $wpdb;
        
        $where_comp = $competition_id ? $wpdb->prepare("AND matchtype_id = %d", $competition_id) : "";
        
        return $wpdb->get_var(
            "SELECT MAX(CAST(round AS UNSIGNED)) 
            FROM {$this->fp_prefix}matches 
            WHERE home_score IS NOT NULL 
            AND away_score IS NOT NULL
            $where_comp"
        );
    }
    
    /**
     * Render mini leaderboard widget
     */
    public function render_mini_leaderboard($type, $data, $title = '') {
        if (empty($data) || empty($data['rankings'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="sr-mini-leaderboard sr-mini-leaderboard-<?php echo esc_attr($type); ?>">
            <?php if ($title): ?>
            <h4 class="sr-mini-leaderboard-title"><?php echo esc_html($title); ?></h4>
            <?php endif; ?>
            
            <div class="sr-mini-leaderboard-list">
                <?php foreach ($data['rankings'] as $entry): ?>
                <div class="sr-mini-leaderboard-row <?php echo get_current_user_id() == $entry['user_id'] ? 'sr-current-user' : ''; ?>">
                    <span class="sr-mini-rank"><?php echo esc_html($entry['ranking']); ?></span>
                    <img src="<?php echo esc_url($entry['avatar_url']); ?>" alt="" class="sr-mini-avatar">
                    <span class="sr-mini-name"><?php echo esc_html($entry['display_name']); ?></span>
                    <span class="sr-mini-score">
                        <?php 
                        $score_key = isset($entry['round_score']) ? 'round_score' : 
                                    (isset($entry['form_score']) ? 'form_score' : 'period_score');
                        echo esc_html($entry[$score_key]); 
                        ?> pts
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render tabbed mini leaderboards widget
     */
    public function render_tabbed_widget($competition_id = null, $league_id = null) {
        if (!SR_Gamification()->feature_flags->is_enabled('mini_leaderboards_enabled')) {
            return '';
        }
        
        $current_round = $this->get_current_round_leaderboard($competition_id, $league_id);
        $last_5 = $this->get_last_fixtures_leaderboard(5, $competition_id, $league_id);
        $last_7_days = $this->get_last_days_leaderboard(7, $competition_id, $league_id);
        
        ob_start();
        ?>
        <div class="sr-mini-leaderboards-widget">
            <div class="sr-mini-tabs">
                <button class="sr-mini-tab active" data-tab="current-round">
                    <?php esc_html_e('This Round', 'sportsrush-gamification'); ?>
                </button>
                <button class="sr-mini-tab" data-tab="last-5">
                    <?php esc_html_e('Last 5', 'sportsrush-gamification'); ?>
                </button>
                <button class="sr-mini-tab" data-tab="last-7-days">
                    <?php esc_html_e('7 Days', 'sportsrush-gamification'); ?>
                </button>
            </div>
            
            <div class="sr-mini-tab-content active" id="sr-tab-current-round">
                <?php 
                if (!empty($current_round['rankings'])) {
                    $title = sprintf(__('Round %d Leaders', 'sportsrush-gamification'), $current_round['round']);
                    echo $this->render_mini_leaderboard('current-round', $current_round, $title);
                } else {
                    echo '<p class="sr-no-data">' . esc_html__('No completed matches in current round yet.', 'sportsrush-gamification') . '</p>';
                }
                ?>
            </div>
            
            <div class="sr-mini-tab-content" id="sr-tab-last-5">
                <?php 
                if (!empty($last_5['rankings'])) {
                    echo $this->render_mini_leaderboard('last-5', $last_5, __('Last 5 Fixtures Form', 'sportsrush-gamification'));
                } else {
                    echo '<p class="sr-no-data">' . esc_html__('Not enough data yet.', 'sportsrush-gamification') . '</p>';
                }
                ?>
            </div>
            
            <div class="sr-mini-tab-content" id="sr-tab-last-7-days">
                <?php 
                if (!empty($last_7_days['rankings'])) {
                    echo $this->render_mini_leaderboard('last-7-days', $last_7_days, __('Last 7 Days', 'sportsrush-gamification'));
                } else {
                    echo '<p class="sr-no-data">' . esc_html__('No matches in the last 7 days.', 'sportsrush-gamification') . '</p>';
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
