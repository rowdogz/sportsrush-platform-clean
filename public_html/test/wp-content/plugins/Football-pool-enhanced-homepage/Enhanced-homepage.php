<?php
/*
Plugin Name: Football Pool Sponsor Manager + v0 Homepage
Description: Admin sponsor manager + v0-styled homepage powered by Football Pool data.
Version: 2.0
Author: Your Name
*/

/* =========================================================
   ADMIN: SPONSOR MANAGER (unchanged)
   ========================================================= */

function fp_sponsors_add_admin_menu() {
    add_menu_page(
        'Sponsor Settings',
        'Sponsors',
        'edit_sponsors',
        'fp_sponsor_settings',
        'fp_sponsors_settings_page',
        'dashicons-admin-generic',
        25
    );
}
add_action('admin_menu', 'fp_sponsors_add_admin_menu');

function fp_create_prediction_user_role() {
    add_role('prediction_user', 'Prediction User', [
        'read' => true,
        'edit_sponsors' => true,
    ]);
}
register_activation_hook(__FILE__, 'fp_create_prediction_user_role');

function fp_remove_prediction_user_role() {
    remove_role('prediction_user');
}
register_deactivation_hook(__FILE__, 'fp_remove_prediction_user_role');

function fp_add_sponsor_capability() {
    if ($role = get_role('prediction_user')) {
        $role->add_cap('edit_sponsors');
    }
    if ($admin = get_role('administrator')) {
        $admin->add_cap('edit_sponsors');
    }
}
add_action('init', 'fp_add_sponsor_capability');

function fp_add_sponsors_menu_link($wp_admin_bar) {
    if (current_user_can('edit_sponsors')) {
        $wp_admin_bar->add_node([
            'id'    => 'fp_manage_sponsors',
            'title' => 'Manage Sponsors',
            'href'  => admin_url('admin.php?page=fp_sponsor_settings'),
            'meta'  => ['class' => 'fp-admin-bar-sponsors']
        ]);
    }
}
add_action('admin_bar_menu', 'fp_add_sponsors_menu_link', 100);

function fp_sponsors_register_settings() {
    register_setting('fp_sponsor_settings_group', 'fp_sponsors');
    add_filter('whitelist_options', function ($options) {
        $options['fp_sponsor_settings_group'] = ['fp_sponsors'];
        return $options;
    });
}
add_action('admin_init', 'fp_sponsors_register_settings');

