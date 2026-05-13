# SportsRush — Fixture and Results Pipeline

## Overview

The fixture and results pipeline is entirely custom-built in Python and Bash. It runs outside WordPress, directly connecting to the MariaDB database. There is no commercial sports data API subscription as the primary source — instead, the system scrapes public-facing web pages from BBC Sport, RL.com (rugbyleague.com), and LeagueUnlimited.

---

## How Fixtures Are Imported

### Primary Scraper: `real-score-updater-rlcom-superleague.py`

This is the main production scraper. It is invoked by `weeklyfixtures.sh`, which runs it once per day for each of the next 7 days (today + 6 ahead):

```bash
for ((i=0; i<7; i++)); do
  DATE=$(date -d "$START_DATE +$i day" +%Y-%m-%d)
  python3 real-score-updater-rlcom-superleague.py $DATE
done
```

**Data Sources:**
1. **RL.com (rugbyleague.com)** — The primary source for Super League and other Rugby League competitions. The scraper fetches the match centre page for each active competition (URL stored in `pool_wpkl_scrape_competitions.rlcom_url`).
2. **LeagueUnlimited** — A secondary/fallback source that provides round information and fixture timing data in a different HTML format.

**Process for each scrape date:**
1. Load active competitions from `pool_wpkl_scrape_competitions` (filtered by `active = 1` and date range).
2. For each active competition, fetch the relevant RL.com or LeagueUnlimited page.
3. Parse fixture blocks from the HTML using BeautifulSoup heuristics (see below).
4. For each fixture found on the target date, resolve team names to IDs via the alias table.
5. Convert match time from UK local time (Europe/London, BST/GMT aware) to UTC.
6. Check `pool_wpkl_matches` for an existing row within a 336-hour window (14 days) with the same teams and competition.
7. If found: update `play_date` if changed, update round columns if available.
8. If not found: insert a new row.

### Secondary Scraper: `real-score-updater.py`

An older, simpler version that scrapes the **BBC Sport Rugby League** scores and fixtures page:
```
https://www.bbc.co.uk/sport/rugby-league/scores-fixtures/{YYYY-MM-DD}
```

It uses hardcoded BBC CSS class names to find match blocks. Because BBC's class names are generated (e.g. `ssrcss-1bjtunb-GridContainer e1efi6g55`) and can change without notice when BBC updates their frontend, this scraper is fragile. It uses the same upsert logic but with a tighter 3-day window for duplicate checking.

This scraper is retained as a fallback or for competitions not covered by RL.com.

### Flashscore Scraper: `flashscore_add_rounds.py`

A specialised one-off tool that enriches existing match records with **round numbers** by scraping Flashscore. It matches scraped fixtures to database records using a 60-minute time-window tolerance and updates the `round` column. Not run as a regular cron job — used manually when round data is missing.

### API-Based Alternative: `superleague_fixtures.py`

Uses the `rugby.api-sports.io` REST API with an API key to fetch Super League fixtures. This was an exploratory approach — whether it is currently active in production is unclear. If active, it would provide structured data without web scraping fragility. A `sportradar_test.py` script also exists as a proof-of-concept for the Sportradar API.

---

## HTML Parsing Approach (RL.com Heuristics)

The RL.com scraper uses a **heuristic-based DOM traversal** rather than relying on stable CSS class names, because RL.com's structure is irregular:

1. First, find all `<span class="venue-label">` elements. Walk up the DOM tree (up to 18 parent levels) to find the closest ancestor that looks like a fixture container (contains a match time, has at least 2 `<span class="team-name">` elements, and is not too large).
2. If no venue labels are found, fall back to finding `<span class="team-name">` and walking up similarly.
3. Deduplicate found blocks using DOM node identity.
4. Within each block: extract home/away team names (prefer `d-none d-lg-block` desktop spans), match time (24-hour regex), venue, and round number.
5. Date is found by scanning backwards through the DOM from each fixture block for a heading matching a date pattern (e.g. "Sat 12th April 2025").

**LeagueUnlimited parsing** is more structured: walk the DOM in order, detect round headers (`<h2 class="Round 10">` etc.), then collect `<a class="full-draw-game">` elements under each round header.

---

## How Results Are Updated

Results (scores) follow a different flow from fixtures. The key mechanism is `scores.php`, a PHP script run by `dailyscores.sh`:

```bash
# dailyscores.sh
php scores.php --date=$(date -d "yesterday" +%Y-%m-%d)
```

This updates `home_score` and `away_score` in `pool_wpkl_matches` for completed matches. Once scores are set (non-NULL), the Football Pool plugin considers those matches as having results.

After `scores.php` runs, `football-pool.sh` triggers:
```bash
wp football-pool calc
```
This triggers the score recalculation pipeline in the Football Pool plugin, updating all scorehistory tables.

---

## Team Alias Handling

Team name normalisation is a critical step because different data sources use different names for the same team (e.g. "Hull KR", "Hull Kingston Rovers", "Kingston Rovers").

