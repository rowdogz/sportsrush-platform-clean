<?php
/**
 * Public-facing functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Public {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_notifications_bell_footer'));
        add_action('wp_head', array($this, 'add_admin_bar_fix'), 999);
        
        // Shortcodes
        add_shortcode('sr_daily_pick', array($this, 'shortcode_daily_pick'));
        add_shortcode('sr_streaks', array($this, 'shortcode_streaks'));
        add_shortcode('sr_achievements', array($this, 'shortcode_achievements'));
        add_shortcode('sr_rivals', array($this, 'shortcode_rivals'));
        add_shortcode('sr_mini_leaderboards', array($this, 'shortcode_mini_leaderboards'));
        add_shortcode('sr_banter', array($this, 'shortcode_banter'));
        add_shortcode('sr_today_widget', array($this, 'shortcode_today_widget'));
        
        // Filter leaderboard output to add position changes
        add_filter('footballpool_ranking_row', array($this, 'filter_ranking_row'), 10, 3);
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on relevant pages
        if (!$this->should_load_assets()) {
            return;
        }
        
        wp_enqueue_style(
            'sr-gamification-styles',
            SR_GAMIFICATION_PLUGIN_URL . 'public/css/sr-gamification.css',
            array(),
            SR_GAMIFICATION_VERSION
        );
        
        wp_enqueue_script(
            'sr-gamification-scripts',
            SR_GAMIFICATION_PLUGIN_URL . 'public/js/sr-gamification.js',
            array('jquery'),
            SR_GAMIFICATION_VERSION,
            true
        );
        
        wp_localize_script('sr-gamification-scripts', 'srGamification', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sr_gamification_nonce'),
            'isLoggedIn' => is_user_logged_in(),
            'strings' => array(
                'submitting' => __('Submitting...', 'sportsrush-gamification'),
                'success' => __('Success!', 'sportsrush-gamification'),
                'error' => __('An error occurred.', 'sportsrush-gamification'),
                'loading' => __('Loading...', 'sportsrush-gamification'),
                'noNotifications' => __('No notifications yet.', 'sportsrush-gamification'),
            ),
        ));
    }
    
    /**
     * Check if we should load assets
     */
    private function should_load_assets() {
        // Always load if any gamification feature is enabled
        $flags = SR_Gamification()->feature_flags;
        
        return $flags->is_enabled('rivals_enabled') ||
               $flags->is_enabled('position_changes_enabled') ||
               $flags->is_enabled('daily_pick_enabled') ||
               $flags->is_enabled('streaks_enabled') ||
               $flags->is_enabled('mini_leaderboards_enabled') ||
               $flags->is_enabled('achievements_enabled') ||
               $flags->is_enabled('banter_summaries_enabled') ||
               $flags->is_enabled('smart_notifications_enabled');
    }
    
    /**
     * Add CSS fix to hide admin bar search box that blocks theme toggle
     */
    public function add_admin_bar_fix() {
        if (is_admin_bar_showing()) {
            echo '<style type="text/css">
                #wpadminbar #wp-admin-bar-search,
                #wpadminbar .ab-top-menu #wp-admin-bar-search,
                #wp-admin-bar-search {
                    display: none !important;
                    visibility: hidden !important;
                    width: 0 !important;
                    height: 0 !important;
                    overflow: hidden !important;
                    position: absolute !important;
                    left: -9999px !important;
                }
            </style>';
        }
    }
    
    /**
     * Render notifications bell in footer
     */
    public function render_notifications_bell_footer() {
        if (!is_user_logged_in()) {
            return;
        }
        
        if (!SR_Gamification()->feature_flags->is_enabled('smart_notifications_enabled')) {
            return;
        }
        
        echo SR_Gamification()->notifications->render_bell();
    }
    
    /**
     * Daily Pick shortcode
     */
    public function shortcode_daily_pick($atts) {
        return SR_Gamification()->daily_pick->render_widget();
    }
    
    /**
     * Streaks shortcode
     */
    public function shortcode_streaks($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
        ), $atts);
        
        return SR_Gamification()->streaks->render_widget($atts['user_id']);
    }
    
    /**
     * Achievements shortcode
     */
    public function shortcode_achievements($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
        ), $atts);
        
        return SR_Gamification()->achievements->render_achievements_widget($atts['user_id']);
    }
    
    /**
     * Rivals shortcode
     */
    public function shortcode_rivals($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'context' => 'global',
            'context_id' => null,
        ), $atts);
        
        return SR_Gamification()->rivals->render_rivals_widget(
            $atts['user_id'],
            $atts['context'],
            $atts['context_id']
        );
    }
    
    /**
     * Mini leaderboards shortcode
     */
    public function shortcode_mini_leaderboards($atts) {
        $atts = shortcode_atts(array(
            'competition_id' => null,
            'league_id' => null,
        ), $atts);
        
        return SR_Gamification()->mini_leaderboards->render_tabbed_widget(
            $atts['competition_id'],
            $atts['league_id']
        );
    }
    
    /**
     * Banter shortcode
     */
    public function shortcode_banter($atts) {
        $atts = shortcode_atts(array(
            'league_id' => null,
        ), $atts);
        
        if (!$atts['league_id']) {
            return '';
        }
        
        return SR_Gamification()->banter->render_widget($atts['league_id']);
    }
    
    /**
     * Today widget shortcode - combines daily pick, streaks, and rivals
     */
    public function shortcode_today_widget($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'context' => 'global',
            'context_id' => null,
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="sr-today-widget sr-login-required"><p>' . 
                   esc_html__('Log in to see your daily stats!', 'sportsrush-gamification') . 
                   '</p></div>';
        }
        
        ob_start();
        ?>
        <div class="sr-today-widget">
            <h3 class="sr-today-title">
                <span class="sr-icon">&#127775;</span>
                <?php esc_html_e('Today', 'sportsrush-gamification'); ?>
            </h3>
            
            <div class="sr-today-grid">
                <?php if (SR_Gamification()->feature_flags->is_enabled('daily_pick_enabled')): ?>
                <div class="sr-today-section">
                    <?php echo SR_Gamification()->daily_pick->render_widget(); ?>
                </div>
                <?php endif; ?>
                
                <?php if (SR_Gamification()->feature_flags->is_enabled('streaks_enabled')): ?>
                <div class="sr-today-section">
                    <?php echo SR_Gamification()->streaks->render_widget($atts['user_id']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (SR_Gamification()->feature_flags->is_enabled('rivals_enabled')): ?>
                <div class="sr-today-section">
                    <?php echo SR_Gamification()->rivals->render_rivals_widget(
                        $atts['user_id'],
                        $atts['context'],
                        $atts['context_id']
                    ); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Filter ranking row to add position changes and badges
     */
    public function filter_ranking_row($row_html, $user_data, $ranking_data) {
        if (!SR_Gamification()->feature_flags->is_enabled('position_changes_enabled') &&
            !SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            return $row_html;
        }
        
        $user_id = isset($user_data['user_id']) ? $user_data['user_id'] : 0;
        
        if (!$user_id) {
            return $row_html;
        }
        
        $additions = '';
        
        // Add position change indicator
        if (SR_Gamification()->feature_flags->is_enabled('position_changes_enabled')) {
            $change = SR_Gamification()->snapshots->get_position_change($user_id, 'global');
            $additions .= SR_Gamification()->snapshots->render_position_indicator($change['change'], $change['direction']);
        }
        
        // Add achievement badges
        if (SR_Gamification()->feature_flags->is_enabled('achievements_enabled')) {
            $additions .= SR_Gamification()->achievements->render_user_badges($user_id);
        }
        
        if (!empty($additions)) {
            // Insert additions after the user name
            $row_html = preg_replace(
                '/(<td[^>]*class="[^"]*user[^"]*"[^>]*>.*?)(<\/td>)/s',
                '$1 ' . $additions . '$2',
                $row_html
            );
        }
        
        return $row_html;
    }
}
