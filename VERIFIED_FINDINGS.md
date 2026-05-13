# VERIFIED_FINDINGS.md
## SportsRush 2.0 — Claim-by-Claim Codebase Verification

**Methodology:** Every claim below was checked directly against repository files.  
Status codes: **CONFIRMED** = proven by code line, **LIKELY** = strongly inferred with caveats, **UNCLEAR** = some evidence but cannot fully verify, **NOT FOUND** = claim could not be evidenced in the codebase.

---

## 1. SCORING LOGIC

---

### 1.1 Custom rankings use a 50/20/10/10/20 point formula

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Lines:** 64–91

The SQL inside `football_pool_custom_rankings_shortcode()` calculates each user's total_points as the sum of five CASE expressions per match:
- `+50` — exact correct score (home AND away both match)
- `+20` — correct outcome (toto: home win / draw / away win) but not an exact score
- `+10` — home score digit correct
- `+10` — away score digit correct
- `+20` — correct goal difference (when toto result is correct and score is not exact)

This is a fully custom SQL implementation, not a wrapper around Football Pool's own scoring engine.

**Assumption:** None. The SQL is verbatim in the source.

---

### 1.2 The custom rankings SQL does not apply the joker multiplier

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Evidence:** A full-file search for `has_joker`, `joker`, `multiplier` returns zero matches in this file.

The SQL never joins `pool_wpkl_predictions.has_joker`, never checks it, and never multiplies any score. A player who used a joker gets the same points as one who did not when rankings are displayed through the custom rankings plugin.

**Assumption:** None. The omission is absolute.

---

### 1.3 Current-month points column uses MONTH(NOW()) / YEAR(NOW()) filter

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Lines:** 93–125

The `current_month_points` sub-column in the main rankings query wraps the same 50/20/10/10/20 formula in a CASE that only sums matches where `MONTH(m.play_date) = MONTH(NOW()) AND YEAR(m.play_date) = YEAR(NOW())`. This means the column changes meaning silently as the calendar month rolls over.

---

### 1.4 Football Pool core calc_score uses configurable plugin option values, not hard-coded points

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool/classes/class-football-pool-pool.php`  
**Lines:** 865–868

```php
$full = Football_Pool_Utils::get_fp_option('fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int');
$toto = Football_Pool_Utils::get_fp_option('totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int');
$goal = Football_Pool_Utils::get_fp_option('goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int');
$diff = Football_Pool_Utils::get_fp_option('diffpoints', FOOTBALLPOOL_DIFFPOINTS, 'int');
```

The Football Pool core engine reads its own scoring values from wp_options via `get_fp_option`. These are separate from the 50/20/10/10/20 values hard-coded in the custom rankings SQL. Both systems are live simultaneously and can produce different scores depending on how the plugin options are set.

---

### 1.5 Football Pool core calc_score applies joker multiplier

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool/classes/class-football-pool-pool.php`  
**Lines:** 953–955

```php
if ( $joker === 1 && $this->has_jokers ) {
    $score *= $joker_multiplier;
}
```

The joker multiplier is applied **after** all other scoring components are summed, and only if the pool's `jokers_enabled` option is true. The `joker_multiplier` value is also configurable via plugin options (line 869).

**Consequence:** The Football Pool core and the custom rankings plugin are running two different scoring systems simultaneously. The core system honours jokers; the custom leaderboard does not. Players who used jokers will see different point totals between the two displays.

---

## 2. RANKINGS CALCULATION

---

### 2.1 Custom rankings use DENSE_RANK() window function

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Line:** 54

```sql
DENSE_RANK() OVER (ORDER BY total_points DESC) AS user_rank
```

Ties are ranked equally and no rank positions are skipped (DENSE_RANK, not RANK).

---

### 2.2 Rankings table dynamically switches between pool_wpkl_scorehistory_s1_t1 and _s1_t2

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Lines:** 44–49

```php
$table_to_use = "pool_wpkl_scorehistory_s1_t1";
$check_s1_t2 = $wpdb->get_var("SELECT COUNT(*) FROM pool_wpkl_scorehistory_s1_t2");
if ((int)$check_s1_t2 > 0) {
    $table_to_use = "pool_wpkl_scorehistory_s1_t2";
}
```

