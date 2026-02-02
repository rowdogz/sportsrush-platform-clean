<?php
/**
 * Points Integration - Adds gamification points to Football Pool totals
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Points_Integration {
    
    private $entries_table;
    private $user_points_cache = array();
    
    public function __construct() {
        global $wpdb;
        $this->entries_table = $wpdb->prefix . 'sr_daily_pick_entries';
        
        // Hook into Football Pool ranking filters
        add_filter('footballpool_get_ranking', array($this, 'add_gamification_points_to_ranking'), 10, 1);
        add_filter('footballpool_get_ranking_limited', array($this, 'add_gamification_points_to_ranking'), 10, 1);
    }
    
    /**
     * Add gamification points to each user in the ranking
     */
    public function add_gamification_points_to_ranking($ranking) {
        if (empty($ranking) || !is_array($ranking)) {
            return $ranking;
        }
        
        // Get all user IDs from the ranking
        $user_ids = array();
        foreach ($ranking as $row) {
            if (isset($row['user_id'])) {
                $user_ids[] = (int) $row['user_id'];
            }
        }
        
        if (empty($user_ids)) {
            return $ranking;
        }
        
        // Get gamification points for all users in one query
        $gamification_points = $this->get_gamification_points_for_users($user_ids);
        
        // Add gamification points to each user's total
        foreach ($ranking as &$row) {
            if (isset($row['user_id'])) {
                $user_id = (int) $row['user_id'];
                $bonus_points = isset($gamification_points[$user_id]) ? $gamification_points[$user_id] : 0;
                
                if ($bonus_points > 0) {
                    // Add to points/total_score
                    if (isset($row['points'])) {
                        $row['points'] = (int) $row['points'] + $bonus_points;
                    }
                    if (isset($row['total_score'])) {
                        $row['total_score'] = (int) $row['total_score'] + $bonus_points;
                    }
                    
                    // Store the bonus for display purposes
                    $row['gamification_bonus'] = $bonus_points;
                }
            }
        }
        
        // Re-sort by points and re-assign rankings
        usort($ranking, function($a, $b) {
            $points_a = isset($a['points']) ? (int) $a['points'] : (isset($a['total_score']) ? (int) $a['total_score'] : 0);
            $points_b = isset($b['points']) ? (int) $b['points'] : (isset($b['total_score']) ? (int) $b['total_score'] : 0);
            return $points_b - $points_a; // Descending order
        });
        
        // Re-assign rankings
        $rank = 1;
        foreach ($ranking as &$row) {
            $row['ranking'] = $rank++;
        }
        
        return $ranking;
    }
    
    /**
     * Get total gamification points for multiple users
     */
    public function get_gamification_points_for_users($user_ids) {
        global $wpdb;
        
        if (empty($user_ids)) {
            return array();
        }
        
        $points = array();
        
        // Initialize all users with 0 points
        foreach ($user_ids as $user_id) {
            $points[$user_id] = 0;
        }
        
        // Get Daily Pick points
        if (SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
            $daily_pick_points = $wpdb->get_results($wpdb->prepare(
                "SELECT user_id, SUM(points_awarded) as total_points 
                FROM {$this->entries_table} 
                WHERE user_id IN ($placeholders) 
                AND points_awarded > 0
                GROUP BY user_id",
                $user_ids
            ));
            
            foreach ($daily_pick_points as $row) {
                $points[(int) $row->user_id] += (int) $row->total_points;
            }
        }
        
        // Add streak bonus points from user meta
        if (SR_Gamification()->feature_flags->is_enabled('streaks_enabled')) {
            foreach ($user_ids as $user_id) {
                $streak_points = (int) get_user_meta($user_id, 'sr_streak_points_total', true);
                if ($streak_points > 0) {
                    $points[$user_id] += $streak_points;
                }
            }
        }
        
        return $points;
    }
    
    /**
     * Get total gamification points for a single user
     */
    public function get_user_gamification_points($user_id) {
        // Check cache first
        if (isset($this->user_points_cache[$user_id])) {
            return $this->user_points_cache[$user_id];
        }
        
        $points = $this->get_gamification_points_for_users(array($user_id));
        $total = isset($points[$user_id]) ? $points[$user_id] : 0;
        
        // Cache the result
        $this->user_points_cache[$user_id] = $total;
        
        return $total;
    }
    
    /**
     * Get breakdown of gamification points for a user
     */
    public function get_user_points_breakdown($user_id) {
        global $wpdb;
        
        $breakdown = array(
            'daily_pick' => 0,
            'streaks' => 0,
            'achievements' => 0,
            'total' => 0,
        );
        
        // Daily Pick points
        if (SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            $daily_pick_points = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(points_awarded) FROM {$this->entries_table} 
                WHERE user_id = %d AND points_awarded > 0",
                $user_id
            ));
            $breakdown['daily_pick'] = (int) $daily_pick_points;
        }
        
        // Streak bonus points from user meta
        if (SR_Gamification()->feature_flags->is_enabled('streaks_enabled')) {
            $breakdown['streaks'] = (int) get_user_meta($user_id, 'sr_streak_points_total', true);
        }
        
        $breakdown['total'] = $breakdown['daily_pick'] + $breakdown['streaks'] + $breakdown['achievements'];
        
        return $breakdown;
    }
    
    /**
     * Clear the points cache
     */
    public function clear_cache($user_id = null) {
        if ($user_id !== null) {
            unset($this->user_points_cache[$user_id]);
        } else {
            $this->user_points_cache = array();
        }
    }
}
