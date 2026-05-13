# SportsRush — Database and Data Flow

## Database Overview

- **Engine:** MariaDB 10.11 on Hostinger shared hosting
- **Database name:** `u108848352_KDqxs`
- **Host:** `127.0.0.1` (localhost, IPv4 forced for driver compatibility)
- **WordPress table prefix:** `wpkl_`
- **Football Pool plugin prefix:** `pool_wpkl_`
- **Private leagues custom prefix:** `custom_` (no prefix, bespoke tables)

The database is a MariaDB instance shared with other Hostinger accounts. It uses UTF8MB4 charset throughout. There are no foreign key constraints enforced — referential integrity is managed at the application layer only.

---

## Table Groups

### WordPress Core Tables (prefix: `wpkl_`)

| Table                                                         | Purpose                                                                                        |
| ------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| `wpkl_users`                                                  | WordPress user accounts (ID, login, email, password hash, display name)                        |
| `wpkl_usermeta`                                               | User metadata (roles, custom fields, paid entitlements stored as `sr_league_paid_<league_id>`) |
| `wpkl_posts`                                                  | Pages, posts, WooCommerce products                                                             |
| `wpkl_postmeta`                                               | Post metadata including WooCommerce order meta (`sr_league_id`)                                |
| `wpkl_options`                                                | All WordPress and plugin settings (including Football Pool options)                            |
| `wpkl_terms`, `wpkl_term_taxonomy`, `wpkl_term_relationships` | WooCommerce product categories, tags                                                           |
| `wpkl_comments`, `wpkl_commentmeta`                           | Standard WP comments (rarely used)                                                             |

### Football Pool Plugin Tables (prefix: `pool_wpkl_`)

These are the core operational tables of the platform.

#### `pool_wpkl_matches`

The central fixture/result table.

| Column         | Type            | Description                                      |
| -------------- | --------------- | ------------------------------------------------ |
| `id`           | INT UNSIGNED PK | Unique match ID                                  |
| `stadium_id`   | INT             | FK → `pool_wpkl_stadiums.id` (defaults to 2)     |
| `home_team_id` | INT             | FK → `pool_wpkl_teams.id`                        |
| `away_team_id` | INT             | FK → `pool_wpkl_teams.id`                        |
| `home_score`   | INT             | NULL until result is available                   |
| `away_score`   | INT             | NULL until result is available                   |
| `play_date`    | DATETIME        | Stored in UTC                                    |
| `matchtype_id` | INT             | FK → `pool_wpkl_matchtypes.id` (competition)     |
| `round`        | INT             | Numeric round number (optional, added via ALTER) |
| `round_name`   | VARCHAR         | Display name e.g. "Round 10" or "Quarter Final"  |
| `round_order`  | INT             | Sort order for rounds                            |

#### `pool_wpkl_teams`

| Column      | Type            | Description                          |
| ----------- | --------------- | ------------------------------------ |
| `id`        | INT UNSIGNED PK | Team ID                              |
| `name`      | VARCHAR         | Canonical team name                  |
| `photo`     | VARCHAR         | Team logo/badge image path           |
| `flag`      | VARCHAR         | Country/region flag image            |
| `link`      | VARCHAR         | External link                        |
| `group_id`  | INT             | Optional grouping (e.g. group stage) |
| `is_active` | TINYINT         | Whether team is active               |

#### `pool_wpkl_matchtypes`

Competition/league definitions.

| Column       | Type    | Description                                 |
| ------------ | ------- | ------------------------------------------- |
| `id`         | INT PK  | Competition ID                              |
| `name`       | VARCHAR | Competition name (e.g. "Super League 2025") |
| `visibility` | TINYINT | Whether shown in rankings dropdown          |

#### `pool_wpkl_predictions`

User predictions for each match.

| Column       | Type    | Description                   |
| ------------ | ------- | ----------------------------- |
| `user_id`    | INT     | FK → `wpkl_users.ID`          |
| `match_id`   | INT     | FK → `pool_wpkl_matches.id`   |
| `home_score` | INT     | User's predicted home score   |
| `away_score` | INT     | User's predicted away score   |
| `has_joker`  | TINYINT | 1 if joker multiplier applied |

Composite PK on `(user_id, match_id)`.

#### `pool_wpkl_scorehistory_s1_t1` and `pool_wpkl_scorehistory_s1_t2`

Calculated score snapshots. The plugin uses two alternating tables (t1 and t2) — one is "active" (being read), the other is being written during a recalculation, then they swap. This avoids showing partial results during calculation.

#### `pool_wpkl_rankings`

Defines separate ranking contexts (e.g. "Super League 2025 Overall", "Super League 2025 Monthly").

| Column | Type    | Description          |
| ------ | ------- | -------------------- |
| `id`   | INT PK  | Ranking ID           |
| `name` | VARCHAR | Ranking display name |

#### `pool_wpkl_rankings_matches` and `pool_wpkl_rankings_bonusquestions`

Many-to-many join tables linking rankings to which matches/questions they include.

