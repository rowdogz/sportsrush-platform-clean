<?php
/*
Plugin Name: Football Pool Custom Results with Team Flags and User Points
Description: Adds a custom results page with competition and week filtering for Football Pool plugin, displaying team flags next to their names, user points per match, and total points summary.
Version: 2.8
Author: Your Name
*/

add_shortcode('football_pool_results', 'football_pool_custom_results_shortcode');

function football_pool_custom_results_shortcode() {
    global $wpdb;

    // Fetch visible competitions for the filter dropdown
    $competitions_query = "SELECT id, name FROM pool_wpkl_matchtypes WHERE visibility = 1 ORDER BY name ASC";
    $competitions = $wpdb->get_results($competitions_query);

    if (empty($competitions)) {
        return "<p>No competitions available. Please ensure the pool_wpkl_matchtypes table is populated.</p>";
    }

    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : 0;
    $selected_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : 'all';
    $user_id = get_current_user_id();

    // Weeks for filtering
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

    // Match results with predictions
    $matches_query = "
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
    if ($selected_matchtype_id) {
            $matches_query .= $wpdb->prepare(" AND m.matchtype_id = %d", $selected_matchtype_id);
    }
    if ($selected_week !== 'all') {
        $matches_query .= $wpdb->prepare(" AND WEEK(m.play_date, 1) = %d", intval($selected_week));
    }
    $matches_query .= " ORDER BY m.play_date ASC";
    $matches = $wpdb->get_results($wpdb->prepare($matches_query, $user_id));
    
    // Fetch user points for each match
$user_points_query = "
    SELECT 
    p.match_id,
    m.home_score AS actual_home_score, 
    m.away_score AS actual_away_score,
    p.home_score AS predicted_home_score, 
    p.away_score AS predicted_away_score,

    CASE
        WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50
        ELSE 0
    END AS full_correct_score,

    CASE
        WHEN (m.home_score = p.home_score AND m.away_score = p.away_score) THEN 0
        WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
              (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
              (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
        ELSE 0
    END AS result_points,

    CASE
        WHEN m.home_score = p.home_score THEN 10
        ELSE 0
    END AS home_goal_bonus,

    CASE
        WHEN m.away_score = p.away_score THEN 10
        ELSE 0
    END AS away_goal_bonus,

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
WHERE p.user_id = %d;
";

$user_points_results = $wpdb->get_results($wpdb->prepare($user_points_query, $user_id));

$user_points = [];
foreach ($user_points_results as $points) {
    $user_points[$points->match_id] = 
        $points->full_correct_score + 
        $points->result_points + 
        $points->home_goal_bonus +
        $points->away_goal_bonus +
        $points->goal_difference_bonus;
}

$all_matches_query = "
    SELECT 
        m.id AS match_id,
        m.matchtype_id,
        mt.name AS competition_name,
        m.home_score,
        m.away_score,
        p.home_score AS predicted_home_score,
        p.away_score AS predicted_away_score,

        CASE
            WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50
            ELSE 0
        END AS full_correct_score,

        CASE
            WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
            WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                  (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                  (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
            ELSE 0
        END AS result_points,

        CASE
            WHEN m.home_score = p.home_score THEN 10 ELSE 0
        END AS home_goal_bonus,

        CASE
            WHEN m.away_score = p.away_score THEN 10 ELSE 0
        END AS away_goal_bonus,

        CASE
            WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
            WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                  (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                  (m.home_score = m.away_score AND p.home_score = p.away_score))
            AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
                (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score)) THEN 20
            ELSE 0
        END AS goal_difference_bonus

    FROM pool_wpkl_predictions p
    JOIN pool_wpkl_matches m ON p.match_id = m.id
    JOIN pool_wpkl_matchtypes mt ON m.matchtype_id = mt.id
    WHERE p.user_id = %d
    AND m.home_score IS NOT NULL
    AND m.away_score IS NOT NULL
";
$all_matches = $wpdb->get_results($wpdb->prepare($all_matches_query, $user_id));

$manual_totals_by_competition = [];

foreach ($all_matches as $match) {
    $competition_name = $match->competition_name;
    $match_points = $match->full_correct_score +
                    $match->result_points +
                    $match->home_goal_bonus +
                    $match->away_goal_bonus +
                    $match->goal_difference_bonus;

    if (!isset($manual_totals_by_competition[$competition_name])) {
        $manual_totals_by_competition[$competition_name] = 0;
    }

    $manual_totals_by_competition[$competition_name] += $match_points;
}

$table_to_use = "pool_wpkl_scorehistory_s1_t1";
$check_s1_t2 = $wpdb->get_var("SELECT COUNT(*) FROM pool_wpkl_scorehistory_s1_t2");

if ($check_s1_t2 > 0) {
    $table_to_use = "pool_wpkl_scorehistory_s1_t2";
}

    // FIXED: Calculate rankings from predictions table instead of pool_wpkl_football_match_scores
    // This query calculates points for ALL users in ACTIVE competitions (visibility = 1)
    // and then ranks them, returning only the current user's rankings
    $ranking_query = "
    WITH UserPoints AS (
        SELECT 
            p.user_id,
            mt.id AS competition_id,
            mt.name AS competition_name,
            SUM(
                CASE WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50 ELSE 0 END +
                CASE 
                    WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                    WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                          (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                          (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
                    ELSE 0
                END +
                CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END +
                CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END +
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
        FROM pool_wpkl_predictions p
        JOIN pool_wpkl_matches m ON p.match_id = m.id
        JOIN pool_wpkl_matchtypes mt ON m.matchtype_id = mt.id
        WHERE mt.visibility = 1
        AND m.home_score IS NOT NULL
        AND m.away_score IS NOT NULL
        GROUP BY p.user_id, mt.id, mt.name
    ),
    Ranked AS (
        SELECT 
            user_id,
            competition_id,
            competition_name,
            total_points,
            DENSE_RANK() OVER (PARTITION BY competition_id ORDER BY total_points DESC) AS rank
        FROM UserPoints
    )
    SELECT competition_id, competition_name, user_id, total_points, rank
    FROM Ranked
    WHERE user_id = %d
    ORDER BY competition_name ASC
";
    $ranking_results = $wpdb->get_results($wpdb->prepare($ranking_query, $user_id));

    $points_summary = [];

foreach ($ranking_results as $row) {
    $competition_name = esc_html($row->competition_name);

    $points_summary[] = [
        'competition_name' => $competition_name,
        'total_points' => intval($row->total_points),
        'user_ranking' => intval($row->rank)
    ];
}

    ob_start();
    ?>

    <!-- User Summary -->
    <div class="user-points-summary">
        <h4>User Points Summary</h4>
        <table>
            <thead><tr><th>Competition</th><th>Points</th><th>Ranking</th></tr></thead>
            <tbody>
            <?php foreach ($points_summary as $summary): ?>
                <tr>
                    <td><?php echo $summary['competition_name']; ?></td>
                    <td><?php echo $summary['total_points']; ?></td>
                    <td><?php echo $summary['user_ranking']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Filters -->
    <div class="filters-summary-container">
        <form method="get" class="football-pool-filter-form">
            <label for="competition">Filter by Competition:</label>
            <select name="competition" id="competition" onchange="this.form.submit();">
                <option value="0">All Competitions</option>
                <?php foreach ($competitions as $competition): ?>
                    <option value="<?php echo esc_attr($competition->id); ?>" <?php selected($selected_matchtype_id, $competition->id); ?>>
                        <?php echo esc_html($competition->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="week">Filter by Week:</label>
            <select name="week" id="week" onchange="this.form.submit();">
                <option value="all">All Weeks</option>
                <?php $label = 1; foreach ($weeks as $week): ?>
                    <option value="<?php echo $week->week_number; ?>" <?php selected($selected_week, $week->week_number); ?>>
                        Week <?php echo $label++; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Toggle Button -->
    <div class="score-toggle-container">
        <button type="button" id="score-toggle" class="score-toggle-button">Show Predicted Scores</button>
    </div>

    <!-- Results Table -->
    <?php if (!empty($matches)): ?>
        <div class="desktop-results-container">
            <table class="football-pool-results-table">
                <thead>
                    <tr>
                        <th>Date</th><th>Competition</th><th>Home</th><th>Score</th><th>Away</th><th>User Points</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($matches as $match): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($match->play_date)); ?></td>
                        <td><?php echo esc_html($match->competition_name); ?></td>
                        <td class="team-cell">
                            <img src="<?php echo esc_url($match->home_team_flag); ?>" alt="" class="team-flag" />
                            <span><?php echo esc_html($match->home_team_name); ?></span>
                        </td>
                        <td class="score-cell">
                            <div class="score-wrapper">
                                <span class="actual-score"><?php echo "{$match->home_score} - {$match->away_score}"; ?></span>
                                <span class="predicted-score" style="display:none;">
                                    <?php echo (isset($match->predicted_home_score) && isset($match->predicted_away_score)) 
                                        ? "{$match->predicted_home_score} - {$match->predicted_away_score}" 
                                        : '—'; ?>
                                </span>
                            </div>
                        </td>
                        <td class="team-cell">
                            <img src="<?php echo esc_url($match->away_team_flag); ?>" alt="" class="team-flag" />
                            <span><?php echo esc_html($match->away_team_name); ?></span>
                        </td>
                        <td class="points-cell">
    <strong><?php echo isset($user_points[$match->match_id]) ? esc_html($user_points[$match->match_id]) : '0'; ?></strong>
</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Output -->
        <div class="test-mobile-output">
            <div class="mobile-results-container">
                <?php foreach ($matches as $match): ?>
                    <div class="match-card">
                        <div class="match-header">
                            <span class="match-date"><?php echo date('d M Y', strtotime($match->play_date)); ?></span>
                            <span class="match-competition"><?php echo esc_html($match->competition_name); ?></span>
                        </div>
                        <div class="match-teams">
                            <div class="team">
                                <img src="<?php echo esc_url($match->home_team_flag); ?>" alt="">
                                <span><?php echo esc_html($match->home_team_name); ?></span>
                            </div>
                            <div class="match-score">
                                <span class="actual-score"><?php echo "{$match->home_score} - {$match->away_score}"; ?></span>
                                <span class="predicted-score" style="display:none;">
                                    <?php echo (isset($match->predicted_home_score) && isset($match->predicted_away_score)) 
                                        ? "{$match->predicted_home_score} - {$match->predicted_away_score}" 
                                        : '—'; ?>
                                </span>
                            </div>
                            <div class="team">
                                <img src="<?php echo esc_url($match->away_team_flag); ?>" alt="">
                                <span><?php echo esc_html($match->away_team_name); ?></span>
                            </div>
                        </div>
                        <div class="match-points">
                            <strong>User Points:</strong>
                            <strong><?php echo isset($user_points[$match->match_id]) ? esc_html($user_points[$match->match_id]) : '0'; ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <p>No matches available for the selected filters.</p>
    <?php endif; ?>

    <style>
        .results-container {
    width: 100%;
    padding: 20px;
}
.filters-summary-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 900px;
    margin: 0 auto 20px;
    gap: 15px;
}
.football-pool-filter-form {
    display: flex;
    align-items: center;
    gap: 15px;
}
.user-points-summary {
    border: 1px solid #ddd;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
}
.user-points-summary h4 {
    margin: 0 0 10px;
    text-align: center;
}
.user-points-summary table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.user-points-summary th, .user-points-summary td {
    padding: 5px;
    border: 1px solid #ddd;
    text-align: right;
}
.user-points-summary th {
    text-align: left;
    background-color: #f4f4f4;
}
.desktop-results-container {
    display: block;
    width: 100%;
}
.football-pool-results-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
.football-pool-results-table th, .football-pool-results-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}
.football-pool-results-table th {
    background-color: #f4f4f4;
    text-transform: uppercase;
}
.team-cell {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 10px;
    text-align: left;
    padding-left: 10px;
}
.team-flag {
    width: 40px;
    height: auto;
    display: block;
}
.points-cell strong {
    font-size: 16px;
    font-weight: bold;
}

.test-mobile-output {
    display: none;
}

@media (max-width: 768px) {
    .desktop-results-container {
        display: none;
    }

    .test-mobile-output {
        display: block;
        border: none;
        padding: 0;
        margin-top: 0;
        border-radius: 0;
        background-color: transparent;
    }

    .match-card {
    margin-bottom: 20px;
    padding: 10px;
    border: 1px solid #ddd;
    background: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.05);
}

    .filters-summary-container {
        flex-direction: column;
        align-items: stretch;
    }

    .user-points-summary {
        width: 100%;
        margin-top: 10px;
        padding: 10px;
        font-size: 14px;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }

    .user-points-summary table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        table-layout: fixed;
    }

    .user-points-summary th, .user-points-summary td {
        padding: 5px;
        border: 1px solid #ddd;
        text-align: center;
        white-space: nowrap;
    }

    .user-points-summary table {
        display: block;
        width: 100%;
        overflow-x: auto;
    }

    .football-pool-filter-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }

    .football-pool-filter-form label {
        display: block;
        font-size: 14px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .football-pool-filter-form select {
        width: 100%;
        padding: 8px;
        font-size: 16px;
    }

    .match-teams {
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-align: center;
        width: 100%;
    }

    .team {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 30%;
        min-width: 100px;
    }

    .team img {
        width: 40px;
        height: 40px;
        margin-bottom: 5px;
    }

    .team span {
        display: block;
        font-size: 14px;
        word-wrap: break-word;
        text-align: center;
        max-width: 100px;
    }

    .match-score {
        width: 15%;
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        min-width: 50px;
    }
}

@media (max-width: 480px) {
    .match-teams {
        flex-direction: column;
        align-items: center;
    }

    .team {
        width: 100%;
    }

    .match-score {
        width: 100%;
        font-size: 16px;
        margin-top: 5px;
        text-align: center;
    }
    
    .team img {
        width: 30px;
        height: auto;
        margin-bottom: 3px;
    }

    .team span {
        font-size: 12px;
        max-width: 80%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggleBtn = document.getElementById("score-toggle");
        let showingPredictions = false;
        toggleBtn.addEventListener("click", function () {
            document.querySelectorAll(".actual-score").forEach(el => el.style.display = showingPredictions ? "inline" : "none");
            document.querySelectorAll(".predicted-score").forEach(el => el.style.display = showingPredictions ? "none" : "inline");
            toggleBtn.textContent = showingPredictions ? "Show Predicted Scores" : "Show Actual Scores";
            showingPredictions = !showingPredictions;
        });
    });
    </script>

    <?php
    return ob_get_clean();
}
?>
