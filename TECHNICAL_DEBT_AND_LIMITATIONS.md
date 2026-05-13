# SportsRush — Technical Debt and Limitations

## Summary

SportsRush has been organically grown on top of WordPress and a third-party prediction plugin. The result is a platform where the core business logic is split across a third-party open-source plugin, multiple bespoke custom plugins, out-of-band Python scripts, and raw SQL queries embedded in PHP shortcodes. This creates significant coupling, duplication, and fragility risks as the platform scales.

---

## 1. Architecture Problems

### Dual Scoring Engine Divergence
The platform has **two scoring implementations**: the Football Pool plugin's history table pipeline and the custom SQL rankings shortcode. They use different code paths, potentially different formulas (joker multiplier is missing from the SQL path), and different trigger mechanisms. A change to the scoring logic must be made in both places simultaneously or the two will show different results to users and admins.

### No API Layer
All data access is direct PHP-to-MySQL via `$wpdb` or raw Python mysql.connector queries. There is no REST API, no data abstraction layer, and no separation between data access and presentation. Adding a mobile app or third-party integration would require either scraping the WordPress HTML or building an API from scratch.

### Business Logic in Shortcodes
Significant business logic — including the entire scoring formula and rankings calculation — lives inside a WordPress shortcode function. This means it executes on every page load for every visitor hitting the rankings page. There is no caching of the query result at the data layer (only at the full-page HTML level via LiteSpeed Cache).

### Plugin Interdependencies
Multiple custom plugins are implicitly coupled to each other and to specific database table structures:
- `football-pool-custom-rankings` assumes specific column names in `pool_wpkl_matches` and `pool_wpkl_predictions`.
- `private_league_rankings` assumes WooCommerce is active.
- `sportsrush-team-aliases` assumes the Python scraper writes to `pool_wpkl_team_aliases`.
- None of these dependencies are formally declared or version-checked.

### Out-of-Band Data Pipeline
The Python scraping pipeline runs **entirely outside WordPress** — it connects directly to the database using hard-coded credentials. This means:
- WordPress has no knowledge of or control over fixture imports.
- If the database schema changes (e.g. a column is renamed), both the PHP plugins and Python scripts must be updated independently.
- There is no transactional consistency guarantee between the scraper and the WordPress application.

---

## 2. WordPress / Plugin Limitations

### Football Pool Plugin Version Lock
The core Football Pool plugin (by Antoine Hurkmans) is an open-source GPL project not designed for Rugby League or the SportsRush scoring model. The platform has worked around its limitations with custom plugins that override or bypass its functionality. Updating the Football Pool plugin risks breaking all custom overrides. Not updating it creates security exposure.

### WP-CLI Dependency for Score Calculation
Triggering rankings recalculation requires running `wp football-pool calc` via WP-CLI. On shared hosting, WP-CLI must be run as a shell command. If the hosting environment changes, or PHP path changes, or WP-CLI version changes, the cron jobs will silently fail.

### WordPress Admin as Primary Interface
All league management, alias management, competition configuration, and fixture management is done through the WordPress admin dashboard. There is no purpose-built operations UI. Adding features requires custom plugin development.

### Full Site Editing Theme Complexity
The Soccer Club (FSE) theme stores layout in JSON-based block patterns and HTML templates. Non-technical admins can inadvertently break page layouts using the Gutenberg block editor. There is no design system or component library to ensure consistency.

### Plugin Bloat
The site has **50+ plugins installed**, many of which appear to be exploratory or no longer in use (e.g. `hostinger-ai-assistant`, `mojo-marketplace-wp-plugin`, `sql-executioner`, `string-locator`, `hello.php`). Each active plugin runs on every WordPress page load, adding PHP execution overhead, potential conflicts, and security surface area.

---

## 3. Scalability Concerns

### Shared Hosting Resource Limits
Hostinger shared hosting imposes CPU, memory, and connection limits. As user numbers and match counts grow:
- The live SQL rankings query (window function over all users × all matches) will become progressively slower.
- Score recalculation (iterating all users × all matches in PHP) may time out on large datasets.
- Concurrent users during popular match days will create MySQL connection pool pressure.

### No Horizontal Scaling
WordPress on shared hosting cannot be horizontally scaled. Adding more users means the single server must handle more load.

### No Read Replicas
All reads and writes go to the same MySQL instance. High-traffic ranking pages and write-heavy scraper runs compete for the same database connections.

### Score Recalculation Blocking
The Football Pool calculation process is single-threaded PHP. For large user bases, a full recalculation can take minutes. During this time, the `calculation_in_progress` lock prevents other calculations and the displayed rankings may be stale.