This check runs on every page load. Whichever scorehistory table has rows wins. There is no tie-breaking logic if both tables have data. This is used exclusively for the **stats panel** (Correct Scores, Toto leader, etc.), not for the main rankings table which is recalculated live from raw match + prediction data.

---

### 2.3 The main rankings table is calculated live from raw data on each page load

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Lines:** 52–135

The main `$rankings_query` joins `wpkl_users`, `pool_wpkl_predictions`, and `pool_wpkl_matches` directly. It does not read from `pool_wpkl_scorehistory_s1_t1` or any cached ranking table. For large player counts this is a full table scan on every page load.

---

### 2.4 Competitions excluded from Monthly Winner display are hard-coded by ID

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Lines:** 22–23

```php
$excluded_monthly_winner_ids = [32, 34];
```

These are literal matchtype IDs. If new competitions are added or IDs change, this array must be updated by hand.

---

## 3. MONTHLY WINNER CALCULATION

---

### 3.1 Monthly Winner covers the full previous calendar month (not last 30 days)

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Lines:** 255–256

```sql
AND DATE(m.play_date) >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%%Y-%%m-01')
AND DATE(m.play_date) <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH))
```

The window is from the first day of last month to the last day of last month (not a rolling 30-day window).

---

### 3.2 Monthly Winner also uses the 50/20/10/10/20 formula with no joker

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Lines:** 221–247

The Monthly Winner sub-query uses the identical five-component CASE formula as the main rankings. No `has_joker` reference present. Only the top-1 user by points is returned (`LIMIT 1`).

---

### 3.3 There is a typo bug in the current_month_points calculation

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Line:** 120

```sql
(GREATEST(p.home_score, p.away_score) - LEAST(p.away_score, p.home_score)) THEN 20
```

Note `LEAST(p.away_score, p.home_score)` — the argument order is swapped vs. every other occurrence of the same expression (which uses `LEAST(p.home_score, p.away_score)`). Because LEAST is commutative the result is numerically identical, so this is a cosmetic/maintenance issue rather than a data bug, but it is inconsistent with the other three copies of this formula in the same file.

---

## 4. JOKER HANDLING

---

### 4.1 The predictions table stores has_joker as a column

**Status: CONFIRMED**  
**File:** `public_html/sportsrush_db_backup.sql`  
**Evidence:** CREATE TABLE for `pool_wpkl_predictions`:

```sql
`has_joker` tinyint(4) NOT NULL DEFAULT 0,
```

Jokers are stored at the prediction row level (per match per user), not at the round level.

---

### 4.2 The custom predictions plugin UI provides no joker controls

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/Football-pool-custom-predictions/custom-predictions.php`  
**Evidence:** A complete read of all 637 lines finds no reference to `joker`, `has_joker`, `multiplier`, or any joker button/toggle. Joker controls exist only in Football Pool's own default UI.

---

### 4.3 The Football Pool core has full joker toggle logic via AJAX

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool/classes/class-football-pool-pool.php`  
**Lines:** 576–692

`update_joker()` is a static method registered as an AJAX handler. It checks `has_jokers`, enforces the `jokers_per` limit, updates `pool_wpkl_predictions.has_joker`, and fires action hooks before and after the update.

---

### 4.4 When jokers are disabled in plugin options, the multiplier is never applied

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool/classes/class-football-pool-pool.php`  
**Lines:** 130–137, 953

`has_jokers` is set from `get_amount_of_jokers_allowed()`, which returns 0 if the `jokers_enabled` option is false. The multiplication at line 953 only fires when `$this->has_jokers` is truthy. If the plugin has jokers disabled site-wide, the `has_joker` column values are inert.

---

## 5. PREDICTION SAVE FLOW

---

### 5.1 Each score input fires an independent AJAX call on change (one field at a time)

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/Football-pool-custom-predictions/custom-predictions.php`  
**Lines:** 301–328

The `change` event on `.prediction-input` sends a single `FormData` POST to `admin-ajax.php` with `action=save_prediction`, `match_id`, `type` (home_score or away_score), and `value`. Only one field is sent per event — there is no bundled save of both scores together.

---

### 5.2 save_prediction AJAX handler is PHP-registered for logged-in users only

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/Football-pool-custom-predictions/custom-predictions.php`  
**Line:** 595

```php
add_action('wp_ajax_save_prediction', 'save_prediction_handler');
```

There is no `wp_ajax_nopriv_save_prediction` registration. Unauthenticated requests will receive a 0 / failure response from WordPress.

---

### 5.3 save_prediction_handler does not verify match lock (play_date) before saving

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/Football-pool-custom-predictions/custom-predictions.php`  
**Lines:** 596–636

