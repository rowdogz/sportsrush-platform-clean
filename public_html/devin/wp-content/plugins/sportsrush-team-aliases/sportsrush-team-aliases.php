<?php
/*
Plugin Name: SportsRush Team Aliases
Description: Manage team name aliases (scraped name -> canonical team) for SportsRush scrapers, plus an Alias Scanner powered by rlcom_alias_scan_out.json.
Version: 1.2
Author: Bperrow
*/

if (!defined('ABSPATH')) exit;

function sr_aliases_tables() {
    return [
        'aliases' => 'pool_wpkl_team_aliases',
        'teams'   => 'pool_wpkl_teams',
        'matches' => 'pool_wpkl_matches',
    ];
}

function sr_aliases_paths() {
    // Where your Python script writes the JSON
    return [
        'json_path'   => '/home/u108848352/domains/sportsrush.co.uk/public_html/scripts/rlcom_alias_scan_out.json',
        'scan_script' => '/home/u108848352/domains/sportsrush.co.uk/public_html/scripts/rlcom-alias-scan.py',
    ];
}

function sr_norm($s) {
    $s = strtolower(trim((string)$s));
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

add_action('admin_menu', function () {
    add_menu_page(
        'Team Aliases',
        'Team Aliases',
        'manage_options',
        'sportsrush-team-aliases',
        'sportsrush_team_aliases_admin_page',
        'dashicons-randomize',
        81
    );

    add_submenu_page(
        'sportsrush-team-aliases',
        'Alias Scanner',
        'Alias Scanner',
        'manage_options',
        'sportsrush-team-aliases-scanner',
        'sportsrush_team_aliases_scanner_page'
    );
});

//
// MAIN ADMIN PAGE
//
function sportsrush_team_aliases_admin_page() {
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $t = sr_aliases_tables();
    $aliases_table = $t['aliases'];
    $teams_table   = $t['teams'];

    // Delete alias
    if (isset($_GET['delete']) && is_numeric($_GET['delete']) && check_admin_referer('sr_delete_alias')) {
        $id = intval($_GET['delete']);
        $wpdb->delete($aliases_table, ['id' => $id], ['%d']);
        echo '<div class="updated notice"><p>Alias deleted.</p></div>';
    }

    // Add/update alias
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['sr_alias_nonce'])
        && wp_verify_nonce($_POST['sr_alias_nonce'], 'sr_save_alias')
    ) {
        $alias_name = sanitize_text_field($_POST['alias_name'] ?? '');
        $team_id    = intval($_POST['team_id'] ?? 0);
        $edit_id    = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;

        if (!$alias_name || !$team_id) {
            echo '<div class="notice notice-error"><p>Please provide an alias name and select a team.</p></div>';
        } else {
            if ($edit_id > 0) {
                $wpdb->update(
                    $aliases_table,
                    ['alias_name' => $alias_name, 'team_id' => $team_id],
                    ['id' => $edit_id],
                    ['%s', '%d'],
                    ['%d']
                );
                echo '<div class="updated notice"><p>Alias updated.</p></div>';
            } else {
                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$aliases_table} (alias_name, team_id)
                         VALUES (%s, %d)
                         ON DUPLICATE KEY UPDATE team_id = VALUES(team_id)",
                        $alias_name,
                        $team_id
                    )
                );
                echo '<div class="updated notice"><p>Alias saved.</p></div>';
            }
        }
    }

    // Edit mode
    $edit_row = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        $edit_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$aliases_table} WHERE id = %d", $edit_id));
    }

    // Load teams + aliases
    $teams = $wpdb->get_results("SELECT id, name FROM {$teams_table} ORDER BY name ASC");
    $aliases = $wpdb->get_results("
        SELECT a.id, a.alias_name, a.team_id, t.name AS team_name
        FROM {$aliases_table} a
        LEFT JOIN {$teams_table} t ON a.team_id = t.id
        ORDER BY a.alias_name ASC
    ");
    ?>
    <div class="wrap">
        <h1>Team Aliases</h1>
        <p>Map scraped names (e.g. <code>Hull KR</code>) to your canonical team record (e.g. <code>Hull Kingston Rovers</code>).</p>

        <p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=sportsrush-team-aliases-scanner')); ?>">
                Open Alias Scanner
            </a>
        </p>

        <h2><?php echo $edit_row ? 'Edit Alias' : 'Add Alias'; ?></h2>
        <form method="post">
            <?php wp_nonce_field('sr_save_alias', 'sr_alias_nonce'); ?>
            <?php if ($edit_row): ?>
                <input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_row->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th><label for="alias_name">Alias name</label></th>
                    <td>
                        <input type="text" name="alias_name" id="alias_name" class="regular-text"
                               value="<?php echo esc_attr($edit_row->alias_name ?? ''); ?>" required>
                        <p class="description">Exactly as the scraper sees it.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="team_id">Canonical team</label></th>
                    <td>
                        <select name="team_id" id="team_id" required>
                            <option value="">Select team…</option>
                            <?php foreach ($teams as $tm): ?>
                                <option value="<?php echo intval($tm->id); ?>"
                                    <?php selected(($edit_row->team_id ?? 0), $tm->id); ?>>
                                    <?php echo esc_html($tm->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button($edit_row ? 'Update Alias' : 'Save Alias'); ?>
        </form>

        <hr style="margin: 30px 0;">

        <h2>Existing Aliases</h2>
        <?php if ($aliases): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Alias</th>
                        <th>Canonical Team</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($aliases as $a): ?>
                    <tr>
                        <td><?php echo esc_html($a->alias_name); ?></td>
                        <td><?php echo esc_html($a->team_name ?: '(missing team)'); ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=sportsrush-team-aliases&edit=' . intval($a->id))); ?>">Edit</a>
                            <a class="button button-link-delete"
                               href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=sportsrush-team-aliases&delete=' . intval($a->id)), 'sr_delete_alias')); ?>"
                               onclick="return confirm('Delete this alias?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No aliases yet.</p>
        <?php endif; ?>
    </div>
    <?php
}

//
// ALIAS SCANNER PAGE (Admin only)
//
function sportsrush_team_aliases_scanner_page() {
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $t = sr_aliases_tables();
    $p = sr_aliases_paths();

    $aliases_table = $t['aliases'];
    $teams_table   = $t['teams'];
    $matches_table = $t['matches'];

    $json_path = $p['json_path'];
    $scan_script = $p['scan_script'];

    // Apply mapping (alias_name -> canonical team_id)
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['sr_apply_mapping_nonce'])
        && wp_verify_nonce($_POST['sr_apply_mapping_nonce'], 'sr_apply_mapping')
        && isset($_POST['action']) && $_POST['action'] === 'apply_mapping'
    ) {
        $alias_name = sanitize_text_field($_POST['alias_name'] ?? '');
        $canonical_team_id = intval($_POST['canonical_team_id'] ?? 0);

        if (!$alias_name || !$canonical_team_id) {
            echo '<div class="notice notice-error"><p>Please select a canonical team.</p></div>';
        } else {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$aliases_table} (alias_name, team_id)
                     VALUES (%s, %d)
                     ON DUPLICATE KEY UPDATE team_id = VALUES(team_id)",
                    $alias_name,
                    $canonical_team_id
                )
            );
            echo '<div class="updated notice"><p>Alias mapping saved: <strong>' . esc_html($alias_name) . '</strong></p></div>';
        }
    }

    // Optional: run scan (requires shell_exec allowed)
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['sr_run_scan_nonce'])
        && wp_verify_nonce($_POST['sr_run_scan_nonce'], 'sr_run_scan')
        && isset($_POST['action']) && $_POST['action'] === 'run_scan'
    ) {
        if (!function_exists('shell_exec')) {
            echo '<div class="notice notice-error"><p>shell_exec is disabled on this host. Run the scan via SSH instead.</p></div>';
        } else {
            $cmd = escapeshellcmd($scan_script);
            // Capture stdout + stderr
            $out = shell_exec($cmd . ' 2>&1');
            echo '<div class="updated notice"><p>Scan attempted. Output:</p><pre style="white-space:pre-wrap;">' . esc_html($out) . '</pre></div>';
        }
    }

    // Load teams
    $teams = $wpdb->get_results("SELECT id, name FROM {$teams_table} ORDER BY name ASC");

    // Build lookup sets
    $team_names_norm = [];
    foreach ($teams as $tm) {
        $team_names_norm[sr_norm($tm->name)] = (int)$tm->id;
    }

    $alias_rows = $wpdb->get_results("SELECT alias_name, team_id FROM {$aliases_table}");
    $alias_names_norm = [];
    foreach ($alias_rows as $ar) {
        $alias_names_norm[sr_norm($ar->alias_name)] = (int)$ar->team_id;
    }

    // Read JSON
    $scan_data = null;
    if (file_exists($json_path)) {
        $raw = file_get_contents($json_path);
        $scan_data = json_decode($raw, true);
    }

    // Build unmapped list
    $unmapped = []; // rows: [competition, alias_name]
    if (is_array($scan_data)) {
        foreach ($scan_data as $comp => $names) {
            if (!is_array($names)) continue;
            foreach ($names as $n) {
                $n = trim((string)$n);
                if ($n === '') continue;

                $norm = sr_norm($n);

                // If exact team exists, it's fine
                if (isset($team_names_norm[$norm])) continue;

                // If already mapped as alias, fine
                if (isset($alias_names_norm[$norm])) continue;

                $unmapped[] = [
                    'competition' => (string)$comp,
                    'alias_name'  => $n
                ];
            }
        }
    }

    // Sort for readability
    usort($unmapped, function($a, $b) {
        $c = strcmp($a['competition'], $b['competition']);
        if ($c !== 0) return $c;
        return strcmp($a['alias_name'], $b['alias_name']);
    });

    ?>
    <div class="wrap">
        <h1>Alias Scanner</h1>
        <p>
            This page reads <code><?php echo esc_html($json_path); ?></code> (generated by your rl.com scan script),
            then lists any scraped team names that are not an exact match in <code>pool_wpkl_teams</code> and are not already in <code>pool_wpkl_team_aliases</code>.
        </p>

        <p style="color:#666;">
            This is intentionally conservative: no fuzzy auto-merge (so “York Knights” won’t get mixed up with “York Acorns”).
        </p>

        <hr>

        <h2>Run scan</h2>
        <p>
            If your host allows it, you can run the scan from here. If not, run it via SSH and refresh this page.
        </p>

        <form method="post" style="margin-bottom:20px;">
            <?php wp_nonce_field('sr_run_scan', 'sr_run_scan_nonce'); ?>
            <input type="hidden" name="action" value="run_scan">
            <button type="submit" class="button">Run Scan Now</button>
        </form>

        <h2>Unmapped scraped names</h2>

        <?php if (!file_exists($json_path)): ?>
            <div class="notice notice-error">
                <p>Scan JSON not found at: <code><?php echo esc_html($json_path); ?></code></p>
            </div>
        <?php elseif (!$scan_data): ?>
            <div class="notice notice-error">
                <p>Scan JSON exists but could not be parsed (invalid JSON).</p>
            </div>
        <?php elseif (empty($unmapped)): ?>
            <div class="notice notice-success">
                <p>No unmapped names found. Everything in the scan is either an existing team or already mapped.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Competition</th>
                        <th>Scraped name</th>
                        <th>Map to canonical team</th>
                        <th>Apply</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($unmapped as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['competition']); ?></td>
                        <td><strong><?php echo esc_html($row['alias_name']); ?></strong></td>
                        <td>
                            <form method="post" style="display:flex;gap:10px;align-items:center;margin:0;">
                                <?php wp_nonce_field('sr_apply_mapping', 'sr_apply_mapping_nonce'); ?>
                                <input type="hidden" name="action" value="apply_mapping">
                                <input type="hidden" name="alias_name" value="<?php echo esc_attr($row['alias_name']); ?>">

                                <select name="canonical_team_id" required>
                                    <option value="">Select team…</option>
                                    <?php foreach ($teams as $tm): ?>
                                        <option value="<?php echo intval($tm->id); ?>">
                                            <?php echo esc_html($tm->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit" class="button button-primary"
                                        onclick="return confirm('Create alias mapping for: <?php echo esc_js($row['alias_name']); ?> ?');">
                                    Apply
                                </button>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:12px;color:#666;">
                Apply will only create an alias record (<code>alias_name → team_id</code>). It will not merge/rename your teams automatically.
            </p>
        <?php endif; ?>
    </div>
    <?php
}

//
// Optional front-end UI: shortcode [team_alias_manager] (admins only)
//
add_shortcode('team_alias_manager', function () {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '';
    }
    ob_start();
    sportsrush_team_aliases_admin_page();
    return ob_get_clean();
});