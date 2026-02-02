<?php
/**
 * Smart Notifications System
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Notifications {
    
    private $table_name;
    private $fp_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sr_notifications';
        $this->fp_prefix = 'pool_' . $wpdb->prefix;
    }
    
    /**
     * Create a notification
     */
    public function create_notification($user_id, $type, $payload = array()) {
        if (!SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            return false;
        }
        
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'notification_type' => $type,
                'payload' => wp_json_encode($payload),
                'is_read' => 0,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Get notifications for a user
     */
    public function get_notifications($user_id, $limit = 20, $include_read = false) {
        global $wpdb;
        
        $where_read = $include_read ? '' : 'AND is_read = 0';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE user_id = %d
            $where_read
            ORDER BY created_at DESC
            LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
        
        foreach ($results as &$notification) {
            if ($notification['payload']) {
                $notification['payload'] = json_decode($notification['payload'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get unread count for a user
     */
    public function get_unread_count($user_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
            WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }
    
    /**
     * Mark notification as read
     */
    public function mark_read($notification_id, $user_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('is_read' => 1),
            array('id' => $notification_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function mark_all_read($user_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('is_read' => 1),
            array('user_id' => $user_id, 'is_read' => 0),
            array('%d'),
            array('%d', '%d')
        );
    }
    
    /**
     * AJAX handler for marking notification as read
     */
    public function ajax_mark_read() {
        check_ajax_referer('sr_gamification_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Not logged in.', 'sportsrush-gamification')));
        }
        
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if (!$notification_id) {
            wp_send_json_error(array('message' => __('Invalid notification.', 'sportsrush-gamification')));
        }
        
        $this->mark_read($notification_id, get_current_user_id());
        
        wp_send_json_success(array(
            'unread_count' => $this->get_unread_count(get_current_user_id()),
        ));
    }
    
    /**
     * AJAX handler for marking all notifications as read
     */
    public function ajax_mark_all_read() {
        check_ajax_referer('sr_gamification_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Not logged in.', 'sportsrush-gamification')));
        }
        
        $this->mark_all_read(get_current_user_id());
        
        wp_send_json_success(array('unread_count' => 0));
    }
    
    /**
     * AJAX handler for getting notifications
     */
    public function ajax_get_notifications() {
        check_ajax_referer('sr_gamification_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Not logged in.', 'sportsrush-gamification')));
        }
        
        $notifications = $this->get_notifications(get_current_user_id(), 20, true);
        
        wp_send_json_success(array(
            'notifications' => $notifications,
            'unread_count' => $this->get_unread_count(get_current_user_id()),
        ));
    }
    
    /**
     * Check for deadline warnings
     */
    public function check_deadline_warnings() {
        if (!SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            return;
        }
        
        global $wpdb;
        
        $warning_minutes = (int) get_option('sr_deadline_warning_minutes', 60);
        $warning_time = date('Y-m-d H:i:s', strtotime("+{$warning_minutes} minutes"));
        $now = current_time('mysql');
        
        // Get upcoming matches within warning window
        $upcoming_matches = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, ht.name as home_team_name, at.name as away_team_name
            FROM {$this->fp_prefix}matches m
            JOIN {$this->fp_prefix}teams ht ON m.home_team_id = ht.id
            JOIN {$this->fp_prefix}teams at ON m.away_team_id = at.id
            WHERE m.play_date > %s
            AND m.play_date <= %s
            AND m.home_score IS NULL",
            $now,
            $warning_time
        ));
        
        foreach ($upcoming_matches as $match) {
            // Find users who haven't predicted this match
            $users_without_prediction = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT lu.user_id
                FROM {$this->fp_prefix}league_users lu
                WHERE lu.user_id NOT IN (
                    SELECT p.user_id FROM {$this->fp_prefix}predictions p
                    WHERE p.match_id = %d
                )
                AND lu.user_id NOT IN (
                    SELECT n.user_id FROM {$this->table_name} n
                    WHERE n.notification_type = 'deadline_soon'
                    AND JSON_EXTRACT(n.payload, '$.match_id') = %d
                    AND n.created_at > DATE_SUB(%s, INTERVAL 2 HOUR)
                )",
                $match->id,
                $match->id,
                $now
            ));
            
            foreach ($users_without_prediction as $user_id) {
                $this->create_notification(
                    $user_id,
                    'deadline_soon',
                    array(
                        'match_id' => $match->id,
                        'home_team' => $match->home_team_name,
                        'away_team' => $match->away_team_name,
                        'kickoff' => $match->play_date,
                        'message' => sprintf(
                            __('%s vs %s kicks off soon! Make your prediction.', 'sportsrush-gamification'),
                            $match->home_team_name,
                            $match->away_team_name
                        ),
                    )
                );
            }
        }
    }
    
    /**
     * Check for rival overtakes
     */
    public function check_rival_overtakes() {
        if (!SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            return;
        }
        
        if (!SR_Gamification()->feature_flags->is_enabled('rivals_enabled')) {
            return;
        }
        
        global $wpdb;
        
        // Get all users with rivals
        $rivals_table = $wpdb->prefix . 'sr_user_rivals';
        
        $users_with_rivals = $wpdb->get_results(
            "SELECT DISTINCT user_id FROM {$rivals_table}"
        );
        
        foreach ($users_with_rivals as $row) {
            $overtake = SR_Gamification()->rivals->check_rival_overtake($row->user_id, 'global');
            
            if ($overtake) {
                $this->create_notification(
                    $row->user_id,
                    'rival_overtook',
                    array(
                        'overtaker_id' => $overtake['overtaker']['user_id'],
                        'overtaker_name' => $overtake['overtaker']['display_name'],
                        'context_type' => $overtake['context_type'],
                        'context_id' => $overtake['context_id'],
                        'message' => sprintf(
                            __('%s has overtaken you in the rankings!', 'sportsrush-gamification'),
                            $overtake['overtaker']['display_name']
                        ),
                    )
                );
            }
        }
    }
    
    /**
     * Get notification icon
     */
    private function get_notification_icon($type) {
        $icons = array(
            'rival_overtook' => '&#128104;&#8205;&#128104;&#8205;&#128102;',
            'deadline_soon' => '&#9200;',
            'rank_change' => '&#128200;',
            'banter_ready' => '&#128172;',
            'daily_pick_ready' => '&#127919;',
            'achievement_earned' => '&#127942;',
        );
        
        return isset($icons[$type]) ? $icons[$type] : '&#128276;';
    }
    
    /**
     * Format notification for display
     */
    public function format_notification($notification) {
        $payload = is_array($notification['payload']) ? $notification['payload'] : array();
        $message = isset($payload['message']) ? $payload['message'] : '';
        
        if (empty($message)) {
            switch ($notification['notification_type']) {
                case 'rival_overtook':
                    $message = __('Someone has overtaken you in the rankings!', 'sportsrush-gamification');
                    break;
                case 'deadline_soon':
                    $message = __('A match deadline is approaching!', 'sportsrush-gamification');
                    break;
                case 'banter_ready':
                    $message = __('Weekly banter summary is ready!', 'sportsrush-gamification');
                    break;
                case 'daily_pick_ready':
                    $message = __('Today\'s Daily Pick is available!', 'sportsrush-gamification');
                    break;
                case 'achievement_earned':
                    $achievement_name = isset($payload['achievement_name']) ? $payload['achievement_name'] : '';
                    $message = sprintf(__('Achievement unlocked: %s', 'sportsrush-gamification'), $achievement_name);
                    break;
                default:
                    $message = __('You have a new notification.', 'sportsrush-gamification');
            }
        }
        
        return array(
            'id' => $notification['id'],
            'type' => $notification['notification_type'],
            'icon' => $this->get_notification_icon($notification['notification_type']),
            'message' => $message,
            'is_read' => (bool) $notification['is_read'],
            'created_at' => $notification['created_at'],
            'time_ago' => human_time_diff(strtotime($notification['created_at']), current_time('timestamp')),
            'payload' => $payload,
        );
    }
    
    /**
     * Render notifications bell
     */
    public function render_bell() {
        if (!SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            return '';
        }
        
        if (!is_user_logged_in()) {
            return '';
        }
        
        $unread_count = $this->get_unread_count(get_current_user_id());
        
        ob_start();
        ?>
        <div class="sr-notifications-bell" id="sr-notifications-bell">
            <button type="button" class="sr-bell-button" aria-label="<?php esc_attr_e('Notifications', 'sportsrush-gamification'); ?>">
                <span class="sr-bell-icon">&#128276;</span>
                <?php if ($unread_count > 0): ?>
                <span class="sr-bell-badge"><?php echo esc_html($unread_count > 99 ? '99+' : $unread_count); ?></span>
                <?php endif; ?>
            </button>
            
            <div class="sr-notifications-dropdown" id="sr-notifications-dropdown" style="display: none;">
                <div class="sr-notifications-header">
                    <h5><?php esc_html_e('Notifications', 'sportsrush-gamification'); ?></h5>
                    <button type="button" class="sr-mark-all-read" id="sr-mark-all-read">
                        <?php esc_html_e('Mark all read', 'sportsrush-gamification'); ?>
                    </button>
                </div>
                <div class="sr-notifications-list" id="sr-notifications-list">
                    <div class="sr-notifications-loading">
                        <?php esc_html_e('Loading...', 'sportsrush-gamification'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render notifications list HTML
     */
    public function render_notifications_list($user_id) {
        $notifications = $this->get_notifications($user_id, 20, true);
        
        if (empty($notifications)) {
            return '<div class="sr-no-notifications">' . esc_html__('No notifications yet.', 'sportsrush-gamification') . '</div>';
        }
        
        ob_start();
        foreach ($notifications as $notification) {
            $formatted = $this->format_notification($notification);
            ?>
            <div class="sr-notification-item <?php echo $formatted['is_read'] ? 'sr-read' : 'sr-unread'; ?>" 
                 data-notification-id="<?php echo esc_attr($formatted['id']); ?>">
                <span class="sr-notification-icon"><?php echo $formatted['icon']; ?></span>
                <div class="sr-notification-content">
                    <p class="sr-notification-message"><?php echo esc_html($formatted['message']); ?></p>
                    <span class="sr-notification-time"><?php echo esc_html($formatted['time_ago']); ?> <?php esc_html_e('ago', 'sportsrush-gamification'); ?></span>
                </div>
            </div>
            <?php
        }
        return ob_get_clean();
    }
    
    /**
     * Clean up old notifications
     */
    public function cleanup_old_notifications($days = 30) {
        global $wpdb;
        
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < %s AND is_read = 1",
            $cutoff
        ));
    }
}
