# HIGH_RISK_AREAS.md
## SportsRush 2.0 — Top 10 Areas Most Dangerous to Rebuild Incorrectly

Each entry below identifies a subsystem where a rebuild mistake would cause silent data corruption, incorrect rankings, lost revenue, or a security regression — backed by specific codebase evidence and with concrete test cases defined.

---

## #1 — Dual Scoring Systems Running Simultaneously

### Why it is dangerous
The platform currently has **two independent scoring engines** that produce different results for the same predictions. The Football Pool core plugin calculates scores using configurable `wp_options` values and stores results in `pool_wpkl_scorehistory_s1_t1/t2`. The custom rankings plugin recalculates scores from scratch on every page load using a hard-coded 50/20/10/10/20 SQL formula. Both are live at the same time. A rebuild that preserves only one or the other without a deliberate policy decision will either:
- Drop the joker multiplier entirely (if the custom SQL path is kept)
- Produce different historical totals than what users currently see (if the core's stored history is used)

Neither system is "wrong" — they are just diverged. The rebuild must canonicalise exactly one formula before writing a single line of scoring code.

### Codebase evidence
- Custom rankings formula: `football-pool-custom-rankings.php` lines 64–91 (hard-coded CASE expressions, no joker reference)
- Core engine formula: `class-football-pool-pool.php` lines 865–955 (configurable options, joker multiplier at line 953)
- Joker column in predictions: `sportsrush_db_backup.sql` — `has_joker tinyint(4) NOT NULL DEFAULT 0` in `pool_wpkl_predictions`

### Test cases
1. Submit a correct exact score (e.g. 3-1 predicted, 3-1 result) with no joker — assert total = 50+20+10+10 = **90 points** (the 50+20 are mutually exclusive with toto path; confirm formula intent with the client before hardcoding the expected value)
2. Submit the same correct exact score **with joker active** — assert the displayed leaderboard total is double (if joker multiplier = 2) and confirm both the rankings page AND any stored history reflect the same value
3. Submit a correct outcome (1-0 predicted, 2-0 result) — assert = 20 (toto) + 10 (home goal) + 0 (away goal) + 20 (goal diff, if same) or 20+10+0+0 = 30
4. Submit a prediction for a draw that was actually a draw (1-1 predicted, 1-1 result) — exact score = 50+10+10 = 70; goal diff bonus should NOT apply to an exact score (confirmed in code: the diff bonus CASE checks `home_score = p.home_score AND away_score = p.away_score THEN 0` first)
5. Compare rankings page point total to Football Pool's own built-in rankings page for the same user — they must match or a documented policy explains why they differ

---

## #2 — Prediction Lock Is Only Enforced in the UI, Not on the Server

### Why it is dangerous
The 30-minute pre-kickoff lock is applied exclusively in the PHP queries that **display** the predictions form — matches are filtered by `TIMESTAMP(m.play_date) > (NOW() + INTERVAL 30 MINUTE)`. The AJAX save handler (`save_prediction_handler`) performs no such check. A user who opens the predictions form before kickoff and leaves the browser tab open can submit scores after the match has started. On rebuild, if the UI-side filter is copied faithfully but the server-side guard is still absent, this vulnerability will be silently carried forward into the new system.

### Codebase evidence
- UI-side lock: `custom-predictions.php` lines 37–39 (WHERE clause in `sr_get_future_rounds`) and lines 71–72 (WHERE clause in `sr_fetch_matches_for_round`)
- Missing lock in save handler: `custom-predictions.php` lines 596–636 — no `play_date` query anywhere in the function body

### Test cases
1. Open the predictions form for a match with kick-off in 25 minutes. Do not submit. Wait 35 minutes. Submit a prediction score. Assert the server **rejects** the save with an appropriate error (this test will currently **pass** the prediction through — it is the expected failure on the current system, the expected pass on the rebuild)
2. Submit a direct POST to `admin-ajax.php` with `action=save_prediction` and a `match_id` for a match that kicked off yesterday — assert HTTP 403 or JSON error
3. Submit a prediction with a valid nonce for a future match — assert it saves correctly (regression guard)
4. Simultaneously open two browser sessions for the same user, submit conflicting scores for the same match — assert the database contains only one row and no duplicate-key error is surfaced to the user

---

## #3 — Monthly Winner Calendar Boundary Logic

### Why it is dangerous
The Monthly Winner is defined as the player with the most points from matches whose `play_date` falls **within the previous calendar month** (first day to last day). The formula is correct in code, but it is calculated live, uses `DATE_SUB(NOW(), INTERVAL 1 MONTH)` in multiple places, and contains a subtle typo in one of the four duplicate copies of the formula. A rebuild that gets the date window slightly wrong (e.g. using a 30-day rolling window instead of calendar month boundaries) will declare a different winner, which has real-world prize implications.

### Codebase evidence
- Correct date window: `football-pool-custom-rankings.php` lines 255–256:  
  `DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%%Y-%%m-01')` to `LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH))`
- Typo in current_month points: line 120 — `LEAST(p.away_score, p.home_score)` argument order reversed (commutative so not a numeric bug, but flags copy-paste drift between the four formula instances)
- Exclusion list: lines 22–23 — competition IDs 32 and 34 are hard-coded exclusions for Monthly Winner display

### Test cases
1. Seed test matches on the 1st and last day of the previous calendar month — assert both are included in the Monthly Winner calculation
2. Seed a match on the last day of two months ago — assert it is **excluded**
3. Seed a match on the 1st day of the current month — assert it is **excluded** from Monthly Winner (it should appear in the current month column instead)
4. Verify that competition IDs excluded from Monthly Winner display have a configurable mechanism (not a hard-coded array) in the rebuild
5. Run the Monthly Winner query across a month boundary at midnight (e.g. 23:59 on the 31st vs 00:01 on the 1st) — assert results change appropriately

---

## #4 — Round Column Exists in Production but Not in the SQL Backup

### Why it is dangerous
The committed database backup (`sportsrush_db_backup.sql`) shows `pool_wpkl_matches` with **no `round` column**. The live custom predictions plugin queries `m.round` throughout (`custom-predictions.php` lines 34, 45, 73). If a rebuild is seeded from the backup SQL, the `round` column will be missing and every predictions page query will throw a MySQL error. There is no `ALTER TABLE` script in the repository to add the column. The migration is either in `flashscore_add_rounds.py` or was run manually and never committed.

### Codebase evidence
- Backup schema (no round): `sportsrush_db_backup.sql` CREATE TABLE `pool_wpkl_matches` — 8 columns, no `round`
- Live code querying round: `custom-predictions.php` line 34: `SELECT DISTINCT m.round AS round_number`; line 73: `AND m.round = %d`
- Script that likely added it: `public_html/scripts/flashscore_add_rounds.py` (contains live DB credentials, filename implies round population)

### Test cases
1. Run the migrations against a clean database — assert `pool_wpkl_matches` has a `round` column of integer type before any application code is tested
2. Insert a match with `round = NULL` and confirm it is excluded from the predictions page round list (the WHERE clause `AND m.round IS NOT NULL` at line 39 must be verified)
3. Insert two matches in the same competition with the same round number — assert they appear together under one round heading on the predictions page
4. Insert a match with `round = 0` — assert it is handled gracefully (the handler rejects `round_number <= 0` at line 465)

---

## #5 — Score Result Ingestion: BBC CSS Class Dependency

### Why it is dangerous
The BBC Sport results scraper (`real-score-updater.py`) parses match scores by targeting specific CSS class names that are auto-generated by BBC's frontend build system. These class names (e.g. `ssrcss-1bjtunb-GridContainer e1efi6g55`, `ssrcss-bon2fo-WithInlineFallback-TeamHome e1efi6g53`) change without notice whenever BBC deploys a new frontend build. When they change, the scraper silently returns zero matches, no scores are updated, and no error is surfaced to users — the rankings simply stop updating. This has almost certainly already happened at least once given the age of these class strings.

### Codebase evidence
- `public_html/scripts/real-score-updater.py` lines 231, 263–265, 271–272 — six hardcoded CSS class string literals targeting BBC HTML structure
- `public_html/scripts/real-score-updater-rlcom-superleague.py` — uses BeautifulSoup + regex patterns `_RLCOM_ROUND_RE`, `_DATE_HEADING_RE` against RL.com HTML

### Test cases
1. Run the scraper against a known past date with confirmed results — assert the correct scores are returned and written to the database
2. Run the scraper against a date with no matches — assert zero rows are updated and no exception is thrown
3. Simulate a BBC CSS class name change by modifying one class string — assert the scraper logs a clear, actionable error (not silent zero results)
4. On rebuild: define an integration test that hits the BBC page for yesterday's date once per week and asserts at least one match is returned — this becomes the canary for BBC structure changes
5. Verify that the scraper does not overwrite scores that are already set (only fills in NULLs)

---

## #6 — Team Alias Matching: Fragile Hard-Coded Map

### Why it is dangerous
The results update script (`scores.php`) normalises team names from BBC output using a hard-coded PHP array (`$hardcoded_team_aliases`). When a new mismatch appears (e.g. BBC publishes "Catalans Dragons" but the DB stores "Catalans"), no score is written and no error is logged — the match simply stays with `NULL` scores. The optional database-driven aliases table (`pool_wpkl_team_aliases`) is explicitly disabled (`$team_aliases_table = null`). There is also no feedback loop: administrators have no way to know which team names the scraper failed to match on any given run without reading the log file directly.

### Codebase evidence
- `public_html/scripts/scores.php` line 35: `$team_aliases_table = null;`
- `public_html/scripts/scores.php` lines 37–54: `$hardcoded_team_aliases` array with 10 fixed entries
- `normalise_name()` function trims, lowercases, collapses whitespace, and normalises apostrophes before comparison

### Test cases
1. Feed the scraper a team name not in the alias map (e.g. "Catalans Dragons" if "Catalans" is in the DB) — assert the log records an unmatched name explicitly, not a silent skip
2. Feed a name that differs only in punctuation (e.g. "Hull F.C." vs "Hull FC") — assert it resolves correctly after normalisation
3. Add a new alias to the database alias table (once the DB table is created in the rebuild) — assert the scraper uses it without a code deploy
4. Feed a name that matches after lowercase + trim but not before — assert `normalise_name()` handles it
5. Run against a full round of results — assert every match with a known result has a non-NULL score in the DB afterwards (zero unmatched teams)

---

## #7 — WooCommerce Payment Double-Trigger on Access Grant

### Why it is dangerous
`sr_handle_wc_order_paid` is hooked to both `woocommerce_order_status_processing` and `woocommerce_order_status_completed`. An order that transitions `pending → processing → completed` (the normal WooCommerce flow for card payments) will trigger `sr_handle_wc_order_paid` **twice** for the same order. The `sr_grant_league_access` function uses a plain `$wpdb->insert()` into `custom_competition_users` with no `ON DUPLICATE KEY IGNORE` or pre-insert existence check. This risks duplicate membership rows, which could cause: double-counting in member lists, display bugs in the admin panel, or exceptions in queries that expect a single membership row.

### Codebase evidence
- `private_league_rankings.php` lines 140–141: both status hooks call the same function
- `private_league_rankings.php` line 97: `$wpdb->insert('custom_competition_users', […])` — plain INSERT, no duplicate guard
- `private_league_rankings.php` line 84: `sr_grant_league_access()` function entry — no existence check before insert

### Test cases
1. Place a test WooCommerce order for a paid private league — assert `custom_competition_users` contains exactly **one row** for that user+league combination after payment completes
2. Manually fire `do_action('woocommerce_order_status_processing', $order_id)` then `do_action('woocommerce_order_status_completed', $order_id)` — assert the membership count is still 1
3. Attempt to join a league a user is already a member of (e.g. by purchasing again) — assert graceful handling, not a duplicate key SQL error
4. Verify the post-purchase redirect fires only once even when both status hooks trigger
5. Cancel an order after processing but before completing — assert the membership is revoked (or confirm the policy: is membership permanent once granted?)

---

## #8 — save_prediction AJAX Has No Nonce (CSRF Exposure)

### Why it is dangerous
The `save_prediction` AJAX handler (`custom-predictions.php` line 595) is registered only for logged-in users but contains **no nonce verification**. Any page on the internet can send a cross-site POST to `https://sportsrush.co.uk/wp-admin/admin-ajax.php?action=save_prediction` with a valid `match_id` and a logged-in user's browser session cookie (if the user happens to be visiting the attacker's page simultaneously). This is a textbook CSRF attack. On rebuild, if the nonce is not added, the vulnerability persists.

