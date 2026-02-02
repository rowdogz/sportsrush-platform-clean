<?php
/*
Plugin Name: Football Pool — Rules Page (v0 Layout)
Description: Renders the rules page in a v0-styled layout while using Football Pool plugin data (shortcodes/options).
Version: 1.1
Author: Your Name
*/

add_shortcode('football_pool_rules_v0', 'sr_football_pool_rules_v0');

function sr_football_pool_rules_v0() {
    // Helper: safely run a shortcode and extract an integer
    $sc_int = function($shortcode, $fallback = 0) {
        $out = do_shortcode($shortcode);
        // Strip anything non-digit (keeps negatives just in case)
        if (is_string($out)) {
            if (preg_match('/-?\d+/', $out, $m)) return intval($m[0]);
        } elseif (is_numeric($out)) {
            return intval($out);
        }
        return intval($fallback);
    };

    // Pull points from Football Pool shortcodes
    $toto_pts = $sc_int('[fp-totopoints]', 20);
    $full_pts = $sc_int('[fp-fullpoints]', 50);
    $goal_pts = $sc_int('[fp-goalpoints]', 10); // correct score bonus
    $diff_pts = $sc_int('[fp-diffpoints]', 20); // score difference bonus

    // Try to detect the prediction lock (minutes before kickoff) from plugin options (fallback 30)
    $lock_minutes = get_option('footballpool_prediction_before');
    if (!is_numeric($lock_minutes)) {
        // $lock_minutes = get_option('fp_prediction_before');
        // $lock_minutes = get_option('pool_prediction_lock');
    }
    $lock_minutes = is_numeric($lock_minutes) ? intval($lock_minutes) : 30;

    ob_start();

    // Scoped overrides to force white cards/backgrounds only inside this block
    ?>
    <style>
      .sr-rules {
        /* If your theme maps bg classes via CSS variables (e.g. hsl(var(--card))) */
        --card: 0 0% 100%;
        --background: 0 0% 100%;
        --muted: 0 0% 100%;
      }
      .sr-rules .bg-card,
      .sr-rules .bg-background,
      .sr-rules .bg-muted,
      .sr-rules .bg-muted\/50 {
        background-color: #fff !important;
      }
      .sr-rules .border {
        border-color: rgba(0,0,0,0.08) !important;
      }
    </style>
    <?php
    ?>
    <div class="sr-rules container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">How It Works</h1>
        <p class="text-muted-foreground text-lg">Everything you need to know about making predictions and earning points.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- How to play -->
          <div class="rounded-2xl border bg-white">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">How to Play</div>
            </div>
            <div class="p-4 space-y-3">
              <p class="text-sm leading-relaxed">
                Predict the <strong>final score</strong> of each match in your chosen competition.
                You can submit or change your prediction up to
                <strong><?php echo esc_html($lock_minutes); ?> minutes</strong> before kickoff.
              </p>
              <ul class="list-disc pl-5 text-sm space-y-1">
                <li>Go to the <strong>Predictions</strong> page, choose a competition, and enter your scores.</li>
                <li>Edits are auto-saved, and you can also manually save/update your prediction on each match card.</li>
                <li>Once a match is within the lock window, it becomes unavailable for editing.</li>
              </ul>
            </div>
          </div>

          <!-- How you score points -->
          <div class="rounded-2xl border bg-white">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">How You Score Points</div>
              <div class="text-xs text-muted-foreground mt-1">Points are configured in the Football Pool plugin and reflected here automatically.</div>
            </div>
            <div class="p-4">
              <!-- Points grid -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-xl border p-4 bg-white">
                  <div class="text-sm font-medium">Full Score (Exact)</div>
                  <div class="text-2xl font-bold mt-1">
                    <?php echo esc_html($full_pts); ?><span class="text-sm font-medium ml-1">pts</span>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">
                    Exact match of both teams’ scores.
                  </div>
                </div>

                <div class="rounded-xl border p-4 bg-white">
                  <div class="text-sm font-medium">Toto (Correct Outcome)</div>
                  <div class="text-2xl font-bold mt-1">
                    <?php echo esc_html($toto_pts); ?><span class="text-sm font-medium ml-1">pts</span>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">
                    Correctly predict the winner or a draw (outcome only).
                  </div>
                </div>

                <div class="rounded-xl border p-4 bg-white">
                  <div class="text-sm font-medium">Correct Score Bonus</div>
                  <div class="text-2xl font-bold mt-1">
                    <?php echo esc_html($goal_pts); ?><span class="text-sm font-medium ml-1">pts</span>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">
                    Bonus for getting a team’s exact score right.
                  </div>
                </div>

                <div class="rounded-xl border p-4 bg-white">
                  <div class="text-sm font-medium">Goal Difference Bonus</div>
                  <div class="text-2xl font-bold mt-1">
                    <?php echo esc_html($diff_pts); ?><span class="text-sm font-medium ml-1">pts</span>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">
                    Bonus for matching the goal/points difference.
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div class="mt-4 rounded-xl border p-4 bg-white">
                <div class="text-sm">
                  <strong>Important:</strong> If you earn the <strong>Full Score</strong>, you do <em>not</em> also receive Toto or Goal Difference points for that match.
                </div>
              </div>
            </div>
          </div>

          <!-- Toto explainer + examples -->
          <div class="rounded-2xl border bg-white">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">Understanding the “Toto” Scoring Method</div>
            </div>
            <div class="p-4 space-y-4">
              <p class="text-sm leading-relaxed">
                “Toto” rewards you for predicting the <strong>match outcome</strong> (home win, away win, or draw).
                You don’t need the exact score — just the correct result.
              </p>

              <div class="rounded-xl border p-4 bg-white">
                <div class="text-sm font-medium">How it works</div>
                <ul class="list-disc pl-5 text-sm mt-2 space-y-1">
                  <li>Get <strong><?php echo esc_html($toto_pts); ?> points</strong> for the right outcome.</li>
                  <li>No extra Toto or Goal Difference points if you already have the Full Score.</li>
                </ul>
              </div>

              <!-- Examples table -->
              <div class="rounded-xl border overflow-x-auto bg-white">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-muted-foreground">
                      <th class="py-2 px-3">Match Result</th>
                      <th class="py-2 px-3">Your Prediction</th>
                      <th class="py-2 px-3">Points</th>
                      <th class="py-2 px-3">Why?</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="border-t">
                      <td class="py-2 px-3">🏆 Team A wins 3–1</td>
                      <td class="py-2 px-3">✅ Team A wins 2–0</td>
                      <td class="py-2 px-3"><?php echo esc_html($toto_pts); ?></td>
                      <td class="py-2 px-3">Outcome correct (Team A wins).</td>
                    </tr>
                    <tr class="border-t">
                      <td class="py-2 px-3">🤝 Draw 2–2</td>
                      <td class="py-2 px-3">✅ Draw 1–1</td>
                      <td class="py-2 px-3"><?php echo esc_html($toto_pts); ?></td>
                      <td class="py-2 px-3">Outcome correct (draw).</td>
                    </tr>
                    <tr class="border-t">
                      <td class="py-2 px-3">❌ Team A wins 2–1</td>
                      <td class="py-2 px-3">❌ Team B wins 0–1</td>
                      <td class="py-2 px-3">0</td>
                      <td class="py-2 px-3">Wrong outcome.</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Tiebreakers -->
              <div class="rounded-xl border p-4 bg-white">
                <div class="text-sm font-medium mb-1">Tiebreaker Rules</div>
                <ol class="list-decimal pl-5 text-sm space-y-1">
                  <li>Most <strong>Full Scores</strong> (exact scores).</li>
                  <li>Most <strong>Toto</strong> (correct outcomes).</li>
                </ol>
                <div class="text-xs text-muted-foreground mt-2">
                  If players are still tied after these, the plugin’s ranking logic may apply further ties or equal ranks.
                </div>
              </div>
            </div>
          </div>

          <!-- Helpful links -->
          <div class="rounded-2xl border bg-white">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">Where to next?</div>
            </div>
            <div class="p-4">
              <ul class="list-disc pl-5 text-sm space-y-1">
                <li>Check your current position on the <a href="/rankings/" class="underline">Rankings</a> page.</li>
                <li>Make or update predictions on the <a href="/predictions/" class="underline">Predictions</a> page.</li>
                <li>Review final scores and your points on the <a href="/results/" class="underline">Results</a> page.</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Side info -->
        <div class="space-y-6">
          <!-- Quick facts card -->
          <div class="rounded-2xl border bg-white">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">Quick Facts</div>
            </div>
            <div class="p-4 space-y-3 text-sm">
              <div class="flex items-center justify-between">
                <span>Prediction lock window</span>
                <span class="font-semibold"><?php echo esc_html($lock_minutes); ?> mins</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Full Score</span>
                <span class="font-semibold"><?php echo esc_html($full_pts); ?> pts</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Toto (Result)</span>
                <span class="font-semibold"><?php echo esc_html($toto_pts); ?> pts</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Correct Score bonus</span>
                <span class="font-semibold"><?php echo esc_html($goal_pts); ?> pts</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Goal difference bonus</span>
                <span class="font-semibold"><?php echo esc_html($diff_pts); ?> pts</span>
              </div>
            </div>
          </div>

          <!-- FAQ (optional) -->
          <div class="rounded-2xl border bg-white">
            <div class="p-4 border-b">
              <div class="text-lg font-semibold">FAQ</div>
            </div>
            <div class="p-4 space-y-3 text-sm">
              <details class="rounded-lg border p-3 bg-white">
                <summary class="cursor-pointer font-medium">Can I change my prediction?</summary>
                <div class="mt-2 text-muted-foreground">
                  Yes—until <?php echo esc_html($lock_minutes); ?> minutes before kickoff.
                </div>
              </details>
              <details class="rounded-lg border p-3 bg-white">
                <summary class="cursor-pointer font-medium">Do I always get all bonuses?</summary>
                <div class="mt-2 text-muted-foreground">
                  No. If you hit the <strong>Full Score</strong>, you don’t also get Toto or Goal Difference for that match.
                </div>
              </details>
              <details class="rounded-lg border p-3 bg-white">
                <summary class="cursor-pointer font-medium">Where do I see my rank?</summary>
                <div class="mt-2 text-muted-foreground">
                  Visit the <a href="/rankings/" class="underline">Rankings</a> page and choose the competition.
                </div>
              </details>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}