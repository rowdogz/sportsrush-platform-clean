<?php
/**
 * Weekly Banter Summaries for Private Leagues
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Banter {
    
    private $table_name;
    private $fp_prefix;
    
    // Banter templates
    private $templates = array(
        'biggest_climber' => array(
            '%s rocketed up %d places this week - someone\'s been eating their Weetabix!',
            '%s climbed %d spots! Were they cheating or just lucky?',
            'Watch out! %s gained %d positions and is coming for everyone!',
        ),
        'biggest_faller' => array(
            '%s dropped %d places... time to hang up the crystal ball?',
            'Oof! %s fell %d spots. Maybe stick to the day job?',
            '%s plummeted %d positions. We\'ve all been there, mate.',
        ),
        'top_scorer' => array(
            '%s smashed it with %d points this week - absolute scenes!',
            'Bow down to %s who bagged %d points!',
            '%s scored %d points. Show off.',
        ),
        'worst_scorer' => array(
            '%s managed just %d points... bless.',
            'Only %d points for %s this week. Thoughts and prayers.',
            '%s scraped together %d points. It\'s the taking part that counts!',
        ),
        'manager_of_week' => array(
            'Manager of the Week: %s! Take a bow, son.',
            'This week\'s gaffer: %s! The people\'s champion.',
            '%s is your Manager of the Week. Drinks are on them!',
        ),
        'wooden_spoon' => array(
            'Wooden Spoon goes to %s. Better luck next week!',
            '%s wins the coveted Wooden Spoon. Frame it!',
            'Commiserations to %s for this week\'s Wooden Spoon.',
        ),
    );
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sr_banter_summaries';
        $this->fp_prefix = 'pool_' . $wpdb->prefix;
    }
    
    /**
     * Generate banter summaries for all leagues
     */
    public function generate_all_summaries() {
        if (!SR_Gamification()->feature_flags->is_enabled('banter_summaries_enabled')) {
            return;
        }
        
        global $wpdb;
        
        // Get all private leagues
        $leagues = $wpdb->get_results(
            "SELECT id, name, matchtype_id FROM custom_competitions WHERE is_private = 1"
        );
        
        foreach ($leagues as $league) {
            $this->generate_league_summary($league->id);
        }
    }
    
    /**
     * Generate banter summary for a specific league
     */
    public function generate_league_summary($league_id) {
        global $wpdb;
        
        // Get the Monday of this week
        $week_start = date('Y-m-d', strtotime('monday this week'));
        
        // Check if summary already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE league_id = %d AND week_start = %s",
            $league_id,
            $week_start
        ));
        
        if ($exists) {
            return $exists;
        }
        
        // Get league info
        $league = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM custom_competitions WHERE id = %d",
            $league_id
        ));
        
        if (!$league) {
            return false;
        }
        
        // Calculate stats for the past week
        $stats = $this->calculate_weekly_stats($league_id, $league->matchtype_id);
        
        if (empty($stats)) {
            return false;
        }
        
        // Generate banter lines
        $banter_lines = $this->generate_banter_lines($stats);
        
        // Save summary
        $summary_payload = array(
            'week_start' => $week_start,
            'stats' => $stats,
            'banter_lines' => $banter_lines,
            'generated_at' => current_time('mysql'),
        );
        
        $wpdb->insert(
            $this->table_name,
            array(
                'league_id' => $league_id,
                'week_start' => $week_start,
                'summary_payload' => wp_json_encode($summary_payload),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        // Create notifications for league members
        if (SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            $members = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM custom_competition_users WHERE custom_competition_id = %d",
                $league_id
            ));
            
            foreach ($members as $user_id) {
                SR_Gamification()->notifications->create_notification(
                    $user_id,
                    'banter_ready',
                    array(
                        'league_id' => $league_id,
                        'league_name' => $league->name,
                        'message' => __('This week\'s banter summary is ready!', 'sportsrush-gamification'),
                    )
                );
            }
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Calculate weekly stats for a league
     */
    private function calculate_weekly_stats($league_id, $competition_id) {
        global $wpdb;
        
        $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        // Get league members
        $members = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM custom_competition_users WHERE custom_competition_id = %d",
            $league_id
        ));
        
        if (empty($members)) {
            return array();
        }
        
        $members_str = implode(',', array_map('intval', $members));
        
        // Get matches from the past week
        $where_comp = $competition_id ? $wpdb->prepare("AND m.matchtype_id = %d", $competition_id) : "";
        
        $match_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->fp_prefix}matches m
            WHERE m.play_date >= %s
            AND m.home_score IS NOT NULL
            AND m.away_score IS NOT NULL
            $where_comp",
            $week_ago
        ));
        
        if (empty($match_ids)) {
            return array();
        }
        
        $match_ids_str = implode(',', array_map('intval', $match_ids));
        
        $scorehistory_table = $this->fp_prefix . 'scorehistory_s1_t1';
        
        // Get weekly scores
        $weekly_scores = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                sh.user_id,
                u.display_name,
                SUM(sh.score) as week_score
            FROM {$scorehistory_table} sh
            JOIN {$wpdb->prefix}users u ON sh.user_id = u.ID
            WHERE sh.type = 0
            AND sh.source_id IN ($match_ids_str)
            AND sh.user_id IN ($members_str)
            GROUP BY sh.user_id
            ORDER BY week_score DESC"
        ), ARRAY_A);
        
        if (empty($weekly_scores)) {
            return array();
        }
        
        // Get position changes from snapshots
        $snapshots = SR_Gamification()->snapshots;
        $position_changes = array();
        
        foreach ($members as $user_id) {
            $change = $snapshots->get_position_change($user_id, 'league', $league_id);
            $position_changes[$user_id] = $change;
        }
        
        // Find biggest climber and faller
        $biggest_climber = null;
        $biggest_faller = null;
        $max_climb = 0;
        $max_fall = 0;
        
        foreach ($position_changes as $user_id => $change) {
            if ($change['direction'] === 'up' && $change['change'] > $max_climb) {
                $max_climb = $change['change'];
                $biggest_climber = array(
                    'user_id' => $user_id,
                    'display_name' => get_userdata($user_id)->display_name,
                    'change' => $change['change'],
                );
            }
            if ($change['direction'] === 'down' && $change['change'] > $max_fall) {
                $max_fall = $change['change'];
                $biggest_faller = array(
                    'user_id' => $user_id,
                    'display_name' => get_userdata($user_id)->display_name,
                    'change' => $change['change'],
                );
            }
        }
        
        // Top and bottom scorers
        $top_scorer = $weekly_scores[0];
        $worst_scorer = end($weekly_scores);
        
        return array(
            'top_scorer' => $top_scorer,
            'worst_scorer' => $worst_scorer,
            'biggest_climber' => $biggest_climber,
            'biggest_faller' => $biggest_faller,
            'manager_of_week' => $top_scorer, // Same as top scorer for now
            'wooden_spoon' => $worst_scorer,
            'total_matches' => count($match_ids),
            'total_participants' => count($weekly_scores),
        );
    }
    
    /**
     * Generate banter lines from stats
     */
    private function generate_banter_lines($stats) {
        $lines = array();
        
        if (!empty($stats['top_scorer'])) {
            $template = $this->get_random_template('top_scorer');
            $lines['top_scorer'] = sprintf(
                $template,
                $stats['top_scorer']['display_name'],
                $stats['top_scorer']['week_score']
            );
        }
        
        if (!empty($stats['worst_scorer']) && $stats['worst_scorer']['user_id'] !== $stats['top_scorer']['user_id']) {
            $template = $this->get_random_template('worst_scorer');
            $lines['worst_scorer'] = sprintf(
                $template,
                $stats['worst_scorer']['display_name'],
                $stats['worst_scorer']['week_score']
            );
        }
        
        if (!empty($stats['biggest_climber'])) {
            $template = $this->get_random_template('biggest_climber');
            $lines['biggest_climber'] = sprintf(
                $template,
                $stats['biggest_climber']['display_name'],
                $stats['biggest_climber']['change']
            );
        }
        
        if (!empty($stats['biggest_faller'])) {
            $template = $this->get_random_template('biggest_faller');
            $lines['biggest_faller'] = sprintf(
                $template,
                $stats['biggest_faller']['display_name'],
                $stats['biggest_faller']['change']
            );
        }
        
        if (!empty($stats['manager_of_week'])) {
            $template = $this->get_random_template('manager_of_week');
            $lines['manager_of_week'] = sprintf(
                $template,
                $stats['manager_of_week']['display_name']
            );
        }
        
        return $lines;
    }
    
    /**
     * Get random template for a category
     */
    private function get_random_template($category) {
        if (!isset($this->templates[$category])) {
            return '%s';
        }
        
        $templates = $this->templates[$category];
        return $templates[array_rand($templates)];
    }
    
    /**
     * Get latest banter summary for a league
     */
    public function get_latest_summary($league_id) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE league_id = %d
            ORDER BY week_start DESC
            LIMIT 1",
            $league_id
        ));
        
        if ($result && $result->summary_payload) {
            $result->payload = json_decode($result->summary_payload, true);
        }
        
        return $result;
    }
    
    /**
     * Render banter widget for a league
     */
    public function render_widget($league_id) {
        if (!SR_Gamification()->feature_flags->is_enabled('banter_summaries_enabled')) {
            return '';
        }
        
        $summary = $this->get_latest_summary($league_id);
        
        if (!$summary || empty($summary->payload['banter_lines'])) {
            return '';
        }
        
        $lines = $summary->payload['banter_lines'];
        $stats = $summary->payload['stats'];
        
        ob_start();
        ?>
        <div class="sr-banter-widget">
            <div class="sr-banter-header">
                <h4 class="sr-banter-title">
                    <span class="sr-icon">&#128172;</span>
                    <?php esc_html_e('Weekly Banter', 'sportsrush-gamification'); ?>
                </h4>
                <span class="sr-banter-date">
                    <?php echo esc_html(date_i18n('j M Y', strtotime($summary->week_start))); ?>
                </span>
            </div>
            
            <div class="sr-banter-content">
                <?php if (!empty($lines['manager_of_week'])): ?>
                <div class="sr-banter-line sr-banter-highlight">
                    <span class="sr-banter-icon">&#127942;</span>
                    <span class="sr-banter-text"><?php echo esc_html($lines['manager_of_week']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($lines['top_scorer'])): ?>
                <div class="sr-banter-line">
                    <span class="sr-banter-icon">&#11088;</span>
                    <span class="sr-banter-text"><?php echo esc_html($lines['top_scorer']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($lines['biggest_climber'])): ?>
                <div class="sr-banter-line">
                    <span class="sr-banter-icon">&#128200;</span>
                    <span class="sr-banter-text"><?php echo esc_html($lines['biggest_climber']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($lines['biggest_faller'])): ?>
                <div class="sr-banter-line">
                    <span class="sr-banter-icon">&#128201;</span>
                    <span class="sr-banter-text"><?php echo esc_html($lines['biggest_faller']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($lines['worst_scorer'])): ?>
                <div class="sr-banter-line">
                    <span class="sr-banter-icon">&#129325;</span>
                    <span class="sr-banter-text"><?php echo esc_html($lines['worst_scorer']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="sr-banter-footer">
                <span class="sr-banter-stats">
                    <?php printf(
                        esc_html__('%d matches | %d players', 'sportsrush-gamification'),
                        $stats['total_matches'],
                        $stats['total_participants']
                    ); ?>
                </span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