### No CDN for Dynamic Content
While LiteSpeed Cache can cache full HTML pages, any personalised content (logged-in user's ranking highlighted, their prediction status) cannot be fully cached. Personalised pages bypass full-page cache and hit PHP + MySQL on every request.

---

## 4. Security Concerns

### Hard-coded Database Credentials in Git
The Python scripts contain the production database username and password in plain text, committed to the Git repository. Anyone with read access to the repository has the database credentials. This is a critical vulnerability.

### No Environment Variables Usage
There is no `.env` file pattern or environment variable injection. All configuration (DB credentials, API keys) is either in `wp-config.php` (for WordPress) or hard-coded in Python scripts.

### SQL Executioner Plugin
The `sql-executioner` plugin allows arbitrary SQL execution from the WordPress admin. If any admin account is compromised, an attacker has direct database read/write access through the WordPress UI. This plugin should not exist on a production server.

### Query Monitor Plugin on Production
The `query-monitor` plugin is a developer tool that exposes detailed database query information, PHP errors, and hook data. It should not be active on production as it can leak implementation details.

### BBC Scraper Running as Web Request
`run_cron_job.php` is a web-accessible PHP file that triggers the Python scraper. If this URL is discovered, anyone can trigger arbitrary scraper runs (denial of service on the database, unnecessary external HTTP requests). It has no authentication.

### WordPress User Enumeration
Standard WordPress user enumeration (`/?author=1`) is not explicitly blocked. Combined with login forms, this aids brute-force attacks.

### WooCommerce with Stripe: PCI Scope
Using Stripe's payment gateway means card data never touches the SportsRush servers (Stripe handles tokenisation). However, the WooCommerce + WordPress stack itself must remain patched to avoid vulnerabilities in the checkout flow.

---

## 5. Mobile Limitations

- There is no native mobile app. The site is a responsive website only.
- The WordPress FSE theme and Gutenberg blocks may not render optimally on small screens without custom mobile CSS overrides.
- The prediction entry UX (typing scores into form fields) is functional but not optimised for touch input or mobile keyboards.
- Push notifications for results, score changes, or ranking movements are not possible without a native app or Progressive Web App (PWA) implementation.
- Real-time score updates during a match are not available — there is no WebSocket or polling mechanism in the frontend.

---

## 6. Technical Debt

### No Test Coverage
There are no automated tests of any kind — no unit tests for the PHP scoring logic, no integration tests for the scraper pipeline, no end-to-end tests. Regressions in the scoring formula or fixture ingestion are only caught when users notice incorrect results.

### Copy-Paste Scoring Logic
The scoring formula is duplicated across multiple files: once in the Football Pool PHP calculation class, once in the custom rankings SQL query, and possibly in the monthly winner SQL block. Any change to the scoring model must be propagated manually to all three locations.

### Multiple WordPress Installations in the Same Document Root
Having `devin/`, `staging/`, `rent/`, and `yourclubhere/` WordPress installations nested inside the production document root is a serious operational risk. A vulnerability in any of these sub-installations could compromise the production environment. They share the same filesystem and (potentially) the same database server.

### Log Files in Web Root
Script log files (`.log`) are stored in `public_html/scripts/` which is under the web root. These logs may contain database query details, team names, error messages, and timestamps. If directory indexing is enabled on Hostinger, these logs are publicly readable.

### No Schema Migrations
Database schema changes are applied manually (via `ALTER TABLE` directly, or via WordPress plugin activation hooks that use `dbDelta`). There is no version-controlled migration system. Rolling back a schema change is manual. The round columns (`round`, `round_name`, `round_order`) were added ad-hoc and the scraper has to detect their presence at runtime with `SHOW COLUMNS`.

### Cron Job Fragility
The shell scripts use absolute paths (`/home/u108848352/domains/sportsrush.co.uk/public_html/`) and a specific Python interpreter path (`/opt/alt/python311/bin/python3`). If Hostinger changes the directory structure, Python version, or account paths, all cron jobs silently break.

---

## 7. Areas Most Difficult to Maintain

| Area | Difficulty | Reason |
|------|-----------|--------|
| Scoring formula changes | Very High | Logic duplicated across PHP + SQL; joker discrepancy between paths |
| Scraper reliability | Very High | Depends on BBC/RL.com HTML structure; breaks on any frontend redesign of those sites |
| Team alias management | High | Manual process; auto-creation can silently corrupt team data |
| Plugin compatibility | High | 50+ plugins; Football Pool core + 10 custom overrides; any update can cascade |
| Score recalculation timing | High | Two-step manual process (scores → calc); failure in either step silently breaks rankings |
| Private league payment edge cases | High | Refunds, failed sessions, duplicate orders all require manual admin intervention |
| Adding mobile support | Very High | No API layer; all data is tightly coupled to WordPress rendering; no PWA or app infrastructure |
| Database scaling | Very High | Shared hosting ceiling; no query optimisation infrastructure; live SQL in shortcodes |
