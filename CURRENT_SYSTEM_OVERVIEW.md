# SportsRush 2.0 — Current System Overview

## What Is SportsRush?

SportsRush (sportsrush.co.uk) is a UK-based sports prediction community platform, primarily focused on Rugby League (Super League and associated competitions). It allows registered users to predict match scores, compete on leaderboards, join private prediction leagues, earn achievements, and follow live results. The platform operates as a WordPress site with a significant layer of custom plugins and external Python scraping scripts that keep match data current.

---

## Main Technologies Used

| Layer | Technology |
|-------|-----------|
| CMS / Framework | WordPress (latest, running on PHP 8.2) |
| Database | MariaDB 10.11 (hosted on Hostinger shared hosting) |
| Table Prefix | `wpkl_` (WordPress core), `pool_wpkl_` (Football Pool plugin) |
| Server | Hostinger shared hosting (`u108848352` account) |
| Theme | Soccer Club v1.3.8 (Full Site Editing / Gutenberg blocks) |
| Core Prediction Engine | Football Pool plugin (by Antoine Hurkmans) |
| Custom Plugins | 10+ bespoke plugins written by the SportsRush team |
| Data Scraping | Python 3.11 scripts (BeautifulSoup, requests, mysql.connector) |
| Email | FluentSMTP + MailPoet |
| Payments | WooCommerce + WooCommerce Stripe Gateway v10.3.1 |
| Charts / Viz | Highcharts JS plugin |
| SEO | All-in-One SEO Pack |
| Caching | LiteSpeed Cache |
| Backups | UpdraftPlus, BlogVault, All-in-One WP Migration |

---

## How the Frontend Works

The frontend is a **WordPress Full Site Editing (FSE)** site using the **Soccer Club** theme by WP Radiant. This means:

- Page layouts are defined by HTML template files in `wp-content/themes/soccer-club/templates/` (e.g. `front-page.html`, `single.html`, `page.html`).
- Global styling is controlled via `theme.json` (colours, typography, spacing) and a `style.css` for custom CSS variables. The site uses the fonts **Abel** and **Teko**.
- Content areas are rendered using Gutenberg blocks and shortcodes injected by the Football Pool and custom plugins.
- Dynamic sections (predictions form, rankings tables, private leagues, gamification widgets) are rendered server-side via PHP shortcodes that output HTML.
- JavaScript is minimal; the Football Pool plugin provides AJAX-based prediction auto-save and joker toggling. jQuery is used throughout (bundled with WordPress).
- Highcharts JS is used for any chart/graph displays (e.g. points-over-time visualisations).
- There is a custom mobile menu plugin but no dedicated mobile app — the site is responsive only.

### Key Shortcodes Used on Pages

| Shortcode | Purpose |
|-----------|---------|
| `[football_pool_rankings]` | Custom competition-filtered rankings table |
| `[private_league_rankings]` | Per-user private league rankings view |
| `[football_pool_enhanced_homepage]` | Sponsor/news slider on homepage |
| `[team_alias_manager]` | Admin-only alias management UI (front-end embed) |

---

## How the Backend Works

The backend is entirely PHP/WordPress. There is no separate API server. All business logic is executed by WordPress action hooks, shortcodes, and AJAX handlers defined in the custom plugins. Key backend components:

### Football Pool Plugin (Core Engine)
Located at `wp-content/plugins/football-pool/`. This is an open-source plugin (GPL v3) that manages:
- Match fixtures and results (`pool_wpkl_matches`)
- User predictions (`pool_wpkl_predictions`)
- Scoring calculation (PHP class `Football_Pool_Admin_Score_Calculation`)
- Rankings history (`pool_wpkl_scorehistory_s1_t1` / `_s1_t2`)
- Leagues, bonus questions, shoutbox

### Custom Prediction Plugin
`Football-pool-custom-predictions` — Replaces the Football Pool plugin's default prediction UI with an AJAX auto-save interface. Users type scores and predictions are committed to the database immediately without a form submit.

### Custom Rankings Plugin
`football-pool-custom-rankings` — Overrides the default rankings display. Implements the custom scoring formula (50/20/10/10/20 points) directly as a SQL query using `DENSE_RANK() OVER (ORDER BY total_points DESC)` rather than relying on the Football Pool plugin's history tables alone. Includes competition filtering via `?competition=ID` query parameters and monthly winner calculation.

### Private Leagues Plugin
`private_league_rankings` — Provides paid and free private mini-leagues on top of the main pool. Uses WooCommerce for payment and stores members in custom tables (`custom_competitions`, `custom_competition_users`). Admin can create leagues, set prices, and manage membership.

### Gamification Plugin
`sportsrush-gamification` — Adds engagement features: rivals, streaks, achievements, daily pick, banter summaries, mini-leaderboards, and notifications.

### Team Aliases Plugin
`sportsrush-team-aliases` — WordPress admin UI to manage the `pool_wpkl_team_aliases` table, mapping names that scrapers encounter (e.g. "Hull KR") to the canonical team record (e.g. "Hull Kingston Rovers"). Includes an Alias Scanner that reads the output JSON from `rlcom-alias-scan.py`.

### Scraper Competitions Admin Plugin
`scraper-competitions-admin` — WordPress admin UI to manage the `pool_wpkl_scrape_competitions` table, which controls which competitions the scrapers are active for and over what date ranges.

---

## Hosting and Deployment Assumptions

- Hosted on **Hostinger shared hosting** under the account `u108848352`.
- The primary domain is `sportsrush.co.uk` with the web root at `/home/u108848352/domains/sportsrush.co.uk/public_html/`.
- The database runs on localhost (`127.0.0.1`) — credentials are currently hard-coded in the Python scraper files.
- There are multiple WordPress installations within the same file system:
  - `public_html/` — the main production site
  - `public_html/devin/` — a development/staging version (same plugin set, used for testing)
  - `public_html/staging/` — another staging environment
  - `public_html/rent/` and `public_html/yourclubhere/` — exploratory multi-tenant sub-sites
- Cron jobs are presumably configured via Hostinger's cron scheduler (or WP-Cron) to run the shell scripts in `public_html/scripts/` on schedule.
- SSL is managed via the `wp-letsencrypt-ssl` plugin.
- The `deploy.sh` script at the root uses `rsync` for deploying to Hostinger — suggesting development happens locally or in another environment and is pushed manually.

---

## How Users Interact With the System

1. **Registration & Login** — Users register via a custom registration form (`custom-registration-form-builder-with-submission-manager`). Social login via Facebook OAuth is available (`nextend-facebook-connect`). Login/logout is integrated into the navigation menu.

2. **Making Predictions** — Registered users visit the predictions page, see a list of upcoming matches filtered by competition and round, and enter their predicted home/away scores. The custom predictions plugin auto-saves each entry via AJAX. Users can assign a "joker" multiplier to one match per round.

3. **Viewing Rankings** — The rankings page shows a competition-filtered leaderboard (using `?competition=ID`). The logged-in user's row is highlighted. Monthly and overall winner lines appear at the top.

4. **Private Leagues** — Users can browse available private leagues at `/join-leagues/`. Free leagues are joined directly; paid leagues go through WooCommerce checkout (Stripe payment). After joining, users see their league's rankings at `/private-leagues/`.

5. **Gamification** — Users can view rivals (closest competitors), track streaks, earn achievements, and see AI-generated "banter" summaries about their performance.

6. **Admin** — Site admins manage fixtures, results, competitions, team aliases, scraper configuration, and trigger score recalculations from the WordPress dashboard.
