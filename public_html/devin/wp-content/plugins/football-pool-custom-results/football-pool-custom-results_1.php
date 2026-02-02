<?php
/*
Plugin Name: Football Pool Custom Results with Team Flags and User Points
Description: Adds a custom results page with competition and week filtering for Football Pool plugin, displaying team flags next to their names, user points per match, and total points summary.
Version: 2.6
Author: Your Name
*/

add_shortcode('football_pool_results', 'football_pool_custom_results_shortcode');

function football_pool_custom_results_shortcode() {
    global $wpdb;

    // Fetch visible competitions (match types)
    $competitions_query = "
        SELECT mt.id, mt.name 
        FROM pool_wpkl_matchtypes AS mt
        WHERE mt.visibility = 1
        ORDER BY mt.name ASC
    ";
    $competitions = $wpdb->get_results($competitions_query);

    if (empty($competitions)) {
        return "<p>No competitions available. Please ensure the pool_wpkl_matchtypes table is populated.</p>";
    }

    // Get the selected competition and week from the URL
    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : 0;
    $selected_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : 'all';

    // Fetch weeks for filtering
    $weeks_query = "
        SELECT DISTINCT 
            WEEK(m.play_date, 1) AS week_number
        FROM pool_wpkl_matches AS m
        JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id
        WHERE mt.visibility = 1
    ";
    if ($selected_matchtype_id) {
        $weeks_query .= $wpdb->prepare(" AND m.matchtype_id = %d", $selected_matchtype_id);
    }
    $weeks_query .= " ORDER BY week_number ASC";
    $weeks = $wpdb->get_results($weeks_query);

    // Get the logged-in user's ID
    $user_id = get_current_user_id();

    // Build SQL query for matches
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

    -- 50 points for exact score match
    CASE
        WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50
        ELSE 0
    END AS full_correct_score,

    -- 20 points for correct match outcome (win/loss/draw) (✅ Not awarded if full score is earned)
    CASE
        WHEN (m.home_score = p.home_score AND m.away_score = p.away_score) THEN 0  -- Full score overrides Toto
        WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
              (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
              (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
        ELSE 0
    END AS result_points,

    -- ✅ 10 points for getting the home score correct (Even if full score is awarded)
    CASE
        WHEN m.home_score = p.home_score THEN 10
        ELSE 0
    END AS home_goal_bonus,

    -- ✅ 10 points for getting the away score correct (Even if full score is awarded)
    CASE
        WHEN m.away_score = p.away_score THEN 10
        ELSE 0
    END AS away_goal_bonus,

    -- 20 points for goal difference bonus (✅ Only awarded when result_points > 0 and full score = 0)
    CASE
        WHEN (m.home_score = p.home_score AND m.away_score = p.away_score) THEN 0  -- Full score overrides goal diff bonus
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

// Fetch user points
$user_points_results = $wpdb->get_results($wpdb->prepare($user_points_query, $user_id));

// Store user points in an associative array (match_id => total points)
$user_points = [];
foreach ($user_points_results as $points) {
    $user_points[$points->match_id] = 
        $points->full_correct_score + 
        $points->result_points + 
        $points->home_goal_bonus +  // ✅ Home score correct (always awarded)
        $points->away_goal_bonus +  // ✅ Away score correct (always awarded)
        $points->goal_difference_bonus;
}

    // Detect which table is being used
$table_to_use = "pool_wpkl_scorehistory_s1_t1";
$check_s1_t2 = $wpdb->get_var("SELECT COUNT(*) FROM pool_wpkl_scorehistory_s1_t2");

if ($check_s1_t2 > 0) {
    $table_to_use = "pool_wpkl_scorehistory_s1_t2"; // Use _s1_t2 if it has data
}

// Fetch total points and ranking for the user (separately)
$user_total_points_query = "
    WITH RankedUsers AS (
        SELECT 
            s.user_id,
            r.name AS competition_name,
            COALESCE(s.total_score, 0) AS total_points,
            DENSE_RANK() OVER (PARTITION BY r.id ORDER BY s.total_score DESC) AS user_ranking  -- Keep ranking consistent
        FROM " . $table_to_use . " AS s
        JOIN pool_wpkl_rankings AS r ON s.ranking_id = r.id
        JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
        WHERE mt.visibility = 1
    )
    SELECT competition_name, total_points, user_ranking
    FROM RankedUsers
    WHERE user_id = %d
    ORDER BY FIELD(competition_name, (SELECT GROUP_CONCAT(DISTINCT name ORDER BY id ASC SEPARATOR ',') FROM pool_wpkl_matchtypes))
";
$user_total_points_summary = $wpdb->get_results($wpdb->prepare($user_total_points_query, $user_id));

// Prepare user total points for summary table
$points_summary = [];
foreach ($user_total_points_summary as $point_row) {
    $points_summary[] = [
        'competition_name' => esc_html($point_row->competition_name),
        'total_points' => intval($point_row->total_points),
        'user_ranking' => intval($point_row->user_ranking),
    ];
}

        // Output results with filters and user points summary
    ob_start();
    ?>
    <!-- ✅ Move the User Points Summary ABOVE the Filters -->
<div class="user-points-summary">
    <h4>User Points Summary</h4>
    <table>
        <thead>
            <tr>
                <th>Competition</th>
                <th>Points</th>
                <th>Ranking</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($points_summary as $summary): ?>
                <tr>
                    <td><?php echo esc_html($summary['competition_name']); ?></td>
                    <td><?php echo esc_html($summary['total_points']); ?></td>
                    <td><?php echo esc_html($summary['user_ranking']); ?></td>
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
            <?php $week_label = 1; ?>
            <?php foreach ($weeks as $week): ?>
                <option value="<?php echo esc_attr($week->week_number); ?>" <?php selected($selected_week, $week->week_number); ?>>
                    Week <?php echo esc_html($week_label++); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- ✅ Score Toggle Button -->
<div class="score-toggle-container">
    <button type="button" id="score-toggle" class="score-toggle-button">Show Predicted Scores</button>
</div>


        <?php if (!empty($matches)): ?>
            <div class="desktop-results-container">
                <table class="football-pool-results-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Competition</th>
                            <th>Home Team</th>
                            <th>Score</th>
                            <th>Away Team</th>
                            <th>User Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td><?php echo esc_html(date('d M Y', strtotime($match->play_date))); ?></td>
                                <td><?php echo esc_html($match->competition_name ?? 'N/A'); ?></td>
                                <td class="team-cell">
                                    <img src="<?php echo esc_url($match->home_team_flag); ?>" alt="<?php echo esc_attr($match->home_team_name); ?>" class="team-flag" />
                                    <span><?php echo esc_html($match->home_team_name); ?></span>
                                </td>
                                <td class="score-cell">
                                    <div class="score-wrapper">
    <span class="actual-score"><?php echo esc_html($match->home_score . ' - ' . $match->away_score); ?></span>
    <span class="predicted-score" style="display:none;">
        <?php echo isset($match->predicted_home_score, $match->predicted_away_score)
            ? esc_html($match->predicted_home_score . ' - ' . $match->predicted_away_score)
            : '—'; ?>
    </span>
</div>
                                </td>
                                <td class="team-cell">
                                    <img src="<?php echo esc_url($match->away_team_flag); ?>" alt="<?php echo esc_attr($match->away_team_name); ?>" class="team-flag" />
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
            
            <div class="test-mobile-output">
   
    <?php if (!empty($matches)): ?>
        <div class="mobile-results-container">
            <?php foreach ($matches as $match): ?>
                <div class="match-card">
                    <div class="match-header">
                        <span class="match-date"><?php echo esc_html(date('d M Y', strtotime($match->play_date))); ?></span>
                        <span class="match-competition"><?php echo esc_html($match->competition_name ?? 'N/A'); ?></span>
                    </div>
                    <div class="match-teams">
                        <div class="team">
                            <img src="<?php echo esc_url($match->home_team_flag); ?>" alt="<?php echo esc_attr($match->home_team_name); ?>">
                            <span><?php echo esc_html($match->home_team_name); ?></span>
                        </div>
                        <div class="match-score">
    <span class="actual-score"><?php echo esc_html($match->home_score . ' - ' . $match->away_score); ?></span>
    <span class="predicted-score" style="display:none;">
        <?php echo isset($match->predicted_home_score, $match->predicted_away_score)
            ? esc_html($match->predicted_home_score . ' - ' . $match->predicted_away_score)
            : '—'; ?>
    </span>
</div>
                        <div class="team">
                            <img src="<?php echo esc_url($match->away_team_flag); ?>" alt="<?php echo esc_attr($match->away_team_name); ?>">
                            <span><?php echo esc_html($match->away_team_name); ?></span>
                        </div>
                    </div>
                    <div class="match-points">
                        <strong>User Points:</strong> 
                        <span><?php echo isset($user_points[$match->match_id]) ? esc_html($user_points[$match->match_id]) : '0'; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
        <?php else: ?>
            <p>No matches available for the selected filters.</p>
        <?php endif; ?>
    </div>

    <?php
    echo '<style>
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
    </style>';
    
    echo '<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("score-toggle");
    let showingPredictions = false;

    toggleBtn.addEventListener("click", function () {
        const actualScores = document.querySelectorAll(".actual-score");
        const predictedScores = document.querySelectorAll(".predicted-score");

        actualScores.forEach(el => el.style.display = showingPredictions ? "inline" : "none");
        predictedScores.forEach(el => el.style.display = showingPredictions ? "none" : "inline");

        toggleBtn.textContent = showingPredictions ? "Show Predicted Scores" : "Show Actual Scores";
        showingPredictions = !showingPredictions;
    });
});
</script>';
    
    
    //Output for Mobile
    
    
    return ob_get_clean();
}
?>