#### `pool_wpkl_leagues` and `pool_wpkl_league_users`

The Football Pool plugin's built-in league grouping (separate from the custom `private_league_rankings` plugin).

| Table                    | Key Columns                 |
| ------------------------ | --------------------------- |
| `pool_wpkl_leagues`      | `id`, `name`, `description` |
| `pool_wpkl_league_users` | `user_id`, `league_id`      |

#### `pool_wpkl_bonusquestions`

| Column               | Type     | Description              |
| -------------------- | -------- | ------------------------ |
| `id`                 | INT PK   | Question ID              |
| `question`           | TEXT     | Question text            |
| `answer`             | TEXT     | Correct answer           |
| `points`             | SMALLINT | Points awarded           |
| `answer_before_date` | DATETIME | Deadline for answers     |
| `score_date`         | DATETIME | When scoring was applied |
| `match_id`           | INT      | Optional link to a match |
| `question_order`     | SMALLINT | Display sort order       |

#### `pool_wpkl_bonusquestions_useranswers`

| Column        | Type    | Description             |
| ------------- | ------- | ----------------------- |
| `question_id` | INT     | FK → bonusquestions     |
| `user_id`     | INT     | FK → wpkl_users         |
| `answer`      | TEXT    | User's submitted answer |
| `correct`     | TINYINT | 1 if correct            |
| `points`      | INT     | Points awarded          |

#### `pool_wpkl_stadiums`

Venue data. Rarely used — most fixtures default to `stadium_id = 2`.

#### `pool_wpkl_team_aliases`

Maps scraped names to canonical team IDs.

| Column       | Type    | Description                              |
| ------------ | ------- | ---------------------------------------- |
| `id`         | INT PK  | Alias ID                                 |
| `alias_name` | VARCHAR | Name as seen by scraper (e.g. "Hull KR") |
| `team_id`    | INT     | FK → `pool_wpkl_teams.id`                |

Unique constraint on `alias_name`.

#### `pool_wpkl_scrape_competitions` (created by `scraper-competitions-admin` plugin, prefixed `wpkl_pool_wpkl_` in some contexts)

Controls which competitions the scrapers process.

| Column       | Type    | Description                                  |
| ------------ | ------- | -------------------------------------------- |
| `id`         | INT PK  | Row ID                                       |
| `bbc_title`  | VARCHAR | Competition title as it appears on BBC Sport |
| `db_name`    | VARCHAR | Matches `pool_wpkl_matchtypes.name`          |
| `rlcom_url`  | VARCHAR | RL.com match centre URL for this competition |
| `start_date` | DATE    | Optional date range start (NULL = always)    |
| `end_date`   | DATE    | Optional date range end (NULL = always)      |
| `active`     | TINYINT | 1 = scraper processes this competition       |

#### `pool_wpkl_seasons`

Season metadata. Used to scope rankings to a specific year/season.

#### `pool_wpkl_shoutbox`

Simple chat/shoutbox messages visible on the site.

#### `pool_wpkl_predictions_audit_log`

Full audit trail of prediction changes (who changed what, when).

### Private Leagues Custom Tables

Created by the `private_league_rankings` plugin. Not in the Football Pool schema.

| Table                      | Purpose                                                                     |
| -------------------------- | --------------------------------------------------------------------------- |
| `custom_competitions`      | Private league definitions (`id`, `name`, `is_paid`, `wc_product_id`, etc.) |
| `custom_competition_users` | User membership (`user_id`, `custom_competition_id`, `created_at`)          |

Paid access entitlement is stored in `wpkl_usermeta` as `sr_league_paid_<league_id> = 1`.

### Supporting Plugin Tables

| Table Group                | Plugin                                                    |
| -------------------------- | --------------------------------------------------------- |
| `wpkl_mailpoet_*`          | MailPoet — newsletter subscribers, campaigns, automations |
| `wpkl_fsmpt_email_logs`    | FluentSMTP — outbound email logs                          |
| `wpkl_actionscheduler_*`   | WordPress Action Scheduler — background job queue         |
| `wpkl_aioseo_*`            | All-in-One SEO — post SEO metadata                        |
| `wpkl_wpforms_*`           | WPForms — form submissions and payment logs               |
| `wpkl_fa_user_logins`      | User Login History plugin                                 |
| `wpkl_ewwwio_*`            | EWWW Image Optimizer                                      |
| `wpkl_duplicator_packages` | Duplicator backup packages                                |

---

## Key Relationships

```
wpkl_users
    ↓ user_id
pool_wpkl_predictions ←→ pool_wpkl_matches
                              ↓ matchtype_id
                         pool_wpkl_matchtypes
                              ↓ home/away_team_id
                         pool_wpkl_teams
                              ↑ team_id
                         pool_wpkl_team_aliases
                         (alias_name → canonical team)

pool_wpkl_rankings
    ↓ (via rankings_matches / rankings_bonusquestions)
pool_wpkl_matches + pool_wpkl_bonusquestions

pool_wpkl_scorehistory_s1_t1/t2
    (calculated snapshot: user_id, ranking_id, points, position)

custom_competitions
    ↓ custom_competition_id
custom_competition_users ← wpkl_users.ID

WooCommerce orders (wpkl_postmeta: sr_league_id)
    → triggers sr_grant_league_access()
    → writes wpkl_usermeta: sr_league_paid_<league_id>
    → inserts custom_competition_users row
```

