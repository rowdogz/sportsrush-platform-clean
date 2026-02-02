<<?php
/*
Plugin Name: Football Pool Custom Results with Accurate Rankings and User Points
Description: Displays match results, calculates user points per match, and shows correct rankings using score history.
Version: 3.0
Author: Your Name
*/

add_shortcode('football_pool_results', 'football_pool_custom_results_shortcode');

function football_pool_custom_results_shortcode() {
    global $wpdb;

    $competitions = $wpdb->get_results("SELECT id, name FROM pool_wpkl_matchtypes WHERE visibility = 1 ORDER BY name ASC");
    if (empty($competitions)) return "<p>No competitions available.</p>";

    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : 0;
    $selected_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : 'all';
    $user_id = get_current_user_id();

    // Weeks for filtering
    $weeks_query = "SELECT DISTINCT WEEK(m.play_date, 1) AS week_number FROM pool_wpkl_matches AS m JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id WHERE mt.visibility = 1";
    if ($selected_matchtype_id) $weeks_query .= $wpdb->prepare(" AND m.matchtype_id = %d", $selected_matchtype_id);
    $weeks_query .= " ORDER BY week_number ASC";
    $weeks = $wpdb->get_results($weeks_query);

    // Match results & predictions
    $matches_query = "
        SELECT 
            m.id AS match_id, m.play_date, 
            t1.name AS home_team_name, t1.flag AS home_team_flag,
            t2.name AS away_team_name, t2.flag AS away_team_flag,
            m.home_score, m.away_score,
            mt.name AS competition_name,
            p.home_score AS predicted_home_score,
            p.away_score AS predicted_away_score
        FROM pool_wpkl_matches AS m
        JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id
        LEFT JOIN pool_wpkl_teams AS t1 ON m.home_team_id = t1.id
        LEFT JOIN pool_wpkl_teams AS t2 ON m.away_team_id = t2.id
        LEFT JOIN pool_wpkl_predictions AS p ON m.id = p.match_id AND p.user_id = %d
        WHERE mt.visibility = 1 AND m.home_score IS NOT NULL AND m.away_score IS NOT NULL";
    if ($selected_matchtype_id) $matches_query .= $wpdb->prepare(" AND m.matchtype_id = %d", $selected_matchtype_id);
    if ($selected_week !== 'all') $matches_query .= $wpdb->prepare(" AND WEEK(m.play_date, 1) = %d", intval($selected_week));
    $matches_query .= " ORDER BY m.play_date ASC";
    $matches = $wpdb->get_results($wpdb->prepare($matches_query, $user_id));

    // Manually calculate user points
    $points_query = "
        SELECT p.match_id,
            CASE WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50 ELSE 0 END AS full,
            CASE WHEN m.home_score != p.home_score OR m.away_score != p.away_score THEN
                CASE WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                           (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                           (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20 ELSE 0 END
            ELSE 0 END AS result,
            CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END AS home_bonus,
            CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END AS away_bonus,
            CASE WHEN m.home_score != p.home_score OR m.away_score != p.away_score THEN
                CASE WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                           (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                           (m.home_score = m.away_score AND p.home_score = p.away_score)) AND 
                     (ABS(m.home_score - m.away_score) = ABS(p.home_score - p.away_score)) THEN 20 ELSE 0 END
            ELSE 0 END AS diff_bonus
        FROM pool_wpkl_predictions p
        JOIN pool_wpkl_matches m ON p.match_id = m.id
        WHERE p.user_id = %d AND m.home_score IS NOT NULL AND m.away_score IS NOT NULL";

    $points_results = $wpdb->get_results($wpdb->prepare($points_query, $user_id));
    $user_points = [];
    foreach ($points_results as $p) {
        $user_points[$p->match_id] = $p->full + $p->result + $p->home_bonus + $p->away_bonus + $p->diff_bonus;
    }

    // Rankings summary from scorehistory
    $score_table = ($wpdb->get_var("SELECT COUNT(*) FROM pool_wpkl_scorehistory_s1_t2") > 0) ? "pool_wpkl_scorehistory_s1_t2" : "pool_wpkl_scorehistory_s1_t1";
    $ranking_query = "
        SELECT competition_name, total_points, user_ranking FROM (
            SELECT 
                s.user_id,
                r.name AS competition_name,
                COALESCE(s.total_score, 0) AS total_points,
                DENSE_RANK() OVER (PARTITION BY r.id ORDER BY s.total_score DESC) AS user_ranking
            FROM $score_table AS s
            JOIN pool_wpkl_rankings r ON s.ranking_id = r.id
            JOIN pool_wpkl_matchtypes mt ON r.name = mt.name
            WHERE mt.visibility = 1
        ) AS ranked
        WHERE user_id = %d";
    $rankings = $wpdb->get_results($wpdb->prepare($ranking_query, $user_id));

    $competition_map = [];
    foreach ($competitions as $c) $competition_map[$c->id] = $c->name;
    $points_summary = [];
    foreach ($rankings as $row) {
        $points_summary[] = [
            'competition_name' => esc_html($row->competition_name),
            'total_points' => intval($row->total_points),
            'user_ranking' => intval($row->user_ranking)
        ];
    }

    // Output begins
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
                            <strong><?php echo $match->user_points ?? 0; ?></strong>
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
                            <span><?php echo $match->user_points ?? 0; ?></span>
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
    flex-direction: column; /* Stack filters & summary */
    align-items: center;
    width: 100%;
    max-width: 900px; /* Keep layout centered */
    margin: 0 auto 20px;
    gap: 15px; /* Spacing between filters and summary */
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
    max-width: 300px;
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
    justify-content: flex-start; /* Align content to the left */
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

/* Hide the test mobile output on desktop */
.test-mobile-output {
    display: none;
}

/* ✅ Default Mobile Styles (KEEP EXISTING LAYOUT) */
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
    background: #f9f9f9; /* Light gray to match default theme */
    border-radius: 8px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.05); /* Softer shadow */
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

    /* ✅ Match Layout for Mobile (KEEP EXISTING) */
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
        width: 30%; /* Ensure equal spacing */
        min-width: 100px; /* Prevent shrinking */
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
        max-width: 100px; /* Prevent overly long names from stretching layout */
    }

    .match-score {
        width: 15%; /* Set space for the score */
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        min-width: 50px;
    }
}

/* ✅ Fix Small Screen Overflow Issue (ONLY APPLIES TO VERY SMALL SCREENS) */
@media (max-width: 480px) {
    .match-teams {
        flex-direction: column; /* Stack teams vertically */
        align-items: center;
    }

    .team {
        width: 100%; /* Full width for very small screens */
    }

    .match-score {
        width: 100%;
        font-size: 16px;
        margin-top: 5px;
        text-align: center;
    }
    
    .team img {
        width: 30px; /* Reduce size slightly for small screens */
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