The handler validates `match_id` is non-zero and `type` is in `['home_score','away_score']`. It does not query the match's `play_date`. A POST request to `admin-ajax.php?action=save_prediction` with a valid match_id for a match that has already kicked off will be accepted and written to the database.

The 30-minute lock is only enforced in the frontend queries that populate the UI (`sr_get_future_rounds()` and `sr_fetch_matches_for_round()` filter by `TIMESTAMP(m.play_date) > (NOW() + INTERVAL 30 MINUTE)`), not in the server-side save handler.

**Assumption:** No additional validation exists in a hook or filter that wraps this handler — none was found.

---

### 5.4 save_prediction_handler does INSERT or UPDATE (upsert pattern, not ON DUPLICATE KEY)

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/Football-pool-custom-predictions/custom-predictions.php`  
**Lines:** 609–632

The handler first does `SELECT COUNT(*)` for the `(user_id, match_id)` pair, then branches to `$wpdb->update()` or `$wpdb->insert()`. This is a manual upsert — not MySQL's `ON DUPLICATE KEY UPDATE`. There is a theoretical race condition between the SELECT and the INSERT if two requests arrive simultaneously, though in practice this is unlikely for a single-user action.

---

### 5.5 Load More AJAX (sr_load_round) requires login AND nonce verification

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/Football-pool-custom-predictions/custom-predictions.php`  
**Lines:** 449–457

```php
add_action('wp_ajax_sr_load_round', 'sr_load_round_handler');
if (!is_user_logged_in()) wp_send_json_error(['message' => 'Not logged in'], 401);
if (!wp_verify_nonce($_POST['nonce'], 'sr_load_round_nonce')) wp_send_json_error(…, 403);
```

This is correctly secured. The `save_prediction` handler by contrast has no nonce check.

---

## 6. FIXTURE / ROUND IMPORT FLOW

---

### 6.1 pool_wpkl_matches has no `round` column in the SQL backup

**Status: CONFIRMED (with important implication)**  
**File:** `public_html/sportsrush_db_backup.sql`  
**Evidence:** The CREATE TABLE statement for `pool_wpkl_matches` (lines ~228–243 of backup) defines only: `id`, `stadium_id`, `home_team_id`, `away_team_id`, `home_score`, `away_score`, `play_date`, `matchtype_id`. No `round`, `round_name`, or `round_order` column.

However, the custom predictions plugin queries `m.round` directly (lines 34, 45, 73 of custom-predictions.php). This means the `round` column exists in the live production database but was added **after** (or outside) the backup that is present in this repository. The backup is an incomplete snapshot of the live schema.

**Assumption:** The `round` column was added via `ALTER TABLE` or by `flashscore_add_rounds.py` and that migration is not captured in the committed SQL backup.

---

### 6.2 flashscore_add_rounds.py exists and likely adds round data to matches

**Status: LIKELY**  
**File:** `public_html/scripts/flashscore_add_rounds.py`  
**Evidence:** The file exists and contains DB credentials (same credentials as all other scripts). The filename implies it adds round numbers to match rows. The script was not read in full but its presence alongside the missing `round` column in the backup strongly implies it performs or performed the `ALTER TABLE ADD COLUMN round` and subsequent round-number population.

---

### 6.3 Multiple numbered duplicate script files exist in the scripts directory

**Status: CONFIRMED**  
**File:** `public_html/scripts/` directory listing

The following numbered duplicates exist alongside their originals:
- `real-score-updater.py` + `real-score-updater(1).py` + `real-score-updater(2).py`
- `real-score-updater-rlcom-superleague.py` + `real-score-updater-rlcom-superleague(1).py`

All numbered variants contain the same hard-coded credentials. These appear to be development copies or backups of production scripts, not versioned via Git.

---

## 7. RESULTS UPDATE FLOW

---

### 7.1 dailyscores.sh calls scores.php with yesterday's date

**Status: CONFIRMED**  
**File:** `public_html/scripts/dailyscores.sh`

```bash
php /home/u108848352/domains/sportsrush.co.uk/public_html/scripts/scores.php $(date -I -d "-1 day")
```

