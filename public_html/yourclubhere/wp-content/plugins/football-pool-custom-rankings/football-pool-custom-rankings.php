<?php
/*
Plugin Name: Football Pool Custom Rankings Page
Description: Adds a custom rankings page with competition filtering for the Football Pool plugin, highlighting the logged-in user's name in the list. Also displays stats for the selected competition.
Version: 1.3
Author: Your Name
*/

add_shortcode('football_pool_rankings', 'football_pool_custom_rankings_shortcode');

function football_pool_custom_rankings_shortcode() {
    global $wpdb;

    // Fetch visible competitions (match types)
    $competitions_query = "SELECT id, name FROM pool_wpkl_matchtypes WHERE visibility = 1 ORDER BY name ASC";
    $competitions = $wpdb->get_results($competitions_query);

    if (empty($competitions)) {
        return "<p>No competitions available. Please ensure the pool_wpkl_matchtypes table is populated.</p>";
    }

    // Default to the first competition in the list if none is selected
    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : $competitions[0]->id;
    
    // Detect which table is being used
$table_to_use = "pool_wpkl_scorehistory_s1_t1";
$check_s1_t2 = $wpdb->get_var("SELECT COUNT(*) FROM pool_wpkl_scorehistory_s1_t2");

if ($check_s1_t2 > 0) {
    $table_to_use = "pool_wpkl_scorehistory_s1_t2"; // Use _s1_t2 if it has data
}
    // Fetch rankings for the selected competition
    $rankings_query = "
    SELECT 
        s.ranking AS user_rank,
        s.total_score AS user_points,
        u.ID AS user_id,
        u.display_name AS user_name
    FROM $table_to_use AS s  -- Now uses the detected table
    JOIN pool_wpkl_rankings AS r ON s.ranking_id = r.id
    JOIN pool_wpkl_matchtypes AS mt ON r.name = mt.name
    JOIN wpkl_users AS u ON s.user_id = u.ID
    WHERE mt.visibility = 1
";
if ($selected_matchtype_id) {
    $rankings_query .= $wpdb->prepare(" AND mt.id = %d", $selected_matchtype_id);
}
$rankings_query .= " ORDER BY s.ranking ASC";

    $rankings = $wpdb->get_results($rankings_query);

    // Fetch stats for the selected competition
    $stats_query = "
    (
        SELECT 
            'Correct Scores' AS category,
            u.display_name AS user_name,
            SUM(s.full) AS count
        FROM $table_to_use AS s  -- Dynamic table selection
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
        SELECT 
            'Toto' AS category,
            u.display_name AS user_name,
            SUM(s.toto) AS count
        FROM $table_to_use AS s
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
        SELECT 
            'Goal Bonus' AS category,
            u.display_name AS user_name,
            SUM(s.goal_bonus) AS count
        FROM $table_to_use AS s
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
        SELECT 
            'Goal Difference Bonus' AS category,
            u.display_name AS user_name,
            SUM(s.goal_diff_bonus) AS count
        FROM $table_to_use AS s
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
    SELECT 
        'Monthly Winner' AS category,
        u.display_name AS user_name,
        SUM(fms.score) AS count
    FROM pool_wpkl_football_match_scores AS fms
    JOIN wpkl_users AS u ON fms.user_id = u.ID
    JOIN pool_wpkl_matches AS m ON fms.match_id = m.id
    WHERE fms.competition_id = %d
      AND DATE(m.play_date) >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
      AND DATE(m.play_date) <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH))
    GROUP BY fms.user_id
    ORDER BY count DESC
    LIMIT 1
)
";
    $stats = $wpdb->get_results($wpdb->prepare($stats_query, $selected_matchtype_id, $selected_matchtype_id, $selected_matchtype_id, $selected_matchtype_id, $selected_matchtype_id));

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
                    <thead>
                        <tr style="background-color: #f4f4f4;">
                            <th style="padding: 8px; border: 1px solid #ddd;">Rank</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">User Name</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rankings as $ranking): ?>
                            <tr style="<?php echo $ranking->user_id == $user_id ? 'background-color: #dff0d8;' : ''; ?>">
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($ranking->user_rank); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($ranking->user_name); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($ranking->user_points); ?></td>
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
            <h4 style="margin: 0 0 10px; text-align: center;">Competition Stats</h4>
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

/* 📱 Mobile Layout Fix */
@media (max-width: 768px) {
    .rankings-page-container {
        flex-direction: column; /* Stack everything vertically */
        align-items: center; /* Center all elements */
    }

    .football-pool-filter-form {
        width: 100%; /* Make filter dropdown full width */
        text-align: center; /* Center align text */
        margin-bottom: 10px; /* Add spacing */
    }

    .stats-container {
        width: 90%; /* Responsive full width */
        max-width: 350px; /* Prevent it from being too wide */
        margin: 10px auto; /* Center it */
        text-align: center; /* Align content to center */
        order: -1; /* 🚀 Move it above the rankings table */
    }

    .stats-container table {
        width: 100%; /* Ensure table inside the box fits */
    }
}
</style>';
?>
    
    <?php
    return ob_get_clean();
}
?>