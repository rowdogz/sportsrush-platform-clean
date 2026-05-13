# SPORTSRUSH_CANONICAL_RULES.md

## SportsRush 2.0 — Canonical Business Rules Contract

**Purpose:** This document defines the authoritative rules for SportsRush 2.0. It is the single source of truth that both the legacy system and the rebuild must be tested against. Where the current system is ambiguous, contradictory, or incomplete, the rule is marked **NEEDS OWNER DECISION** and must be resolved before development of that section begins.

**Evidence basis:** `VERIFIED_FINDINGS.md` (claim-by-claim code verification) and `HIGH_RISK_AREAS.md` (risk assessment with line-level citations).

---

## SECTION 1 — SCORING ENGINE

---

### 1.1 Correct Score (Exact Match)

**Current behaviour:**  
Custom rankings plugin awards **50 points** when `m.home_score = p.home_score AND m.away_score = p.away_score`. The Football Pool core engine reads `fullpoints` from `wp_options` (default constant `FOOTBALLPOOL_FULLPOINTS`). Both systems are live simultaneously. The custom leaderboard uses 50; the core's stored history may use a different configured value.

**Known issue/risk:**  
Two independent scoring systems can show different totals for the same player. See `VERIFIED_FINDINGS.md §1.1`.

**Canonical rule for SR2.0:**  
An exact correct score (both home and away digits match) is worth **50 points**. This value is system-wide, stored in configuration, and used by every scoring path — leaderboard display, monthly winner, private leagues, and stored history. There is one scoring engine.

**Acceptance criteria:**

- Prediction 3-1, result 3-1 → score contribution = 50
- Prediction 0-0, result 0-0 → score contribution = 50
- No other scoring path (monthly, private, all-time) produces a different total for the same prediction

**Suggested automated tests:**

```
test_exact_score_home_win → predict 3-1, result 3-1 → expect 50 pts base
test_exact_score_draw → predict 1-1, result 1-1 → expect 50 pts base
test_exact_score_away_win → predict 0-2, result 0-2 → expect 50 pts base
test_exact_score_zero_zero → predict 0-0, result 0-0 → expect 50 pts base
```

---

### 1.2 Correct Result (Toto / Outcome)

**Current behaviour:**  
Custom rankings awards **20 points** for the correct outcome (home win / draw / away win) when the score is not exact. The exact-score CASE fires first; the toto CASE has an explicit guard (`WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0`), so the two bonuses are mutually exclusive. A correct score earns 50, not 50+20.

**Canonical rule for SR2.0:**  
The correct result bonus is **20 points**. It is awarded when the predicted outcome (home win, draw, away win) matches the actual outcome, AND the prediction is not an exact score (exact score takes priority and the toto bonus is not additionally awarded).

**Acceptance criteria:**

- Predict 1-0, result 2-0 → toto correct, score not exact → 20 pts (+ applicable bonuses below)
- Predict 3-1, result 3-1 → exact score → 50 pts (toto bonus NOT additionally added)
- Predict 1-0, result 0-1 → wrong outcome → 0 pts from this component

**Suggested automated tests:**

```
test_toto_home_win_not_exact → predict 1-0, result 2-0 → toto=20
test_toto_draw_not_exact → predict 1-1, result 2-2 → toto=20
test_toto_away_win_not_exact → predict 0-1, result 0-3 → toto=20
test_exact_score_excludes_toto → predict 2-1, result 2-1 → total base=50, toto bonus NOT added
test_wrong_outcome → predict 1-0, result 0-1 → toto=0
```

---

### 1.3 Home Score Bonus

**Current behaviour:**  
**10 points** awarded when `m.home_score = p.home_score`, regardless of whether the full score or toto was correct. This bonus stacks with other components. It is awarded independently of the away score.

**Canonical rule for SR2.0:**  
10 points are added when the predicted home team score matches the actual home team score exactly. This bonus is independent — it applies whether the overall prediction was exact, toto-correct, or outcome-wrong.

**Acceptance criteria:**

