<?php
/**
 * Admin Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class SR_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_form_submissions'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add submenu under existing SportsRush/Football Pool menu if it exists
        $parent_slug = 'footballpool-options';
        
        // Check if Football Pool admin menu exists
        global $submenu;
        if (!isset($submenu[$parent_slug])) {
            // Create our own top-level menu
            add_menu_page(
                __('SportsRush Gamification', 'sportsrush-gamification'),
                __('SR Gamification', 'sportsrush-gamification'),
                'manage_options',
                'sr-gamification',
                array($this, 'render_settings_page'),
                'dashicons-awards',
                30
            );
            $parent_slug = 'sr-gamification';
        } else {
            // Add as submenu to Football Pool
            add_submenu_page(
                $parent_slug,
                __('Gamification', 'sportsrush-gamification'),
                __('Gamification', 'sportsrush-gamification'),
                'manage_options',
                'sr-gamification',
                array($this, 'render_settings_page')
            );
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'sr-gamification') === false) {
            return;
        }
        
        wp_enqueue_style(
            'sr-admin-styles',
            SR_GAMIFICATION_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            SR_GAMIFICATION_VERSION
        );
        
        wp_enqueue_script(
            'sr-admin-scripts',
            SR_GAMIFICATION_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            SR_GAMIFICATION_VERSION,
            true
        );
        
        wp_localize_script('sr-admin-scripts', 'srAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sr_admin_nonce'),
            'strings' => array(
                'running' => __('Running...', 'sportsrush-gamification'),
                'success' => __('Success!', 'sportsrush-gamification'),
                'error' => __('Error occurred.', 'sportsrush-gamification'),
            ),
        ));
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        if (!isset($_POST['sr_admin_action'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('sr_admin_settings', 'sr_admin_nonce');
        
        $action = sanitize_text_field($_POST['sr_admin_action']);
        
        switch ($action) {
            case 'save_feature_flags':
                $this->save_feature_flags();
                break;
            case 'save_settings':
                $this->save_settings();
                break;
        }
        
        wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
        exit;
    }
    
    /**
     * Save feature flags
     */
    private function save_feature_flags() {
        $flags = SR_Gamification()->feature_flags;
        $default_flags = $flags->get_default_flags();
        
        $new_flags = array();
        foreach (array_keys($default_flags) as $flag_name) {
            $new_flags[$flag_name] = isset($_POST['sr_flags'][$flag_name]) && $_POST['sr_flags'][$flag_name] === '1';
        }
        
        $flags->set_flags($new_flags);
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $settings = array(
            'sr_daily_pick_points_correct' => 'intval',
            'sr_daily_pick_points_partial' => 'intval',
            'sr_login_streak_points' => 'intval',
            'sr_shield_cost_points' => 'intval',
            'sr_deadline_warning_minutes' => 'intval',
        );
        
        foreach ($settings as $key => $sanitize) {
            if (isset($_POST[$key])) {
                $value = call_user_func($sanitize, $_POST[$key]);
                update_option($key, $value);
            }
        }
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'flags';
        
        ?>
        <div class="wrap sr-admin-wrap">
            <h1><?php esc_html_e('SportsRush Gamification', 'sportsrush-gamification'); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Settings saved successfully.', 'sportsrush-gamification'); ?></p>
            </div>
            <?php endif; ?>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=sr-gamification&tab=flags" class="nav-tab <?php echo $active_tab === 'flags' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Feature Flags', 'sportsrush-gamification'); ?>
                </a>
                <a href="?page=sr-gamification&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'sportsrush-gamification'); ?>
                </a>
                <a href="?page=sr-gamification&tab=shields" class="nav-tab <?php echo $active_tab === 'shields' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Manage Shields', 'sportsrush-gamification'); ?>
                </a>
                <a href="?page=sr-gamification&tab=cron" class="nav-tab <?php echo $active_tab === 'cron' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Cron Jobs', 'sportsrush-gamification'); ?>
                </a>
                <a href="?page=sr-gamification&tab=status" class="nav-tab <?php echo $active_tab === 'status' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Status', 'sportsrush-gamification'); ?>
                </a>
            </nav>
            
            <div class="sr-admin-content">
                <?php
                switch ($active_tab) {
                    case 'flags':
                        $this->render_flags_tab();
                        break;
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'shields':
                        $this->render_shields_tab();
                        break;
                    case 'cron':
                        $this->render_cron_tab();
                        break;
                    case 'status':
                        $this->render_status_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render feature flags tab
     */
    private function render_flags_tab() {
        $flags = SR_Gamification()->feature_flags;
        $current_flags = $flags->get_all_flags();
        $flag_labels = $flags->get_flag_labels();
        
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('sr_admin_settings', 'sr_admin_nonce'); ?>
            <input type="hidden" name="sr_admin_action" value="save_feature_flags">
            
            <table class="form-table sr-flags-table">
                <tbody>
                    <?php foreach ($flag_labels as $flag_name => $info): ?>
                    <tr>
                        <th scope="row">
                            <label for="sr_flag_<?php echo esc_attr($flag_name); ?>">
                                <?php echo esc_html($info['label']); ?>
                            </label>
                        </th>
                        <td>
                            <label class="sr-toggle">
                                <input type="checkbox" 
                                       id="sr_flag_<?php echo esc_attr($flag_name); ?>"
                                       name="sr_flags[<?php echo esc_attr($flag_name); ?>]"
                                       value="1"
                                       <?php checked($current_flags[$flag_name], true); ?>>
                                <span class="sr-toggle-slider"></span>
                            </label>
                            <p class="description"><?php echo esc_html($info['description']); ?></p>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php submit_button(__('Save Feature Flags', 'sportsrush-gamification')); ?>
        </form>
        <?php
    }
    
    /**
     * Render settings tab
     */
    private function render_settings_tab() {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('sr_admin_settings', 'sr_admin_nonce'); ?>
            <input type="hidden" name="sr_admin_action" value="save_settings">
            
            <h2><?php esc_html_e('Daily Pick Settings', 'sportsrush-gamification'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sr_daily_pick_points_correct"><?php esc_html_e('Points for Correct Pick', 'sportsrush-gamification'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="sr_daily_pick_points_correct" name="sr_daily_pick_points_correct" 
                               value="<?php echo esc_attr(get_option('sr_daily_pick_points_correct', 3)); ?>" min="0" max="100">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sr_daily_pick_points_partial"><?php esc_html_e('Points for Partial Correct', 'sportsrush-gamification'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="sr_daily_pick_points_partial" name="sr_daily_pick_points_partial" 
                               value="<?php echo esc_attr(get_option('sr_daily_pick_points_partial', 1)); ?>" min="0" max="100">
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e('Login Streak Settings', 'sportsrush-gamification'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sr_login_streak_points"><?php esc_html_e('Points per 7-Day Streak', 'sportsrush-gamification'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="sr_login_streak_points" name="sr_login_streak_points" 
                               value="<?php echo esc_attr(get_option('sr_login_streak_points', 3)); ?>" min="0" max="100">
                        <p class="description"><?php esc_html_e('Points awarded every 7 consecutive login days (e.g., day 7, 14, 21, etc.). Set to 0 to disable.', 'sportsrush-gamification'); ?></p>
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e('Notification Settings', 'sportsrush-gamification'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sr_deadline_warning_minutes"><?php esc_html_e('Deadline Warning (minutes)', 'sportsrush-gamification'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="sr_deadline_warning_minutes" name="sr_deadline_warning_minutes" 
                               value="<?php echo esc_attr(get_option('sr_deadline_warning_minutes', 60)); ?>" min="15" max="1440">
                        <p class="description"><?php esc_html_e('How many minutes before a match deadline to send a warning notification.', 'sportsrush-gamification'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Settings', 'sportsrush-gamification')); ?>
        </form>
        <?php
    }
    
    /**
     * Render shields management tab
     */
    private function render_shields_tab() {
        global $wpdb;
        
        // Handle shield award form submission
        if (isset($_POST['sr_award_shield']) && check_admin_referer('sr_award_shield_nonce', 'sr_shield_nonce')) {
            $user_id = intval($_POST['sr_shield_user_id']);
            $shield_count = intval($_POST['sr_shield_count']);
            
            if ($user_id > 0 && $shield_count > 0) {
                SR_Gamification()->streaks->award_shield($user_id, $shield_count);
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     sprintf(esc_html__('Successfully awarded %d shield(s) to user.', 'sportsrush-gamification'), $shield_count) . 
                     '</p></div>';
            }
        }
        
        // Get all users with their shield counts
        $users_with_shields = $wpdb->get_results(
            "SELECT u.ID, u.display_name, u.user_email, s.shields_available, s.login_streak_count
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->prefix}sr_user_streaks s ON u.ID = s.user_id
             ORDER BY s.shields_available DESC, u.display_name ASC
             LIMIT 100"
        );
        
        ?>
        <h2><?php esc_html_e('Award Shields to User', 'sportsrush-gamification'); ?></h2>
        <p class="description"><?php esc_html_e('Shields protect users from losing their login streak when they miss a day. Users also earn 1 shield automatically when they win a Daily Pick.', 'sportsrush-gamification'); ?></p>
        
        <form method="post" action="" class="sr-award-shield-form">
            <?php wp_nonce_field('sr_award_shield_nonce', 'sr_shield_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sr_shield_user_id"><?php esc_html_e('Select User', 'sportsrush-gamification'); ?></label>
                    </th>
                    <td>
                        <select name="sr_shield_user_id" id="sr_shield_user_id" class="regular-text" required>
                            <option value=""><?php esc_html_e('-- Select a user --', 'sportsrush-gamification'); ?></option>
                            <?php
                            $all_users = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));
                            foreach ($all_users as $user) {
                                printf(
                                    '<option value="%d">%s (%s)</option>',
                                    esc_attr($user->ID),
                                    esc_html($user->display_name),
                                    esc_html($user->user_email)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sr_shield_count"><?php esc_html_e('Number of Shields', 'sportsrush-gamification'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="sr_shield_count" id="sr_shield_count" value="1" min="1" max="10" required>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Award Shields', 'sportsrush-gamification'), 'primary', 'sr_award_shield'); ?>
        </form>
        
        <h2 style="margin-top: 30px;"><?php esc_html_e('User Shields Overview', 'sportsrush-gamification'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('User', 'sportsrush-gamification'); ?></th>
                    <th><?php esc_html_e('Email', 'sportsrush-gamification'); ?></th>
                    <th><?php esc_html_e('Shields Available', 'sportsrush-gamification'); ?></th>
                    <th><?php esc_html_e('Current Login Streak', 'sportsrush-gamification'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users_with_shields)): ?>
                <tr>
                    <td colspan="4"><?php esc_html_e('No users found.', 'sportsrush-gamification'); ?></td>
                </tr>
                <?php else: ?>
                <?php foreach ($users_with_shields as $user): ?>
                <tr>
                    <td><strong><?php echo esc_html($user->display_name); ?></strong></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td>
                        <span class="sr-shield-count"><?php echo esc_html($user->shields_available ?: 0); ?></span>
                        <span class="dashicons dashicons-shield" style="color: #0073aa;"></span>
                    </td>
                    <td><?php echo esc_html($user->login_streak_count ?: 0); ?> <?php esc_html_e('days', 'sportsrush-gamification'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render cron tab
     */
    private function render_cron_tab() {
        $cron = SR_Gamification()->cron;
        $scheduled = $cron->get_scheduled_times();
        $log = $cron->get_cron_log();
        
        ?>
        <h2><?php esc_html_e('Scheduled Jobs', 'sportsrush-gamification'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Job', 'sportsrush-gamification'); ?></th>
                    <th><?php esc_html_e('Next Run', 'sportsrush-gamification'); ?></th>
                    <th><?php esc_html_e('Last Run', 'sportsrush-gamification'); ?></th>
                    <th><?php esc_html_e('Actions', 'sportsrush-gamification'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $job_keys = array(
                    'sr_generate_daily_pick' => 'daily_pick',
                    'sr_build_snapshots' => 'snapshots',
                    'sr_award_achievements' => 'achievements',
                    'sr_generate_banter' => 'banter',
                    'sr_settle_daily_picks' => 'settle_picks',
                    'sr_check_deadlines' => 'deadlines',
                );
                
                foreach ($scheduled as $hook => $info): 
                    $job_key = isset($job_keys[$hook]) ? $job_keys[$hook] : '';
                    $log_key = str_replace('sr_', '', $hook);
                    $log_key = str_replace('_', '_', $log_key);
                    // Map to actual log keys
                    $log_map = array(
                        'sr_generate_daily_pick' => 'daily_pick_generator',
                        'sr_build_snapshots' => 'snapshot_builder',
                        'sr_award_achievements' => 'achievements_processor',
                        'sr_generate_banter' => 'banter_generator',
                        'sr_settle_daily_picks' => 'daily_pick_settler',
                        'sr_check_deadlines' => 'deadline_checker',
                    );
                    $log_key = isset($log_map[$hook]) ? $log_map[$hook] : '';
                ?>
                <tr>
                    <td><strong><?php echo esc_html($info['label']); ?></strong></td>
                    <td><?php echo esc_html($info['next_run']); ?></td>
                    <td><?php echo isset($log[$log_key]) ? esc_html($log[$log_key]) : __('Never', 'sportsrush-gamification'); ?></td>
                    <td>
                        <?php if ($job_key): ?>
                        <button type="button" class="button sr-run-cron-now" data-job="<?php echo esc_attr($job_key); ?>">
                            <?php esc_html_e('Run Now', 'sportsrush-gamification'); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p class="description" style="margin-top: 20px;">
            <?php esc_html_e('Note: Cron jobs run automatically at their scheduled times. Use "Run Now" for testing or to manually trigger a job.', 'sportsrush-gamification'); ?>
        </p>
        <?php
    }
    
    /**
     * Render status tab
     */
    private function render_status_tab() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'sr_daily_picks',
            $wpdb->prefix . 'sr_daily_pick_entries',
            $wpdb->prefix . 'sr_user_streaks',
            $wpdb->prefix . 'sr_achievements',
            $wpdb->prefix . 'sr_user_achievements',
            $wpdb->prefix . 'sr_user_rivals',
            $wpdb->prefix . 'sr_leaderboard_snapshots',
            $wpdb->prefix . 'sr_notifications',
            $wpdb->prefix . 'sr_banter_summaries',
        );
        
        ?>
        <h2><?php esc_html_e('Database Tables', 'sportsrush-gamification'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Table', 'sportsrush-gamification'); ?></th>
                    <th><?php esc_html_e('Status', 'sportsrush-gamification'); ?></th>
                    <th><?php esc_html_e('Rows', 'sportsrush-gamification'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $table): 
                    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
                    $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0;
                ?>
                <tr>
                    <td><code><?php echo esc_html($table); ?></code></td>
                    <td>
                        <?php if ($exists): ?>
                        <span class="sr-status-ok">&#10004; <?php esc_html_e('OK', 'sportsrush-gamification'); ?></span>
                        <?php else: ?>
                        <span class="sr-status-error">&#10008; <?php esc_html_e('Missing', 'sportsrush-gamification'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($count); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2 style="margin-top: 30px;"><?php esc_html_e('Plugin Information', 'sportsrush-gamification'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Plugin Version', 'sportsrush-gamification'); ?></th>
                    <td><?php echo esc_html(SR_GAMIFICATION_VERSION); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Database Version', 'sportsrush-gamification'); ?></th>
                    <td><?php echo esc_html(get_option('sr_gamification_db_version', 'Not set')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Football Pool Plugin', 'sportsrush-gamification'); ?></th>
                    <td>
                        <?php if (class_exists('Football_Pool')): ?>
                        <span class="sr-status-ok">&#10004; <?php esc_html_e('Active', 'sportsrush-gamification'); ?></span>
                        <?php else: ?>
                        <span class="sr-status-error">&#10008; <?php esc_html_e('Not Active', 'sportsrush-gamification'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2 style="margin-top: 30px;"><?php esc_html_e('Quick Stats', 'sportsrush-gamification'); ?></h2>
        <?php
        $stats = array();
        
        // Get some quick stats
        $stats['total_achievements'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sr_achievements");
        $stats['awarded_achievements'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sr_user_achievements");
        $stats['active_streaks'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sr_user_streaks WHERE login_streak_count > 0");
        $stats['daily_picks'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sr_daily_picks");
        $stats['snapshots'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sr_leaderboard_snapshots");
        $stats['unread_notifications'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sr_notifications WHERE is_read = 0");
        ?>
        <div class="sr-stats-grid">
            <div class="sr-stat-card">
                <span class="sr-stat-value"><?php echo esc_html($stats['total_achievements']); ?></span>
                <span class="sr-stat-label"><?php esc_html_e('Achievements', 'sportsrush-gamification'); ?></span>
            </div>
            <div class="sr-stat-card">
                <span class="sr-stat-value"><?php echo esc_html($stats['awarded_achievements']); ?></span>
                <span class="sr-stat-label"><?php esc_html_e('Awarded', 'sportsrush-gamification'); ?></span>
            </div>
            <div class="sr-stat-card">
                <span class="sr-stat-value"><?php echo esc_html($stats['active_streaks']); ?></span>
                <span class="sr-stat-label"><?php esc_html_e('Active Streaks', 'sportsrush-gamification'); ?></span>
            </div>
            <div class="sr-stat-card">
                <span class="sr-stat-value"><?php echo esc_html($stats['daily_picks']); ?></span>
                <span class="sr-stat-label"><?php esc_html_e('Daily Picks', 'sportsrush-gamification'); ?></span>
            </div>
            <div class="sr-stat-card">
                <span class="sr-stat-value"><?php echo esc_html($stats['snapshots']); ?></span>
                <span class="sr-stat-label"><?php esc_html_e('Snapshots', 'sportsrush-gamification'); ?></span>
            </div>
            <div class="sr-stat-card">
                <span class="sr-stat-value"><?php echo esc_html($stats['unread_notifications']); ?></span>
                <span class="sr-stat-label"><?php esc_html_e('Unread Notifications', 'sportsrush-gamification'); ?></span>
            </div>
        </div>
        <?php
    }
}
