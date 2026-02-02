<?php
/**
 * Template Name: Match Results
 */

get_header();

if (!is_user_logged_in()) {
    echo '<p>You must be logged in to view your match results.</p>';
    get_footer();
    exit;
}

global $wpdb, $current_user;
wp_get_current_user();

$user_id = $current_user->ID;

// Fetch matches and predictions for the logged-in user
$results = $wpdb->get_results("
    SELECT m.id AS match_id, m.play_date, m.home_team_id, m.away_team_id, p.points
    FROM pool_wpkl_matches m
    LEFT JOIN pool_wpkl_user_predictions p ON m.id = p.match_id
    WHERE p.user_id = $user_id
    ORDER BY m.play_date ASC
");

echo '<div class="match-results-container">';
echo '<h1>Match Results</h1>';

if ($results) {
    echo '<table class="match-results-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Match</th>';
    echo '<th>Date</th>';
    echo '<th>Points Scored</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($results as $result) {
        $home_team = $wpdb->get_var("SELECT name FROM pool_wpkl_teams WHERE id = $result->home_team_id");
        $away_team = $wpdb->get_var("SELECT name FROM pool_wpkl_teams WHERE id = $result->away_team_id");
        $date = date('d-m-Y H:i', strtotime($result->play_date));
        $points = $result->points ?: 0;

        echo '<tr>';
        echo '<td>' . esc_html($home_team) . ' vs ' . esc_html($away_team) . '</td>';
        echo '<td>' . esc_html($date) . '</td>';
        echo '<td>' . esc_html($points) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
} else {
    echo '<p>No match results available.</p>';
}

echo '</div>';

get_footer();