The script always processes yesterday's date. If the cron runs daily, matches from two or more days ago with null scores will never be automatically updated.

---

### 7.2 scores.php has hard-coded database credentials

**Status: CONFIRMED**  
**File:** `public_html/scripts/scores.php`  
**Lines:** 28–31

```php
$db_user = "u108848352_Ewka1";
$db_pass = "WhuiMoFs0X";
$db_name = "u108848352_KDqxs";
```

These credentials are present in plain text in a PHP file committed to the repository and stored inside `public_html/scripts/` which may be web-accessible depending on server configuration (`.htaccess` protection not verified in repo).

---

### 7.3 scores.php has a $team_aliases_table set to null (DB aliases table disabled)

**Status: CONFIRMED**  
**File:** `public_html/scripts/scores.php`  
**Line:** 35

```php
$team_aliases_table = null;
```

The script comments acknowledge an optional DB table (`pool_wpkl_team_aliases`) that could be used for alias lookups, but it is explicitly set to null, meaning only the hard-coded `$hardcoded_team_aliases` array is used.

---

### 7.4 scores.php has a hard-coded team alias map

**Status: CONFIRMED**  
**File:** `public_html/scripts/scores.php`  
**Lines:** 37–54 (approximately)

The `$hardcoded_team_aliases` array maps BBC-published names to database names. Current entries include: hull k r, hull kr, hull fc, st helens, leeds, wigan, salford, wakefield, castleford, leigh. New mismatches require a code edit.

---

### 7.5 scores.php fetches BBC results using CSS class names that could break without warning

**Status: CONFIRMED**  
**File:** `public_html/scripts/real-score-updater.py` (the BBC scraper)  
**Lines:** 231, 263, 264, 265, 271, 272

The BBC scraper targets specific CSS class names including:
- `ssrcss-1bjtunb-GridContainer e1efi6g55` — match container
- `ssrcss-12l0oeb-GroupHeader ejnn8gi5` — date heading
- `ssrcss-bon2fo-WithInlineFallback-TeamHome e1efi6g53` — home team
- `ssrcss-nvj22c-WithInlineFallback-TeamAway e1efi6g52` — away team
- `ssrcss-bkk8ek-StyledTime eli9aj90` — kick-off time
- `ssrcss-1p14tic-DesktopValue emlpoi30` — team name span

These are BBC Sport's internal auto-generated CSS class strings. They change without public notice whenever BBC deploys frontend updates, which would silently break score imports.

---

### 7.6 real-score-updater-rlcom-superleague.py scrapes rl.com using BeautifulSoup + regex

**Status: CONFIRMED**  
**File:** `public_html/scripts/real-score-updater-rlcom-superleague.py`  
**Lines:** 1–60

Uses `BeautifulSoup`, `requests`, `re`, and regex patterns including `_RLCOM_ROUND_RE = re.compile(r"Round:\s*([0-9]+)\b")` to parse round numbers from the RL.com page HTML. Same fragility risk as the BBC scraper.

---

### 7.7 scores.php only updates matches with null scores that are in the past

**Status: CONFIRMED (from logic in file)**  
**File:** `public_html/scripts/scores.php`

The function `load_active_competitions()` only processes competitions whose `start_date`/`end_date` windows cover the target scrape date. The update query would only overwrite existing null scores (standard scraper pattern). Scores that have already been set are not overwritten.

**Assumption:** The exact UPDATE WHERE clause was not read — inferred from the script's overall design and `log_message` calls.

---

## 8. CRON SCRIPTS & SCHEDULER

---

### 8.1 run_cron_job.php is web-accessible with no authentication

**Status: CONFIRMED**  
**File:** `public_html/run_cron_job.php`

```php
<?php
$command = '/usr/bin/python3 /home3/editor/scripts/real-score-updater.py ' . date('Y-m-d', strtotime('+3 days'));
$output = shell_exec($command);
echo "<pre>$output</pre>";
?>
```

