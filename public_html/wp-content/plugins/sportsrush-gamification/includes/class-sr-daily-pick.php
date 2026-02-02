<?php
/**
 * Daily Pick System
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Daily_Pick {
    
    private $picks_table;
    private $entries_table;
    private $fp_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->picks_table = $wpdb->prefix . 'sr_daily_picks';
        $this->entries_table = $wpdb->prefix . 'sr_daily_pick_entries';
        $this->fp_prefix = 'pool_' . $wpdb->prefix;
    }
    
    /**
     * Generate daily pick for today
     */
    public function generate_daily_pick() {
        if (!SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            return false;
        }
        
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        // Check if pick already exists for today
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->picks_table} WHERE pick_date = %s",
            $today
        ));
        
        if ($exists) {
            return $exists;
        }
        
        // Find a fixture for today or the next upcoming fixture
        $fixture = $this->find_fixture_for_pick($today);
        
        if (!$fixture) {
            return false;
        }
        
        // Create the daily pick
        $pick_data = array(
            'pick_date' => $today,
            'competition_id' => $fixture->matchtype_id,
            'fixture_id' => $fixture->id,
            'pick_type' => 'winner',
            'pick_payload' => wp_json_encode(array(
                'home_team_id' => $fixture->home_team_id,
                'away_team_id' => $fixture->away_team_id,
                'home_team_name' => $fixture->home_team_name,
                'away_team_name' => $fixture->away_team_name,
                'play_date' => $fixture->play_date,
            )),
            'lock_time' => $fixture->play_date,
            'settle_time' => null,
            'settled' => 0,
            'created_at' => current_time('mysql'),
        );
        
        $wpdb->insert($this->picks_table, $pick_data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Find a fixture for the daily pick
     */
    private function find_fixture_for_pick($date) {
        global $wpdb;
        
        // First try to find a fixture today
        $fixture = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, 
                    ht.name as home_team_name, 
                    at.name as away_team_name
            FROM {$this->fp_prefix}matches m
            JOIN {$this->fp_prefix}teams ht ON m.home_team_id = ht.id
            JOIN {$this->fp_prefix}teams at ON m.away_team_id = at.id
            WHERE DATE(m.play_date) = %s
            AND m.home_score IS NULL
            ORDER BY m.play_date ASC
            LIMIT 1",
            $date
        ));
        
        if ($fixture) {
            return $fixture;
        }
        
        // If no fixture today, find the next upcoming fixture
        $fixture = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, 
                    ht.name as home_team_name, 
                    at.name as away_team_name
            FROM {$this->fp_prefix}matches m
            JOIN {$this->fp_prefix}teams ht ON m.home_team_id = ht.id
            JOIN {$this->fp_prefix}teams at ON m.away_team_id = at.id
            WHERE m.play_date > %s
            AND m.home_score IS NULL
            ORDER BY m.play_date ASC
            LIMIT 1",
            $date . ' 00:00:00'
        ));
        
        return $fixture;
    }
    
    /**
     * Get today's daily pick
     */
    public function get_todays_pick() {
        if (!SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            return null;
        }
        
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        $pick = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->picks_table} WHERE pick_date = %s",
            $today
        ));
        
        if ($pick && $pick->pick_payload) {
            $pick->payload = json_decode($pick->pick_payload, true);
        }
        
        return $pick;
    }
    
    /**
     * Get user's entry for a daily pick
     */
    public function get_user_entry($pick_id, $user_id) {
        global $wpdb;
        
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->entries_table} 
            WHERE daily_pick_id = %d AND user_id = %d",
            $pick_id,
            $user_id
        ));
        
        if ($entry && $entry->entry_payload) {
            $entry->payload = json_decode($entry->entry_payload, true);
        }
        
        return $entry;
    }
    
    /**
     * Submit a daily pick entry (or update existing)
     */
    public function submit_entry($pick_id, $user_id, $choice) {
        if (!SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            return new WP_Error('disabled', __('Daily Pick is currently disabled.', 'sportsrush-gamification'));
        }
        
        global $wpdb;
        
        // Get the pick
        $pick = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->picks_table} WHERE id = %d",
            $pick_id
        ));
        
        if (!$pick) {
            return new WP_Error('not_found', __('Daily Pick not found.', 'sportsrush-gamification'));
        }
        
        // Check if locked
        if (strtotime($pick->lock_time) <= current_time('timestamp')) {
            return new WP_Error('locked', __('This Daily Pick is now locked.', 'sportsrush-gamification'));
        }
        
        // Validate choice
        $valid_choices = array('home', 'away', 'draw');
        if (!in_array($choice, $valid_choices)) {
            return new WP_Error('invalid_choice', __('Invalid choice.', 'sportsrush-gamification'));
        }
        
        // Check if already entered - if so, update instead
        $existing = $this->get_user_entry($pick_id, $user_id);
        
        if ($existing) {
            // Update existing entry
            $result = $wpdb->update(
                $this->entries_table,
                array(
                    'entry_payload' => wp_json_encode(array('choice' => $choice)),
                ),
                array('id' => $existing->id),
                array('%s'),
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('db_error', __('Failed to update entry.', 'sportsrush-gamification'));
            }
            
            return 'updated';
        }
        
        // Insert new entry
        $result = $wpdb->insert(
            $this->entries_table,
            array(
                'daily_pick_id' => $pick_id,
                'user_id' => $user_id,
                'entry_payload' => wp_json_encode(array('choice' => $choice)),
                'points_awarded' => 0,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to save entry.', 'sportsrush-gamification'));
        }
        
        return true;
    }
    
    /**
     * AJAX handler for submitting entry
     */
    public function ajax_submit_entry() {
        check_ajax_referer('sr_gamification_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'sportsrush-gamification')));
        }
        
        // Accept both 'pick_id' and 'daily_pick_id' for flexibility
        $pick_id = isset($_POST['daily_pick_id']) ? intval($_POST['daily_pick_id']) : (isset($_POST['pick_id']) ? intval($_POST['pick_id']) : 0);
        $choice = isset($_POST['choice']) ? sanitize_text_field($_POST['choice']) : '';
        
        $result = $this->submit_entry($pick_id, get_current_user_id(), $choice);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        $message = ($result === 'updated') 
            ? __('Pick updated!', 'sportsrush-gamification')
            : __('Pick saved!', 'sportsrush-gamification');
        
        wp_send_json_success(array(
            'message' => $message,
            'choice' => $choice,
            'updated' => ($result === 'updated')
        ));
    }
    
    /**
     * Settle daily picks
     */
    public function settle_daily_picks() {
        if (!SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            return;
        }
        
        global $wpdb;
        
        // Get unsettled picks where fixture has a result
        $picks = $wpdb->get_results(
            "SELECT dp.*, m.home_score, m.away_score
            FROM {$this->picks_table} dp
            JOIN {$this->fp_prefix}matches m ON dp.fixture_id = m.id
            WHERE dp.settled = 0
            AND m.home_score IS NOT NULL
            AND m.away_score IS NOT NULL"
        );
        
        foreach ($picks as $pick) {
            $this->settle_pick($pick);
        }
    }
    
    /**
     * Settle a single pick
     */
    private function settle_pick($pick) {
        global $wpdb;
        
        // Determine the correct result
        $correct_result = 'draw';
        if ($pick->home_score > $pick->away_score) {
            $correct_result = 'home';
        } elseif ($pick->away_score > $pick->home_score) {
            $correct_result = 'away';
        }
        
        $points_correct = (int) get_option('sr_daily_pick_points_correct', 3);
        
        // Get all entries for this pick
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->entries_table} WHERE daily_pick_id = %d",
            $pick->id
        ));
        
        foreach ($entries as $entry) {
            $payload = json_decode($entry->entry_payload, true);
            $user_choice = isset($payload['choice']) ? $payload['choice'] : '';
            
            $points = 0;
            if ($user_choice === $correct_result) {
                $points = $points_correct;
                
                // Award achievement for first daily pick win
                if (SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
                    SR_Gamification()->achievements->maybe_award_achievement($entry->user_id, 'daily_pick_winner');
                }
                
                // Award a shield for winning the Daily Pick
                if (SR_Gamification()->feature_flags->is_enabled('streaks_enabled')) {
                    SR_Gamification()->streaks->award_shield($entry->user_id, 1);
                }
            }
            
            // Update entry with points
            $wpdb->update(
                $this->entries_table,
                array('points_awarded' => $points),
                array('id' => $entry->id),
                array('%d'),
                array('%d')
            );
            
            // Create notification for winner
            if ($points > 0 && SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
                SR_Gamification()->notifications->create_notification(
                    $entry->user_id,
                    'daily_pick_ready',
                    array(
                        'message' => sprintf(__('You won %d points on today\'s Daily Pick!', 'sportsrush-gamification'), $points),
                        'pick_id' => $pick->id,
                    )
                );
            }
        }
        
        // Mark pick as settled
        $wpdb->update(
            $this->picks_table,
            array(
                'settled' => 1,
                'settle_time' => current_time('mysql'),
            ),
            array('id' => $pick->id),
            array('%d', '%s'),
            array('%d')
        );
    }
    
    /**
     * Check if pick is locked
     */
    public function is_pick_locked($pick) {
        if (!$pick) {
            return true;
        }
        return strtotime($pick->lock_time) <= current_time('timestamp');
    }
    
    /**
     * Get time until lock
     */
    public function get_time_until_lock($pick) {
        if (!$pick) {
            return 0;
        }
        
        $lock_time = strtotime($pick->lock_time);
        $now = current_time('timestamp');
        
        return max(0, $lock_time - $now);
    }
    
    /**
     * Render daily pick widget
     */
    public function render_widget() {
        if (!SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')) {
            return '';
        }
        
        $pick = $this->get_todays_pick();
        
        if (!$pick) {
            return '<div class="sr-daily-pick-widget sr-no-pick"><p>' . esc_html__('No Daily Pick available today.', 'sportsrush-gamification') . '</p></div>';
        }
        
        $user_id = get_current_user_id();
        $user_entry = $user_id ? $this->get_user_entry($pick->id, $user_id) : null;
        $is_locked = $this->is_pick_locked($pick);
        $time_until_lock = $this->get_time_until_lock($pick);
        
        ob_start();
        ?>
        <div class="sr-daily-pick-widget" data-pick-id="<?php echo esc_attr($pick->id); ?>">
            <div class="sr-daily-pick-header">
                <h4 class="sr-daily-pick-title">
                    <span class="sr-icon">&#127919;</span>
                    <?php esc_html_e("Today's Pick", 'sportsrush-gamification'); ?>
                </h4>
                <?php if (!$is_locked && $time_until_lock > 0): ?>
                <div class="sr-daily-pick-countdown" data-lock-time="<?php echo esc_attr($pick->lock_time); ?>">
                    <span class="sr-countdown-label"><?php esc_html_e('Locks in:', 'sportsrush-gamification'); ?></span>
                    <span class="sr-countdown-time"><?php echo esc_html($this->format_countdown($time_until_lock)); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="sr-daily-pick-fixture">
                <div class="sr-fixture-teams">
                    <span class="sr-team sr-home-team"><?php echo esc_html($pick->payload['home_team_name']); ?></span>
                    <span class="sr-vs">vs</span>
                    <span class="sr-team sr-away-team"><?php echo esc_html($pick->payload['away_team_name']); ?></span>
                </div>
                <div class="sr-fixture-time">
                    <?php echo esc_html(date_i18n('D j M, H:i', strtotime($pick->payload['play_date']))); ?>
                </div>
            </div>
            
            <?php 
            // Determine current user choice if any
            $current_choice = $user_entry ? $user_entry->payload['choice'] : '';
            
            if ($pick->settled): ?>
                <?php // Show settled result ?>
                <div class="sr-daily-pick-settled">
                    <?php if ($user_entry): ?>
                        <p class="sr-entry-message">
                            <?php 
                            $choice_labels = array(
                                'home' => $pick->payload['home_team_name'] . ' ' . __('Win', 'sportsrush-gamification'),
                                'away' => $pick->payload['away_team_name'] . ' ' . __('Win', 'sportsrush-gamification'),
                                'draw' => __('Draw', 'sportsrush-gamification'),
                            );
                            $user_choice = $user_entry->payload['choice'];
                            printf(
                                esc_html__('Your pick: %s', 'sportsrush-gamification'),
                                '<strong>' . esc_html($choice_labels[$user_choice]) . '</strong>'
                            );
                            ?>
                        </p>
                        <?php if ($user_entry->points_awarded > 0): ?>
                        <p class="sr-entry-result sr-winner">
                            <?php printf(esc_html__('You won %d points!', 'sportsrush-gamification'), $user_entry->points_awarded); ?>
                        </p>
                        <?php else: ?>
                        <p class="sr-entry-result sr-loser">
                            <?php esc_html_e('Better luck next time!', 'sportsrush-gamification'); ?>
                        </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><?php esc_html_e('You did not enter this pick.', 'sportsrush-gamification'); ?></p>
                    <?php endif; ?>
                </div>
            <?php elseif ($is_locked): ?>
                <?php // Show locked state with visual buttons (disabled) ?>
                <div class="sr-daily-pick-locked">
                    <p class="sr-pick-question"><?php esc_html_e('Who will win?', 'sportsrush-gamification'); ?></p>
                    <div class="sr-pick-options sr-pick-options-locked">
                        <button type="button" class="sr-pick-option sr-pick-locked<?php echo $current_choice === 'home' ? ' selected' : ''; ?>" disabled>
                            <?php echo esc_html($pick->payload['home_team_name']); ?>
                        </button>
                        <button type="button" class="sr-pick-option sr-pick-locked<?php echo $current_choice === 'draw' ? ' selected' : ''; ?>" disabled>
                            <?php esc_html_e('Draw', 'sportsrush-gamification'); ?>
                        </button>
                        <button type="button" class="sr-pick-option sr-pick-locked<?php echo $current_choice === 'away' ? ' selected' : ''; ?>" disabled>
                            <?php echo esc_html($pick->payload['away_team_name']); ?>
                        </button>
                    </div>
                    <?php if ($user_entry): ?>
                        <p class="sr-locked-message">Your pick is locked - awaiting result</p>
                    <?php else: ?>
                        <p class="sr-locked-message"><?php esc_html_e('This pick is now locked.', 'sportsrush-gamification'); ?></p>
                    <?php endif; ?>
                </div>
            <?php elseif ($user_id): ?>
                <?php // Show interactive form - user can select/change their pick ?>
                <div class="sr-daily-pick-form" data-pick-id="<?php echo esc_attr($pick->id); ?>">
                    <input type="hidden" name="sr_daily_pick_id" value="<?php echo esc_attr($pick->id); ?>">
                    <input type="hidden" name="sr_pick_choice" value="<?php echo esc_attr($current_choice); ?>">
                    <p class="sr-pick-question"><?php esc_html_e('Who will win?', 'sportsrush-gamification'); ?></p>
                    <div class="sr-pick-options">
                        <button type="button" class="sr-pick-option<?php echo $current_choice === 'home' ? ' selected' : ''; ?>" data-value="home">
                            <?php echo esc_html($pick->payload['home_team_name']); ?>
                        </button>
                        <button type="button" class="sr-pick-option<?php echo $current_choice === 'draw' ? ' selected' : ''; ?>" data-value="draw">
                            <?php esc_html_e('Draw', 'sportsrush-gamification'); ?>
                        </button>
                        <button type="button" class="sr-pick-option<?php echo $current_choice === 'away' ? ' selected' : ''; ?>" data-value="away">
                            <?php echo esc_html($pick->payload['away_team_name']); ?>
                        </button>
                    </div>
                    <p class="sr-pick-status"></p>
                    <p class="sr-pick-points">
                        <?php printf(esc_html__('Win %d points for a correct pick!', 'sportsrush-gamification'), (int) get_option('sr_daily_pick_points_correct', 3)); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="sr-daily-pick-login">
                    <p><?php esc_html_e('Log in to make your pick!', 'sportsrush-gamification'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Format countdown time
     */
    private function format_countdown($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }
        
        return sprintf('%dm', $minutes);
    }
}