function fp_sponsors_settings_page() {
    $sponsors = get_option('fp_sponsors', []);
    if (!is_array($sponsors)) $sponsors = [];
    ?>
    <div class="wrap">
        <h1>Sponsor Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('fp_sponsor_settings_group'); ?>
            <?php do_settings_sections('fp_sponsor_settings_group'); ?>
            <table class="form-table" id="sponsor-table">
                <thead>
                    <tr>
                        <th>Image URL</th>
                        <th>Upload</th>
                        <th>Link URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sponsor-rows">
                    <?php foreach ($sponsors as $index => $sponsor) : ?>
                        <tr>
                            <td><input type="text" name="fp_sponsors[<?php echo $index; ?>][image]" value="<?php echo esc_attr($sponsor['image'] ?? ''); ?>" class="regular-text sponsor-image"></td>
                            <td><button type="button" class="upload-button button">Upload</button></td>
                            <td><input type="text" name="fp_sponsors[<?php echo $index; ?>][link]" value="<?php echo esc_attr($sponsor['link'] ?? ''); ?>" class="regular-text"></td>
                            <td><button type="button" class="button remove-sponsor">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" id="add-sponsor" class="button button-primary">Add Sponsor</button></p>
            <?php submit_button(); ?>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const sponsorRows = document.getElementById('sponsor-rows');

        document.getElementById('add-sponsor').addEventListener('click', function () {
            const rowCount = sponsorRows.children.length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" name="fp_sponsors[${rowCount}][image]" class="regular-text sponsor-image"></td>
                <td><button type="button" class="upload-button button">Upload</button></td>
                <td><input type="text" name="fp_sponsors[${rowCount}][link]" class="regular-text"></td>
                <td><button type="button" class="button remove-sponsor">Remove</button></td>
            `;
            sponsorRows.appendChild(tr);
            bindUpload(tr.querySelector('.upload-button'));
        });

        sponsorRows.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-sponsor')) {
                e.target.closest('tr').remove();
            }
        });

        function bindUpload(button) {
            button.addEventListener('click', function (e) {
                let frame;
                const inputField = e.target.closest('tr').querySelector('.sponsor-image');
                if (frame) { frame.open(); return; }
                frame = wp.media({ title: 'Select or Upload Image', button: { text: 'Use this image' }, multiple: false });
                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    inputField.value = attachment.url;
                });
                frame.open();
            });
        }
        document.querySelectorAll('.upload-button').forEach(bindUpload);
    });
    </script>
    <?php
}

/* =========================================================
   FRONTEND: v0 HOMEPAGE SHORTCODE
   ========================================================= */

add_shortcode('sportsrush_home_v0', 'sr_render_home_v0');

function sr_render_home_v0() {
    global $wpdb;

    // --- Sponsors (used later) ---
    $sponsors = get_option('fp_sponsors', []);
    if (!is_array($sponsors)) $sponsors = [];

    // --- Competitions (visible only) ---
    $competitions = $wpdb->get_results("
        SELECT id, name
        FROM pool_wpkl_matchtypes
        WHERE visibility = 1
        ORDER BY name ASC
    ");

    // --- Site stats ---
    $active_predictors = intval($wpdb->get_var("
        SELECT COUNT(DISTINCT p.user_id)
        FROM pool_wpkl_predictions p
    "));

    $predictions_made = intval($wpdb->get_var("
        SELECT COUNT(*)
        FROM pool_wpkl_predictions
    "));

    $live_competitions = intval($wpdb->get_var("
        SELECT COUNT(DISTINCT m.matchtype_id)
        FROM pool_wpkl_matches m
        JOIN pool_wpkl_matchtypes mt ON mt.id = m.matchtype_id
        WHERE mt.visibility = 1
          AND TIMESTAMP(m.play_date) >= NOW()
    "));

    // --- Per-competition stats for cards ---
    $comp_cards = [];
    foreach ($competitions as $c) {
        $cid = intval($c->id);

        // Participants in this competition (ever)
        $participants = intval($wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.user_id)
            FROM pool_wpkl_predictions p
            JOIN pool_wpkl_matches m ON m.id = p.match_id
            WHERE m.matchtype_id = %d
        ", $cid)));

        // Next round (nearest upcoming week number)
        $next_week = $wpdb->get_var($wpdb->prepare("
            SELECT WEEK(m.play_date, 1)
            FROM pool_wpkl_matches m
            WHERE m.matchtype_id = %d
              AND TIMESTAMP(m.play_date) >= NOW()
            ORDER BY m.play_date ASC
            LIMIT 1
        ", $cid));
        $next_round_label = $next_week ? ('Round ' . intval($next_week)) : '—';

        // Status
        $is_live = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM pool_wpkl_matches
            WHERE matchtype_id = %d
              AND TIMESTAMP(play_date) >= NOW()
            LIMIT 1
        ", $cid));
        $status = $is_live ? 'Live' : 'Completed';

        $comp_cards[] = [
            'id'           => $cid,
            'name'         => $c->name,
            'participants' => $participants,
            'next_round'   => $next_round_label,
            'status'       => $status,
        ];
    }

    ob_start(); ?>
    <div class="flex flex-col min-h-screen">
      <!-- Hero -->
      <section class="relative overflow-hidden bg-gradient-animated py-20 md:py-32">
        <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:50px_50px]"></div>
        <div class="container relative z-10 mx-auto px-4">
          <div class="flex flex-col items-center text-center space-y-8">
            <span class="inline-flex px-4 py-2 text-sm font-medium rounded-md bg-[var(--color-rugby-purple)]/20 text-[var(--color-rugby-purple)] border border-[var(--color-rugby-purple)]/30">
              UK's #1 Rugby League Predictions Platform
            </span>

            <h1 class="text-4xl font-bold tracking-tight text-balance sm:text-6xl md:text-7xl">
              <span class="gradient-text-fire">SPORTSRUSH</span>
            </h1>

            <p class="max-w-2xl text-xl text-muted-foreground text-balance leading-relaxed">
              Predict exact scores, climb the leaderboards, and win prizes in the most competitive rugby league prediction community.
            </p>

            <div class="flex flex-col sm:flex-row gap-4">
              <a href="/predictions" class="inline-flex items-center justify-center rounded-md px-6 py-3 text-base font-medium hover-lift-red bg-[var(--color-rugby-red)] hover:bg-[var(--color-rugby-red)]/90 text-white">
                <svg class="mr-2 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 17h5l-1.405-4.215A2 2 0 0 0 16.683 11H7.317a2 2 0 0 0-1.912 1.785L4 17h5m6 0v2a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-2m8 0H8"/></svg>
                Start Predicting
              </a>
              <a href="/rules" class="inline-flex items-center justify-center rounded-md px-6 py-3 text-base font-medium hover-lift-blue border border-[var(--color-rugby-blue)] text-[var(--color-rugby-blue)] hover:bg-[var(--color-rugby-blue)]/10 bg-transparent">
                View Rules
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Stats -->
      <section class="py-16 bg-muted/30">
        <div class="container mx-auto px-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="rounded-2xl border text-center hover-lift-blue glass-blue">
              <div class="p-6">
                <svg class="h-12 w-12 mx-auto text-[var(--color-rugby-blue)] mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <div class="text-3xl font-bold gradient-text-ocean"><?php echo esc_html(number_format_i18n($active_predictors)); ?></div>
                <div class="text-sm text-muted-foreground mt-1">Active Predictors</div>
              </div>
            </div>

            <div class="rounded-2xl border text-center hover-lift-green glass-green">
              <div class="p-6">
                <svg class="h-12 w-12 mx-auto text-[var(--color-rugby-green)] mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18"/><path d="M19 19V8a2 2 0 0 0-2-2H7"/><path d="M7 13h6"/><path d="M7 9h10"/></svg>
                <div class="text-3xl font-bold gradient-text-nature"><?php echo esc_html(number_format_i18n($predictions_made)); ?></div>
                <div class="text-sm text-muted-foreground mt-1">Predictions Made</div>
              </div>
            </div>

            <div class="rounded-2xl border text-center hover-lift-red glass-red">
              <div class="p-6">
                <svg class="h-12 w-12 mx-auto text-[var(--color-rugby-red)] mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m12 2 4 7 8 1-6 5 2 8-8-4-8 4 2-8-6-5 8-1z"/></svg>
                <div class="text-3xl font-bold gradient-text-fire"><?php echo esc_html(number_format_i18n($live_competitions)); ?></div>
                <div class="text-sm text-muted-foreground mt-1">Live Competitions</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Active Competitions -->
      <section class="py-16">
        <div class="container mx-auto px-4">
          <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4 gradient-text">Active Competitions</h2>
            <p class="text-muted-foreground text-lg">Join the action in these live competitions</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($comp_cards as $i => $card): 
              // color tint rotation
              $tints = [
                ['card' => 'red',   'class' => 'hover-lift-red border-[var(--color-rugby-red)]/20',   'badge' => 'bg-[var(--color-rugby-red)] hover:bg-[var(--color-rugby-red)]/90', 'badgeAlt' => 'bg-[var(--color-rugby-blue)]/20 text-[var(--color-rugby-blue)]'],
                ['card' => 'blue',  'class' => 'hover-lift-blue border-[var(--color-rugby-blue)]/20', 'badge' => 'bg-[var(--color-rugby-blue)]', 'badgeAlt' => 'bg-[var(--color-rugby-blue)]/20 text-[var(--color-rugby-blue)]'],
                ['card' => 'green', 'class' => 'hover-lift-green border-[var(--color-rugby-green)]/20','badge' => 'bg-[var(--color-rugby-green)]', 'badgeAlt' => 'bg-[var(--color-rugby-green)]/20 text-[var(--color-rugby-green)]'],
              ];
              $t = $tints[$i % 3];
              $is_live = ($card['status'] === 'Live');
            ?>
            <div class="rounded-2xl border <?php echo esc_attr($t['class']); ?>">
              <div class="p-5 border-b">
                <div class="flex items-center justify-between">
                  <div class="text-lg font-semibold"><?php echo esc_html($card['name']); ?></div>
                  <?php if ($is_live): ?>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-md text-white <?php echo esc_attr($t['badge']); ?>">Live</span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-md <?php echo esc_attr($t['badgeAlt']); ?>">Completed</span>
                  <?php endif; ?>
                </div>
                <div class="text-xs text-muted-foreground mt-1">Competition</div>
              </div>
              <div class="p-5 space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-muted-foreground">Participants</span>
                  <span class="font-medium"><?php echo esc_html(number_format_i18n($card['participants'])); ?></span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-muted-foreground">Next Round</span>
                  <span class="font-medium"><?php echo esc_html($card['next_round']); ?></span>
                </div>
                <div class="pt-2">
                  <a href="/predictions/?competition=<?php echo esc_attr($card['id']); ?>" class="inline-flex items-center rounded-md px-3 py-2 text-sm border hover:bg-muted/50">
                    Make Predictions
                  </a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <!-- How It Works -->
      <section class="py-16 bg-muted/30">
        <div class="container mx-auto px-4">
          <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4 gradient-text-ocean">How It Works</h2>
            <p class="text-muted-foreground text-lg">Simple steps to start winning</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center space-y-4">
              <div class="w-16 h-16 bg-[var(--color-rugby-purple)]/20 rounded-full flex items-center justify-center mx-auto hover-lift">
                <svg class="h-8 w-8 text-[var(--color-rugby-purple)]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 7V3m8 4V3M3 11h18M5 19h14a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z"/></svg>
              </div>
              <h3 class="text-xl font-semibold">1. Choose Fixtures</h3>
              <p class="text-muted-foreground">Select upcoming rugby league matches from various competitions</p>
            </div>

            <div class="text-center space-y-4">
              <div class="w-16 h-16 bg-[var(--color-rugby-blue)]/20 rounded-full flex items-center justify-center mx-auto hover-lift">
                <svg class="h-8 w-8 text-[var(--color-rugby-blue)]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m12 19 9 2-9-18-9 18 9-2Z"/></svg>
              </div>
              <h3 class="text-xl font-semibold">2. Predict Scores</h3>
              <p class="text-muted-foreground">Enter your exact score predictions for each team</p>
            </div>

            <div class="text-center space-y-4">
              <div class="w-16 h-16 bg-[var(--color-rugby-green)]/20 rounded-full flex items-center justify-center mx-auto hover-lift">
                <svg class="h-8 w-8 text-[var(--color-rugby-green)]" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m3 3 7.5 7.5M21 21l-6-6m-3-1a5 5 0 1 0-7 7 5 5 0 0 0 7-7Z"/></svg>
              </div>
              <h3 class="text-xl font-semibold">3. Climb Rankings</h3>
              <p class="text-muted-foreground">Earn points for correct predictions and compete for prizes</p>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="py-16">
        <div class="container mx-auto px-4">
          <div class="rounded-2xl border text-center p-8 hover-lift border-[var(--color-rugby-purple)]/30 glass">
            <div class="mb-4">
              <h3 class="text-2xl mb-2 gradient-text">Ready to Start Predicting?</h3>
              <div class="text-lg text-muted-foreground">Join thousands of rugby league fans competing for prizes</div>
            </div>
            <a href="/predictions" class="inline-flex items-center justify-center rounded-md px-6 py-3 text-base font-medium hover-lift-green bg-[var(--color-rugby-green)] hover:bg-[var(--color-rugby-green)]/90 text-white">
              <svg class="mr-2 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m12 2 4 7 8 1-6 5 2 8-8-4-8 4 2-8-6-5 8-1z"/></svg>
              Join Now — It’s Free!
            </a>
          </div>
        </div>
      </section>

      <!-- Sponsors carousel -->
      <?php if (!empty($sponsors)): ?>
      <section class="py-12">
        <div class="container mx-auto px-4">
          <div class="text-center mb-6">
            <h3 class="text-2xl font-semibold">Our Partners</h3>
            <p class="text-muted-foreground">Thanks to the brands supporting the community</p>
          </div>
          <div class="relative max-w-full mx-auto">
            <button class="slider-nav left" aria-label="Previous">&lt;</button>
            <div class="enhanced-homepage-slider">
              <?php foreach ($sponsors as $s): 
                $img = esc_url($s['image'] ?? '');
                $lnk = esc_url($s['link'] ?? '#');
                if (!$img) continue;
              ?>
                <div class="sponsor-slide">
                  <a href="<?php echo $lnk; ?>" target="_blank" rel="noopener">
                    <img src="<?php echo $img; ?>" alt="Sponsor">
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
            <button class="slider-nav right" aria-label="Next">&gt;</button>
          </div>
        </div>
      </section>
      <?php endif; ?>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const slider = document.querySelector('.enhanced-homepage-slider');
        if (!slider) return;
        const leftButton = document.querySelector('.slider-nav.left');
        const rightButton = document.querySelector('.slider-nav.right');
        if (leftButton) leftButton.addEventListener('click', () => slider.scrollBy({ left: -250, behavior: 'smooth' }));
        if (rightButton) rightButton.addEventListener('click', () => slider.scrollBy({ left:  250, behavior: 'smooth' }));
      });
    </script>

    <style>
  /* === Brand palette === */
  :root{
    --color-rugby-red:#EF4444;
    --color-rugby-blue:#3B82F6;
    --color-rugby-green:#10B981;
    --color-rugby-purple:#8B5CF6;
    --color-rugby-orange:#F59E0B;
  }

  /* Make cards white, borders subtle */
  .bg-card{ background:#fff; }
  .bg-background{ background:#fff; }
  .bg-muted\/30{ background: rgba(0,0,0,.03); } /* fallback if utility not present */
  .text-muted-foreground{ color: rgba(0,0,0,.55); }

  /* === Animated hero background === */
  .bg-gradient-animated{
    background: linear-gradient(120deg,
      rgba(139,92,246,.25),
      rgba(59,130,246,.25),
      rgba(16,185,129,.25),
      rgba(239,68,68,.25)
    );
    background-size: 200% 200%;
    animation: srGradient 12s ease infinite;
  }
  @keyframes srGradient{
    0%{ background-position:0% 50%; }
    50%{ background-position:100% 50%; }
    100%{ background-position:0% 50%; }
  }

  /* Soft grid overlay used in hero */
  .bg-grid-white\/\[0\.02\]{ 
    background-image: linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: var(--bg-grid-size,50px) var(--bg-grid-size,50px);
  }

  /* === Gradient text accents === */
  .gradient-text{
    background: linear-gradient(90deg, var(--color-rugby-purple), var(--color-rugby-blue));
    -webkit-background-clip:text; background-clip:text; color: transparent;
  }
  .gradient-text-fire{
    background: linear-gradient(90deg, var(--color-rugby-red), var(--color-rugby-orange));
    -webkit-background-clip:text; background-clip:text; color: transparent;
  }
  .gradient-text-ocean{
    background: linear-gradient(90deg, var(--color-rugby-blue), var(--color-rugby-green));
    -webkit-background-clip:text; background-clip:text; color: transparent;
  }
  .gradient-text-nature{
    background: linear-gradient(90deg, #16a34a, #22c55e);
    -webkit-background-clip:text; background-clip:text; color: transparent;
  }

  /* === “Glass” effect cards === */
  .glass, .glass-blue, .glass-green, .glass-red{
    backdrop-filter: blur(6px);
  }
  .glass{ background: linear-gradient(180deg, rgba(255,255,255,.8), rgba(255,255,255,.65)); }
  .glass-blue{ background: linear-gradient(180deg, rgba(59,130,246,.10), rgba(59,130,246,.06)); }
  .glass-green{ background: linear-gradient(180deg, rgba(16,185,129,.10), rgba(16,185,129,.06)); }
  .glass-red{ background: linear-gradient(180deg, rgba(239,68,68,.10), rgba(239,68,68,.06)); }

  /* === Lift-on-hover micro-interaction === */
  .hover-lift-red:hover   { transform: translateY(-3px); box-shadow: 0 10px 22px rgba(239,68,68,.18); }
  .hover-lift-blue:hover  { transform: translateY(-3px); box-shadow: 0 10px 22px rgba(59,130,246,.18); }
  .hover-lift-green:hover { transform: translateY(-3px); box-shadow: 0 10px 22px rgba(16,185,129,.18); }
  .hover-lift:hover       { transform: translateY(-3px); box-shadow: 0 10px 22px rgba(0,0,0,.10); }
  .hover-lift, .hover-lift-red, .hover-lift-blue, .hover-lift-green{
    transition: transform .25s ease, box-shadow .25s ease;
  }

  /* === Badges / buttons helpers (used in markup) === */
  .border-\[var\(--color-rugby-red\)\]\/20{ border-color: color-mix(in srgb, var(--color-rugby-red) 20%, transparent); }
  .border-\[var\(--color-rugby-blue\)\]\/20{ border-color: color-mix(in srgb, var(--color-rugby-blue) 20%, transparent); }
  .border-\[var\(--color-rugby-green\)\]\/20{ border-color: color-mix(in srgb, var(--color-rugby-green) 20%, transparent); }
  .border-\[var\(--color-rugby-purple\)\]\/30{ border-color: color-mix(in srgb, var(--color-rugby-purple) 30%, transparent); }

  /* === CTA button hovers === */
  .bg-\[var\(--color-rugby-red\)\]{ background: var(--color-rugby-red); color:#fff; }
  .bg-\[var\(--color-rugby-blue\)\]{ background: var(--color-rugby-blue); color:#fff; }
  .bg-\[var\(--color-rugby-green\)\]{ background: var(--color-rugby-green); color:#fff; }
  .bg-\[var\(--color-rugby-purple\)\]\/20{ background: color-mix(in srgb, var(--color-rugby-purple) 20%, transparent); }
  .text-\[var\(--color-rugby-blue\)\]{ color: var(--color-rugby-blue); }
  .text-\[var\(--color-rugby-purple\)\]{ color: var(--color-rugby-purple); }
  .text-\[var\(--color-rugby-green\)\]{ color: var(--color-rugby-green); }
  .text-\[var\(--color-rugby-orange\)\]{ color: var(--color-rugby-orange); }

  /* === Sponsors slider (kept from your version) === */
  .enhanced-homepage-slider {
    display:flex; flex-wrap:nowrap; overflow-x:auto; gap:20px; padding:10px 0;
    scroll-behavior:smooth; max-width:100%; margin:0 auto; position:relative;
  }
  .enhanced-homepage-slider::-webkit-scrollbar{ display:none; }
  .sponsor-slide{ flex:0 0 200px; text-align:center; }
  .sponsor-slide img{ width:180px; height:auto; border-radius:10px; transition: transform .3s ease; background:#fff; }
  .sponsor-slide img:hover{ transform: scale(1.06); }
  .slider-nav{
    position:absolute; top:50%; transform: translateY(-50%);
    background-color: rgba(0,0,0,.45); color:#fff; border:none; border-radius:50%;
    width:40px; height:40px; cursor:pointer; z-index:10; display:flex; align-items:center; justify-content:center;
    box-shadow:0 2px 5px rgba(0,0,0,.2);
  }
  .slider-nav:hover{ background-color: rgba(0,0,0,.75); }
  .slider-nav.left{ left:10px; } .slider-nav.right{ right:10px; }
</style>
    <?php
    return ob_get_clean();
}

/* (Optional) Keep the old sponsors-only shortcode if you still use it somewhere */
add_shortcode('football_pool_enhanced_homepage', function () {
    $sponsors = get_option('fp_sponsors', []);
    if (!is_array($sponsors) || empty($sponsors)) {
        return '<p>No sponsors available.</p>';
    }
    ob_start(); ?>
    <div class="slider-container" style="position: relative; max-width: 100%; margin: 0 auto;">
        <button class="slider-nav left" aria-label="Previous">&lt;</button>
        <div class="enhanced-homepage-slider">
            <?php foreach ($sponsors as $sponsor): ?>
                <div class="sponsor-slide">
                    <a href="<?php echo esc_url($sponsor['link'] ?? '#'); ?>" target="_blank" rel="noopener">
                        <img src="<?php echo esc_url($sponsor['image'] ?? ''); ?>" alt="Sponsor">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="slider-nav right" aria-label="Next">&gt;</button>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const slider = document.querySelector('.enhanced-homepage-slider');
        if (!slider) return;
        const leftButton = document.querySelector('.slider-nav.left');
        const rightButton = document.querySelector('.slider-nav.right');
        if (leftButton) leftButton.addEventListener('click', () => slider.scrollBy({ left: -250, behavior: 'smooth' }));
        if (rightButton) rightButton.addEventListener('click', () => slider.scrollBy({ left:  250, behavior: 'smooth' }));
      });
    </script>
    <?php
    return ob_get_clean();
});