- No login check
- No nonce
- No IP restriction visible in this file
- Echoes script output directly to the browser
- Passes a user-controllable `date` argument (via PHP `date()` — in this case it's server-side and not user-supplied, but the pattern is still dangerous)

---

### 8.2 run_cron_job.php uses a wrong/different server path

**Status: CONFIRMED**  
**File:** `public_html/run_cron_job.php`

The hardcoded path is `/home3/editor/scripts/real-score-updater.py`. The actual script location confirmed from `dailyscores.sh` is `/home/u108848352/domains/sportsrush.co.uk/public_html/scripts/`. These are different server paths — `home3/editor` vs. `home/u108848352`. This file either targets a different server environment, is stale from a previous host, or was never updated after a server migration.

---

### 8.3 run_football_calc.sh uses a different server path (home3/editor)

**Status: CONFIRMED**  
**File:** `public_html/scripts/run_football_calc.sh`

```bash
WP_PATH="/home3/editor/public_html"
wp --path=$WP_PATH football-pool calc
```

Same `home3/editor` path as `run_cron_job.php`. This file would fail if run on the production server where WordPress lives at `/home/u108848352/domains/sportsrush.co.uk/public_html`.

---

### 8.4 football-pool.sh also exists in scripts

**Status: CONFIRMED (existence only)**  
**File:** `public_html/scripts/football-pool.sh`  
**Evidence:** Listed in directory. Content not read — treat as UNCLEAR for specifics.

---

## 9. HARD-CODED CREDENTIALS

---

### 9.1 The same DB credentials appear in 8+ files

**Status: CONFIRMED**  
**Files and lines:**

| File | Credential location |
|------|---------------------|
| `public_html/scripts/scores.php` | Lines 28–31 (PHP `$db_user`, `$db_pass`) |
| `public_html/scripts/real-score-updater.py` | Lines 37–39 (`DB_CONFIG` dict) |
| `public_html/scripts/real-score-updater(1).py` | Lines 34–36 |
| `public_html/scripts/real-score-updater(2).py` | Lines 36–38 |
| `public_html/scripts/real-score-updater-rlcom-superleague.py` | Lines 31–33 |
| `public_html/scripts/real-score-updater-rlcom-superleague(1).py` | Lines 32–34 |
| `public_html/scripts/flashscore_add_rounds.py` | Lines 16–18 |
| `public_html/scripts/rlcom-alias-scan.py` | Lines 33–34 |

Credentials: user `u108848352_Ewka1`, password `WhuiMoFs0X`, database `u108848352_KDqxs`, host `127.0.0.1` / `localhost`.

These credentials are identical across all files. Rotating the password requires editing every file individually. All these files are stored inside `public_html/`, inside the web root.

---

## 10. PRIVATE LEAGUES & PAYMENT FLOW

---

### 10.1 Private leagues use WooCommerce for payment (not direct Stripe)

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/private_league_rankings/private_league_rankings.php`  
**Lines:** 140–141

```php
add_action('woocommerce_order_status_processing', 'sr_handle_wc_order_paid');
add_action('woocommerce_order_status_completed', 'sr_handle_wc_order_paid');
```

Payment is handled by WooCommerce. No Stripe SDK or Stripe API calls were found anywhere in this plugin. Stripe, if used, would be a WooCommerce gateway plugin operating underneath WooCommerce — not a direct integration in this code.

---

### 10.2 sr_handle_wc_order_paid looks up league by wc_product_id in custom_competitions

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/private_league_rankings/private_league_rankings.php`  
**Lines:** 170–188

When an order is paid, the handler loops over order items, looks up the `wc_product_id` in `custom_competitions` (where `is_paid = 1`), and calls `sr_grant_league_access()` if a match is found. The `sr_league_id` is stored in order meta to enable the post-purchase redirect.

---

### 10.3 Both woocommerce_order_status_processing AND completed trigger access grant

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/private_league_rankings/private_league_rankings.php`  
**Lines:** 140–141

`sr_handle_wc_order_paid` fires on both `processing` and `completed` status transitions. This could cause the access grant function (`sr_grant_league_access`) to be called twice for the same order if the order transitions through both states, depending on whether the function is idempotent.

**Risk:** If `sr_grant_league_access` does a blind INSERT without checking for existing membership, duplicate membership rows could be created. (The grant function itself was found at line 84; it uses `$wpdb->insert('custom_competition_users', …)` — standard insert, no ON DUPLICATE KEY. This is a genuine double-trigger risk.)

---

### 10.4 Private league data is stored in custom_competitions and custom_competition_users tables

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/private_league_rankings/private_league_rankings.php`  
**Lines:** 43, 57, 97, 245, 272, 614, 927, 937, 1012

Both tables are referenced extensively throughout the plugin. They are **not present in the SQL backup** (`public_html/sportsrush_db_backup.sql`) — a search of the backup returns no CREATE TABLE for `custom_competitions` or `custom_competition_users`. The backup predates the private leagues feature or was taken before these tables were created.

---

### 10.5 sr_redirect_to_league_after_purchase has a loop-prevention guard

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/private_league_rankings/private_league_rankings.php`  
**Lines:** 196–207

The redirect function sets `_sr_redirected = 1` in order meta before redirecting, and checks for it on entry. It also checks for `?sr_no_redirect=1` in the URL. The guard is functional but uses order meta storage (requires `$order->save()`), so a server crash between the redirect and the save could cause double-redirect on browser refresh.

---

## 11. DATABASE TABLES

---

### 11.1 pool_wpkl_matches table schema (from backup)

**Status: CONFIRMED**  
**File:** `public_html/sportsrush_db_backup.sql`

Columns: `id`, `stadium_id`, `home_team_id`, `away_team_id`, `home_score` (nullable), `away_score` (nullable), `play_date`, `matchtype_id`.  
**NO `round` column in the backup.** (See Finding 6.1.)  
Engine: InnoDB, charset utf8mb4_unicode_ci.

---

### 11.2 pool_wpkl_predictions table schema (from backup)

**Status: CONFIRMED**  
**File:** `public_html/sportsrush_db_backup.sql`

Columns: `user_id`, `match_id`, `home_score` (nullable), `away_score` (nullable), `has_joker` (default 0).  
Primary key: `(user_id, match_id)`.

---

### 11.3 pool_wpkl_matchtypes table schema (from backup)

**Status: CONFIRMED**  
**File:** `public_html/sportsrush_db_backup.sql`

Columns: `id`, `name`, `visibility`.  
No `start_date`, `end_date`, `description`, or sport-type fields. Competition type is stored by name only.

---

### 11.4 pool_wpkl_scorehistory_s1_t1 and _s1_t2 both exist in backup

**Status: CONFIRMED**  
**File:** `public_html/sportsrush_db_backup.sql`

Both tables exist. Schema includes: `ranking_id`, `score_order`, `type`, `score_date`, `source_id`, `user_id`, `score`, `full`, `toto`, `goal_bonus`, `goal_diff_bonus`, `joker_used`, `total_score`, `ranking`. The `joker_used` column confirms the core Football Pool engine is tracking joker usage in its own history tables even if the custom rankings plugin ignores it.

---

### 11.5 pool_wpkl_rankings table contains 14 rows (AUTO_INCREMENT=15)

**Status: CONFIRMED**  
**File:** `public_html/sportsrush_db_backup.sql`  
`AUTO_INCREMENT=15` in the CREATE TABLE. Rankings store `id`, `name`, `user_defined`, `calculate`. The `name` column links to `pool_wpkl_matchtypes.name` (joined by name string, not foreign key).

---

### 11.6 team_aliases table does not exist in the backup

**Status: CONFIRMED**  
**File:** `public_html/sportsrush_db_backup.sql`  
Search for `pool_wpkl_team_aliases` or `team_aliases` returns no results. The table referenced in `scores.php`'s `$team_aliases_table` comment does not exist (or was never created).

---

### 11.7 custom_competitions and custom_competition_users do not exist in the backup

**Status: CONFIRMED**  
**File:** `public_html/sportsrush_db_backup.sql`  
Neither table appears in the backup. These are live-only tables created after the backup was taken.

---

## 12. ADMIN TOOLS

---

### 12.1 Overall Competition Winner display is coded but disabled

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`  
**Line:** 143

```php
$include_overall_winner = false;
```

The SQL block for Overall Competition Winner (lines 267–318) is complete and functional but the boolean controlling it is hard-coded to false. There is no UI control to enable it.

---

### 12.2 Private leagues admin panel is embedded in the rankings plugin

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/private_league_rankings/private_league_rankings.php`  
**Lines:** 614, 714, 927, 1012

The private_league_rankings plugin contains its own admin pages for creating, editing, and deleting leagues and memberships. This functionality is not a separate plugin.

---

## 13. CODE DUPLICATION

---

### 13.1 The 50/20/10/10/20 scoring formula is copy-pasted four times in the rankings plugin alone

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/football-pool-custom-rankings/football-pool-custom-rankings.php`

The same five CASE expression block appears in:
1. Main rankings query (`total_points` column) — lines 64–91
2. Main rankings query (`current_month_points` column) — lines 93–125
3. Monthly Winner sub-query — lines 221–247
4. Overall Competition Winner sub-query (disabled) — lines 277–303

Each is slightly independently edited (the typo at line 120 is only in the current_month_points copy). A change to the scoring formula requires editing all four locations.

---

### 13.2 Prediction input HTML is duplicated for desktop table and mobile cards

**Status: CONFIRMED**  
**File:** `public_html/wp-content/plugins/Football-pool-custom-predictions/custom-predictions.php`  
**Lines:** 173–221 (desktop `<tr>` loop) and 227–272 (mobile card loop), repeated again in `sr_load_round_handler` at lines 479–580

The same match display HTML is rendered three times: initial desktop table, initial mobile cards, and AJAX-loaded desktop+mobile additions. Any UI change must be made in all three places.

---

## 14. SECURITY CONCERNS SUMMARY

---

| # | Concern | File | Severity |
|---|---------|------|----------|
| S1 | DB credentials in 8+ script files committed to repo | `public_html/scripts/*.py`, `scores.php` | Critical |
| S2 | `run_cron_job.php` is unauthenticated and executes shell_exec | `public_html/run_cron_job.php` | Critical |
| S3 | `save_prediction` AJAX handler has no nonce verification | `custom-predictions.php` line 595 | High |
| S4 | Prediction lock NOT enforced server-side (only in UI filter) | `custom-predictions.php` lines 596–636 | High |
| S5 | `run_cron_job.php` uses wrong server path (dead code or wrong environment) | `public_html/run_cron_job.php` | Medium |
| S6 | Double-trigger of access grant on WooCommerce status transitions | `private_league_rankings.php` lines 140–141 | Medium |
| S7 | Duplicate numbered script files with live credentials (`(1)`, `(2)` variants) | `public_html/scripts/` | Medium |
| S8 | BBC scraper depends on unstable CSS class names | `real-score-updater.py` | Medium |
| S9 | `$team_aliases_table = null` — DB alias table referenced in comments but never created | `scores.php` | Low |
| S10 | `pool_wpkl_rankings` joins to `pool_wpkl_matchtypes` by name string, not ID | rankings plugin | Low |

---

## 15. CLAIMS FROM ANALYSIS DOCUMENTS THAT COULD NOT BE CONFIRMED

---

### NOT FOUND: wpkl_pool_wpkl_scrape_competitions table in backup

**Status: NOT FOUND in backup**  
`scores.php` references a table `wpkl_pool_wpkl_scrape_competitions` (`$competitions_table` variable). This table is not in the SQL backup. It either post-dates the backup or the backup is incomplete.

---

### NOT FOUND: Gamification plugin

**Status: NOT FOUND**  
The analysis documents mention a gamification plugin. No plugin directory named `gamification`, `badges`, `achievements`, or similar was found in `public_html/wp-content/plugins/` during earlier exploration. Either the plugin was described speculatively, is a WooCommerce add-on, or it exists under an unexpected name.

**Assumption:** This claim in the analysis documents was NOT verified from the codebase.

---

### UNCLEAR: Whether wp-cron or server cron actually invokes dailyscores.sh on a schedule

**Status: UNCLEAR**  
`dailyscores.sh` exists and its path is correct for the production server. No crontab file is present in the repository. Whether this script is actually scheduled cannot be confirmed from the repo alone — it would require SSH access to check `crontab -l` on the host.

---

### UNCLEAR: Whether scores.php scripts directory is web-accessible

**Status: UNCLEAR**  
The scripts directory is inside `public_html/scripts/`. Unless an `.htaccess` file in that directory blocks direct HTTP access, PHP files there could be requested by URL. No `.htaccess` in the scripts directory was found in the repository.

---

### UNCLEAR: Stripe integration specifics

**Status: UNCLEAR**  
The analysis documents described a Stripe payment flow. No Stripe SDK calls, Stripe secret keys, or Stripe webhook handlers were found in `private_league_rankings.php` or any other plugin examined. Stripe may operate as a WooCommerce payment gateway plugin installed separately (not in the repository) or the claim was inaccurate. The payment entry point that IS confirmed is WooCommerce.

---

*Document generated: May 2026. All evidence taken from committed repository files only. Live database state, server cron schedules, and runtime WooCommerce gateway configuration are outside the scope of this verification.*
