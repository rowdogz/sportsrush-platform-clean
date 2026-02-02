<?php
/*
Plugin Name: Custom Predictions Page with Round Filter + Auto Save + Load More (All Rounds)
Description: Predictions page with competition/round filters, AJAX auto-save, and “All Rounds” loads 1 round at a time via Load More. Auto-selects next round when changing competition.
Version: 3.0
Author: Bperrow
*/

add_shortcode('custom_football_predictions', 'custom_predictions_page');

/**
 * =============================
 * Helpers
 * =============================
 */

function sr_get_competitions($wpdb) {
    return $wpdb->get_results("
        SELECT id, name
        FROM pool_wpkl_matchtypes
        WHERE visibility = 1
        ORDER BY name ASC
    ");
}

/**
 * Returns a list of DISTINCT rounds that have FUTURE matches (play_date > NOW()+30m),
 * filtered by visibility and optional competition.
 *
 * NOTE: Only includes rounds that are NOT NULL.
 */
function sr_get_future_rounds($wpdb, $selected_matchtype_id = 0) {
    $sql = "
        SELECT DISTINCT m.round AS round_number, MIN(m.play_date) AS round_start_date
        FROM pool_wpkl_matches AS m
        JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id
        WHERE mt.visibility = 1
          AND TIMESTAMP(m.play_date) > (NOW() + INTERVAL 30 MINUTE)
          AND m.round IS NOT NULL
    ";
    if ($selected_matchtype_id) {
        $sql .= $wpdb->prepare(" AND m.matchtype_id = %d", $selected_matchtype_id);
    }
    $sql .= "
        GROUP BY m.round
        ORDER BY round_start_date ASC
    ";

    return $wpdb->get_results($sql);
}

function sr_fetch_matches_for_round($wpdb, $user_id, $selected_matchtype_id, $round_number) {
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
          AND m.round = %d
    ";

    $params = [$user_id, $round_number];

    if ($selected_matchtype_id) {
        $query .= " AND m.matchtype_id = %d";
        $params[] = $selected_matchtype_id;
    }

    $query .= " ORDER BY m.play_date ASC";

    return $wpdb->get_results($wpdb->prepare($query, ...$params));
}

/**
 * =============================
 * Main shortcode
 * =============================
 */
function custom_predictions_page() {
    global $wpdb;

    $competitions = sr_get_competitions($wpdb);

    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : 0;
    $selected_round        = isset($_GET['round']) ? intval($_GET['round']) : 0; // 0 means "All Rounds"

    // Populate dropdown with *future rounds* for the currently-selected competition (or all competitions)
    $rounds = sr_get_future_rounds($wpdb, $selected_matchtype_id);

    // In "All Rounds" mode, we auto-pick the next available round (first in the list)
    $first_round = 0;
    if (!empty($rounds)) {
        $first_round = (int)$rounds[0]->round_number;
    }

    $round_numbers = array_map(function($r){ return (int)$r->round_number; }, $rounds);
    $is_all_rounds = ($selected_round === 0);

    $initial_round = $is_all_rounds ? $first_round : $selected_round;

    $user_id = get_current_user_id();
    $matches = [];
    if ($initial_round) {
        $matches = sr_fetch_matches_for_round($wpdb, $user_id, $selected_matchtype_id, $initial_round);
    }

    // For all-rounds mode, remove initial round from queue so JS loads the remainder
    $remaining_rounds = $round_numbers;
    if ($is_all_rounds && $initial_round) {
        $remaining_rounds = array_values(array_filter($remaining_rounds, function($r) use ($initial_round) {
            return $r !== $initial_round;
        }));
    }

    $nonce = wp_create_nonce('sr_load_round_nonce');

    ob_start();
    ?>
    <!-- Filter Form -->
    <form method="get" class="football-pool-filter-form">
        <label for="competition">Filter by Competition:</label>
        <select name="competition" id="competition">
            <option value="0">All Competitions</option>
            <?php foreach ($competitions as $competition): ?>
                <option value="<?php echo esc_attr($competition->id); ?>" <?php selected($selected_matchtype_id, (int)$competition->id); ?>>
                    <?php echo esc_html($competition->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="round">Filter by Round:</label>
        <select name="round" id="round">
            <option value="0">All Rounds</option>
            <?php foreach ($rounds as $r): ?>
                <option value="<?php echo esc_attr((int)$r->round_number); ?>" <?php selected($selected_round, (int)$r->round_number); ?>>
                    Round <?php echo esc_html((int)$r->round_number); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (empty($rounds)): ?>
        <p>No upcoming rounds found yet (rounds are missing or all matches are in the past).</p>
    <?php elseif (!empty($matches)): ?>
        <form id="predictions-form">
            <!-- Desktop Table Layout -->
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
                    <tbody id="sr-desktop-tbody">
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">
                                    <span class="match-date"><?php echo esc_html(date('d M Y', strtotime($match->play_date))); ?></span>
                                    <br class="desktop-only">
                                    <span class="match-time">
                                        <?php 
                                        $utc_date = new DateTime($match->play_date, new DateTimeZone('UTC'));
                                        $utc_date->setTimezone(new DateTimeZone('Europe/London'));
                                        echo esc_html($utc_date->format('H:i')); 
                                        ?>
                                    </span>
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($match->competition_name); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <img src="<?php echo esc_url($match->home_team_flag); ?>" alt="<?php echo esc_attr($match->home_team_name); ?>" style="width: 40px; height: auto; display: block; margin: 0 auto 5px;">
                                    <p style="margin: 0; font-weight: bold;"><?php echo esc_html($match->home_team_name); ?></p>
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center;">
                                        <input type="number"
                                            name="predictions[<?php echo esc_attr($match->match_id); ?>][home_score]"
                                            min="0"
                                            value="<?php echo esc_attr($match->predicted_home_score); ?>"
                                            class="prediction-input"
                                            data-match-id="<?php echo esc_attr($match->match_id); ?>"
                                            data-type="home_score"
                                            placeholder="H"
                                            style="width: 40px; height: 30px; text-align: center; border: 1px solid #ccc; border-radius: 5px;">
                                        -
                                        <input type="number"
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
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <img src="<?php echo esc_url($match->away_team_flag); ?>" alt="<?php echo esc_attr($match->away_team_name); ?>" style="width: 40px; height: auto; display: block; margin: 0 auto 5px;">
                                    <p style="margin: 0; font-weight: bold;"><?php echo esc_html($match->away_team_name); ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards Layout -->
            <div class="mobile-predictions-container" id="sr-mobile-container">
                <?php foreach ($matches as $match): ?>
                    <div class="match-card">
                        <div class="match-header">
                            <span class="match-date">
                                <?php 
                                $utc_date = new DateTime($match->play_date, new DateTimeZone('UTC'));
                                $utc_date->setTimezone(new DateTimeZone('Europe/London'));
                                echo esc_html($utc_date->format('d M Y @ H:i')); 
                                ?>
                            </span>
                            <span class="competition-name"><?php echo esc_html($match->competition_name); ?></span>
                        </div>
                        <div class="match-content">
                            <div class="team-container">
                                <img src="<?php echo esc_url($match->home_team_flag); ?>" alt="<?php echo esc_attr($match->home_team_name); ?>">
                                <p><?php echo esc_html($match->home_team_name); ?></p>
                            </div>

                            <div class="prediction-container">
                                <input type="number"
                                    name="predictions[<?php echo esc_attr($match->match_id); ?>][home_score]"
                                    min="0"
                                    value="<?php echo esc_attr($match->predicted_home_score); ?>"
                                    class="prediction-input"
                                    data-match-id="<?php echo esc_attr($match->match_id); ?>"
                                    data-type="home_score"
                                    placeholder="H">
                                -
                                <input type="number"
                                    name="predictions[<?php echo esc_attr($match->match_id); ?>][away_score]"
                                    min="0"
                                    value="<?php echo esc_attr($match->predicted_away_score); ?>"
                                    class="prediction-input"
                                    data-match-id="<?php echo esc_attr($match->match_id); ?>"
                                    data-type="away_score"
                                    placeholder="A">
                                <span class="save-indicator" style="display: none; margin-left: 5px; color: green;">✔</span>
                            </div>

                            <div class="team-container">
                                <img src="<?php echo esc_url($match->away_team_flag); ?>" alt="<?php echo esc_attr($match->away_team_name); ?>">
                                <p><?php echo esc_html($match->away_team_name); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($is_all_rounds && !empty($remaining_rounds)): ?>
                <div id="sr-loadmore-wrap"
                     data-competition="<?php echo esc_attr($selected_matchtype_id); ?>"
                     data-roundqueue="<?php echo esc_attr(wp_json_encode($remaining_rounds)); ?>"
                     data-nonce="<?php echo esc_attr($nonce); ?>"
                     style="margin-top:16px;">
                    <button type="button" id="sr-load-more" class="button">Load next round</button>
                    <span id="sr-load-more-status" style="margin-left:10px;"></span>
                </div>
            <?php endif; ?>

        </form>
    <?php else: ?>
        <p>No matches available for the selected competition/round.</p>
    <?php endif; ?>

    <script>
    // Front-end AJAX URL
    var ajaxurl = "<?php echo esc_js(admin_url('admin-ajax.php')); ?>";

    // Re-bind autosave after dynamically adding inputs
    function srBindAutoSave(root) {
        (root || document).querySelectorAll('.prediction-input').forEach(input => {
            if (input.dataset.srBound === "1") return;
            input.dataset.srBound = "1";

            input.addEventListener('change', function () {
                const matchId = this.dataset.matchId;
                const type = this.dataset.type;
                const value = this.value;

                const parent = this.closest('td') || this.closest('.prediction-container');
                const saveIndicator = parent ? parent.querySelector('.save-indicator') : null;

                const data = new FormData();
                data.append('action', 'save_prediction');
                data.append('match_id', matchId);
                data.append('type', type);
                data.append('value', value);

                fetch(ajaxurl, { method: 'POST', body: data })
                    .then(r => r.json())
                    .then(result => {
                        if (result && result.success) {
                            if (saveIndicator) {
                                saveIndicator.style.display = 'inline';
                                setTimeout(() => saveIndicator.style.display = 'none', 1500);
                            }
                        } else {
                            console.error('Failed to save prediction');
                        }
                    })
                    .catch(() => console.error('Failed to save prediction'));
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        srBindAutoSave(document);

        // AUTO-SELECT NEXT ROUND when competition changes:
        // Instead of submitting with the current round, force round=0 so PHP picks the next available.
        const form = document.querySelector('.football-pool-filter-form');
        const comp = document.getElementById('competition');
        const rnd  = document.getElementById('round');

        function setParamAndGo(params) {
            const url = new URL(window.location.href);
            Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
            window.location.href = url.toString();
        }

        if (comp) {
            comp.addEventListener('change', function () {
                const competition = this.value || '0';
                setParamAndGo({ competition: competition, round: '0' });
            });
        }

        if (rnd) {
            rnd.addEventListener('change', function () {
                if (form) form.submit();
            });
        }

        // Load more (All Rounds only)
        const wrap = document.getElementById('sr-loadmore-wrap');
        const btn = document.getElementById('sr-load-more');
        const status = document.getElementById('sr-load-more-status');
        if (!wrap || !btn) return;

        function setStatus(msg){ if(status) status.textContent = msg || ''; }

        btn.addEventListener('click', async function(){
            btn.disabled = true;
            setStatus('Loading…');

            let queue;
            try { queue = JSON.parse(wrap.dataset.roundqueue || '[]'); } catch(e) { queue = []; }
            if (!queue.length) {
                setStatus('No more rounds.');
                btn.style.display = 'none';
                return;
            }

            const nextRound = queue.shift();
            wrap.dataset.roundqueue = JSON.stringify(queue);

            const data = new FormData();
            data.append('action', 'sr_load_round');
            data.append('nonce', wrap.dataset.nonce);
            data.append('competition', wrap.dataset.competition || '0');
            data.append('round', String(nextRound));

            try {
                const res = await fetch(ajaxurl, { method:'POST', body: data });
                const json = await res.json();

                if (!json || !json.success) {
                    setStatus((json && json.data && json.data.message) ? json.data.message : 'Load failed');
                    queue.unshift(nextRound);
                    wrap.dataset.roundqueue = JSON.stringify(queue);
                    btn.disabled = false;
                    return;
                }

                const desktopRows = json.data.desktop_rows || '';
                const mobileCards = json.data.mobile_cards || '';

                if (desktopRows) {
                    const tbody = document.getElementById('sr-desktop-tbody');
                    const tmp = document.createElement('tbody');
                    tmp.innerHTML = desktopRows;
                    while (tmp.firstChild) tbody.appendChild(tmp.firstChild);
                }

                if (mobileCards) {
                    const mob = document.getElementById('sr-mobile-container');
                    const tmp2 = document.createElement('div');
                    tmp2.innerHTML = mobileCards;
                    while (tmp2.firstChild) mob.appendChild(tmp2.firstChild);
                }

                srBindAutoSave(document);

                if (!queue.length) {
                    btn.style.display = 'none';
                    setStatus('All rounds loaded.');
                } else {
                    setStatus('');
                    btn.disabled = false;
                }
            } catch (e) {
                setStatus('Load failed');
                try {
                    let q = JSON.parse(wrap.dataset.roundqueue || '[]');
                    q.unshift(nextRound);
                    wrap.dataset.roundqueue = JSON.stringify(q);
                } catch(_) {}
                btn.disabled = false;
            }
        });
    });
    </script>
    <?php

    return ob_get_clean();
}

/**
 * =============================
 * AJAX: Load next round
 * =============================
 */
add_action('wp_ajax_sr_load_round', 'sr_load_round_handler');
function sr_load_round_handler() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sr_load_round_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    global $wpdb;
    $user_id = get_current_user_id();

    $selected_matchtype_id = isset($_POST['competition']) ? intval($_POST['competition']) : 0;
    $round_number = isset($_POST['round']) ? intval($_POST['round']) : 0;

    if ($round_number <= 0) {
        wp_send_json_error(['message' => 'Invalid round'], 400);
    }

    $matches = sr_fetch_matches_for_round($wpdb, $user_id, $selected_matchtype_id, $round_number);

    if (empty($matches)) {
        wp_send_json_success([
            'desktop_rows' => '',
            'mobile_cards' => ''
        ]);
    }

    // Desktop <tr> rows
    ob_start();
    foreach ($matches as $match):
        ?>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;">
                <span class="match-date"><?php echo esc_html(date('d M Y', strtotime($match->play_date))); ?></span>
                <br class="desktop-only">
                <span class="match-time">
                    <?php 
                    $utc_date = new DateTime($match->play_date, new DateTimeZone('UTC'));
                    $utc_date->setTimezone(new DateTimeZone('Europe/London'));
                    echo esc_html($utc_date->format('H:i')); 
                    ?>
                </span>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($match->competition_name); ?></td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <img src="<?php echo esc_url($match->home_team_flag); ?>" alt="<?php echo esc_attr($match->home_team_name); ?>" style="width: 40px; height: auto; display: block; margin: 0 auto 5px;">
                <p style="margin: 0; font-weight: bold;"><?php echo esc_html($match->home_team_name); ?></p>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center;">
                    <input type="number"
                        name="predictions[<?php echo esc_attr($match->match_id); ?>][home_score]"
                        min="0"
                        value="<?php echo esc_attr($match->predicted_home_score); ?>"
                        class="prediction-input"
                        data-match-id="<?php echo esc_attr($match->match_id); ?>"
                        data-type="home_score"
                        placeholder="H"
                        style="width: 40px; height: 30px; text-align: center; border: 1px solid #ccc; border-radius: 5px;">
                    -
                    <input type="number"
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
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <img src="<?php echo esc_url($match->away_team_flag); ?>" alt="<?php echo esc_attr($match->away_team_name); ?>" style="width: 40px; height: auto; display: block; margin: 0 auto 5px;">
                <p style="margin: 0; font-weight: bold;"><?php echo esc_html($match->away_team_name); ?></p>
            </td>
        </tr>
        <?php
    endforeach;
    $desktop_rows = ob_get_clean();

    // Mobile cards
    ob_start();
    foreach ($matches as $match):
        ?>
        <div class="match-card">
            <div class="match-header">
                <span class="match-date">
                    <?php 
                    $utc_date = new DateTime($match->play_date, new DateTimeZone('UTC'));
                    $utc_date->setTimezone(new DateTimeZone('Europe/London'));
                    echo esc_html($utc_date->format('d M Y @ H:i')); 
                    ?>
                </span>
                <span class="competition-name"><?php echo esc_html($match->competition_name); ?></span>
            </div>
            <div class="match-content">
                <div class="team-container">
                    <img src="<?php echo esc_url($match->home_team_flag); ?>" alt="<?php echo esc_attr($match->home_team_name); ?>">
                    <p><?php echo esc_html($match->home_team_name); ?></p>
                </div>

                <div class="prediction-container">
                    <input type="number"
                        name="predictions[<?php echo esc_attr($match->match_id); ?>][home_score]"
                        min="0"
                        value="<?php echo esc_attr($match->predicted_home_score); ?>"
                        class="prediction-input"
                        data-match-id="<?php echo esc_attr($match->match_id); ?>"
                        data-type="home_score"
                        placeholder="H">
                    -
                    <input type="number"
                        name="predictions[<?php echo esc_attr($match->match_id); ?>][away_score]"
                        min="0"
                        value="<?php echo esc_attr($match->predicted_away_score); ?>"
                        class="prediction-input"
                        data-match-id="<?php echo esc_attr($match->match_id); ?>"
                        data-type="away_score"
                        placeholder="A">
                    <span class="save-indicator" style="display: none; margin-left: 5px; color: green;">✔</span>
                </div>

                <div class="team-container">
                    <img src="<?php echo esc_url($match->away_team_flag); ?>" alt="<?php echo esc_attr($match->away_team_name); ?>">
                    <p><?php echo esc_html($match->away_team_name); ?></p>
                </div>
            </div>
        </div>
        <?php
    endforeach;
    $mobile_cards = ob_get_clean();

    wp_send_json_success([
        'desktop_rows' => $desktop_rows,
        'mobile_cards' => $mobile_cards,
    ]);
}

/**
 * =============================
 * AJAX: Save prediction (unchanged)
 * =============================
 */
add_action('wp_ajax_save_prediction', 'save_prediction_handler');
function save_prediction_handler() {
    global $wpdb;

    $user_id  = get_current_user_id();
    $match_id = intval($_POST['match_id'] ?? 0);
    $type     = sanitize_text_field($_POST['type'] ?? '');
    $value    = intval($_POST['value'] ?? 0);

    $allowed = ['home_score', 'away_score'];
    if (!$match_id || !in_array($type, $allowed, true)) {
        wp_send_json_error(['message' => 'Invalid request'], 400);
    }

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM pool_wpkl_predictions WHERE user_id = %d AND match_id = %d",
        $user_id,
        $match_id
    ));

    if ($existing) {
        $wpdb->update(
            'pool_wpkl_predictions',
            [ $type => $value ],
            [ 'user_id' => $user_id, 'match_id' => $match_id ],
            [ '%d' ],
            [ '%d', '%d' ]
        );
    } else {
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