The `sr_load_round` handler correctly uses `wp_verify_nonce` (lines 455–457). The pattern exists in the same file — it was simply not applied to `save_prediction`.

### Codebase evidence
- `custom-predictions.php` line 595: `add_action('wp_ajax_save_prediction', 'save_prediction_handler');`
- `custom-predictions.php` lines 596–636: full handler body — no `check_ajax_referer()` or `wp_verify_nonce()` call
- `custom-predictions.php` lines 455–457: `sr_load_round` correctly calls `wp_verify_nonce($_POST['nonce'], 'sr_load_round_nonce')`

### Test cases
1. Send a POST to `admin-ajax.php` with `action=save_prediction`, a valid `match_id`, and no nonce — on the **rebuild**, assert the server returns 403
2. Send the same POST with a valid nonce — assert the prediction saves correctly
3. Send a POST with a forged/expired nonce — assert 403
4. Confirm the nonce is embedded in the predictions page HTML and passed in the fetch FormData (regression guard for the frontend JS)

---

## #9 — Hard-Coded Database Credentials Across 8+ Script Files

### Why it is dangerous
The live database password (`WhuiMoFs0X`) appears in plain text in at least 8 committed files inside `public_html/`, which is the web root. Scripts stored in `public_html/scripts/` may be directly HTTP-accessible if no `.htaccess` restricts the directory. Even if the files are not HTTP-accessible today, anyone with repository access has the live production database password. On rebuild, copying these scripts without rotating and externalising the credentials would carry the exposure forward.

