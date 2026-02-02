<?php
/*
Plugin Name: Custom Predictions Page with Week Filter and Auto Save (Desktop + Mobile)
Description: Adds a custom predictions page with a week filter and AJAX auto-save, including a competition column and separate mobile view.
Version: 1.9
Author: Bperrow
*/

add_shortcode('custom_football_predictions', 'custom_predictions_page');

function custom_predictions_page() {
    global $wpdb;

    // 1. Fetch competition names (match types) with visibility filter
    $competitions_query = "SELECT id, name FROM pool_wpkl_matchtypes WHERE visibility = 1 ORDER BY name ASC";
    $competitions = $wpdb->get_results($competitions_query);

    // 2. Determine which competition and week is selected (if any)
    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : 0;
   // Default to the closest upcoming week with matches
$today = date('Y-m-d'); // Get today's date

// Fetch all weeks sorted in ascending order
$weeks_query = "
    SELECT DISTINCT WEEK(m.play_date, 1) AS week_number, MIN(m.play_date) AS week_start_date
    FROM pool_wpkl_matches AS m
    JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id
    WHERE mt.visibility = 1
    GROUP BY week_number
    ORDER BY week_start_date ASC
";
$weeks = $wpdb->get_results($weeks_query);

// Find the closest week that still has unplayed matches
$selected_week = 0;
foreach ($weeks as $week) {
    // Check if this week has any remaining matches
    $matches_remaining = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM pool_wpkl_matches AS m
        JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id
        WHERE mt.visibility = 1
        AND WEEK(m.play_date, 1) = %d
        AND TIMESTAMP(m.play_date) > (NOW() + INTERVAL 30 MINUTE)
    ", $week->week_number));

    if ($matches_remaining > 0) {
        $selected_week = $week->week_number;
        break;
    }
}

// If all weeks have passed, select the last available week
if ($selected_week == 0 && !empty($weeks)) {
    $selected_week = end($weeks)->week_number;
}

// Override with user selection if they manually choose a week
if (isset($_GET['week'])) {
    $selected_week = intval($_GET['week']);
}

    // 3. Fetch weeks for filtering based on the selected competition and visibility filter
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

    // 4. Fetch matches for the selected competition and week with visibility filter
    $query = "
        SELECT 
            m.id AS match_id,
            m.play_date, 
            m.home_team_id, 
            m.away_team_id,
            t1.name AS home_team_name,
            t2.name AS away_team_name,
            t1.flag AS home_team_flag,
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
        AND TIMESTAMP(m.play_date) > (NOW() + INTERVAL 30 MINUTE)
    ";

    if ($selected_matchtype_id) {
        $query .= $wpdb->prepare(" AND m.matchtype_id = %d", $selected_matchtype_id);
    }
    if ($selected_week) {
        $query .= $wpdb->prepare(" AND WEEK(m.play_date, 1) = %d", $selected_week);
    }

    $query .= " ORDER BY m.play_date ASC";

    $matches = $wpdb->get_results($wpdb->prepare($query, get_current_user_id()));

    // 5. Output the filter form and the two layouts (desktop table + mobile cards).
    ob_start();
    ?>
    <!-- Filter Form -->
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
            <option value="0">All Weeks</option>
            <?php 
            $week_label = 1;
            foreach ($weeks as $week): ?>
                <option value="<?php echo esc_attr($week->week_number); ?>" <?php selected($selected_week, $week->week_number); ?>>
                    Week <?php echo esc_html($week_label); ?>
                </option>
                <?php $week_label++; ?>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (!empty($matches)): ?>
        <form id="predictions-form">
            <!-- ===================== -->
            <!-- Desktop Table Layout -->
            <!-- ===================== -->
            <div class="predictions-container">
    <table class="football-pool-predictions-table" style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 20px;">
        <thead>
            <tr style="background-color: #f4f4f4;">
                <th style="padding: 8px; border: 1px solid #ddd;">Date</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Competition</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Home Team</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Prediction</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Away Team</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($matches as $match): ?>
                <tr>
                    <!-- Date -->
                    <td style="padding: 8px; border: 1px solid #ddd;"><span class="match-date"><?php echo esc_html(date('d M Y', strtotime($match->play_date))); ?></span>
    <br class="desktop-only">
    <span class="match-time"><?php echo esc_html(date('H:i', strtotime($match->play_date))); ?></span></td>

                    <!-- Competition -->
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($match->competition_name); ?></td>

                    <!-- Home Team -->
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                        <img src="<?php echo esc_url($match->home_team_flag); ?>" 
                             alt="<?php echo esc_attr($match->home_team_name); ?>" 
                             style="width: 40px; height: auto; display: block; margin: 0 auto 5px;">
                        <p style="margin: 0; font-weight: bold;"><?php echo esc_html($match->home_team_name); ?></p>
                    </td>

                    <!-- Prediction -->
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <input  type="number"
                                    name="predictions[<?php echo esc_attr($match->match_id); ?>][home_score]"
                                    min="0"
                                    value="<?php echo esc_attr($match->predicted_home_score); ?>"
                                    class="prediction-input"
                                    data-match-id="<?php echo esc_attr($match->match_id); ?>"
                                    data-type="home_score"
                                    placeholder="H"
                                    style="width: 40px; height: 30px; text-align: center; border: 1px solid #ccc; border-radius: 5px;">
                            -
                            <input  type="number"
                                    name="predictions[<?php echo esc_attr($match->match_id); ?>][away_score]"
                                    min="0"
                                    value="<?php echo esc_attr($match->predicted_away_score); ?>"
                                    class="prediction-input"
                                    data-match-id="<?php echo esc_attr($match->match_id); ?>"
                                    data-type="away_score"
                                    placeholder="A"
                                    style="width: 40px; height: 30px; text-align: center; border: 1px solid #ccc; border-radius: 5px;">
                            <span class="save-indicator" style="display: none; margin-left: 5px; color: green;">✔</span>
                        </div>
                    </td>

                    <!-- Away Team -->
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                        <img src="<?php echo esc_url($match->away_team_flag); ?>" 
                             alt="<?php echo esc_attr($match->away_team_name); ?>" 
                             style="width: 40px; height: auto; display: block; margin: 0 auto 5px;">
                        <p style="margin: 0; font-weight: bold;"><?php echo esc_html($match->away_team_name); ?></p>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

            <!-- ===================== -->
            <!-- Mobile Cards Layout -->
            <!-- ===================== -->
            <div class="mobile-predictions-container">
                <?php foreach ($matches as $match): ?>
                    <div class="match-card">
                        <div class="match-header">
                            <span class="match-date"><?php echo esc_html(date('d M Y @ H:i', strtotime($match->play_date))); ?></span>
                            <span class="competition-name"><?php echo esc_html($match->competition_name); ?></span>
                        </div>
                        <div class="match-content">
                            <!-- Home Team -->
                            <div class="team-container">
                                <img src="<?php echo esc_url($match->home_team_flag); ?>" 
                                     alt="<?php echo esc_attr($match->home_team_name); ?>">
                                <p><?php echo esc_html($match->home_team_name); ?></p>
                            </div>

                            <!-- Prediction Inputs -->
                            <div class="prediction-container">
                                <input  type="number"
                                        name="predictions[<?php echo esc_attr($match->match_id); ?>][home_score]"
                                        min="0"
                                        value="<?php echo esc_attr($match->predicted_home_score); ?>"
                                        class="prediction-input"
                                        data-match-id="<?php echo esc_attr($match->match_id); ?>"
                                        data-type="home_score"
                                        placeholder="H">
                                -
                                <input  type="number"
                                        name="predictions[<?php echo esc_attr($match->match_id); ?>][away_score]"
                                        min="0"
                                        value="<?php echo esc_attr($match->predicted_away_score); ?>"
                                        class="prediction-input"
                                        data-match-id="<?php echo esc_attr($match->match_id); ?>"
                                        data-type="away_score"
                                        placeholder="A">
                                <span class="save-indicator" style="display: none; margin-left: 5px; color: green;">✔</span>
                            </div>

                            <!-- Away Team -->
                            <div class="team-container">
                                <img src="<?php echo esc_url($match->away_team_flag); ?>" 
                                     alt="<?php echo esc_attr($match->away_team_name); ?>">
                                <p><?php echo esc_html($match->away_team_name); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    <?php else: ?>
        <p>No matches available for the selected competition or week.</p>
    <?php endif; ?>

    <!-- ================================ -->
    <!-- AJAX Auto-Save for Predictions -->
    <!-- ================================ -->
    <script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.prediction-input').forEach(input => {
        input.addEventListener('change', function () {
            const matchId = this.dataset.matchId;
            const type = this.dataset.type;
            const value = this.value;

            // Find the save indicator (works for both table & mobile views)
            const parent = this.closest('td') || this.closest('.prediction-container');
            const saveIndicator = parent ? parent.querySelector('.save-indicator') : null;

            const data = new FormData();
            data.append('action', 'save_prediction');
            data.append('match_id', matchId);
            data.append('type', type);
            data.append('value', value);

            fetch(ajaxurl, {
                method: 'POST',
                body: data
            }).then(response => response.json())
              .then(result => {
                  if (result.success) {
                      if (saveIndicator) {
                          saveIndicator.style.display = 'inline';
                          setTimeout(() => {
                              saveIndicator.style.display = 'none';
                          }, 1500);
                      }
                      console.log('Prediction saved for match ' + matchId);
                  } else {
                      console.error('Failed to save prediction');
                  }
              });
        });
    });
});
</script>

<script>
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>
    <?php
    return ob_get_clean();
}

// =============================================
// AJAX handler for saving predictions
// =============================================
add_action('wp_ajax_save_prediction', 'save_prediction_handler');
function save_prediction_handler() {
    global $wpdb;

    $user_id  = get_current_user_id();
    $match_id = intval($_POST['match_id']);
    $type     = sanitize_text_field($_POST['type']);
    $value    = intval($_POST['value']);

    // Check if the user already has a prediction for this match
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM pool_wpkl_predictions WHERE user_id = %d AND match_id = %d",
        $user_id, 
        $match_id
    ));

    if ($existing) {
        // Update existing prediction
        $wpdb->update(
            'pool_wpkl_predictions',
            [ $type => $value ],
            [ 'user_id' => $user_id, 'match_id' => $match_id ],
            [ '%d' ],
            [ '%d', '%d' ]
        );
    } else {
        // Insert new prediction
        $wpdb->insert(
            'pool_wpkl_predictions',
            [
                'user_id'  => $user_id,
                'match_id' => $match_id,
                $type      => $value
            ],
            [ '%d', '%d', '%d' ]
        );
    }

    wp_send_json_success();
}
?>