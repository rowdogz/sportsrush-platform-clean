<?php
/*
Plugin Name: Custom Predictions — v0 Layout (Cards) with Stadium Backgrounds, Manual Save & Auto-Save
Description: v0-styled predictions page: header, stats cards, competition filter (auto-submit), fixture cards with stadium name and faint background, manual Save/Update button, and AJAX auto-save.
Version: 3.4
Author: Bperrow
*/

add_shortcode('custom_football_predictions', 'custom_predictions_page');

function custom_predictions_page() {
    if (!is_user_logged_in()) {
        return '<div class="rounded-2xl border bg-card p-5">Please log in to make predictions.</div>';
    }

    global $wpdb;

    $user_id = get_current_user_id();

    // -------------------------------------------------
    // 1) COMPETITIONS (visible only)
    // -------------------------------------------------
    $competitions = $wpdb->get_results("
        SELECT id, name
        FROM pool_wpkl_matchtypes
        WHERE visibility = 1
        ORDER BY name ASC
    ");

    $selected_matchtype_id = isset($_GET['competition']) ? intval($_GET['competition']) : 0;

    // -------------------------------------------------
    // 2) MATCHES (upcoming only: kickoff >= NOW()) + user predictions
    //    JOIN stadiums to get name + photo
    // -------------------------------------------------
    $matches_sql = "
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
            p.away_score AS predicted_away_score,
            s.name  AS stadium_name,
            s.photo AS stadium_photo
        FROM pool_wpkl_matches AS m
        JOIN pool_wpkl_matchtypes AS mt ON m.matchtype_id = mt.id
        LEFT JOIN pool_wpkl_teams AS t1 ON m.home_team_id = t1.id
        LEFT JOIN pool_wpkl_teams AS t2 ON m.away_team_id = t2.id
        LEFT JOIN pool_wpkl_predictions AS p ON m.id = p.match_id AND p.user_id = %d
        LEFT JOIN pool_wpkl_stadiums AS s ON m.stadium_id = s.id
        WHERE mt.visibility = 1
          AND TIMESTAMP(m.play_date) >= NOW()
    ";
    $params = [ $user_id ];
    if ($selected_matchtype_id) {
        $matches_sql .= " AND m.matchtype_id = %d";
        $params[] = $selected_matchtype_id;
    }
    $matches_sql .= " ORDER BY m.play_date ASC";
    $matches = $wpdb->get_results($wpdb->prepare($matches_sql, $params));

    // -------------------------------------------------
    // 3) STATS for v0 header cards
    // -------------------------------------------------
    $availableFixtures = count($matches);

    $totalPredictions = 0;
    foreach ($matches as $m) {
        if ($m->predicted_home_score !== null || $m->predicted_away_score !== null) {
            $totalPredictions++;
        }
    }
    $completion = $availableFixtures > 0 ? round(($totalPredictions / $availableFixtures) * 100) : 0;

    // Deadline = time until earliest kickoff in current filtered set
    $deadline_label = '—';
    if ($availableFixtures > 0) {
        $first_ts = strtotime($matches[0]->play_date);
        $delta    = max(0, $first_ts - time());
        $days  = floor($delta / 86400);
        $hours = floor(($delta % 86400) / 3600);
        $mins  = floor(($delta % 3600) / 60);
        if ($days > 0) {
            $deadline_label = sprintf('%dd %dh', $days, $hours);
        } elseif ($hours > 0) {
            $deadline_label = sprintf('%dh %dm', $hours, $mins);
        } else {
            $deadline_label = sprintf('%dm', $mins);
        }
    }

    // Nonce + ajax
    $nonce   = wp_create_nonce('sr_save_prediction');
    $ajaxurl = admin_url('admin-ajax.php');

    // -------------------------------------------------
    // 4) RENDER — v0 Shell
    // -------------------------------------------------
    ob_start(); ?>
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2 gradient-text">Make Your Predictions</h1>
        <p class="text-muted-foreground text-lg">Predict exact scores for upcoming rugby league matches</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <!-- Available Fixtures -->
        <div class="rounded-2xl border bg-card hover-lift">
          <div class="p-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-medium">Available Fixtures</div>
            <svg class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 2v4m8-4v4M3 10h18M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/></svg>
          </div>
          <div class="px-4 pb-4">
            <div class="text-2xl font-bold text-primary"><?php echo esc_html($availableFixtures); ?></div>
          </div>
        </div>

        <!-- Your Predictions -->
        <div class="rounded-2xl border bg-card hover-lift">
          <div class="p-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-medium">Your Predictions</div>
            <svg class="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10"/></svg>
          </div>
          <div class="px-4 pb-4">
            <div class="text-2xl font-bold text-accent"><?php echo esc_html($totalPredictions); ?></div>
          </div>
        </div>

        <!-- Completion -->
        <div class="rounded-2xl border bg-card hover-lift">
          <div class="p-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-medium">Completion</div>
            <svg class="h-4 w-4" style="color: var(--color-success)" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 21h8M12 17v4M7 4h10M7 4a5 5 0 0 1-5 5v1a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V9a5 5 0 0 1-5-5"/></svg>
          </div>
          <div class="px-4 pb-4">
            <div class="text-2xl font-bold" style="color: var(--color-success)"><?php echo esc_html($completion); ?>%</div>
          </div>
        </div>

        <!-- Deadline -->
        <div class="rounded-2xl border bg-card hover-lift">
          <div class="p-4 pb-2 flex items-center justify-between">
            <div class="text-sm font-medium">Deadline</div>
            <svg class="h-4 w-4" style="color: var(--color-warning)" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div class="px-4 pb-4">
            <div class="text-2xl font-bold" style="color: var(--color-warning)"><?php echo esc_html($deadline_label); ?></div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex items-center justify-between mb-6">
        <form method="get" class="flex items-center gap-4">
          <div>
            <select name="competition" class="w-[220px] rounded-xl border bg-background px-3 py-2 text-sm" onchange="this.form.submit()">
              <option value="0">All Competitions</option>
              <?php foreach ($competitions as $c): ?>
                <option value="<?php echo esc_attr($c->id); ?>" <?php selected($selected_matchtype_id, $c->id); ?>>
                  <?php echo esc_html($c->name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>

        <span class="inline-flex items-center rounded-md border px-3 py-1.5 text-sm border-primary text-primary">
          <?php echo esc_html($availableFixtures); ?> fixtures available
        </span>
      </div>

      <!-- Fixtures Grid -->
      <?php if (!empty($matches)): ?>
        <form id="predictions-form" onsubmit="return false;">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($matches as $m): 
              $stadium_name  = !empty($m->stadium_name)  ? $m->stadium_name  : '';
              $stadium_photo = !empty($m->stadium_photo) ? $m->stadium_photo : '';
              $bg_style = $stadium_photo ? "background-image: url('" . esc_url($stadium_photo) . "'); background-size: cover; background-position: center; background-repeat: no-repeat;" : "";
              $has_prediction = ($m->predicted_home_score !== null || $m->predicted_away_score !== null);
              $btn_label = $has_prediction ? 'Update prediction' : 'Save prediction';
            ?>
              <article
                class="relative rounded-2xl border bg-card p-4 hover:shadow-sm transition
                       h-[320px] md:h-[340px] lg:h-[360px] flex flex-col overflow-hidden">
                <?php if ($bg_style): ?>
                  <div class="absolute inset-0" style="<?php echo $bg_style; ?>"></div>
                  <div class="absolute inset-0 bg-white/85"></div>
                <?php endif; ?>

                <!-- Content wrapper sits above background/overlay -->
                <div class="relative flex flex-col flex-1">
                  <!-- Top: Competition + Date/Time (larger/darker) -->
                  <div class="flex items-start justify-between gap-3">
                    <div class="text-sm text-gray-700">
                      <div class="font-semibold"><?php echo esc_html($m->competition_name); ?></div>
                      <div class="mt-0.5">
                        <?php echo esc_html(date_i18n('M j, Y', strtotime($m->play_date))); ?>
                        ·
                        <?php echo esc_html(date_i18n('H:i', strtotime($m->play_date))); ?>
                      </div>
                    </div>
                  </div>

                  <!-- Middle: Stadium (centered) -->
                  <?php if ($stadium_name): ?>
                    <div class="mt-2 text-center text-sm font-semibold text-gray-800">
                      <?php echo esc_html($stadium_name); ?>
                    </div>
                  <?php else: ?>
                    <div class="mt-2"></div>
                  <?php endif; ?>

                  <!-- Teams + Inputs (stacked: name, input, flag) -->
                  <div class="mt-4 grid grid-cols-3 items-center gap-3">
                    <!-- Home block -->
                    <div class="flex flex-col items-center">
                      <!-- Name -->
                      <span class="text-lg font-bold text-gray-900 text-center">
                        <?php echo esc_html($m->home_team_name); ?>
                      </span>
                      <!-- Prediction -->
                      <input
                        type="number"
                        min="0"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        name="predictions[<?php echo esc_attr($m->match_id); ?>][home_score]"
                        value="<?php echo esc_attr($m->predicted_home_score); ?>"
                        class="mt-2 w-16 h-10 text-center rounded-xl border bg-background"
                        data-match-id="<?php echo esc_attr($m->match_id); ?>"
                        data-type="home_score"
                      />
                      <!-- Tick -->
                      <span class="save-indicator hidden text-[11px] text-green-600 mt-1">✔</span>
                      <!-- Flag -->
                      <?php if (!empty($m->home_team_flag)): ?>
                        <img class="mt-2 h-8 w-8 rounded object-cover"
                             src="<?php echo esc_url($m->home_team_flag); ?>"
                             alt="<?php echo esc_attr($m->home_team_name); ?>">
                      <?php endif; ?>
                    </div>

                    <!-- Dash separator -->
                    <div class="flex items-center justify-center">
                      <span class="text-base font-semibold">-</span>
                    </div>

                    <!-- Away block -->
                    <div class="flex flex-col items-center">
                      <!-- Name -->
                      <span class="text-lg font-bold text-gray-900 text-center">
                        <?php echo esc_html($m->away_team_name); ?>
                      </span>
                      <!-- Prediction -->
                      <input
                        type="number"
                        min="0"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        name="predictions[<?php echo esc_attr($m->match_id); ?>][away_score]"
                        value="<?php echo esc_attr($m->predicted_away_score); ?>"
                        class="mt-2 w-16 h-10 text-center rounded-xl border bg-background"
                        data-match-id="<?php echo esc_attr($m->match_id); ?>"
                        data-type="away_score"
                      />
                      <!-- Tick -->
                      <span class="save-indicator hidden text-[11px] text-green-600 mt-1">✔</span>
                      <!-- Flag -->
                      <?php if (!empty($m->away_team_flag)): ?>
                        <img class="mt-2 h-8 w-8 rounded object-cover"
                             src="<?php echo esc_url($m->away_team_flag); ?>"
                             alt="<?php echo esc_attr($m->away_team_name); ?>">
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Button: bottom center -->
                  <div class="mt-4 flex justify-center">
                    <button
  type="button"
  class="sr-save-btn inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold bg-white text-gray-900 hover:bg-gray-100"
  data-match-id="<?php echo esc_attr($m->match_id); ?>"
>
  <?php echo esc_html($btn_label); ?>
</button>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </form>
      <?php else: ?>
        <!-- Empty State -->
        <div class="rounded-2xl border bg-card text-center py-12 border-dashed">
          <div class="max-w-md mx-auto">
            <div class="text-muted-foreground text-lg font-medium">No fixtures available</div>
            <div class="text-muted-foreground mt-1">
              Try selecting a different competition or check back later for new fixtures.
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- AJAX Auto-Save + Manual Save -->
    <script>
      (function(){
        const ajaxurl = <?php echo json_encode($ajaxurl); ?>;
        const nonce   = <?php echo json_encode($nonce); ?>;

        function showTick(inputEl){
          const block = inputEl.closest('.flex.flex-col.items-center');
          const tick  = block ? block.querySelector('.save-indicator') : null;
          if (tick){
            tick.classList.remove('hidden');
            setTimeout(()=>tick.classList.add('hidden'), 1200);
          }
        }

        // Auto-save on input (unchanged)
        document.addEventListener('input', function(e){
          const el = e.target;
          if (!el.matches('input[data-match-id]')) return;
          const matchId = el.dataset.matchId;
          const type    = el.dataset.type;
          const value   = el.value;

          const formData = new FormData();
          formData.append('action', 'save_prediction');
          formData.append('match_id', matchId);
          formData.append('type', type);
          formData.append('value', value);
          formData.append('_wpnonce', nonce);

          fetch(ajaxurl, { method:'POST', body: formData })
            .then(r => r.json())
            .then(res => { if (res && res.success){ showTick(el); } })
            .catch(()=>{ /* ignore */ });
        }, { passive: true });

        // Manual save button — saves BOTH home and away in sequence using existing endpoint
        document.addEventListener('click', async function(e){
          const btn = e.target.closest('.sr-save-btn');
          if (!btn) return;

          const matchId = btn.dataset.matchId;
          const article = btn.closest('article');
          const home = article.querySelector('input[name="predictions[' + matchId + '][home_score]"]');
          const away = article.querySelector('input[name="predictions[' + matchId + '][away_score]"]');

          // Basic guard
          if (!home || !away) return;

          const original = btn.textContent;
          btn.disabled = true;
          btn.textContent = 'Saving...';

          try {
            // Save home
            const fd1 = new FormData();
            fd1.append('action', 'save_prediction');
            fd1.append('match_id', matchId);
            fd1.append('type', 'home_score');
            fd1.append('value', home.value);
            fd1.append('_wpnonce', nonce);
            await fetch(ajaxurl, { method:'POST', body: fd1 }).then(r=>r.json());

            // Save away
            const fd2 = new FormData();
            fd2.append('action', 'save_prediction');
            fd2.append('match_id', matchId);
            fd2.append('type', 'away_score');
            fd2.append('value', away.value);
            fd2.append('_wpnonce', nonce);
            await fetch(ajaxurl, { method:'POST', body: fd2 }).then(r=>r.json());

            btn.textContent = 'Saved!';
            setTimeout(()=>{ btn.textContent = original; btn.disabled = false; }, 1200);
          } catch(err){
            btn.textContent = 'Error — retry';
            setTimeout(()=>{ btn.textContent = original; btn.disabled = false; }, 1500);
          }
        });
      })();
    </script>
    <?php
    return ob_get_clean();
}

// -------------------------------------------------
// AJAX save handler (with nonce) — unchanged
// -------------------------------------------------
add_action('wp_ajax_save_prediction', 'save_prediction_handler');
function save_prediction_handler() {
    check_ajax_referer('sr_save_prediction');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authorised'], 403);
    }

    global $wpdb;
    $user_id  = get_current_user_id();
    $match_id = isset($_POST['match_id']) ? intval($_POST['match_id']) : 0;
    $type     = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $value    = isset($_POST['value']) ? intval($_POST['value']) : null;

    if (!$match_id || !in_array($type, ['home_score','away_score'], true)) {
        wp_send_json_error(['message' => 'Bad request'], 400);
    }

    // Ensure match is still open (>= NOW())
    $is_open = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM pool_wpkl_matches
        WHERE id = %d AND TIMESTAMP(play_date) >= NOW()
    ", $match_id));
    if (!$is_open) {
        wp_send_json_error(['message' => 'Match closed'], 400);
    }

    // Upsert prediction (single field)
    $exists = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM pool_wpkl_predictions WHERE user_id = %d AND match_id = %d
    ", $user_id, $match_id));

    if ($exists) {
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