Additionally, if the password is ever rotated, **all 8+ files** must be found and updated simultaneously — a process that is error-prone and has historically been skipped (evidenced by the numbered duplicate script variants that all still contain the same credentials).

### Codebase evidence
Files with hardcoded credentials (user `u108848352_Ewka1`, password `WhuiMoFs0X`, database `u108848352_KDqxs`):
- `public_html/scripts/scores.php`
- `public_html/scripts/real-score-updater.py`
- `public_html/scripts/real-score-updater(1).py`
- `public_html/scripts/real-score-updater(2).py`
- `public_html/scripts/real-score-updater-rlcom-superleague.py`
- `public_html/scripts/real-score-updater-rlcom-superleague(1).py`
- `public_html/scripts/flashscore_add_rounds.py`
- `public_html/scripts/rlcom-alias-scan.py`

### Test cases
1. Assert no script file in the repository contains the string `WhuiMoFs0X` or any literal database password (automated grep in CI)
2. Assert all scripts read credentials from environment variables or a single external `.env` file outside `public_html/`
3. Assert the credentials file is listed in `.gitignore`
4. Verify that the scripts directory is not HTTP-accessible (request `https://sportsrush.co.uk/scripts/scores.php` and assert 403 or 404)
5. Rotate the database password — assert only one file/variable needs updating and all scripts continue to work

