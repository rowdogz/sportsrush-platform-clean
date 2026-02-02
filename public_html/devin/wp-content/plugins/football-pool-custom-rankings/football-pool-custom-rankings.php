<?php
/*
Plugin Name: Football Pool Custom Rankings Page
Description: Adds a custom rankings page with competition filtering for the Football Pool plugin, highlighting the logged-in user's name in the list. Also displays stats for the selected competition, with optional Monthly Winner and Overall Competition Winner lines per competition.
Version: 1.5
Author: Your Name
*/

add_shortcode('football_pool_rankings', 'football_pool_custom_rankings_shortcode');

function football_pool_custom_rankings_shortcode() {
    global $wpdb;

    // ----------------------------
    // CONFIG
    // ----------------------------
    // Competitions (by NAME) that should display the "Overall Competition Winner" line in the stats box.
    // (Case-insensitive match.)
    $overall_winner_competition_names = [
        'Championship 2025',
        'League One 2025',
        'Super League 2025',
        'NRL 2025',
    ];

    // Competitions where the "Monthly Winner (last month)" should be HIDDEN (still by ID, as before).
    $excluded_monthly_winner_ids = [32, 34];
    // ----------------------------

    // Fetch visible competitions (match types)
    $competitions_query = "SELECT id, name FROM pool_wpkl_matchtypes WHERE visibility = 1 ORDER BY name ASC";
    $competitions = $wpdb->get_results($competitions_query);

    if (empty($competitions)) {
        return "<p>No competitions available. Please ensure the pool_wpkl_matchtypes table is populated.</p>";
    }

    // Default to the first competition in the list if none is selected
    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : $competitions[0]->id;

    $selected_competition_name = '';
    foreach ($competitions as $competition) {
        if ((int)$competition->id === (int)$selected_matchtype_id) {
            $selected_competition_name = (string)$competition->name;
            break;
        }
    }

    // Detect which scorehistory table is being used
    $table_to_use = "pool_wpkl_scorehistory_s1_t1";
    $check_s1_t2 = $wpdb->get_var("SELECT COUNT(*) FROM pool_wpkl_scorehistory_s1_t2");
    if ((int)$check_s1_t2 > 0) {
        $table_to_use = "pool_wpkl_scorehistory_s1_t2"; // Use _s1_t2 if it has data
    }

    // Fetch rankings for the selected competition using your manual scoring logic
    $rankings_query = $wpdb->prepare("
        SELECT 
            DENSE_RANK() OVER (ORDER BY total_points DESC) AS user_rank,
            user_id,
            user_name,
            total_points,
            current_month_points
        FROM (
            SELECT 
                u.ID AS user_id,
                u.display_name AS user_name,

                SUM(
                    CASE
                        WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50
                        ELSE 0
                    END
                    +
                    CASE
                        WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                        WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                              (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                              (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
                        ELSE 0
                    END
                    +
                    CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END
                    +
                    CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END
                    +
                    CASE
                        WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                        WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                              (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                              (m.home_score = m.away_score AND p.home_score = p.away_score))
                        AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
                            (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score)) THEN 20
                        ELSE 0
                    END
                ) AS total_points,

                SUM(
                    CASE
                        WHEN MONTH(m.play_date) = MONTH(NOW()) AND YEAR(m.play_date) = YEAR(NOW())
                        THEN
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50
                                ELSE 0
                            END
                            +
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                                WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                                      (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                                      (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
                                ELSE 0
                            END
                            +
                            CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END
                            +
                            CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END
                            +
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                                WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                                      (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                                      (m.home_score = m.away_score AND p.home_score = p.away_score))
                                AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
                                    (GREATEST(p.home_score, p.away_score) - LEAST(p.away_score, p.home_score)) THEN 20
                                ELSE 0
                            END
                        ELSE 0
                    END
                ) AS current_month_points

            FROM wpkl_users u
            JOIN pool_wpkl_predictions p ON p.user_id = u.ID
            JOIN pool_wpkl_matches m ON p.match_id = m.id
            WHERE m.home_score IS NOT NULL
            AND m.away_score IS NOT NULL
            AND m.matchtype_id = %d
            GROUP BY u.ID
        ) AS sub
    ", $selected_matchtype_id);

    $rankings = $wpdb->get_results($rankings_query);

    // Decide whether to show Monthly Winner
    $include_monthly_winner = !in_array($selected_matchtype_id, $excluded_monthly_winner_ids, true);

    // Decide whether to show Overall Winner (by NAME)
    $include_overall_winner = in_array(
        mb_strtolower($selected_competition_name),
        array_map('mb_strtolower', $overall_winner_competition_names),
        true
    );

    // ----------------------------
    // Build Stats Query in parts to keep prepare placeholders correct
    // ----------------------------
    $stats_parts = [];

    // Correct Scores
    $stats_parts[] = "
        (
            SELECT 'Correct Scores' AS category, u.display_name AS user_name, SUM(s.full) AS count
            FROM {$table_to_use} s
            JOIN wpkl_users u ON s.user_id = u.ID
            JOIN pool_wpkl_rankings r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes mt ON r.name = mt.name
            WHERE s.full > 0 AND mt.id = %d
            GROUP BY s.user_id
            ORDER BY count DESC
            LIMIT 1
        )
    ";

    // Toto
    $stats_parts[] = "
        (
            SELECT 'Toto' AS category, u.display_name AS user_name, SUM(s.toto) AS count
            FROM {$table_to_use} s
            JOIN wpkl_users u ON s.user_id = u.ID
            JOIN pool_wpkl_rankings r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes mt ON r.name = mt.name
            WHERE s.toto > 0 AND mt.id = %d
            GROUP BY s.user_id
            ORDER BY count DESC
            LIMIT 1
        )
    ";

    // Points Bonus
    $stats_parts[] = "
        (
            SELECT 'Points Bonus' AS category, u.display_name AS user_name, SUM(s.goal_bonus) AS count
            FROM {$table_to_use} s
            JOIN wpkl_users u ON s.user_id = u.ID
            JOIN pool_wpkl_rankings r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes mt ON r.name = mt.name
            WHERE s.goal_bonus > 0 AND mt.id = %d
            GROUP BY s.user_id
            ORDER BY count DESC
            LIMIT 1
        )
    ";

    // Points Difference Bonus
    $stats_parts[] = "
        (
            SELECT 'Points Difference Bonus' AS category, u.display_name AS user_name, SUM(s.goal_diff_bonus) AS count
            FROM {$table_to_use} s
            JOIN wpkl_users u ON s.user_id = u.ID
            JOIN pool_wpkl_rankings r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes mt ON r.name = mt.name
            WHERE s.goal_diff_bonus > 0 AND mt.id = %d
            GROUP BY s.user_id
            ORDER BY count DESC
            LIMIT 1
        )
    ";

    // Monthly Winner (last month), if enabled
    if ($include_monthly_winner) {
        $stats_parts[] = "
            (
                SELECT 
                    CONCAT('Monthly Winner (', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%M %Y'), ')') AS category,
                    u.display_name AS user_name,
                    ms.total_points AS count
                FROM (
                    SELECT 
                        u.ID,
                        SUM(
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50
                                ELSE 0
                            END
                            +
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                                WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                                      (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                                      (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
                                ELSE 0
                            END
                            +
                            CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END
                            +
                            CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END
                            +
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                                WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                                      (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                                      (m.home_score = m.away_score AND p.home_score = p.away_score))
                                AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
                                    (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score)) THEN 20
                                ELSE 0
                            END
                        ) AS total_points,
                        SUM(s.full) AS correct_scores,
                        SUM(s.goal_diff_bonus) AS goal_diff_bonus,
                        SUM(s.goal_bonus) AS goal_bonus,
                        SUM(s.toto) AS toto
                    FROM wpkl_users u
                    JOIN pool_wpkl_predictions p ON p.user_id = u.ID
                    JOIN pool_wpkl_matches m ON p.match_id = m.id
                    JOIN {$table_to_use} s ON s.user_id = u.ID
                    JOIN pool_wpkl_rankings r ON s.ranking_id = r.id
                    JOIN pool_wpkl_matchtypes mt ON r.name = mt.name
                    WHERE m.matchtype_id = %d
                      AND m.home_score IS NOT NULL
                      AND m.away_score IS NOT NULL
                      AND DATE(m.play_date) >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%%Y-%%m-01')
                      AND DATE(m.play_date) <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH))
                      AND mt.id = m.matchtype_id
                    GROUP BY u.ID
                    ORDER BY total_points DESC, correct_scores DESC, goal_diff_bonus DESC, goal_bonus DESC, toto DESC
                    LIMIT 1
                ) AS ms
                JOIN wpkl_users u ON u.ID = ms.ID
            )
        ";
    }

    // Overall Competition Winner, if selected for this competition NAME
    if ($include_overall_winner) {
        $stats_parts[] = "
            (
                SELECT 
                    'Overall Competition Winner' AS category,
                    u.display_name AS user_name,
                    sub.total_points AS count
                FROM (
                    SELECT 
                        u.ID,
                        SUM(
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50
                                ELSE 0
                            END
                            +
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                                WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                                      (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                                      (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
                                ELSE 0
                            END
                            +
                            CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END
                            +
                            CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END
                            +
                            CASE
                                WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                                WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                                      (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                                      (m.home_score = m.away_score AND p.home_score = p.away_score))
                                AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
                                    (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score)) THEN 20
                                ELSE 0
                            END
                        ) AS total_points
                    FROM wpkl_users u
                    JOIN pool_wpkl_predictions p ON p.user_id = u.ID
                    JOIN pool_wpkl_matches m ON p.match_id = m.id
                    WHERE m.matchtype_id = %d
                      AND m.home_score IS NOT NULL
                      AND m.away_score IS NOT NULL
                    GROUP BY u.ID
                    ORDER BY total_points DESC
                    LIMIT 1
                ) AS sub
                JOIN wpkl_users u ON u.ID = sub.ID
            )
        ";
    }

    // Join all parts with UNION ALL and prepare with the correct number of placeholders
    $stats_query = implode(" UNION ALL ", $stats_parts);

    // Build placeholder list matching the number of %d occurrences in order
    $prepare_args = [];
    // Base 4 blocks always present:
    $prepare_args[] = $selected_matchtype_id; // Correct Scores
    $prepare_args[] = $selected_matchtype_id; // Toto
    $prepare_args[] = $selected_matchtype_id; // Points Bonus
    $prepare_args[] = $selected_matchtype_id; // Points Difference Bonus
    if ($include_monthly_winner) {
        $prepare_args[] = $selected_matchtype_id; // Monthly Winner (last month)
    }
    if ($include_overall_winner) {
        $prepare_args[] = $selected_matchtype_id; // Overall Competition Winner
    }

    // Execute prepared query (dynamic arg count)
    $stats_prepared = call_user_func_array(
        [$wpdb, 'prepare'],
        array_merge([$stats_query], $prepare_args)
    );
    $stats = $wpdb->get_results($stats_prepared);

    // Get the logged-in user's ID
    $user_id = get_current_user_id();

    // Output results with a competition filter, rankings table, and stats table
    ob_start();
    ?>
    <div class="rankings-page-container">
        <!-- Rankings Table -->
        <div style="flex: 1;">
            <form method="get" class="football-pool-filter-form">
                <label for="competition">Filter by Competition:</label>
                <select name="competition" id="competition" onchange="this.form.submit();">
                    <?php foreach ($competitions as $competition): ?>
                        <option value="<?php echo esc_attr($competition->id); ?>" <?php selected($selected_matchtype_id, $competition->id); ?>>
                            <?php echo esc_html($competition->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if (!empty($rankings)): ?>
                <table class="football-pool-rankings-table" style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 20px;">
                    <?php $current_month_name = date('F'); ?>
                    <thead>
                        <tr style="background-color: #f4f4f4;">
                            <th style="padding: 8px; border: 1px solid #ddd;">Rank</</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">User Name</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Total Points</th>
                            <th style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($current_month_name); ?> Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rankings as $ranking): ?>
                            <tr style="<?php echo ((int)$ranking->user_id === (int)$user_id) ? 'background-color: #dff0d8;' : ''; ?>">
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($ranking->user_rank); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($ranking->user_name); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($ranking->total_points); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($ranking->current_month_points); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No rankings available for the selected competition.</p>
            <?php endif; ?>
        </div>

        <!-- Stats Table -->
        <div class="stats-container" style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; border-radius: 8px; width: 300px;">
            <h4 style="margin: 0 0 5px; text-align: center;">Competition Stats</h4>
            <p style="margin: 0 0 10px; text-align: center; font-weight: bold;"><?php echo esc_html($selected_competition_name); ?></p>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background-color: #f4f4f4;">
                        <th style="text-align: left; padding: 5px;">Top</th>
                        <th style="text-align: left; padding: 5px;">User</th>
                        <th style="text-align: right; padding: 5px;">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats)): ?>
                        <?php foreach ($stats as $stat): ?>
                            <tr>
                                <td style="padding: 5px;"><?php echo esc_html($stat->category); ?></td>
                                <td style="padding: 5px;"><?php echo esc_html($stat->user_name); ?></td>
                                <td style="padding: 5px; text-align: right;"><?php echo esc_html($stat->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="padding: 5px; text-align: center;">No stats available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    echo '<style>
/* Default Layout */
.rankings-page-container {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-start;
}

/* Stats Table Styling */
.stats-container {
    border: 1px solid #ddd;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    width: 300px;
}

.stats-container table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

/* Ensure text wraps properly */
.stats-container td {
    padding: 5px;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Specific fix for username column */
.stats-container td:nth-child(2) {
    max-width: 120px;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Mobile Layout Fix */
@media (max-width: 768px) {
    .rankings-page-container {
        flex-direction: column;
        align-items: center;
    }

    .football-pool-filter-form {
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
    }

    .stats-container {
        width: 90%;
        max-width: 350px;
        margin: 10px auto;
        text-align: center;
        order: -1; /* Move it above the rankings table on mobile */
    }

    .stats-container table {
        width: 100%;
    }
}
</style>';
    return ob_get_clean();
}
?>