**Resolution order in the scraper:**
1. Check `pool_wpkl_team_aliases` for the exact scraped name → returns canonical `team_id`.
2. If not found, check `pool_wpkl_teams.name` for an exact match → if found, also register an alias for next time.
3. If neither found, **auto-create a new team** in `pool_wpkl_teams` and register an alias.

**Risk:** Step 3 can create phantom duplicate teams if a new alias is not mapped correctly. For example, "Leigh Leopards" and "Leigh" might both end up as separate team entries, causing match lookup failures and missed fixture updates.

**Admin tooling:** The Team Aliases admin plugin provides:
- A manual alias management UI (add, edit, delete aliases).
- An Alias Scanner that reads `rlcom_alias_scan_out.json` (generated by `rlcom-alias-scan.py`) and presents any unmapped scraped names for the admin to map to canonical teams.

---

## Round Handling

Round data is stored in three optional columns on `pool_wpkl_matches`:
- `round` (INT) — numeric round number (e.g. 10)
- `round_name` (VARCHAR) — display text (e.g. "Round 10", "Quarter Final")
- `round_order` (INT) — sort order (usually equals numeric round; for cup stages, assigned in first-seen order)

The scraper detects which columns exist at runtime via `SHOW COLUMNS FROM pool_wpkl_matches` — if a column is absent, it skips writing that field without error. This means the round schema is an optional extension that was bolted on after the initial deployment.

For non-numeric rounds (cup stages like "Quarter Final"), `round_name` carries the label and `round_order` is assigned sequentially in the order the scraper first encounters each new round name within a session.

---

## Error Handling

### Python Scrapers
- Database connection failures are logged and the script exits early.
- HTTP failures (non-200 responses) are logged and the competition is skipped.
- Parse errors per fixture block (`AttributeError`) are caught, logged, and the block is skipped — other fixtures in the same run are unaffected.
- Team auto-creation uses `INSERT IGNORE` to avoid duplicate key errors.
- Timezone conversion uses `pytz` with explicit BST/GMT disambiguation using `try/except AmbiguousTimeError` and `NonExistentTimeError`.
- All logs are written to `.log` files in `public_html/scripts/`.

### Missing Competitions
If `pool_wpkl_scrape_competitions` has no active entries for a given date, the scraper exits early with a warning. Competitions must be manually activated in the WordPress admin via the Scraper Competitions Admin UI.

### Competitions Table Discovery
The scraper attempts to find the competitions table by probing a list of candidate names (`wpkl_pool_wpkl_scrape_competitions`, `pool_wpkl_scrape_competitions`, etc.) because the WordPress table prefix varies between installations (the `devin/` sandbox uses a different prefix). This is a pragmatic workaround for running the same script across multiple environments.

---

## Known Weaknesses

### 1. Hard-coded Database Credentials
Database credentials (`u108848352_Ewka1` / `WhuiMoFs0X`) are stored in plain text in the Python scraper files and committed to the Git repository. This is a critical security issue.

### 2. BBC CSS Class Dependency
The `real-score-updater.py` scraper uses specific BBC Sport CSS class names that are auto-generated by their build system and can change at any BBC frontend deployment. A BBC change will silently break the scraper with zero matches found.

### 3. No Score-Result Scraper in RL.com Script
The main scraper (`real-score-updater-rlcom-superleague.py`) only handles **fixtures** (upcoming matches). The mechanism for populating `home_score` and `away_score` for completed matches relies on `scores.php` which uses a separate, simpler scraper. If `scores.php` fails, scores remain NULL indefinitely and users cannot earn points.

### 4. No Retry Logic
If an HTTP request to RL.com or BBC times out, the competition is skipped for that run. There is no retry mechanism or alerting. Failures are only visible in the log files.

### 5. Team Auto-Creation Risk
Auto-creating teams on alias miss can pollute the `pool_wpkl_teams` table with near-duplicate entries, breaking the alias resolution chain and causing future matches for those teams to fail insertion.

### 6. Timezone Edge Cases
The BST↔GMT transition is handled but uses `is_dst=False` (assume GMT) for ambiguous times and `is_dst=True` (assume BST) for non-existent times. This is a reasonable heuristic but means matches scheduled exactly during a clock-change may have a 1-hour offset in UTC.

### 7. No Data Validation
There is no validation that scraped scores are realistic (e.g. scores in the hundreds, or negative values). Corrupt HTML could theoretically write invalid data.

### 8. Manual Admin Trigger for Score Calculation
After `scores.php` updates match scores, a separate `wp football-pool calc` must be triggered. If the cron schedule slips (both must succeed sequentially), rankings will be stale.

### 9. No Idempotency Guarantee
Running the fixture scraper multiple times for the same date is generally safe (it checks for existing matches), but the 336-hour deduplication window could theoretically cause issues if the same teams play twice within 14 days (e.g. cup replay after a draw).