---

## #10 — The SQL Backup Is an Incomplete Snapshot of the Live Schema

### Why it is dangerous
The committed `sportsrush_db_backup.sql` is missing multiple tables that are actively used in production:
- `custom_competitions` — private leagues core data
- `custom_competition_users` — membership records (i.e. who paid for which league)
- `wpkl_pool_wpkl_scrape_competitions` — controls which competitions are scraped by date window
- `pool_wpkl_team_aliases` — the alias DB table that scores.php references but treats as not-yet-created

The `pool_wpkl_matches` table in the backup is also missing the `round` column that the live predictions plugin depends on. If a rebuild uses this backup as its migration starting point, the application will be missing critical live data and will produce SQL errors on first use.

### Codebase evidence
- Backup reviewed: `public_html/sportsrush_db_backup.sql`
- `custom_competitions`: queried in `private_league_rankings.php` lines 174–179 — not in backup
- `custom_competition_users`: queried in same plugin lines 43, 57, 97, 245 — not in backup
- `wpkl_pool_wpkl_scrape_competitions`: referenced in `scores.php` `$competitions_table` variable — not in backup
- `pool_wpkl_matches` round column: queried in `custom-predictions.php` lines 34, 73 — column absent from CREATE TABLE in backup

### Test cases
1. Before rebuild begins: run `SHOW TABLES` on the live database and diff the result against tables present in the backup — every missing table must be exported before development starts
2. Run `SHOW CREATE TABLE pool_wpkl_matches` on live — confirm whether `round` column exists and capture its type and any index
3. Run the full migration against a clean database and then run every plugin's database query — assert zero "Unknown column" or "Table doesn't exist" MySQL errors
4. Assert `custom_competitions` and `custom_competition_users` have been seeded from live data before any payment or league functionality is tested
5. Assert that schema migrations are version-controlled (e.g. numbered migration files or Flyway/Liquibase) so future schema changes are never again captured only in a one-off backup

---

*Evidence base: `VERIFIED_FINDINGS.md` (claim-by-claim verification against this repository). All file and line references are from committed code, not inferred.*
