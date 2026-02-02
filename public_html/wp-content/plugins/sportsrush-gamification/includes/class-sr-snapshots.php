<?php
/**
 * Leaderboard Snapshots and Position Change Tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Snapshots {
    
    private $table_name;
    private $fp_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sr_leaderboard_snapshots';
        $this->fp_prefix = 'pool_' . $wpdb->prefix;
    }
    
    /**
     * Build snapshots for all contexts
     */
    public function build_all_snapshots() {
        // Build global snapshot
        $this->build_global_snapshot();
        
        // Build per-competition snapshots
        $this->build_competition_snapshots();
        
        // Build per-league snapshots
        $this->build_league_snapshots();
    }
    
    /**
     * Build global leaderboard snapshot
     */
    public function build_global_snapshot() {
        global $wpdb;
        
        $rankings = $this->get_current_rankings('global', null, null);
        
        if (empty($rankings)) {
            return false;
        }
        
        $current_round = $this->get_current_round();
        
        return $this->save_snapshot('global', null, null, $current_round, $rankings);
    }
    
    /**
     * Build snapshots for each competition
     */
    public function build_competition_snapshots() {
        global $wpdb;
        
        $competitions = $wpdb->get_results(
            "SELECT id, name FROM {$this->fp_prefix}matchtypes WHERE visibility = 1"
        );
        
        foreach ($competitions as $competition) {
            $rankings = $this->get_current_rankings('competition', $competition->id, null);
            
            if (!empty($rankings)) {
                $current_round = $this->get_current_round($competition->id);
                $this->save_snapshot('competition', $competition->id, $competition->id, $current_round, $rankings);
            }
        }
    }
    
    /**
     * Build snapshots for each private league
     */
    public function build_league_snapshots() {
        global $wpdb;
        
        $leagues = $wpdb->get_results(
            "SELECT id, name, matchtype_id FROM custom_competitions WHERE is_private = 1"
        );
        
        foreach ($leagues as $league) {
            $rankings = $this->get_current_rankings('league', $league->id, $league->matchtype_id);
            
            if (!empty($rankings)) {
                $current_round = $this->get_current_round($league->matchtype_id);
                $this->save_snapshot('league', $league->id, $league->matchtype_id, $current_round, $rankings);
            }
        }
    }
    
    /**
     * Get current rankings for a context
     */
    public function get_current_rankings($context_type, $context_id, $competition_id) {
        global $wpdb;
        
        $scorehistory_table = $this->fp_prefix . 'scorehistory_s1_t1';
        
        if ($context_type === 'league' && $context_id) {
            // Get users in this private league
            $user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM custom_competition_users WHERE custom_competition_id = %d",
                $context_id
            ));
            
            if (empty($user_ids)) {
                return array();
            }
            
            $user_ids_str = implode(',', array_map('intval', $user_ids));
            
            // Get rankings for these users, filtered by competition if set
            if ($competition_id) {
                $sql = $wpdb->prepare(
                    "SELECT 
                        sh.user_id,
                        u.display_name,
                        SUM(sh.score) as total_score,
                        @rank := @rank + 1 as ranking
                    FROM {$scorehistory_table} sh
                    JOIN {$wpdb->prefix}users u ON sh.user_id = u.ID
                    JOIN {$this->fp_prefix}matches m ON sh.source_id = m.id AND sh.type = 0
                    CROSS JOIN (SELECT @rank := 0) r
                    WHERE sh.user_id IN ($user_ids_str)
                    AND m.matchtype_id = %d
                    GROUP BY sh.user_id
                    ORDER BY total_score DESC",
                    $competition_id
                );
            } else {
                $sql = "SELECT 
                        sh.user_id,
                        u.display_name,
                        SUM(sh.score) as total_score,
                        @rank := @rank + 1 as ranking
                    FROM {$scorehistory_table} sh
                    JOIN {$wpdb->prefix}users u ON sh.user_id = u.ID
                    CROSS JOIN (SELECT @rank := 0) r
                    WHERE sh.user_id IN ($user_ids_str)
                    GROUP BY sh.user_id
                    ORDER BY total_score DESC";
            }
        } elseif ($context_type === 'competition' && $context_id) {
            // Get rankings for a specific competition
            $sql = $wpdb->prepare(
                "SELECT 
                    sh.user_id,
                    u.display_name,
                    SUM(sh.score) as total_score,
                    @rank := @rank + 1 as ranking
                FROM {$scorehistory_table} sh
                JOIN {$wpdb->prefix}users u ON sh.user_id = u.ID
                JOIN {$this->fp_prefix}matches m ON sh.source_id = m.id AND sh.type = 0
                CROSS JOIN (SELECT @rank := 0) r
                WHERE m.matchtype_id = %d
                GROUP BY sh.user_id
                ORDER BY total_score DESC",
                $context_id
            );
        } else {
            // Global rankings
            $sql = "SELECT 
                    sh.user_id,
                    u.display_name,
                    SUM(sh.score) as total_score,
                    @rank := @rank + 1 as ranking
                FROM {$scorehistory_table} sh
                JOIN {$wpdb->prefix}users u ON sh.user_id = u.ID
                CROSS JOIN (SELECT @rank := 0) r
                GROUP BY sh.user_id
                ORDER BY total_score DESC";
        }
        
        // Reset the rank variable
        $wpdb->query("SET @rank := 0");
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Assign proper rankings (handle ties)
        $rankings = array();
        $current_rank = 0;
        $previous_score = null;
        $skip = 0;
        
        foreach ($results as $index => $row) {
            if ($previous_score !== $row['total_score']) {
                $current_rank = $index + 1;
                $previous_score = $row['total_score'];
            }
            
            $rankings[] = array(
                'user_id' => (int) $row['user_id'],
                'display_name' => $row['display_name'],
                'total_score' => (int) $row['total_score'],
                'ranking' => $current_rank,
            );
        }
        
        return $rankings;
    }
    
    /**
     * Save a snapshot
     */
    private function save_snapshot($context_type, $context_id, $competition_id, $round_number, $rankings) {
        global $wpdb;
        
        $payload = wp_json_encode($rankings);
        
        return $wpdb->insert(
            $this->table_name,
            array(
                'context_type' => $context_type,
                'context_id' => $context_id,
                'competition_id' => $competition_id,
                'round_number' => $round_number,
                'snapshot_payload' => $payload,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%d', '%d', '%s', '%s')
        );
    }
    
    /**
     * Get the current round number
     */
    public function get_current_round($competition_id = null) {
        global $wpdb;
        
        $where = "WHERE home_score IS NOT NULL AND away_score IS NOT NULL";
        if ($competition_id) {
            $where .= $wpdb->prepare(" AND matchtype_id = %d", $competition_id);
        }
        
        $round = $wpdb->get_var(
            "SELECT MAX(CAST(round AS UNSIGNED)) 
            FROM {$this->fp_prefix}matches 
            $where"
        );
        
        return $round ? (int) $round : 1;
    }
    
    /**
     * Get the previous snapshot for a context
     */
    public function get_previous_snapshot($context_type, $context_id = null, $competition_id = null) {
        global $wpdb;
        
        $where = array("context_type = %s");
        $params = array($context_type);
        
        if ($context_id !== null) {
            $where[] = "context_id = %d";
            $params[] = $context_id;
        } else {
            $where[] = "context_id IS NULL";
        }
        
        if ($competition_id !== null) {
            $where[] = "competition_id = %d";
            $params[] = $competition_id;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = $wpdb->prepare(
            "SELECT snapshot_payload, round_number, created_at 
            FROM {$this->table_name} 
            WHERE $where_clause 
            ORDER BY created_at DESC 
            LIMIT 1, 1",
            ...$params
        );
        
        $result = $wpdb->get_row($sql);
        
        if ($result && $result->snapshot_payload) {
            return array(
                'rankings' => json_decode($result->snapshot_payload, true),
                'round_number' => $result->round_number,
                'created_at' => $result->created_at,
            );
        }
        
        return null;
    }
    
    /**
     * Get the latest snapshot for a context
     */
    public function get_latest_snapshot($context_type, $context_id = null, $competition_id = null) {
        global $wpdb;
        
        $where = array("context_type = %s");
        $params = array($context_type);
        
        if ($context_id !== null) {
            $where[] = "context_id = %d";
            $params[] = $context_id;
        } else {
            $where[] = "context_id IS NULL";
        }
        
        if ($competition_id !== null) {
            $where[] = "competition_id = %d";
            $params[] = $competition_id;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = $wpdb->prepare(
            "SELECT snapshot_payload, round_number, created_at 
            FROM {$this->table_name} 
            WHERE $where_clause 
            ORDER BY created_at DESC 
            LIMIT 1",
            ...$params
        );
        
        $result = $wpdb->get_row($sql);
        
        if ($result && $result->snapshot_payload) {
            return array(
                'rankings' => json_decode($result->snapshot_payload, true),
                'round_number' => $result->round_number,
                'created_at' => $result->created_at,
            );
        }
        
        return null;
    }
    
    /**
     * Calculate position changes for a user
     */
    public function get_position_change($user_id, $context_type, $context_id = null, $competition_id = null) {
        $latest = $this->get_latest_snapshot($context_type, $context_id, $competition_id);
        $previous = $this->get_previous_snapshot($context_type, $context_id, $competition_id);
        
        if (!$latest || !$previous) {
            return array(
                'current_rank' => null,
                'previous_rank' => null,
                'change' => 0,
                'direction' => 'none',
            );
        }
        
        $current_rank = null;
        $previous_rank = null;
        
        foreach ($latest['rankings'] as $entry) {
            if ((int) $entry['user_id'] === (int) $user_id) {
                $current_rank = $entry['ranking'];
                break;
            }
        }
        
        foreach ($previous['rankings'] as $entry) {
            if ((int) $entry['user_id'] === (int) $user_id) {
                $previous_rank = $entry['ranking'];
                break;
            }
        }
        
        if ($current_rank === null || $previous_rank === null) {
            return array(
                'current_rank' => $current_rank,
                'previous_rank' => $previous_rank,
                'change' => 0,
                'direction' => 'none',
            );
        }
        
        $change = $previous_rank - $current_rank;
        $direction = 'none';
        
        if ($change > 0) {
            $direction = 'up';
        } elseif ($change < 0) {
            $direction = 'down';
        }
        
        return array(
            'current_rank' => $current_rank,
            'previous_rank' => $previous_rank,
            'change' => abs($change),
            'direction' => $direction,
        );
    }
    
    /**
     * Get rankings with position changes
     */
    public function get_rankings_with_changes($context_type, $context_id = null, $competition_id = null) {
        $current_rankings = $this->get_current_rankings($context_type, $context_id, $competition_id);
        $previous = $this->get_previous_snapshot($context_type, $context_id, $competition_id);
        
        $previous_rankings = array();
        if ($previous && isset($previous['rankings'])) {
            foreach ($previous['rankings'] as $entry) {
                $previous_rankings[$entry['user_id']] = $entry['ranking'];
            }
        }
        
        foreach ($current_rankings as &$entry) {
            $user_id = $entry['user_id'];
            $current_rank = $entry['ranking'];
            
            if (isset($previous_rankings[$user_id])) {
                $previous_rank = $previous_rankings[$user_id];
                $change = $previous_rank - $current_rank;
                
                $entry['previous_rank'] = $previous_rank;
                $entry['change'] = abs($change);
                $entry['direction'] = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'none');
            } else {
                $entry['previous_rank'] = null;
                $entry['change'] = 0;
                $entry['direction'] = 'new';
            }
        }
        
        return $current_rankings;
    }
    
    /**
     * Render position change indicator HTML
     */
    public function render_position_indicator($change, $direction) {
        if (!SR_Gamification()->feature_flags->is_enabled('position_changes_enabled')) {
            return '';
        }
        
        $html = '<span class="sr-position-change sr-position-' . esc_attr($direction) . '">';
        
        switch ($direction) {
            case 'up':
                $html .= '<span class="sr-arrow">&#9650;</span>';
                $html .= '<span class="sr-change-value">+' . esc_html($change) . '</span>';
                break;
            case 'down':
                $html .= '<span class="sr-arrow">&#9660;</span>';
                $html .= '<span class="sr-change-value">-' . esc_html($change) . '</span>';
                break;
            case 'new':
                $html .= '<span class="sr-new-badge">NEW</span>';
                break;
            default:
                $html .= '<span class="sr-no-change">-</span>';
        }
        
        $html .= '</span>';
        
        return $html;
    }
    
    /**
     * Clean up old snapshots (keep last 30 days)
     */
    public function cleanup_old_snapshots($days = 30) {
        global $wpdb;
        
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < %s",
            $cutoff
        ));
    }
}