- Predict 2-1, result 2-3 (home score matches, away doesn't) → home bonus = 10
- Predict 2-1, result 2-1 (exact) → home bonus = 10 (included in total, not double-counted)
- Predict 2-1, result 3-1 (home wrong, away matches) → home bonus = 0, away bonus = 10

**Suggested automated tests:**

```
test_home_bonus_only → predict 2-1, result 2-3 → home_bonus=10, away_bonus=0
test_away_bonus_only → predict 2-1, result 3-1 → home_bonus=0, away_bonus=10
test_both_bonuses → predict 2-1, result 2-1 → home_bonus=10, away_bonus=10
test_no_bonus → predict 2-1, result 3-2 → home_bonus=0, away_bonus=0
```

---

### 1.4 Away Score Bonus

**Current behaviour:**  
**10 points** awarded when `m.away_score = p.away_score`. Symmetric with home score bonus.

**Canonical rule for SR2.0:**  
Same rule as 1.3 applied to the away score. 10 points, independent, stackable.

_(See tests in 1.3 above.)_

---

### 1.5 Goal Difference Bonus

**Current behaviour:**  
**20 points** awarded when the goal difference of the prediction matches the actual goal difference, AND the toto result is correct, AND the prediction is not an exact score. The precise rule in the custom rankings SQL is:

```sql
WHEN ((correct outcome)) AND
     (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
     (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score))
THEN 20
```

The Football Pool core engine has three configurable `diffpoints_rule` modes (0 = classic: toto + no draws; 1 = toto including draws; 2 = all including exact). The custom plugin always uses what is effectively mode 0 logic.

**Known issue/risk:**  
The goal diff bonus rule is applied inconsistently. The Football Pool core can be configured to one of three modes; the custom SQL always uses the classic mode. These can produce different totals for the same match. **NEEDS OWNER DECISION on which mode SR2.0 uses.**

**Canonical rule for SR2.0:**  
**NEEDS OWNER DECISION.** Recommend: the goal difference bonus is **20 points**, awarded when (a) the outcome is correct (toto), (b) the prediction is not an exact score, and (c) the goal margin matches (e.g. predict 2-0, result 3-1 → both have a margin of 2). Drawn predictions are excluded from this bonus unless the owner explicitly decides otherwise.

**Acceptance criteria (pending owner decision on draws):**

- Predict 2-0 (margin 2), result 3-1 (margin 2), outcome correct → diff bonus = 20
- Predict 1-0 (margin 1), result 2-1 (margin 1), outcome correct → diff bonus = 20
- Predict 1-1 (draw), result 2-2 (draw), outcome correct, not exact → diff bonus = **NEEDS OWNER DECISION** (0 or 20?)
- Predict 2-0, result 2-0 (exact score) → diff bonus = 0 (exact score takes priority)
- Predict 2-0, result 3-2 (margin 1 vs 2, outcome correct) → diff bonus = 0

**Suggested automated tests:**

```
test_diff_bonus_home_win → predict 2-0, result 3-1 → diff=20
test_diff_bonus_away_win → predict 0-2, result 1-3 → diff=20
test_diff_bonus_not_on_exact → predict 2-0, result 2-0 → diff=0 (exact takes priority)
test_diff_bonus_wrong_margin → predict 2-0, result 3-2 → diff=0
test_diff_bonus_draw_OWNER_DECISION → predict 1-1, result 2-2 → diff=?
```

---

### 1.6 Joker / Multiplier Behaviour

**Current behaviour:**  
The Football Pool core supports a joker multiplier. When a player marks a match with `has_joker=1` in `pool_wpkl_predictions`, the core's `calc_score()` multiplies the entire match score by `joker_multiplier` (read from `wp_options`). The **custom leaderboard and monthly winner SQL never read `has_joker`** — joker multipliers are silently dropped on the custom-built rankings pages. The joker toggle UI exists only in Football Pool's own default interface, not in the custom predictions plugin.

**Known issue/risk:**  
Players who used jokers have artificially low scores on the custom leaderboard. The magnitude of this discrepancy depends on how many jokers were used historically and what the multiplier is configured to. This is the highest-severity silent data error in the system.

**Canonical rule for SR2.0:**  
**NEEDS OWNER DECISION.** Two options:

- **Option A (Jokers enabled):** The joker is an explicit feature. Players may mark one match per round (or per the configured `jokers_per` rule) as a joker. The entire point total for that match is multiplied by a configurable multiplier (suggested default: 2×). The multiplier must be applied by the single canonical scoring engine — it cannot be applied in some views and not others.

- **Option B (Jokers disabled):** Jokers are removed. `has_joker` column is retired. All players are scored identically per prediction. This simplifies the engine and eliminates the current inconsistency.

**Acceptance criteria (if Option A):**

- A player with `has_joker=1` on match 3-1 (exact score, no other bonuses) → total for that match = (50+10+10) × multiplier
- The same total appears on the main leaderboard, monthly leaderboard, and private league leaderboard
- A player without a joker on the same match → total = 50+10+10

**Suggested automated tests:**

```
test_joker_doubles_score → joker on exact 3-1 → expect 140 (if multiplier=2)
test_no_joker_normal_score → no joker on exact 3-1 → expect 70
test_joker_applies_to_all_views → assert rankings page == private league page for same user+match
test_joker_limit_per_round → attempt to set joker on two matches in same round → second is rejected
```

---

### 1.7 Abandoned / Postponed / Void Fixtures

**Current behaviour:**  
No handling exists for abandoned or postponed matches. If `home_score` and `away_score` remain NULL, the match simply does not contribute to any score calculation (the WHERE clause `m.home_score IS NOT NULL AND m.away_score IS NOT NULL` filters it out). There is no status column on `pool_wpkl_matches` in the backup schema.

**Canonical rule for SR2.0:**  
**NEEDS OWNER DECISION.** Recommend: matches have an explicit `status` field (`scheduled`, `completed`, `postponed`, `abandoned`, `void`). Scoring rules per status:

- `completed` — scored normally
- `postponed` — predictions carried over if rescheduled; no points until completed
- `abandoned` — **NEEDS OWNER DECISION**: award points for play that occurred, or void the match entirely?
- `void` — no points awarded; prediction is not shown in any calculation

**Acceptance criteria:**

- A match with status `postponed` contributes 0 points and is not shown in any ranking calculation
- A match with status `void` contributes 0 points even if scores were partially entered
- A match with status `completed` follows normal scoring rules

---

### 1.8 Results Edited After Points Are Calculated

**Current behaviour:**  
The custom rankings plugin recalculates all points live from `pool_wpkl_matches` and `pool_wpkl_predictions` on every page load. If a score is corrected in the DB, the rankings update automatically. The Football Pool core's `pool_wpkl_scorehistory` tables store calculated history and must be explicitly recalculated via WP-CLI (`wp football-pool calc`). These two systems can therefore show different totals after a correction.

**Canonical rule for SR2.0:**  
When a result is corrected, recalculation must be automatic, complete, and apply to all views simultaneously. No manual WP-CLI step should be required for a correction to be reflected. The system must log result corrections with a timestamp, the previous score, the new score, and the user who made the change (audit trail).

**Acceptance criteria:**

- Update `home_score` on a past match → within one page load, all leaderboards reflect the corrected points
- The correction is logged with before/after values and editor identity
- Points adjustments from the correction are visible in the admin panel

---

## SECTION 2 — RANKINGS

---

### 2.1 Total Points Calculation

**Current behaviour:**  
Total points = sum of all scoring components (§1.1–1.5, and §1.6 if jokers enabled) across all scored matches in the selected competition, where `home_score IS NOT NULL AND away_score IS NOT NULL`. Recalculated live on each page load.

**Canonical rule for SR2.0:**  
A user's total points for a competition is the sum of their canonical score (per §1.1–1.6) across all completed, non-void matches in that competition where the user submitted a prediction. This value must be consistent across all views (main leaderboard, private leagues, monthly view, profile page).

**Acceptance criteria:**

- Player with 10 predictions across a season, all scoreable, sees the same total on every page that displays their points
- A player with zero predictions shows 0 points (not absent from the table)

---

### 2.2 Rank Ordering

**Current behaviour:**  
Ordered by `total_points DESC` using `DENSE_RANK()`. No tiebreaker beyond points is currently implemented.

**Canonical rule for SR2.0:**  
Primary sort: `total_points DESC`. Tiebreaker order: **NEEDS OWNER DECISION**. Recommended tiebreaker sequence: (1) most correct exact scores, (2) most correct outcomes (toto), (3) alphabetical by display name. The tiebreaker must be defined before launch and consistently applied.

**Acceptance criteria:**

- Two players with identical points receive the same rank number
- The rank after a tie group skips no numbers (DENSE_RANK behaviour: 1, 2, 2, 3 — not 1, 2, 2, 4)
- Tiebreaker is applied consistently when points are equal

---

### 2.3 Tie Handling

**Current behaviour:**  
`DENSE_RANK()` confirmed in code. Two players with equal points share the same rank. The next rank is sequential (not skipped).

**Canonical rule for SR2.0:**  
DENSE_RANK behaviour is mandatory. Ties share a rank. The following rank is consecutive (1, 2, 2, 3 — never 1, 2, 2, 4).

**Acceptance criteria:**

- Seed three players: A=100pts, B=100pts, C=90pts → ranks: A=1, B=1, C=2
- Seed four players: A=100, B=90, C=90, D=80 → ranks: A=1, B=2, C=2, D=3

---

### 2.4 Competition Filter

**Current behaviour:**  
Rankings are filtered by `matchtype_id` from `pool_wpkl_matchtypes`. Only competitions with `visibility=1` appear in the filter dropdown. The selected competition is passed as a GET parameter (`?competition=N`).

**Canonical rule for SR2.0:**  
A user can filter rankings to any visible competition. The default competition shown on first load is deterministic (e.g. alphabetically first, or most recently active — **NEEDS OWNER DECISION** on default). Competition visibility is admin-controlled. URL state is shareable (the selected competition is preserved in the URL).

**Acceptance criteria:**

- A competition with `visibility=0` does not appear in any dropdown or rankings view
- Changing the competition filter updates all displayed data (rankings table, stats panel, monthly winner)
- The URL reflects the current competition selection

---

### 2.5 Private League Filters

**Current behaviour:**  
Private leagues use a separate shortcode (`private_league_rankings`) and a separate set of tables (`custom_competitions`, `custom_competition_users`). Rankings within a private league use the same 50/20/10/10/20 formula applied to only the matchtype associated with that league. Only league members see the private league rankings.

**Canonical rule for SR2.0:**  
Private league rankings use the same canonical scoring engine as public rankings (§1.1–1.6). Scope is restricted to the `matchtype_id` assigned to the league. Access requires confirmed membership. A non-member who navigates directly to a private league URL sees an access-denied message, not an empty table.

---

### 2.6 Monthly Rankings

**Current behaviour:**  
The main rankings table includes a `current_month_points` column, calculated live using `MONTH(NOW())` and `YEAR(NOW())` to filter match dates. This column changes meaning as the calendar month rolls.

**Canonical rule for SR2.0:**  
The monthly points column shows points earned in the **current calendar month to date** (from 00:00 on the 1st of the current month to the moment of the request). It is clearly labelled with the month name (e.g. "May Points"). A separate all-time total is always visible alongside it.

**Acceptance criteria:**

- On 1 May at 00:01, the column resets to 0 for all users (no matches in May yet)
- A match played on 30 April contributes to April totals, not May
- The column header clearly shows which month is being displayed

---

### 2.7 Monthly Winner Calculation

**Current behaviour:**  
Monthly Winner = the player with the highest points from matches whose `play_date` falls within the previous calendar month (first day to last day of last month, confirmed from code). Calculated live, shown in the stats panel. Some competitions (IDs 32 and 34) are excluded from Monthly Winner display by a hard-coded array.

**Canonical rule for SR2.0:**  
Monthly Winner is defined as the player with the highest points from completed, non-void matches whose `play_date` falls within the **full previous calendar month** (midnight on the 1st to 23:59:59 on the last day). In the event of a tie for Monthly Winner, **NEEDS OWNER DECISION** (both names shown / coin flip / head-to-head?). Competition exclusions from Monthly Winner display must be admin-configurable, not hard-coded.

**Acceptance criteria:**

- A match on the first day of last month is included
- A match on the last day of last month is included
- A match on the first day of two months ago is excluded
- A match on the first day of the current month is excluded
- Monthly Winner calculation uses the same formula as all other rankings (no separate engine)

---

## SECTION 3 — PREDICTION LOCKING

---

### 3.1 Server-Side Lock Rule

**Current behaviour:**  
There is **no server-side prediction lock**. The save handler (`save_prediction_handler`) accepts predictions for any match regardless of kick-off time. The 30-minute cutoff is applied only in the SQL that populates the frontend display.

**Known issue/risk:**  
Any logged-in user can save a prediction for a match that has already started or finished by POSTing directly to `admin-ajax.php`. See `VERIFIED_FINDINGS.md §5.3`.

**Canonical rule for SR2.0:**  
The prediction save endpoint **must** validate the match kick-off time server-side. A prediction for a match whose `play_date` is less than 30 minutes in the future (or in the past) must be rejected with an error response. This check must occur in the server-side handler, not only in the UI.

**Acceptance criteria:**

- POST `save_prediction` for a match kicking off in 31 minutes → accepted
- POST `save_prediction` for a match kicking off in 29 minutes → rejected with clear error
- POST `save_prediction` for a match that kicked off yesterday → rejected
- POST `save_prediction` with valid match (future) from a different user's session → rejected (user ID from session, not from POST body)

---

### 3.2 Client-Side Display Rule

**Current behaviour:**  
The predictions page only loads matches where `TIMESTAMP(m.play_date) > (NOW() + INTERVAL 30 MINUTE)`. Matches within 30 minutes or in the past do not appear in the predictions form.

**Canonical rule for SR2.0:**  
The frontend must not display editable prediction inputs for locked matches. A locked match may still be displayed (as read-only) so the user can see their prediction and the eventual result. The display state must update when the user navigates to the page — it must not rely solely on the initial server render for time-sensitive logic.

**Acceptance criteria:**

- A match 35 minutes away shows editable score inputs
- A match 25 minutes away shows a locked/read-only state (no input fields, or fields are disabled with clear messaging)
- A match from yesterday shows the user's submitted prediction and the actual result

---

### 3.3 Exact Cutoff Time

**Current behaviour:**  
Cutoff is `NOW() + INTERVAL 30 MINUTE` in MySQL. The cutoff is relative to the database server clock.

**Canonical rule for SR2.0:**  
Predictions are locked **30 minutes before the match kick-off time** as stored in the database. The lock applies at the second the server processes the request — there is no grace period beyond the 30-minute window. Configuration of this window must be stored in the application config (not hard-coded) so it can be adjusted by an administrator without a code deploy.

---

### 3.4 Timezone Handling

**Current behaviour:**  
Match times are stored as `datetime` in UTC in `pool_wpkl_matches.play_date`. The custom predictions plugin converts them to `Europe/London` time for display (`DateTimeZone('Europe/London')`). Lock comparison uses MySQL's `NOW()` which is also in UTC.

**Canonical rule for SR2.0:**  
All match times are stored in UTC. All lock calculations occur in UTC. Display conversion to the user's local timezone (default: `Europe/London`) is done at render time. Daylight Saving Time transitions (BST ↔ GMT) must be handled correctly — a match at 19:00 BST is stored as 18:00 UTC, and the lock fires at 17:30 UTC.

**Acceptance criteria:**

- A match scheduled for 19:00 BST (18:00 UTC) locks at 17:30 UTC (30 minutes before kick-off in UTC)
- The predictions page displays the correct local time to the user (19:00 BST, not 18:00 UTC)
- A BST-to-GMT transition does not cause a match to lock at the wrong time

---

### 3.5 Admin Override Rules

**Current behaviour:**  
No admin override mechanism exists. There is no way to re-open predictions for a locked match without manually updating the `play_date` in the database.

**Canonical rule for SR2.0:**  
Administrators with the appropriate role must be able to:

1. Re-open predictions for a specific match (move its lock time or override the lock state)
2. Lock predictions for a specific match early (before the 30-minute window)
3. Enter or correct results on behalf of users (with a logged audit trail)

All admin overrides are logged.

---

## SECTION 4 — FIXTURES AND RESULTS

---

### 4.1 Fixture Creation

**Current behaviour:**  
Fixtures are created through the Football Pool WordPress admin UI. The `play_date` is stored in UTC. The `round` column (absent from the backup but present in live code) is used to group matches.

**Canonical rule for SR2.0:**  
Fixtures are created with at minimum: `home_team_id`, `away_team_id`, `matchtype_id`, `play_date` (UTC), `round` (integer, required), and `status` (see §1.7). Fixtures without a round number must not be accepted — round is mandatory. Duplicate fixtures (same home team, away team, competition, and date) must be rejected.

---

### 4.2 Fixture Updates

**Current behaviour:**  
Fixture rescheduling is done manually through the WordPress admin. If `play_date` is changed after predictions have been made, predictions are silently preserved but the lock window shifts accordingly.

**Canonical rule for SR2.0:**  
When a fixture's `play_date` is updated:

- If the new kick-off is in the future and the match was not previously locked: no user action required, predictions are preserved
- If the match was locked (predictions closed): **NEEDS OWNER DECISION** — should previously locked predictions be re-opened?
- If the match has been scored (result entered): a reschedule should not be possible without first clearing the result (to prevent accidental data corruption)
- All rescheduling events are logged

---

### 4.3 Team Aliases

**Current behaviour:**  
Team names from the BBC scraper are normalised (lowercase, trim, collapse whitespace) and matched against a hard-coded PHP array in `scores.php`. The database-driven aliases table (`pool_wpkl_team_aliases`) is referenced in comments but does not exist (`$team_aliases_table = null`). Unmatched names produce silent null scores.

**Canonical rule for SR2.0:**  
Team aliases are stored in a database table (`team_aliases`), not in code. The alias table has at minimum: `id`, `external_name` (normalised), `team_id` (FK to teams table), `source` (e.g. `bbc`, `rlcom`). The results updater must log a warning for any team name that cannot be matched. An admin interface must allow adding new aliases without a code deploy.

**Acceptance criteria:**

- A BBC team name not in the alias table → warning logged with the unmatched string → no score written → admin is notified
- A new alias added via admin → the next scraper run resolves the team correctly
- Alias matching is case-insensitive

---

### 4.4 Round Handling

**Current behaviour:**  
The `round` column exists in the live `pool_wpkl_matches` table (added after the SQL backup). The custom predictions plugin groups matches by `round` integer and loads one round at a time. Round numbers are integers with no display name beyond "Round N".

**Canonical rule for SR2.0:**  
Every match must belong to a round. The round has: `round_number` (integer), `competition_id`, and optionally `round_name` (display string, e.g. "Round 1", "Grand Final" — **NEEDS OWNER DECISION** on whether named rounds are required). Rounds can be browsed forward and backward from the predictions page. A competition with no future rounds displays a clear message rather than an empty page.

---

### 4.5 Duplicate Prevention

**Current behaviour:**  
No duplicate prevention exists on the fixture creation side. The database schema has no unique constraint on `(home_team_id, away_team_id, matchtype_id, play_date)`.

**Canonical rule for SR2.0:**  
A unique constraint on `(home_team_id, away_team_id, matchtype_id, play_date)` must be applied at the database level. The application layer must catch the constraint violation and return a clear error to the admin rather than a generic database error.

---

### 4.6 Result Updates

**Current behaviour:**  
Results are entered via the Football Pool WordPress admin UI or automatically by the scraper scripts. Scores are written directly to `pool_wpkl_matches.home_score` and `pool_wpkl_matches.away_score`. The custom rankings recalculate automatically. The Football Pool core scorehistory must be recalculated manually.

**Canonical rule for SR2.0:**  
When a result is entered or updated:

1. `home_score` and `away_score` are written atomically
2. All affected rankings (overall, monthly, private league) are invalidated and recalculated
3. No manual recalculation step is required
4. The result write is a privileged admin action (not accessible to regular users)

---

### 4.7 Correction of Historical Results

**Current behaviour:**  
Overwriting `home_score`/`away_score` in `pool_wpkl_matches` immediately changes the live SQL-based rankings with no audit trail.

**Canonical rule for SR2.0:**  
Historical result corrections are logged with: original score, corrected score, timestamp, and admin user ID. Users are not notified automatically of corrections, but the change is visible in an admin audit log. Points recalculation following a correction applies to all affected views.

---

## SECTION 5 — PRIVATE LEAGUES

---

### 5.1 Joining Rules

**Current behaviour:**  
Two join paths exist: (a) admin manually adds a user via the private leagues admin panel, (b) user purchases a WooCommerce product linked to the league. In both cases, a row is inserted into `custom_competition_users`.

**Canonical rule for SR2.0:**  
A user can join a private league by:

- Direct admin grant (no payment required)
- Purchase of the linked WooCommerce product (payment path)
- **NEEDS OWNER DECISION:** invitation link / invite code mechanism?

League visibility rules:

- Public (listed in catalogue): any user can see the league exists and join if eligible
- Private (unlisted): only users with a direct link or admin grant can access
- **NEEDS OWNER DECISION:** should there be an approval step (join request), or is payment/invite immediate access?

---

### 5.2 Payment / Access Grant

**Current behaviour:**  
`sr_handle_wc_order_paid` fires on both `woocommerce_order_status_processing` and `woocommerce_order_status_completed`. Access is granted by inserting a row into `custom_competition_users` via `sr_grant_league_access()`. The insert has no duplicate guard.

**Canonical rule for SR2.0:**  
Access is granted exactly once per user per league, regardless of how many times the payment hook fires. The grant function must be idempotent: if a membership row already exists for `(user_id, league_id)`, the function returns success without inserting a duplicate. The database must enforce a unique constraint on `(user_id, custom_competition_id)`.

**Acceptance criteria:**

- Purchasing a league twice (e.g. page refresh during checkout) results in exactly one membership row
- Access is granted within one WooCommerce status transition (not delayed)
- A user who is manually added by admin and later also purchases is not double-listed

---

### 5.3 Duplicate Payment Protection

**Current behaviour:**  
No protection. Duplicate WooCommerce orders would create duplicate membership rows.

**Canonical rule for SR2.0:**  
Before inserting a membership, the system checks for an existing row. If one exists, the insert is skipped and the order is marked as fulfilled. The WooCommerce order is tagged with the `sr_league_id` in meta to allow idempotent re-processing. A database-level unique constraint on `(user_id, custom_competition_id)` provides the final safety net.

---

### 5.4 Refunds / Cancellations

**Current behaviour:**  
No refund handling exists. If an order is refunded in WooCommerce, league access is not revoked automatically.

**Canonical rule for SR2.0:**  
**NEEDS OWNER DECISION.** Options:

- **Option A:** Refunds revoke access. A WooCommerce refund/cancellation hook fires `sr_revoke_league_access()`.
- **Option B:** Refunds are handled manually. No automatic revocation.

Regardless of the chosen option, an admin tool to manually revoke league access must exist.

---

### 5.5 League Membership Permissions

**Current behaviour:**  
Members can view the private league rankings. No further permissions tiers exist within a private league. Admins manage leagues via the WordPress admin panel.

**Canonical rule for SR2.0:**  
**NEEDS OWNER DECISION on permission tiers.** Minimum required:

- `member` — can view the league's rankings and submit predictions within the linked competition
- `admin` (site-wide) — can create/edit/delete leagues, manage membership, set prices

Optional (decide before build):

- `league_owner` — a named user who can manage membership for their own league but not others

---

## SECTION 6 — SECURITY

---

### 6.1 CSRF Protection

**Current behaviour:**  
The `sr_load_round` AJAX handler uses `wp_verify_nonce`. The `save_prediction` AJAX handler has **no nonce**. See `VERIFIED_FINDINGS.md §5.4`.

**Canonical rule for SR2.0:**  
Every state-changing AJAX endpoint must verify a WordPress nonce before processing. Read-only endpoints (e.g. loading match data) must verify the user is authenticated. Nonces must be scoped to the specific action (e.g. `sr_save_prediction_nonce` — not a generic global nonce). Nonces are embedded in the page HTML and passed in every AJAX request.

**Acceptance criteria:**

- POST to any state-changing endpoint without a nonce → 403
- POST with an expired nonce → 403
- POST with a valid nonce from a different action → 403
- POST with a valid nonce for the correct action → processed normally

---

### 6.2 Authentication Required Endpoints

**Current behaviour:**  
`wp_ajax_save_prediction` and `wp_ajax_sr_load_round` are registered under `wp_ajax_` (logged-in users only). No `wp_ajax_nopriv_` variants exist, so unauthenticated requests receive a WordPress error response.

**Canonical rule for SR2.0:**  
All prediction, ranking write, and private league endpoints require an authenticated session. Any endpoint registered with `wp_ajax_nopriv_` must be explicitly reviewed and justified. The list of public endpoints must be documented.

**Public endpoints (unauthenticated access permitted):**

- Read-only public rankings display (if public competitions exist)

**Authenticated endpoints (session required):**

- Save prediction
- Load round
- Access private league rankings
- Any admin action

---

### 6.3 Admin-Only Endpoints

**Current behaviour:**  
No capability checks exist on the private leagues admin functions visible in the plugin code. It is assumed WordPress admin role restriction applies at the menu level, but this was not confirmed at the endpoint level.

**Canonical rule for SR2.0:**  
Every admin action endpoint must check `current_user_can('manage_options')` (or a custom capability) at the start of the function. This check must occur server-side on every request, not only when registering the admin menu item.

**Acceptance criteria:**

- A logged-in subscriber-level user directly POSTing to an admin action endpoint → 403
- An admin user POSTing to the same endpoint → processed
- Menu-level restriction alone is not accepted as the security boundary

---

### 6.4 Secrets / Credentials Handling

**Current behaviour:**  
Database credentials are hard-coded in 8+ script files inside `public_html/`. The password `WhuiMoFs0X` appears in plain text in committed source files. See `VERIFIED_FINDINGS.md §9.1`.

**Canonical rule for SR2.0:**  
No credentials, API keys, database passwords, or secrets of any kind are stored in source code or committed to version control. All credentials are read from environment variables or a `.env` file that is:

- Listed in `.gitignore`
- Stored outside `public_html/` (above the web root)
- Rotatable without requiring a code change or deploy

**Acceptance criteria:**

- A grep of the entire repository for the current DB password returns zero matches
- A grep for any hardcoded password pattern (e.g. `"password".*:.*"`) returns zero hits in application code
- Rotating the DB password requires changing one value in one location only

---

### 6.5 Public Cron Protection

**Current behaviour:**  
`run_cron_job.php` is located at `public_html/run_cron_job.php` with no authentication check, no nonce, and no IP restriction. It calls `shell_exec()` and echoes the result to the browser. Additionally, it uses the wrong server path (`/home3/editor/scripts/`) and would not function correctly on the production server. See `VERIFIED_FINDINGS.md §8.1, §8.2`.

**Canonical rule for SR2.0:**  
No cron-triggering endpoint may be accessible via HTTP without authentication. Options in order of preference:

1. **Server-side cron** (`crontab` on the host) calling scripts directly — no HTTP endpoint needed
2. **WP-CLI** invoked from server cron — no HTTP endpoint needed
3. **Protected HTTP endpoint** — requires a secret token in the request header that is not stored in code; IP-restricted at the server level

The current `run_cron_job.php` file must be deleted before launch.

**Acceptance criteria:**

- A GET request to `run_cron_job.php` (or any cron endpoint) without valid credentials → 403 or 404
- Cron scripts do not echo output to the browser under any circumstances
- All cron script paths are correct for the production server environment

---

## SECTION 7 — DATABASE BASELINE

---

### 7.1 Required Production Tables

**Current behaviour:**  
The committed SQL backup (`sportsrush_db_backup.sql`) is missing at least four tables present in the live production database. A rebuild seeded from the backup would be immediately broken.

**Canonical rule for SR2.0:**  
Before any rebuild work begins, the following must be exported from the live production database and committed as migration files:

| Table                                | Status in backup            | Status in live                | Required for SR2.0                             |
| ------------------------------------ | --------------------------- | ----------------------------- | ---------------------------------------------- |
| `pool_wpkl_matches`                  | Present (incomplete schema) | Present (has `round` column)  | Yes — re-export live schema                    |
| `pool_wpkl_predictions`              | Present                     | Present                       | Yes                                            |
| `pool_wpkl_matchtypes`               | Present                     | Present                       | Yes                                            |
| `pool_wpkl_scorehistory_s1_t1`       | Present                     | Present                       | Conditional — if core ranking history retained |
| `pool_wpkl_scorehistory_s1_t2`       | Present                     | Present                       | Conditional                                    |
| `pool_wpkl_rankings`                 | Present                     | Present                       | Conditional                                    |
| `custom_competitions`                | **MISSING**                 | Present                       | Yes — private leagues                          |
| `custom_competition_users`           | **MISSING**                 | Present                       | Yes — private leagues                          |
| `wpkl_pool_wpkl_scrape_competitions` | **MISSING**                 | Present                       | Yes — scraper control                          |
| `pool_wpkl_team_aliases`             | **MISSING**                 | Likely missing (null in code) | Yes — create fresh in SR2.0                    |

---

### 7.2 Required Columns

**Current behaviour:**  
The `round` column is absent from the backup of `pool_wpkl_matches` but present in the live system. A `status` column does not exist in the backup or (likely) in the live table.

**Canonical rule for SR2.0:**  
The following columns are required in SR2.0 that may not exist in the current live schema:

| Table                      | Column       | Type                                                           | Notes                                |
| -------------------------- | ------------ | -------------------------------------------------------------- | ------------------------------------ |
| `matches`                  | `round`      | `INT NOT NULL`                                                 | Already in live, absent from backup  |
| `matches`                  | `status`     | `ENUM('scheduled','completed','postponed','abandoned','void')` | New — not in current system          |
| `matches`                  | `round_name` | `VARCHAR(100) NULL`                                            | **NEEDS OWNER DECISION**             |
| `custom_competitions`      | `status`     | `ENUM('active','closed','archived')`                           | **NEEDS OWNER DECISION**             |
| `custom_competition_users` | `granted_at` | `DATETIME`                                                     | Audit trail                          |
| `team_aliases`             | Full table   | —                                                              | New table — replaces hardcoded array |
| All result tables          | `updated_at` | `DATETIME`                                                     | Audit trail for score corrections    |

---

### 7.3 Migration / Versioning Approach

**Current behaviour:**  
No migration system exists. Schema changes have been applied manually (evidenced by the `round` column existing in live but not in the backup). There are no numbered migration files, no Flyway/Liquibase configuration, and no record of what schema changes have been applied to which environment.

**Canonical rule for SR2.0:**  
All schema changes must be expressed as numbered, sequential migration files (e.g. `001_initial_schema.sql`, `002_add_round_column.sql`). The migration runner must record which migrations have been applied in a `schema_migrations` table. Applying migrations must be idempotent (running the same migration twice has no effect). Production migrations must be run explicitly by a human (not automatically on deploy) with a backup taken beforehand.

**Acceptance criteria:**

- Running all migration files against a clean database produces a schema that the application operates correctly against, with zero manual steps
- Running migrations against the live database with its current state applies only the delta (new migrations not yet run)
- A record of applied migrations is queryable from the database

---

### 7.4 Backup Validation Rules

**Current behaviour:**  
The committed `sportsrush_db_backup.sql` is known to be incomplete. There is no validation that a backup is complete before it is used.

**Canonical rule for SR2.0:**  
Before any backup is used to seed an environment, it must be validated against a checklist:

1. All tables in `7.1 Required Production Tables` are present in the backup
2. All required columns in `7.2 Required Columns` are present in the relevant `CREATE TABLE` statements
3. Row counts for key tables (`matches`, `predictions`, `custom_competition_users`) are greater than zero
4. The backup timestamp is less than 24 hours old for any migration or seeding operation

A validation script must perform these checks automatically and refuse to proceed if any check fails.

---

## APPENDIX — NEEDS OWNER DECISION TRACKER

The following items require a decision from the SportsRush owner before the relevant section can be built. This list must be resolved before development of that feature begins.

| ID    | Section | Question                                                                                                                    |
| ----- | ------- | --------------------------------------------------------------------------------------------------------------------------- |
| OD-01 | §1.5    | Which goal difference bonus mode? Classic (toto, no draws) / toto including draws / all including exact?                    |
| OD-02 | §1.6    | Are jokers enabled in SR2.0? If yes: what is the multiplier? How many jokers per user per round?                            |
| OD-03 | §1.7    | What happens to predictions for abandoned matches? Points for play that occurred, or void?                                  |
| OD-04 | §2.2    | What is the tiebreaker for equal points? Suggested: most correct scores → most toto → alphabetical                          |
| OD-05 | §2.4    | Which competition is shown by default on the rankings page? Alphabetically first / most recently active / admin-configured? |
| OD-06 | §2.7    | In a Monthly Winner tie, what happens? Both names shown / no winner declared / head-to-head?                                |
| OD-07 | §3.2    | Are locked matches shown as read-only on the predictions page, or hidden entirely?                                          |
| OD-08 | §4.2    | If a fixture is rescheduled after predictions were locked, are those predictions re-opened?                                 |
| OD-09 | §4.4    | Are round names (e.g. "Grand Final", "Semi-Final") required, or is "Round N" sufficient?                                    |
| OD-10 | §5.1    | Is an invitation/invite-code join mechanism required for private leagues?                                                   |
| OD-11 | §5.1    | Is there a join approval step (request + accept), or is payment/invite immediate access?                                    |
| OD-12 | §5.4    | Do WooCommerce refunds automatically revoke league access?                                                                  |
| OD-13 | §5.5    | Is a league-owner role required (can manage their own league's membership)?                                                 |
| OD-14 | §7.2    | Is a `round_name` column required on matches (for named rounds)?                                                            |
| OD-15 | §7.2    | Is a status column required on `custom_competitions` (active/closed/archived)?                                              |

---

_This document version covers evidence from the committed repository as of May 2026. It must be updated when Owner Decisions are made and before each feature development phase begins._