---

## How Fixtures, Predictions, Users, Rankings, and Leagues Work

### Fixtures

1. Python scrapers run on schedule, fetching data from BBC Sport, RL.com, or LeagueUnlimited.
2. Each fixture is upserted into `pool_wpkl_matches` (insert if new, update `play_date` if changed).
3. Team names are resolved via `pool_wpkl_team_aliases` first, then by exact match in `pool_wpkl_teams`.
4. Missing teams are auto-created in `pool_wpkl_teams` if not found (risky — can create duplicates).
5. The admin can also add/edit fixtures manually through the Football Pool WordPress admin.

### Predictions

1. Logged-in users access the predictions page (rendered by the custom predictions plugin via shortcode).
2. On each score input change, an AJAX request fires to `save_prediction_handler`, which upserts a row in `pool_wpkl_predictions`.
3. Joker assignment sends a separate AJAX call to `update_joker`.
4. Predictions are locked for a match once its `play_date` passes (match becomes non-editable).

### Score Calculation

1. After match results are entered (manually or by script), an admin triggers score recalculation.
2. This is either via the WordPress admin dashboard or via WP-CLI: `wp football-pool calc` (run by `football-pool.sh`).
3. The `Football_Pool_Admin_Score_Calculation::process()` function iterates all users × all scored matches, calculates points using the configured scoring formula, and writes results to the inactive `scorehistory` table.
4. Once complete, the tables are swapped — the new table becomes active.

### Rankings

1. Rankings are read from the active `scorehistory` table (or recalculated inline for the custom rankings shortcode).
2. The custom rankings plugin (`football-pool-custom-rankings`) bypasses the history table entirely and runs a live SQL query against `pool_wpkl_matches` and `pool_wpkl_predictions`, calculating scores on the fly.
3. Competition filtering is applied via `WHERE m.matchtype_id = %d`.
4. `DENSE_RANK()` is used as the ranking window function.

### Leagues (Public Pool)

- Users belong to a league via `pool_wpkl_league_users`.
- Rankings can be filtered to show only users in a specific league.

### Private Leagues

- Users join via `/join-leagues/` (free or paid).
- Paid leagues go through WooCommerce → Stripe → order status webhook → `sr_handle_wc_order_paid()` → `sr_grant_league_access()`.
- Rankings within a private league are shown at `/private-leagues/?competition=<id>`.

---

## Data Flow Between Systems

```
BBC Sport / RL.com / LeagueUnlimited
    ↓  (HTTP GET via Python requests)
real-score-updater*.py
    ↓  (mysql.connector)
pool_wpkl_matches (INSERT / UPDATE play_date)

[After results are in]
football-pool.sh → wp football-pool calc
    ↓
Football_Pool_Admin_Score_Calculation (PHP)
    ↓
pool_wpkl_scorehistory_s1_t1/t2

[User visits rankings page]
Custom Rankings Shortcode → Live SQL query → HTML table

[User submits prediction]
Browser AJAX → WordPress AJAX handler → pool_wpkl_predictions upsert

[User pays for private league]
Browser → WooCommerce Checkout → Stripe → Webhook
    → woocommerce_order_status_processing
    → sr_handle_wc_order_paid()
    → custom_competition_users INSERT
    → wpkl_usermeta sr_league_paid_<id> = 1
```

---

## Cron Jobs and Scheduled Tasks

All cron jobs are shell scripts designed to be run by the Hostinger cron scheduler (or via WP-CLI).

| Script                                | Schedule (assumed)  | What it does                                                           |
| ------------------------------------- | ------------------- | ---------------------------------------------------------------------- |
| `weeklyfixtures.sh`                   | Daily               | Runs `real-score-updater-rlcom-superleague.py` for today + next 6 days |
| `weeklyfixtures-rlcom-superleague.sh` | Daily               | Similar weekly fixture sync via RL.com                                 |
| `dailyscores.sh`                      | Daily               | Runs `scores.php` for yesterday to update completed match scores       |
| `football-pool.sh`                    | After scores update | Runs `wp football-pool calc` to recalculate all rankings               |
| `run_football_calc.sh`                | After calc          | Alias/duplicate of football-pool.sh                                    |
| `Yearly_fixtures.sh`                  | Season start        | Bulk-loads a full season's fixtures                                    |
| `run_cron_job.php`                    | Via web cron        | Web-triggered version of the scraper (3 days ahead)                    |

WordPress Action Scheduler (`wpkl_actionscheduler_*`) is used by WooCommerce and MailPoet for their own async jobs (order processing, email sending) but is not directly used by the SportsRush scraping pipeline.
