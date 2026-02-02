<?php
/**
 * Plugin Name: Scraper Competitions Admin
 * Description: Manage valid rugby competitions for the scraper (titles, DB names, date ranges, active flag, RL.com URL) from the WP dashboard.
 * Version: 1.1.0
 * Author: SportsRush
 */

if (!defined('ABSPATH')) exit;

class Scraper_Competitions_Admin {
    const TABLE = 'pool_wpkl_scrape_competitions';
    const SLUG  = 'scraper-competitions';

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_scraper_competitions_save', [$this, 'handle_save']);
        add_action('admin_post_scraper_competitions_delete', [$this, 'handle_delete']);
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset = $wpdb->get_charset_collate();

        // Note: dbDelta will add new columns (like rlcom_url) safely.
        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `bbc_title` VARCHAR(255) NOT NULL,
            `db_name`   VARCHAR(255) NOT NULL,
            `rlcom_url` VARCHAR(500) NULL DEFAULT NULL,
            `start_date` DATE NULL DEFAULT NULL,
            `end_date`   DATE NULL DEFAULT NULL,
            `active`     TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `active_idx` (`active`),
            KEY `date_idx` (`start_date`,`end_date`)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Extra safety: if table existed before, ensure rlcom_url exists.
        $col = $wpdb->get_var($wpdb->prepare(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = %s
               AND COLUMN_NAME = 'rlcom_url'
             LIMIT 1",
            $table
        ));
        if (!$col) {
            $wpdb->query("ALTER TABLE `$table` ADD COLUMN `rlcom_url` VARCHAR(500) NULL DEFAULT NULL AFTER `db_name`");
        }
    }

    public function menu() {
        add_options_page(
            'Scraper Competitions',
            'Scraper Competitions',
            'manage_options',
            self::SLUG,
            [$this, 'render_page']
        );
    }

    private function fetch_rows() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->get_results("SELECT * FROM `$table` ORDER BY active DESC, bbc_title ASC", ARRAY_A);
    }

    private function fetch_row($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE id=%d", $id), ARRAY_A);
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) wp_die('Not allowed.');
        check_admin_referer('scraper_competitions_save');

        $id         = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $bbc_title  = isset($_POST['bbc_title']) ? sanitize_text_field(wp_unslash($_POST['bbc_title'])) : '';
        $db_name    = isset($_POST['db_name']) ? sanitize_text_field(wp_unslash($_POST['db_name'])) : '';
        $rlcom_url  = isset($_POST['rlcom_url']) ? esc_url_raw(wp_unslash($_POST['rlcom_url'])) : '';
        $start_date = isset($_POST['start_date']) && $_POST['start_date'] !== '' ? sanitize_text_field(wp_unslash($_POST['start_date'])) : null;
        $end_date   = isset($_POST['end_date']) && $_POST['end_date'] !== '' ? sanitize_text_field(wp_unslash($_POST['end_date'])) : null;
        $active     = isset($_POST['active']) ? 1 : 0;

        if ($bbc_title === '' || $db_name === '') {
            wp_redirect(add_query_arg(['page' => self::SLUG, 'error' => 'missing_fields'], admin_url('options-general.php')));
            exit;
        }

        // Optional: allow blank. If present, basic sanity check (must start http).
        if ($rlcom_url !== '' && !preg_match('#^https?://#i', $rlcom_url)) {
            wp_redirect(add_query_arg(['page' => self::SLUG, 'error' => 'bad_url'], admin_url('options-general.php')));
            exit;
        }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $data = [
            'bbc_title'  => $bbc_title,
            'db_name'    => $db_name,
            'rlcom_url'  => ($rlcom_url !== '' ? $rlcom_url : null),
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'active'     => $active,
        ];

        if ($id > 0) {
            $wpdb->update(
                $table,
                $data,
                ['id' => $id],
                ['%s','%s','%s','%s','%s','%d'],
                ['%d']
            );
            $msg = 'updated=1';
        } else {
            $wpdb->insert(
                $table,
                $data,
                ['%s','%s','%s','%s','%s','%d']
            );
            $msg = 'created=1';
        }

        wp_redirect(add_query_arg(['page' => self::SLUG, $msg => 1], admin_url('options-general.php')));
        exit;
    }

    public function handle_delete() {
        if (!current_user_can('manage_options')) wp_die('Not allowed.');
        check_admin_referer('scraper_competitions_delete');

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            global $wpdb;
            $table = $wpdb->prefix . self::TABLE;
            $wpdb->delete($table, ['id' => $id], ['%d']);
            wp_redirect(add_query_arg(['page' => self::SLUG, 'deleted' => 1], admin_url('options-general.php')));
            exit;
        }
        wp_redirect(add_query_arg(['page' => self::SLUG, 'error' => 'bad_id'], admin_url('options-general.php')));
        exit;
    }

    public function render_page() {
        if (!current_user_can('manage_options')) return;

        $editing = false;
        $row = null;
        if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
            $row = $this->fetch_row(intval($_GET['id']));
            if ($row) $editing = true;
        }

        $rows = $this->fetch_rows();
        ?>
        <div class="wrap">
            <h1>Scraper Competitions</h1>

            <?php if (isset($_GET['created'])): ?>
                <div class="notice notice-success"><p>Competition created.</p></div>
            <?php elseif (isset($_GET['updated'])): ?>
                <div class="notice notice-success"><p>Competition updated.</p></div>
            <?php elseif (isset($_GET['deleted'])): ?>
                <div class="notice notice-success"><p>Competition deleted.</p></div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'missing_fields'): ?>
                <div class="notice notice-error"><p>Please fill in all required fields.</p></div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'bad_url'): ?>
                <div class="notice notice-error"><p>Please enter a valid RL.com URL (must start with http:// or https://), or leave it blank.</p></div>
            <?php endif; ?>

            <h2><?php echo $editing ? 'Edit Competition' : 'Add New Competition'; ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('scraper_competitions_save'); ?>
                <input type="hidden" name="action" value="scraper_competitions_save">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?php echo intval($row['id']); ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="bbc_title">BBC Title<span style="color:#d63638">*</span></label></th>
                        <td><input name="bbc_title" id="bbc_title" type="text" class="regular-text" required
                                   value="<?php echo esc_attr($editing ? $row['bbc_title'] : ''); ?>"
                                   placeholder="e.g., Betfred Super League"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="db_name">Database Competition Name<span style="color:#d63638">*</span></label></th>
                        <td><input name="db_name" id="db_name" type="text" class="regular-text" required
                                   value="<?php echo esc_attr($editing ? $row['db_name'] : ''); ?>"
                                   placeholder="e.g., Super League 2026"></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="rlcom_url">RL.com Match Centre URL</label></th>
                        <td>
                            <input name="rlcom_url" id="rlcom_url" type="url" class="regular-text"
                                   value="<?php echo esc_attr($editing ? ($row['rlcom_url'] ?? '') : ''); ?>"
                                   placeholder="e.g., https://www.rugby-league.com/competitions/pro-national/betfred-super-league/match-centre">
                            <p class="description">
                                Optional. If set, the RL.com scraper will use this URL for this competition.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="start_date">Start Date</label></th>
                        <td><input name="start_date" id="start_date" type="date"
                                   value="<?php echo esc_attr($editing && !empty($row['start_date']) ? $row['start_date'] : ''); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="end_date">End Date</label></th>
                        <td><input name="end_date" id="end_date" type="date"
                                   value="<?php echo esc_attr($editing && !empty($row['end_date']) ? $row['end_date'] : ''); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Active</th>
                        <td><label><input type="checkbox" name="active" <?php checked($editing ? ((int)$row['active'] === 1) : true); ?>> Enabled</label></td>
                    </tr>
                </table>

                <?php submit_button($editing ? 'Update Competition' : 'Add Competition'); ?>
            </form>

            <hr>
            <h2>All Competitions</h2>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>BBC Title</th>
                    <th>DB Name</th>
                    <th>RL.com URL</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($rows): foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo intval($r['id']); ?></td>
                        <td><?php echo esc_html($r['bbc_title']); ?></td>
                        <td><?php echo esc_html($r['db_name']); ?></td>
                        <td>
                            <?php if (!empty($r['rlcom_url'])): ?>
                                <a href="<?php echo esc_url($r['rlcom_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html($r['rlcom_url']); ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(!empty($r['start_date']) ? $r['start_date'] : '—'); ?></td>
                        <td><?php echo esc_html(!empty($r['end_date']) ? $r['end_date'] : '—'); ?></td>
                        <td><?php echo !empty($r['active']) ? 'Yes' : 'No'; ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(['page' => self::SLUG, 'action' => 'edit', 'id' => $r['id']], admin_url('options-general.php'))); ?>">Edit</a>
                            &nbsp;|&nbsp;
                            <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'scraper_competitions_delete', 'id' => $r['id']], admin_url('admin-post.php')), 'scraper_competitions_delete'); ?>"
                               onclick="return confirm('Delete this competition?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8">No competitions yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

new Scraper_Competitions_Admin();