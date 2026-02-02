<?php
/*
Plugin Name: Football Pool Custom Rankings Page
Description: Adds a custom rankings page with competition filtering for the Football Pool plugin, highlighting the logged-in user's name in the list. Also displays stats for the selected competition, with optional Monthly Winner and Overall Competition Winner lines per competition.
Version: 1.6
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('football_pool_rankings', 'football_pool_custom_rankings_shortcode');

function football_pool_custom_rankings_shortcode() {
    global $wpdb;

    // ----------------------------
    // CONFIG
    // ----------------------------
    // Competitions where the "Monthly Winner (last month)" should be HIDDEN (by ID).
    $excluded_monthly_winner_ids = [32, 34];
    // ----------------------------

    // Fetch visible competitions (match types)
    $competitions_query = "SELECT id, name FROM pool_wpkl_matchtypes WHERE visibility = 1 ORDER BY name ASC";
    $competitions = $wpdb->get_results($competitions_query);

    if (empty($competitions)) {
        return "<p>No competitions available. Please ensure the pool_wpkl_matchtypes table is populated.</p>";
    }

    // Default to the first competition in the list if none is selected
    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : intval($competitions[0]->id);

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

    // Overall Competition Winner is now ALWAYS shown for any visible competition
    $include_overall_winner = false;

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
                        ) AS total_points
                    FROM wpkl_users u
                    JOIN pool_wpkl_predictions p ON p.user_id = u.ID
                    JOIN pool_wpkl_matches m ON p.match_id = m.id
                    WHERE m.matchtype_id = %d
                      AND m.home_score IS NOT NULL
                      AND m.away_score IS NOT NULL
                      AND DATE(m.play_date) >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%%Y-%%m-01')
                      AND DATE(m.play_date) <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH))
                    GROUP BY u.ID
                    ORDER BY total_points DESC
                    LIMIT 1
                ) AS ms
                JOIN wpkl_users u ON u.ID = ms.ID
            )
        ";
    }

    // Overall Competition Winner
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

    // Join parts + prepare
    $stats_query = implode(" UNION ALL ", $stats_parts);

    $prepare_args = [];
    $prepare_args[] = $selected_matchtype_id;
    $prepare_args[] = $selected_matchtype_id;
    $prepare_args[] = $selected_matchtype_id;
    $prepare_args[] = $selected_matchtype_id;
    if ($include_monthly_winner) {
        $prepare_args[] = $selected_matchtype_id;
    }
    if ($include_overall_winner) {
        $prepare_args[] = $selected_matchtype_id;
    }

    $stats_prepared = call_user_func_array(
        [$wpdb, 'prepare'],
        array_merge([$stats_query], $prepare_args)
    );
    $stats = $wpdb->get_results($stats_prepared);

    $user_id = get_current_user_id();

    ob_start();
    ?>
    <div class="rankings-page-container">

        <!-- Filter Section -->
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

        <!-- Stats -->
        <div class="stats-container">
            <h4 style="margin: 0 0 5px; text-align: center;">Competition Stats</h4>
            <p style="margin: 0 0 10px; text-align: center; font-weight: bold;"><?php echo esc_html($selected_competition_name); ?></p>

            <?php if (!empty($stats)): ?>

                <!-- Desktop Table (will be enabled only on wide screens via CSS) -->
                <table class="stats-table stats-table-desktop">
                    <thead>
                        <tr>
                            <?php foreach ($stats as $stat): ?>
                                <th><?php echo esc_html($stat->category); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($stats as $stat): ?>
                                <td>
                                    <div class="stat-user"><?php echo esc_html($stat->user_name); ?></div>
                                    <div class="stat-count"><?php echo esc_html($stat->count); ?></div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>

                <!-- Mobile Cards (default ON, desktop hides via CSS) -->
                <div class="stats-cards-mobile">
                    <?php foreach ($stats as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-card-category"><?php echo esc_html($stat->category); ?></div>
                            <div class="stat-card-user"><?php echo esc_html($stat->user_name); ?></div>
                            <div class="stat-card-count"><?php echo esc_html($stat->count); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <p style="text-align: center;">No stats available.</p>
            <?php endif; ?>
        </div>

        <!-- Rankings Table -->
        <?php if (!empty($rankings)): ?>
            <table class="football-pool-rankings-table" style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 20px;">
                <?php $current_month_name = date('F'); ?>
                <thead>
                    <tr style="background-color: #f4f4f4;">
                        <th style="padding: 8px; border: 1px solid #ddd;">Rank</th>
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

    <style>
        /* Default Layout */
        .rankings-page-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: stretch;
            max-width: 100%;
        }

        /* Stats Container */
        .stats-container {
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Table basic */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .stats-table th {
            background-color: #f4f4f4;
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 12px;
            font-weight: bold;
            white-space: normal;
            word-wrap: break-word;
            min-width: 80px;
        }

        .stats-table td {
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #ddd;
            vertical-align: top;

            /* Stop theme/plugin flex hacks */
            display: table-cell !important;
            white-space: normal !important;
        }

        .stat-user {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
            text-align: center;
        }

        .stat-count {
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        
        /* Theme/plugin is forcing some <td> to display:flex — undo it for the stats table only */
.stats-container table.stats-table td {
    display: table-cell !important;
    flex: none !important;
    align-items: initial !important;
    justify-content: initial !important;
}

/* Force stacking inside the cell, even if a parent tries to influence layout */
.stats-container table.stats-table td .stat-user,
.stats-container table.stats-table td .stat-count {
    display: block !important;
    width: 100% !important;
}

        /* ---------------------------------------
           HARD TOGGLE: prevent “both showing”
           --------------------------------------- */

        /* Desktop table OFF by default */
        .stats-table-desktop {
            display: none !important;
        }

        /* Cards ON by default */
        .stats-cards-mobile {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        /* Enable table + hide cards on wider screens */
        @media (min-width: 1200px) {
            .stats-table-desktop {
                display: table !important;
                width: 100%;
            }

            .stats-cards-mobile {
                display: none !important;
            }
        }

        /* Extra small screens */
        @media (max-width: 480px) {
            .stats-cards-mobile {
                grid-template-columns: 1fr;
            }
        }

        /* Card styling */
        .stat-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }

        .stat-card-category {
            font-size: 11px;
            font-weight: bold;
            color: #666;
            margin-bottom: 6px;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .stat-card-user {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .stat-card-count {
            font-size: 16px;
            color: #007bff;
            font-weight: bold;
        }

        /* Filter */
        @media (max-width: 768px) {
            .stats-container {
                padding: 10px;
            }
            .football-pool-filter-form {
                width: 100%;
                text-align: center;
                margin-bottom: 10px;
            }
        }
    </style>

    <?php
    return ob_get_clean();
}