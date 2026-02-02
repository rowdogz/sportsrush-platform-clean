<?php
/*
Plugin Name: Football Pool Custom Results (v0 Cards) with Team Flags and User Points
Description: Results page with v0 design — summary cards, competition & week filters, predicted-scores toggle, user points summary sidebar, and responsive result cards.
Version: 4.0
Author: Your Name
*/

add_shortcode('football_pool_results', 'football_pool_custom_results_shortcode');

function football_pool_custom_results_shortcode() {
    global $wpdb;

    // ---------------------------
    // 1) INPUTS & CONTEXT
    // ---------------------------
    $user_id = get_current_user_id();
    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : 0;
    $selected_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : 'all';

    // ---------------------------
    // 2) DATA: COMPETITIONS
    // ---------------------------
    $competitions_query = "
        SELECT mt.id, mt.name 
        FROM pool_wpkl_matchtypes AS mt
        WHERE mt.visibility = 1
        ORDER BY mt.name ASC
    ";
    $competitions = $wpdb->get_results($competitions_query);
    if (empty($competitions)) {
        return '<div class="rounded-2xl border bg-card p-5">No competitions available. Please ensure the pool_wpkl_matchtypes table is populated.</div>';
    }

    // ---------------------------
    // 3) DATA: WEEKS (distinct WEEK numbers for selected comp or all visible)
    // ---------------------------
    $weeks_query = "
        SELECT DISTINCT WEEK(m.play_date, 1) AS week_number
        FROM pool_wpkl_matches AS m
        JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id
        WHERE mt.visibility = 1
    ";
    if ($selected_matchtype_id) {
        $weeks_query .= $wpdb->prepare(" AND m.matchtype_id = %d", $selected_matchtype_id);
    }
    $weeks_query .= " ORDER BY week_number ASC";
    $weeks = $wpdb->get_results($weeks_query);

    // ---------------------------
    // 4) DATA: MATCHES (+ user prediction scores joined)
    // ---------------------------
    $matches_sql = "
        SELECT 
            m.id AS match_id,
            m.play_date, 
            m.home_team_id, 
            m.away_team_id, 
            m.home_score, 
            m.away_score,
            t1.name AS home_team_name,
            t1.flag AS home_team_flag,
            t2.name AS away_team_name,
            t2.flag AS away_team_flag,
            mt.name AS competition_name,
            p.home_score AS predicted_home_score,
            p.away_score AS predicted_away_score
        FROM pool_wpkl_matches AS m
        JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id
        LEFT JOIN pool_wpkl_teams AS t1 ON m.home_team_id = t1.id
        LEFT JOIN pool_wpkl_teams AS t2 ON m.away_team_id = t2.id
        LEFT JOIN pool_wpkl_predictions AS p ON m.id = p.match_id AND p.user_id = %d
        WHERE mt.visibility = 1
          AND m.home_score IS NOT NULL
          AND m.away_score IS NOT NULL
    ";
    $params = [$user_id];

    if ($selected_matchtype_id) {
        $matches_sql .= " AND m.matchtype_id = %d";
        $params[] = $selected_matchtype_id;
    }
    if ($selected_week !== 'all') {
        $matches_sql .= " AND WEEK(m.play_date, 1) = %d";
        $params[] = intval($selected_week);
    }
    $matches_sql .= " ORDER BY m.play_date DESC";
    $matches = $wpdb->get_results($wpdb->prepare($matches_sql, $params));

    // ---------------------------
    // 5) DATA: USER POINTS PER MATCH (scores-based rules)
    // ---------------------------
    $user_points_query = "
        SELECT 
            p.match_id,
            m.home_score AS actual_home_score, 
            m.away_score AS actual_away_score,
            p.home_score AS predicted_home_score, 
            p.away_score AS predicted_away_score,

            CASE WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50 ELSE 0 END AS full_correct_score,
            CASE
                WHEN (m.home_score = p.home_score AND m.away_score = p.away_score) THEN 0
                WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                      (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                      (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
                ELSE 0
            END AS result_points,
            CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END AS home_goal_bonus,
            CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END AS away_goal_bonus,
            CASE
                WHEN (m.home_score = p.home_score AND m.away_score = p.away_score) THEN 0
                WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                      (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                      (m.home_score = m.away_score AND p.home_score = p.away_score))
                     AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
                         (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score)) THEN 20
                ELSE 0
            END AS goal_difference_bonus

        FROM pool_wpkl_predictions p
        JOIN pool_wpkl_matches m ON p.match_id = m.id
        WHERE p.user_id = %d
    ";
    $user_points_rows = $wpdb->get_results($wpdb->prepare($user_points_query, $user_id));
    $user_points = [];
    foreach ($user_points_rows as $row) {
        $user_points[(int)$row->match_id] =
            intval($row->full_correct_score)
          + intval($row->result_points)
          + intval($row->home_goal_bonus)
          + intval($row->away_goal_bonus)
          + intval($row->goal_difference_bonus);
    }

    // ---------------------------
    // 6) DATA: USER TOTAL POINTS SUMMARY (by ranking/competition)
    // ---------------------------
    $user_total_points_query = "
        SELECT 
            r.name AS competition_name,
            COALESCE(s.total_score, 0) AS total_points,
            COALESCE(s.ranking, '-') AS user_ranking
        FROM pool_wpkl_rankings AS r
        LEFT JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
        LEFT JOIN pool_wpkl_scorehistory_s1_t1 AS s ON s.ranking_id = r.id AND s.user_id = %d
        WHERE mt.visibility = 1
        ORDER BY r.name ASC
    ";
    $summary_rows = $wpdb->get_results($wpdb->prepare($user_total_points_query, $user_id));
    $points_summary = [];
    $total_points_all = 0;
    $best_competition = '';
    $best_points = 0;
    $best_rank = null; // minimal numeric
    foreach ($summary_rows as $sr) {
        $name = sanitize_text_field($sr->competition_name);
        $pts  = intval($sr->total_points);
        $rank = is_numeric($sr->user_ranking) ? intval($sr->user_ranking) : $sr->user_ranking;

        $points_summary[] = [
            'competition_name' => $name,
            'total_points'     => $pts,
            'user_ranking'     => $rank,
        ];

        $total_points_all += $pts;
        if ($pts > $best_points) {
            $best_points = $pts;
            $best_competition = $name;
        }
        if (is_int($rank)) {
            if ($best_rank === null || $rank < $best_rank) $best_rank = $rank;
        }
    }
    $total_competitions = count($points_summary);

    // ---------------------------
    // 7) HELPERS + STATS from matches
    // ---------------------------
    $fmt_date = function($mysql_datetime) {
        if (empty($mysql_datetime)) return '';
        $ts = strtotime($mysql_datetime);
        return date_i18n('d M Y, H:i', $ts);
    };
    $winner_side = function($home_score, $away_score) {
        if (!is_numeric($home_score) || !is_numeric($away_score)) return 'draw';
        if ($home_score > $away_score) return 'home';
        if ($away_score > $home_score) return 'away';
        return 'draw';
    };
    $outcome = function($home, $away) {
        if (!is_numeric($home) || !is_numeric($away)) return 'draw';
        if ($home > $away) return 'home';
        if ($away > $home) return 'away';
        return 'draw';
    };

    // Build stats across filtered matches for summary cards
    $pred_count = 0;
    $correct_count = 0;
    $exact_count = 0;
    foreach ($matches as $m) {
        $has_pred = ($m->predicted_home_score !== null || $m->predicted_away_score !== null);
        if ($has_pred) {
            $pred_count++;
            $is_exact = ($m->predicted_home_score === $m->home_score) && ($m->predicted_away_score === $m->away_score);
            if ($is_exact) $exact_count++;

            $pred_outcome = $outcome($m->predicted_home_score, $m->predicted_away_score);
            $real_outcome = $outcome($m->home_score, $m->away_score);
            if ($pred_outcome === $real_outcome) $correct_count++;
        }
    }
    $accuracy = $pred_count > 0 ? round(($correct_count / $pred_count) * 100) : 0;

    // ---------------------------
    // 8) RENDER (v0 layout)
    // ---------------------------
    ob_start();
    ?>

    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Results</h1>
        <p class="text-muted-foreground text-lg">View your prediction results and points earned</p>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="rounded-2xl border bg-card">
          <div class="p-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-medium">Total Points</div>
            <svg class="h-4 w-4 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 21h10M12 17v4M7 4h10M7 4a5 5 0 0 1-5 5v1a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V9a5 5 0 0 1-5-5"/></svg>
          </div>
          <div class="px-4 pb-4"><div class="text-2xl font-bold"><?php echo esc_html(number_format_i18n($total_points_all)); ?></div></div>
        </div>

        <div class="rounded-2xl border bg-card">
          <div class="p-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-medium">Correct Results</div>
            <svg class="h-4 w-4 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m22 2-7 20-4-9-9-4Z"/></svg>
          </div>
          <div class="px-4 pb-4"><div class="text-2xl font-bold"><?php echo esc_html($correct_count); ?></div></div>
        </div>

        <div class="rounded-2xl border bg-card">
          <div class="p-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-medium">Exact Scores</div>
            <svg class="h-4 w-4 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
          </div>
          <div class="px-4 pb-4"><div class="text-2xl font-bold"><?php echo esc_html($exact_count); ?></div></div>
        </div>

        <div class="rounded-2xl border bg-card">
          <div class="p-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-medium">Accuracy</div>
            <svg class="h-4 w-4 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18"/><path d="m19 9-6 6-4-4"/></svg>
          </div>
          <div class="px-4 pb-4"><div class="text-2xl font-bold"><?php echo esc_html($accuracy); ?>%</div></div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Filters + Results -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Filters Card -->
          <div class="rounded-2xl border bg-card">
            <div class="p-4 border-b">
              <div class="text-base font-semibold flex items-center gap-2">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 6h18M7 12h10M10 18h4"/></svg>
                <span>Filters</span>
              </div>
            </div>
            <div class="p-4">
              <div class="flex flex-wrap gap-4 items-center">
                <!-- Competition -->
                <form method="get" class="contents" id="sr-results-filter-form">
                  <select name="competition" class="w-[200px] rounded-xl border bg-background px-3 py-2 text-sm" onchange="document.getElementById('sr-results-filter-form').submit()">
                    <option value="0">All Competitions</option>
                    <?php foreach ($competitions as $c): ?>
                      <option value="<?php echo esc_attr($c->id); ?>" <?php selected($selected_matchtype_id, $c->id); ?>>
                        <?php echo esc_html($c->name); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <!-- Week -->
                  <select name="week" class="w-[150px] rounded-xl border bg-background px-3 py-2 text-sm" onchange="document.getElementById('sr-results-filter-form').submit()">
                    <option value="all" <?php selected($selected_week, 'all'); ?>>All Weeks</option>
                    <?php $wk_i = 1; foreach ($weeks as $w): ?>
                      <option value="<?php echo esc_attr($w->week_number); ?>" <?php selected($selected_week, $w->week_number); ?>>
                        <?php echo 'Week ' . esc_html($wk_i++); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>

                <!-- Toggle predicted scores -->
                <button id="sr-toggle-preds" type="button" class="rounded-full border px-4 py-2 text-sm font-semibold bg-white text-gray-900 hover:bg-gray-100">
                  Show Predicted Scores
                </button>
              </div>
            </div>
          </div>

          <!-- Results list -->
          <?php if (!empty($matches)): ?>
            <div class="space-y-4">
              <?php foreach ($matches as $m):
                $side = $winner_side($m->home_score, $m->away_score);
                $points_for_match = isset($user_points[$m->match_id]) ? $user_points[$m->match_id] : 0;

                $has_pred = ($m->predicted_home_score !== null || $m->predicted_away_score !== null);
                $is_exact = $has_pred && $m->predicted_home_score === $m->home_score && $m->predicted_away_score === $m->away_score;

                $pred_outcome = $has_pred ? $outcome($m->predicted_home_score, $m->predicted_away_score) : 'draw';
                $real_outcome = $outcome($m->home_score, $m->away_score);
                $is_correct = $has_pred && ($pred_outcome === $real_outcome);
              ?>
              <article class="rounded-2xl border bg-card p-4 hover:shadow-sm transition">
                <!-- Top row: comp & date + badges -->
                <div class="flex items-start justify-between gap-3">
                  <div class="text-xs text-muted-foreground">
                    <div class="font-medium"><?php echo esc_html($m->competition_name ?? ''); ?></div>
                    <div class="mt-0.5"><?php echo esc_html($fmt_date($m->play_date)); ?></div>
                  </div>
                  <div class="flex items-center gap-2">
                    <?php if ($is_exact): ?>
                      <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px]" style="color:var(--color-success);border-color:var(--color-success)">Exact score</span>
                    <?php elseif ($is_correct): ?>
                      <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] text-primary border-primary">Correct result</span>
                    <?php endif; ?>
                    <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] text-muted-foreground">FT</span>
                  </div>
                </div>

                <!-- Teams & score (wrap names on mobile) -->
                <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                  <!-- Home -->
                  <div class="flex items-start gap-2 min-w-0">
                    <?php if (!empty($m->home_team_flag)): ?>
                      <img class="h-6 w-6 rounded object-cover flex-shrink-0" src="<?php echo esc_url($m->home_team_flag); ?>" alt="<?php echo esc_attr($m->home_team_name); ?>">
                    <?php endif; ?>
                    <span class="flex-1 text-left break-words whitespace-normal leading-tight text-xs md:text-sm <?php echo $side === 'home' ? 'font-semibold' : ''; ?>">
                      <?php echo esc_html($m->home_team_name); ?>
                    </span>
                  </div>

                  <!-- Score -->
                  <div class="text-sm font-semibold tabular-nums px-1">
                    <?php echo esc_html("{$m->home_score} - {$m->away_score}"); ?>
                  </div>

                  <!-- Away -->
                  <div class="flex items-start gap-2 justify-end min-w-0">
                    <span class="flex-1 text-right break-words whitespace-normal leading-tight text-xs md:text-sm <?php echo $side === 'away' ? 'font-semibold' : ''; ?>">
                      <?php echo esc_html($m->away_team_name); ?>
                    </span>
                    <?php if (!empty($m->away_team_flag)): ?>
                      <img class="h-6 w-6 rounded object-cover flex-shrink-0" src="<?php echo esc_url($m->away_team_flag); ?>" alt="<?php echo esc_attr($m->away_team_name); ?>">
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Predicted scores (toggle visibility) -->
                <?php if ($has_pred): ?>
                  <div class="mt-2 text-xs text-muted-foreground sr-pred-block hidden">
                    <span class="opacity-70">Your prediction:</span>
                    <span class="font-semibold"><?php echo esc_html($m->predicted_home_score); ?> - <?php echo esc_html($m->predicted_away_score); ?></span>
                  </div>
                <?php else: ?>
                  <div class="mt-2 text-xs text-muted-foreground sr-pred-block hidden">
                    <span class="opacity-70">Your prediction:</span>
                    <span class="font-semibold">—</span>
                  </div>
                <?php endif; ?>

                <!-- Footer row: user points -->
                <div class="mt-3 flex items-center justify-between">
                  <div class="text-xs text-muted-foreground">
                    <!-- Venue / round could go here later -->
                  </div>
                  <div class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs">
                    <span class="opacity-70">Your points:</span>
                    <span class="font-semibold"><?php echo esc_html($points_for_match); ?></span>
                  </div>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="rounded-2xl border bg-card text-center py-12 border-dashed">
              <div class="max-w-md mx-auto">
                <div class="text-muted-foreground text-lg font-medium">No results found</div>
                <div class="text-muted-foreground mt-1">Try adjusting your filters or check back later.</div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Right: Sidebar -->
        <div class="space-y-6">
          <!-- Points Summary -->
          <?php if (!empty($points_summary)): ?>
          <div class="rounded-2xl border bg-card">
            <div class="p-4 border-b">
              <div class="text-base font-semibold flex items-center gap-2">
                <svg class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 21h10M12 17v4M7 4h10M7 4a5 5 0 0 1-5 5v1a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V9a5 5 0 0 1-5-5"/></svg>
                <span>User Points Summary</span>
              </div>
            </div>
            <div class="p-4">
              <div class="rounded-md border overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-muted-foreground">
                      <th class="py-2 px-3">Competition</th>
                      <th class="py-2 px-3 text-right">Points</th>
                      <th class="py-2 px-3 text-right">Rank</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($points_summary as $row): ?>
                      <tr class="border-t">
                        <td class="py-2 px-3 font-medium"><?php echo esc_html($row['competition_name']); ?></td>
                        <td class="py-2 px-3 text-right"><?php echo esc_html(number_format_i18n($row['total_points'])); ?></td>
                        <td class="py-2 px-3 text-right"><?php echo esc_html(is_int($row['user_ranking']) ? '#'.$row['user_ranking'] : $row['user_ranking']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Quick Stats -->
          <div class="rounded-2xl border bg-card">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">Quick Stats</div>
            </div>
            <div class="p-4 space-y-3">
              <div class="flex justify-between">
                <span class="text-sm text-muted-foreground">Best Competition</span>
                <span class="font-medium text-sm"><?php echo esc_html($best_competition ?: '—'); ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-muted-foreground">Highest Points</span>
                <span class="font-medium text-sm"><?php echo esc_html(number_format_i18n($best_points)); ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-muted-foreground">Best Ranking</span>
                <span class="font-medium text-sm"><?php echo esc_html($best_rank ? '#'.$best_rank : '—'); ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-muted-foreground">Total Competitions</span>
                <span class="font-medium text-sm"><?php echo esc_html($total_competitions); ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Toggle predicted scores visibility
      (function() {
        const btn = document.getElementById('sr-toggle-preds');
        if (!btn) return;
        let showing = false;
        btn.addEventListener('click', function() {
          showing = !showing;
          document.querySelectorAll('.sr-pred-block').forEach(el => {
            if (showing) { el.classList.remove('hidden'); } else { el.classList.add('hidden'); }
          });
          btn.textContent = showing ? 'Hide Predicted Scores' : 'Show Predicted Scores';
        });
      })();
    </script>

    <?php
    return ob_get_clean();
}