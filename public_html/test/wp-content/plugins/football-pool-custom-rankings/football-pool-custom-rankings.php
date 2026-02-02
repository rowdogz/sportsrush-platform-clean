<?php
/*
Plugin Name: Football Pool Custom Rankings Page — v0 Layout
Description: v0-styled rankings page with competition filter, leaderboard (rank, total, monthly, accuracy, trend), sidebar stats, personal panel, and a Detailed Stats toggle.
Version: 2.1
Author: Your Name
*/

add_shortcode('football_pool_rankings', 'football_pool_custom_rankings_shortcode');

function football_pool_custom_rankings_shortcode() {
    if (!function_exists('date_i18n')) { require_once ABSPATH . WPINC . '/l10n.php'; }
    global $wpdb;

    // --------------------------------------------
    // 0) Helpers for dates (this month / last month)
    // --------------------------------------------
    $now = current_time('timestamp'); // WP-aware
    $first_day_this_month = gmdate('Y-m-01', $now);
    $first_day_next_month = gmdate('Y-m-01', strtotime('+1 month', strtotime($first_day_this_month)));
    $first_day_last_month = gmdate('Y-m-01', strtotime('-1 month', strtotime($first_day_this_month)));
    $last_day_last_month  = gmdate('Y-m-t',   strtotime('-1 month', strtotime($first_day_this_month)));

    // --------------------------------------------
    // 1) Competitions (visible only)
    // --------------------------------------------
    $competitions = $wpdb->get_results("
        SELECT id, name
        FROM pool_wpkl_matchtypes
        WHERE visibility = 1
        ORDER BY name ASC
    ");

    if (empty($competitions)) {
        return '<div class="rounded-2xl border bg-card p-5">No competitions available.</div>';
    }

    // Selected competition (default = first visible)
    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : intval($competitions[0]->id);
    $selected_comp = null;
    foreach ($competitions as $c) { if ((int)$c->id === $selected_matchtype_id) { $selected_comp = $c; break; } }
    if (!$selected_comp) { $selected_comp = $competitions[0]; $selected_matchtype_id = intval($selected_comp->id); }

    // --------------------------------------------
    // 2) Pick scorehistory table (s1_t2 if populated)
    // --------------------------------------------
    $table_to_use = "pool_wpkl_scorehistory_s1_t1";
    $check_s1_t2  = $wpdb->get_var("SELECT COUNT(*) FROM pool_wpkl_scorehistory_s1_t2");
    if (is_numeric($check_s1_t2) && intval($check_s1_t2) > 0) {
        $table_to_use = "pool_wpkl_scorehistory_s1_t2";
    }

    // --------------------------------------------
    // 3) Base rankings (total points + rank) for selected competition
    // --------------------------------------------
    $rankings = $wpdb->get_results($wpdb->prepare("
        SELECT 
            s.ranking        AS user_rank,
            s.total_score    AS total_points,
            u.ID             AS user_id,
            u.display_name   AS user_name
        FROM {$table_to_use} AS s
        JOIN pool_wpkl_rankings   AS r  ON s.ranking_id = r.id
        JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
        JOIN wpkl_users           AS u  ON s.user_id   = u.ID
        WHERE mt.visibility = 1
          AND mt.id = %d
        ORDER BY s.ranking ASC
    ", $selected_matchtype_id));

    // --------------------------------------------
    // 4) Monthly points (THIS month) for selected competition
    //    (using your pool_wpkl_football_match_scores table)
    // --------------------------------------------
    $monthly_points_this = $wpdb->get_results($wpdb->prepare("
        SELECT fms.user_id, SUM(fms.score) AS pts
        FROM pool_wpkl_football_match_scores AS fms
        JOIN pool_wpkl_matches AS m ON m.id = fms.match_id
        WHERE fms.competition_id = %d
          AND DATE(m.play_date) >= %s
          AND DATE(m.play_date)  < %s
        GROUP BY fms.user_id
    ", $selected_matchtype_id, $first_day_this_month, $first_day_next_month));
    $monthly_map_this = [];
    foreach ($monthly_points_this as $row) { $monthly_map_this[intval($row->user_id)] = intval($row->pts); }

    // Last month points (for trend)
    $monthly_points_last = $wpdb->get_results($wpdb->prepare("
        SELECT fms.user_id, SUM(fms.score) AS pts
        FROM pool_wpkl_football_match_scores AS fms
        JOIN pool_wpkl_matches AS m ON m.id = fms.match_id
        WHERE fms.competition_id = %d
          AND DATE(m.play_date) >= %s
          AND DATE(m.play_date) <= %s
        GROUP BY fms.user_id
    ", $selected_matchtype_id, $first_day_last_month, $last_day_last_month));
    $monthly_map_last = [];
    foreach ($monthly_points_last as $row) { $monthly_map_last[intval($row->user_id)] = intval($row->pts); }

    // --------------------------------------------
    // 5) Accuracy per user (correct / total) in this competition
    // --------------------------------------------
    $accuracy_rows = $wpdb->get_results($wpdb->prepare("
        SELECT 
            p.user_id,
            SUM(
                CASE
                    WHEN (m.home_score IS NOT NULL AND m.away_score IS NOT NULL)
                         AND (
                            (m.home_score > m.away_score AND p.home_score > p.away_score) OR
                            (m.home_score < m.away_score AND p.home_score < p.away_score) OR
                            (m.home_score = m.away_score AND p.home_score = p.away_score)
                         )
                    THEN 1 ELSE 0
                END
            ) AS correct_cnt,
            SUM(
                CASE
                    WHEN (m.home_score IS NOT NULL AND m.away_score IS NOT NULL) THEN 1
                    ELSE 0
                END
            ) AS total_cnt
        FROM pool_wpkl_predictions AS p
        JOIN pool_wpkl_matches     AS m  ON p.match_id = m.id
        WHERE m.matchtype_id = %d
        GROUP BY p.user_id
    ", $selected_matchtype_id));
    $acc_map = []; // user_id => [correct, total]
    foreach ($accuracy_rows as $r) {
        $acc_map[intval($r->user_id)] = [
            'correct' => intval($r->correct_cnt),
            'total'   => intval($r->total_cnt),
        ];
    }

    // --------------------------------------------
    // 6) Build leaderboard rows with monthly + accuracy + trend
    // --------------------------------------------
    $current_user_id = get_current_user_id();
    $leaderboard = [];
    foreach ($rankings as $r) {
        $uid = intval($r->user_id);
        $this_month = isset($monthly_map_this[$uid]) ? $monthly_map_this[$uid] : 0;
        $last_month = isset($monthly_map_last[$uid]) ? $monthly_map_last[$uid] : 0;
        $trend = ($this_month > $last_month) ? 'up' : (($this_month < $last_month) ? 'down' : 'same');

        $correct = isset($acc_map[$uid]) ? $acc_map[$uid]['correct'] : 0;
        $total   = isset($acc_map[$uid]) ? $acc_map[$uid]['total']   : 0;
        $accuracy_pct = ($total > 0) ? round(($correct / $total) * 100) : 0;

        $leaderboard[] = [
            'rank'          => intval($r->user_rank),
            'user_id'       => $uid,
            'user_name'     => esc_html($r->user_name),
            'total_points'  => intval($r->total_points),
            'monthly_points'=> $this_month,
            'correct'       => $correct,
            'total'         => $total,
            'accuracy'      => $accuracy_pct,
            'trend'         => $trend,
            'highlight'     => ($uid === $current_user_id),
        ];
    }

    // --------------------------------------------
    // 7) Competition Stats (top categories)
    // --------------------------------------------
    $stats = $wpdb->get_results($wpdb->prepare("
        (
            SELECT 'Correct Scores' AS category, u.display_name AS user_name, SUM(s.full) AS count
            FROM {$table_to_use} AS s
            JOIN wpkl_users AS u ON s.user_id = u.ID
            JOIN pool_wpkl_rankings AS r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
            WHERE s.full > 0 AND mt.id = %d
            GROUP BY s.user_id
            ORDER BY count DESC
            LIMIT 1
        )
        UNION ALL
        (
            SELECT 'Toto' AS category, u.display_name AS user_name, SUM(s.toto) AS count
            FROM {$table_to_use} AS s
            JOIN wpkl_users AS u ON s.user_id = u.ID
            JOIN pool_wpkl_rankings AS r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
            WHERE s.toto > 0 AND mt.id = %d
            GROUP BY s.user_id
            ORDER BY count DESC
            LIMIT 1
        )
        UNION ALL
        (
            SELECT 'Points Bonus' AS category, u.display_name AS user_name, SUM(s.goal_bonus) AS count
            FROM {$table_to_use} AS s
            JOIN wpkl_users AS u ON s.user_id = u.ID
            JOIN pool_wpkl_rankings AS r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
            WHERE s.goal_bonus > 0 AND mt.id = %d
            GROUP BY s.user_id
            ORDER BY count DESC
            LIMIT 1
        )
        UNION ALL
        (
            SELECT 'Goal Difference Bonus' AS category, u.display_name AS user_name, SUM(s.goal_diff_bonus) AS count
            FROM {$table_to_use} AS s
            JOIN wpkl_users AS u ON s.user_id = u.ID
            JOIN pool_wpkl_rankings AS r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
            WHERE s.goal_diff_bonus > 0 AND mt.id = %d
            GROUP BY s.user_id
            ORDER BY count DESC
            LIMIT 1
        )
        UNION ALL
        (
            SELECT 'Monthly Winner (last month)' AS category, u.display_name AS user_name, SUM(fms.score) AS count
            FROM pool_wpkl_football_match_scores AS fms
            JOIN wpkl_users AS u ON fms.user_id = u.ID
            JOIN pool_wpkl_matches AS m ON fms.match_id = m.id
            WHERE fms.competition_id = %d
              AND DATE(m.play_date) >= %s
              AND DATE(m.play_date) <= %s
            GROUP BY fms.user_id
            ORDER BY count DESC
            LIMIT 1
        )
    ", $selected_matchtype_id, $selected_matchtype_id, $selected_matchtype_id, $selected_matchtype_id, $selected_matchtype_id, $first_day_last_month, $last_day_last_month));

    // --------------------------------------------
    // 8) Detailed Stats datasets for the selected competition
    // --------------------------------------------

    // A) Last 6 months totals (by month) for this competition
    $months_rows = $wpdb->get_results($wpdb->prepare("
        SELECT DATE_FORMAT(m.play_date, '%%Y-%%m') AS ym, SUM(fms.score) AS pts
        FROM pool_wpkl_football_match_scores AS fms
        JOIN pool_wpkl_matches AS m ON m.id = fms.match_id
        WHERE fms.competition_id = %d
          AND DATE(m.play_date) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY ym
        ORDER BY ym DESC
    ", $selected_matchtype_id));
    $months_series = []; // newest first
    foreach ($months_rows as $r) {
        $months_series[] = [
            'label' => esc_html($r->ym),
            'pts'   => intval($r->pts),
        ];
    }

    // B) Top 5 users THIS month (use $monthly_map_this built earlier)
    $top5_map = $monthly_map_this;
    arsort($top5_map);
    $top5_ids = array_slice(array_keys($top5_map), 0, 5);
    $top5_names = [];
    if (!empty($top5_ids)) {
        $placeholders = implode(',', array_fill(0, count($top5_ids), '%d'));
        $names_sql = $wpdb->prepare("
            SELECT ID, display_name FROM wpkl_users WHERE ID IN ($placeholders)
        ", ...$top5_ids);
        $top5_names_rows = $wpdb->get_results($names_sql);
        foreach ($top5_names_rows as $u) {
            $top5_names[intval($u->ID)] = $u->display_name;
        }
    }
    $top5_list = [];
    foreach ($top5_ids as $uid) {
        $top5_list[] = [
            'name' => isset($top5_names[$uid]) ? esc_html($top5_names[$uid]) : ('User #'.$uid),
            'pts'  => intval($top5_map[$uid]),
        ];
    }

    // C) Category totals (competition-wide, cumulative)
    $category_totals_row = $wpdb->get_row($wpdb->prepare("
        SELECT 
            COALESCE(SUM(s.full), 0)            AS full_total,
            COALESCE(SUM(s.toto), 0)            AS toto_total,
            COALESCE(SUM(s.goal_bonus), 0)      AS goal_bonus_total,
            COALESCE(SUM(s.goal_diff_bonus), 0) AS goal_diff_total
        FROM {$table_to_use} AS s
        JOIN pool_wpkl_rankings   AS r  ON s.ranking_id = r.id
        JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
        WHERE mt.id = %d
    ", $selected_matchtype_id));
    $category_totals = [
        'Exact scores'           => intval($category_totals_row->full_total),
        'Correct results (Toto)' => intval($category_totals_row->toto_total),
        'Points bonus'           => intval($category_totals_row->goal_bonus_total),
        'Goal diff bonus'        => intval($category_totals_row->goal_diff_total),
    ];

    // --------------------------------------------
    // 9) Your Stats (current user in selected competition)
    // --------------------------------------------
    $your_row = null;
    foreach ($leaderboard as $row) {
        if ($row['user_id'] === $current_user_id) { $your_row = $row; break; }
    }

    // --------------------------------------------
    // 10) Render (v0 layout)
    // --------------------------------------------
    ob_start(); ?>
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Rankings</h1>
        <p class="text-muted-foreground text-lg">See how you stack up against other predictors</p>
      </div>

      <!-- Competition Filter -->
      <div class="mb-6">
        <form method="get" class="inline-block">
          <select name="competition"
                  class="w-[250px] rounded-xl border bg-background px-3 py-2 text-sm"
                  onchange="this.form.submit()">
            <?php foreach ($competitions as $c): ?>
              <option value="<?php echo esc_attr($c->id); ?>" <?php selected($selected_matchtype_id, $c->id); ?>>
                <?php echo esc_html($c->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Leaderboard -->
        <div class="lg:col-span-3">
          <div class="rounded-2xl border bg-card">
            <div class="p-4 border-b">
              <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 21h10M12 17v4M7 4h10M7 4a5 5 0 0 1-5 5v1a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V9a5 5 0 0 1-5-5"/></svg>
                <div class="text-lg font-semibold">Leaderboard</div>
              </div>
              <div class="text-xs text-muted-foreground mt-1">
                Current standings for <?php echo esc_html($selected_comp->name); ?>
              </div>
            </div>

            <div class="p-4 overflow-x-auto">
              <?php if (!empty($leaderboard)): ?>
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-muted-foreground">
                      <th class="py-2 px-3">Rank</th>
                      <th class="py-2 px-3">User</th>
                      <th class="py-2 px-3 text-right">Total</th>
                      <th class="py-2 px-3 text-right">Monthly</th>
                      <th class="py-2 px-3 text-right">Correct/Total</th>
                      <th class="py-2 px-3 text-right">Accuracy</th>
                      <th class="py-2 px-3 text-right">Trend</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($leaderboard as $row): 
                      $is_me = $row['highlight'];
                      $trendIcon = $row['trend'] === 'up' ? '↑' : ($row['trend'] === 'down' ? '↓' : '—');
                      $trendClass = $row['trend'] === 'up' ? 'text-green-600' : ($row['trend'] === 'down' ? 'text-red-600' : 'text-muted-foreground');
                    ?>
                      <tr class="border-t <?php echo $is_me ? 'bg-primary/5' : ''; ?>">
                        <td class="py-2 px-3"><?php echo esc_html($row['rank']); ?></td>
                        <td class="py-2 px-3 font-medium"><?php echo esc_html($row['user_name']); ?></td>
                        <td class="py-2 px-3 text-right"><?php echo esc_html(number_format_i18n($row['total_points'])); ?></td>
                        <td class="py-2 px-3 text-right"><?php echo esc_html(number_format_i18n($row['monthly_points'])); ?></td>
                        <td class="py-2 px-3 text-right"><?php echo esc_html($row['correct'] . ' / ' . $row['total']); ?></td>
                        <td class="py-2 px-3 text-right"><?php echo esc_html($row['accuracy']); ?>%</td>
                        <td class="py-2 px-3 text-right"><span class="<?php echo esc_attr($trendClass); ?>"><?php echo esc_html($trendIcon); ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <div class="text-sm text-muted-foreground">No rankings available for the selected competition.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Competition Stats -->
          <div class="rounded-2xl border bg-card">
            <div class="p-4 border-b">
              <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="m12 2 4 7 8 1-6 5 2 8-8-4-8 4 2-8-6-5 8-1z"/>
                </svg>
                <div class="text-lg font-semibold">Competition Stats</div>
              </div>
              <div class="text-xs text-muted-foreground mt-1">
                <?php echo esc_html($selected_comp->name); ?>
              </div>
            </div>

            <div class="p-4 space-y-3">
              <?php if (!empty($stats)): ?>
                <?php foreach ($stats as $stat): ?>
                  <div class="flex justify-between items-start border-t pt-2 first:border-t-0 first:pt-0">
                    <!-- Category -->
                    <div class="text-sm font-medium text-muted-foreground">
                      <?php echo esc_html($stat->category); ?>
                    </div>

                    <!-- Username + Count -->
                    <div class="text-right">
                      <div class="text-sm font-semibold break-words whitespace-normal leading-tight">
                        <?php echo esc_html($stat->user_name); ?>
                      </div>
                      <div class="text-xs text-muted-foreground">
                        <?php echo esc_html(number_format_i18n($stat->count)); ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-sm text-muted-foreground">No stats available.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Your Stats -->
          <div class="rounded-2xl border bg-card">
            <div class="p-4 border-b">
              <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 21h8M12 17v4M7 4h10M7 4a5 5 0 0 1-5 5v1a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V9a5 5 0 0 1-5-5"/></svg>
                <div class="text-lg font-semibold">Your Stats</div>
              </div>
            </div>
            <div class="p-4 space-y-4">
              <?php if ($your_row): ?>
                <div class="text-center space-y-1">
                  <div class="text-2xl font-bold text-primary">#<?php echo esc_html($your_row['rank']); ?></div>
                  <div class="text-sm text-muted-foreground">Current Rank</div>
                </div>
                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-sm text-muted-foreground">Total Points</span>
                    <span class="font-medium"><?php echo esc_html(number_format_i18n($your_row['total_points'])); ?></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-muted-foreground">Monthly Points</span>
                    <span class="font-medium"><?php echo esc_html(number_format_i18n($your_row['monthly_points'])); ?></span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-muted-foreground">Accuracy</span>
                    <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs"><?php echo esc_html($your_row['accuracy']); ?>%</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-muted-foreground">Predictions</span>
                    <span class="font-medium"><?php echo esc_html($your_row['correct'] . '/' . $your_row['total']); ?></span>
                  </div>
                </div>
              <?php else: ?>
                <div class="text-sm text-muted-foreground">You are not ranked in this competition.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="rounded-2xl border bg-card">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">Quick Actions</div>
            </div>
            <div class="p-2 space-y-1">
              <button id="sr-view-detailed-stats" type="button" class="w-full text-left p-2 rounded-md hover:bg-muted/50 text-sm">
                View Detailed Stats
              </button>
              <button class="w-full text-left p-2 rounded-md hover:bg-muted/50 text-sm" type="button">
                Compare with Friends
              </button>
              <button class="w-full text-left p-2 rounded-md hover:bg-muted/50 text-sm" type="button">
                Export Rankings
              </button>
            </div>
          </div>

          <!-- Detailed Stats (hidden by default) -->
          <div id="sr-detailed-stats" class="rounded-2xl border bg-card hidden">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">Detailed Stats</div>
              <div class="text-xs text-muted-foreground mt-1">
                <?php echo esc_html($selected_comp->name); ?>
              </div>
            </div>

            <div class="p-4 space-y-6">
              <!-- Last 6 months -->
              <div>
                <div class="text-sm font-semibold mb-2">Points (last 6 months)</div>
                <?php if (!empty($months_series)): ?>
                  <div class="rounded-md border overflow-x-auto">
                    <table class="w-full text-sm">
                      <thead>
                        <tr class="text-left text-muted-foreground">
                          <th class="py-2 px-3">Month</th>
                          <th class="py-2 px-3 text-right">Points</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($months_series as $row): ?>
                          <tr class="border-t">
                            <td class="py-2 px-3"><?php echo esc_html($row['label']); ?></td>
                            <td class="py-2 px-3 text-right"><?php echo esc_html(number_format_i18n($row['pts'])); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-sm text-muted-foreground">No recent monthly data.</div>
                <?php endif; ?>
              </div>

              <!-- Top 5 this month -->
              <div>
                <div class="text-sm font-semibold mb-2">Top 5 (this month)</div>
                <?php if (!empty($top5_list)): ?>
                  <div class="rounded-md border overflow-x-auto">
                    <table class="w-full text-sm">
                      <thead>
                        <tr class="text-left text-muted-foreground">
                          <th class="py-2 px-3">User</th>
                          <th class="py-2 px-3 text-right">Points</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($top5_list as $u): ?>
                          <tr class="border-t">
                            <td class="py-2 px-3 font-medium break-words whitespace-normal leading-tight"><?php echo esc_html($u['name']); ?></td>
                            <td class="py-2 px-3 text-right"><?php echo esc_html(number_format_i18n($u['pts'])); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-sm text-muted-foreground">No points recorded this month.</div>
                <?php endif; ?>
              </div>

              <!-- Category totals -->
              <div>
                <div class="text-sm font-semibold mb-2">Points by category</div>
                <?php if (!empty($category_totals)): ?>
                  <div class="rounded-md border overflow-x-auto">
                    <table class="w-full text-sm">
                      <thead>
                        <tr class="text-left text-muted-foreground">
                          <th class="py-2 px-3">Category</th>
                          <th class="py-2 px-3 text-right">Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($category_totals as $label => $val): ?>
                          <tr class="border-t">
                            <td class="py-2 px-3"><?php echo esc_html($label); ?></td>
                            <td class="py-2 px-3 text-right"><?php echo esc_html(number_format_i18n($val)); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-sm text-muted-foreground">No category totals available.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <!-- /Detailed Stats -->
        </div>
      </div>
    </div>

    <script>
      (function(){
        const btn = document.getElementById('sr-view-detailed-stats');
        const panel = document.getElementById('sr-detailed-stats');
        if (!btn || !panel) return;

        btn.addEventListener('click', function(){
          const isHidden = panel.classList.contains('hidden');
          if (isHidden) {
            panel.classList.remove('hidden');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            btn.textContent = 'Hide Detailed Stats';
          } else {
            panel.classList.add('hidden');
            btn.textContent = 'View Detailed Stats';
          }
        });
      })();
    </script>
<?php
    return ob_get_clean();
}
?>